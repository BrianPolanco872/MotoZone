@extends('layouts.admin')

@section('title', 'Devoluciones')
@section('subtitle', 'Solicitudes, autorizaciones y reintegros')

@section('content')
    {{-- Barra superior --}}
    <div class="rounded-2xl border border-mz-border bg-gradient-to-r from-mz-bg-surface via-mz-bg-surface/95 to-mz-bg-surface p-4 sm:p-5 shadow-xl shadow-black/25">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-mz-accent-primary/15 text-mz-accent-primary border border-mz-accent-primary/25">
                    <x-icon name="arrow-uturn-left" class="size-5" />
                </div>
                <div>
                    <h2 class="text-sm font-bold text-white">Gestión de devoluciones</h2>
                    <p class="text-xs text-mz-text-muted mt-0.5">Al autorizarse, descuentan efectivo de caja y reponen el inventario que se descontó en la venta.</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('pos.sales.previous') }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/80 hover:bg-mz-bg-surface-2 px-4 text-xs font-bold text-mz-text-secondary hover:text-white transition-all shadow-xs active:scale-95">
                    <x-icon name="receipt" class="size-4 text-mz-accent-primary" />
                    Buscar ticket
                </a>
                <a href="{{ route('pos.index') }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/80 hover:bg-mz-bg-surface-2 px-4 text-xs font-bold text-mz-text-secondary hover:text-white transition-all shadow-xs active:scale-95">
                    <x-icon name="arrow-uturn-left" class="size-4" />
                    Volver al POS
                </a>
            </div>
        </div>

        {{-- Filtros --}}
        <form action="{{ route('pos.returns.index') }}" method="GET" class="mt-4 pt-4 border-t border-mz-border-light/60 flex flex-wrap items-end gap-3">
            <div>
                <label for="returns-status" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Estado</label>
                <select id="returns-status" name="status" class="mz-select mt-1.5 h-11 rounded-xl text-sm font-semibold">
                    <option value="">Todas las solicitudes</option>
                    <option value="pending" @selected($status === 'pending')>⏳ Pendientes</option>
                    <option value="approved" @selected($status === 'approved')>✅ Autorizadas</option>
                    <option value="rejected" @selected($status === 'rejected')>❌ Rechazadas</option>
                </select>
            </div>
            <div>
                <label for="returns-search" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Folio de venta</label>
                <input id="returns-search" type="search" name="search" value="{{ $search }}" placeholder="Ej. V-000123" class="mz-input mt-1.5 h-11 w-56 rounded-xl text-sm font-semibold">
            </div>
            <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-mz-accent-primary hover:bg-mz-accent-hover text-white px-5 text-xs font-bold transition-all shadow-md shadow-mz-accent-primary/20 active:scale-95">
                <x-icon name="search" class="size-4" />
                Filtrar
            </button>
            @if ($status !== '' || $search !== '')
                <a href="{{ route('pos.returns.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-mz-border-light bg-mz-bg-surface-2 px-4 text-xs font-bold text-mz-text-secondary hover:text-white transition-all active:scale-95">
                    Limpiar
                </a>
            @endif
        </form>
    </div>

    {{-- Tabla de devoluciones --}}
    <section class="mt-5 rounded-2xl border border-mz-border bg-mz-bg-surface shadow-xl shadow-black/20 overflow-hidden">
        <div class="overflow-x-auto pos-scrollbar">
            <table class="min-w-full divide-y divide-mz-border text-sm">
                <thead class="bg-mz-bg-surface-2/40">
                    <tr class="text-left text-xs uppercase tracking-wider text-mz-text-muted">
                        <th class="px-5 py-3.5 font-semibold">Solicitud</th>
                        <th class="px-5 py-3.5 font-semibold">Folio venta</th>
                        <th class="px-5 py-3.5 font-semibold">Productos & Motivo</th>
                        <th class="px-5 py-3.5 font-semibold">Solicitó</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Monto reintegrado</th>
                        <th class="px-5 py-3.5 font-semibold">Estado</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mz-border-light/40">
                    @forelse ($returns as $return)
                        <tr class="group transition-colors duration-150 hover:bg-mz-bg-surface-2/40">
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs text-mz-text-muted">
                                <span class="font-bold text-white">#{{ $return->id }}</span>
                                <span class="block text-[11px] text-mz-text-muted">{{ $return->created_at->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs font-bold text-white">
                                <span class="rounded-md bg-mz-bg-surface-2 px-2 py-1 border border-mz-border-light">{{ $return->sale?->folio }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-sm text-mz-text-secondary">
                                <span class="font-semibold">{{ \App\Support\Quantity::format($return->items->sum('quantity')) }} · cantidad devuelta (medidas mixtas)</span>
                                <span class="block max-w-xs truncate text-xs text-mz-text-muted mt-0.5" title="{{ $return->reason }}">{{ $return->reason }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-mz-text-secondary font-medium">{{ $return->requester?->name }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right font-display text-base font-extrabold tabular-nums text-white">${{ number_format((float) $return->total, 2) }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                @php
                                    $badge = match ($return->status) {
                                        \App\Models\SaleReturn::STATUS_PENDING => ['Pendiente', 'bg-mz-warning/15 text-mz-warning border border-mz-warning/30'],
                                        \App\Models\SaleReturn::STATUS_APPROVED => ['Autorizada', 'bg-mz-success/15 text-mz-success border border-mz-success/30'],
                                        default => ['Rechazada', 'bg-mz-error/15 text-mz-error border border-mz-error/30'],
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider {{ $badge[1] }}">
                                    {{ $badge[0] }}
                                </span>
                                @if ($return->approver)
                                    <span class="mt-1 block text-[11px] text-mz-text-muted">Por {{ $return->approver->name }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                @if ($return->isPending() && $canApprove)
                                    <div class="flex items-center justify-end gap-2">
                                        <form action="{{ route('pos.returns.approve', $return) }}" method="POST" onsubmit="return confirm('¿Autorizar esta devolución? Se devolverá el efectivo y el inventario.')">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-mz-success hover:bg-mz-success/90 text-white px-3 py-1.5 text-xs font-bold transition-all shadow-sm active:scale-95">
                                                <x-icon name="check" class="size-3.5" />
                                                Autorizar
                                            </button>
                                        </form>
                                        <form action="{{ route('pos.returns.reject', $return) }}" method="POST" onsubmit="return confirm('¿Rechazar esta devolución?')">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-mz-error/40 bg-mz-error/10 hover:bg-mz-error/20 px-2.5 py-1.5 text-xs font-bold text-mz-error transition-all active:scale-95">
                                                <x-icon name="x" class="size-3.5" />
                                                Rechazar
                                            </button>
                                        </form>
                                    </div>
                                @elseif ($return->isPending())
                                    <span class="text-xs font-medium text-mz-warning">Esperando autorización</span>
                                @else
                                    <span class="text-xs text-mz-text-muted font-mono">{{ $return->approved_at?->format('d/m/Y') ?? $return->rejected_at?->format('d/m/Y') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-mz-bg-surface-2 text-mz-text-muted">
                                    <x-icon name="arrow-uturn-left" class="size-7" />
                                </div>
                                <p class="mt-3 text-sm font-bold text-mz-text-primary">No hay devoluciones registradas</p>
                                <p class="mt-1 text-xs text-mz-text-muted">Usa «Buscar ticket» para localizar una venta y tramitar una devolución.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $returns->links() }}</div>
@endsection
