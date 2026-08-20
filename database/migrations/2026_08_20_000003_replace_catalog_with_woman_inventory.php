<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $payload = json_decode(file_get_contents(database_path('data/inventory-woman-import.json')), true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($payload): void {
            DB::table('cash_register_denominations')->delete();
            DB::table('sale_items')->delete();
            DB::table('sales')->delete();
            DB::table('cash_registers')->delete();
            DB::table('inventory_movements')->delete();
            DB::table('inventory_adjustments')->delete();
            DB::table('product_stocks')->delete();
            DB::table('products')->delete();
            DB::table('categories')->delete();

            $sede = DB::table('sedes')->whereRaw('UPPER(nombre) = ?', ['LIUVA MUJER'])->first();
            $sedeId = $sede?->id ?? DB::table('sedes')->insertGetId([
                'nombre' => 'LIUVA MUJER',
                'prefix_codigo' => 'MUJE',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('sedes')->where('id', $sedeId)->update(['prefix_codigo' => 'MUJE', 'active' => true]);

            $categoryIds = [];
            foreach ($payload['categories'] as $name) {
                $categoryIds[$name] = DB::table('categories')->insertGetId([
                    'nombre' => str($name)->title()->toString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($payload['products'] as $index => $row) {
                $productId = DB::table('products')->insertGetId([
                    'nombre' => $row['name'],
                    'descripcion' => 'Importado desde INVENTARIO LMUJER.xlsx',
                    'codigo_interno' => 'MUJE'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                    'codigo_barras' => null,
                    'precio_oficial' => $row['price'],
                    'unidad' => 'unidad',
                    'stock_minimo' => 0,
                    'category_id' => $categoryIds[$row['category']],
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('product_stocks')->insert([
                    'product_id' => $productId,
                    'sede_id' => $sedeId,
                    'stock' => $row['stock'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // La sustitución de un catálogo productivo es intencionalmente irreversible.
    }
};
