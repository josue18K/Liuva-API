<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemCustomizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_user_can_store_personal_colors(): void
    {
        $seller = User::factory()->active()->create();

        $this->withToken($seller->createToken('seller')->plainTextToken)
            ->putJson('/api/preferences', [
                'brand_color' => '#d63384',
                'background_color' => '#fdf2f8',
            ])
            ->assertOk()
            ->assertJsonPath('preferences.brand_color', '#d63384');

        $this->assertDatabaseHas('users', [
            'id' => $seller->id,
            'brand_color' => '#d63384',
            'background_color' => '#fdf2f8',
        ]);
    }

    public function test_profile_changes_name_but_never_email(): void
    {
        $seller = User::factory()->active()->create(['email' => 'original@example.com']);

        $this->withToken($seller->createToken('seller')->plainTextToken)
            ->putJson('/api/profile', [
                'name' => 'Nuevo nombre',
                'email' => 'cambiado@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Nuevo nombre')
            ->assertJsonPath('user.email', 'original@example.com');
    }

    public function test_internal_codes_start_at_zero_for_each_sede_prefix(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::query()->create(['nombre' => 'Ropa', 'active' => true]);
        $pauza = Sede::query()->create(['nombre' => 'LIUVA PAUZA', 'prefix_codigo' => 'PAU', 'active' => true]);
        $mujer = Sede::query()->create(['nombre' => 'LIUVA MUJER', 'prefix_codigo' => 'MUJE', 'active' => true]);
        $token = $admin->createToken('admin')->plainTextToken;

        $this->withToken($token)->getJson("/api/admin/products-next-code?sede_id={$pauza->id}")
            ->assertOk()->assertJsonPath('codigo_interno', 'PAU000');

        $this->withToken($token)->postJson('/api/admin/products', [
            'nombre' => 'Producto Pauza', 'sede_id' => $pauza->id, 'codigo_interno' => '',
            'precio_oficial' => '10.00', 'unidad' => 'unidad', 'stock_minimo' => 0,
            'category_id' => $category->id, 'active' => true,
        ])->assertCreated()->assertJsonPath('product.codigo_interno', 'PAU000');

        $this->withToken($token)->getJson("/api/admin/products-next-code?sede_id={$pauza->id}")
            ->assertOk()->assertJsonPath('codigo_interno', 'PAU001');
        $this->withToken($token)->getJson("/api/admin/products-next-code?sede_id={$mujer->id}")
            ->assertOk()->assertJsonPath('codigo_interno', 'MUJE000');
    }

    public function test_sede_prefix_is_inferred_from_liuva_name(): void
    {
        $admin = User::factory()->admin()->create();

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->postJson('/api/admin/sedes', [
                'nombre' => 'LIUVA PAUZA',
                'active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('sede.prefix_codigo', 'PAU');
    }

    public function test_reset_preserves_only_admin_accounts_and_their_preferences(): void
    {
        $admin = User::factory()->admin()->create([
            'brand_color' => '#7c3aed',
            'background_color' => '#f5f3ff',
        ]);
        $seller = User::factory()->active()->create();
        $sede = Sede::query()->create(['nombre' => 'Temporal', 'prefix_codigo' => 'TEMP', 'active' => true]);
        $seller->update(['sede_id' => $sede->id]);
        $category = Category::query()->create(['nombre' => 'Temporal', 'active' => true]);
        Product::query()->create([
            'nombre' => 'Temporal', 'codigo_interno' => 'TEMP000', 'precio_oficial' => '1.00',
            'unidad' => 'unidad', 'stock_minimo' => 0, 'category_id' => $category->id, 'active' => true,
        ]);

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->postJson('/api/admin/reset-system', [
                'confirm' => true,
                'confirmation_text' => 'REINICIAR LIUVA',
            ])
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => User::ROLE_ADMIN,
            'brand_color' => '#7c3aed',
        ]);
        $this->assertDatabaseMissing('users', ['id' => $seller->id]);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('sedes', 0);
    }
}
