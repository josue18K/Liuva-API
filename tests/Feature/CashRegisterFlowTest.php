<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashRegisterFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_close_counts_only_cash_sales_in_expected_amount(): void
    {
        $sede = Sede::query()->create(['nombre' => 'Principal', 'active' => true]);
        $seller = User::factory()->active()->create(['sede_id' => $sede->id]);
        $token = $seller->createToken('seller')->plainTextToken;

        $opening = $this->withToken($token)->postJson('/api/cash-registers/open', [
            'sede_id' => $sede->id,
            'denominations' => [['denominacion' => '100', 'cantidad' => 1]],
        ])->assertCreated()->assertJsonPath('cash_register.monto_contado', '100.00')->json('cash_register');

        Sale::query()->create([
            'user_id' => $seller->id, 'sede_id' => $sede->id, 'cash_register_id' => $opening['id'],
            'forma_pago' => 'efectivo', 'total' => '20.00',
        ]);
        Sale::query()->create([
            'user_id' => $seller->id, 'sede_id' => $sede->id, 'cash_register_id' => $opening['id'],
            'forma_pago' => 'yape', 'total' => '30.00',
        ]);

        $this->withToken($token)->postJson('/api/cash-registers/close', [
            'cash_register_id' => $opening['id'],
            'denominations' => [
                ['denominacion' => '100', 'cantidad' => 1],
                ['denominacion' => '20', 'cantidad' => 1],
            ],
        ])->assertCreated()
            ->assertJsonPath('cash_register.monto_esperado', '120.00')
            ->assertJsonPath('cash_register.monto_contado', '120.00')
            ->assertJsonPath('cash_register.diferencia', '0.00')
            ->assertJsonPath('sales_summary.efectivo.total', '20.00')
            ->assertJsonPath('sales_summary.yape.total', '30.00');
    }

    public function test_user_cannot_open_second_cash_register_or_use_invalid_denomination(): void
    {
        $sede = Sede::query()->create(['nombre' => 'Principal', 'active' => true]);
        $seller = User::factory()->active()->create(['sede_id' => $sede->id]);
        $token = $seller->createToken('seller')->plainTextToken;

        $this->withToken($token)->postJson('/api/cash-registers/open', [
            'sede_id' => $sede->id,
            'denominations' => [['denominacion' => '50', 'cantidad' => 1]],
        ])->assertCreated();

        $this->withToken($token)->postJson('/api/cash-registers/open', [
            'sede_id' => $sede->id,
            'denominations' => [['denominacion' => '50', 'cantidad' => 1]],
        ])->assertUnprocessable();

        $this->withToken($token)->postJson('/api/cash-registers/open', [
            'sede_id' => $sede->id,
            'denominations' => [['denominacion' => '3', 'cantidad' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors('denominations.0.denominacion');
    }

    public function test_seller_cannot_open_cash_in_another_sede(): void
    {
        $assigned = Sede::query()->create(['nombre' => 'Asignada', 'active' => true]);
        $other = Sede::query()->create(['nombre' => 'Otra', 'active' => true]);
        $seller = User::factory()->active()->create(['sede_id' => $assigned->id]);

        $this->withToken($seller->createToken('seller')->plainTextToken)
            ->postJson('/api/cash-registers/open', [
                'sede_id' => $other->id,
                'denominations' => [['denominacion' => '10', 'cantidad' => 1]],
            ])->assertForbidden();
    }
}
