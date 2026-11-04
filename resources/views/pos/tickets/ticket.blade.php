<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket {{ $sale->folio }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 12px;
            background: #f1f5f9;
            font-family: "Courier New", ui-monospace, monospace;
            color: #0f172a;
        }
        .toolbar {
            max-width: 320px;
            margin: 0 auto 12px;
            display: flex;
            gap: 8px;
            justify-content: center;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
        }
        .toolbar button, .toolbar a {
            flex: 1;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }
        .toolbar button.primary {
            background: #0f172a;
            border-color: #0f172a;
            color: #fff;
        }
        .ticket {
            width: {{ (int) ($settings['ticket']['width_mm'] ?? 80) }}mm;
            max-width: 100%;
            margin: 0 auto;
            padding: 10mm 4mm;
            background: #fff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.15);
            font-size: 11px;
            line-height: 1.45;
        }
        .center { text-align: center; }
        .business { font-size: 15px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
        .muted { color: #475569; font-size: 10px; }
        .divider { border: 0; border-top: 1px dashed #94a3b8; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 10px; text-transform: uppercase; color: #475569; padding-bottom: 4px; }
        th.num, td.num { text-align: right; white-space: nowrap; }
        td { padding: 3px 0; vertical-align: top; }
        .item-name { font-weight: 700; }
        .totals td { padding: 2px 0; }
        .totals .label { color: #475569; }
        .total-row td { padding-top: 6px; font-size: 14px; font-weight: 700; }
        .footer { margin-top: 10px; text-align: center; font-size: 10px; color: #475569; }
        .folio { font-size: 12px; font-weight: 700; letter-spacing: 0.08em; }
        @media print {
            body { padding: 0; background: #fff; }
            .toolbar { display: none; }
            .ticket { box-shadow: none; margin: 0; width: auto; max-width: none; padding: 0; }
            @page { size: {{ (int) ($settings['ticket']['width_mm'] ?? 80) }}mm auto; margin: 4mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="primary" onclick="window.print()">Imprimir</button>
        <a href="{{ route('pos.index') }}">Nueva venta</a>
    </div>

    <div class="ticket">
        <div class="center">
            <p class="business">{{ $settings['business']['name'] ?? config('app.name') }}</p>
            @if (! empty($settings['business']['address']))
                <p class="muted">{{ $settings['business']['address'] }}</p>
            @endif
            @if (! empty($settings['business']['phone']))
                <p class="muted">Tel. {{ $settings['business']['phone'] }}</p>
            @endif
            @if (! empty($settings['business']['tax_id']))
                <p class="muted">RFC {{ $settings['business']['tax_id'] }}</p>
            @endif
        </div>

        <hr class="divider">

        <div class="center">
            <p class="folio">{{ $sale->folio }}</p>
            <p class="muted">{{ $sale->paid_at?->format('d/m/Y H:i') ?? $sale->created_at->format('d/m/Y H:i') }}</p>
            <p class="muted">Atendió: {{ $sale->user?->name }}</p>
        </div>

        <hr class="divider">

        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="num">Cant.</th>
                    <th class="num">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td>
                            <span class="item-name">{{ $item->product_name }}</span>
                            <span class="muted" style="display:block">
                                {{ $item->sku ?: $item->barcode }}
                                · ${{ number_format((float) $item->unit_price, 2) }} c/u
                            </span>
                        </td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">${{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                    @if ((int) $item->returned_quantity > 0)
                        <tr>
                            <td colspan="3" class="muted">Devueltas: {{ $item->returned_quantity }} pieza(s)</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        <hr class="divider">

        <table class="totals">
            <tr>
                <td class="label">Subtotal</td>
                <td class="num">${{ number_format((float) $sale->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td class="label">IVA incluido</td>
                <td class="num">${{ number_format((float) $sale->tax_total, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Total</td>
                <td class="num">${{ number_format((float) $sale->total, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Pago con</td>
                <td class="num">${{ number_format((float) $sale->amount_paid, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Cambio</td>
                <td class="num">${{ number_format((float) $sale->change_amount, 2) }}</td>
            </tr>
        </table>

        <hr class="divider">

        <div class="center">
            <p class="muted">Método de pago: {{ config('pos.payment_methods.' . ($sale->payment_method ?? 'cash') . '.label', ucfirst($sale->payment_method ?? 'efectivo')) }}</p>
            <p class="footer">{{ $settings['ticket']['footer'] ?? 'Gracias por su compra.' }}</p>
            <p class="muted">Conserve este ticket para cambios y devoluciones.</p>
        </div>
    </div>
</body>
</html>
