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
            background: #111318;
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
            border: 1px solid #374151;
            background: #1C2028;
            color: #FFFFFF;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }
        .toolbar button.primary {
            background: #F97316;
            border-color: #F97316;
            color: #fff;
        }
        .toolbar button.primary:hover {
            background: #EA580C;
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
        th.num, td.num { text-align: right; white-space: nowrap; padding-left: 2mm; }
        td { padding: 3px 0; vertical-align: top; }
        .item-name { font-weight: 700; }
        .ticket p { margin: 4px 0; overflow-wrap: anywhere; }
        .ticket td:first-child { overflow-wrap: anywhere; }
        .ticket table { table-layout: auto; }
        .ticket th:first-child { width: 100%; }
        .ticket td.num { font-size: 10px; }
        .totals td { padding: 2px 0; }
        .totals .label { color: #475569; }
        .total-row td { padding-top: 6px; font-size: 14px; font-weight: 700; }
        .footer { margin-top: 10px; text-align: center; font-size: 10px; color: #475569; }
        .folio { font-size: 12px; font-weight: 700; letter-spacing: 0.08em; }
        @media print {
            body { padding: 0; background: #fff; }
            .toolbar { display: none; }
            .ticket { box-shadow: none; margin: 0 auto; width: calc(var(--ticket-width, 80mm) - 8mm); max-width: 100%; padding: 0; }
            @page { size: auto; margin: 4mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="primary" onclick="window.print()">Imprimir</button>
        <a href="{{ route('pos.index') }}">Nueva venta</a>
    </div>

    @include('pos.tickets.receipt')
</body>
</html>
