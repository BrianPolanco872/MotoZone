<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte Consolidado de Corte Diario - {{ $date }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 20mm 15mm 20mm 15mm;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 0;
            font-family: "Helvetica", "Arial", sans-serif;
            font-size: 11px;
            color: #222;
            line-height: 1.4;
        }

        .page { width: 100%; max-width: 100%; }

        header {
            border-bottom: 3px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 18px;
            text-align: left;
        }

        .business-name {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #111827;
            margin: 0 0 4px 0;
        }

        .report-title {
            font-size: 17px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #111827;
            margin: 0 0 3px 0;
        }

        .report-subtitle {
            font-size: 10px;
            color: #6b7280;
            margin: 0 0 6px 0;
        }

        .meta {
            font-size: 9px;
            color: #6b7280;
            margin-top: 4px;
        }

        h2.section {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #111827;
            margin: 20px 0 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #d1d5db;
        }

        table { width: 100%; border-collapse: collapse; }

        th, td {
            padding: 6px 8px;
            border: 1px solid #d1d5db;
            font-size: 9.5px;
            text-align: left;
            vertical-align: middle;
        }

        thead th {
            background: #111827;
            color: #fff;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        td.num, th.num { text-align: right; white-space: nowrap; }
        td.center, th.center { text-align: center; }

        .summary-table td:first-child {
            font-weight: 700;
            color: #374151;
            width: 55%;
        }

        .summary-table td.value {
            font-weight: 800;
            color: #111827;
        }

        .summary-table tr.total-row,
        .employee-table tr.total-row {
            font-weight: 800;
            background: #f3f4f6;
            border-top: 2px solid #111827;
        }

        .badge {
            display: inline-block;
            font-size: 7.5px;
            font-weight: 800;
            padding: 1.5px 6px;
            border-radius: 3px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .badge-completed { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef3c7; color: #92400e; }

        .footer {
            margin-top: 24px;
            border-top: 1px solid #d1d5db;
            padding-top: 8px;
            font-size: 8px;
            color: #6b7280;
            text-align: center;
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="page">
        @if (! empty($totals['warning']))
            <p>{{ $totals['warning'] }}</p>
        @endif
        <header>
            <table width="100%" style="border: none;">
                <tr>
                    <td style="border: none; padding: 0;">
                        <p class="business-name">MotoZone</p>
                        <p class="report-title">Reporte Consolidado de Corte Diario</p>
                        <p class="report-subtitle">Fecha del corte: {{ $date }} &bull; Generado: {{ now()->format('d/m/Y H:i') }}</p>
                    </td>
                    <td style="border: none; padding: 0; text-align: right;">
                        <p class="meta">{{ config('app.name') }}</p>
                    </td>
                </tr>
            </table>
        </header>

        <h2 class="section">Resumen Ejecutivo</h2>
        <table class="summary-table">
            <tbody>
                <tr>
                    <td>Total Ventas</td>
                    <td class="num value">{{ $money($totals['sales'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td>Total Efectivo</td>
                    <td class="num value">{{ $money($totals['cash'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td>Total Electr&oacute;nico / Tarjeta</td>
                    <td class="num value">{{ $money($totals['other_payments'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td>Salidas / Gastos</td>
                    <td class="num value">{{ $money($totals['cash_out'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td>Total Final en Gaveta</td>
                    <td class="num value">{{ $money($totals['should_have'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td>Estado de Conciliaci&oacute;n</td>
                    <td class="num value">
                        @php
                            $diff = (float) ($totals['difference'] ?? 0);
                            $isRec = ! empty($totals['is_reconciled']);
                        @endphp
                        {{ $isRec ? 'Cuadra' : 'Diferencia' }}
                        @if (! $isRec)
                            ({{ $money(abs($diff)) }})
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        @if ($employees->isNotEmpty())
            <h2 class="section">Desglose por Empleado</h2>
            <table class="employee-table">
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th class="num">Tickets</th>
                        <th class="num">Ventas del vendedor</th>
                        <th class="num">Efectivo cobrado</th>
                        <th class="num">Ventas electr&oacute;nicas</th>
                        <th class="num">Salidas</th>
                        <th class="num">En Gaveta</th>
                        <th class="center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($employees as $row)
                        <tr>
                            <td>
                                <div style="font-weight: 800;">{{ $row['name'] }}</div>
                                <div style="font-size: 8px; color: #6b7280;">{{ $row['email'] ?? '&mdash;' }}</div>
                            </td>
                            <td class="num">{{ (int) ($row['tickets'] ?? 0) }}</td>
                            <td class="num">{{ $money($row['sales'] ?? 0) }}</td>
                            <td class="num">{{ $money($row['cash'] ?? 0) }}</td>
                            <td class="num">{{ $money($row['other_payments'] ?? 0) }}</td>
                            <td class="num">{{ $money($row['cash_out'] ?? 0) }}</td>
                            <td class="num">{{ $money($row['should_have'] ?? 0) }}</td>
                            <td class="center">
                                @php
                                    $isClosed = ! empty($row['is_day_closed']);
                                @endphp
                                <span class="badge {{ $isClosed ? 'badge-completed' : 'badge-pending' }}">
                                    {{ $isClosed ? 'Completado' : 'Pendiente' }}
                                </span>
                                @if ($isClosed && $row['closed_at'])
                                    <div style="font-size: 7.5px; color: #6b7280; margin-top: 2px;">
                                        {{ \Carbon\Carbon::parse($row['closed_at'])->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td>Total Consolidado</td>
                        <td class="num">{{ (int) ($totals['tickets'] ?? 0) }}</td>
                        <td class="num">{{ $money($totals['sales'] ?? 0) }}</td>
                        <td class="num">{{ $money($totals['cash'] ?? 0) }}</td>
                        <td class="num">{{ $money($totals['other_payments'] ?? 0) }}</td>
                        <td class="num">{{ $money($totals['cash_out'] ?? 0) }}</td>
                        <td class="num">{{ $money($totals['should_have'] ?? 0) }}</td>
                        <td class="center">&mdash;</td>
                    </tr>
                </tbody>
            </table>
        @else
            <div class="empty">No hay cortes globales registrados para la fecha seleccionada.</div>
        @endif

        <div class="footer">
            Reporte generado en {{ config('app.name') }} &bull; {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>
