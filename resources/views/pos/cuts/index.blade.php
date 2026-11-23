@extends('layouts.admin')

@php
    $onlineUsers = $onlineUsers ?? collect();
@endphp

@section('title', $readOnly ? 'Mi corte' : 'Corte del día')
@section('subtitle', $employee ? 'Corte de '.$employee->name : 'Corte global del negocio')

@section('content')
    <?php
        $t = $totals['totals'] ?? [];
        $cp = $totals['computer_panel'] ?? [];
        $cb = $totals['cash_box_panel'] ?? [];
        $co = $totals['cash_out'] ?? [];
        $rec = $totals['reconciliation'] ?? [];
        $departments = $totals['departments'] ?? [];
        $navidadProducts = $totals['navidad_products'] ?? [];
        $navidadTotal = $totals['navidad_total'] ?? 0;
        $hasSnapshot = $snapshot !== null;
        $cashCounted = $hasSnapshot ? ((float) ($snapshot->cash_counted ?? 0)) : 0.0;
        $countedDiff = $hasSnapshot ? ((float) ($snapshot->difference ?? 0)) : 0.0;
        $countedReconciled = $hasSnapshot ? ((bool) ($snapshot->is_reconciled ?? false)) : false;
        $hasAnyMovement = (float) ($t['sales']['total'] ?? 0) > 0 || (float) ($cp['cash_in_total'] ?? 0) > 0 || (float) ($cb['cash_out'] ?? 0) > 0;

        $isToday = $date->isToday();
        $isClosed = $hasSnapshot && $snapshot->isClosed();
        $globalDayClosed = $globalDayClosed ?? \App\Models\DailyCut::isDayClosed($date->toDateString());
        $isOwnCut = $employee !== null && (int) $employee->id === (int) auth()->id();
        $canFinalizeDay = $isToday && $isOwnCut && ! $isClosed && ! $globalDayClosed;
        $canReopenSale = $isToday
            && $isOwnCut
            && $hasSnapshot
            && $snapshot->isClosed()
            && $snapshot->scope === \App\Models\DailyCut::SCOPE_EMPLOYEE
            && (int) $snapshot->user_id === (int) auth()->id()
            && ! $globalDayClosed;
    ?>

    {{-- Barra superior --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between border-b border-mz-border-light pb-4 mb-6">
        <form action="{{ $readOnly ? route('pos.cuts.mine') : route('pos.cuts.index') }}" method="GET" class="flex flex-wrap items-end gap-2">
            <div class="flex items-center gap-2">
                <label for="cut-date" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Fecha</label>
                <input id="cut-date" type="date" name="date" value="{{ $date->toDateString() }}" class="mz-input mt-1.5 h-10">
            </div>

            @if (! $readOnly && $canSeeAny && $employees->isNotEmpty())
                <div class="flex items-center gap-2">
                    <label for="cut-employee" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Empleado</label>
                    <select id="cut-employee" name="employee" class="mz-select mt-1.5 h-10">
                        <option value="">Corte global</option>
                        @foreach ($employees as $option)
                            <option value="{{ $option->id }}" @selected($employee?->id === $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <button type="submit" class="x-button x-button-primary size-sm">
                Ver corte
            </button>
        </form>

        <div class="flex flex-wrap gap-2">
            @if ($hasSnapshot)
                <a href="{{ route('pos.cuts.print', array_filter(['date' => $date->toDateString(), 'employee' => $employee?->id, 'version' => $snapshot->version])) }}" target="_blank" class="x-button x-button-secondary size-sm">
                    <x-icon name="printer" class="size-4" />
                    Imprimir
                </a>
            @else
                <a href="{{ route('pos.cuts.print', array_filter(['date' => $date->toDateString(), 'employee' => $employee?->id])) }}" target="_blank" class="x-button x-button-secondary size-sm">
                    <x-icon name="printer" class="size-4" />
                    Imprimir
                </a>
            @endif

            @if (! $readOnly)
                <a href="{{ route('pos.cuts.history') }}" class="x-button x-button-secondary size-sm">
                    <x-icon name="clock" class="size-4" />
                    Historial
                </a>
            @endif

            @if ($canFinalizeDay)
                <button type="button" class="x-button x-button-primary size-sm" onclick="openFinalizarDiaModal()">
                    <x-icon name="lock" class="size-4" />
                    Finalizar día
                </button>
            @endif

            @if ($canReopenSale)
                <form action="{{ route('pos.cuts.reopen-my-day') }}" method="POST" onsubmit="return confirm('¿Reabrir tu turno para volver a cobrar? El corte de hoy quedará abierto de nuevo.')">
                    @csrf
                    <button type="submit" class="x-button x-button-secondary size-sm">
                        <x-icon name="refresh" class="size-4" />
                        Reabrir venta
                    </button>
                </form>
            @endif

            @if (! $readOnly && $canSeeAny)
                    <form action="{{ route('pos.cuts.store') }}" method="POST" onsubmit="return confirm('¿Hacer el corte? Se guardará una copia que no se puede modificar.')">
                        @csrf
                        <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                        @if (! $readOnly && $canSeeAny && $employees->isNotEmpty())
                            <input type="hidden" name="employee" value="{{ $employee?->id ?? '' }}">
                        @endif
                        <button type="submit" class="x-button x-button-primary size-sm">
                            <x-icon name="check" class="size-4" />
                            Hacer corte
                        </button>
                    </form>
                @endif
        </div>
    </div>

    {{-- Cajeros en línea y cierre de día (solo vista global) --}}
    @if ($canSeeAny && ! $employee)
        <section class="mt-4 mz-card mz-card-padded">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                <div>
                    <h3 class="text-sm font-semibold text-mz-text-primary">Cajeros en línea</h3>
                    <p class="text-[10px] text-mz-text-muted">Cada cajero cierra su propio día desde su perfil.</p>
                </div>
            </div>

            @if ($onlineUsers->isNotEmpty())
                <p class="text-xs text-mz-text-muted mb-2">{{ $onlineUsers->count() }} usuario(s) con sesión activa:</p>
                <ul class="text-xs space-y-1 mb-2">
                    @foreach ($onlineUsers as $u)
                        <li class="flex items-center gap-2">{{ $u['name'] }} <span class="text-[10px] text-mz-text-muted">({{ $u['last_activity']->diffForHumans() }} atrás) @if($u['id'] === auth()->id()) <span class="text-mz-text-muted">· tú</span> @endif)</span></li>
                    @endforeach
                </ul>
            @else
                <p class="text-xs text-mz-text-muted mb-2">No hay cajeros con sesión activa en los últimos 5 minutos.</p>
            @endif

            @if ($errors->has('close_day'))
                <x-alert variant="error">{{ $errors->first('close_day') }}</x-alert>
            @endif
        </section>
    @endif
    @if (! $employee && $breakdown)
        @include('pos.cuts.partials.employee-breakdown', ['date' => $date, 'breakdown' => $breakdown])
    @endif

    {{-- Mensaje de corte realizado --}}
    @if ($hasSnapshot && session('status'))
        <x-alert variant="success">
            Corte hecho a las {{ $snapshot->generated_at?->format('H:i') }} por {{ $snapshot->generatedBy?->name ?? '' }}.
        </x-alert>
    @endif

    {{-- Alerta de diferencia entre ventas y movimientos de caja --}}
    @if (! empty($rec['warning']))
        <x-alert variant="warning">{{ $rec['warning'] }}</x-alert>
    @endif

    @if ($hasSnapshot && ! empty($rec['difference']) && abs((float) $rec['difference']) >= 0.005 && ! $rec['is_reconciled'])
        <div class="mb-4 rounded-md border border-mz-warning/20 bg-mz-warning/5 px-4 py-3 text-sm text-mz-warning flex items-center gap-3">
            <span class="font-semibold">Hay una diferencia de ${{ number_format(abs((float) $rec['difference']), 2) }} entre ventas y movimientos de caja.</span>
            <a href="{{ route('pos.cash.index', ['from' => $date->toDateString(), 'to' => $date->toDateString()]) }}" class="underline hover:text-mz-warning">Ver detalle</a>
        </div>
    @endif

    @if (! $hasAnyMovement && ! $hasSnapshot)
        <div class="mb-4 rounded-md border border-dashed border-mz-border-light bg-mz-bg-surface-2 p-6 text-center text-mz-text-muted text-sm">
            No hay movimientos en esta fecha. Todo aparece en <b>$0.00</b>.
        </div>
    @endif

    {{-- Dos columnas principales --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
        {{-- Columna izquierda: Entradas de efectivo --}}
        <div class="mz-card mz-card-padded">
            <h3 class="text-lg font-semibold text-mz-text-primary mb-4">Entradas de efectivo</h3>
            <table class="w-full text-sm text-mz-text-secondary">
                <tr class="border-b border-mz-border-light">
                    <td class="py-3">Dinero inicial en caja</td>
                    <td class="py-3 text-right text-mz-success">${{ number_format((float) ($cp['initial'] ?? 0), 2) }}</td>
                </tr>
                <tr class="border-b border-mz-border-light">
                    <td class="py-3">Entrada de dinero (cambio)</td>
                    <td class="py-3 text-right text-mz-success">${{ number_format((float) ($cp['change'] ?? 0), 2) }}</td>
                </tr>
                <tr class="font-bold text-mz-text-primary border-t border-mz-border">
                    <td class="py-3">Total</td>
                    <td class="py-3 text-right font-bold">${{ number_format((float) ($cp['cash_in_total'] ?? 0), 2) }}</td>
                </tr>
            </table>
        </div>

        {{-- Columna derecha: Dinero en caja --}}
        <div class="mz-card mz-card-padded">
            <h3 class="text-lg font-semibold text-mz-text-primary mb-4">Dinero en caja</h3>
            <table class="w-full text-sm text-mz-text-secondary">
                <tr class="border-b border-mz-border-light text-mz-success">
                    <td class="py-3">Ventas en efectivo</td>
                    <td class="py-3 text-right">+ ${{ number_format((float) ($cb['sales_movements'] ?? 0), 2) }}</td>
                </tr>
                <tr class="border-b border-mz-border-light text-mz-success">
                    <td class="py-3">Entradas</td>
                    <td class="py-3 text-right">+ ${{ number_format((float) ($cp['cash_in_total'] ?? 0), 2) }}</td>
                </tr>
                <tr class="border-b border-mz-border-light text-mz-success">
                    <td class="py-3">Abonos de clientes en efectivo</td>
                    <td class="py-3 text-right">+ ${{ number_format((float) ($cb['customer_payments'] ?? 0), 2) }}</td>
                </tr>
                @foreach (($cashCustomerPayments ?? collect()) as $cashPayment)
                    <tr class="border-b border-mz-border-light/40 text-xs text-mz-text-muted">
                        <td class="pb-2 pl-4">Abono por {{ $cashPayment->customer_name }}:</td>
                        <td class="pb-2 text-right text-mz-success">+ ${{ number_format((float) $cashPayment->amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="border-b border-mz-border-light text-mz-error">
                    <td class="py-3">Devoluciones en efectivo</td>
                    <td class="py-3 text-right">- ${{ number_format((float) ($cb['refunds'] ?? 0), 2) }}</td>
                </tr>
                <tr class="border-b border-mz-border-light text-mz-error">
                    <td class="py-3">Pagos a proveedores / salidas</td>
                    <td class="py-3 text-right">- ${{ number_format((float) ($co['total'] ?? 0), 2) }}</td>
                </tr>
                <tr class="font-bold text-2xl text-mz-text-primary border-t border-mz-border pt-4">
                    <td class="py-3">TOTAL (debe haber)</td>
                    <td class="py-3 text-right font-bold text-2xl">${{ number_format((float) ($cb['subtotal'] ?? 0), 2) }}</td>
                </tr>
                <tr class="text-mz-text-primary border-t border-mz-border pt-2">
                    <td class="py-2"></td>
                    <td class="py-2 text-right text-sm text-mz-text-muted">Esto es lo que debes tener en caja</td>
                </tr>
            </table>

            {{-- Efectivo contado eliminado: el empleado solo visualiza, no captura --}}
        </div>
    </div>

    {{-- Franja intermedia: Pagos de contado y Ventas por departamento --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
        {{-- Pagos de contado --}}
        <div class="mz-card mz-card-padded">
            <h3 class="text-lg font-semibold text-mz-text-primary mb-4">Pagos de contado</h3>
            <table class="w-full text-sm text-mz-text-secondary">
                <tr class="border-b border-mz-border-light">
                    <td class="py-3">Efectivo</td>
                    <td class="py-3 text-right">${{ number_format((float) ($totals['sales']['total_cash'] ?? 0), 2) }}</td>
                </tr>
                <tr class="border-b border-mz-border-light">
                    <td class="py-3">Tarjeta</td>
                    <td class="py-3 text-right">${{ number_format((float) ($totals['sales']['total_non_cash'] ?? 0), 2) }}</td>
                </tr>
                <tr class="border-b border-mz-border-light">
                    <td class="py-3">Transferencia</td>
                    <td class="py-3 text-right">$0.00</td>
                </tr>
                <tr class="border-b border-mz-border-light">
                    <td class="py-3">Pagos de clientes (total)</td>
                    <td class="py-3 text-right">
                        <a href="#" class="text-mz-accent-primary underline hover:text-mz-accent-hover cursor-pointer"
                           onclick="event.preventDefault(); openCustomerPaymentsModal('{{ $date->toDateString() }}', {{ $employee?->id ?? 'null' }});">
                            ${{ number_format((float) ($totals['customer_payments'] ?? 0), 2) }}
                        </a>
                    </td>
                </tr>
                <tr class="border-b border-mz-border-light/40 text-xs text-mz-text-muted">
                    <td class="pb-2 pl-4">Abonos en efectivo</td>
                    <td class="pb-2 text-right">${{ number_format((float) ($totals['customer_payments_cash'] ?? 0), 2) }}</td>
                </tr>
                <tr class="border-b border-mz-border-light/40 text-xs text-mz-text-muted">
                    <td class="pb-2 pl-4">Abonos con tarjeta de débito/crédito</td>
                    <td class="pb-2 text-right">${{ number_format((float) ($totals['customer_payments_card'] ?? 0), 2) }}</td>
                </tr>
                <tr class="border-b border-mz-border-light/40 text-xs text-mz-text-muted">
                    <td class="pb-2 pl-4">Abonos por transferencia</td>
                    <td class="pb-2 text-right">${{ number_format((float) ($totals['customer_payments_transfer'] ?? 0), 2) }}</td>
                </tr>
                <tr class="font-bold text-mz-text-primary border-t border-mz-border">
                    <td class="py-3">Pagos a proveedores / salidas</td>
                    <td class="py-3 text-right">
                        <a href="#" class="text-mz-accent-primary underline hover:text-mz-accent-hover cursor-pointer"
                           onclick="event.preventDefault(); openSupplierPaymentsModal('{{ $date->toDateString() }}', {{ $employee?->id ?? 'null' }});">
                            ${{ number_format((float) ($co['total'] ?? 0), 2) }}
                        </a>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Ventas segunda tienda --}}
        <div class="mz-card mz-card-padded">
            <h3 class="text-lg font-semibold text-mz-text-primary mb-4">Ventas segunda tienda</h3>
            @if (! empty($navidadProducts))
                <table class="w-full text-sm text-mz-text-secondary">
                    <thead>
                        <tr class="border-b border-mz-border-light text-xs uppercase text-mz-text-muted">
                            <th class="py-3 text-left font-medium">Producto</th>
                            <th class="py-3 text-right font-medium">Cantidad vendida</th>
                            <th class="py-3 text-right font-medium">Importe</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mz-border-light">
                        @foreach ($navidadProducts as $product)
                            <tr>
                                <td class="py-3">{{ $product['name'] }}</td>
                                <td class="py-3 text-right">{{ \App\Support\Quantity::format($product['quantity']) }}</td>
                                <td class="py-3 text-right">${{ number_format((float) $product['total'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="font-bold text-mz-text-primary border-t border-mz-border pt-3">
                            <td class="py-3">Total Navidad</td>
                            <td class="py-3 text-right">{{ \App\Support\Quantity::format(array_sum(array_column($navidadProducts, 'quantity'))) }}</td>
                            <td class="py-3 text-right">${{ number_format((float) $navidadTotal, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            @else
                <div class="text-center text-mz-text-muted text-sm py-8">
                    Sin ventas de segunda tienda hoy
                </div>
            @endif
        </div>
    </div>

    {{-- Franja inferior fija --}}
    <div class="border border-mz-border rounded-lg p-5 bg-mz-bg-surface-2">
        <div class="flex justify-between text-xl font-bold text-mz-text-primary border-b border-mz-border-light pb-3 mb-3">
            <span>Ventas totales</span>
            <span class="text-right">${{ number_format((float) ($t['net_sales'] ?? 0), 2) }}</span>
        </div>
        <div class="flex justify-between text-xl font-bold text-mz-text-primary">
            <span>Ganancia del día</span>
            <span class="text-right">${{ number_format((float) ($t['profit'] ?? 0), 2) }}</span>
        </div>
    </div>

    {{-- Modal: Confirmar "Finalizar día" --}}
    @if ($canFinalizeDay)
        <dialog id="finalizar-dia-modal" class="w-full max-w-md rounded-lg border border-mz-border bg-mz-bg-surface shadow-lg p-6 mz-card">
            <div class="flex flex-col gap-4">
                <h3 class="text-lg font-semibold text-mz-text-primary">¿Estás seguro?</h3>
                <p class="text-mz-text-secondary">Esto cerrará tu turno y guardará el corte final. Las ventas quedarán bloqueadas hasta que uses «Reabrir venta».</p>
                <form method="POST" action="{{ route('pos.cuts.close-my-day') }}" class="flex flex-col gap-3">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                    <button type="button" class="x-button x-button-secondary size-sm" onclick="closeFinalizarDiaModal()">Cancelar</button>
                    <button type="submit" class="x-button x-button-primary size-sm">Sí, finalizar día</button>
                </form>
            </div>
        </dialog>
    @endif

    {{-- Modal: Detalle de Abonos de Clientes (Pagos de Clientes) --}}
    <dialog id="customer-payments-modal" class="w-full max-w-2xl rounded-lg border border-mz-border bg-mz-bg-surface shadow-lg p-6 mz-card">
        <div class="flex flex-col gap-4">
            <div class="flex justify-between items-start">
                <h3 class="text-lg font-semibold text-mz-text-primary">Abonos de Clientes</h3>
                <button type="button" class="text-mz-text-muted hover:text-mz-text-primary" onclick="closeCustomerPaymentsModal()">Cerrar</button>
            </div>
            <p class="text-xs text-mz-text-muted" id="customer-payments-fecha"></p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm divide-y divide-mz-border-light">
                    <thead>
                        <tr class="text-xs uppercase text-mz-text-muted">
                            <th class="py-3 text-left">Fecha</th>
                            <th class="py-3 text-left">Hora</th>
                            <th class="py-3 text-left">Cliente / Deudor</th>
                            <th class="py-3 text-right">Monto</th>
                            <th class="py-3 text-left">Método</th>
                            <th class="py-3 text-left">Motivo / Nota</th>
                            <th class="py-3 text-left">Empleado</th>
                        </tr>
                    </thead>
                    <tbody id="customer-payments-body" class="divide-y divide-mz-border-light">
                        <!-- se llena vía JS -->
                    </tbody>
                    <tfoot>
                        <tr class="font-bold text-mz-text-primary">
                            <td class="py-3 text-right" colspan="5">Total</td>
                            <td class="py-3 text-right" id="customer-payments-total">$0.00</td>
                            <td class="py-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p class="text-sm text-mz-text-muted" id="customer-payments-empty" hidden>Sin movimientos en este corte.</p>
        </div>
    </dialog>

    {{-- Modal: Detalle de Pagos a Proveedores --}}
    <dialog id="supplier-payments-modal" class="w-full max-w-2xl rounded-lg border border-mz-border bg-mz-bg-surface shadow-lg p-6 mz-card" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); box-sizing: border-box;">
        <div class="flex flex-col gap-4">
            <div class="flex justify-between items-start">
                <h3 class="text-lg font-semibold text-mz-text-primary">Pagos a Proveedores</h3>
                <button type="button" class="text-mz-text-muted hover:text-mz-text-primary" onclick="closeSupplierPaymentsModal()">Cerrar</button>
            </div>
            <p class="text-xs text-mz-text-muted" id="supplier-payments-fecha"></p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm divide-y divide-mz-border-light">
                    <thead>
                        <tr class="text-xs uppercase text-mz-text-muted">
                            <th class="py-3 text-left">¿Qué fue?</th>
                            <th class="py-3 text-center">Fecha</th>
                            <th class="py-3 text-center">Hora</th>
                            <th class="py-3 text-right">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody id="supplier-payments-body" class="divide-y divide-mz-border-light">
                        <!-- se llena vía JS -->
                    </tbody>
                    <tfoot>
                        <tr class="font-bold text-mz-text-primary">
                            <td class="py-3 text-right" colspan="3">Total</td>
                            <td class="py-3 text-right" id="supplier-payments-total">$0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p class="text-sm text-mz-text-muted" id="supplier-payments-empty" hidden>Sin movimientos en este corte.</p>
        </div>
    </dialog>

    <script>
        function openFinalizarDiaModal() {
            const d = document.getElementById('finalizar-dia-modal');
            if (d) d.showModal();
        }
        function closeFinalizarDiaModal() {
            const d = document.getElementById('finalizar-dia-modal');
            if (d) d.close();
        }

        async function openCustomerPaymentsModal(date, employeeId) {
            const modal = document.getElementById('customer-payments-modal');
            const body = document.getElementById('customer-payments-body');
            const empty = document.getElementById('customer-payments-empty');
            const fecha = document.getElementById('customer-payments-fecha');
            const totalEl = document.getElementById('customer-payments-total');

            body.innerHTML = '';
            empty.hidden = false;
            empty.textContent = 'Cargando detalle...';
            totalEl.textContent = '$0.00';
            fecha.textContent = 'Fecha: ' + date;

            // Abrimos el modal de inmediato: así se ve algo y se puede cerrar
            // aunque la petición tarde o falle.
            if (modal && !modal.open) {
                modal.showModal();
            }

            const url = new URL("{{ route('pos.cuts.customer-payments-detail') }}", window.location.origin);
            url.searchParams.set('date', date);
            if (employeeId) url.searchParams.set('employee', employeeId);

            try {
                const res = await fetch(url);
                const data = await res.json();

                if (!data.items || data.items.length === 0) {
                    empty.hidden = false;
                    empty.textContent = 'Sin movimientos en este corte.';
                } else {
                    empty.hidden = true;
                    let total = 0;
                    const metodosAbono = { cash: 'Efectivo', card: 'Tarjeta débito/crédito', transfer: 'Transferencia' };
                    data.items.forEach(function (item) {
                        total += parseFloat(item.monto || 0);
                        const tr = document.createElement('tr');
                        tr.className = 'text-mz-text-secondary';
                        const cells = [
                            [item.fecha, 'py-3 whitespace-nowrap'],
                            [item.hora, 'py-3 whitespace-nowrap'],
                            [item.deudor || item.cliente, 'py-3'],
                            ['$' + parseFloat(item.monto || 0).toFixed(2), 'py-3 text-right'],
                            [metodosAbono[item.metodo] || item.metodo, 'py-3'],
                            [item.motivo || '—', 'py-3'],
                            [item.empleado, 'py-3'],
                        ];
                        cells.forEach(function (cell) {
                            const td = document.createElement('td');
                            td.className = cell[1];
                            td.textContent = String(cell[0] ?? '');
                            tr.appendChild(td);
                        });
                        body.appendChild(tr);
                    });

                    totalEl.textContent = '$' + total.toFixed(2);
                }
            } catch (e) {
                empty.hidden = false;
                empty.textContent = 'Error cargando el detalle.';
            }
        }

        async function openSupplierPaymentsModal(date, employeeId) {
            const modal = document.getElementById('supplier-payments-modal');
            const body = document.getElementById('supplier-payments-body');
            const empty = document.getElementById('supplier-payments-empty');
            const fecha = document.getElementById('supplier-payments-fecha');
            const totalEl = document.getElementById('supplier-payments-total');

            body.innerHTML = '';
            empty.hidden = false;
            empty.textContent = 'Cargando detalle...';
            totalEl.textContent = '$0.00';
            fecha.textContent = 'Fecha: ' + date;

            // Abrimos el modal de inmediato: así se ve algo y se puede cerrar
            // aunque la petición tarde o falle.
            if (modal && !modal.open) {
                modal.showModal();
            }

            const url = new URL("{{ route('pos.cuts.supplier-payments-detail') }}", window.location.origin);
            url.searchParams.set('date', date);
            if (employeeId) url.searchParams.set('employee', employeeId);

            try {
                const res = await fetch(url);
                const data = await res.json();

                if (!data.items || data.items.length === 0) {
                    empty.hidden = false;
                    empty.textContent = 'Sin salidas en este corte.';
                } else {
                    empty.hidden = true;
                    let total = 0;
                    data.items.forEach(function (item) {
                        total += parseFloat(item.monto || 0);
                        const tr = document.createElement('tr');
                        tr.className = 'text-mz-text-secondary';
                        tr.innerHTML = `
                            <td class="py-3">${item.descripcion || item.categoria || '—'}</td>
                            <td class="py-3 text-center whitespace-nowrap">${item.fecha}</td>
                            <td class="py-3 text-center whitespace-nowrap">${item.hora}</td>
                            <td class="py-3 text-right">$${parseFloat(item.monto).toFixed(2)}</td>
                        `;
                        body.appendChild(tr);
                    });

                    totalEl.textContent = '$' + total.toFixed(2);
                }
            } catch (e) {
                empty.hidden = false;
                empty.textContent = 'Error cargando el detalle.';
            }
        }

        function closeCustomerPaymentsModal() {
            const d = document.getElementById('customer-payments-modal');
            if (d) d.close();
        }
        function closeSupplierPaymentsModal() {
            const d = document.getElementById('supplier-payments-modal');
            if (d) d.close();
        }

        // Cerrar modales con tecla Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                ['finalizar-dia-modal', 'customer-payments-modal', 'supplier-payments-modal'].forEach(function (id) {
                    const d = document.getElementById(id);
                    if (d && d.open) d.close();
                });
            }
        });
    </script>

    <style>
        /* Letras grandes y claras en pantallas de escritorio */
        @media (min-width: 1024px) {
            .text-lg { font-size: 1.125rem; }
            .text-2xl { font-size: 1.5rem; }
            table { font-size: 1rem; }
            table td, table th { padding: 0.75rem 0.5rem; }
        }

        /* En móvil los bloques van en una columna y Dinero en caja va primero ya está por el grid */
        @media (max-width: 1023px) {
            .text-2xl { font-size: 1.25rem; }
        }
    </style>
@endsection
