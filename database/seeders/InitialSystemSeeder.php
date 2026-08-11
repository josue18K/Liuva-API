<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\License;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sede;
use Illuminate\Database\Seeder;

class InitialSystemSeeder extends Seeder
{
    public function run(): void
    {
        $sede = Sede::query()->firstOrCreate(
            ['nombre' => 'Sede Principal'],
            [
                'direccion' => 'Lima - Principal',
                'active' => true,
            ]
        );

        $category1 = Category::query()->firstOrCreate(
            ['nombre' => 'Accesorios'],
            ['active' => true]
        );

        $category2 = Category::query()->firstOrCreate(
            ['nombre' => 'Herramientas'],
            ['active' => true]
        );

        $product1 = Product::query()->firstOrCreate(
            ['codigo_interno' => 'LIU-001'],
            [
                'nombre' => 'Cable USB Tipo C',
                'codigo_barras' => '750000000001',
                'precio_oficial' => 15.00,
                'category_id' => $category1->id,
                'active' => true,
            ]
        );

        $product2 = Product::query()->firstOrCreate(
            ['codigo_interno' => 'LIU-002'],
            [
                'nombre' => 'Adaptador Universal',
                'codigo_barras' => '750000000002',
                'precio_oficial' => 25.00,
                'category_id' => $category1->id,
                'active' => true,
            ]
        );

        $product3 = Product::query()->firstOrCreate(
            ['codigo_interno' => 'LIU-003'],
            [
                'nombre' => 'Multímetro Digital',
                'codigo_barras' => '750000000003',
                'precio_oficial' => 59.90,
                'category_id' => $category2->id,
                'active' => true,
            ]
        );

        ProductStock::query()->firstOrCreate(
            ['product_id' => $product1->id, 'sede_id' => $sede->id],
            ['stock' => 20]
        );

        ProductStock::query()->firstOrCreate(
            ['product_id' => $product2->id, 'sede_id' => $sede->id],
            ['stock' => 15]
        );

        ProductStock::query()->firstOrCreate(
            ['product_id' => $product3->id, 'sede_id' => $sede->id],
            ['stock' => 10]
        );

        License::query()->firstOrCreate(
            ['code' => 'LIUVA-TEST001'],
            [
                'status' => 'disponible',
                'estado' => License::STATUS_AVAILABLE,
            ]
        );
    }
}
