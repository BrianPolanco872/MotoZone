@extends('layouts.admin')

@section('title', 'Punto de venta')
@section('subtitle', 'Cobra, imprime tickets y controla la caja del día')

@section('content')
    @php
        $user = auth()->user();
        $currency = $settings['currency'] ?? 'MXN';
        $cashCategories = \App\Models\CashMovement::manualCategories();
        $showStartDay = ($permissions['cash'] ?? false)
            && ! ($initial_cash_registered ?? false)
            && ! ($permissions['day_closed'] ?? false);
    @endphp

    {{-- Banner de Iniciar Día si falta registrar fondo de caja --}}
    @if ($showStartDay)
        <div class="mb-4 flex flex-col justify-between gap-4 rounded-xl border border-amber-300 bg-linear-to-r from-amber-50 via-orange-50/60 to-mz-bg-surface p-4 shadow-sm sm:flex-row sm:items-center pos-night:border-amber-900/70 pos-night:from-[#231811] pos-night:via-[#1c1613] pos-night:to-[#171311]">
            <div class="flex items-center gap-3.5">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-amber-500/30 bg-amber-500/15 text-mz-warning">
                    <x-icon name="banknotes" class="size-6" />
                </div>
                <div>
                    <h3 class="text-sm font-bold text-mz-text-primary">Fondo de caja inicial pendiente</h3>
                    <p class="mt-0.5 text-xs text-mz-text-secondary">Para iniciar tu turno y cobrar tickets, registra el efectivo disponible con el que abre la caja.</p>
                </div>
            </div>
            <button type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-orange-700 px-4 py-2.5 text-xs font-bold whitespace-nowrap text-white shadow-sm shadow-orange-500/25 transition-colors hover:bg-orange-800"
                    onclick="document.getElementById('start-day-dialog').showModal()">
                <x-icon name="banknotes" class="size-4" />
                Iniciar turno y caja
            </button>
        </div>
    @endif

    @if (! $permissions['sell'])
        <div class="mb-4">
            <x-alert variant="warning">
                <p class="font-semibold">Tu cuenta puede abrir el punto de venta, pero no cobrar.</p>
                <p class="mt-1 text-xs">Pide al administrador el permiso <strong>«Cobrar y registrar ventas»</strong>.</p>
            </x-alert>
        </div>
    @endif

    {{-- Contenedor dinámico de error --}}
    <div id="pos-error" class="mb-4 hidden rounded-xl border border-mz-error/30 bg-mz-error/10 px-5 py-4 text-sm text-mz-error" role="alert"></div>

    {{-- Botón para reabrir corte cuando se cerró --}}
    <button id="pos-reopen-day" class="mb-4 inline-flex items-center gap-2 self-start rounded-xl border border-mz-warning/40 bg-mz-warning/10 px-4 py-2.5 text-xs font-bold text-mz-warning hover:bg-mz-warning/20 transition-all shadow-sm {{ ($permissions['own_day_closed'] ?? false) ? '' : 'hidden' }}" type="button">
        <x-icon name="refresh" class="size-4 shrink-0" />
        Reabrir mi corte del día
    </button>

    {{-- Búsqueda y detalle del carrito --}}
    <div class="flex min-h-0 flex-1 flex-col">
        <div class="flex min-h-0 min-w-0 flex-1 flex-col gap-4">
            <!-- Barra de Búsqueda y Escáner -->
            <section class="relative z-30 rounded-xl border border-mz-border bg-mz-bg-surface p-4 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="pos-search" class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-mz-text-primary">
                                <x-icon name="search" class="size-3.5 text-mz-accent-primary" />
                                Escanear o buscar producto
                            </label>
                            <span class="hidden text-[11px] font-semibold text-mz-text-secondary lg:inline">Pistola lectora o teclado</span>
                        </div>
                        <div class="relative">
                            <div class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-mz-accent-primary">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5zM6.75 6.75h.008v.008H6.75V6.75zM6.75 16.5h.008v.008H6.75V16.5zM16.5 6.75h.008v.008H16.5V6.75zM13.5 13.5h.008v.008H13.5V13.5zM13.5 16.5h.008v.008H13.5V16.5zM13.5 19.5h.008v.008H13.5V19.5zM16.5 13.5h.008v.008H16.5V13.5zM16.5 16.5h.008v.008H16.5V16.5zM16.5 19.5h.008v.008H16.5V19.5zM19.5 13.5h.008v.008H19.5V13.5zM19.5 16.5h.008v.008H19.5V16.5zM19.5 19.5h.008v.008H19.5V19.5z" />
                                </svg>
                            </div>
                            <input id="pos-search" type="search" autocomplete="off"
                                   placeholder="Escanea un código o escribe el inicio del nombre..."
                                   class="block w-full rounded-lg border border-mz-border-light bg-mz-bg-primary/70 py-2.5 pr-4 pl-11 text-sm text-mz-text-primary placeholder-mz-text-muted focus:border-mz-accent-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/25 transition-colors sm:pr-24">
                            <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 hidden sm:flex items-center gap-1">
                                <kbd class="rounded bg-mz-bg-surface-2 px-2 py-0.5 text-[10px] font-mono text-mz-text-muted border border-mz-border-light">Enter ↵</kbd>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="inline-flex h-10 cursor-pointer select-none items-center gap-2 rounded-lg border border-mz-border-light bg-mz-bg-surface px-3 text-xs font-bold text-mz-text-primary transition-colors hover:bg-mz-bg-surface-2 pos-night:bg-mz-bg-surface-2 pos-night:hover:bg-mz-border-light" title="Selecciona un producto del carrito para aplicar mayoreo">
                            <input id="pos-wholesale" type="checkbox" @disabled(! $permissions['wholesale']) class="size-4 accent-orange-700">
                            <span>Mayoreo</span>
                        </label>

                        <button type="button"
                                onclick="document.getElementById('custom-product-dialog').showModal()"
                                class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg border border-mz-border-light bg-mz-bg-surface px-3 text-xs font-bold whitespace-nowrap text-mz-text-primary transition-colors hover:bg-mz-bg-surface-2 pos-night:bg-mz-bg-surface-2 pos-night:hover:bg-mz-border-light">
                            <x-icon name="plus" class="size-3.5 text-mz-accent-primary" />
                            <span>Artículo libre</span>
                        </button>
                    </div>
                </div>

                {{-- Resultados desplegables de búsqueda --}}
                <div id="pos-results" class="pos-scrollbar absolute inset-x-0 top-full mt-2 hidden max-h-[50dvh] overflow-auto rounded-xl border border-mz-border-light bg-mz-bg-surface shadow-xl pos-night:shadow-black/60"></div>
            </section>

            {{-- Banner venta pendiente --}}
            <div id="pending-banner" class="hidden rounded-2xl border border-mz-warning/40 bg-gradient-to-r from-mz-warning/15 via-mz-bg-surface to-mz-bg-surface p-4 text-sm text-mz-warning shadow-lg">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-mz-warning/20 text-mz-warning border border-mz-warning/30">
                            <x-icon name="pause" class="size-4" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-mz-text-primary">Cobrando ticket pendiente</p>
                            <p class="mt-0.5 text-xs text-mz-warning">Los precios y cantidades ya están guardados para esta venta.</p>
                        </div>
                    </div>
                    <button type="button" id="pos-new-sale" class="inline-flex items-center gap-1.5 rounded-xl border border-mz-border-light bg-mz-bg-surface-2 px-3.5 py-2 text-xs font-bold text-mz-text-primary hover:bg-mz-bg-surface hover:border-mz-accent-primary/40 transition-all active:scale-95">
                        <x-icon name="plus" class="size-3.5" />
                        Nueva venta
                    </button>
                </div>
            </div>

            <!-- Tabla de Detalle del Carrito -->
            <section class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-xl border border-mz-border bg-mz-bg-surface shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-mz-border px-4 py-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex size-7 items-center justify-center rounded-lg bg-mz-accent-primary/10 text-mz-accent-primary border border-mz-accent-primary/20">
                            <x-icon name="cart" class="size-4" />
                        </div>
                        <div>
                            <h2 class="text-xs font-bold tracking-wider text-mz-text-primary uppercase">Detalle de la venta</h2>
                            <p class="text-[11px] text-mz-text-muted mt-0.5">Artículos agregados al ticket actual</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($permissions['cash'])
                            <button type="button" data-cash-open="in"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-mz-success/30 bg-mz-success/10 px-3 py-1.5 text-xs font-bold text-mz-success transition-colors hover:bg-mz-success/20">
                                <x-icon name="arrow-down" class="size-3.5 text-mz-success" />
                                <span>Entrada</span>
                                <kbd class="hidden rounded bg-mz-success/15 px-1 py-0.5 font-mono text-[10px] md:inline">F7</kbd>
                            </button>
                            <button type="button" data-cash-open="out"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-mz-error/30 bg-mz-error/10 px-3 py-1.5 text-xs font-bold text-mz-error transition-colors hover:bg-mz-error/20">
                                <x-icon name="arrow-up" class="size-3.5 text-mz-error" />
                                <span>Salida</span>
                                <kbd class="hidden rounded bg-mz-error/15 px-1 py-0.5 font-mono text-[10px] md:inline">F8</kbd>
                            </button>
                        @endif
                        <span id="pos-items-count" class="rounded-md border border-amber-500/40 bg-amber-500/10 px-2.5 py-1 font-mono text-xs font-bold text-mz-warning">0 renglones</span>
                    </div>
                </div>

                <div class="pos-scrollbar min-h-0 flex-1" style="height: 0; min-height: 388px; overflow: auto;">
                    <table id="pos-cart-table" class="min-w-full divide-y divide-mz-border text-sm">
                        <thead class="sticky top-0 z-10 h-12 bg-mz-bg-surface-2 pos-night:bg-slate-900">
                            <tr class="text-left text-[11px] tracking-wide text-mz-text-primary uppercase [&>th]:font-extrabold">
                                <th scope="col" class="px-4 py-3">Foto</th>
                                <th scope="col" class="px-4 py-3">Código</th>
                                <th scope="col" class="px-4 py-3">Producto</th>
                                <th scope="col" class="px-4 py-3">Categoría</th>
                                <th scope="col" class="px-4 py-3 text-right">Precio unitario</th>
                                <th scope="col" class="px-4 py-3 text-center">Cantidad</th>
                                <th scope="col" class="px-4 py-3 text-right">Importe</th>
                                <th scope="col" class="px-4 py-3 text-right">Existencia en tienda</th>
                                <th scope="col" class="px-4 py-3 text-right">Existencia en bodega</th>
                                <th id="pos-cart-store2-heading" scope="col" class="px-4 py-3 text-right" hidden>Existencia Tienda 2</th>
                                <th scope="col" class="px-4 py-3 text-center"><span class="sr-only">Quitar</span></th>
                            </tr>
                        </thead>
                        <tbody id="pos-cart-body" class="divide-y divide-mz-border-light/40"></tbody>

                        <!-- Estado vacío del carrito -->
                        <tbody id="pos-empty">
                            <tr>
                                <td id="pos-empty-cell" colspan="10" class="px-5 py-6 text-center h-full" style="vertical-align: middle; box-sizing: border-box;">
                                    <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-orange-500/10 text-mz-accent-primary ring-8 ring-orange-500/5">
                                        <x-icon name="cart" class="size-8" />
                                    </div>
                                    <p class="mt-4 text-base font-bold text-mz-text-primary">El ticket está vacío</p>
                                    <p class="mt-1 text-xs text-mz-text-muted max-w-sm mx-auto">Escanea el código de barras con la lectora o busca un producto en la barra superior para comenzar a cobrar.</p>
                                    <div class="mt-4 flex flex-wrap items-center justify-center gap-2 text-[11px] text-mz-text-muted">
                                        <span class="inline-flex items-center gap-1 rounded-md bg-mz-bg-surface-2 px-2 py-0.5 border border-mz-border-light font-mono"><kbd>Enter</kbd> Buscar</span>
                                        <span class="inline-flex items-center gap-1 rounded-md bg-mz-bg-surface-2 px-2 py-0.5 border border-mz-border-light font-mono"><kbd>F12</kbd> Cobrar</span>
                                        <span class="inline-flex items-center gap-1 rounded-md bg-mz-bg-surface-2 px-2 py-0.5 border border-mz-border-light font-mono"><kbd>Esc</kbd> Cerrar diálogo</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <dialog id="product-image-dialog" aria-labelledby="product-image-title" class="w-[calc(100%-2rem)] max-w-5xl max-h-[95dvh] overflow-auto rounded-2xl border border-mz-border-light bg-mz-bg-surface p-0 shadow-2xl shadow-black/95 backdrop:bg-black/75" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); box-sizing: border-box;">
        <div class="flex items-center justify-between gap-3 border-b border-mz-border-light bg-mz-bg-surface-2/40 px-5 py-4">
            <h2 id="product-image-title" class="min-w-0 break-words text-base font-bold text-mz-text-primary">Imagen del producto</h2>
            <button type="button" id="product-image-close" data-close-dialog="product-image-dialog" aria-label="Cerrar imagen" class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl border border-mz-border-light text-mz-text-secondary transition-colors hover:border-mz-error/50 hover:bg-mz-error/10 hover:text-mz-error" title="Cerrar imagen">
                <x-icon name="x" class="size-5" />
            </button>
        </div>
        <div class="p-4">
            <img id="product-image-preview" alt="" class="mx-auto max-h-[75dvh] w-full object-contain">
            <p id="product-image-error" hidden class="px-5 py-12 text-center text-sm text-mz-text-muted" role="status">La imagen no está disponible.</p>
        </div>
    </dialog>

    <!-- MODAL DE COBRO (Payment Dialog) -->
    <dialog id="payment-dialog" class="w-full max-w-lg rounded-2xl border border-mz-border-light bg-mz-bg-surface shadow-2xl shadow-black/95 p-0 overflow-hidden" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); box-sizing: border-box;">
        <!-- Header del Modal -->
        <div class="border-b border-mz-border-light px-6 py-4.5 bg-mz-bg-surface-2/40 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-xl bg-mz-accent-primary/15 text-mz-accent-primary border border-mz-accent-primary/25">
                    <x-icon name="banknotes" class="size-5" />
                </div>
                <div>
                    <h2 class="text-base font-bold text-mz-text-primary">Cobrar venta</h2>
                    <p class="text-xs text-mz-text-muted">Selecciona método de pago e ingresa el importe</p>
                </div>
            </div>
            <button type="button" data-close-dialog="payment-dialog" class="rounded-xl p-1.5 text-mz-text-muted hover:bg-mz-bg-surface-2 hover:text-mz-text-primary transition-colors" aria-label="Cerrar">
                <x-icon name="x" class="size-5" />
            </button>
        </div>

        <div class="p-6 space-y-4">
            <!-- Método de pago -->
            <div>
                <label for="payment-method" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Método de pago</label>
                <select id="payment-method" name="payment_method" class="mz-select mt-1.5 h-11 text-sm font-semibold">
                    <option value="cash">Efectivo</option>
                    <option value="card">Tarjeta de débito / crédito</option>
                    <option value="transfer">Transferencia bancaria</option>
                    <option value="mixed">Mixto (efectivo + otro)</option>
                </select>
            </div>

            <!-- Total a cobrar -->
            <div class="rounded-2xl border border-mz-accent-primary/40 bg-gradient-to-br from-mz-accent-primary/20 via-mz-accent-primary/10 to-transparent p-4 text-center shadow-inner">
                <p class="text-xs font-bold uppercase tracking-widest text-mz-accent-primary">Total a liquidar</p>
                <p id="payment-total" class="mt-1 font-display text-4xl font-black tabular-nums text-mz-text-primary">$0.00</p>
            </div>

            <!-- Pago con / Monto recibido -->
            <div id="payment-amount-group" class="space-y-2">
                <label for="payment-amount" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Pago con (Efectivo recibido)</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-bold text-mz-text-muted">$</span>
                    <input id="payment-amount" type="number" step="0.01" min="0" inputmode="decimal"
                           class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary/90 pl-9 pr-4 py-3 text-right text-3xl font-extrabold tabular-nums text-mz-text-primary placeholder-mz-text-muted focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/25 transition-all shadow-inner"
                           placeholder="0.00">
                </div>

                <!-- Botones rápidos de denominaciones -->
                <div class="flex flex-wrap gap-1.5 pt-1">
                    <button type="button" data-amount-exact class="inline-flex items-center rounded-lg border border-mz-accent-primary/40 bg-mz-accent-primary/10 hover:bg-orange-700 hover:text-white px-3 py-1.5 text-xs font-bold text-mz-accent-primary transition-all active:scale-95">
                        Exacto
                    </button>
                    <button type="button" data-amount="50" class="inline-flex items-center rounded-lg border border-mz-border-light bg-mz-bg-surface-2 hover:border-mz-accent-primary/50 hover:bg-mz-bg-surface hover:text-mz-text-primary px-3 py-1.5 text-xs font-mono font-semibold text-mz-text-secondary transition-all active:scale-95">
                        $50
                    </button>
                    <button type="button" data-amount="100" class="inline-flex items-center rounded-lg border border-mz-border-light bg-mz-bg-surface-2 hover:border-mz-accent-primary/50 hover:bg-mz-bg-surface hover:text-mz-text-primary px-3 py-1.5 text-xs font-mono font-semibold text-mz-text-secondary transition-all active:scale-95">
                        $100
                    </button>
                    <button type="button" data-amount="200" class="inline-flex items-center rounded-lg border border-mz-border-light bg-mz-bg-surface-2 hover:border-mz-accent-primary/50 hover:bg-mz-bg-surface hover:text-mz-text-primary px-3 py-1.5 text-xs font-mono font-semibold text-mz-text-secondary transition-all active:scale-95">
                        $200
                    </button>
                    <button type="button" data-amount="500" class="inline-flex items-center rounded-lg border border-mz-border-light bg-mz-bg-surface-2 hover:border-mz-accent-primary/50 hover:bg-mz-bg-surface hover:text-mz-text-primary px-3 py-1.5 text-xs font-mono font-semibold text-mz-text-secondary transition-all active:scale-95">
                        $500
                    </button>
                    <button type="button" data-amount="1000" class="inline-flex items-center rounded-lg border border-mz-border-light bg-mz-bg-surface-2 hover:border-mz-accent-primary/50 hover:bg-mz-bg-surface hover:text-mz-text-primary px-3 py-1.5 text-xs font-mono font-semibold text-mz-text-secondary transition-all active:scale-95">
                        $1,000
                    </button>
                </div>
            </div>

            <!-- Cambio -->
            <div class="flex items-center justify-between rounded-xl border border-mz-border-light/60 bg-mz-bg-surface-2/60 p-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-mz-text-muted block">Cambio a entregar</span>
                    <span class="text-[11px] text-mz-text-muted">Cálculo en tiempo real</span>
                </div>
                <span id="payment-change" class="font-display text-3xl font-black tabular-nums text-mz-text-primary">$0.00</span>
            </div>

            <p id="payment-error" class="hidden rounded-xl bg-mz-error/15 border border-mz-error/30 p-3 text-xs font-bold text-mz-error text-center"></p>
        </div>

        <!-- Footer del Modal -->
        <div class="flex flex-col-reverse gap-2 border-t border-mz-border-light/70 bg-mz-bg-surface-2/30 px-6 py-4 sm:flex-row sm:justify-end">
            <button type="button" data-close-dialog="payment-dialog" class="inline-flex items-center justify-center rounded-xl border border-mz-border-light px-4 py-2.5 text-xs font-bold text-mz-text-secondary hover:bg-mz-bg-surface-2 hover:text-mz-text-primary transition-all active:scale-95">
                Cancelar
            </button>
            <button type="button" id="payment-confirm" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 px-6 py-2.5 text-xs font-extrabold text-white shadow-lg shadow-emerald-600/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed active:scale-95">
                <x-icon name="check" class="size-4" />
                Confirmar cobro
            </button>
        </div>
    </dialog>

    <!-- MODAL DE MOVIMIENTO DE CAJA -->
    @if ($permissions['cash'])
        <dialog id="cash-dialog" class="w-full max-w-md rounded-2xl border border-mz-border-light bg-mz-bg-surface shadow-2xl shadow-black/95 p-0 overflow-hidden" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); box-sizing: border-box;">
            <form id="cash-form" action="{{ route('pos.cash.store') }}" method="POST">
                @csrf
                <input type="hidden" name="type" id="cash-type" value="in">
                <input type="hidden" name="category" id="cash-category" value="other">

                <div class="border-b border-mz-border-light px-6 py-4.5 bg-mz-bg-surface-2/40 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 items-center justify-center rounded-xl bg-mz-accent-primary/15 text-mz-accent-primary border border-mz-accent-primary/25">
                            <x-icon name="banknotes" class="size-5" />
                        </div>
                        <div>
                            <h2 id="cash-title" class="text-base font-bold text-mz-text-primary">Entrada de efectivo</h2>
                            <p class="text-xs text-mz-text-muted">Quedará registrado con tu usuario y la fecha actual.</p>
                        </div>
                    </div>
                    <button type="button" data-close-dialog="cash-dialog" class="rounded-xl p-1.5 text-mz-text-muted hover:bg-mz-bg-surface-2 hover:text-mz-text-primary transition-colors" aria-label="Cerrar">
                        <x-icon name="x" class="size-5" />
                    </button>
                </div>

                <div class="space-y-4 p-6">
                    <div>
                        <label for="cash-amount" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Cantidad ({{ $currency }})</label>
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-lg font-bold text-mz-text-muted">$</span>
                            <input id="cash-amount" name="amount" type="text" inputmode="decimal" step="0.01" min="0.01" required
                                   class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary/90 pl-8 pr-4 py-2.5 text-right text-xl font-bold tabular-nums text-mz-text-primary placeholder-mz-text-muted focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/25 transition-all"
                                   placeholder="0.00">
                        </div>
                    </div>

                    <div>
                        <label for="cash-concept" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Concepto / Motivo</label>
                        <textarea id="cash-concept" name="concept" rows="3" maxlength="255" required
                                  class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary/90 px-3.5 py-2.5 text-sm text-mz-text-primary placeholder-mz-text-muted focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/25 transition-all"
                                  placeholder="Describe brevemente la razón del movimiento de efectivo..."></textarea>
                    </div>

                    <p id="cash-error" class="hidden rounded-xl bg-mz-error/15 border border-mz-error/30 p-3 text-xs font-bold text-mz-error text-center" role="alert"></p>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-mz-border-light/70 bg-mz-bg-surface-2/30 px-6 py-4 sm:flex-row sm:justify-end">
                    <button type="button" data-close-dialog="cash-dialog" class="inline-flex items-center justify-center rounded-xl border border-mz-border-light px-4 py-2.5 text-xs font-bold text-mz-text-secondary hover:bg-mz-bg-surface-2 hover:text-mz-text-primary transition-all active:scale-95">
                        Cancelar
                    </button>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-700 hover:bg-orange-800 text-white px-5 py-2.5 text-xs font-bold shadow-md shadow-mz-accent-primary/20 transition-all active:scale-95">
                        <x-icon name="check" class="size-4" />
                        Guardar movimiento
                    </button>
                </div>
            </form>
        </dialog>
    @endif

    <!-- MODAL: EFECTIVO INICIAL DEL TURNO (Start Day) -->
    @if ($showStartDay)
        <dialog id="start-day-dialog" class="w-full max-w-md rounded-2xl border border-mz-border-light bg-mz-bg-surface shadow-2xl shadow-black/95 p-0 overflow-hidden" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); box-sizing: border-box;">
            <form id="start-day-form" action="{{ route('pos.cash.store') }}" method="POST">
                @csrf
                <input type="hidden" name="category" value="initial">
                <input type="hidden" name="type" value="in">
                <input type="hidden" name="concept" value="Efectivo inicial del turno">

                <div class="border-b border-mz-border-light px-6 py-4.5 bg-mz-bg-surface-2/40 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 items-center justify-center rounded-xl bg-mz-accent-primary/15 text-mz-accent-primary border border-mz-accent-primary/25">
                            <x-icon name="banknotes" class="size-5" />
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-mz-text-primary">Iniciar día y turno</h2>
                            <p class="text-xs text-mz-text-muted">Apertura con fondo de caja inicial</p>
                        </div>
                    </div>
                    <button type="button" class="rounded-xl p-1.5 text-mz-text-muted hover:bg-mz-bg-surface-2 hover:text-mz-text-primary transition-colors" onclick="document.getElementById('start-day-dialog').close()" aria-label="Cerrar">
                        <x-icon name="x" class="size-5" />
                    </button>
                </div>

                <div class="space-y-4 p-6">
                    <div>
                        <label for="start-day-amount" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Efectivo inicial disponible ({{ $currency }})</label>
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-xl font-bold text-mz-text-muted">$</span>
                            <input id="start-day-amount" name="amount" type="text" inputmode="decimal" step="0.01" min="0.01" required
                                   class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary/90 pl-8 pr-4 py-3 text-right text-2xl font-bold tabular-nums text-mz-text-primary placeholder-mz-text-muted focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/25 transition-all shadow-inner"
                                   placeholder="0.00">
                        </div>
                        <p class="mt-1.5 text-xs text-mz-text-muted">Ingresa las monedas y billetes disponibles en tu gaveta al comenzar.</p>
                    </div>

                    <p id="start-day-error" class="hidden rounded-xl bg-mz-error/15 border border-mz-error/30 p-3 text-xs font-bold text-mz-error text-center" role="alert"></p>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-mz-border-light/70 bg-mz-bg-surface-2/30 px-6 py-4 sm:flex-row sm:justify-end">
                    <button type="button" class="inline-flex items-center justify-center rounded-xl border border-mz-border-light px-4 py-2.5 text-xs font-bold text-mz-text-secondary hover:bg-mz-bg-surface-2 hover:text-mz-text-primary transition-all active:scale-95" onclick="document.getElementById('start-day-dialog').close()">
                        Cancelar
                    </button>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-700 hover:bg-orange-800 text-white px-5 py-2.5 text-xs font-bold shadow-md shadow-mz-accent-primary/20 transition-all active:scale-95">
                        <x-icon name="check" class="size-4" />
                        Guardar e iniciar día
                    </button>
                </div>
            </form>
        </dialog>
    @endif

    <!-- MODAL DE CAPTURA PREVIA: PRODUCTO A GRANEL -->
    <dialog id="bulk-dialog" aria-labelledby="bulk-title" class="w-full max-w-md rounded-2xl border border-mz-border-light bg-mz-bg-surface p-0 shadow-2xl shadow-black/95 overflow-hidden backdrop:bg-black/75" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); box-sizing: border-box;">
        <form id="bulk-form" onsubmit="return false;">
            <!-- Encabezado del modal -->
            <div class="flex items-center justify-between gap-3 border-b border-mz-border-light bg-mz-bg-surface-2/40 px-6 py-4.5">
                <div class="flex items-center gap-3">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-mz-accent-primary/15 text-mz-accent-primary border border-mz-accent-primary/30">
                        <x-icon name="tag" class="size-4.5" />
                    </span>
                    <div>
                        <h2 id="bulk-title" class="text-base font-bold text-mz-text-primary tracking-tight">¿Cantidad del Producto?</h2>
                        <p class="text-[11px] text-mz-text-muted">Modalidad: A granel</p>
                    </div>
                </div>
                <button type="button" id="bulk-close-btn" class="rounded-xl p-1.5 text-mz-text-muted transition-colors hover:bg-mz-bg-surface-2 hover:text-mz-text-primary" aria-label="Cerrar (Esc)">
                    <x-icon name="x" class="size-5" />
                </button>
            </div>

            <!-- Contenido del Modal -->
            <div class="space-y-4 p-6">
                <!-- Tarjeta del Producto y Precio Unitario -->
                <div class="rounded-xl border border-mz-border-light/70 bg-mz-bg-primary/70 p-3.5 space-y-1.5">
                    <p id="bulk-name" class="font-bold text-sm text-mz-text-primary leading-snug break-words"></p>
                    <div class="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-mz-border-light/40">
                        <span class="text-xs font-semibold text-mz-accent-primary font-mono">
                            Precio Unitario = <strong id="bulk-price" class="text-mz-text-primary font-bold font-mono">$0.00</strong>
                        </span>
                        <span id="bulk-stock-info" class="text-[11px] text-mz-text-muted">
                            Existencia: <strong id="bulk-available-stock" class="text-mz-text-secondary font-mono">0</strong>
                        </span>
                    </div>
                </div>

                <!-- Campo 1 — Cantidad del Producto -->
                <div>
                    <label for="bulk-quantity" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted mb-1.5 flex items-center justify-between">
                        <span>Cantidad del Producto</span>
                        <span class="text-[10px] lowercase text-mz-text-muted font-normal">decimales permitidos</span>
                    </label>
                    <div class="relative">
                        <input id="bulk-quantity" type="number" inputmode="decimal" step="0.001" min="0.001" max="9999" value="1.00"
                               class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary px-3.5 py-2.5 text-right text-xl font-bold tabular-nums font-mono text-mz-text-primary placeholder-mz-text-muted focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/25 transition-all shadow-inner"
                               placeholder="1.00">
                        <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold uppercase tracking-wider text-mz-text-muted">
                            Cant.
                        </span>
                    </div>
                </div>

                <!-- Campo 2 — Importe Actual -->
                <div>
                    <label for="bulk-amount" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted mb-1.5 flex items-center justify-between">
                        <span>Importe Actual</span>
                        <span class="text-[10px] text-mz-text-muted font-normal">o ingresa el monto en $</span>
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-lg font-bold text-mz-accent-primary">$</span>
                        <input id="bulk-amount" type="number" inputmode="decimal" step="0.01" min="0.01" max="999999"
                               class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary pl-8 pr-3.5 py-2.5 text-right text-xl font-bold tabular-nums font-mono text-mz-text-primary placeholder-mz-text-muted focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/25 transition-all shadow-inner"
                               placeholder="0.00">
                    </div>
                </div>

                <!-- Alerta de validación / stock -->
                <p id="bulk-error" role="alert" class="hidden rounded-xl border border-mz-error/30 bg-mz-error/15 p-3 text-xs font-bold text-mz-error text-center"></p>
            </div>

            <!-- Acciones / Botones -->
            <div class="flex flex-col-reverse gap-2.5 border-t border-mz-border-light/70 bg-mz-bg-surface-2/30 px-6 py-4 sm:flex-row sm:justify-end">
                <button type="button" id="bulk-cancel" class="inline-flex items-center justify-center rounded-xl border border-mz-border-light px-4 py-2.5 text-xs font-bold text-mz-text-secondary hover:bg-mz-bg-surface-2 hover:text-mz-text-primary transition-all active:scale-95">
                    Cancelar (Esc)
                </button>
                <button type="button" id="bulk-accept" class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-700 hover:bg-orange-800 text-white px-5 py-2.5 text-xs font-bold shadow-md shadow-mz-accent-primary/20 transition-all active:scale-95">
                    <x-icon name="check" class="size-4" />
                    <span>Aceptar (Enter)</span>
                </button>
            </div>
        </form>
    </dialog>

    <!-- MODAL: ARTÍCULO LIBRE / PRODUCTO COMÚN -->
    <dialog id="custom-product-dialog" class="w-full max-w-md rounded-2xl border border-mz-border-light bg-mz-bg-surface shadow-2xl shadow-black/95 p-0 overflow-hidden" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); box-sizing: border-box;">
        <form id="custom-product-form" method="POST">
            @csrf

            <div class="border-b border-mz-border-light px-6 py-4.5 bg-mz-bg-surface-2/40 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex size-9 items-center justify-center rounded-xl bg-mz-accent-primary/15 text-mz-accent-primary border border-mz-accent-primary/25">
                        <x-icon name="plus" class="size-5" />
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-mz-text-primary">Artículo libre</h2>
                        <p class="text-xs text-mz-text-muted">Cobro directo que no descuenta inventario</p>
                    </div>
                </div>
                <button type="button" class="rounded-xl p-1.5 text-mz-text-muted hover:bg-mz-bg-surface-2 hover:text-mz-text-primary transition-colors" onclick="document.getElementById('custom-product-dialog').close()" aria-label="Cerrar">
                    <x-icon name="x" class="size-5" />
                </button>
            </div>

            <div class="space-y-4 p-6">
                <div>
                    <label for="custom-product-name" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Nombre o descripción</label>
                    <input id="custom-product-name" name="name" type="text" maxlength="255" required
                           class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary/90 px-3.5 py-2.5 text-sm text-mz-text-primary placeholder-mz-text-muted focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/25 transition-all mt-1.5"
                           placeholder="Ej. Servicio de ajuste, accesorio genérico...">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="custom-product-price" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Precio ({{ $currency }})</label>
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-mz-text-muted">$</span>
                            <input id="custom-product-price" name="price" type="text" inputmode="decimal" step="0.01" min="0.01" required
                                   class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary/90 pl-7 pr-3 py-2.5 text-right text-base font-bold tabular-nums text-mz-text-primary placeholder-mz-text-muted focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/25 transition-all"
                                   placeholder="0.00">
                        </div>
                    </div>

                    <div>
                        <label for="custom-product-quantity" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Cantidad</label>
                        <input id="custom-product-quantity" name="quantity" type="number" step="1" min="1" max="9999" value="1" required
                               class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary/90 px-3 py-2.5 text-center text-base font-bold tabular-nums text-mz-text-primary focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/25 transition-all mt-1.5">
                    </div>
                </div>

                <p id="custom-product-error" class="hidden rounded-xl bg-mz-error/15 border border-mz-error/30 p-3 text-xs font-bold text-mz-error text-center" role="alert"></p>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-mz-border-light/70 bg-mz-bg-surface-2/30 px-6 py-4 sm:flex-row sm:justify-end">
                <button type="button" class="inline-flex items-center justify-center rounded-xl border border-mz-border-light px-4 py-2.5 text-xs font-bold text-mz-text-secondary hover:bg-mz-bg-surface-2 hover:text-mz-text-primary transition-all active:scale-95" onclick="document.getElementById('custom-product-dialog').close()">
                    Cancelar
                </button>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-700 hover:bg-orange-800 text-white px-5 py-2.5 text-xs font-bold shadow-md shadow-mz-accent-primary/20 transition-all active:scale-95">
                    <x-icon name="plus" class="size-4" />
                    Agregar al ticket
                </button>
            </div>
        </form>
    </dialog>

    <!-- MODAL DE TICKETS PENDIENTES -->
    <dialog id="pending-sales-dialog" aria-labelledby="pending-sales-title" class="w-full max-w-xl rounded-2xl border border-mz-border-light bg-mz-bg-surface p-0 shadow-2xl shadow-black/95 backdrop:bg-black/75" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); box-sizing: border-box;">
        <div class="flex items-center justify-between gap-3 border-b border-mz-border-light bg-mz-bg-surface-2/40 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-xl bg-mz-warning/15 text-mz-warning border border-mz-warning/25">
                    <x-icon name="pause" class="size-5" />
                </div>
                <div>
                    <h2 id="pending-sales-title" class="text-base font-bold text-mz-text-primary">Tickets pendientes</h2>
                    <p class="text-xs text-mz-text-muted">Cotizaciones guardadas sin cobrar</p>
                </div>
            </div>
            <button type="button" data-close-dialog="pending-sales-dialog" class="rounded-xl p-1.5 text-mz-text-muted hover:bg-mz-bg-surface-2 hover:text-mz-text-primary transition-colors" aria-label="Cerrar">
                <x-icon name="x" class="size-5" />
            </button>
        </div>
        <div class="max-h-[70dvh] overflow-y-auto pos-scrollbar p-4">
            <p id="pending-sales-empty" class="py-10 text-center text-sm text-mz-text-muted">No hay tickets pendientes.</p>
            <div id="pending-sales-list" class="space-y-2"></div>
        </div>
    </dialog>

    <!-- MODAL DE VENTAS DEL DÍA Y DEVOLUCIONES -->
    <dialog id="day-sales-dialog" aria-labelledby="day-sales-title" class="w-[calc(100%-1rem)] max-w-6xl rounded-2xl border border-mz-border-light bg-mz-bg-surface p-0 shadow-2xl shadow-black/95 backdrop:bg-black/75" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); box-sizing: border-box;">
        <div class="flex items-center justify-between gap-3 border-b border-mz-border-light bg-mz-bg-surface-2/40 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-xl bg-mz-accent-primary/15 text-mz-accent-primary border border-mz-accent-primary/25">
                    <x-icon name="report" class="size-5" />
                </div>
                <div>
                    <h2 id="day-sales-title" class="text-base font-bold text-mz-text-primary">Ventas del día y devoluciones</h2>
                    <p class="text-xs text-mz-text-muted">Consulta ventas, devuelve artículos e imprime copias</p>
                </div>
            </div>
            <button type="button" data-close-dialog="day-sales-dialog" class="rounded-xl p-1.5 text-mz-text-muted hover:bg-mz-bg-surface-2 hover:text-mz-text-primary transition-colors" aria-label="Cerrar">
                <x-icon name="x" class="size-5" />
            </button>
        </div>

        <div class="grid max-h-[min(78dvh,calc(100dvh_-_6rem))] grid-cols-1 gap-4 overflow-y-auto p-4 lg:h-[78dvh] lg:grid-cols-2 lg:grid-rows-[minmax(0,1fr)]">
            <section class="flex min-h-0 flex-col gap-3 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/30 p-3">
                <label for="day-sales-search" class="text-xs font-bold text-mz-text-secondary">Buscar por código del producto o descripción de la venta</label>
                <input id="day-sales-search" type="search" autocomplete="off" placeholder="Código o descripción" class="mz-input h-10 w-full text-sm">

                <div role="region" aria-label="Lista de ventas del día" tabindex="0" class="min-h-0 flex-1 overflow-auto rounded-xl border border-mz-border-light focus-visible:outline-2 focus-visible:outline-mz-accent-primary pos-scrollbar">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 bg-mz-bg-surface-2 text-mz-text-muted">
                            <tr>
                                <th class="px-3 py-2 font-bold">Código</th>
                                <th class="px-2 py-2 font-bold">Arts</th>
                                <th class="px-2 py-2 font-bold">Hora</th>
                                <th class="px-3 py-2 text-right font-bold">Total</th>
                            </tr>
                        </thead>
                        <tbody id="day-sales-rows"></tbody>
                    </table>
                    <p id="day-sales-empty" class="px-3 py-8 text-center text-sm text-mz-text-muted">No hay ventas para este día.</p>
                </div>

                <div class="flex flex-wrap items-end gap-2 border-t border-mz-border-light pt-3">
                    <div class="min-w-36 flex-1">
                        <label for="day-sales-date" class="text-xs font-bold text-mz-text-secondary">Del día:</label>
                        <input id="day-sales-date" type="date" class="mz-input mt-1 h-10 w-full text-sm">
                    </div>
                    <button type="button" id="day-sales-today" class="h-10 rounded-xl border border-mz-accent-primary/40 bg-mz-accent-primary/10 px-3 text-xs font-bold text-mz-accent-primary transition-all hover:bg-orange-700 hover:text-white active:scale-95">Hoy</button>
                </div>
            </section>

            <section class="relative flex min-h-0 flex-col gap-3 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/30 p-3">
                <div class="grid grid-cols-3 gap-2 rounded-xl border border-mz-border-light bg-mz-bg-primary/60 p-3 text-xs">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-mz-text-muted">Código</p>
                        <p id="day-sales-detail-code" class="truncate font-mono font-bold text-mz-text-primary">—</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-mz-text-muted">Cajero</p>
                        <p id="day-sales-detail-cashier" class="truncate font-semibold text-mz-text-primary">—</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-mz-text-muted">Cliente</p>
                        <p id="day-sales-detail-client" class="truncate font-semibold text-mz-text-primary">—</p>
                    </div>
                </div>

                <div id="day-sales-cancelled" hidden class="rounded-xl border-2 border-mz-error bg-mz-error/15 py-3 text-center font-display text-3xl font-black tracking-[0.3em] text-mz-error">CANCELADO</div>

                <div class="min-h-0 flex-1 overflow-auto rounded-xl border border-mz-border-light pos-scrollbar">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 bg-mz-bg-surface-2 text-mz-text-muted">
                            <tr>
                                <th class="px-3 py-2 font-bold">Cant.</th>
                                <th class="px-2 py-2 font-bold">Descripción</th>
                                <th class="px-3 py-2 text-right font-bold">Importe</th>
                            </tr>
                        </thead>
                        <tbody id="day-sales-items"></tbody>
                    </table>
                    <p id="day-sales-items-empty" class="px-3 py-8 text-center text-sm text-mz-text-muted">Selecciona una venta para ver el detalle.</p>
                </div>

                <button type="button" id="day-sales-return" disabled class="inline-flex items-center justify-center gap-2 rounded-xl border border-mz-warning/40 bg-mz-warning/10 px-4 py-2.5 text-xs font-bold text-mz-warning transition-all hover:bg-mz-warning hover:text-mz-bg-primary disabled:cursor-not-allowed disabled:opacity-40 active:scale-95">
                    Devolver Artículo seleccionado
                </button>

                <div class="grid grid-cols-2 gap-2">
                    <div class="rounded-xl border border-mz-accent-primary/40 bg-mz-accent-primary/10 p-3">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-mz-accent-primary">Total:</p>
                        <p id="day-sales-total" class="font-display text-2xl font-black tabular-nums text-mz-text-primary">$0.00</p>
                    </div>
                    <div class="rounded-xl border border-mz-border-light bg-mz-bg-primary/60 p-3">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-mz-text-muted">Pago Con:</p>
                        <p id="day-sales-paid" class="font-display text-2xl font-black tabular-nums text-mz-text-primary">$0.00</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" id="day-sales-cancel" disabled class="inline-flex flex-1 items-center justify-center rounded-xl border border-mz-error/40 bg-mz-error/10 px-4 py-2.5 text-xs font-bold text-mz-error transition-all hover:bg-mz-error hover:text-mz-bg-primary disabled:cursor-not-allowed disabled:opacity-40 active:scale-95">Cancelar Venta</button>
                    <button type="button" id="day-sales-print" disabled class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/80 px-4 py-2.5 text-xs font-bold text-mz-text-secondary transition-all hover:text-mz-text-primary disabled:cursor-not-allowed disabled:opacity-40 active:scale-95">
                        <x-icon name="printer" class="size-4 text-mz-accent-primary" />
                        Imprimir copia
                    </button>
                </div>
            </section>
        </div>
    </dialog>

    <style>
        /* Tailwind genera .inline-flex después de .hidden: hace falta especificidad para ocultar de verdad. */
        #pos-reopen-day.hidden { display: none; }
    </style>

    <script type="application/json" id="pos-config">
        {!! json_encode([
            'routes' => [
                'search' => route('pos.products.search'),
                'frequent' => route('pos.products.frequent'),
                'store' => route('pos.sales.store'),
                'charge' => route('pos.sales.charge', ['sale' => '__SALE__']),
                'show' => route('pos.sales.show', ['sale' => '__SALE__']),
                'cancel' => route('pos.sales.cancel', ['sale' => '__SALE__']),
                'cash' => $permissions['cash'] ? route('pos.cash.index') : null,
                'pending' => route('pos.sales.pending'),
                'pendingList' => route('pos.sales.pendingList'),
                'dayList' => route('pos.sales.dayList'),
                'returnItem' => route('pos.sales.returnItem', ['sale' => '__SALE__', 'saleItem' => '__ITEM__']),
                'ticket' => route('pos.sales.ticket', ['sale' => '__SALE__']),
                'ticketData' => route('pos.sales.ticketData', ['sale' => '__SALE__']),
                'reopen_my_day' => route('pos.cuts.reopen-my-day'),
            ],
            'permissions' => $permissions,
            'lastSale' => $lastSale,
            'currency' => $currency,
            'csrf' => csrf_token(),
            'storeEnabled' => (bool) ($settings['enabled'] ?? true),
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>
@endsection

@section('pos-checkout')
    <footer class="bottom-0 z-20 border-t border-mz-border bg-mz-bg-surface shadow-[0_-2px_12px_rgb(15_23_42/0.08)] lg:[@media(min-height:42rem)]:sticky">
        <div class="mx-auto max-w-[1920px] space-y-2 px-4 py-3 sm:px-6">
            <div id="last-sale-indicator" class="hidden rounded-lg border border-mz-success/30 bg-mz-success/10 px-3 py-2 text-xs">
                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 font-semibold text-mz-text-primary">
                        <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-white">
                            <x-icon name="check" class="size-3.5" />
                        </span>
                        <span>Última venta cobrada: Folio <span id="last-sale-folio" class="font-mono font-bold underline"></span></span>
                        @if ($permissions['tickets_own'])
                            <span class="hidden text-mz-success sm:inline">· Listo para imprimir ticket</span>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-x-3 gap-y-1 font-semibold text-mz-text-primary">
                        <span>Pago con: <span id="last-sale-paid" class="font-mono text-mz-success">—</span></span>
                        <span>Cambio: <span id="last-sale-change" class="font-mono">—</span></span>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 rounded-xl border border-mz-border-light bg-mz-bg-surface-2 px-3 py-2 text-mz-text-primary pos-night:bg-slate-900">
                    <span class="text-[10px] font-black tracking-wider uppercase">Total a cobrar</span>
                    <span class="rounded bg-mz-border px-1.5 py-0.5 font-mono text-[10px] font-bold">{{ $currency }}</span>
                    <p id="pos-total" class="font-mono text-xl leading-none font-black tracking-tight tabular-nums">$0.00</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 [&_svg]:shrink-0">
                    <button type="button" id="pos-clear" class="x-button x-button-secondary size-sm text-xs hover:border-mz-error/40 hover:bg-mz-error/10 hover:text-mz-error">
                        <x-icon name="trash" class="size-4" />
                        Eliminar venta
                    </button>
                    <div class="flex items-center gap-1.5">
                        <button type="button" id="pos-pending" @disabled(! $permissions['sell']) class="x-button x-button-secondary size-sm text-xs">
                            <x-icon name="pause" class="size-4" />
                            <span>Dejar pendiente</span>
                            <kbd class="hidden rounded bg-mz-bg-surface-2 px-1 py-0.5 font-mono text-[10px] md:inline">F6</kbd>
                        </button>
                        <button type="button" id="pos-pending-list" aria-label="Ver tickets pendientes" title="Ver tickets pendientes" class="x-button x-button-secondary h-9 min-w-9 px-2">
                            <x-icon name="report" class="size-4" />
                            <span id="pos-pending-count" class="hidden -ml-1 rounded-full bg-mz-warning px-1.5 text-[10px] font-bold text-mz-bg-primary">0</span>
                        </button>
                    </div>
                    <button type="button" id="pos-print-ticket" disabled class="x-button x-button-secondary size-sm text-xs">
                        <x-icon name="printer" class="size-4" />
                        Reimprimir último ticket
                    </button>
                    <button type="button" id="pos-day-sales" class="x-button x-button-secondary size-sm text-xs">
                        <x-icon name="report" class="size-4" />
                        Ventas del día y devoluciones
                    </button>
                    <button type="button" id="pos-charge" @disabled(! $permissions['sell']) class="x-button size-sm rounded-lg bg-orange-700 text-sm font-extrabold text-white shadow-sm shadow-orange-500/25 hover:bg-orange-800 focus:ring-orange-700">
                        <x-icon name="banknotes" class="size-5" />
                        <span>Cobrar venta</span>
                    </button>
                </div>
            </div>
        </div>
    </footer>
@endsection
