<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_records_sale_with_open_cash_and_exact_totals(): void
    {
        [$seller, $sede, $product] = $this->scenario();
        $cash = $this->openCash($seller, $sede);

        $response = $this->withToken($seller->createToken('seller')->plainTextToken)
            ->postJson('/api/sales', [
                'sede_id' => $sede->id,
                'forma_pago' => 'yape',
                'items' => [[
                    'product_id' => $product->id,
                    'cantidad' => 3,
                    'precio_vendido' => '9.99',
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('sale.total', '29.97')
            ->assertJsonPath('sale.forma_pago', 'yape')
            ->assertJsonPath('sale.cash_register_id', $cash->id);

        $saleId = $response->json('sale.id');
        $this->assertSame('V-'.str_pad((string) $saleId, 8, '0', STR_PAD_LEFT), $response->json('sale.comprobante_numero'));
        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'stock' => 7]);
        $this->assertDatabaseHas('inventory_movements', [
            'origen_tipo' => 'venta', 'origen_id' => $saleId, 'cantidad' => 3, 'stock_nuevo' => 7,
        ]);
    }

    public function test_sale_requires_open_cash_and_preserves_stock_on_failure(): void
    {
        [$seller, $sede, $product] = $this->scenario();

        $this->withToken($seller->createToken('seller')->plainTextToken)
            ->postJson('/api/sales', [
                'sede_id' => $sede->id,
                'forma_pago' => 'efectivo',
                'items' => [['product_id' => $product->id, 'cantidad' => 1, 'precio_vendido' => '10.00']],
            ])->assertUnprocessable();

        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'stock' => 10]);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_seller_cannot_operate_another_sede_or_view_another_sale(): void
    {
        [$seller, $sede] = $this->scenario();
        $otherSede = Sede::query()->create(['nombre' => 'Otra', 'active' => true]);
        $otherSeller = User::factory()->active()->create(['sede_id' => $otherSede->id]);
        $sale = Sale::query()->create([
            'user_id' => $otherSeller->id, 'sede_id' => $otherSede->id,
            'forma_pago' => 'efectivo', 'total' => '1.00', 'comprobante_numero' => 'V-00000001',
        ]);
        $token = $seller->createToken('seller')->plainTextToken;

        $this->withToken($token)->getJson("/api/sales/search-products?sede_id={$otherSede->id}&q=a")->assertForbidden();
        $this->withToken($token)->getJson("/api/sales/{$sale->id}")->assertNotFound();
        $this->withToken($token)->getJson('/api/sales')->assertOk()->assertJsonCount(0, 'sales.data');

        $this->assertNotNull($sede);
    }

    private function scenario(): array
    {
        $sede = Sede::query()->create(['nombre' => 'Principal', 'active' => true]);
        $seller = User::factory()->active()->create(['sede_id' => $sede->id]);
        $category = Category::query()->create(['nombre' => 'Accesorios', 'active' => true]);
        $product = Product::query()->create([
            'nombre' => 'Cable', 'codigo_interno' => 'LIU-1', 'precio_oficial' => '12.50',
            'unidad' => 'unidad', 'stock_minimo' => 2, 'category_id' => $category->id, 'active' => true,
        ]);
        ProductStock::query()->create(['product_id' => $product->id, 'sede_id' => $sede->id, 'stock' => 10]);

        return [$seller, $sede, $product];
    }

    private function openCash(User $user, Sede $sede): CashRegister
    {
        return CashRegister::query()->create([
            'user_id' => $user->id, 'sede_id' => $sede->id, 'tipo' => 'apertura',
            'monto_contado' => '100.00', 'fecha_hora' => now(),
        ]);
    }
}
