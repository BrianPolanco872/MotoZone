@extends('layouts.admin')

@section('title', 'Ventas pendientes')
@section('subtitle', 'Tickets guardados sin cobrar')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-mz-text-secondary">Las ventas pendientes no descuentan inventario ni mueven caja hasta que se cobran.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('pos.index') }}" class="x-button x-button-secondary size-sm">
                <x-icon name="arrow-uturn-left" class="size-4" />
                Volver al POS
            </a>
        </div>
    </div>

    <section class="mt-4 mz-card mz-card-padded overflow-hidden">
        <div class="border-b border-mz-border px-4 py-3">
            <h2 class="text-sm font-semibold text-mz-text-primary">Pendientes de cobro</h2>
            <p class="text-xs text-mz-text-muted">{{ $sales->total() }} {{ $sales->total() === 1 ? 'venta' : 'ventas' }} en espera.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-mz-border-light text-sm">
                <thead class="bg-mz-bg-surface">
                    <tr class="text-left text-xs uppercase tracking-wide text-mz-text-muted">
                        <th class="px-4 py-3 font-medium">Folio</th>
                        <th class="px-4 py-3 font-medium">Guardada</th>
                        @if ($canSeeAny)
                            <th class="px-4 py-3 font-medium">Cajero</th>
                        @endif
                        <th class="px-4 py-3 font-medium">Artículos</th>
                        <th class="px-4 py-3 text-right font-medium">Total</th>
                        <th class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mz-border-light">
                    @forelse ($sales as $sale)
                        <tr class="transition hover:bg-mz-bg-surface-2">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-mz-text-primary">{{ $sale->folio }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-mz-text-secondary">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                            @if ($canSeeAny)
                                <td class="whitespace-nowrap px-4 py-3 text-mz-text-secondary">{{ $sale->user?->name }}</td>
                            @endif
                            <td class="px-4 py-3 text-mz-text-secondary">
                                {{ $sale->items->sum('quantity') }} piezas
                                <span class="block text-xs text-mz-text-muted">{{ $sale->items->count() }} productos</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-mz-text-primary">${{ number_format((float) $sale->total, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('pos.index', ['pending' => $sale->id]) }}" class="x-button x-button-secondary size-xs">
                                        <x-icon name="banknotes" class="size-3.5" />
                                        Cobrar
                                    </a>
                                    <form action="{{ route('pos.sales.cancel', $sale) }}" method="POST" onsubmit="return confirm('¿Cancelar esta venta pendiente?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="x-button x-button-ghost size-xs text-mz-error">
                                            <x-icon name="trash" class="size-3.5" />
                                            Cancelar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canSeeAny ? 6 : 5 }}" class="px-4 py-14 text-center">
                                <p class="text-sm font-medium text-mz-text-secondary">No hay ventas pendientes</p>
                                <a href="{{ route('pos.index') }}" class="mt-2 inline-block text-xs font-medium text-mz-accent-primary hover:text-mz-accent-hover">Ir al punto de venta</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-4">{{ $sales->links() }}</div>
@endsection
