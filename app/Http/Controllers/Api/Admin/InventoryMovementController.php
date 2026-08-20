<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryMovementRequest;
use App\Models\ActivityLog;
use App\Models\InventoryMovement;
use App\Models\ProductStock;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryMovementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'sede_id' => ['nullable', 'integer', 'exists:sedes,id'],
            'tipo' => ['nullable', 'in:entrada,salida,ajuste'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $movements = InventoryMovement::query()
            ->with([
                'product:id,nombre,codigo_interno,codigo_barras',
                'sede:id,nombre',
                'user:id,name,email',
            ])
            ->when($request->user()->role === User::ROLE_SELLER, fn ($query) => $query->where('sede_id', $request->user()->sede_id))
            ->when(isset($validated['product_id']), fn ($query) => $query->where('product_id', $validated['product_id']))
            ->when(isset($validated['sede_id']), fn ($query) => $query->where('sede_id', $validated['sede_id']))
            ->when(isset($validated['tipo']), fn ($query) => $query->where('tipo', $validated['tipo']))
            ->latest()
            ->paginate($validated['per_page'] ?? 25)
            ->withQueryString();

        return response()->json(['movements' => $movements]);
    }

    public function store(StoreInventoryMovementRequest $request): JsonResponse
    {
        if ($request->user()->role === User::ROLE_SELLER && $request->user()->sede_id !== $request->integer('sede_id')) {
            abort(403, 'Solo puedes registrar inventario en tu sede asignada.');
        }

        $movement = DB::transaction(function () use ($request): InventoryMovement {
            ProductStock::query()->firstOrCreate([
                'product_id' => $request->integer('product_id'),
                'sede_id' => $request->integer('sede_id'),
            ], ['stock' => 0]);

            $stock = ProductStock::query()
                ->where('product_id', $request->integer('product_id'))
                ->where('sede_id', $request->integer('sede_id'))
                ->lockForUpdate()
                ->firstOrFail();

            $previous = $stock->stock;
            $type = $request->string('tipo')->toString();
            $new = match ($type) {
                InventoryMovement::TYPE_ENTRY => $previous + $request->integer('cantidad'),
                InventoryMovement::TYPE_EXIT => $previous - $request->integer('cantidad'),
                InventoryMovement::TYPE_ADJUSTMENT => $request->integer('stock_objetivo'),
            };

            if ($new < 0) {
                abort(422, 'Stock insuficiente para realizar la salida.');
            }

            $stock->update(['stock' => $new]);

            $movement = InventoryMovement::query()->create([
                'product_id' => $stock->product_id,
                'sede_id' => $stock->sede_id,
                'user_id' => $request->user()->id,
                'tipo' => $type,
                'cantidad' => abs($new - $previous),
                'stock_anterior' => $previous,
                'stock_nuevo' => $new,
                'origen_tipo' => 'manual',
                'motivo' => $request->string('motivo'),
            ]);

            ActivityLog::query()->create([
                'user_id' => $request->user()->id,
                'accion' => 'Movimiento de inventario',
                'modelo' => InventoryMovement::class,
                'modelo_id' => $movement->id,
                'detalle' => "{$type}: stock {$previous} → {$new}.",
            ]);

            return $movement;
        });

        return response()->json([
            'message' => 'Movimiento de inventario registrado correctamente.',
            'movement' => $movement->load(['product:id,nombre,codigo_interno', 'sede:id,nombre', 'user:id,name']),
        ], 201);
    }

    public function analytics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sede_id' => ['nullable', 'integer', 'exists:sedes,id'],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);
        $sedeId = $request->user()->role === User::ROLE_SELLER
            ? $request->user()->sede_id
            : ($validated['sede_id'] ?? null);
        $since = now()->subDays($validated['days'] ?? 30);

        $base = InventoryMovement::query()
            ->when($sedeId, fn ($query) => $query->where('sede_id', $sedeId))
            ->where('created_at', '>=', $since);

        $summary = (clone $base)
            ->selectRaw("COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN cantidad ELSE 0 END), 0) as entradas")
            ->selectRaw("COALESCE(SUM(CASE WHEN tipo = 'salida' THEN cantidad ELSE 0 END), 0) as salidas")
            ->selectRaw("COUNT(CASE WHEN tipo = 'ajuste' AND COALESCE(origen_tipo, '') != 'importacion' THEN 1 END) as conteos")
            ->first();

        $rotation = (clone $base)
            ->where('tipo', InventoryMovement::TYPE_EXIT)
            ->selectRaw('product_id, SUM(cantidad) as unidades_salida')
            ->groupBy('product_id')
            ->orderByDesc('unidades_salida')
            ->with('product:id,nombre,codigo_interno,codigo_barras')
            ->limit(10)
            ->get();

        $stockQuery = ProductStock::query()->when($sedeId, fn ($query) => $query->where('sede_id', $sedeId));

        return response()->json([
            'period_days' => $validated['days'] ?? 30,
            'entradas' => (int) $summary->entradas,
            'salidas' => (int) $summary->salidas,
            'conteos' => (int) $summary->conteos,
            'stock_total' => (int) (clone $stockQuery)->sum('stock'),
            'productos_con_stock' => (int) (clone $stockQuery)->where('stock', '>', 0)->count(),
            'mayor_rotacion' => $rotation,
        ]);
    }
}
