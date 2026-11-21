@extends('layouts.admin')

@section('title', 'Ventas del día')
@section('subtitle', 'Tickets cobrados y totales por fecha')

@section('content')
    {{-- Barra superior de navegación y filtros --}}
    <div class="rounded-2xl border border-mz-border bg-gradient-to-r from-mz-bg-surface via-mz-bg-surface/95 to-mz-bg-surface p-4 sm:p-5 shadow-xl shadow-black/25">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <form action="{{ route('pos.sales.day') }}" method="GET" class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="sales-date" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Fecha de corte</label>
                    <input id="sales-date" type="date" name="date" value="{{ $date->toDateString() }}" class="mz-input mt-1.5 h-11 text-sm font-semibold rounded-xl">
                </div>

                @if ($canSeeAny)
                    <div>
                        <label for="sales-employee" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Cajero</label>
                        <select id="sales-employee" name="employee" class="mz-select mt-1.5 h-11 text-sm font-semibold rounded-xl">
                            <option value="">Todos los cajeros</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected((int) request('employee') === $employee->id)>{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-mz-accent-primary hover:bg-mz-accent-hover text-white px-5 text-xs font-bold transition-all shadow-md shadow-mz-accent-primary/20 active:scale-95">
                    <x-icon name="search" class="size-4" />
                    Consultar
                </button>
            </form>

            <div class="flex items-center gap-2">
                <a href="{{ route('pos.index') }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/80 hover:bg-mz-bg-surface-2 px-4 text-xs font-bold text-mz-text-secondary hover:text-white transition-all shadow-xs active:scale-95">
                    <x-icon name="arrow-uturn-left" class="size-4" />
                    Volver al POS
                </a>
            </div>
        </div>
    </div>

    {{-- Tarjetas de métricas del día --}}
    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-mz-border bg-mz-bg-surface p-5 shadow-xl shadow-black/20 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-mz-text-muted">Tickets cobrados</p>
                <p class="mt-1 font-display text-3xl font-extrabold tabular-nums text-white">{{ $summary['count'] }}</p>
            </div>
            <div class="flex size-11 items-center justify-center rounded-xl bg-mz-bg-surface-2 text-mz-accent-primary border border-mz-border-light">
                <x-icon name="receipt" class="size-5" />
            </div>
        </div>

        <div class="rounded-2xl border border-mz-border bg-mz-bg-surface p-5 shadow-xl shadow-black/20 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-mz-text-muted">Total facturado</p>
                <p class="mt-1 font-display text-3xl font-extrabold tabular-nums text-mz-accent-primary">${{ number_format($summary['total'], 2) }}</p>
            </div>
            <div class="flex size-11 items-center justify-center rounded-xl bg-mz-accent-primary/10 text-mz-accent-primary border border-mz-accent-primary/25">
                <x-icon name="banknotes" class="size-5" />
            </div>
        </div>

        <div class="rounded-2xl border border-mz-border bg-mz-bg-surface p-5 shadow-xl shadow-black/20 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-mz-text-muted">Fecha visualizada</p>
                <p class="mt-1 font-display text-2xl font-bold text-white">{{ $date->format('d/m/Y') }}</p>
            </div>
            <div class="flex size-11 items-center justify-center rounded-xl bg-mz-bg-surface-2 text-mz-text-muted border border-mz-border-light">
                <x-icon name="clock" class="size-5" />
            </div>
        </div>
    </div>

    {{-- Tabla de tickets --}}
    <section class="mt-5 rounded-2xl border border-mz-border bg-mz-bg-surface shadow-xl shadow-black/20 overflow-hidden">
        <div class="overflow-x-auto pos-scrollbar">
            <table class="min-w-full divide-y divide-mz-border text-sm">
                <thead class="bg-mz-bg-surface-2/40">
                    <tr class="text-left text-xs uppercase tracking-wider text-mz-text-muted">
                        <th class="px-5 py-3.5 font-semibold">Hora</th>
                        <th class="px-5 py-3.5 font-semibold">Folio</th>
                        @if ($canSeeAny)
                            <th class="px-5 py-3.5 font-semibold">Cajero</th>
                        @endif
                        <th class="px-5 py-3.5 font-semibold">Artículos</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Total</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mz-border-light/40">
                    @forelse ($sales as $sale)
                        <tr class="group transition-colors duration-150 hover:bg-mz-bg-surface-2/40">
                            <td class="whitespace-nowrap px-5 py-3.5 text-mz-text-muted text-xs font-mono">{{ $sale->paid_at?->format('H:i') }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs font-bold text-white">
                                <span class="rounded-md bg-mz-bg-surface-2 px-2 py-1 border border-mz-border-light">{{ $sale->folio }}</span>
                            </td>
                            @if ($canSeeAny)
                                <td class="whitespace-nowrap px-5 py-3.5 text-mz-text-secondary font-medium">{{ $sale->user?->name }}</td>
                            @endif
                            <td class="px-5 py-3.5 text-mz-text-secondary">
                                <span class="inline-flex items-center gap-1.5 font-medium">
                                    {{ \App\Support\Quantity::format($sale->items->sum('quantity')) }} · cantidad vendida (medidas mixtas)
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right font-display text-base font-extrabold tabular-nums text-white">${{ number_format((float) $sale->total, 2) }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if (auth()->user()->can('pos.tickets.print.any') || $sale->user_id === auth()->id())
                                        <a href="{{ route('pos.sales.ticket', $sale) }}" target="_blank"
                                           class="inline-flex items-center gap-1 rounded-lg border border-mz-border-light bg-mz-bg-surface-2/80 hover:bg-mz-bg-surface-2 px-2.5 py-1.5 text-xs font-bold text-mz-text-secondary hover:text-white transition-all shadow-xs active:scale-95">
                                            <x-icon name="printer" class="size-3.5 text-mz-accent-primary" />
                                            Ticket
                                        </a>
                                    @endif

                                    @if ($canRequestReturns && $sale->items->contains(fn ($item) => $item->availableReturnQuantity() > 0))
                                        <a href="{{ route('pos.returns.create', $sale) }}"
                                           class="inline-flex items-center gap-1 rounded-lg border border-mz-warning/40 bg-mz-warning/10 hover:bg-mz-warning/20 px-2.5 py-1.5 text-xs font-bold text-mz-warning transition-all shadow-xs active:scale-95">
                                            <x-icon name="arrow-uturn-left" class="size-3.5" />
                                            Devolución
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canSeeAny ? 6 : 5 }}" class="px-5 py-16 text-center">
                                <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-mz-bg-surface-2 text-mz-text-muted">
                                    <x-icon name="receipt" class="size-7" />
                                </div>
                                <p class="mt-3 text-sm font-bold text-mz-text-primary">No hay ventas registradas en esta fecha</p>
                                <a href="{{ route('pos.index') }}" class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-mz-accent-primary hover:text-mz-accent-hover">
                                    Ir a cobrar al punto de venta &rarr;
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
