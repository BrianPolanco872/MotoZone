@extends('layouts.admin')

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
        $hasSnapshot = $snapshot !== null;
        $cashCounted = $hasSnapshot ? ((float) ($snapshot->cash_counted ?? 0)) : 0.0;
        $countedDiff = $hasSnapshot ? ((float) ($snapshot->difference ?? 0)) : 0.0;
        $countedReconciled = $hasSnapshot ? ((bool) ($snapshot->is_reconciled ?? false)) : false;
        $hasAnyMovement = (float) ($t['sales']['total'] ?? 0) > 0 || (float) ($cp['cash_in_total'] ?? 0) > 0 || (float) ($cb['cash_out'] ?? 0) > 0;
    ?>

    <!-- Barra superior -->
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between border-b border-slate-200 pb-4 mb-6">
        <form action="{{ $readOnly ? route('pos.cuts.mine') : route('pos.cuts.index') }}" method="GET" class="flex flex-wrap items-end gap-2">
            <div class="flex items-center gap-2">
                <label for="cut-date" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Fecha</label>
                <input id="cut-date" type="date" name="date" value="{{ $date->toDateString() }}" class="mt-1.5 h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
            </div>

            @if (! $readOnly && $employees->isNotEmpty())
                <div class="flex items-center gap-2">
                    <label for="cut-employee" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Empleado</label>
                    <select id="cut-employee" name="employee" class="mt-1.5 h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                        <option value="">Corte global</option>
                        @foreach ($employees as $option)
                            <option value="{{ $option->id }}" @selected($employee?->id === $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <button type="submit" class="h-10 rounded-lg bg-ink-900 px-4 text-sm font-medium text-white transition hover:bg-ink-800">Ver corte</button>
        </form>

        <div class="flex flex-wrap gap-2">
            @if ($hasSnapshot)
                <a href="{{ route('pos.cuts.print', array_filter(['date' => $date->toDateString(), 'employee' => $employee?->id, 'version' => $snapshot->version])) }}" target="_blank" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    <x-icon name="printer" class="size-4" />
                    Imprimir
                </a>
            @else
                <a href="{{ route('pos.cuts.print', array_filter(['date' => $date->toDateString(), 'employee' => $employee?->id])) }}" target="_blank" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    <x-icon name="printer" class="size-4" />
                    Imprimir
                </a>
            @endif

            @if (! $readOnly)
                <a href="{{ route('pos.cuts.history') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    <x-icon name="clock" class="size-4" />
                    Historial
                </a>

                @if ($hasSnapshot && $snapshot->version > 0)
                    <form action="{{ route('pos.cuts.store') }}" method="POST" onsubmit="return confirm('¿Rehacer el corte? Se guardará una nueva versión y se pedirá un motivo.')">
                        @csrf
                        <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                        @if (! $readOnly && $employees->isNotEmpty())
                            <input type="hidden" name="employee" value="{{ $employee?->id ?? '' }}">
                        @endif
                        <input type="hidden" name="rehacer" value="1">
                        <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-700 px-4 text-sm font-semibold text-white transition hover:bg-brand-800">
                            <x-icon name="check" class="size-4" />
                            Rehacer corte
                        </button>
                    </form>
                @else
                    <form action="{{ route('pos.cuts.store') }}" method="POST" onsubmit="return confirm('¿Hacer el corte? Se guardará una copia que no se puede modificar.')">
                        @csrf
                        <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                        @if (! $readOnly && $employees->isNotEmpty())
                            <input type="hidden" name="employee" value="{{ $employee?->id ?? '' }}">
                        @endif
                        <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-700 px-4 text-sm font-semibold text-white transition hover:bg-brand-800">
                            <x-icon name="check" class="size-4" />
                            Hacer corte
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    <!-- Mensaje de corte realizado -->
    @if ($hasSnapshot && session('status'))
        <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            Corte hecho a las {{ $snapshot->generated_at?->format('H:i') }} por {{ $snapshot->generatedBy?->name ?? '' }}.
        </div>
    @endif

    <!-- Alerta de diferencia entre ventas y movimientos de caja -->
    @if ($hasSnapshot && ! empty($rec['difference']) && abs((float) $rec['difference']) >= 0.005 && ! $rec['is_reconciled'])
        <div class="mb-6 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800 flex items-center gap-3">
            <span class="font-semibold">Hay una diferencia de ${{ number_format(abs((float) $rec['difference']), 2) }} entre ventas y movimientos de caja.</span>
            <a href="{{ route('pos.cash.index', ['from' => $date->toDateString(), 'to' => $date->toDateString()]) }}" class="underline hover:text-amber-700">Ver detalle</a>
        </div>
    @endif

    @if (! $hasAnyMovement && ! $hasSnapshot)
        <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-6 text-center text-slate-500 text-sm">
            No hay movimientos en esta fecha. Todo aparece en <b>$0.00</b>.
        </div>
    @endif

    <!-- Dos columnas principales -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Columna izquierda: Entradas de efectivo -->
        <div class="border border-slate-200 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Entradas de efectivo</h3>
            <table class="w-full text-sm text-slate-700">
                <tr class="border-b border-slate-100">
                    <td class="py-3">Dinero inicial en caja</td>
                    <td class="py-3 text-right text-emerald-600">${{ number_format((float) ($cp['initial'] ?? 0), 2) }}</td>
                </tr>
                <tr class="border-b border-slate-100">
                    <td class="py-3">Entrada de dinero (cambio)</td>
                    <td class="py-3 text-right text-emerald-600">${{ number_format((float) ($cp['change'] ?? 0), 2) }}</td>
                </tr>
                <tr class="font-bold text-slate-900 border-t border-slate-200">
                    <td class="py-3">Total</td>
                    <td class="py-3 text-right font-bold">${{ number_format((float) ($cp['cash_in_total'] ?? 0), 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Columna derecha: Dinero en caja -->
        <div class="border border-slate-200 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Dinero en caja</h3>
            <table class="w-full text-sm text-slate-700">
                <tr class="border-b border-slate-100 text-emerald-600">
                    <td class="py-3">Ventas en efectivo</td>
                    <td class="py-3 text-right">+ ${{ number_format((float) ($cb['sales_movements'] ?? 0), 2) }}</td>
                </tr>
                <tr class="border-b border-slate-100 text-emerald-600">
                    <td class="py-3">Entradas</td>
                    <td class="py-3 text-right">+ ${{ number_format((float) ($cp['cash_in_total'] ?? 0), 2) }}</td>
                </tr>
                <tr class="border-b border-slate-100 text-rose-600">
                    <td class="py-3">Devoluciones en efectivo</td>
                    <td class="py-3 text-right">- ${{ number_format((float) ($cb['refunds'] ?? 0), 2) }}</td>
                </tr>
                <tr class="border-b border-slate-100 text-rose-600">
                    <td class="py-3">Salidas / proveedores</td>
                    <td class="py-3 text-right">- ${{ number_format((float) ($co['total'] ?? 0), 2) }}</td>
                </tr>
                <tr class="font-bold text-2xl text-slate-900 border-t border-slate-200 pt-4">
                    <td class="py-3">TOTAL (debe haber)</td>
                    <td class="py-3 text-right font-bold text-2xl">${{ number_format((float) ($cb['subtotal'] ?? 0), 2) }}</td>
                </tr>
            </table>

            <!-- Efectivo contado opcional -->
            @if ($hasSnapshot && ! $readOnly)
                <div class="mt-6 border-t border-slate-200 pt-4">
                    <form action="{{ route('pos.cuts.record-cash', $snapshot) }}" method="POST" class="flex flex-wrap gap-3 items-end">
                        @csrf
                        @method('POST')
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-semibold text-slate-500 uppercase">Efectivo contado</label>
                            <input name="cash_counted" type="number" step="0.01" min="0"
                                value="{{ old('cash_counted', $cashCounted) }}"
                                class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm text-right text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 w-32">
                        </div>
                        <input name="reason" type="hidden" value="Conteo físico del corte">
                        <button type="submit" class="h-10 rounded-lg bg-brand-700 px-4 text-sm font-semibold text-white transition hover:bg-brand-800">Guardar conteo</button>
                    </form>

                    @if ($cashCounted > 0)
                        <div class="mt-2 p-2 rounded-lg text-sm
                            {{ $countedReconciled ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-rose-50 border border-rose-200 text-rose-700' }}">
                            @if ($countedReconciled)
                                ✔ Cuadra
                            @elseif ($countedDiff > 0)
                                Sobran ${{ number_format($countedDiff, 2) }}
                            @else
                                Faltan ${{ number_format(abs($countedDiff), 2) }}
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Franja intermedia: Pagos de contado y Ventas por departamento -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Pagos de contado -->
        <div class="border border-slate-200 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Pagos de contado</h3>
            <table class="w-full text-sm text-slate-700">
                <tr class="border-b border-slate-100">
                    <td class="py-3">Efectivo</td>
                    <td class="py-3 text-right">${{ number_format((float) ($totals['sales']['total_cash'] ?? 0), 2) }}</td>
                </tr>
                <tr class="border-b border-slate-100">
                    <td class="py-3">Tarjeta</td>
                    <td class="py-3 text-right">${{ number_format((float) ($totals['sales']['total_non_cash'] ?? 0), 2) }}</td>
                </tr>
                <tr class="border-b border-slate-100">
                    <td class="py-3">Transferencia</td>
                    <td class="py-3 text-right">$0.00</td>
                </tr>
                <tr class="border-b border-slate-100">
                    <td class="py-3">Pagos de clientes</td>
                    <td class="py-3 text-right">${{ number_format((float) ($cb['customer_payments'] ?? 0), 2) }}</td>
                </tr>
                <tr class="font-bold text-slate-900 border-t border-slate-200">
                    <td class="py-3">Pagos a proveedores</td>
                    <td class="py-3 text-right">${{ number_format((float) ($co['suppliers'] ?? 0), 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Ventas por departamento -->
        <div class="border border-slate-200 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Ventas por departamento</h3>
            <?php $hasDepts = ! empty($departments); ?>
            @if ($hasDepts)
                <table class="w-full text-sm text-slate-700">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs uppercase text-slate-500">
                            <th class="py-3 text-left font-medium">Departamento</th>
                            <th class="py-3 text-right font-medium">Piezas</th>
                            <th class="py-3 text-right font-medium">Importe</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($departments as $department)
                            <tr>
                                <td class="py-3">{{ $department['name'] }}</td>
                                <td class="py-3 text-right">{{ $department['quantity'] }}</td>
                                <td class="py-3 text-right">${{ number_format((float) $department['total'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="font-bold text-slate-900 border-t border-slate-200 pt-3">
                            <td class="py-3">Total</td>
                            <td class="py-3 text-right">{{ array_sum(array_column($departments, 'quantity')) }}</td>
                            <td class="py-3 text-right">${{ number_format((float) $t['sales'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            @else
                <div class="text-center text-slate-500 text-sm py-8">
                    Sin ventas hoy
                </div>
            @endif
        </div>
    </div>

    <!-- Franja inferior fija -->
    <div class="border border-slate-200 rounded-xl p-6 bg-slate-50">
        <div class="flex justify-between text-xl font-bold text-slate-900 border-b border-slate-200 pb-3 mb-3">
            <span>Ventas totales</span>
            <span class="text-right">${{ number_format((float) ($t['net_sales'] ?? 0), 2) }}</span>
        </div>
        <div class="flex justify-between text-xl font-bold text-slate-900">
            <span>Ganancia del día</span>
            <span class="text-right">${{ number_format((float) ($t['profit'] ?? 0), 2) }}</span>
        </div>
    </div>
@endsection

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