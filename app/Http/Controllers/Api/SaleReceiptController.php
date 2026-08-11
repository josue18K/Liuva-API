<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SaleReceiptController extends Controller
{
    public function show(Sale $sale)
    {
        $sale->load([
            'user:id,name,email',
            'sede:id,nombre',
            'items.product:id,nombre,codigo_interno,codigo_barras',
        ]);

        return view('receipts.sale', compact('sale'));
    }

    public function generate(Sale $sale): JsonResponse
    {
        $sale->load([
            'user:id,name,email',
            'sede:id,nombre',
            'items.product:id,nombre,codigo_interno,codigo_barras',
        ]);

        $html = view('receipts.sale', compact('sale'))->render();

        $relativePath = 'receipts/sale_' . $sale->id . '.html';

        Storage::disk('public')->put($relativePath, $html);

        $publicUrl = asset('storage/' . $relativePath);

        $sale->update([
            'comprobante_path' => $relativePath,
        ]);

        $message = "Hola, gracias por su compra en Importaciones Liuva. Aquí tiene su comprobante de venta N° {$sale->id}: {$publicUrl}";

        $whatsAppUrl = 'https://wa.me/?text=' . urlencode($message);

        return response()->json([
            'message' => 'Comprobante generado correctamente.',
            'receipt_url' => $publicUrl,
            'receipt_path' => $relativePath,
            'whatsapp_message' => $message,
            'whatsapp_url' => $whatsAppUrl,
        ]);
    }
}
