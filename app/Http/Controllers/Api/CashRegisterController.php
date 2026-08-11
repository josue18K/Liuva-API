<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CloseCashRegisterRequest;
use App\Http\Requests\OpenCashRegisterRequest;
use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\CashRegisterDenomination;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashRegisterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CashRegister::query()
            ->with([
                'user:id,name,email',
                'sede:id,nombre',
                'parent:id,fecha_hora,tipo',
                'denominations',
            ])
            ->latest();

        if ($request->user()->role === 'vendedor') {
            $query->where('user_id', $request->user()->id);
        }

        $cashRegisters = $query->get();

        return response()->json([
            'cash_registers' => $cashRegisters,
        ]);
    }

    public function open(OpenCashRegisterRequest $request): JsonResponse
    {
        $existingOpen = CashRegister::query()
            ->where('user_id', $request->user()->id)
            ->where('sede_id', $request->integer('sede_id'))
            ->where('tipo', 'apertura')
            ->whereDoesntHave('children')
            ->exists();

        if ($existingOpen) {
            return response()->json([
                'message' => 'Ya tienes una caja abierta en esta sede.',
            ], 422);
        }

        $cashRegister = DB::transaction(function () use ($request) {
            $montoContado = collect($request->input('denominations'))
                ->sum(fn ($item) => ((float) $item['denominacion']) * ((int) $item['cantidad']));

            $cashRegister = CashRegister::query()->create([
                'parent_cash_register_id' => null,
                'user_id' => $request->user()->id,
                'sede_id' => $request->integer('sede_id'),
                'tipo' => 'apertura',
                'monto_esperado' => null,
                'monto_contado' => $montoContado,
                'diferencia' => null,
                'observaciones' => $request->string('observaciones'),
                'fecha_hora' => now(),
            ]);

            foreach ($request->input('denominations') as $item) {
                CashRegisterDenomination::query()->create([
                    'cash_register_id' => $cashRegister->id,
                    'denominacion' => $item['denominacion'],
                    'cantidad' => $item['cantidad'],
                    'subtotal' => ((float) $item['denominacion']) * ((int) $item['cantidad']),
                ]);
            }

            return $cashRegister;
        });

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Apertura de caja',
            'modelo' => CashRegister::class,
            'modelo_id' => $cashRegister->id,
            'detalle' => 'Se realizó una apertura de caja.',
        ]);

        return response()->json([
            'message' => 'Caja abierta correctamente.',
            'cash_register' => $cashRegister->load(['user:id,name,email', 'sede:id,nombre', 'denominations']),
        ], 201);
    }

    public function close(CloseCashRegisterRequest $request): JsonResponse
    {
        $opening = CashRegister::query()
            ->where('id', $request->integer('cash_register_id'))
            ->where('tipo', 'apertura')
            ->with('children')
            ->first();

        if (! $opening) {
            return response()->json([
                'message' => 'La apertura de caja no existe.',
            ], 404);
        }

        if ($opening->user_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'No tienes permisos para cerrar esta caja.',
            ], 403);
        }

        if ($opening->children()->exists()) {
            return response()->json([
                'message' => 'Esta caja ya fue cerrada.',
            ], 422);
        }

        $closing = DB::transaction(function () use ($request, $opening) {
            $montoContado = collect($request->input('denominations'))
                ->sum(fn ($item) => ((float) $item['denominacion']) * ((int) $item['cantidad']));

            $ventasDuranteTurno = Sale::query()
                ->where('user_id', $opening->user_id)
                ->where('sede_id', $opening->sede_id)
                ->whereBetween('created_at', [$opening->fecha_hora, now()])
                ->sum('total');

            $montoEsperado = (float) $opening->monto_contado + (float) $ventasDuranteTurno;
            $diferencia = $montoContado - $montoEsperado;

            $closing = CashRegister::query()->create([
                'parent_cash_register_id' => $opening->id,
                'user_id' => $opening->user_id,
                'sede_id' => $opening->sede_id,
                'tipo' => 'cierre',
                'monto_esperado' => $montoEsperado,
                'monto_contado' => $montoContado,
                'diferencia' => $diferencia,
                'observaciones' => $request->string('observaciones'),
                'fecha_hora' => now(),
            ]);

            foreach ($request->input('denominations') as $item) {
                CashRegisterDenomination::query()->create([
                    'cash_register_id' => $closing->id,
                    'denominacion' => $item['denominacion'],
                    'cantidad' => $item['cantidad'],
                    'subtotal' => ((float) $item['denominacion']) * ((int) $item['cantidad']),
                ]);
            }

            return $closing;
        });

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'accion' => 'Cierre de caja',
            'modelo' => CashRegister::class,
            'modelo_id' => $closing->id,
            'detalle' => 'Se realizó un cierre de caja.',
        ]);

        return response()->json([
            'message' => 'Caja cerrada correctamente.',
            'cash_register' => $closing->load(['user:id,name,email', 'sede:id,nombre', 'parent', 'denominations']),
        ], 201);
    }

    public function show(CashRegister $cashRegister, Request $request): JsonResponse
    {
        if ($request->user()->role === 'vendedor' && $cashRegister->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'No tienes permisos para ver este registro de caja.',
            ], 403);
        }

        $cashRegister->load([
            'user:id,name,email',
            'sede:id,nombre',
            'parent:id,fecha_hora,tipo,monto_contado',
            'children:id,parent_cash_register_id,fecha_hora,tipo,monto_contado,monto_esperado,diferencia',
            'denominations',
        ]);

        return response()->json([
            'cash_register' => $cashRegister,
        ]);
    }
}
