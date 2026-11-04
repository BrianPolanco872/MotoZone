@extends('layouts.admin')

@section('title', 'Devoluciones')
@section('subtitle', 'Solicitudes, autorizaciones y reintegros')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-500">
            Las devoluciones descuentan el efectivo de la caja y devuelven el producto al inventario al autorizarse.
        </p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('pos.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                <x-icon name="arrow-uturn-left" class="size-4" />
                Volver al POS
            </a>
            <a href="{{ route('pos.sales.previous') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                <x-icon name="receipt" class="size-4" />
                Buscar venta
            </a>
        </div>
    </div>

    <form action="{{ route('pos.returns.index') }}" method="GET" class="mt-5 flex flex-wrap items-end gap-2">
        <div>
            <label for="returns-status" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Estado</label>
            <select id="returns-status" name="status" class="mt-1.5 h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                <option value="">Todas</option>
                <option value="pending" @selected($status === 'pending')>Pendientes</option>
                <option value="approved" @selected($status === 'approved')>Autorizadas</option>
                <option value="rejected" @selected($status === 'rejected')>Rechazadas</option>
            </select>
        </div>
        <div>
            <label for="returns-search" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Folio de venta</label>
            <input id="returns-search" type="search" name="search" value="{{ $search }}" placeholder="Ej. V-000123" class="mt-1.5 h-10 w-52 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
        </div>
        <button type="submit" class="h-10 rounded-lg bg-ink-900 px-4 text-sm font-medium text-white transition hover:bg-ink-800">Filtrar</button>
        @if ($status !== '' || $search !== '')
            <a href="{{ route('pos.returns.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Limpiar</a>
        @endif
    </form>

    <section class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-5 py-3 font-medium">Solicitud</th>
                        <th class="px-5 py-3 font-medium">Venta</th>
                        <th class="px-5 py-3 font-medium">Productos</th>
                        <th class="px-5 py-3 font-medium">Solicitó</th>
                        <th class="px-5 py-3 text-right font-medium">Reintegro</th>
                        <th class="px-5 py-3 font-medium">Estado</th>
                        <th class="px-5 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($returns as $return)
                        <tr class="transition hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">
                                #{{ $return->id }}
                                <span class="block text-xs text-slate-400">{{ $return->created_at->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 font-mono text-xs font-semibold text-slate-900">{{ $return->sale?->folio }}</td>
                            <td class="px-5 py-3 text-slate-600">
                                {{ $return->items->sum('quantity') }} pieza(s)
                                <span class="block max-w-xs truncate text-xs text-slate-400" title="{{ $return->reason }}">{{ $return->reason }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $return->requester?->name }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-semibold tabular-nums text-slate-900">${{ number_format((float) $return->total, 2) }}</td>
                            <td class="whitespace-nowrap px-5 py-3">
                                @php
                                    $badge = match ($return->status) {
                                        \App\Models\SaleReturn::STATUS_PENDING => ['Pendiente', 'bg-sky-50 text-sky-700'],
                                        \App\Models\SaleReturn::STATUS_APPROVED => ['Autorizada', 'bg-emerald-50 text-emerald-700'],
                                        default => ['Rechazada', 'bg-rose-50 text-rose-700'],
                                    };
                                @endphp
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $badge[1] }}">{{ $badge[0] }}</span>
                                @if ($return->approver)
                                    <span class="mt-0.5 block text-[11px] text-slate-400">{{ $return->approver->name }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                @if ($return->isPending() && $canApprove)
                                    <div class="flex items-center justify-end gap-2">
                                        <form action="{{ route('pos.returns.approve', $return) }}" method="POST" onsubmit="return confirm('¿Autorizar esta devolución? Se devolverá el efectivo y el inventario.')">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-emerald-600 px-2.5 text-xs font-semibold text-white transition hover:bg-emerald-700">
                                                <x-icon name="check" class="size-3.5" />
                                                Autorizar
                                            </button>
                                        </form>
                                        <form action="{{ route('pos.returns.reject', $return) }}" method="POST" onsubmit="return confirm('¿Rechazar esta devolución?')">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-rose-200 px-2.5 text-xs font-medium text-rose-600 transition hover:bg-rose-50">
                                                <x-icon name="x" class="size-3.5" />
                                                Rechazar
                                            </button>
                                        </form>
                                    </div>
                                @elseif ($return->isPending())
                                    <span class="text-xs text-slate-400">Esperando autorización</span>
                                @else
                                    <span class="text-xs text-slate-400">{{ $return->approved_at?->format('d/m/Y') ?? $return->rejected_at?->format('d/m/Y') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center">
                                <p class="text-sm font-medium text-slate-700">No hay devoluciones registradas</p>
                                <p class="mt-1 text-xs text-slate-500">Entra a «Tickets anteriores» y elige una venta para solicitar una devolución.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $returns->links() }}</div>
@endsection
