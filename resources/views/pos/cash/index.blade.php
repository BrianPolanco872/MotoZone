@extends('layouts.admin')

@section('title', 'Caja')
@section('subtitle', 'Entradas, salidas y monitoreo de ventas por cajero en tiempo real')

@php
    $user = auth()->user();
    $canSeeAny = $user->can('pos.sales.day.any');
    $showArchived = (bool) ($filters['show_archived'] ?? false);
    $inCategories = $inCategories ?? array_filter($categories, fn ($c) => $c['type'] === \App\Models\CashMovement::TYPE_IN);
    $outCategories = $outCategories ?? array_filter($categories, fn ($c) => $c['type'] === \App\Models\CashMovement::TYPE_OUT);
    $filtersActive = (bool) ($filters['from'] || $filters['to'] || $filters['type'] || $filters['search']);
    $historyFiltersActive = (bool) ($filters['employee'] || ($filters['day_requested'] ?? false) || $showArchived);
    $historyClearUrl = route('pos.cash.index', array_filter([
        'from' => $filters['from'] ?: null,
        'to' => $filters['to'] ?: null,
        'type' => $filters['type'] ?: null,
        'search' => $filters['search'] ?: null,
    ]));
    $filtersClearUrl = route('pos.cash.index', array_filter([
        'employee' => $filters['employee'] ?: null,
        'day' => ($filters['day_requested'] ?? false) ? ($filters['day'] ?: null) : null,
        'show_archived' => $showArchived ? '1' : null,
    ]));
@endphp

