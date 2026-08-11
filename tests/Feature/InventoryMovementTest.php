<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_entry_exit_and_adjustment_update_stock_with_traceability(): void
    {
        [$product, $sede] = $this->catalog();
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('admin')->plainTextToken;

        $this->withToken($token)->postJson('/api/admin/inventory-movements', [
            'product_id' => $product->id,
            'sede_id' => $sede->id,
            'tipo' => 'entrada',
            'cantidad' => 20,
            'motivo' => 'Compra a proveedor',
        ])->assertCreated()->assertJsonPath('movement.stock_nuevo', 20);

        $this->withToken($token)->postJson('/api/admin/inventory-movements', [
            'product_id' => $product->id,
            'sede_id' => $sede->id,
            'tipo' => 'salida',
            'cantidad' => 4,
            'motivo' => 'Producto dañado',
        ])->assertCreated()->assertJsonPath('movement.stock_nuevo', 16);

        $this->withToken($token)->postJson('/api/admin/inventory-movements', [
            'product_id' => $product->id,
            'sede_id' => $sede->id,
            'tipo' => 'ajuste',
            'stock_objetivo' => 12,
            'motivo' => 'Conteo físico',
        ])->assertCreated()->assertJsonPath('movement.stock_nuevo', 12);

        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'stock' => 12]);
        $this->assertDatabaseCount('inventory_movements', 3);
    }

    public function test_exit_cannot_leave_negative_stock(): void
    {
        [$product, $sede] = $this->catalog();
        ProductStock::query()->create(['product_id' => $product->id, 'sede_id' => $sede->id, 'stock' => 2]);
        $admin = User::factory()->admin()->create();

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->postJson('/api/admin/inventory-movements', [
                'product_id' => $product->id,
                'sede_id' => $sede->id,
                'tipo' => 'salida',
                'cantidad' => 3,
                'motivo' => 'Intento inválido',
            ])->assertUnprocessable();

        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'stock' => 2]);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_seller_cannot_manage_manual_inventory(): void
    {
        [$product, $sede] = $this->catalog();
        $seller = User::factory()->active()->create();

        $this->withToken($seller->createToken('seller')->plainTextToken)
            ->postJson('/api/admin/inventory-movements', [
                'product_id' => $product->id,
                'sede_id' => $sede->id,
                'tipo' => 'entrada',
                'cantidad' => 1,
                'motivo' => 'Sin permiso',
            ])->assertForbidden();
    }

    private function catalog(): array
    {
        $category = Category::query()->create(['nombre' => 'Accesorios', 'active' => true]);
        $sede = Sede::query()->create(['nombre' => 'Principal', 'active' => true]);
        $product = Product::query()->create([
            'nombre' => 'Cable', 'codigo_interno' => 'LIU-1', 'precio_oficial' => 10,
            'unidad' => 'unidad', 'stock_minimo' => 5, 'category_id' => $category->id, 'active' => true,
        ]);

        return [$product, $sede];
    }
}
