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
        // Normalización de datos para el PDF simplificado
        $cutDate = $date ? $date->format('d/m/Y') : date('d/m/Y');

        $employees = collect($breakdown['employees'] ?? [])->map(function ($e) {
            return (object) [
                'name' => $e['name'] ?? '—',
                'total_sales' => (float) ($e['sales'] ?? 0),
                'cash_sales' => (float) ($e['cash_in'] ?? 0),
                'expenses' => (float) ($e['cash_out'] ?? 0),
            ];
        });
    @endphp

    <div class="header">
        <div class="header-left">
            <h1 class="title">Resumen de Cortes por Empleado</h1>
            <div class="subtitle">Corte diario consolidado — {{ $cutDate }}</div>
        </div>
        <div class="header-right">
            <div><strong>Generado:</strong> {{ date('d/m/Y H:i') }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30%;">EMPLEADO</th>
                <th class="text-right" style="width: 17.5%;">VENTAS TOTALES</th>
                <th class="text-right" style="width: 17.5%;">INGRESO DE DINERO</th>
                <th class="text-right" style="width: 17.5%;">SALIDA</th>
                <th class="text-right" style="width: 17.5%;">TOTAL EMPLEADO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $emp)
            <tr>
                <td class="font-bold">{{ $emp->name }}</td>
                <td class="text-right">${{ number_format($emp->total_sales, 2) }}</td>
                <td class="text-right text-green">${{ number_format($emp->cash_sales, 2) }}</td>
                <td class="text-right text-red">-${{ number_format(abs($emp->expenses), 2) }}</td>
                <td class="text-right font-bold">${{ number_format(($emp->total_sales + $emp->cash_sales) - abs($emp->expenses), 2) }}</td>
            </tr>
            @endforeach

            <!-- TOTAL DE TODOS JUNTOS AL FINAL -->
            <tr class="total-row">
                <td class="font-bold">TOTAL DE TODOS JUNTOS</td>
                <td class="text-right">${{ number_format($employees->sum('total_sales'), 2) }}</td>
                <td class="text-right text-green">${{ number_format($employees->sum('cash_sales'), 2) }}</td>
                <td class="text-right text-red">-${{ number_format(abs($employees->sum('expenses')), 2) }}</td>
                <td class="text-right font-bold" style="color: #ea580c;">
                    ${{ number_format(($employees->sum('total_sales') + $employees->sum('cash_sales')) - abs($employees->sum('expenses')), 2) }}
                </td>
            </tr>
        </tbody>
    </table>

</body>
</html>
