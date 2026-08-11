<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_complete_product_with_normalized_codes(): void
    {
        $category = $this->category();

        $this->actingAsAdmin()
            ->postJson('/api/admin/products', [
                'nombre' => 'Cable reforzado',
                'descripcion' => 'Cable USB para carga rápida.',
                'codigo_interno' => ' liu-100 ',
                'codigo_barras' => '750000000100',
                'precio_oficial' => '19.90',
                'unidad' => 'Unidad',
                'stock_minimo' => 5,
                'category_id' => $category->id,
                'active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('product.codigo_interno', 'LIU-100')
            ->assertJsonPath('product.unidad', 'unidad')
            ->assertJsonPath('product.stock_minimo', 5);
    }

    public function test_product_list_is_paginated_and_searches_all_codes(): void
    {
        $category = $this->category();
        $this->product($category, 'Adaptador USB', 'LIU-200', '750000000200');
        $this->product($category, 'Multímetro', 'LIU-201', '750000000201');

        $this->actingAsAdmin()
            ->getJson('/api/admin/products?q=750000000200&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'products.data')
            ->assertJsonPath('products.data.0.codigo_interno', 'LIU-200')
            ->assertJsonPath('products.per_page', 1);
    }

    public function test_product_rejects_invalid_barcode_and_inactive_category(): void
    {
        $category = $this->category(false);

        $this->actingAsAdmin()
            ->postJson('/api/admin/products', [
                'nombre' => 'Producto inválido',
                'codigo_interno' => 'LIU-300',
                'codigo_barras' => 'ABC-123',
                'precio_oficial' => '10.00',
                'unidad' => 'unidad',
                'stock_minimo' => 0,
                'category_id' => $category->id,
                'active' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['codigo_barras', 'category_id']);
    }

    public function test_category_list_is_paginated_and_filterable(): void
    {
        $this->category(true, 'Accesorios');
        $this->category(false, 'Archivada');

        $this->actingAsAdmin()
            ->getJson('/api/admin/categories?active=1&q=Accesorios')
            ->assertOk()
            ->assertJsonCount(1, 'categories.data')
            ->assertJsonPath('categories.data.0.nombre', 'Accesorios');
    }

    private function actingAsAdmin(): static
    {
        $admin = User::factory()->admin()->create();

        return $this->withToken($admin->createToken('admin')->plainTextToken);
    }

    private function category(bool $active = true, string $name = 'Accesorios'): Category
    {
        return Category::query()->create([
            'nombre' => $name,
            'active' => $active,
        ]);
    }

    private function product(Category $category, string $name, string $internalCode, string $barcode): Product
    {
        return Product::query()->create([
            'nombre' => $name,
            'codigo_interno' => $internalCode,
            'codigo_barras' => $barcode,
            'precio_oficial' => '10.00',
            'unidad' => 'unidad',
            'stock_minimo' => 0,
            'category_id' => $category->id,
            'active' => true,
        ]);
    }
}
