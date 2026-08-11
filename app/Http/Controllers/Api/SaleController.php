<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function searchProducts(Request $request): JsonResponse
    {
        $request->validate([
            'sede_id' => ['required', 'integer', 'exists:sedes,id'],
            'q' => ['required', 'string', 'min:1'],
        ]);

        $search = trim((string) $request->query('q'));
        $sedeId = (int) $request->query('sede_id');

        $products = Product::query()
            ->with([
                'category:id,nombre',
                'stocks' => function ($query) use ($sedeId) {
                    $query->where('sede_id', $sedeId);
                },
            ])
            ->where('active', true)
            ->where(function ($query) use ($search) {
                $query->where('nombre', 'like', '%' . $search . '%')
                    ->orWhere('codigo_interno', 'like', '%' . $search . '%')
                    ->orWhere('codigo_barras', 'like', '%' . $search . '%');
            })
            ->limit(20)
            ->get()
            ->map(function ($product) use ($sedeId) {
                $stockRow = $product->stocks->firstWhere('sede_id', $sedeId);

                return [
                    'id' => $product->id,
                    'nombre' => $product->nombre,
                    'codigo_interno' => $product->codigo_interno,
                    'codigo_barras' => $product->codigo_barras,
                    'precio_oficial' => $product->precio_oficial,
                    'category' => $product->category,
                    'stock_disponible' => $stockRow?->stock ?? 0,
                    'active' => $product->active,
                ];
            })
            ->values();

        return response()->json([
            'products' => $products,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $sales = Sale::query()
            ->with([
                'user:id,name,email',
                'sede:id,nombre',
                'items.product:id,nombre,codigo_interno,codigo_barras',
            ])
            ->latest()
            ->get();

        return response()->json([
            'sales' => $sales,
        ]);
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        $sale = DB::transaction(function () use ($request) {
            $itemsPayload = $request->input('items');
            $itemsToInsert = [];
            $total = 0;

            foreach ($itemsPayload as $item) {
                $product = Product::query()
                    ->where('id', $item['product_id'])
                    ->where('active', true)
                    ->first();

                if (! $product) {
                    abort(422, 'Uno de los productos no existe o está inactivo.');
                }

                $stock = ProductStock::query()
                    ->where('product_id', $product->id)
                    ->where('sede_id', $request->integer('sede_id'))
                    ->lockForUpdate()
                    ->first();

                if (! $stock || $stock->stock < (int) $item['cantidad']) {
                    abort(422, 'Stock insuficiente para el producto: ' . $product->nombre);
                }

                $precioOficial = (float) $product->precio_oficial;
                $precioVendido = (float) $item['precio_vendido'];
                $cantidad = (int) $item['cantidad'];
                $subtotal = $precioVendido * $cantidad;

                $stock->stock = $stock->stock - $cantidad;
                $stock->save();

                $itemsToInsert[] = [
                    'product_id' => $product->id,
                    'precio_oficial' => $precioOficial,
                    'precio_vendido' => $precioVendido,
                    'cantidad' => $cantidad,
                    'subtotal' => $subtotal,
                ];

                $total += $subtotal;
            }

            $sale = Sale::query()->create([
                'user_id' => $request->user()->id,
                'sede_id' => $request->integer('sede_id'),
                'total' => $total,
                'comprobante_path' => null,
            ]);

            foreach ($itemsToInsert as $itemData) {
                SaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'product_id' => $itemData['product_id'],
                    'precio_oficial' => $itemData['precio_oficial'],
                    'precio_vendido' => $itemData['precio_vendido'],
                    'cantidad' => $itemData['cantidad'],
                    'subtotal' => $itemData['subtotal'],
                ]);
            }

            return $sale;
        });

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Registro de venta',
            'modelo' => Sale::class,
            'modelo_id' => $sale->id,
            'detalle' => 'Se registró una venta con total de S/ ' . $sale->total,
        ]);

        return response()->json([
            'message' => 'Venta registrada correctamente.',
            'sale' => $sale->load([
                'user:id,name,email',
                'sede:id,nombre',
                'items.product:id,nombre,codigo_interno,codigo_barras',
            ]),
        ], 201);
    }

    public function show(Sale $sale): JsonResponse
    {
        $sale->load([
            'user:id,name,email',
            'sede:id,nombre',
            'items.product:id,nombre,codigo_interno,codigo_barras',
        ]);

        return response()->json([
            'sale' => $sale,
        ]);
    }
}
