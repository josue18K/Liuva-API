<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Inventario {{ $sede->nombre }}</title>
    <style>
        @page { margin: 30px 34px 42px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #19302e; font-family: "DejaVu Sans", sans-serif; font-size: 10px; }
        .header { padding: 18px 20px; border-radius: 12px; background: #075f58; color: white; }
        .brand { font-size: 11px; font-weight: bold; letter-spacing: 1.5px; text-transform: uppercase; }
        h1 { margin: 7px 0 3px; font-size: 23px; }
        .meta { color: #d5efeb; font-size: 9px; }
        .summary { margin: 14px 0; padding: 10px 13px; border: 1px solid #cce4e0; background: #f1faf8; }
        .summary strong { color: #075f58; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th { padding: 9px 7px; background: #182320; color: white; text-align: left; font-size: 8px; text-transform: uppercase; }
        td { padding: 8px 7px; border-bottom: 1px solid #e1e9e7; vertical-align: top; }
        tbody tr:nth-child(even) td { background: #f7f9f8; }
        .product { width: 29%; font-weight: bold; }
        .code { width: 14%; }
        .barcode { width: 20%; }
        .category { width: 16%; }
        .qty { width: 9%; text-align: right; }
        .state { width: 12%; text-align: center; }
        .badge { display: inline-block; padding: 3px 6px; border-radius: 8px; background: #fff1da; color: #8a5b17; font-size: 8px; font-weight: bold; }
        .badge.ok { background: #dff3e9; color: #217653; }
        .footer { position: fixed; right: 0; bottom: -26px; left: 0; color: #7a8987; text-align: center; font-size: 8px; }
    </style>
</head>
<body>
    <div class="footer">Liuva - Reporte de inventario generado por el sistema</div>
    <section class="header">
        <div class="brand">Liuva</div>
        <h1>Inventario de {{ $sede->nombre }}</h1>
        <div class="meta">Generado el {{ $generatedAt->format('d/m/Y') }} a las {{ $generatedAt->format('H:i') }} · Por: {{ $generatedBy }}</div>
    </section>
    <div class="summary"><strong>{{ $stocks->count() }}</strong> productos incluidos en este reporte.</div>
    <table>
        <thead>
            <tr>
                <th class="product">Producto</th>
                <th class="code">Código</th>
                <th class="barcode">Código de barras</th>
                <th class="category">Categoría</th>
                <th class="qty">Cantidad</th>
                <th class="state">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($stocks as $row)
                <tr>
                    <td class="product">{{ $row->product->nombre }}</td>
                    <td class="code">{{ $row->product->codigo_interno }}</td>
                    <td class="barcode">{{ $row->product->codigo_barras ?: '-' }}</td>
                    <td class="category">{{ $row->product->category?->nombre ?: '-' }}</td>
                    <td class="qty">{{ $row->stock }} {{ $row->product->unidad }}</td>
                    <td class="state"><span class="badge {{ $row->stock > $row->product->stock_minimo ? 'ok' : '' }}">{{ $row->stock > $row->product->stock_minimo ? 'Disponible' : 'Regularizar' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" style="padding: 28px; text-align: center; color: #71807e;">No hay productos para los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
