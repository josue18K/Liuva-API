<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_and_licenses_support_the_new_lifecycle(): void
    {
        $this->assertTrue(Schema::hasColumns('users', ['sede_id', 'estado']));
        $this->assertTrue(Schema::hasColumns('licenses', ['estado', 'blocked_at']));
    }

    public function test_products_support_the_complete_catalog_information(): void
    {
        $this->assertTrue(Schema::hasColumns('products', [
            'descripcion',
            'unidad',
            'stock_minimo',
        ]));
    }

    public function test_inventory_movements_have_traceability_fields(): void
    {
        $this->assertTrue(Schema::hasColumns('inventory_movements', [
            'product_id',
            'sede_id',
            'user_id',
            'tipo',
            'cantidad',
            'stock_anterior',
            'stock_nuevo',
            'origen_tipo',
            'origen_id',
            'motivo',
        ]));
    }

    public function test_sales_support_payment_cash_register_and_secure_receipts(): void
    {
        $this->assertTrue(Schema::hasColumns('sales', [
            'cash_register_id',
            'forma_pago',
            'comprobante_numero',
            'comprobante_token',
        ]));
    }
}
