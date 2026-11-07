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
    <div class="mz-card p-4 sm:p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-mz-text-muted">Ventas totales</p>
        <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-mz-text-primary">{{ $money($grandTotals['sales'] ?? 0) }}</p>
        <p class="mt-1 text-xs text-mz-text-muted">{{ $salesSection['count'] ?? 0 }} ticket(s) cobrados</p>
    </div>
    <div class="mz-card p-4 sm:p-5 border-mz-success/20 bg-mz-success/5">
        <p class="text-xs font-semibold uppercase tracking-wide text-mz-success">Ventas en efectivo</p>
        <p class="mt-1 font-display text-2xl font-bold tabular-nums text-mz-success">{{ $money($salesSection['total_cash'] ?? 0) }}</p>
        <p class="mt-1 text-xs text-mz-text-muted">Solo efectivo entra a caja</p>
    </div>
    <div class="mz-card p-4 sm:p-5 border-mz-success/20 bg-mz-success/5">
        <p class="text-xs font-semibold uppercase tracking-wide text-mz-success">Ganancia neta</p>
        <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-mz-success">{{ $money($grandTotals['profit'] ?? 0) }}</p>
        <p class="mt-1 text-xs text-mz-text-muted">Después de devoluciones</p>
    </div>
    <div class="mz-card p-4 sm:p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-mz-text-muted">Devoluciones</p>
        <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-mz-text-primary">{{ $money($grandTotals['refunds'] ?? 0) }}</p>
        <p class="mt-1 text-xs text-mz-text-muted">{{ $returnsSection['count'] ?? 0 }} autorizada(s)</p>
    </div>
</div>

<div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
    {{-- Panel 1: Dinero registrado en computadora --}}
    <section class="mz-card p-4 sm:p-5 overflow-hidden">
        <h2 class="text-base font-semibold text-mz-text-primary mb-3">Panel 1 — Dinero registrado en computadora</h2>
        <dl class="mt-3 space-y-2 text-sm">
            <div class="flex items-center justify-between border-b border-mz-border-light pb-2">
                <dt class="text-mz-text-muted">Dinero inicial</dt>
                <dd class="tabular-nums text-mz-success">+{{ $money($computerPanel['initial'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-mz-border-light pb-2">
                <dt class="text-mz-text-muted">Entradas de efectivo</dt>
                <dd class="tabular-nums text-mz-success">+{{ $money($computerPanel['cash_in_total'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-mz-border-light pb-2">
                <dt class="text-mz-text-muted">Ventas en efectivo cobradas</dt>
                <dd class="tabular-nums text-mz-accent-primary">+{{ $money($computerPanel['cash_sales'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-mz-border-light pb-2">
                <dt class="text-mz-text-muted">Pagos de clientes en efectivo</dt>
                <dd class="tabular-nums text-mz-success">+{{ $money($computerPanel['customer_payments'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-mz-border-light pb-2">
                <dt class="text-mz-text-muted">− Devoluciones pagadas en efectivo</dt>
                <dd class="tabular-nums text-mz-error">−{{ $money($computerPanel['refunds'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-mz-border-light pb-2">
                <dt class="text-mz-text-muted">− Salidas de efectivo</dt>
                <dd class="tabular-nums text-mz-warning">−{{ $money($computerPanel['cash_out'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-t border-mz-border pt-2">
                <dt class="font-semibold text-mz-text-primary">Subtotal: debe haber en computadora</dt>
                <dd class="font-display text-lg font-bold tabular-nums text-mz-text-primary">{{ $money($computerPanel['subtotal'] ?? 0) }}</dd>
            </div>
        </dl>
    </section>

    {{-- Panel 2: Caja --}}
    <section class="mz-card p-4 sm:p-5 overflow-hidden">
        <h2 class="text-base font-semibold text-mz-text-primary mb-3">Panel 2 — Caja (desde movimientos de caja)</h2>
        <dl class="mt-3 space-y-2 text-sm">
            <div class="flex items-center justify-between border-b border-mz-border-light pb-2">
                <dt class="text-mz-text-muted">Dinero inicial</dt>
                <dd class="tabular-nums text-mz-success">+{{ $money($cashBoxPanel['initial'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-mz-border-light pb-2">
                <dt class="text-mz-text-muted">Entradas de efectivo</dt>
                <dd class="tabular-nums text-mz-success">+{{ $money($cashBoxPanel['cash_in_total'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-mz-border-light pb-2">
                <dt class="text-mz-text-muted">Ventas en efectivo (mov. caja)</dt>
                <dd class="tabular-nums text-mz-accent-primary">+{{ $money($cashBoxPanel['sales_movements'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-mz-border-light pb-2">
                <dt class="text-mz-text-muted">Pagos de clientes</dt>
                <dd class="tabular-nums text-mz-success">+{{ $money($cashBoxPanel['customer_payments'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-mz-border-light pb-2">
                <dt class="text-mz-text-muted">− Devoluciones (mov. caja)</dt>
                <dd class="tabular-nums text-mz-error">−{{ $money($cashBoxPanel['refunds'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-b border-mz-border-light pb-2">
                <dt class="text-mz-text-muted">− Salidas de efectivo</dt>
                <dd class="tabular-nums text-mz-warning">−{{ $money($cashBoxPanel['cash_out'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-t border-mz-border pt-2">
                <dt class="font-semibold text-mz-text-primary">Subtotal: debe haber en caja</dt>
                <dd class="font-display text-lg font-bold tabular-nums text-mz-text-primary">{{ $money($cashBoxPanel['subtotal'] ?? 0) }}</dd>
            </div>
        </dl>
    </section>
</div>

<!-- Ventas y netas -->
<div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <section class="mz-card p-4 sm:p-5 overflow-hidden">
        <h2 class="text-base font-semibold text-mz-text-primary mb-3">Ventas</h2>
        <dl class="mt-3 space-y-2 text-sm">
            <div class="flex items-center justify-between">
                <dt class="text-mz-text-muted">Subtotal</dt>
                <dd class="tabular-nums text-mz-text-secondary">{{ $money($salesSection['subtotal'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-mz-text-muted">IVA incluido</dt>
                <dd class="tabular-nums text-mz-text-secondary">{{ $money($salesSection['tax'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-mz-text-muted">Total cobrado (efectivo)</dt>
                <dd class="tabular-nums text-mz-accent-primary">{{ $money($salesSection['total_cash'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-mz-text-muted">Otros medios de pago</dt>
                <dd class="tabular-nums text-mz-text-muted">{{ $money($salesSection['total_non_cash'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-t border-mz-border-light pt-2">
                <dt class="font-medium text-mz-text-secondary">Ventas totales</dt>
                <dd class="font-semibold tabular-nums text-mz-text-primary">{{ $money($grandTotals['sales'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-mz-text-muted">Menos devoluciones</dt>
                <dd class="tabular-nums text-mz-error">−{{ $money($grandTotals['refunds'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between border-t border-mz-border-light pt-2">
                <dt class="font-semibold text-mz-text-primary">Ventas netas</dt>
                <dd class="font-display text-lg font-bold tabular-nums text-mz-text-primary">{{ $money($grandTotals['net_sales'] ?? 0) }}</dd>
            </div>
        </dl>
    </section>
</div>
