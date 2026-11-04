<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Corte {{ $date->format('d/m/Y') }}</title>
    @php
        $salesSection = $totals['sales'] ?? [];
        $computerPanel = $totals['computer_panel'] ?? [];
        $cashBoxPanel = $totals['cash_box_panel'] ?? [];
        $reconciliation = $totals['reconciliation'] ?? [];
        $grandTotals = $totals['totals'] ?? [];
        $departments = $totals['departments'] ?? [];
        $cashOut = $totals['cash_out'] ?? [];
        $money = fn ($value) => '$'.number_format((float) $value, 2);
        $cut = $cut ?? null;
    @endphp
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 8px;
            background: #ffffff;
            font-family: "Courier New", ui-monospace, monospace;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.4;
        }
        .sheet {
            width: {{ (int) ($settings['ticket']['width_mm'] ?? 80) }}mm;
            max-width: 100%;
            margin: 0 auto;
            padding: 6mm 3mm;
            background: #fff;
        }
        header { text-align: center; border-bottom: 1px dashed #475569; padding-bottom: 6px; margin-bottom: 8px; }
        header h1 { margin: 0; font-size: 14px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
        header p { margin: 2px 0 0; color: #475569; font-size: 10px; }
        .meta { display: flex; justify-content: space-between; margin-top: 6px; font-size: 9px; color: #64748b; }
        .meta span { white-space: nowrap; }
        h2 {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin: 12px 0 6px;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 3px;
            color: #475569;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 2.5px 0; vertical-align: top; }
        th { text-align: left; font-size: 9px; text-transform: uppercase; color: #475569; border-bottom: 1px solid #e2e8f0; }
        .num { text-align: right; white-space: nowrap; }
        .row-total td { border-top: 1px solid #cbd5e1; padding-top: 4px; font-weight: 700; }
        .strong { font-weight: 700; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .panel { border: 1px solid #cbd5e1; padding: 6px 8px; margin: 6px 0; }
        .panel h3 { font-size: 9px; text-transform: uppercase; margin: 0 0 4px; color: #0f172a; border-bottom: 1px dashed #cbd5e1; padding-bottom: 3px; }
        .panel .line { display: flex; justify-content: space-between; font-size: 10px; margin: 2px 0; }
        .panel .line .label { color: #475569; }
        .panel .line .val { font-weight: 600; }
        .panel .total-line { border-top: 1px solid #94a3b8; margin-top: 4px; padding-top: 4px; font-weight: 700; font-size: 11px; }
        .reconciliation { border: 2px solid #0f172a; padding: 8px 10px; margin: 10px 0; }
        .reconciliation h3 { font-size: 9px; text-transform: uppercase; margin: 0 0 6px; color: #0f172a; }
        .reconciliation .row { display: flex; justify-content: space-between; font-size: 10px; margin: 3px 0; }
        .reconciliation .row .val { font-weight: 700; }
        .badge { display: inline-block; font-size: 9px; font-weight: 700; padding: 1px 6px; border-radius: 3px; text-transform: uppercase; }
        .badge-ok { background: #dcfce7; color: #166534; }
        .badge-bad { background: #fee2e2; color: #991b1b; }
        .sign { margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; text-align: center; }
        .sign div { border-top: 1px solid #0f172a; padding-top: 6px; font-size: 9px; color: #475569; }
        .footer { margin-top: 12px; font-size: 9px; color: #64748b; text-align: center; }
        @media print {
            body { padding: 0; background: #fff; font-size: 11px; }
            .sheet { box-shadow: none; max-width: none; padding: 0; width: {{ (int) ($settings['ticket']['width_mm'] ?? 80) }}mm; }
            @page { size: {{ (int) ($settings['ticket']['width_mm'] ?? 80) }}mm auto; margin: 4mm; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <header>
            <h1>{{ $settings['business']['name'] ?? config('app.name') }}</h1>
            <p>Corte del día · {{ $date->format('d/m/Y') }}</p>
            <div class="meta">
                <span>{{ $employee ? 'Empleado: '.$employee->name : 'Corte global' }}</span>
                <span>Versión {{ $cut?->version ?? 1 }}</span>
                <span>Generado {{ now()->format('d/m/Y H:i') }}</span>
            </div>
        </header>

        <h2>Resumen de ventas</h2>
        <table>
            <tbody>
                <tr><td>Ventas totales</td><td class="num">{{ $money($grandTotals['sales'] ?? 0) }}</td></tr>
                <tr><td>Ventas en efectivo</td><td class="num">{{ $money($salesSection['total_cash'] ?? 0) }}</td></tr>
                <tr><td>Otros medios de pago</td><td class="num">{{ $money($salesSection['total_non_cash'] ?? 0) }}</td></tr>
                <tr><td>Menos devoluciones</td><td class="num">−{{ $money($grandTotals['refunds'] ?? 0) }}</td></tr>
                <tr class="row-total"><td>Ventas netas</td><td class="num">{{ $money($grandTotals['net_sales'] ?? 0) }}</td></tr>
                <tr><td>Ganancia</td><td class="num">{{ $money($grandTotals['profit'] ?? 0) }}</td></tr>
                <tr><td>Tickets cobrados</td><td class="num">{{ $grandTotals['tickets'] ?? 0 }}</td></tr>
            </tbody>
        </table>

        <div class="grid">
            <div class="panel">
                <h3>Panel 1 — Computadora</h3>
                <div class="line"><span class="label">Dinero inicial</span><span class="val num">+{{ $money($computerPanel['initial'] ?? 0) }}</span></div>
                <div class="line"><span class="label">Entradas de efectivo</span><span class="val num">+{{ $money($computerPanel['cash_in_total'] ?? 0) }}</span></div>
                <div class="line"><span class="label">Ventas en efectivo</span><span class="val num">+{{ $money($computerPanel['cash_sales'] ?? 0) }}</span></div>
                <div class="line"><span class="label">Pagos de clientes</span><span class="val num">+{{ $money($computerPanel['customer_payments'] ?? 0) }}</span></div>
                <div class="line"><span class="label">− Devoluciones</span><span class="val num text-rose-600">−{{ $money($computerPanel['refunds'] ?? 0) }}</span></div>
                <div class="line"><span class="label">− Salidas de efectivo</span><span class="val num text-amber-600">−{{ $money($computerPanel['cash_out'] ?? 0) }}</span></div>
                <div class="line total-line"><span class="label">Subtotal en computadora</span><span class="val num">{{ $money($computerPanel['subtotal'] ?? 0) }}</span></div>
            </div>
            <div class="panel">
                <h3>Panel 2 — Caja</h3>
                <div class="line"><span class="label">Dinero inicial</span><span class="val num">+{{ $money($cashBoxPanel['initial'] ?? 0) }}</span></div>
                <div class="line"><span class="label">Entradas de efectivo</span><span class="val num">+{{ $money($cashBoxPanel['cash_in_total'] ?? 0) }}</span></div>
                <div class="line"><span class="label">Ventas (mov. caja)</span><span class="val num">+{{ $money($cashBoxPanel['sales_movements'] ?? 0) }}</span></div>
                <div class="line"><span class="label">Pagos de clientes</span><span class="val num">+{{ $money($cashBoxPanel['customer_payments'] ?? 0) }}</span></div>
                <div class="line"><span class="label">− Devoluciones</span><span class="val num text-rose-600">−{{ $money($cashBoxPanel['refunds'] ?? 0) }}</span></div>
                <div class="line"><span class="label">− Salidas de efectivo</span><span class="val num text-amber-600">−{{ $money($cashBoxPanel['cash_out'] ?? 0) }}</span></div>
                <div class="line total-line"><span class="label">Subtotal en caja</span><span class="val num">{{ $money($cashBoxPanel['subtotal'] ?? 0) }}</span></div>
            </div>
        </div>

        <div class="reconciliation">
            <h3>Conciliación</h3>
            <div class="row"><span class="label">En computadora</span><span class="val num">{{ $money($reconciliation['computer_subtotal'] ?? 0) }}</span></div>
            <div class="row"><span class="label">En caja</span><span class="val num">{{ $money($reconciliation['cash_box_subtotal'] ?? 0) }}</span></div>
            <div class="row total-line">
                <span class="label">Diferencia</span>
                <span class="val num
                    @php $diff = (float) ($reconciliation['difference'] ?? 0); @endphp
                    {{ abs($diff) < 0.005 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $money(abs($diff)) }}</span>
            </div>
            <div class="row">
                <span class="label">Estado</span>
                <span class="val
                    @php $isRec = (float) ($reconciliation['is_reconciled'] ?? false); @endphp
                    {{ $isRec ? 'badge badge-ok' : 'badge badge-bad' }}">{{ $isRec ? 'CUADRA' : 'NO CUADRA' }}</span>
            </div>
        </div>

        @if ($cut && $cut->cash_counted)
            <div class="reconciliation">
                <h3>Efectivo contado físicamente</h3>
                <div class="row"><span class="label">Monto contado</span><span class="val num">{{ $money($cut->cash_counted) }}</span></div>
                <div class="row"><span class="label">Sobrante / Faltante</span><span class="val num
                    @php $diff = (float) ($cut->difference ?? 0); @endphp
                    {{ $diff > 0 ? 'text-emerald-700' : ($diff < 0 ? 'text-rose-700' : 'text-slate-700') }}
                    ">{{ $diff > 0 ? '+' : '' }}{{ $money(abs($diff)) }}</span></div>
                <div class="row"><span class="label">Contado el</span><span class="val">{{ $cut->cash_counted_at?->format('d/m/Y H:i') }}</span></div>
                @if ($cut->reason)
                    <div class="row"><span class="label">Motivo</span><span class="val">{{ $cut->reason }}</span></div>
                @endif
            </div>
        @endif

        <h2>Desglose por departamento</h2>
        <table>
            <thead>
                <tr>
                    <th>Departamento</th>
                    <th class="num">Piezas</th>
                    <th class="num">Tickets</th>
                    <th class="num">Importe</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($departments as $department)
                    <tr>
                        <td>{{ $department['name'] }}</td>
                        <td class="num">{{ $department['quantity'] }}</td>
                        <td class="num">{{ $department['tickets'] }}</td>
                        <td class="num">{{ $money($department['total']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">Sin ventas registradas en esta fecha.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="sign">
            <div>Firma del cajero</div>
            <div>Firma del administrador</div>
        </div>

        <div class="footer">
            {{ $settings['ticket']['footer'] ?? 'Gracias por su compra.' }}<br>
            Corte generado en {{ config('app.name') }} · {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>
