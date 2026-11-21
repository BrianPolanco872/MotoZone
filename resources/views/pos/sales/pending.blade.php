@extends('layouts.admin')

@section('title', 'Ventas pendientes')
@section('subtitle', 'Tickets guardados sin cobrar')

@section('content')
    {{-- Barra superior --}}
    <div class="rounded-2xl border border-mz-border bg-gradient-to-r from-mz-bg-surface via-mz-bg-surface/95 to-mz-bg-surface p-4 sm:p-5 shadow-xl shadow-black/25">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-mz-warning/15 text-mz-warning border border-mz-warning/25">
                    <x-icon name="pause" class="size-5" />
                </div>
                <div>
                    <h2 class="text-sm font-bold text-white">Tickets en espera</h2>
                    <p class="text-xs text-mz-text-muted mt-0.5">No descuentan inventario ni afectan caja hasta que se realiza el cobro.</p>
                </div>
            </div>

            <a href="{{ route('pos.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/80 hover:bg-mz-bg-surface-2 px-4 text-xs font-bold text-mz-text-secondary hover:text-white transition-all shadow-xs active:scale-95">
                <x-icon name="arrow-uturn-left" class="size-4" />
                Volver al POS
            </a>
        </div>
    </div>

    {{-- Tabla de ventas pendientes --}}
    <section class="mt-5 rounded-2xl border border-mz-border bg-mz-bg-surface shadow-xl shadow-black/20 overflow-hidden">
        <div class="flex items-center justify-between border-b border-mz-border px-5 py-3.5 bg-mz-bg-surface-2/30">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-mz-warning animate-pulse"></span>
                <h3 class="text-xs font-bold uppercase tracking-wider text-mz-text-primary">Pendientes por liquidar</h3>
            </div>
            <span class="rounded-full bg-mz-warning/15 border border-mz-warning/30 px-3 py-0.5 text-xs font-bold text-mz-warning">
                {{ $sales->total() }} {{ $sales->total() === 1 ? 'ticket' : 'tickets' }}
            </span>
        </div>

        <div class="overflow-x-auto pos-scrollbar">
            <table class="min-w-full divide-y divide-mz-border text-sm">
                <thead class="bg-mz-bg-surface-2/40">
                    <tr class="text-left text-xs uppercase tracking-wider text-mz-text-muted">
                        <th class="px-5 py-3.5 font-semibold">Folio</th>
                        <th class="px-5 py-3.5 font-semibold">Hora de guardado</th>
                        @if ($canSeeAny)
                            <th class="px-5 py-3.5 font-semibold">Cajero</th>
                        @endif
                        <th class="px-5 py-3.5 font-semibold">Artículos</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Total a cobrar</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mz-border-light/40">
                    @forelse ($sales as $sale)
                        <tr class="group transition-colors duration-150 hover:bg-mz-bg-surface-2/40">
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs font-bold text-white">
                                <span class="rounded-md bg-mz-bg-surface-2 px-2 py-1 border border-mz-border-light">{{ $sale->folio }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-xs text-mz-text-muted font-mono">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                            @if ($canSeeAny)
                                <td class="whitespace-nowrap px-5 py-3.5 text-sm font-medium text-mz-text-secondary">{{ $sale->user?->name }}</td>
                            @endif
                            <td class="px-5 py-3.5 text-sm text-mz-text-secondary">
                                <span class="font-semibold">{{ \App\Support\Quantity::format($sale->items->sum('quantity')) }} · cantidad (medidas mixtas)</span>
                                <span class="block text-xs text-mz-text-muted">({{ $sale->items->count() }} producto{{ $sale->items->count() === 1 ? '' : 's' }})</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right font-display text-base font-extrabold tabular-nums text-mz-accent-primary">${{ number_format((float) $sale->total, 2) }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('pos.index', ['pending' => $sale->id]) }}"
                                       class="inline-flex items-center gap-1.5 rounded-lg bg-mz-accent-primary hover:bg-mz-accent-hover text-white px-3 py-1.5 text-xs font-bold transition-all shadow-md shadow-mz-accent-primary/20 active:scale-95">
                                        <x-icon name="banknotes" class="size-3.5" />
                                        Cobrar ahora
                                    </a>
                                    <form action="{{ route('pos.sales.cancel', $sale) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas cancelar esta venta pendiente?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-mz-border-light px-2.5 py-1.5 text-xs font-bold text-mz-text-muted hover:border-mz-error/40 hover:bg-mz-error/10 hover:text-mz-error transition-all active:scale-95">
                                            <x-icon name="trash" class="size-3.5" />
                                            Cancelar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canSeeAny ? 6 : 5 }}" class="px-5 py-16 text-center">
                                <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-mz-bg-surface-2 text-mz-text-muted">
                                    <x-icon name="pause" class="size-7" />
                                </div>
                                <p class="mt-3 text-sm font-bold text-mz-text-primary">No hay ventas pendientes por cobrar</p>
                                <a href="{{ route('pos.index') }}" class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-mz-accent-primary hover:text-mz-accent-hover">
                                    Ir al punto de venta &rarr;
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $sales->links() }}</div>
@endsection
