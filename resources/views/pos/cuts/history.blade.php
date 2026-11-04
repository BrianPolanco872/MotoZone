@extends('layouts.admin')

@section('title', 'Historial de cortes')
@section('subtitle', 'Todas las versiones de cortes guardadas')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-500">Cada vez que se hace un corte se guarda una nueva versión inmutable. Las versiones anteriores se conservan.</p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('pos.cuts.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-700 px-4 text-sm font-semibold text-white transition hover:bg-brand-800">
                <x-icon name="report" class="size-4" />
                Corte actual
            </a>
            <a href="{{ route('pos.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                <x-icon name="arrow-uturn-left" class="size-4" />
                Volver al POS
            </a>
        </div>
    </div>

    <section class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-5 py-3 font-medium">Fecha</th>
                        <th class="px-5 py-3 font-medium">Alcance</th>
                        <th class="px-5 py-3 font-medium">Empleado</th>
                        <th class="px-5 py-3 font-center">Versión</th>
                        <th class="px-5 py-3 font-medium">Generado</th>
                        <th class="px-5 py-3 font-medium">Por</th>
                        <th class="px-5 py-3 text-right font-medium">Ventas</th>
                        <th class="px-5 py-3 text-right font-medium">Caja</th>
                        <th class="px-5 py-3 text-right font-medium">Diferencia</th>
                        <th class="px-5 py-3 font-medium">Estado</th>
                        <th class="px-5 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($cuts as $cut)
                        @php
                            $totals = $cut->totals ?? [];
                            $computer = (float) ($totals['computer_panel']['subtotal'] ?? 0);
                            $cashBox = (float) ($totals['cash_box_panel']['subtotal'] ?? 0);
                            $diff = round($cashBox - $computer, 2);
                            $isRec = abs($diff) < 0.005;
                        @endphp
                        <tr class="transition hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-3 font-semibold text-slate-900">{{ $cut->cut_date->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $cut->scope === 'global' ? 'Global' : 'Por empleado' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $cut->user?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-center font-mono text-xs font-semibold text-slate-900">v{{ $cut->version }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $cut->generated_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $cut->generatedBy?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums text-slate-700">${{ number_format((float) ($totals['totals']['sales'] ?? 0), 2) }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums text-slate-700">${{ number_format($cashBox, 2) }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums text-slate-700">
                                ${{ number_format(abs($diff), 2) }}
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold
                                    {{ $isRec ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                    {{ $isRec ? 'Cuadra' : 'Diferencia' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('pos.cuts.print', ['date' => $cut->cut_date->toDateString(), 'employee' => $cut->user_id, 'version' => $cut->version]) }}" target="_blank" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                                        <x-icon name="printer" class="size-3.5" />
                                        Imprimir
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-5 py-14 text-center">
                                <p class="text-sm font-medium text-slate-700">Todavía no hay cortes guardados</p>
                                <a href="{{ route('pos.cuts.index') }}" class="mt-2 inline-block text-xs font-medium text-brand-700 hover:text-brand-800">Generar el corte de hoy</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $cuts->links() }}</div>
@endsection
