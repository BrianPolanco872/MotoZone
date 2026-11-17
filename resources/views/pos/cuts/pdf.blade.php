<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen de Cortes por Empleado</title>
    <style>
        @page { size: letter portrait; margin: 15mm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #1f2937; font-size: 11px; margin: 0; padding: 0; }
        
        .header { display: table; width: 100%; border-bottom: 2px solid #ea580c; padding-bottom: 8px; margin-bottom: 20px; }
        .header-left { display: table-cell; vertical-align: bottom; }
        .header-right { display: table-cell; text-align: right; vertical-align: bottom; font-size: 10px; color: #4b5563; }
        .title { font-size: 18px; font-weight: bold; color: #111827; margin: 0; }
        .subtitle { font-size: 11px; color: #6b7280; margin-top: 2px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f3f4f6; color: #374151; font-weight: bold; font-size: 10px; text-transform: uppercase; text-align: left; padding: 10px 8px; border-bottom: 2px solid #d1d5db; }
        td { padding: 9px 8px; border-bottom: 1px solid #e5e7eb; color: #374151; font-size: 11px; }
        tr:nth-child(even) { background-color: #fafafa; }
        
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-red { color: #ef4444; }
        .text-green { color: #10b981; }

        /* FILA DE TOTAL DE TODOS JUNTOS */
        .total-row td { background-color: #e5e7eb; font-weight: bold; color: #111827; font-size: 12px; border-top: 2px solid #9ca3af; border-bottom: 2px solid #9ca3af; }
    </style>
</head>
<body>

    @php
        $cutDate = $date->format('d/m/Y');
        $employees = $breakdown['employees'] ?? [];
        $cashBoxPanel = $totals['cash_box_panel'] ?? [];
        $warning = $totals['reconciliation']['warning'] ?? $breakdown['total']['warning'] ?? null;
        $summary = [
            'sales' => $totals['totals']['sales'] ?? $totals['sales']['total'] ?? 0,
            'cash_in' => $cashBoxPanel['cash_in_total'] ?? 0,
            'cash_out' => $cashBoxPanel['cash_out'] ?? 0,
            'should_have' => $cashBoxPanel['subtotal'] ?? $totals['should_have'] ?? $breakdown['total']['should_have'] ?? 0,
            'difference' => $totals['reconciliation']['difference'] ?? 0,
        ];
        $money = fn ($value) => '$'.number_format((float) $value, 2);
    @endphp

    <div class="header">
        <div class="header-left">
            <h1 class="title">{{ $employee ? 'Corte por Empleado' : 'Resumen de Cortes por Empleado' }}</h1>
            <div class="subtitle">{{ $employee ? $employee->name : 'Corte diario consolidado' }} — {{ $cutDate }}</div>
        </div>
        <div class="header-right">
            <div><strong>Versión {{ $cut->version }}</strong></div>
            <div><strong>Generado:</strong> {{ $cut->generated_at?->format('d/m/Y H:i') ?? '—' }}</div>
        </div>
    </div>

    @if ($warning)
        <p class="text-red">{{ $warning }}</p>
    @endif
    @if (($totals['sales']['total_mixed'] ?? 0) > 0)
        <p>Pagos mixtos sin desglose: {{ $money($totals['sales']['total_mixed']) }}</p>
    @endif

    @if (! $employee && ! isset($breakdown))
        <p>Desglose por empleado no disponible en este corte antiguo. Se muestran únicamente los totales guardados.</p>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 25%;">EMPLEADO</th>
                <th class="text-right" style="width: 15%;">VENTAS TOTALES</th>
                <th class="text-right" style="width: 15%;">ENTRADAS EFECTIVO</th>
                <th class="text-right" style="width: 15%;">SALIDAS EFECTIVO</th>
                <th class="text-right" style="width: 15%;">DEBE HABER EN CAJA</th>
                <th class="text-right" style="width: 15%;">DIFERENCIA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $emp)
            <tr>
                <td class="font-bold">
                    {{ $emp['name'] }}
                    @if (! empty($emp['warning']))
                        <br><span class="text-red">{{ $emp['warning'] }}</span>
                    @endif
                </td>
                <td class="text-right">{{ $money($emp['sales'] ?? 0) }}</td>
                <td class="text-right text-green">{{ $money($emp['cash_in'] ?? 0) }}</td>
                <td class="text-right text-red">−{{ $money($emp['cash_out'] ?? 0) }}</td>
                <td class="text-right font-bold">{{ $money($emp['should_have'] ?? 0) }}</td>
                <td class="text-right">{{ $money($emp['difference'] ?? 0) }}</td>
            </tr>
            @endforeach

            <!-- TOTAL DE TODOS JUNTOS AL FINAL -->
            <tr class="total-row">
                <td class="font-bold">{{ $employee ? 'TOTAL EMPLEADO' : 'TOTAL DE TODOS JUNTOS' }}</td>
                <td class="text-right">{{ $money($summary['sales']) }}</td>
                <td class="text-right text-green">{{ $money($summary['cash_in']) }}</td>
                <td class="text-right text-red">−{{ $money($summary['cash_out']) }}</td>
                <td class="text-right font-bold" style="color: #ea580c;">
                    {{ $money($summary['should_have']) }}
                </td>
                <td class="text-right">{{ $money($summary['difference']) }}</td>
            </tr>
        </tbody>
    </table>
    <p class="subtitle">Ventas totales incluye otros medios de pago; no se suma al efectivo de caja. Diferencia: caja menos computadora.</p>

</body>
</html>
