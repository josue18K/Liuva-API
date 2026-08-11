<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductStock;
use App\Models\Sede;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryReportController extends Controller
{
    public function bySede(Request $request, Sede $sede): JsonResponse
    {
        $request->validate([
            'solo_con_stock' => ['nullable', 'boolean'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $query = ProductStock::query()
            ->with([
                'product:id,nombre,codigo_interno,codigo_barras,category_id,stock_minimo,unidad,active',
                'product.category:id,nombre',
                'sede:id,nombre',
            ])
            ->where('sede_id', $sede->id)
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->whereHas('product', fn ($query) => $query->where('category_id', $request->integer('category_id')));
            })
            ->orderByDesc('stock');

        if ($request->boolean('solo_con_stock')) {
            $query->where('stock', '>', 0);
        }

        $stocks = $query->get();

        $lines = [];
        $lines[] = '📦 REPORTE DE INVENTARIO';
        $lines[] = "Sede: {$sede->nombre}";
        $lines[] = 'Fecha: '.now()->format('d/m/Y H:i');
        $lines[] = str_repeat('-', 30);

        foreach ($stocks as $row) {
            $product = $row->product;

            $lines[] = "Producto: {$product->nombre}";
            $lines[] = "Código interno: {$product->codigo_interno}";
            $lines[] = 'Código barras: '.($product->codigo_barras ?: '-');
            $lines[] = 'Categoría: '.($product->category?->nombre ?: '-');
            $dozens = intdiv($row->stock, 12);
            $remainder = $row->stock % 12;
            $lines[] = "Stock: {$row->stock} {$product->unidad}";
            $lines[] = "Equivalencia: {$dozens} docena(s) + {$remainder} unidad(es)";
            if ($row->stock <= $product->stock_minimo) {
                $lines[] = '⚠️ STOCK MÍNIMO';
            }
            $lines[] = str_repeat('-', 30);
        }

        $text = implode("\n", $lines);
        $whatsappUrl = 'https://wa.me/?text='.urlencode($text);

        return response()->json([
            'sede' => [
                'id' => $sede->id,
                'nombre' => $sede->nombre,
            ],
            'total_productos' => $stocks->count(),
            'report_text' => $text,
            'whatsapp_url' => $whatsappUrl,
            'items' => $stocks->map(function ($row) {
                return [
                    'product_id' => $row->product->id,
                    'nombre' => $row->product->nombre,
                    'codigo_interno' => $row->product->codigo_interno,
                    'codigo_barras' => $row->product->codigo_barras,
                    'categoria' => $row->product->category?->nombre,
                    'stock' => $row->stock,
                    'stock_minimo' => $row->product->stock_minimo,
                    'stock_bajo' => $row->stock <= $row->product->stock_minimo,
                    'docenas' => intdiv($row->stock, 12),
                    'unidades_restantes' => $row->stock % 12,
                    'active' => $row->product->active,
                ];
            })->values(),
        ]);
    }
}
