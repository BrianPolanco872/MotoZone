@extends('layouts.admin')

@section('title', 'Ventas pendientes')
@section('subtitle', 'Tickets guardados sin cobrar')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">Las ventas pendientes no descuentan inventario ni mueven caja hasta que se cobran.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('pos.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                <x-icon name="arrow-uturn-left" class="size-4" />
                Volver al POS
            </a>
        </div>
    </div>

    <section class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Pendientes de cobro</h2>
            <p class="text-xs text-slate-500">{{ $sales->total() }} {{ $sales->total() === 1 ? 'venta' : 'ventas' }} en espera.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-5 py-3 font-medium">Folio</th>
                        <th class="px-5 py-3 font-medium">Guardada</th>
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
                            <td class="whitespace-nowrap px-5 py-3 font-mono text-xs font-semibold text-slate-900">{{ $sale->folio }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                            @if ($canSeeAny)
                                <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $sale->user?->name }}</td>
                            @endif
                            <td class="px-5 py-3 text-slate-600">
                                {{ $sale->items->sum('quantity') }} piezas
                                <span class="block text-xs text-slate-400">{{ $sale->items->count() }} productos</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-semibold tabular-nums text-slate-900">${{ number_format((float) $sale->total, 2) }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('pos.index', ['pending' => $sale->id]) }}" class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-brand-700 px-2.5 text-xs font-semibold text-white transition hover:bg-brand-800">
                                        <x-icon name="banknotes" class="size-3.5" />
                                        Cobrar
                                    </a>
                                    <form action="{{ route('pos.sales.cancel', $sale) }}" method="POST" onsubmit="return confirm('¿Cancelar esta venta pendiente?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-rose-200 px-2.5 text-xs font-medium text-rose-600 transition hover:bg-rose-50">
                                            <x-icon name="trash" class="size-3.5" />
                                            Cancelar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canSeeAny ? 6 : 5 }}" class="px-5 py-14 text-center">
                                <p class="text-sm font-medium text-slate-700">No hay ventas pendientes</p>
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
