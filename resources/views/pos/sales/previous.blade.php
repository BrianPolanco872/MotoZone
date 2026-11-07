@extends('layouts.admin')

@section('title', 'Tickets anteriores')
@section('subtitle', 'Consulta y reimpresión de ventas cobradas')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <form action="{{ route('pos.sales.previous') }}" method="GET" class="flex flex-wrap items-end gap-2">
            <div>
                <label for="sales-search" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Buscar folio</label>
                <input id="sales-search" type="search" name="search" value="{{ $search }}" placeholder="Ej. V-000123" class="mz-input mt-1.5 h-10 w-56">
            </div>
            <button type="submit" class="x-button x-button-primary size-sm h-10">Buscar</button>
            @if ($search !== '')
                <a href="{{ route('pos.sales.previous') }}" class="x-button x-button-secondary size-sm h-10">Limpiar</a>
            @endif
        </form>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('pos.sales.day') }}" class="x-button x-button-secondary size-sm h-10">
                <x-icon name="chart" class="size-4" />
                Ventas del día
            </a>
            <a href="{{ route('pos.index') }}" class="x-button x-button-secondary size-sm h-10">
                <x-icon name="arrow-uturn-left" class="size-4" />
                Volver al POS
            </a>
        </div>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="mz-card px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-mz-text-muted">Tickets encontrados</p>
            <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-mz-text-primary">{{ $summary['count'] }}</p>
        </div>
        <div class="mz-card px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-mz-text-muted">Total cobrado</p>
            <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-mz-text-primary">${{ number_format($summary['total'], 2) }}</p>
        </div>
        <div class="mz-card px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-mz-text-muted">Alcance</p>
            <p class="mt-1 text-sm font-semibold text-mz-text-primary">{{ $canSeeAny ? 'Todos los empleados' : 'Solo tus ventas' }}</p>
        </div>
    </div>

    <section class="mt-5 mz-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="mz-table">
                <thead>
                    <tr>
                        <th class="font-medium">Folio</th>
                        <th class="font-medium">Fecha</th>
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
                            <td class="whitespace-nowrap font-mono text-xs font-semibold text-mz-text-primary">{{ $sale->folio }}</td>
                            <td class="whitespace-nowrap">{{ $sale->paid_at?->format('d/m/Y H:i') }}</td>
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
                                    <p class="text-sm font-medium text-mz-text-secondary">No se encontraron tickets</p>
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
