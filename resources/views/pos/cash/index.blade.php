@extends('layouts.admin')

@section('title', 'Caja')
@section('subtitle', 'Entradas y salidas de efectivo del día')

@php
    $user = auth()->user();
    $canSeeAny = $user->can('pos.sales.day.any');
    $showArchived = (bool) $filters['show_archived'] ?? false;
    $inCategories = $inCategories ?? array_filter($categories, fn ($c) => $c['type'] === \App\Models\CashMovement::TYPE_IN);
    $outCategories = $outCategories ?? array_filter($categories, fn ($c) => $c['type'] === \App\Models\CashMovement::TYPE_OUT);
@endphp

@section('content')
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <form action="{{ $showArchived ? route('pos.cash.archived') : route('pos.cash.index') }}" method="GET" class="flex flex-wrap items-end gap-2">
            <div class="flex items-center gap-2">
                <label for="cash-from" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 sr-only">Desde</label>
                <input id="cash-from" type="date" name="from" value="{{ $filters['from'] }}" class="mt-1.5 h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                <span class="text-xs text-slate-400">al</span>
                <input id="cash-to" type="date" name="to" value="{{ $filters['to'] }}" class="mt-1.5 h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
            </div>

            @if ($canSeeAny)
                <div>
                    <label for="cash-employee" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 sr-only">Empleado</label>
                    <select id="cash-employee" name="employee" class="mt-1.5 h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                        <option value="">Todos los empleados</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}" @selected($filters['employee'] == $emp->id)>{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="cash-type" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 sr-only">Tipo</label>
                <select id="cash-type" name="type" class="mt-1.5 h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                    <option value="">Todos</option>
                    <option value="in" @selected($filters['type'] === 'in')>Entradas</option>
                    <option value="out" @selected($filters['type'] === 'out')>Salidas</option>
                </select>
            </div>

            <div>
                <label for="cash-search" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 sr-only">Buscar concepto</label>
                <input id="cash-search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Buscar concepto..." class="mt-1.5 h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
            </div>

            <label class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-medium text-slate-700">
                <input id="cash-show-archived" name="show_archived" type="checkbox" value="1" @checked($showArchived) class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                Mostrar archivados
            </label>

            <button type="submit" class="h-10 rounded-lg bg-ink-900 px-4 text-sm font-medium text-white transition hover:bg-ink-800">Filtrar</button>

            @if ($filters['from'] || $filters['to'] || $filters['employee'] || $filters['type'] || $filters['search'])
                <a href="{{ $showArchived ? route('pos.cash.archived') : route('pos.cash.index') }}" class="h-10 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Limpiar</a>
            @endif

            @if ($canSeeAny)
                <a href="{{ route('pos.cash.export', array_filter([
                    'from' => $filters['from'] ?: null,
                    'to' => $filters['to'] ?: null,
                    'employee' => $filters['employee'] ?: null,
                    'type' => $filters['type'] ?: null,
                    'search' => $filters['search'] ?: null,
                    'show_archived' => $showArchived ? '1' : null,
                ])) }}" class="h-10 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    <x-icon name="download" class="size-4" />
                    Exportar CSV
                </a>
            @endif
        </form>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('pos.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                <x-icon name="arrow-uturn-left" class="size-4" />
                Volver al POS
            </a>
            @if (auth()->user()->can('pos.cut.manage'))
                <a href="{{ route('pos.cuts.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-ink-900 px-4 text-sm font-medium text-white transition hover:bg-ink-800">
                    <x-icon name="report" class="size-4" />
                    Corte del día
                </a>
            @endif
        </div>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Entradas del período</p>
            <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-emerald-800">${{ number_format($inTotal, 2) }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Salidas del período</p>
            <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-amber-800">${{ number_format($outTotal, 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Neto registrado</p>
            <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-slate-900">${{ number_format($netTotal, 2) }}</p>
            <p class="mt-1 text-xs text-slate-500">Excluye movimientos archivados (si está activado).</p>
        </div>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-[22rem_minmax(0,1fr)]">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">Registrar movimiento</h2>
            <p class="mt-1 text-xs text-slate-500">Anota el dinero inicial, el cambio, pagos a proveedores o gastos.</p>

            <form action="{{ route('pos.cash.store') }}" method="POST" class="mt-4 space-y-4" id="cash-form">
                @csrf

                <div>
                    <label for="movement-category" class="block text-sm font-medium text-slate-700">Tipo de movimiento</label>
                    <select id="movement-category" name="category" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
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
                    @error('category') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div id="movement-direction-wrap" class="hidden">
                    <label for="movement-type" class="block text-sm font-medium text-slate-700">Dirección del movimiento</label>
                    <select id="movement-type" name="type" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                        <option value="in">Entrada</option>
                        <option value="out">Salida</option>
                    </select>
                </div>

                <div>
                    <label for="movement-amount" class="block text-sm font-medium text-slate-700">Monto</label>
                    <input id="movement-amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-right text-lg font-semibold tabular-nums text-slate-900 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20" placeholder="0.00">
                    @error('amount') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="movement-concept" class="block text-sm font-medium text-slate-700">Concepto</label>
                    <input id="movement-concept" name="concept" type="text" value="{{ old('concept') }}" required maxlength="255" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20" placeholder="Ej. cambio para la caja, pago a proveedor...">
                    @error('concept') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 text-sm font-semibold text-white transition hover:bg-brand-800">
                    <x-icon name="check" class="size-4" />
                    Guardar movimiento
                </button>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-900">Movimientos
                        <span class="ml-2 text-xs text-slate-500">{{ $showArchived ? '(archivados)' : 'del período' }}</span>
                    </h2>
                    <p class="text-xs text-slate-500">{{ $movements->total() }} movimiento(s)</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3 font-medium">Fecha/Hora</th>
                            <th class="px-5 py-3 font-medium">Tipo</th>
                            <th class="px-5 py-3 font-medium">Concepto</th>
                            @if ($canSeeAny)
                                <th class="px-5 py-3 font-medium">Empleado</th>
                            @endif
                            <th class="px-5 py-3 text-right font-medium">Monto</th>
                            <th class="px-5 py-3 font-medium">Estado</th>
                            <th class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($movements as $movement)
                            <tr class="transition hover:bg-slate-50 @if ($movement->isArchived()) bg-amber-50 @elseif ($movement->isDeleted()) bg-rose-50 @endif">
                                <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $movement->happened_at?->format('d/m/Y H:i') }}</td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    <span @class([
                                        'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                        'bg-emerald-50 text-emerald-700' => $movement->isIn(),
                                        'bg-amber-50 text-amber-700' => ! $movement->isIn(),
                                    ])>
                                        <x-icon :name="$movement->isIn() ? 'arrow-down' : 'arrow-up'" class="size-3" />
                                        {{ $movement->isIn() ? 'Entrada' : 'Salida' }}
                                    </span>
                                    <span class="mt-0.5 block text-[11px] text-slate-400">{{ $movement->categoryLabel() }}</span>
                                </td>
                                <td class="px-5 py-3 text-slate-700">{{ $movement->concept }}</td>
                                @if ($canSeeAny)
                                    <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $movement->user?->name }}</td>
                                @endif
                                <td @class([
                                    'whitespace-nowrap px-5 py-3 text-right font-semibold tabular-nums',
                                    'text-emerald-700' => $movement->isIn(),
                                    'text-amber-700' => ! $movement->isIn(),
                                ])>
                                    {{ $movement->isIn() ? '+' : '−' }}${{ number_format((float) $movement->amount, 2) }}
                                </td>
                                <td class="px-5 py-3">
                                    @if ($movement->isArchived())
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-amber-100 text-amber-700">
                                            <x-icon name="archive" class="size-3" /> Archivado
                                        </span>
                                    @elseif ($movement->isDeleted())
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-rose-100 text-rose-700">
                                            <x-icon name="trash" class="size-3" /> Eliminado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-slate-100 text-slate-700">
                                            Activo
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($movement->isArchived() && $canSeeAny)
                                            <form action="{{ route('pos.cash.restore', $movement) }}" method="POST" onsubmit="return confirm('¿Restaurar este movimiento? Volverá a aparecer en la vista principal.')">
                                                @csrf
                                                <button type="submit" class="inline-flex h-7 items-center gap-1 rounded-lg border border-emerald-200 px-2 text-xs font-medium text-emerald-700 transition hover:bg-emerald-50" title="Restaurar">
                                                    <x-icon name="arrow-uturn-left" class="size-3.5" />
                                                </button>
                                            </form>
                                        @elseif ($movement->canBeDeleted() && $canSeeAny)
                                            <form action="{{ route('pos.cash.archive', $movement) }}" method="POST" onsubmit="return confirm('¿Archivar este movimiento? Saldrá de la vista principal pero seguirá contando en cortes históricos.')">
                                                @csrf
                                                <button type="submit" class="inline-flex h-7 items-center gap-1 rounded-lg border border-amber-200 px-2 text-xs font-medium text-amber-700 transition hover:bg-amber-50" title="Archivar">
                                                    <x-icon name="archive" class="size-3.5" />
                                                </button>
                                            </form>
                                            <form action="{{ route('pos.cash.destroy', $movement) }}" method="POST" onsubmit="return confirm('¿Eliminar este movimiento? Se requiere un motivo y no se podrá deshazar si pertenece a un corte cerrado.')">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="deletion_reason" value="">
                                                <button type="button" class="inline-flex h-7 items-center gap-1 rounded-lg border border-rose-200 px-2 text-xs font-medium text-rose-600 transition hover:bg-rose-50 delete-btn" title="Eliminar" data-url="{{ route('pos.cash.destroy', $movement) }}" data-csrf="{{ csrf_token() }}">
                                                    <x-icon name="trash" class="size-3.5" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canSeeAny ? 7 : 6 }}" class="px-5 py-14 text-center">
                                    <p class="text-sm font-medium text-slate-700">Aún no hay movimientos en este período</p>
                                    <p class="mt-1 text-xs text-slate-500">Registra el dinero inicial de la caja para comenzar.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $movements->links() }}</div>
        </section>
    </div>

    <!-- Modal de eliminación con motivo -->
    <dialog id="delete-confirm-dialog" class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-900/50">
        <form method="POST" class="p-6 space-y-4">
            @csrf
            @method('DELETE')
            <input type="hidden" name="deletion_reason" id="delete-reason-input">

            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Eliminar movimiento</h2>
                <button type="button" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Cerrar" onclick="document.getElementById('delete-confirm-dialog').close()">
                    <x-icon name="x" class="size-5" />
                </button>
            </div>

            <div class="space-y-4 px-5 py-4">
                <p class="text-sm text-slate-700">¿Estás seguro de eliminar este movimiento?</p>
                <p class="text-xs text-slate-500">El motivo es obligatorio y quedará registrado en la auditoría.</p>

                <div>
                    <label for="delete-reason" class="block text-sm font-medium text-slate-700">Motivo de eliminación</label>
                    <textarea id="delete-reason" name="deletion_reason" required rows="3" class="mt-1.5 h-24 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20" placeholder="Ej. error de captura, duplicado..."></textarea>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-slate-100 px-5 py-4 sm:flex-row sm:justify-end">
                <button type="button" class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 px-4 text-sm font-medium text-slate-600 hover:bg-slate-50" onclick="document.getElementById('delete-confirm-dialog').close()">
                    Cancelar
                </button>
                <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-rose-600 px-4 text-sm font-semibold text-white hover:bg-rose-700">
                    <x-icon name="trash" class="size-4" />
                    Eliminar
                </button>
            </div>
        </form>
    </dialog>

    <script>
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
    </script>
@endsection
