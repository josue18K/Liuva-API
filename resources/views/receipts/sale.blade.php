<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Venta #{{ $sale->id }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #f5f5f5;
            padding: 24px;
            color: #1f2937;
        }

        .receipt-wrapper {
            max-width: 420px;
            margin: 0 auto;
        }

        .receipt-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }

        .brand {
            text-align: center;
            margin-bottom: 20px;
        }

        .brand h1 {
            font-size: 22px;
            margin-bottom: 6px;
        }

        .brand p {
            font-size: 13px;
            color: #6b7280;
        }

        .divider {
            border-top: 1px dashed #d1d5db;
            margin: 16px 0;
        }

        .meta,
        .totals,
        .item-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .meta-label,
        .item-name,
        .totals-label {
            color: #6b7280;
        }

        .items {
            margin-top: 12px;
        }

        .item-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 12px;
            margin-bottom: 10px;
        }

        .item-title {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #111827;
        }

        .total-box {
            background: #111827;
            color: #ffffff;
            border-radius: 14px;
            padding: 14px;
            margin-top: 14px;
        }

        .total-box .totals {
            margin-bottom: 0;
            font-size: 16px;
            font-weight: 700;
        }

        .footer {
            text-align: center;
            margin-top: 18px;
            font-size: 12px;
            color: #6b7280;
        }

        @media (max-width: 480px) {
            body {
                padding: 12px;
            }

            .receipt-card {
                padding: 16px;
                border-radius: 16px;
            }

            .brand h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-wrapper">
        <div class="receipt-card">
            <div class="brand">
                <h1>Importaciones Liuva</h1>
                <p>Comprobante de venta</p>
            </div>

            <div class="meta">
                <span class="meta-label">N° Venta</span>
                <span>#{{ $sale->id }}</span>
            </div>

            <div class="meta">
                <span class="meta-label">Fecha</span>
                <span>{{ $sale->created_at->format('d/m/Y H:i') }}</span>
            </div>

            <div class="meta">
                <span class="meta-label">Vendedor</span>
                <span>{{ $sale->user->name }}</span>
            </div>

            <div class="meta">
                <span class="meta-label">Sede</span>
                <span>{{ $sale->sede->nombre }}</span>
            </div>

            <div class="divider"></div>

            <div class="items">
                @foreach ($sale->items as $item)
                    <div class="item-card">
                        <div class="item-title">{{ $item->product->nombre }}</div>

                        <div class="item-row">
                            <span class="item-name">Cantidad</span>
                            <span>{{ $item->cantidad }}</span>
                        </div>

                        <div class="item-row">
                            <span class="item-name">Precio cobrado</span>
                            <span>S/ {{ number_format($item->precio_vendido, 2) }}</span>
                        </div>

                        <div class="item-row">
                            <span class="item-name">Subtotal</span>
                            <span>S/ {{ number_format($item->subtotal, 2) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="total-box">
                <div class="totals">
                    <span class="totals-label" style="color:#d1d5db;">Total pagado</span>
                    <span>S/ {{ number_format($sale->total, 2) }}</span>
                </div>
            </div>

            <div class="footer">
                Gracias por su compra
            </div>
        </div>
    </div>
</body>
</html>
