<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Sede;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReceiptReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_generates_png_receipt_and_public_access_uses_uuid(): void
    {
        Storage::fake('public');
        [$seller, $sede, $product] = $this->catalog();
        $sale = Sale::query()->create([
            'user_id' => $seller->id, 'sede_id' => $sede->id, 'forma_pago' => 'plin',
            'total' => '19.98', 'comprobante_numero' => 'V-00000001', 'comprobante_token' => (string) Str::uuid(),
        ]);
        SaleItem::query()->create([
            'sale_id' => $sale->id, 'product_id' => $product->id, 'precio_oficial' => '12.50',
            'precio_vendido' => '9.99', 'cantidad' => 2, 'subtotal' => '19.98',
        ]);
        Setting::query()->create(['clave' => 'mensaje_comprobante', 'valor' => 'Gracias por elegir Liuva.']);

        $response = $this->withToken($seller->createToken('seller')->plainTextToken)
            ->postJson("/api/sales/{$sale->id}/generate-receipt")
            ->assertOk()
            ->assertJsonPath('whatsapp_message', fn (string $message) => str_contains($message, 'Gracias por elegir Liuva.'));

        $path = $response->json('receipt_path');
        Storage::disk('public')->assertExists($path);
        $this->assertSame("\x89PNG", substr(Storage::disk('public')->get($path), 0, 4));

        $this->get("/api/public/receipts/{$sale->comprobante_token}")
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
        $this->get("/api/public/receipts/{$sale->id}")->assertNotFound();
    }

    public function test_seller_cannot_generate_another_sellers_receipt(): void
    {
        [$seller, $sede] = $this->catalog();
        $other = User::factory()->active()->create(['sede_id' => $sede->id]);
        $sale = Sale::query()->create([
            'user_id' => $other->id, 'sede_id' => $sede->id, 'forma_pago' => 'efectivo',
            'total' => '1.00', 'comprobante_token' => (string) Str::uuid(),
        ]);

        $this->withToken($seller->createToken('seller')->plainTextToken)
            ->postJson("/api/sales/{$sale->id}/generate-receipt")
            ->assertNotFound();
    }

    public function test_inventory_report_filters_category_and_includes_dozen_and_low_stock(): void
    {
        [, $sede, $product] = $this->catalog();
        ProductStock::query()->create(['product_id' => $product->id, 'sede_id' => $sede->id, 'stock' => 14]);
        $admin = User::factory()->admin()->create();

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->getJson("/api/admin/inventory-reports/sede/{$sede->id}?category_id={$product->category_id}")
            ->assertOk()
            ->assertJsonPath('items.0.docenas', 1)
            ->assertJsonPath('items.0.unidades_restantes', 2)
            ->assertJsonPath('items.0.stock_bajo', true)
            ->assertJsonPath('report_text', fn (string $text) => str_contains($text, '1 docena(s) + 2 unidad(es)'));
    }

    public function test_admin_downloads_inventory_as_pdf(): void
    {
        [, $sede, $product] = $this->catalog();
        ProductStock::query()->create(['product_id' => $product->id, 'sede_id' => $sede->id, 'stock' => 4]);
        $admin = User::factory()->admin()->create();

        $response = $this->withToken($admin->createToken('admin')->plainTextToken)
            ->get("/api/admin/inventory-reports/sede/{$sede->id}/pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertSame('%PDF', substr($response->getContent(), 0, 4));
    }

    private function catalog(): array
    {
        $sede = Sede::query()->create(['nombre' => 'Principal', 'active' => true]);
        $seller = User::factory()->active()->create(['sede_id' => $sede->id]);
        $category = Category::query()->create(['nombre' => 'Accesorios', 'active' => true]);
        $product = Product::query()->create([
            'nombre' => 'Cable USB', 'codigo_interno' => 'LIU-1', 'precio_oficial' => '12.50',
            'unidad' => 'unidad', 'stock_minimo' => 15, 'category_id' => $category->id, 'active' => true,
        ]);

        return [$seller, $sede, $product];
    }
}
