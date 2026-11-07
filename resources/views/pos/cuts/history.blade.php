@extends('layouts.admin')

@section('title', 'Historial de cortes')
@section('subtitle', 'Todas las versiones de cortes guardadas')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-mz-text-muted">Cada vez que se hace un corte se guarda una nueva versión inmutable. Las versiones anteriores se conservan.</p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('pos.cuts.index') }}" class="x-button x-button-primary size-sm">
                <x-icon name="report" class="size-4" />
                Corte actual
            </a>
            <a href="{{ route('pos.index') }}" class="x-button x-button-secondary size-sm">
                <x-icon name="arrow-uturn-left" class="size-4" />
                Volver al POS
            </a>
        </div>
    </div>

    {{-- Filtros de historial --}}
    <form method="GET" action="{{ route('pos.cuts.history') }}" class="mt-4 mz-card mz-card-padded grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="flex flex-col gap-1">
            <label for="hist-from" class="text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Desde</label>
            <input id="hist-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mz-input mt-1.5 h-10">
        </div>
        <div class="flex flex-col gap-1">
            <label for="hist-to" class="text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Hasta</label>
            <input id="hist-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mz-input mt-1.5 h-10">
        </div>
        @if ($employees->isNotEmpty())
            <div class="flex flex-col gap-1">
                <label for="hist-employee" class="text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Empleado</label>
                <select id="hist-employee" name="employee" class="mz-select mt-1.5 h-10">
                    <option value="">Todos los empleados</option>
                    @foreach ($employees as $emp)
                        <option value="{{ $emp->id }}" @selected($selectedEmployee === $emp->id)>{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">&nbsp;</label>
            <button type="submit" class="x-button x-button-primary size-sm mt-1.5 h-10">Filtrar</button>
            <a href="{{ route('pos.cuts.history') }}" class="x-button x-button-secondary size-sm mt-1.5 h-10">Limpiar</a>
        </div>
    </form>

    <section class="mt-4 mz-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-mz-border-light text-sm">
                <thead class="bg-mz-bg-surface">
                    <tr class="text-left text-xs uppercase tracking-wide text-mz-text-muted">
                        <th class="px-4 py-3 font-medium">Fecha</th>
                        <th class="px-4 py-3 font-medium">Alcance</th>
                        <th class="px-4 py-3 font-medium">Empleado</th>
                        <th class="px-4 py-3 font-center">Versión</th>
                        <th class="px-4 py-3 font-medium">Generado</th>
                        <th class="px-4 py-3 font-medium">Por</th>
                        <th class="px-4 py-3 text-right font-medium">Ventas</th>
                        <th class="px-4 py-3 text-right font-medium">Caja</th>
                        <th class="px-4 py-3 text-right font-medium">Diferencia</th>
                        <th class="px-4 py-3 font-medium">Estado</th>
                        <th class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mz-border-light">
                    @forelse ($cuts as $cut)
                        @php
                            $totals = $cut->totals ?? [];
                            $computer = (float) ($totals['computer_panel']['subtotal'] ?? 0);
                            $cashBox = (float) ($totals['cash_box_panel']['subtotal'] ?? 0);
                            $diff = round($cashBox - $computer, 2);
                            $isRec = abs($diff) < 0.005;
                        @endphp
                        <tr class="transition hover:bg-mz-bg-surface-2">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-mz-text-primary">{{ $cut->cut_date->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-mz-text-secondary">{{ $cut->scope === 'global' ? 'Global' : 'Por empleado' }}</td>
                            <td class="px-4 py-3 text-mz-text-secondary">{{ $cut->user?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-center font-mono text-xs font-semibold text-mz-text-primary">v{{ $cut->version }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-mz-text-secondary">{{ $cut->generated_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-mz-text-secondary">{{ $cut->generatedBy?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-mz-text-secondary">${{ number_format((float) ($totals['totals']['sales'] ?? 0), 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-mz-text-secondary">${{ number_format($cashBox, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-mz-text-secondary">
                                ${{ number_format(abs($diff), 2) }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold
                                    {{ $isRec ? 'bg-mz-success/10 text-mz-success' : 'bg-mz-error/10 text-mz-error' }}">
                                    {{ $isRec ? 'Cuadra' : 'Diferencia' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('pos.cuts.print', ['date' => $cut->cut_date->toDateString(), 'employee' => $cut->user_id, 'version' => $cut->version]) }}" target="_blank" class="x-button x-button-secondary size-xs">
                                        <x-icon name="printer" class="size-3.5" />
                                        Imprimir
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-14 text-center">
                                <p class="text-sm font-medium text-mz-text-secondary">Todavía no hay cortes guardados</p>
                                <a href="{{ route('pos.cuts.index') }}" class="mt-2 inline-block text-xs font-medium text-mz-accent-primary hover:text-mz-accent-hover">Generar el corte de hoy</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-4">{{ $cuts->links() }}</div>
@endsection
