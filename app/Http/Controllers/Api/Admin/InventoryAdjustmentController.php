<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryAdjustmentRequest;
use App\Http\Requests\UpdateInventoryAdjustmentRequest;
use App\Models\ActivityLog;
use App\Models\InventoryAdjustment;
use App\Models\ProductStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InventoryAdjustmentController extends Controller
{
    public function index(): JsonResponse
    {
        $adjustments = InventoryAdjustment::query()
            ->with([
                'product:id,nombre,codigo_interno,codigo_barras',
                'sede:id,nombre',
                'user:id,name,email',
            ])
            ->latest()
            ->get();

        return response()->json([
            'adjustments' => $adjustments,
        ]);
    }

    public function store(StoreInventoryAdjustmentRequest $request): JsonResponse
    {
        $adjustment = DB::transaction(function () use ($request) {
            $stock = ProductStock::query()->firstOrNew([
                'product_id' => $request->integer('product_id'),
                'sede_id' => $request->integer('sede_id'),
            ]);

            $stock->stock = ($stock->stock ?? 0) + $request->integer('cantidad');
            $stock->save();

            return InventoryAdjustment::query()->create([
                'product_id' => $request->integer('product_id'),
                'sede_id' => $request->integer('sede_id'),
                'user_id' => $request->user()->id,
                'tipo' => $request->string('tipo'),
                'cantidad' => $request->integer('cantidad'),
                'motivo' => $request->string('motivo'),
            ]);
        });

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Registro de ajuste de inventario',
            'modelo' => InventoryAdjustment::class,
            'modelo_id' => $adjustment->id,
            'detalle' => 'Se registró un ajuste manual de inventario.',
        ]);

        return response()->json([
            'message' => 'Ajuste de inventario registrado correctamente.',
            'adjustment' => $adjustment->load([
                'product:id,nombre,codigo_interno,codigo_barras',
                'sede:id,nombre',
                'user:id,name,email',
            ]),
        ], 201);
    }

    public function show(InventoryAdjustment $inventoryAdjustment): JsonResponse
    {
        return response()->json([
            'adjustment' => $inventoryAdjustment->load([
                'product:id,nombre,codigo_interno,codigo_barras',
                'sede:id,nombre',
                'user:id,name,email',
            ]),
        ]);
    }

    public function update(UpdateInventoryAdjustmentRequest $request, InventoryAdjustment $inventoryAdjustment): JsonResponse
    {
        DB::transaction(function () use ($request, $inventoryAdjustment) {
            $stock = ProductStock::query()->where([
                'product_id' => $inventoryAdjustment->product_id,
                'sede_id' => $inventoryAdjustment->sede_id,
            ])->firstOrFail();

            $stock->stock = $stock->stock - $inventoryAdjustment->cantidad + $request->integer('cantidad');

            if ($stock->stock < 0) {
                abort(422, 'El ajuste dejaría el stock en negativo.');
            }

            $stock->save();

            $inventoryAdjustment->update([
                'tipo' => $request->string('tipo'),
                'cantidad' => $request->integer('cantidad'),
                'motivo' => $request->string('motivo'),
            ]);
        });

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Actualización de ajuste de inventario',
            'modelo' => InventoryAdjustment::class,
            'modelo_id' => $inventoryAdjustment->id,
            'detalle' => 'Se actualizó un ajuste manual de inventario.',
        ]);

        return response()->json([
            'message' => 'Ajuste de inventario actualizado correctamente.',
            'adjustment' => $inventoryAdjustment->fresh()->load([
                'product:id,nombre,codigo_interno,codigo_barras',
                'sede:id,nombre',
                'user:id,name,email',
            ]),
        ]);
    }

    public function destroy(InventoryAdjustment $inventoryAdjustment): JsonResponse
    {
        DB::transaction(function () use ($inventoryAdjustment) {
            $stock = ProductStock::query()->where([
                'product_id' => $inventoryAdjustment->product_id,
                'sede_id' => $inventoryAdjustment->sede_id,
            ])->firstOrFail();

            $stock->stock = $stock->stock - $inventoryAdjustment->cantidad;

            if ($stock->stock < 0) {
                abort(422, 'No se puede eliminar este ajuste porque el stock quedaría negativo.');
            }

            $stock->save();
            $inventoryAdjustment->delete();
        });

        ActivityLog::query()->create([
            'user_id' => request()->user()->id,
            'accion' => 'Eliminación de ajuste de inventario',
            'modelo' => InventoryAdjustment::class,
            'modelo_id' => $inventoryAdjustment->id,
            'detalle' => 'Se eliminó un ajuste manual de inventario.',
        ]);

        return response()->json([
            'message' => 'Ajuste de inventario eliminado correctamente.',
        ]);
    }
}
