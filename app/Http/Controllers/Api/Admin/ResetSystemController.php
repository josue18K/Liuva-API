<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\CashRegisterDenomination;
use App\Models\Category;
use App\Models\InventoryAdjustment;
use App\Models\InventoryMovement;
use App\Models\License;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Sede;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResetSystemController extends Controller
{
    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'confirm' => ['required', 'boolean'],
            'confirmation_text' => ['required', 'in:REINICIAR LIUVA'],
        ]);

        if (! $validated['confirm']) {
            abort(422, 'Debes confirmar el reinicio del sistema.');
        }

        $adminId = $request->user()->id;

        DB::transaction(function () use ($adminId) {
            CashRegisterDenomination::query()->delete();
            SaleItem::query()->delete();
            Sale::query()->delete();
            CashRegister::query()->delete();
            InventoryMovement::query()->delete();
            InventoryAdjustment::query()->delete();
            ProductStock::query()->delete();
            Product::query()->delete();
            License::query()->delete();
            ActivityLog::query()->delete();
            User::query()->where('role', '!=', User::ROLE_ADMIN)->delete();
            Category::query()->delete();
            Sede::query()->delete();
            Setting::query()->delete();

            ActivityLog::query()->create([
                'user_id' => $adminId,
                'accion' => 'Reinicio del sistema',
                'modelo' => null,
                'modelo_id' => null,
                'detalle' => 'Se eliminaron todos los datos operativos. Se conservaron únicamente las cuentas administradoras y sus preferencias personales.',
            ]);
        });

        return response()->json([
            'message' => 'Sistema reiniciado correctamente. Se conservaron únicamente las cuentas administradoras y sus preferencias.',
        ]);
    }
}
