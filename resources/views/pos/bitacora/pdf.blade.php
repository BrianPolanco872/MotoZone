<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Bitácora de cortes</title>
    <style>
        @page { size: letter landscape; margin: 14mm 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #1f2937; font-family: Helvetica, Arial, sans-serif; font-size: 9px; }
        header { border-bottom: 2px solid #ea580c; margin-bottom: 14px; padding-bottom: 9px; }
        h1 { color: #111827; font-size: 19px; margin: 0 0 4px; }
        .subtitle, .meta { color: #6b7280; font-size: 9px; }
        .meta { margin-top: 7px; }
        table { border-collapse: collapse; margin-top: 10px; width: 100%; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th { background: #111827; color: #fff; font-size: 8px; text-align: left; text-transform: uppercase; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: middle; }
        td { font-size: 8.5px; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        .right { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .empty { color: #6b7280; padding: 20px; text-align: center; }
        .status-ok { color: #047857; font-weight: bold; }
        .status-diff { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
    <header>
        <h1>Bitácora de cortes</h1>
        <div class="subtitle">Reporte consolidado de snapshots guardados</div>
        <div class="meta">
            <strong>Fecha:</strong> {{ $filters['date'] ? \Illuminate\Support\Carbon::parse($filters['date'])->format('d/m/Y') : 'Todas' }}
            &nbsp; | &nbsp;
            <strong>Empleado:</strong> {{ $selectedEmployee?->name ?? 'Todos (global)' }}
            &nbsp; | &nbsp;
            <strong>Registros:</strong> {{ $cuts->count() }}
            &nbsp; | &nbsp;
            <strong>Generado:</strong> {{ $generatedAt->format('d/m/Y H:i') }}
        </div>
    </header>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Alcance / empleado</th>
                <th class="center">Versión</th>
                <th>Generado</th>
                <th>Por</th>
                <th class="right">Ventas</th>
                <th class="right">Caja esperada</th>
                <th class="right">Diferencia</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cuts as $cut)
                @php
                    $totals = $cut->totals ?? [];
                    $sales = (float) data_get($totals, 'totals.sales', data_get($totals, 'sales.total', 0));
                    $cashExpected = (float) data_get($totals, 'cash_box_panel.subtotal', 0);
                @endphp
                <tr>
                    <td>{{ $cut->cut_date->format('d/m/Y') }}</td>
                    <td>{{ $cut->scope === \App\Models\DailyCut::SCOPE_GLOBAL ? 'Todos (global)' : ($cut->user?->name ?? 'Empleado eliminado') }}</td>
                    <td class="center">v{{ $cut->version }}</td>
                    <td>{{ $cut->generated_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $cut->generatedBy?->name ?? '—' }}</td>
                    <td class="right">${{ number_format($sales, 2) }}</td>
                    <td class="right">${{ number_format($cashExpected, 2) }}</td>
                    <td class="right">{{ $cut->difference === null ? '—' : '$'.number_format((float) $cut->difference, 2) }}</td>
                    <td>
                        @if ($cut->is_reconciled === null)
                            Sin dato
                        @elseif ($cut->is_reconciled)
                            <span class="status-ok">Cuadra</span>
                        @else
                            <span class="status-diff">Diferencia</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">No se encontraron cortes con los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
