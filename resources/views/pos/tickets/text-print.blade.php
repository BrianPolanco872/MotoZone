<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket de venta</title>
    <style>
        @include('pos.tickets.text-styles')
        @page { size: auto; margin: 0; }
        html, body { margin: 0; padding: 0; background: #fff; }
        .ticket-text { margin: 0; }
        @media print { .print-actions { display: none; } }
    </style>
    @vite('resources/js/ticket-text.js')
</head>
<body @if ($embedded) data-ticket-embedded @endif>
    <pre id="ticket" class="ticket-text" data-ticket-print-document @if ($autoPrint) data-ticket-auto-print @endif data-paper-width="{{ $receipt['ticket']['width_mm'] }}" data-receipt="{{ json_encode($receipt, JSON_THROW_ON_ERROR) }}"></pre>
    @unless ($embedded)
    <div class="print-actions">
        <button type="button" onclick="window.print()">Imprimir</button>
    </div>
    @endunless
</body>
</html>
