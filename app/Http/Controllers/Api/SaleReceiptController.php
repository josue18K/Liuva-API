<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleReceiptController extends Controller
{
    public function show(string $token): BinaryFileResponse
    {
        $sale = Sale::query()->where('comprobante_token', $token)->firstOrFail();

        abort_unless($sale->comprobante_path && Storage::disk('public')->exists($sale->comprobante_path), 404);

        return response()->file(Storage::disk('public')->path($sale->comprobante_path), [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function generate(Sale $sale, Request $request): JsonResponse
    {
        if ($request->user()->role === User::ROLE_SELLER && $sale->user_id !== $request->user()->id) {
            abort(404);
        }

        $sale->load(['user:id,name', 'sede:id,nombre', 'items.product:id,nombre']);
        $relativePath = 'receipts/'.$sale->comprobante_token.'.png';
        Storage::disk('public')->put($relativePath, $this->renderPng($sale));
        $sale->update(['comprobante_path' => $relativePath]);

        $receiptUrl = route('public.receipts.show', ['token' => $sale->comprobante_token]);
        $customMessage = Setting::query()->where('clave', 'mensaje_comprobante')->value('valor')
            ?: 'Gracias por su compra en Importaciones Liuva.';
        $whatsAppMessage = $customMessage.' Comprobante '.$sale->comprobante_numero.': '.$receiptUrl;

        return response()->json([
            'message' => 'Comprobante PNG generado correctamente.',
            'receipt_url' => $receiptUrl,
            'receipt_path' => $relativePath,
            'whatsapp_message' => $whatsAppMessage,
            'whatsapp_url' => 'https://wa.me/?text='.urlencode($whatsAppMessage),
        ]);
    }

    private function renderPng(Sale $sale): string
    {
        $width = 720;
        $height = 430 + ($sale->items->count() * 105);
        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, 24, 24, 27);
        $muted = imagecolorallocate($image, 82, 82, 91);
        $soft = imagecolorallocate($image, 244, 244, 245);
        $accent = imagecolorallocate($image, 37, 99, 235);
        imagefill($image, 0, 0, $white);
        imagefilledrectangle($image, 0, 0, $width, 105, $ink);
        $this->text($image, 5, 30, 28, 'IMPORTACIONES LIUVA', $white);
        $this->text($image, 3, 30, 65, 'COMPROBANTE DE VENTA', $white);

        $this->text($image, 4, 30, 130, $sale->comprobante_numero ?? 'VENTA #'.$sale->id, $accent);
        $this->text($image, 3, 30, 165, 'Fecha: '.$sale->created_at->format('d/m/Y H:i'), $muted);
        $this->text($image, 3, 30, 190, 'Sede: '.$sale->sede->nombre, $muted);
        $this->text($image, 3, 30, 215, 'Vendedor: '.$sale->user->name, $muted);
        $this->text($image, 3, 30, 240, 'Pago: '.mb_strtoupper($sale->forma_pago), $muted);

        $y = 285;
        foreach ($sale->items as $item) {
            imagefilledrectangle($image, 25, $y, $width - 25, $y + 85, $soft);
            $this->text($image, 4, 40, $y + 12, $item->product->nombre, $ink);
            $this->text($image, 3, 40, $y + 48, "{$item->cantidad} x S/ {$item->precio_vendido}", $muted);
            $this->text($image, 4, 520, $y + 43, 'S/ '.$item->subtotal, $ink);
            $y += 105;
        }

        imagefilledrectangle($image, 25, $y + 5, $width - 25, $y + 75, $ink);
        $this->text($image, 5, 45, $y + 28, 'TOTAL', $white);
        $this->text($image, 5, 500, $y + 28, 'S/ '.$sale->total, $white);
        $this->text($image, 3, 210, $y + 105, 'GRACIAS POR SU COMPRA', $muted);

        ob_start();
        imagepng($image, null, 9);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    private function text(\GdImage $image, int $font, int $x, int $y, string $text, int $color): void
    {
        $safeText = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        imagestring($image, $font, $x, $y, $safeText, $color);
    }
}
