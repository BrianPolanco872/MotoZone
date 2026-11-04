@extends('layouts.admin')

@section('title', 'Ventas del día')
@section('subtitle', 'Tickets cobrados y totales por fecha')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <form action="{{ route('pos.sales.day') }}" method="GET" class="flex flex-wrap items-end gap-2">
            <div>
                <label for="sales-date" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Fecha</label>
                <input id="sales-date" type="date" name="date" value="{{ $date->toDateString() }}" class="mt-1.5 h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
            </div>

            @if ($canSeeAny)
                <div>
                    <label for="sales-employee" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Cajero</label>
                    <select id="sales-employee" name="employee" class="mt-1.5 h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                        <option value="">Todos</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((int) request('employee') === $employee->id)>{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <button type="submit" class="h-10 rounded-lg bg-ink-900 px-4 text-sm font-medium text-white transition hover:bg-ink-800">Ver ventas</button>
        </form>

        <a href="{{ route('pos.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
            <x-icon name="arrow-uturn-left" class="size-4" />
            Volver al POS
        </a>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tickets</p>
            <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-slate-900">{{ $summary['count'] }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ventas cobradas</p>
            <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-slate-900">${{ number_format($summary['total'], 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Día</p>
            <p class="mt-1 font-display text-2xl font-semibold text-slate-900">{{ $date->format('d/m/Y') }}</p>
        </div>
    </div>

    <section class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-5 py-3 font-medium">Hora</th>
                        <th class="px-5 py-3 font-medium">Folio</th>
                        @if ($canSeeAny)
                            <th class="px-5 py-3 font-medium">Cajero</th>
                        @endif
                        <th class="px-5 py-3 font-medium">Artículos</th>
                        <th class="px-5 py-3 text-right font-medium">Total</th>
                        <th class="px-5 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($sales as $sale)
                        <tr class="transition hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $sale->paid_at?->format('H:i') }}</td>
                            <td class="whitespace-nowrap px-5 py-3 font-mono text-xs font-semibold text-slate-900">{{ $sale->folio }}</td>
                            @if ($canSeeAny)
                                <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $sale->user?->name }}</td>
                            @endif
                            <td class="px-5 py-3 text-slate-600">{{ $sale->items->sum('quantity') }} piezas</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-semibold tabular-nums text-slate-900">${{ number_format((float) $sale->total, 2) }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if (auth()->user()->can('pos.tickets.print.any') || $sale->user_id === auth()->id())
                                        <a href="{{ route('pos.sales.ticket', $sale) }}" target="_blank" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                                            <x-icon name="printer" class="size-3.5" />
                                            Ticket
                                        </a>
                                    @endif

                                    @if ($canRequestReturns && $sale->items->contains(fn ($item) => $item->availableReturnQuantity() > 0))
                                        <a href="{{ route('pos.returns.create', $sale) }}" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-amber-200 px-2.5 text-xs font-medium text-amber-700 transition hover:bg-amber-50">
                                            <x-icon name="arrow-uturn-left" class="size-3.5" />
                                            Devolución
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canSeeAny ? 6 : 5 }}" class="px-5 py-14 text-center">
                                <p class="text-sm font-medium text-slate-700">No hay ventas cobradas en esta fecha</p>
                                <a href="{{ route('pos.index') }}" class="mt-2 inline-block text-xs font-medium text-brand-700 hover:text-brand-800">Ir al punto de venta</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $sales->links() }}</div>
@endsection
