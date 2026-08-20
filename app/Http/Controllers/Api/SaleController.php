<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    public function searchProducts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sede_id' => ['required', 'integer', 'exists:sedes,id'],
            'q' => ['required', 'string', 'min:1', 'max:100'],
        ]);

        $sedeId = (int) $validated['sede_id'];
        $this->ensureSedeAccess($request, $sedeId);
        $search = trim($validated['q']);

        $products = Product::query()
            ->with(['category:id,nombre', 'stocks' => fn ($query) => $query->where('sede_id', $sedeId)])
            ->where('active', true)
            ->where(function ($query) use ($search) {
                $query->where('nombre', 'like', '%'.$search.'%')
                    ->orWhere('codigo_interno', 'like', '%'.$search.'%')
                    ->orWhere('codigo_barras', 'like', '%'.$search.'%');
            })
            ->limit(20)
            ->get()
            ->map(function (Product $product) use ($sedeId): array {
                $available = $product->stocks->firstWhere('sede_id', $sedeId)?->stock ?? 0;

                return [
                    'id' => $product->id,
                    'nombre' => $product->nombre,
                    'codigo_interno' => $product->codigo_interno,
                    'codigo_barras' => $product->codigo_barras,
                    'precio_oficial' => $product->precio_oficial,
                    'unidad' => $product->unidad,
                    'category' => $product->category,
                    'stock_disponible' => $available,
                    'stock_bajo' => $available <= $product->stock_minimo,
                ];
            });

        return response()->json(['products' => $products]);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sede_id' => ['nullable', 'integer', 'exists:sedes,id'],
            'forma_pago' => ['nullable', 'in:efectivo,yape,plin,transferencia'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $sales = Sale::query()
            ->with(['user:id,name,email', 'sede:id,nombre', 'items.product:id,nombre,codigo_interno,codigo_barras'])
            ->when($request->user()->role === User::ROLE_SELLER, fn ($query) => $query->where('user_id', $request->user()->id))
            ->when(isset($validated['sede_id']), fn ($query) => $query->where('sede_id', $validated['sede_id']))
            ->when(isset($validated['forma_pago']), fn ($query) => $query->where('forma_pago', $validated['forma_pago']))
            ->latest()
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return response()->json(['sales' => $sales]);
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        $sedeId = $request->integer('sede_id');
        $this->ensureSedeAccess($request, $sedeId);

        $sale = DB::transaction(function () use ($request, $sedeId): Sale {
            $cashRegisterId = CashRegister::query()
                ->where('user_id', $request->user()->id)
                ->where('sede_id', $sedeId)
                ->where('tipo', 'apertura')
                ->whereDoesntHave('children')
                ->latest('fecha_hora')
                ->value('id');

            $preparedItems = [];
            $totalCents = 0;

            foreach ($request->input('items') as $item) {
                $product = Product::query()->whereKey($item['product_id'])->where('active', true)->first();

                if (! $product) {
                    abort(422, 'Uno de los productos no existe o está inactivo.');
                }

                $stock = ProductStock::query()
                    ->where('product_id', $product->id)
                    ->where('sede_id', $sedeId)
                    ->lockForUpdate()
                    ->first();
                $quantity = (int) $item['cantidad'];

                if (! $stock) {
                    $stock = ProductStock::query()->create([
                        'product_id' => $product->id,
                        'sede_id' => $sedeId,
                        'stock' => 0,
                    ]);
                }

                $previousStock = $stock->stock;
                $soldPriceCents = $this->toCents((string) $item['precio_vendido']);
                $officialPriceCents = $this->toCents($product->precio_oficial);
                $subtotalCents = $soldPriceCents * $quantity;
                $stock->update(['stock' => $previousStock - $quantity]);

                $preparedItems[] = compact(
                    'product', 'stock', 'quantity', 'previousStock',
                    'soldPriceCents', 'officialPriceCents', 'subtotalCents'
                );
                $totalCents += $subtotalCents;
            }

            $sale = Sale::query()->create([
                'user_id' => $request->user()->id,
                'sede_id' => $sedeId,
                'cash_register_id' => $cashRegisterId,
                'forma_pago' => $request->string('forma_pago'),
                'total' => $this->fromCents($totalCents),
                'comprobante_token' => (string) Str::uuid(),
            ]);
            $sale->update(['comprobante_numero' => 'V-'.str_pad((string) $sale->id, 8, '0', STR_PAD_LEFT)]);

            foreach ($preparedItems as $item) {
                SaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product']->id,
                    'precio_oficial' => $this->fromCents($item['officialPriceCents']),
                    'precio_vendido' => $this->fromCents($item['soldPriceCents']),
                    'cantidad' => $item['quantity'],
                    'subtotal' => $this->fromCents($item['subtotalCents']),
                ]);

                InventoryMovement::query()->create([
                    'product_id' => $item['product']->id,
                    'sede_id' => $sedeId,
                    'user_id' => $request->user()->id,
                    'tipo' => InventoryMovement::TYPE_EXIT,
                    'cantidad' => $item['quantity'],
                    'stock_anterior' => $item['previousStock'],
                    'stock_nuevo' => $item['stock']->stock,
                    'origen_tipo' => 'venta',
                    'origen_id' => $sale->id,
                    'motivo' => 'Salida automática por venta '.$sale->comprobante_numero,
                ]);
            }

            ActivityLog::query()->create([
                'user_id' => $request->user()->id,
                'accion' => 'Registro de venta manual',
                'modelo' => Sale::class,
                'modelo_id' => $sale->id,
                'detalle' => 'Venta manual '.$sale->comprobante_numero.' por S/ '.$sale->total.'.',
            ]);

            return $sale;
        });

        return response()->json([
            'message' => 'Venta manual registrada correctamente.',
            'sale' => $this->loadSale($sale),
        ], 201);
    }

    public function show(Sale $sale, Request $request): JsonResponse
    {
        if ($request->user()->role === User::ROLE_SELLER && $sale->user_id !== $request->user()->id) {
            abort(404);
        }

        return response()->json(['sale' => $this->loadSale($sale)]);
    }

    private function ensureSedeAccess(Request $request, int $sedeId): void
    {
        if ($request->user()->role === User::ROLE_SELLER && $request->user()->sede_id !== $sedeId) {
            abort(403, 'No tienes permisos para operar en esta sede.');
        }
    }

    private function loadSale(Sale $sale): Sale
    {
        return $sale->load(['user:id,name,email', 'sede:id,nombre', 'items.product:id,nombre,codigo_interno,codigo_barras']);
    }

    private function toCents(string $amount): int
    {
        [$whole, $decimal] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($decimal, 0, 2), 2, '0');
    }

    private function fromCents(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
