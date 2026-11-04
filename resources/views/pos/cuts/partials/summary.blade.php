@php
    /** @var array<string, mixed> $totals */
    $salesSection = $totals['sales'] ?? [];
    $computerPanel = $totals['computer_panel'] ?? [];
    $cashBoxPanel = $totals['cash_box_panel'] ?? [];
    $reconciliation = $totals['reconciliation'] ?? [];
    $grandTotals = $totals['totals'] ?? [];
    $returnsSection = $totals['returns'] ?? [];
    $departments = $totals['departments'] ?? [];
    $money = fn ($value) => '$'.number_format((float) $value, 2);
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ventas totales</p>
        <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-slate-900">{{ $money($grandTotals['sales'] ?? 0) }}</p>
        <p class="mt-1 text-xs text-slate-500">{{ $salesSection['count'] ?? 0 }} ticket(s) cobrados</p>
    </div>
    <div class="rounded-xl border border-brand-200 bg-brand-50 px-5 py-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Ventas en efectivo</p>
        <p class="mt-1 font-display text-2xl font-bold tabular-nums text-brand-800">{{ $money($salesSection['total_cash'] ?? 0) }}</p>
        <p class="mt-1 text-xs text-brand-700">Solo efectivo entra a caja</p>
    </div>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Ganancia neta</p>
        <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-emerald-800">{{ $money($grandTotals['profit'] ?? 0) }}</p>
        <p class="mt-1 text-xs text-emerald-700">Después de devoluciones</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Devoluciones</p>
        <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-slate-900">{{ $money($grandTotals['refunds'] ?? 0) }}</p>
        <p class="mt-1 text-xs text-slate-500">{{ $returnsSection['count'] ?? 0 }} autorizada(s)</p>
    </div>
</div>

<div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
    <!-- Panel 1: Dinero registrado en computadora -->
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-slate-900">Panel 1 — Dinero registrado en computadora</h2>
        <dl class="mt-3 space-y-2 text-sm">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <dt class="text-slate-500">Dinero inicial</dt>
                <dd class="tabular-nums text-emerald-700">+{{ $money($computerPanel['initial'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <dt class="text-slate-500">Entradas de efectivo</dt>
                <dd class="tabular-nums text-emerald-700">+{{ $money($computerPanel['cash_in_total'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <dt class="text-slate-500">Ventas en efectivo cobradas</dt>
                <dd class="tabular-nums text-brand-700">+{{ $money($computerPanel['cash_sales'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <dt class="text-slate-500">Pagos de clientes en efectivo</dt>
                <dd class="tabular-nums text-emerald-700">+{{ $money($computerPanel['customer_payments'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <dt class="text-slate-500">− Devoluciones pagadas en efectivo</dt>
                <dd class="tabular-nums text-rose-700">−{{ $money($computerPanel['refunds'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <dt class="text-slate-500">− Salidas de efectivo</dt>
                <dd class="tabular-nums text-amber-700">−{{ $money($computerPanel['cash_out'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-t border-slate-100 pt-2">
                <dt class="font-semibold text-slate-900">Subtotal: debe haber en computadora</dt>
                <dd class="font-display text-lg font-bold tabular-nums text-slate-900">{{ $money($computerPanel['subtotal'] ?? 0) }}</dd>
            </div>
        </dl>
    </section>

    <!-- Panel 2: Caja -->
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-slate-900">Panel 2 — Caja (desde movimientos de caja)</h2>
        <dl class="mt-3 space-y-2 text-sm">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <dt class="text-slate-500">Dinero inicial</dt>
                <dd class="tabular-nums text-emerald-700">+{{ $money($cashBoxPanel['initial'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <dt class="text-slate-500">Entradas de efectivo</dt>
                <dd class="tabular-nums text-emerald-700">+{{ $money($cashBoxPanel['cash_in_total'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <dt class="text-slate-500">Ventas en efectivo (mov. caja)</dt>
                <dd class="tabular-nums text-brand-700">+{{ $money($cashBoxPanel['sales_movements'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <dt class="text-slate-500">Pagos de clientes</dt>
                <dd class="tabular-nums text-emerald-700">+{{ $money($cashBoxPanel['customer_payments'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <dt class="text-slate-500">− Devoluciones (mov. caja)</dt>
                <dd class="tabular-nums text-rose-700">−{{ $money($cashBoxPanel['refunds'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <dt class="text-slate-500">− Salidas de efectivo</dt>
                <dd class="tabular-nums text-amber-700">−{{ $money($cashBoxPanel['cash_out'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-t border-slate-100 pt-2">
                <dt class="font-semibold text-slate-900">Subtotal: debe haber en caja</dt>
                <dd class="font-display text-lg font-bold tabular-nums text-slate-900">{{ $money($cashBoxPanel['subtotal'] ?? 0) }}</dd>
            </div>
        </dl>
    </section>
</div>

<!-- Ventas y netas -->
<div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-slate-900">Ventas</h2>
        <dl class="mt-3 space-y-2 text-sm">
            <div class="flex items-center justify-between">
                <dt class="text-slate-500">Subtotal</dt>
                <dd class="tabular-nums text-slate-800">{{ $money($salesSection['subtotal'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-slate-500">IVA incluido</dt>
                <dd class="tabular-nums text-slate-800">{{ $money($salesSection['tax'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-slate-500">Total cobrado (efectivo)</dt>
                <dd class="tabular-nums text-brand-700">{{ $money($salesSection['total_cash'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-slate-500">Otros medios de pago</dt>
                <dd class="tabular-nums text-slate-500">{{ $money($salesSection['total_non_cash'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-t border-slate-100 pt-2">
                <dt class="font-medium text-slate-700">Ventas totales</dt>
                <dd class="font-semibold tabular-nums text-slate-900">{{ $money($grandTotals['sales'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-slate-500">Menos devoluciones</dt>
                <dd class="tabular-nums text-rose-600">−{{ $money($grandTotals['refunds'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-t border-slate-100 pt-2">
                <dt class="font-semibold text-slate-900">Ventas netas</dt>
                <dd class="font-display text-lg font-bold tabular-nums text-slate-900">{{ $money($grandTotals['net_sales'] ?? 0) }}</dd>
            </div>
        </dl>
    </section>
</div>
