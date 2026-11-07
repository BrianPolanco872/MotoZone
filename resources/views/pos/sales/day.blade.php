@extends('layouts.admin')

@section('title', 'Ventas del día')
@section('subtitle', 'Tickets cobrados y totales por fecha')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <form action="{{ route('pos.sales.day') }}" method="GET" class="flex flex-wrap items-end gap-2">
            <div>
                <label for="sales-date" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Fecha</label>
                <input id="sales-date" type="date" name="date" value="{{ $date->toDateString() }}" class="mz-input mt-1.5 h-10">
            </div>

            @if ($canSeeAny)
                <div>
                    <label for="sales-employee" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Cajero</label>
                    <select id="sales-employee" name="employee" class="mz-select mt-1.5 h-10">
                        <option value="">Todos</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((int) request('employee') === $employee->id)>{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <button type="submit" class="x-button x-button-primary size-sm h-10">Ver ventas</button>
        </form>

        <a href="{{ route('pos.index') }}" class="x-button x-button-secondary size-sm h-10">
            <x-icon name="arrow-uturn-left" class="size-4" />
            Volver al POS
        </a>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="mz-card px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-mz-text-muted">Tickets</p>
            <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-mz-text-primary">{{ $summary['count'] }}</p>
        </div>
        <div class="mz-card px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-mz-text-muted">Ventas cobradas</p>
            <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-mz-text-primary">${{ number_format($summary['total'], 2) }}</p>
        </div>
        <div class="mz-card px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-mz-text-muted">Día</p>
            <p class="mt-1 font-display text-2xl font-semibold text-mz-text-primary">{{ $date->format('d/m/Y') }}</p>
        </div>
    </div>

    <section class="mt-5 mz-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="mz-table">
                <thead>
                    <tr>
                        <th class="font-medium">Hora</th>
                        <th class="font-medium">Folio</th>
                        @if ($canSeeAny)
                            <th class="font-medium">Cajero</th>
                        @endif
                        <th class="font-medium">Artículos</th>
                        <th class="text-right font-medium">Total</th>
                        <th class="text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td class="whitespace-nowrap">{{ $sale->paid_at?->format('H:i') }}</td>
                            <td class="whitespace-nowrap font-mono text-xs font-semibold text-mz-text-primary">{{ $sale->folio }}</td>
                            @if ($canSeeAny)
                                <td class="whitespace-nowrap">{{ $sale->user?->name }}</td>
                            @endif
                            <td>{{ $sale->items->sum('quantity') }} piezas</td>
                            <td class="whitespace-nowrap text-right font-semibold tabular-nums text-mz-text-primary">${{ number_format((float) $sale->total, 2) }}</td>
                            <td class="whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if (auth()->user()->can('pos.tickets.print.any') || $sale->user_id === auth()->id())
                                        <a href="{{ route('pos.sales.ticket', $sale) }}" target="_blank" class="x-button x-button-secondary size-xs h-8">
                                            <x-icon name="printer" class="size-3.5" />
                                            Ticket
                                        </a>
                                    @endif

                                    @if ($canRequestReturns && $sale->items->contains(fn ($item) => $item->availableReturnQuantity() > 0))
                                        <a href="{{ route('pos.returns.create', $sale) }}" class="inline-flex h-8 items-center gap-1.5 rounded-md border border-mz-warning/40 px-2.5 text-xs font-medium text-mz-warning transition hover:bg-mz-warning/10">
                                            <x-icon name="arrow-uturn-left" class="size-3.5" />
                                            Devolución
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canSeeAny ? 6 : 5 }}" class="px-5 py-14">
                                <div class="mz-empty-state border-0 bg-transparent">
                                    <p class="text-sm font-medium text-mz-text-secondary">No hay ventas cobradas en esta fecha</p>
                                    <a href="{{ route('pos.index') }}" class="mt-2 inline-block text-xs font-medium text-mz-accent-primary hover:text-mz-accent-hover">Ir al punto de venta</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $sales->links() }}</div>
@endsection
