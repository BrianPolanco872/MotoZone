@extends('layouts.admin')

@section('title', 'Caja')
@section('subtitle', 'Entradas y salidas de efectivo del día')

@php
    $user = auth()->user();
    $canSeeAny = $user->can('pos.sales.day.any');
    $showArchived = (bool) $filters['show_archived'] ?? false;
    $inCategories = $inCategories ?? array_filter($categories, fn ($c) => $c['type'] === \App\Models\CashMovement::TYPE_IN);
    $outCategories = $outCategories ?? array_filter($categories, fn ($c) => $c['type'] === \App\Models\CashMovement::TYPE_OUT);
    $filtersActive = (bool) ($filters['from'] || $filters['to'] || $filters['employee'] || $filters['type'] || $filters['search']);
@endphp

@section('content')
    {{-- Barra de acciones --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('pos.index') }}" class="x-button x-button-secondary size-sm">
                <x-icon name="arrow-uturn-left" class="size-4" />
                Volver al POS
            </a>
            @if ($user->can('pos.cut.manage'))
                <a href="{{ route('pos.cuts.index') }}" class="x-button x-button-primary size-sm">
                    <x-icon name="report" class="size-4" />
                    Corte del día
                </a>
            @endif
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="button" data-panel-toggle="cash-filters" aria-controls="cash-filters" aria-expanded="{{ $filtersActive ? 'true' : 'false' }}"
                    class="x-button x-button-secondary size-sm">
                <x-icon name="search" class="size-4" />
                Filtros
            </button>

            @if ($canSeeAny)
                <a href="{{ route('pos.cash.export', array_filter([
                    'from' => $filters['from'] ?: null,
                    'to' => $filters['to'] ?: null,
                    'employee' => $filters['employee'] ?: null,
                    'type' => $filters['type'] ?: null,
                    'search' => $filters['search'] ?: null,
                    'show_archived' => $showArchived ? '1' : null,
                ])) }}" class="x-button x-button-secondary size-sm">
                    <x-icon name="download" class="size-4" />
                    Exportar CSV
                </a>
            @endif

            <button type="button" data-panel-toggle="cash-register" aria-controls="cash-register" aria-expanded="{{ $errors->any() ? 'true' : 'false' }}"
                    class="x-button x-button-primary size-sm">
                <x-icon name="plus" class="size-4" />
                Registrar movimiento
            </button>
        </div>
    </div>

    {{-- Filtros de movimientos (colapsables) --}}
    <section id="cash-filters" @class([
        'mt-4 mz-card mz-card-padded',
        'hidden' => ! $filtersActive,
    ])>
        <form action="{{ $showArchived ? route('pos.cash.archived') : route('pos.cash.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="cash-from" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Desde</label>
                <input id="cash-from" type="date" name="from" value="{{ $filters['from'] }}" class="mz-input mt-1.5 h-10">
            </div>

            <div>
                <label for="cash-to" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Hasta</label>
                <input id="cash-to" type="date" name="to" value="{{ $filters['to'] }}" class="mz-input mt-1.5 h-10">
            </div>

            @if ($canSeeAny)
                <div>
                    <label for="cash-employee" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Empleado</label>
                    <select id="cash-employee" name="employee" class="mz-select mt-1.5 h-10">
                        <option value="">Todos los empleados</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}" @selected($filters['employee'] == $emp->id)>{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="cash-type-filter" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Tipo</label>
                <select id="cash-type-filter" name="type" class="mz-select mt-1.5 h-10">
                    <option value="">Todos</option>
                    <option value="in" @selected($filters['type'] === 'in')>Entradas</option>
                    <option value="out" @selected($filters['type'] === 'out')>Salidas</option>
                </select>
            </div>

            <div class="min-w-[12rem] flex-1">
                <label for="cash-search" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Buscar concepto</label>
                <input id="cash-search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Buscar concepto..." class="mz-input mt-1.5 h-10 w-full">
            </div>

            <label class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-mz-border-light bg-mz-bg-surface-2 px-3 text-sm font-medium text-mz-text-secondary">
                <input id="cash-show-archived" name="show_archived" type="checkbox" value="1" @checked($showArchived) class="mz-checkbox">
                Mostrar archivados
            </label>

            <button type="submit" class="x-button x-button-primary size-sm">
                Filtrar
            </button>

            @if ($filtersActive)
                <a href="{{ $showArchived ? route('pos.cash.archived') : route('pos.cash.index') }}" class="x-button x-button-secondary size-sm">
                    Limpiar
                </a>
            @endif
        </form>
    </section>

    {{-- Corte del día por empleado (se actualiza solo) --}}
    <section class="mt-4 mz-card mz-card-padded overflow-hidden">
        <div class="flex flex-col gap-2 border-b border-mz-border-light px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-mz-text-primary">Corte del día por empleado</h2>
                <p class="mt-0.5 text-xs text-mz-text-muted">
                    {{ \Illuminate\Support\Carbon::parse($cutBoardDate)->locale('es')->translatedFormat('l d \d\e F Y') }}
                    · Se actualiza cada 25 segundos
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span id="cut-board-updated" class="text-[11px] text-mz-text-muted">Actualizado {{ now()->format('H:i:s') }}</span>
                <button type="button" id="cut-board-refresh" class="x-button x-button-secondary size-xs">
                    <x-icon name="refresh" class="size-3.5" />
                    Actualizar
                </button>
            </div>
        </div>

        <div id="cut-board-body"
             data-endpoint="{{ route('pos.cash.cut-board') }}"
             data-date="{{ $cutBoardDate }}"
             aria-live="polite">
            @include('pos.cash.partials.employee-cut-board', $cutBoard + ['canSeeAny' => $canSeeAny])
        </div>
    </section>

    {{-- Registrar movimiento (colapsable) --}}
    <section id="cash-register" @class([
        'mt-4 mz-card mz-card-padded overflow-hidden',
        'hidden' => ! $errors->any(),
    ])>
        <h2 class="text-base font-semibold text-mz-text-primary">Registrar movimiento</h2>
        <p class="mt-1 text-xs text-mz-text-muted">Anota el dinero inicial, el cambio, pagos a proveedores o gastos.</p>

        <form action="{{ route('pos.cash.store') }}" method="POST" class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-end" id="cash-form">
            @csrf

            <div>
                <label for="movement-category" class="block text-sm font-medium text-mz-text-secondary">Tipo de movimiento</label>
                <select id="movement-category" name="category" required class="mz-select mt-1.5 h-11 w-full">
                    <optgroup label="Entradas">
                        @foreach ($inCategories as $key => $category)
                            <option value="{{ $key }}" data-type="in" @selected(old('category') === $key)>{{ $category['label'] }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Salidas">
                        @foreach ($outCategories as $key => $category)
                            <option value="{{ $key }}" data-type="out" @selected(old('category') === $key)>{{ $category['label'] }}</option>
                        @endforeach
                    </optgroup>
                </select>
                @error('category') <p class="mt-1 text-xs font-medium text-mz-error">{{ $message }}</p> @enderror
            </div>

            <div id="movement-direction-wrap" class="hidden">
                <label for="movement-type" class="block text-sm font-medium text-mz-text-secondary">Dirección del movimiento</label>
                <select id="movement-type" name="type" class="mz-select mt-1.5 h-11 w-full">
                    <option value="in">Entrada</option>
                    <option value="out">Salida</option>
                </select>
            </div>

            <div>
                <label for="movement-amount" class="block text-sm font-medium text-mz-text-secondary">Monto</label>
                <input id="movement-amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" required class="mz-input mt-1.5 h-11 w-full text-right text-lg font-semibold tabular-nums text-mz-text-primary" placeholder="0.00">
                @error('amount') <p class="mt-1 text-xs font-medium text-mz-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="movement-concept" class="block text-sm font-medium text-mz-text-secondary">
                    Concepto <span class="font-normal text-mz-text-muted">(opcional)</span>
                </label>
                <input id="movement-concept" name="concept" type="text" value="{{ old('concept') }}" maxlength="255" class="mz-input mt-1.5 h-11 w-full text-sm text-mz-text-secondary placeholder-mz-text-muted" placeholder="Ej. cambio para la caja, pago a proveedor...">
                @error('concept') <p class="mt-1 text-xs font-medium text-mz-error">{{ $message }}</p> @enderror
            </div>

            {{-- Campos para abonos de clientes y pagos a proveedores --}}
            <div id="movement-extra-fields" class="hidden">
                <div>
                    <label for="movement-method" class="block text-sm font-medium text-mz-text-secondary">Método de pago</label>
                    <select id="movement-method" name="method" class="mz-select mt-1.5 h-11 w-full">
                        <option value="cash">Efectivo</option>
                        <option value="card">Tarjeta</option>
                        <option value="transfer">Transferencia</option>
                    </select>
                </div>
                <div id="movement-customer-field" class="hidden">
                    <label for="movement-customer-name" class="block text-sm font-medium text-mz-text-secondary">Nombre del cliente</label>
                    <input id="movement-customer-name" name="customer_name" type="text" value="{{ old('customer_name') }}" maxlength="255" class="mz-input mt-1.5 h-11 w-full text-sm text-mz-text-secondary" placeholder="Nombre del cliente">
                    <input id="movement-customer-id" name="customer_id" type="hidden" value="{{ old('customer_id') }}">
                    @error('customer_name') <p class="mt-1 text-xs font-medium text-mz-error">{{ $message }}</p> @enderror
                </div>
                <div id="movement-supplier-field" class="hidden">
                    <label for="movement-supplier-name" class="block text-sm font-medium text-mz-text-secondary">Nombre del proveedor</label>
                    <input id="movement-supplier-name" name="supplier_name" type="text" value="{{ old('supplier_name') }}" maxlength="255" class="mz-input mt-1.5 h-11 w-full text-sm text-mz-text-secondary" placeholder="Nombre del proveedor">
                    <input id="movement-supplier-id" name="supplier_id" type="hidden" value="{{ old('supplier_id') }}">
                    @error('supplier_name') <p class="mt-1 text-xs font-medium text-mz-error">{{ $message }}</p> @enderror
                </div>
                <div id="movement-reason-field">
                    <label for="movement-reason" class="block text-sm font-medium text-mz-text-secondary">Motivo / Nota</label>
                    <textarea id="movement-reason" name="reason" rows="2" maxlength="500" class="mz-input mt-1.5 h-11 w-full text-sm text-mz-text-secondary" placeholder="Ej. abono a cuenta, liquidación, pago de mercancía, factura #123...">{{ old('reason') }}</textarea>
                    @error('reason') <p class="mt-1 text-xs font-medium text-mz-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="lg:col-span-4 lg:flex lg:justify-end">
                <button type="submit" class="x-button x-button-primary size-sm">
                    <x-icon name="check" class="size-4" />
                    Guardar movimiento
                </button>
            </div>
        </form>
    </section>

    {{-- Movimientos del período --}}
    <section class="mt-4 overflow-hidden rounded-md border border-mz-border bg-mz-bg-surface shadow-md">
        <div class="border-b border-mz-border-light px-4 py-3">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <h2 class="text-base font-semibold text-mz-text-primary">Movimientos
                    <span class="ml-2 text-xs text-mz-text-muted">{{ $showArchived ? '(archivados)' : 'del período' }}</span>
                </h2>
                <p class="text-xs text-mz-text-muted">{{ $movements->total() }} movimiento(s)</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-mz-border-light text-sm">
                <thead class="bg-mz-bg-surface">
                    <tr class="text-left text-xs uppercase tracking-wide text-mz-text-muted">
                        <th class="px-4 py-3 font-medium">Fecha/Hora</th>
                        <th class="px-4 py-3 font-medium">Tipo</th>
                        <th class="px-4 py-3 font-medium">Concepto</th>
                        @if ($canSeeAny)
                            <th class="px-4 py-3 font-medium">Empleado</th>
                        @endif
                        <th class="px-4 py-3 text-right font-medium">Monto</th>
                        <th class="px-4 py-3 font-medium">Estado</th>
                        <th class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mz-border-light">
                    @forelse ($movements as $movement)
                        <tr class="transition hover:bg-mz-bg-surface-2 @if ($movement->isArchived()) bg-mz-warning/5 @elseif ($movement->isDeleted()) bg-mz-error/5 @endif">
                            <td class="whitespace-nowrap px-4 py-3 text-mz-text-secondary">{{ $movement->happened_at?->format('d/m/Y H:i') }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span @class([
                                    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold',
                                    'bg-mz-success/10 text-mz-success' => $movement->isIn(),
                                    'bg-mz-warning/10 text-mz-warning' => ! $movement->isIn(),
                                ])>
                                    <x-icon :name="$movement->isIn() ? 'arrow-down' : 'arrow-up'" class="size-3" />
                                    {{ $movement->isIn() ? 'Entrada' : 'Salida' }}
                                </span>
                                <span class="mt-0.5 block text-[11px] text-mz-text-muted">{{ $movement->categoryLabel() }}</span>
                            </td>
                            <td class="px-4 py-3 text-mz-text-secondary">{{ $movement->concept ?: '—' }}</td>
                            @if ($canSeeAny)
                                <td class="whitespace-nowrap px-4 py-3 text-mz-text-secondary">{{ $movement->user?->name }}</td>
                            @endif
                            <td @class([
                                'whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums',
                                'text-mz-success' => $movement->isIn(),
                                'text-mz-warning' => ! $movement->isIn(),
                            ])>
                                {{ $movement->isIn() ? '+' : '−' }}${{ number_format((float) $movement->amount, 2) }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($movement->isArchived())
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-mz-warning/10 text-mz-warning">
                                        <x-icon name="archive" class="size-3" /> Archivado
                                    </span>
                                @elseif ($movement->isDeleted())
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-mz-error/10 text-mz-error">
                                        <x-icon name="trash" class="size-3" /> Eliminado
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-mz-bg-surface-2 text-mz-text-secondary">
                                        Activo
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($movement->isArchived() && $canSeeAny)
                                        <form action="{{ route('pos.cash.restore', $movement) }}" method="POST" onsubmit="return confirm('¿Restaurar este movimiento? Volverá a aparecer en la vista principal.')">
                                            @csrf
                                            <button type="submit" class="inline-flex h-7 items-center gap-1 rounded-md border border-mz-success/20 px-2 text-xs font-medium text-mz-success transition hover:bg-mz-success/5" title="Restaurar">
                                                <x-icon name="arrow-uturn-left" class="size-3.5" />
                                            </button>
                                        </form>
                                    @elseif ($movement->canBeDeleted() && $canSeeAny)
                                        <form action="{{ route('pos.cash.archive', $movement) }}" method="POST" onsubmit="return confirm('¿Archivar este movimiento? Saldrá de la vista principal pero seguirá contando en cortes históricos.')">
                                            @csrf
                                            <button type="submit" class="inline-flex h-7 items-center gap-1 rounded-md border border-mz-warning/20 px-2 text-xs font-medium text-mz-warning transition hover:bg-mz-warning/5" title="Archivar">
                                                <x-icon name="archive" class="size-3.5" />
                                            </button>
                                        </form>
                                        <form action="{{ route('pos.cash.destroy', $movement) }}" method="POST" onsubmit="return confirm('¿Eliminar este movimiento? Se requiere un motivo y no se podrá deshacer si pertenece a un corte cerrado.')">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="deletion_reason" value="">
                                            <button type="button" class="inline-flex h-7 items-center gap-1 rounded-md border border-mz-error/20 px-2 text-xs font-medium text-mz-error transition hover:bg-mz-error/5 delete-btn" title="Eliminar" data-url="{{ route('pos.cash.destroy', $movement) }}" data-csrf="{{ csrf_token() }}">
                                                <x-icon name="trash" class="size-3.5" />
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canSeeAny ? 7 : 6 }}" class="px-4 py-14 text-center">
                                <p class="text-sm font-medium text-mz-text-secondary">Aún no hay movimientos en este período</p>
                                <p class="mt-1 text-xs text-mz-text-muted">Registra el dinero inicial de la caja para comenzar.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $movements->links() }}</div>
    </section>

    <!-- Modal de eliminación con motivo -->
    <dialog id="delete-confirm-dialog" class="w-full max-w-md rounded-lg border border-mz-border bg-mz-bg-surface shadow-lg p-0">
        <form method="POST" class="p-6 space-y-4">
            @csrf
            @method('DELETE')
            <input type="hidden" name="deletion_reason" id="delete-reason-input">

            <div class="border-b border-mz-border-light px-5 py-4">
                <h2 class="text-base font-semibold text-mz-text-primary">Eliminar movimiento</h2>
                <button type="button" class="rounded-md p-1 text-mz-text-muted hover:bg-mz-bg-surface-2 hover:text-mz-text-primary" aria-label="Cerrar" onclick="document.getElementById('delete-confirm-dialog').close()">
                    <x-icon name="x" class="size-5" />
                </button>
            </div>

            <div class="space-y-4 px-5 py-4">
                <p class="text-sm text-mz-text-secondary">¿Estás seguro de eliminar este movimiento?</p>
                <p class="text-xs text-mz-text-muted">El motivo es obligatorio y quedará registrado en la auditoría.</p>

                <div>
                    <label for="delete-reason" class="block text-sm font-medium text-mz-text-secondary">Motivo de eliminación</label>
                    <textarea id="delete-reason" name="deletion_reason" required rows="3" class="mz-input mt-1.5 h-24" placeholder="Ej. error de captura, duplicado..."></textarea>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-mz-border-light px-5 py-4 sm:flex-row sm:justify-end">
                <button type="button" class="x-button x-button-secondary size-sm" onclick="document.getElementById('delete-confirm-dialog').close()">
                    Cancelar
                </button>
                <button type="submit" class="x-button x-button-danger size-sm">
                    <x-icon name="trash" class="size-4" />
                    Eliminar
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

                const sync = () => button.setAttribute('aria-expanded', String(!panel.classList.contains('hidden')));
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

        // Actualización del panel "Corte del día por empleado"
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
                refreshButton?.classList.add('opacity-60');

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
                    // Un fallo de red no debe romper la pantalla; se reintenta en el siguiente ciclo.
                } finally {
                    refreshButton?.classList.remove('opacity-60');
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
                const csrf = btn.dataset.csrf;

                if (deleteForm && url) {
                    deleteForm.action = url;
                    deleteReasonInput.value = '';
                    deleteReasonTextarea.value = '';
                }

                if (deleteDialog) {
                    deleteDialog.showModal();
                }
            });
        });

        // Sync direction toggle for 'other' category
        const category = document.getElementById('movement-category');
        const wrap = document.getElementById('movement-direction-wrap');

        if (category && wrap) {
            const sync = () => {
                wrap.classList.toggle('hidden', category.value !== 'other');
            };

            category.addEventListener('change', sync);
            sync();
        }

        // Show/hide extra fields for customer/supplier payments
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

                // Set required attributes
                if (methodSelect) methodSelect.required = isCustomer || isSupplier;
                if (customerNameInput) customerNameInput.required = isCustomer;
                if (supplierNameInput) supplierNameInput.required = isSupplier;
            };

            category.addEventListener('change', syncExtra);
            syncExtra();
        }
    </script>
@endsection
