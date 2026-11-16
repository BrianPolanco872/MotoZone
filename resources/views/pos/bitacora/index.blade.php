@extends('layouts.admin')

@section('title', 'Bitácora')
@section('subtitle', 'Consulta histórica de cortes de caja')

@section('content')
    <section class="rounded-2xl border border-mz-border bg-gradient-to-r from-mz-bg-surface via-mz-bg-surface/95 to-mz-bg-surface p-4 shadow-xl shadow-black/20 sm:p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-base font-bold text-mz-text-primary">Bitácora de cortes</h2>
                <p class="mt-1 text-sm text-mz-text-muted">Consulta snapshots guardados por día y empleado.</p>
            </div>
            <a href="{{ $pdfUrl }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-mz-accent-primary px-4 text-xs font-bold text-white shadow-md shadow-mz-accent-primary/20 transition hover:bg-mz-accent-hover active:scale-95">
                <x-icon name="report" class="size-4" />
                Descargar PDF consolidado
            </a>
        </div>

        <form method="GET" action="{{ route('pos.bitacora.index') }}" class="mt-5 flex flex-wrap items-end gap-3 border-t border-mz-border-light/60 pt-4">
            <div>
                <label for="bitacora-date" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Fecha</label>
                <input id="bitacora-date" type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="mz-input mt-1.5 h-10 w-48 rounded-xl text-sm">
            </div>
            <div>
                <label for="bitacora-employee" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Empleado</label>
                <select id="bitacora-employee" name="employee" class="mz-input mt-1.5 h-10 min-w-56 rounded-xl text-sm">
                    <option value="" @selected($filters['employee'] === null)>Todos (global)</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected($filters['employee'] === $employee->id)>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-mz-accent-primary px-5 text-xs font-bold text-white transition hover:bg-mz-accent-hover active:scale-95">
                    <x-icon name="search" class="size-4" />
                    Filtrar
                </button>
                <a href="{{ route('pos.bitacora.index') }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-mz-border-light bg-mz-bg-surface-2 px-4 text-xs font-bold text-mz-text-secondary transition hover:text-white">
                    Limpiar
                </a>
            </div>
        </form>
    </section>

    <section class="mt-5 overflow-hidden rounded-2xl border border-mz-border bg-mz-bg-surface shadow-xl shadow-black/20">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-mz-border-light px-4 py-3 sm:px-5">
            <h2 class="text-sm font-bold text-mz-text-primary">Cortes encontrados</h2>
            <p class="text-xs text-mz-text-muted">
                {{ $cuts->total() }} {{ $cuts->total() === 1 ? 'registro' : 'registros' }}
                @if ($filters['date'])
                    · {{ \Illuminate\Support\Carbon::parse($filters['date'])->format('d/m/Y') }}
                @endif
                · {{ $selectedEmployee?->name ?? 'Todos (global)' }}
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-mz-border text-sm">
                <thead class="bg-mz-bg-surface-2/40">
                    <tr class="text-left text-xs uppercase tracking-wider text-mz-text-muted">
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">Fecha</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Alcance / empleado</th>
                        <th scope="col" class="px-4 py-3 text-center font-semibold">Versión</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">Generado</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Por</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-semibold">Ventas</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-semibold">Caja esperada</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-semibold">Diferencia</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mz-border-light/40">
                    @forelse ($cuts as $cut)
                        @php
                            $totals = $cut->totals ?? [];
                            $sales = (float) data_get($totals, 'totals.sales', data_get($totals, 'sales.total', 0));
                            $cashExpected = (float) data_get($totals, 'cash_box_panel.subtotal', 0);
                        @endphp
                        <tr class="transition-colors hover:bg-mz-bg-surface-2/40">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-mz-text-primary">{{ $cut->cut_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-xs text-mz-text-secondary">
                                {{ $cut->scope === \App\Models\DailyCut::SCOPE_GLOBAL ? 'Todos (global)' : ($cut->user?->name ?? 'Empleado eliminado') }}
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-xs font-bold text-mz-accent-primary">v{{ $cut->version }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-mz-text-muted">{{ $cut->generated_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-xs text-mz-text-secondary">{{ $cut->generatedBy?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-mz-text-primary">${{ number_format($sales, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-mz-text-primary">${{ number_format($cashExpected, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-mz-text-secondary">
                                {{ $cut->difference === null ? '—' : '$'.number_format((float) $cut->difference, 2) }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($cut->is_reconciled === null)
                                    <span class="text-xs text-mz-text-muted">Sin dato</span>
                                @else
                                    <span @class([
                                        'inline-flex rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                        'border-mz-success/30 bg-mz-success/15 text-mz-success' => $cut->is_reconciled,
                                        'border-mz-error/30 bg-mz-error/15 text-mz-error' => ! $cut->is_reconciled,
                                    ])>
                                        {{ $cut->is_reconciled ? 'Cuadra' : 'Diferencia' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-14 text-center">
                                <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-mz-bg-surface-2 text-mz-text-muted">
                                    <x-icon name="report" class="size-6" />
                                </div>
                                <p class="mt-3 text-sm font-semibold text-mz-text-primary">No se encontraron cortes con esos filtros.</p>
                                <p class="mt-1 text-xs text-mz-text-muted">Prueba otra fecha o selecciona otro empleado.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($cuts->hasPages())
        <nav class="mt-5" aria-label="Paginación de Bitácora">
            {{ $cuts->links() }}
        </nav>
    @endif
@endsection
