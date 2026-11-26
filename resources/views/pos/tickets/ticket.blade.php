<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket de venta</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 12px;
            background: #111318;
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
            border: 1px solid #374151;
            background: #1C2028;
            color: #FFFFFF;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }
        .toolbar .primary {
            background: #F97316;
            border-color: #F97316;
            color: #fff;
        }
        .toolbar .primary:hover {
            background: #EA580C;
        }
        @include('pos.tickets.receipt-styles')
        @media print {
            body { padding: 0; background: #fff; }
            .toolbar { display: none; }
        }
    </style>
</head>
<body id="sale-ticket">
    <div class="toolbar">
        @if ($settings['ticket']['print_mode'] === 'text')
            <a class="primary" href="{{ route('pos.sales.ticket', ['sale' => $sale, 'print' => 1]) }}">Imprimir</a>
        @else
            <button type="button" class="primary" onclick="window.print()">Imprimir</button>
        @endif
        <a href="{{ route('pos.index') }}">Nueva venta</a>
    </div>

    @include('pos.tickets.receipt')

    <script>
        const receiptQr = document.querySelector('.receipt-qr');
        if (receiptQr) {
            const hideBrokenQr = () => { receiptQr.parentElement.hidden = true; };
            receiptQr.addEventListener('error', hideBrokenQr, { once: true });
            if (receiptQr.complete && !receiptQr.naturalWidth) {
                hideBrokenQr();
            }
        }

        @if (request()->query('print') === '1')
            window.addEventListener('load', () => window.print(), { once: true });
        @endif
    </script>
</body>
</html>
