@extends('layouts.admin')

@section('title', 'Devoluciones')
@section('subtitle', 'Solicitudes, autorizaciones y reintegros')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-mz-text-muted">
            Las devoluciones descuentan el efectivo de la caja y devuelven el producto al inventario al autorizarse.
        </p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('pos.index') }}" class="x-button x-button-secondary size-sm h-10">
                <x-icon name="arrow-uturn-left" class="size-4" />
                Volver al POS
            </a>
            <a href="{{ route('pos.sales.previous') }}" class="x-button x-button-secondary size-sm h-10">
                <x-icon name="receipt" class="size-4" />
                Buscar venta
            </a>
        </div>
    </div>

    <form action="{{ route('pos.returns.index') }}" method="GET" class="mt-5 flex flex-wrap items-end gap-2">
        <div>
            <label for="returns-status" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Estado</label>
            <select id="returns-status" name="status" class="mz-select mt-1.5 h-10">
                <option value="">Todas</option>
                <option value="pending" @selected($status === 'pending')>Pendientes</option>
                <option value="approved" @selected($status === 'approved')>Autorizadas</option>
                <option value="rejected" @selected($status === 'rejected')>Rechazadas</option>
            </select>
        </div>
        <div>
            <label for="returns-search" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Folio de venta</label>
            <input id="returns-search" type="search" name="search" value="{{ $search }}" placeholder="Ej. V-000123" class="mz-input mt-1.5 h-10 w-52">
        </div>
        <button type="submit" class="x-button x-button-primary size-sm h-10">Filtrar</button>
        @if ($status !== '' || $search !== '')
            <a href="{{ route('pos.returns.index') }}" class="x-button x-button-secondary size-sm h-10">Limpiar</a>
        @endif
    </form>

    <section class="mt-5 mz-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="mz-table">
                <thead>
                    <tr>
                        <th class="font-medium">Solicitud</th>
                        <th class="font-medium">Venta</th>
                        <th class="font-medium">Productos</th>
                        <th class="font-medium">Solicitó</th>
                        <th class="text-right font-medium">Reintegro</th>
                        <th class="font-medium">Estado</th>
                        <th class="text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($returns as $return)
                        <tr>
                            <td class="whitespace-nowrap">
                                #{{ $return->id }}
                                <span class="block text-xs text-mz-text-muted">{{ $return->created_at->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="whitespace-nowrap font-mono text-xs font-semibold text-mz-text-primary">{{ $return->sale?->folio }}</td>
                            <td>
                                {{ $return->items->sum('quantity') }} pieza(s)
                                <span class="block max-w-xs truncate text-xs text-mz-text-muted" title="{{ $return->reason }}">{{ $return->reason }}</span>
                            </td>
                            <td class="whitespace-nowrap">{{ $return->requester?->name }}</td>
                            <td class="whitespace-nowrap text-right font-semibold tabular-nums text-mz-text-primary">${{ number_format((float) $return->total, 2) }}</td>
                            <td class="whitespace-nowrap">
                                @php
                                    $badge = match ($return->status) {
                                        \App\Models\SaleReturn::STATUS_PENDING => ['Pendiente', 'mz-badge-pending'],
                                        \App\Models\SaleReturn::STATUS_APPROVED => ['Autorizada', 'mz-badge-approved'],
                                        default => ['Rechazada', 'mz-badge-rejected'],
                                    };
                                @endphp
                                <span class="mz-badge {{ $badge[1] }}">{{ $badge[0] }}</span>
                                @if ($return->approver)
                                    <span class="mt-0.5 block text-[11px] text-mz-text-muted">{{ $return->approver->name }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right">
                                @if ($return->isPending() && $canApprove)
                                    <div class="flex items-center justify-end gap-2">
                                        <form action="{{ route('pos.returns.approve', $return) }}" method="POST" onsubmit="return confirm('¿Autorizar esta devolución? Se devolverá el efectivo y el inventario.')">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="x-button x-button-success size-xs h-8">
                                                <x-icon name="check" class="size-3.5" />
                                                Autorizar
                                            </button>
                                        </form>
                                        <form action="{{ route('pos.returns.reject', $return) }}" method="POST" onsubmit="return confirm('¿Rechazar esta devolución?')">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="inline-flex h-8 items-center gap-1.5 rounded-md border border-mz-error/40 px-2.5 text-xs font-medium text-mz-error transition hover:bg-mz-error/10">
                                                <x-icon name="x" class="size-3.5" />
                                                Rechazar
                                            </button>
                                        </form>
                                    </div>
                                @elseif ($return->isPending())
                                    <span class="text-xs text-mz-text-muted">Esperando autorización</span>
                                @else
                                    <span class="text-xs text-mz-text-muted">{{ $return->approved_at?->format('d/m/Y') ?? $return->rejected_at?->format('d/m/Y') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14">
                                <div class="mz-empty-state border-0 bg-transparent">
                                    <p class="text-sm font-medium text-mz-text-secondary">No hay devoluciones registradas</p>
                                    <p class="mt-1 text-xs text-mz-text-muted">Usa «Buscar venta» para localizar la venta y solicitar una devolución.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $returns->links() }}</div>
@endsection