@section('content')
    {{-- BARRA SUPERIOR: Cockpit de Operaciones de Caja --}}
    <div class="rounded-2xl border border-mz-border bg-gradient-to-r from-mz-bg-surface via-mz-bg-surface/95 to-mz-bg-surface p-4 sm:p-5 shadow-xl shadow-black/25">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="inline-flex items-center gap-2 rounded-full border border-mz-accent-primary/30 bg-mz-accent-primary/10 px-3 py-1 text-xs font-semibold text-mz-accent-primary shadow-xs">
                        <x-icon name="banknotes" class="size-3.5" />
                        Control y Arqueo de Caja
                    </span>
                    <span class="text-xs text-mz-text-muted">
                        {{ \Illuminate\Support\Carbon::parse($cutBoardDate)->locale('es')->translatedFormat('l d \d\e F Y') }}
                    </span>
                </div>
                <p class="mt-2 text-xs text-mz-text-muted">
                    Supervisa en vivo las ventas de tus cajeros, entradas/salidas de efectivo y estado de cierre de turnos.
                </p>
            </div>

            {{-- Botones de acción rápida --}}
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('pos.index') }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/80 hover:bg-mz-bg-surface-2 px-4 text-xs font-bold text-mz-text-secondary hover:text-white transition-all shadow-xs active:scale-95">
                    <x-icon name="arrow-uturn-left" class="size-4" />
                    Volver al POS
                </a>

                @if ($user->can('pos.cut.manage'))
                    <a href="{{ route('pos.cuts.index') }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/80 hover:bg-mz-bg-surface-2 px-4 text-xs font-bold text-mz-text-secondary hover:text-white transition-all shadow-xs active:scale-95">
                        <x-icon name="report" class="size-4 text-mz-accent-primary" />
                        Corte del día
                    </a>
                @endif

                <button type="button" data-panel-toggle="cash-filters" aria-controls="cash-filters" aria-expanded="{{ $filtersActive ? 'true' : 'false' }}"
                        class="inline-flex h-11 items-center gap-2 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/80 hover:bg-mz-bg-surface-2 px-4 text-xs font-bold text-mz-text-secondary hover:text-white transition-all shadow-xs active:scale-95">
                    <x-icon name="search" class="size-4" />
                    <span>Filtros</span>
                    @if ($filtersActive)
                        <span class="size-2 rounded-full bg-mz-accent-primary"></span>
                    @endif
                </button>
            </div>
        </div>
    </div>

    {{-- Indicador rápido del día --}}
    <div class="mt-4 flex flex-wrap items-center gap-3">
        <span class="inline-flex items-center gap-2 rounded-xl border border-mz-border-light bg-mz-bg-surface px-4 py-2.5 text-sm font-bold text-mz-text-secondary shadow-md">
            <x-icon name="receipt" class="size-4 text-mz-accent-primary" />
            {{ $todayMovementsCount }} movimiento{{ $todayMovementsCount === 1 ? '' : 's' }} de caja hoy
        </span>
        <span class="text-xs text-mz-text-muted">
            Los totales consolidados de ventas, efectivo y turnos se muestran en el monitor de cajeros más abajo.
        </span>
    </div>

    {{-- FILTROS DE MOVIMIENTOS (Colapsable) --}}
    <section id="cash-filters" @class([
        'mt-4 rounded-2xl border border-mz-border bg-mz-bg-surface p-5 shadow-xl shadow-black/20',
        'hidden' => ! $filtersActive,
    ])>
        <form action="{{ route('pos.cash.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
            @if (($filters['day_requested'] ?? false) && $filters['day'])
                <input type="hidden" name="day" value="{{ $filters['day'] }}">
            @endif
            @if ($filters['employee'])
                <input type="hidden" name="employee" value="{{ $filters['employee'] }}">
            @endif
            @if ($showArchived)
                <input type="hidden" name="show_archived" value="1">
            @endif

            <div>
                <label for="cash-from" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Desde</label>
                <input id="cash-from" type="date" name="from" value="{{ $filters['from'] }}" class="mz-input mt-1.5 h-11 w-44 rounded-xl text-sm font-semibold">
            </div>

            <div>
                <label for="cash-to" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Hasta</label>
                <input id="cash-to" type="date" name="to" value="{{ $filters['to'] }}" class="mz-input mt-1.5 h-11 w-44 rounded-xl text-sm font-semibold">
            </div>

            <div>
                <label for="cash-type-filter" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Tipo</label>
                <select id="cash-type-filter" name="type" class="mz-select mt-1.5 h-11 rounded-xl text-sm font-semibold">
                    <option value="">Todos</option>
                    <option value="in" @selected($filters['type'] === 'in')>Entradas</option>
                    <option value="out" @selected($filters['type'] === 'out')>Salidas</option>
                </select>
            </div>

            <div class="min-w-[14rem] flex-1">
                <label for="cash-search" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Buscar concepto</label>
                <input id="cash-search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Buscar por concepto o motivo..." class="mz-input mt-1.5 h-11 w-full rounded-xl text-sm font-semibold">
            </div>

            <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-mz-accent-primary hover:bg-mz-accent-hover text-white px-5 text-xs font-bold transition-all shadow-md shadow-mz-accent-primary/20 active:scale-95">
                <x-icon name="search" class="size-4" />
                Filtrar
            </button>

            @if ($filtersActive)
                <a href="{{ $filtersClearUrl }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-mz-border-light bg-mz-bg-surface-2 px-4 text-xs font-bold text-mz-text-secondary hover:text-white transition-all active:scale-95">
                    Limpiar
                </a>
            @endif
        </form>
    </section>

    {{-- SECCIÓN PROTAGONISTA: MONITOR EN TIEMPO REAL DE VENTAS Y ESTADO DE CAJEROS --}}
    <section class="mt-4 rounded-2xl border border-mz-border bg-mz-bg-surface shadow-2xl shadow-black/25 overflow-hidden">
        {{-- Encabezado del monitor en vivo --}}
        <div class="flex flex-col gap-3 border-b border-mz-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between bg-gradient-to-r from-mz-bg-surface via-mz-bg-surface-2/30 to-mz-bg-surface">
            <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-xl bg-mz-accent-primary/15 text-mz-accent-primary border border-mz-accent-primary/25">
                    <x-icon name="chart" class="size-5" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-extrabold text-white">Monitor de Cajeros y Ventas en Vivo</h2>
                        <span class="inline-flex items-center gap-1 rounded-full bg-mz-success/15 border border-mz-success/30 px-2 py-0.2 text-[10px] font-bold text-mz-success">
                            <span class="relative flex size-1.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-mz-success opacity-75"></span>
                                <span class="relative inline-flex rounded-full size-1.5 bg-mz-success"></span>
                            </span>
                            Live
                        </span>
                    </div>
                    <p class="mt-0.5 text-xs text-mz-text-muted">
                        Ventas en tiempo real, desglose consolidado y estado de turnos (en línea / cerró día).
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span id="cut-board-updated" class="rounded-lg bg-mz-bg-surface-2 px-2.5 py-1 text-xs font-mono font-medium text-mz-text-muted border border-mz-border-light">
                    Actualizado {{ now()->format('H:i:s') }}
                </span>
                <button type="button" id="cut-board-refresh"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/80 hover:bg-mz-bg-surface px-3 py-1.5 text-xs font-bold text-mz-text-secondary hover:text-white transition-all shadow-xs active:scale-95"
                        title="Actualizar datos ahora">
                    <x-icon name="refresh" class="size-3.5 text-mz-accent-primary" />
                    <span>Actualizar</span>
                </button>
            </div>
        </div>

        {{-- Contenedor del Tablero (Se refresca automáticamente cada 25s) --}}
        <div id="cut-board-body"
             data-endpoint="{{ route('pos.cash.cut-board') }}"
             data-date="{{ $cutBoardDate }}"
             aria-live="polite">
            @include('pos.cash.partials.employee-cut-board', $cutBoard + ['canSeeAny' => $canSeeAny])
        </div>
    </section>

    {{-- REGISTRAR MOVIMIENTO (Colapsable) --}}
    <section id="cash-register" @class([
        'mt-4 rounded-2xl border border-mz-border bg-mz-bg-surface p-5 sm:p-6 shadow-xl shadow-black/20 overflow-hidden',
        'hidden' => ! $errors->any(),
    ])>
        <div class="flex items-center gap-2.5 pb-3 border-b border-mz-border">
            <x-icon name="plus" class="size-5 text-mz-accent-primary" />
            <div>
                <h2 class="text-base font-bold text-white">Registrar movimiento de efectivo</h2>
                <p class="text-xs text-mz-text-muted mt-0.5">Entradas (cambio inicial, abonos) o salidas (gastos menores, retiros, pagos a proveedor).</p>
            </div>
        </div>

        <form action="{{ route('pos.cash.store') }}" method="POST" class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1.5fr)_auto] lg:items-end" id="cash-form">
            @csrf

            <div>
                <label for="movement-category" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Categoría del movimiento</label>
                <select id="movement-category" name="category" required class="mz-select mt-1.5 h-11 w-full rounded-xl text-sm font-semibold">
                    <optgroup label="Entradas de efectivo">
                        @foreach ($inCategories as $key => $category)
                            <option value="{{ $key }}" data-type="in" @selected(old('category') === $key)>{{ $category['label'] }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Salidas de efectivo">
                        @foreach ($outCategories as $key => $category)
                            <option value="{{ $key }}" data-type="out" @selected(old('category') === $key)>{{ $category['label'] }}</option>
                        @endforeach
                    </optgroup>
                </select>
                @error('category') <p class="mt-1 text-xs font-semibold text-mz-error">{{ $message }}</p> @enderror
            </div>

            <div id="movement-direction-wrap" class="hidden">
                <label for="movement-type" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Dirección</label>
                <select id="movement-type" name="type" class="mz-select mt-1.5 h-11 w-full rounded-xl text-sm font-semibold">
                    <option value="in">Entrada (+)</option>
                    <option value="out">Salida (−)</option>
                </select>
            </div>

            <div>
                <label for="movement-amount" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Monto (MXN)</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-mz-text-muted">$</span>
                    <input id="movement-amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" required
                           class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary/90 pl-8 pr-3 py-2.5 text-right text-base font-bold tabular-nums text-white placeholder-mz-text-muted focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/20 transition-all shadow-inner"
                           placeholder="0.00">
                </div>
                @error('amount') <p class="mt-1 text-xs font-semibold text-mz-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="movement-concept" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">
                    Concepto o Motivo <span class="font-normal text-mz-text-muted/70">(opcional)</span>
                </label>
                <input id="movement-concept" name="concept" type="text" value="{{ old('concept') }}" maxlength="255"
                       class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary/90 px-3.5 py-2.5 text-sm text-white placeholder-mz-text-muted focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/20 transition-all mt-1.5"
                       placeholder="Ej. cambio para gaveta, compra de papelería, pago a proveedor...">
                @error('concept') <p class="mt-1 text-xs font-semibold text-mz-error">{{ $message }}</p> @enderror
            </div>

            {{-- Campos para abonos de clientes y pagos a proveedores --}}
            <div id="movement-extra-fields" class="hidden lg:col-span-4 grid grid-cols-1 gap-3 sm:grid-cols-2 pt-2 border-t border-mz-border-light/60">
                <div>
                    <label for="movement-method" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Método de pago</label>
                    <select id="movement-method" name="method" class="mz-select mt-1.5 h-11 w-full rounded-xl text-sm font-semibold">
                        <option value="cash">Efectivo</option>
                        <option value="card">Tarjeta</option>
                        <option value="transfer">Transferencia</option>
                    </select>
                </div>
                <div id="movement-customer-field" class="hidden">
                    <label for="movement-customer-name" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Nombre del cliente</label>
                    <input id="movement-customer-name" name="customer_name" type="text" value="{{ old('customer_name') }}" maxlength="255" class="mz-input mt-1.5 h-11 w-full rounded-xl text-sm font-semibold text-white" placeholder="Nombre completo del cliente">
                    <input id="movement-customer-id" name="customer_id" type="hidden" value="{{ old('customer_id') }}">
                    @error('customer_name') <p class="mt-1 text-xs font-semibold text-mz-error">{{ $message }}</p> @enderror
                </div>
                <div id="movement-supplier-field" class="hidden">
                    <label for="movement-supplier-name" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Nombre del proveedor</label>
                    <input id="movement-supplier-name" name="supplier_name" type="text" value="{{ old('supplier_name') }}" maxlength="255" class="mz-input mt-1.5 h-11 w-full rounded-xl text-sm font-semibold text-white" placeholder="Razón social o proveedor">
                    <input id="movement-supplier-id" name="supplier_id" type="hidden" value="{{ old('supplier_id') }}">
                    @error('supplier_name') <p class="mt-1 text-xs font-semibold text-mz-error">{{ $message }}</p> @enderror
                </div>
                <div id="movement-reason-field" class="sm:col-span-2">
                    <label for="movement-reason" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Nota / Justificación detallada</label>
                    <textarea id="movement-reason" name="reason" rows="2" maxlength="500" class="mz-input mt-1.5 w-full rounded-xl text-sm text-white" placeholder="Ej. abono a cuenta, liquidación de factura #123...">{{ old('reason') }}</textarea>
                    @error('reason') <p class="mt-1 text-xs font-semibold text-mz-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="lg:col-span-4 lg:flex lg:justify-end">
                <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-mz-accent-primary hover:bg-mz-accent-hover text-white px-6 text-xs font-bold shadow-lg shadow-mz-accent-primary/25 transition-all active:scale-95">
                    <x-icon name="check" class="size-4" />
                    Guardar movimiento en caja
                </button>
            </div>
        </form>
    </section>

    {{-- MOVIMIENTOS DEL PERÍODO (Tabla y auditoría) --}}
    <section class="mt-5 rounded-2xl border border-mz-border bg-mz-bg-surface shadow-xl shadow-black/20 overflow-hidden">
        <div class="border-b border-mz-border px-5 py-4 bg-mz-bg-surface-2/20">
            <div class="flex flex-col gap-3">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <x-icon name="receipt" class="size-4 text-mz-accent-primary" />
                        <h2 class="text-sm font-bold text-white">Historial de movimientos
                            <span class="ml-1 text-xs text-mz-text-muted font-normal">{{ $showArchived ? '(solo archivados)' : 'del día' }}</span>
                        </h2>
                    </div>
                    <span class="rounded-full bg-mz-bg-surface-2 px-3 py-0.5 text-xs font-semibold text-mz-text-secondary border border-mz-border-light">
                        {{ $movements->total() }} movimiento{{ $movements->total() === 1 ? '' : 's' }}
                    </span>
                </div>

                {{-- Filtros del historial: empleado, día/mes/año y solo archivados --}}
                <form action="{{ route('pos.cash.index') }}" method="GET" class="flex flex-wrap items-end gap-2">
                    @if ($filters['from'])
                        <input type="hidden" name="from" value="{{ $filters['from'] }}">
                    @endif
                    @if ($filters['to'])
                        <input type="hidden" name="to" value="{{ $filters['to'] }}">
                    @endif
                    @if ($filters['type'])
                        <input type="hidden" name="type" value="{{ $filters['type'] }}">
                    @endif
                    @if ($filters['search'])
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                    @endif

                    @if ($canSeeAny)
                        <div>
                            <label for="history-employee" class="block text-[11px] font-bold uppercase tracking-wider text-mz-text-muted">Empleado</label>
                            <select id="history-employee" name="employee" class="mz-select mt-1 h-10 w-48 rounded-xl text-xs font-semibold">
                                <option value="">Todos los empleados</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}" @selected($filters['employee'] == $emp->id)>{{ $emp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($showArchived)
                        <div>
                            <label for="history-day" class="block text-[11px] font-bold uppercase tracking-wider text-mz-text-muted">Día</label>
                            <input id="history-day" type="date" name="day" value="{{ $filters['day'] }}" class="mz-input mt-1 h-10 w-40 rounded-xl text-xs font-semibold">
                        </div>
                    @endif

                    <label class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-xl border border-mz-border-light bg-mz-bg-surface-2 px-3 text-xs font-bold text-mz-text-secondary select-none">
                        <input id="history-show-archived" name="show_archived" type="checkbox" value="1" @checked($showArchived) class="size-4 rounded border-mz-border-light text-mz-accent-primary focus:ring-mz-accent-primary bg-mz-bg-surface">
                        Solo archivados
                    </label>

                    <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-mz-accent-primary hover:bg-mz-accent-hover text-white px-4 text-xs font-bold transition-all shadow-md shadow-mz-accent-primary/20 active:scale-95">
                        <x-icon name="search" class="size-4" />
                        Aplicar
                    </button>

                    @if ($historyFiltersActive)
                        <a href="{{ $historyClearUrl }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-mz-border-light bg-mz-bg-surface-2 px-4 text-xs font-bold text-mz-text-secondary hover:text-white transition-all active:scale-95">
                            Limpiar
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <div class="overflow-x-auto pos-scrollbar">
            <table class="min-w-full divide-y divide-mz-border text-sm">
                <thead class="bg-mz-bg-surface-2/40">
                    <tr class="text-left text-xs uppercase tracking-wider text-mz-text-muted">
                        <th class="px-5 py-3.5 font-semibold">Fecha y Hora</th>
                        <th class="px-4 py-3.5 font-semibold">Tipo & Categoría</th>
                        <th class="px-4 py-3.5 font-semibold">Concepto / Motivo</th>
                        @if ($canSeeAny)
                            <th class="px-4 py-3.5 font-semibold">Empleado</th>
                        @endif
                        <th class="px-4 py-3.5 text-right font-semibold">Monto</th>
                        <th class="px-4 py-3.5 font-semibold">Estado</th>
                        <th class="px-4 py-3.5 text-right font-semibold"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mz-border-light/40">
                    @forelse ($movements as $movement)
                        <tr class="group transition-colors duration-150 hover:bg-mz-bg-surface-2/40 @if ($movement->isArchived()) bg-mz-warning/5 @elseif ($movement->isDeleted()) bg-mz-error/5 @endif">
                            <td class="whitespace-nowrap px-5 py-3.5 text-xs text-mz-text-muted font-mono">{{ $movement->happened_at?->format('d/m/Y H:i') }}</td>
                            <td class="whitespace-nowrap px-4 py-3.5">
                                <span @class([
                                    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold',
                                    'bg-mz-success/15 text-mz-success border border-mz-success/30' => $movement->isIn(),
                                    'bg-mz-error/15 text-mz-error border border-mz-error/30' => ! $movement->isIn(),
                                ])>
                                    <x-icon :name="$movement->isIn() ? 'arrow-down' : 'arrow-up'" class="size-3" />
                                    {{ $movement->isIn() ? 'Entrada' : 'Salida' }}
                                </span>
                                <span class="mt-1 block text-[11px] text-mz-text-muted">{{ $movement->categoryLabel() }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-sm text-mz-text-secondary">
                                <span class="font-medium text-white">{{ $movement->concept ?: '—' }}</span>
                            </td>
                            @if ($canSeeAny)
                                <td class="whitespace-nowrap px-4 py-3.5 text-sm text-mz-text-secondary font-medium">{{ $movement->user?->name }}</td>
                            @endif
                            <td @class([
                                'whitespace-nowrap px-4 py-3.5 text-right font-display text-base font-bold tabular-nums',
                                'text-mz-success' => $movement->isIn(),
                                'text-mz-error' => ! $movement->isIn(),
                            ])>
                                {{ $movement->isIn() ? '+' : '−' }}${{ number_format((float) $movement->amount, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5">
                                @if ($movement->isArchived())
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold bg-mz-warning/15 text-mz-warning border border-mz-warning/30">
                                        <x-icon name="archive" class="size-3" /> Archivado
                                    </span>
                                @elseif ($movement->isDeleted())
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold bg-mz-error/15 text-mz-error border border-mz-error/30">
                                        <x-icon name="trash" class="size-3" /> Eliminado
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-mz-bg-surface-2 text-mz-text-secondary border border-mz-border-light">
                                        Activo
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($movement->isArchived() && $canSeeAny)
                                        <form action="{{ route('pos.cash.restore', $movement) }}" method="POST" onsubmit="return confirm('¿Restaurar este movimiento? Volverá a aparecer en la vista principal.')">
                                            @csrf
                                            <button type="submit" class="inline-flex h-8 items-center gap-1 rounded-lg border border-mz-success/30 bg-mz-success/10 hover:bg-mz-success/20 px-2.5 text-xs font-bold text-mz-success transition-all active:scale-95" title="Restaurar a la lista activa">
                                                <x-icon name="arrow-uturn-left" class="size-3.5" />
                                                Restaurar
                                            </button>
                                        </form>
                                    @elseif ($movement->canBeDeleted() && $canSeeAny)
                                        <form action="{{ route('pos.cash.archive', $movement) }}" method="POST" onsubmit="return confirm('¿Archivar este movimiento? Saldrá de la vista principal pero seguirá contando en cortes históricos.')">
                                            @csrf
                                            <button type="submit" class="inline-flex h-8 items-center gap-1 rounded-lg border border-mz-warning/30 bg-mz-warning/10 hover:bg-mz-warning/20 px-2.5 text-xs font-bold text-mz-warning transition-all active:scale-95" title="Archivar movimiento">
                                                <x-icon name="archive" class="size-3.5" />
                                                Archivar
                                            </button>
                                        </form>
                                        <form action="{{ route('pos.cash.destroy', $movement) }}" method="POST" onsubmit="return confirm('¿Eliminar este movimiento? Se requiere un motivo y no se podrá deshacer si pertenece a un corte cerrado.')">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="deletion_reason" value="">
                                            <button type="button" class="inline-flex h-8 items-center gap-1 rounded-lg border border-mz-error/30 bg-mz-error/10 hover:bg-mz-error/20 px-2 text-xs font-bold text-mz-error transition-all delete-btn active:scale-95" title="Eliminar definitivamente" data-url="{{ route('pos.cash.destroy', $movement) }}" data-csrf="{{ csrf_token() }}">
                                                <x-icon name="trash" class="size-3.5" />
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canSeeAny ? 7 : 6 }}" class="px-5 py-16 text-center">
                                <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-mz-bg-surface-2 text-mz-text-muted">
                                    <x-icon name="banknotes" class="size-7" />
                                </div>
                                <p class="mt-3 text-sm font-bold text-mz-text-primary">
                                    {{ $showArchived ? 'No hay movimientos archivados con estos filtros' : 'Aún no hay movimientos registrados hoy' }}
                                </p>
                                <p class="mt-1 text-xs text-mz-text-muted">{{ $showArchived ? 'Ajusta el empleado o el día para ampliar la búsqueda en archivados.' : 'Los movimientos de días anteriores se consultan activando «Solo archivados».' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-5 border-t border-mz-border-light/40">{{ $movements->links() }}</div>
    </section>

    <!-- Modal de eliminación con motivo -->
    <dialog id="delete-confirm-dialog" class="w-full max-w-md rounded-2xl border border-mz-border-light bg-mz-bg-surface shadow-2xl shadow-black/95 p-0 overflow-hidden" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); box-sizing: border-box;">
        <form method="POST">
            @csrf
            @method('DELETE')
            <input type="hidden" name="deletion_reason" id="delete-reason-input">

            <div class="border-b border-mz-border-light px-6 py-4.5 bg-mz-bg-surface-2/40 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex size-9 items-center justify-center rounded-xl bg-mz-error/15 text-mz-error border border-mz-error/25">
                        <x-icon name="trash" class="size-5" />
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-white">Eliminar movimiento</h2>
                        <p class="text-xs text-mz-text-muted">Esta acción quedará registrada en auditoría</p>
                    </div>
                </div>
                <button type="button" class="rounded-xl p-1.5 text-mz-text-muted hover:bg-mz-bg-surface-2 hover:text-white transition-colors" aria-label="Cerrar" onclick="document.getElementById('delete-confirm-dialog').close()">
                    <x-icon name="x" class="size-5" />
                </button>
            </div>

            <div class="space-y-4 p-6">
                <p class="text-xs text-mz-text-secondary">¿Confirmas que deseas eliminar este movimiento de caja? Es obligatorio indicar el motivo para el registro de auditoría.</p>

                <div>
                    <label for="delete-reason" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Motivo de eliminación</label>
                    <textarea id="delete-reason" name="deletion_reason" required rows="3"
                              class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary/90 p-3 text-sm text-white placeholder-mz-text-muted focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/20 transition-all mt-1.5"
                              placeholder="Ej. error en captura de monto, duplicado, ticket cancelado..."></textarea>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-mz-border-light/70 bg-mz-bg-surface-2/30 px-6 py-4 sm:flex-row sm:justify-end">
                <button type="button" class="inline-flex items-center justify-center rounded-xl border border-mz-border-light px-4 py-2 text-xs font-bold text-mz-text-secondary hover:bg-mz-bg-surface-2 hover:text-white transition-all active:scale-95" onclick="document.getElementById('delete-confirm-dialog').close()">
                    Cancelar
                </button>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-mz-error hover:bg-mz-error/90 text-white px-5 py-2 text-xs font-bold shadow-md shadow-mz-error/20 transition-all active:scale-95">
                    <x-icon name="trash" class="size-4" />
                    Confirmar eliminación
                </button>
            </div>
        </form>
    </dialog>

    <script>
        // Paneles colapsables (filtros y registro de movimiento)
        (() => {
            const storedFilters = sessionStorage.getItem('pos-cash:filters');

            document.querySelectorAll('[data-panel-toggle]').forEach((button) => {
                const panel = document.getElementById(button.dataset.panelToggle);

                if (!panel) {
                    return;
                }

                if (button.dataset.panelToggle === 'cash-filters' && storedFilters !== null) {
                    panel.classList.toggle('hidden', storedFilters === '0');
                }

                const sync = () => {
                    const expanded = !panel.classList.contains('hidden');
                    button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                };

                sync();

                button.addEventListener('click', () => {
                    panel.classList.toggle('hidden');
                    sync();

                    if (button.dataset.panelToggle === 'cash-filters') {
                        sessionStorage.setItem('pos-cash:filters', panel.classList.contains('hidden') ? '0' : '1');
                    }
                });
            });
        })();

        // Actualización en tiempo real del monitor de cajeros (polling cada 25 segundos)
        (() => {
            const body = document.getElementById('cut-board-body');

            if (!body || !body.dataset.endpoint) {
                return;
            }

            const endpoint = body.dataset.endpoint;
            const date = body.dataset.date;
            const label = document.getElementById('cut-board-updated');
            const refreshButton = document.getElementById('cut-board-refresh');
            const intervalMs = 25000;

            let timer = null;
            let controller = null;

            const refresh = async () => {
                controller?.abort();
                controller = new AbortController();
                refreshButton?.classList.add('opacity-50');

                try {
                    const url = new URL(endpoint, window.location.origin);
                    url.searchParams.set('date', date);

                    const response = await fetch(url, {
                        headers: { Accept: 'application/json' },
                        cache: 'no-store',
                        signal: controller.signal,
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();
                    body.innerHTML = data.html;

                    if (label && data.generated_at) {
                        label.textContent = `Actualizado ${data.generated_at}`;
                    }
                } catch (error) {
                    // Si ocurre un corte de red, el siguiente ciclo lo reintenta silenciosamente.
                } finally {
                    refreshButton?.classList.remove('opacity-50');
                }
            };

            const start = () => {
                if (!timer) {
                    timer = setInterval(refresh, intervalMs);
                }
            };

            const stop = () => {
                if (timer) {
                    clearInterval(timer);
                    timer = null;
                }
            };

            refreshButton?.addEventListener('click', refresh);

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    stop();
                } else {
                    refresh();
                    start();
                }
            });

            if (!document.hidden) {
                start();
            }
        })();

        // Manejo de eliminación con motivo (modal)
        const deleteBtns = document.querySelectorAll('.delete-btn');
        const deleteDialog = document.getElementById('delete-confirm-dialog');
        const deleteReasonInput = document.getElementById('delete-reason-input');
        const deleteReasonTextarea = document.getElementById('delete-reason');
        const deleteForm = deleteDialog?.querySelector('form');

        deleteBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const url = btn.dataset.url;

                if (deleteForm && url) {
                    deleteForm.action = url;
                    if (deleteReasonInput) deleteReasonInput.value = '';
                    if (deleteReasonTextarea) deleteReasonTextarea.value = '';
                }

                if (deleteDialog) {
                    deleteDialog.showModal();
                }
            });
        });

        // Sincronización de dirección para categoría 'other'
        const category = document.getElementById('movement-category');
        const wrap = document.getElementById('movement-direction-wrap');

        if (category && wrap) {
            const sync = () => {
                wrap.classList.toggle('hidden', category.value !== 'other');
            };

            category.addEventListener('change', sync);
            sync();
        }

        // Mostrar/ocultar campos adicionales para abonos y pagos a proveedores
        const extraFields = document.getElementById('movement-extra-fields');
        const customerField = document.getElementById('movement-customer-field');
        const supplierField = document.getElementById('movement-supplier-field');
        const reasonField = document.getElementById('movement-reason-field');
        const methodSelect = document.getElementById('movement-method');
        const customerNameInput = document.getElementById('movement-customer-name');
        const supplierNameInput = document.getElementById('movement-supplier-name');

        if (category && extraFields) {
            const syncExtra = () => {
                const val = category.value;
                const isCustomer = val === 'customer_payment';
                const isSupplier = val === 'supplier_payment';

                extraFields.classList.toggle('hidden', !isCustomer && !isSupplier);
                customerField?.classList.toggle('hidden', !isCustomer);
                supplierField?.classList.toggle('hidden', !isSupplier);
                reasonField?.classList.toggle('hidden', !isCustomer && !isSupplier);

                if (methodSelect) methodSelect.required = isCustomer || isSupplier;
                if (customerNameInput) customerNameInput.required = isCustomer;
                if (supplierNameInput) supplierNameInput.required = isSupplier;
            };

            category.addEventListener('change', syncExtra);
            syncExtra();
        }
    </script>
@endsection
