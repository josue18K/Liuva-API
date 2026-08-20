<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\CashRegisterDenomination;
use App\Models\InventoryAdjustment;
use App\Models\InventoryMovement;
use App\Models\License;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResetSystemController extends Controller
{
    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'confirm' => ['required', 'boolean'],
        ]);

        if (!$validated['confirm']) {
            abort(422, 'Debes confirmar el reinicio del sistema.');
        }

        $adminId = $request->user()->id;

        DB::transaction(function () use ($adminId) {
            // Eliminar en orden correcto por dependencias
            CashRegisterDenomination::query()->delete();
            SaleItem::query()->delete();
            Sale::query()->delete();
            CashRegister::query()->delete();
            InventoryMovement::query()->delete();
            InventoryAdjustment::query()->delete();
            ProductStock::query()->delete();
            Product::query()->delete();
            License::query()->delete();

            // Conservar configuraciones
            // Setting::query()->delete(); // No eliminar configuraciones

            ActivityLog::query()->create([
                'user_id' => $adminId,
                'accion' => 'Reinicio del sistema',
                'modelo' => null,
                'modelo_id' => null,
                'detalle' => 'Se reiniciaron ventas, cajas, productos, licencias e inventario. Se conservó el administrador y configuraciones.',
            ]);
        });

        return response()->json([
            'message' => 'Sistema reiniciado correctamente. Se conservaron las cuentas de administrador y la configuración.',
        ]);
    }
}
