<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CloseCashRegisterRequest;
use App\Http\Requests\OpenCashRegisterRequest;
use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\CashRegisterDenomination;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashRegisterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sede_id' => ['nullable', 'integer', 'exists:sedes,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

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

        if (isset($validated['sede_id'])) {
            $query->where('sede_id', $validated['sede_id']);
        }

        $cashRegisters = $query->paginate($validated['per_page'] ?? 20)->withQueryString();

        return response()->json([
            'cash_registers' => $cashRegisters,
        ]);
    }

    public function open(OpenCashRegisterRequest $request): JsonResponse
    {
        $this->ensureSedeAccess($request, $request->integer('sede_id'));

        $cashRegister = DB::transaction(function () use ($request) {
            User::query()->lockForUpdate()->findOrFail($request->user()->id);

            $existingOpen = CashRegister::query()
                ->where('user_id', $request->user()->id)
                ->where('sede_id', $request->integer('sede_id'))
                ->where('tipo', 'apertura')
                ->whereDoesntHave('children')
                ->exists();

            if ($existingOpen) {
                abort(422, 'Ya tienes una caja abierta en esta sede.');
            }

            $montoContado = $this->countedCents($request->input('denominations'));

            $cashRegister = CashRegister::query()->create([
                'parent_cash_register_id' => null,
                'user_id' => $request->user()->id,
                'sede_id' => $request->integer('sede_id'),
                'tipo' => 'apertura',
                'monto_esperado' => null,
                'monto_contado' => $this->fromCents($montoContado),
                'diferencia' => null,
                'observaciones' => $request->string('observaciones'),
                'fecha_hora' => now(),
            ]);

            foreach ($request->input('denominations') as $item) {
                CashRegisterDenomination::query()->create([
                    'cash_register_id' => $cashRegister->id,
                    'denominacion' => $item['denominacion'],
                    'cantidad' => $item['cantidad'],
                    'subtotal' => $this->fromCents($this->toCents((string) $item['denominacion']) * (int) $item['cantidad']),
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
        $summary = [];
        $closing = DB::transaction(function () use ($request, &$summary) {
            $opening = CashRegister::query()
                ->whereKey($request->integer('cash_register_id'))
                ->where('tipo', 'apertura')
                ->lockForUpdate()
                ->first();

            if (! $opening) {
                abort(404, 'La apertura de caja no existe.');
            }
            if ($opening->user_id !== $request->user()->id && $request->user()->role !== User::ROLE_ADMIN) {
                abort(403, 'No tienes permisos para cerrar esta caja.');
            }
            if ($opening->children()->exists()) {
                abort(422, 'Esta caja ya fue cerrada.');
            }

            $montoContado = $this->countedCents($request->input('denominations'));
            $summary = Sale::query()->where('cash_register_id', $opening->id)
                ->selectRaw('forma_pago, SUM(total) as total, COUNT(*) as cantidad')
                ->groupBy('forma_pago')->get()->keyBy('forma_pago')->map(fn ($row) => [
                    'cantidad' => (int) $row->cantidad,
                    'total' => number_format((float) $row->total, 2, '.', ''),
                ])->all();
            $cashSalesCents = $this->toCents((string) ($summary['efectivo']['total'] ?? '0'));
            $montoEsperado = $this->toCents($opening->monto_contado) + $cashSalesCents;
            $diferencia = $montoContado - $montoEsperado;

            $closing = CashRegister::query()->create([
                'parent_cash_register_id' => $opening->id,
                'user_id' => $opening->user_id,
                'sede_id' => $opening->sede_id,
                'tipo' => 'cierre',
                'monto_esperado' => $this->fromCents($montoEsperado),
                'monto_contado' => $this->fromCents($montoContado),
                'diferencia' => $this->fromSignedCents($diferencia),
                'observaciones' => $request->string('observaciones'),
                'fecha_hora' => now(),
            ]);

            foreach ($request->input('denominations') as $item) {
                CashRegisterDenomination::query()->create([
                    'cash_register_id' => $closing->id,
                    'denominacion' => $item['denominacion'],
                    'cantidad' => $item['cantidad'],
                    'subtotal' => $this->fromCents($this->toCents((string) $item['denominacion']) * (int) $item['cantidad']),
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
            'sales_summary' => $summary,
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

    private function ensureSedeAccess(Request $request, int $sedeId): void
    {
        if ($request->user()->role === User::ROLE_SELLER && $request->user()->sede_id !== $sedeId) {
            abort(403, 'No tienes permisos para operar caja en esta sede.');
        }
    }

    private function countedCents(array $denominations): int
    {
        return collect($denominations)->sum(fn (array $item): int => $this->toCents((string) $item['denominacion']) * (int) $item['cantidad']);
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

    private function fromSignedCents(int $cents): string
    {
        return ($cents < 0 ? '-' : '').$this->fromCents(abs($cents));
    }
}
