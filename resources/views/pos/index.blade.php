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

    {{-- BARRA SUPERIOR: Cockpit del Cajero y Terminal POS --}}
    <div class="rounded-2xl border border-mz-border bg-gradient-to-r from-mz-bg-surface via-mz-bg-surface/95 to-mz-bg-surface p-4 sm:p-5 shadow-xl shadow-black/25">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="inline-flex items-center gap-2 rounded-full border border-mz-success/30 bg-mz-success/10 px-3 py-1 text-xs font-semibold text-mz-success shadow-xs">
                        <span class="relative flex size-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-mz-success opacity-75"></span>
                            <span class="relative inline-flex rounded-full size-2 bg-mz-success"></span>
                        </span>
                        Terminal POS Activa
                    </span>
                    @if ($settings['enabled'] === false)
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-mz-error/30 bg-mz-error/10 px-3 py-1 text-xs font-semibold text-mz-error">
                            <x-icon name="alert" class="size-3.5" />
                            Cobro desactivado por admin
                        </span>
                    @endif
                </div>

                <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-mz-text-muted">
                    <span class="inline-flex items-center gap-1.5 text-mz-text-secondary font-medium">
                        <x-icon name="store" class="size-3.5 text-mz-accent-primary" />
                        {{ $settings['business']['name'] }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-mz-text-secondary">
                        <x-icon name="users" class="size-3.5 text-mz-text-muted" />
                        Cajero: <strong class="text-white">{{ $user?->name }}</strong>
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-mz-text-muted">
                        <x-icon name="clock" class="size-3.5 text-mz-text-muted" />
                        {{ now()->format('d/m/Y H:i') }}
                    </span>
                    <span class="inline-flex items-center rounded-md bg-mz-bg-surface-2 px-2 py-0.5 font-mono text-[11px] font-semibold text-mz-accent-primary border border-mz-border-light">
                        {{ $currency }}
                    </span>
                </div>
            </div>

            {{-- Botones de acción rápida en cabecera --}}
            <div class="flex flex-wrap items-center gap-2">
                @if ($permissions['cash'])
                    <button type="button" data-cash-open="in"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/70 px-3 py-2 text-xs font-semibold text-mz-text-secondary transition-all hover:border-mz-success/50 hover:bg-mz-success/10 hover:text-mz-success shadow-xs active:scale-95">
                        <x-icon name="arrow-down" class="size-3.5 text-mz-success" />
                        <span>Entrada</span>
                        <kbd class="hidden md:inline rounded bg-black/40 px-1.5 py-0.2 text-[10px] font-mono text-mz-text-muted">F7</kbd>
                    </button>
                    <button type="button" data-cash-open="out"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/70 px-3 py-2 text-xs font-semibold text-mz-text-secondary transition-all hover:border-mz-error/50 hover:bg-mz-error/10 hover:text-mz-error shadow-xs active:scale-95">
                        <x-icon name="arrow-up" class="size-3.5 text-mz-error" />
                        <span>Salida</span>
                        <kbd class="hidden md:inline rounded bg-black/40 px-1.5 py-0.2 text-[10px] font-mono text-mz-text-muted">F8</kbd>
                    </button>
                @endif

                <a href="{{ route('pos.sales.day') }}"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/70 px-3.5 py-2 text-xs font-semibold text-mz-text-secondary transition-all hover:border-mz-accent-primary/40 hover:bg-mz-bg-surface-2 hover:text-white shadow-xs active:scale-95">
                    <x-icon name="chart" class="size-3.5 text-mz-accent-primary" />
                    <span>Ventas del día</span>
                </a>

                @if ($permissions['cut'])
                    <a href="{{ route('pos.cuts.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-mz-accent-primary hover:bg-mz-accent-hover text-white px-4 py-2 text-xs font-bold transition-all shadow-md shadow-mz-accent-primary/20 active:scale-95">
                        <x-icon name="report" class="size-3.5" />
                        <span>Corte del día</span>
                    </a>
                @endif

                {{-- Accesos que en escritorio viven en el panel derecho; en móvil no existe. --}}
                <a href="{{ route('pos.sales.pending') }}"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/70 px-3.5 py-2 text-xs font-semibold text-mz-text-secondary transition-all hover:border-mz-warning/50 hover:bg-mz-warning/10 hover:text-mz-warning shadow-xs active:scale-95 xl:hidden">
                    <x-icon name="pause" class="size-3.5 text-mz-warning" />
                    <span>Pendientes</span>
                </a>

                @if ($permissions['returns_request'])
                    <a href="{{ route('pos.returns.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/70 px-3.5 py-2 text-xs font-semibold text-mz-text-secondary transition-all hover:border-mz-accent-primary/40 hover:bg-mz-bg-surface-2 hover:text-white shadow-xs active:scale-95 xl:hidden">
                        <x-icon name="arrow-uturn-left" class="size-3.5 text-mz-accent-primary" />
                        <span>Devoluciones</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Banner de Iniciar Día si falta registrar fondo de caja --}}
    @if ($showStartDay)
        <div class="mt-4 rounded-2xl border border-mz-accent-primary/40 bg-gradient-to-r from-mz-accent-primary/15 via-mz-bg-surface to-mz-bg-surface p-4 sm:p-5 shadow-xl shadow-black/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-mz-accent-primary/20 text-mz-accent-primary border border-mz-accent-primary/30">
                    <x-icon name="banknotes" class="size-6" />
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Fondo de caja inicial pendiente</h3>
                    <p class="text-xs text-mz-text-muted mt-0.5">Para iniciar tu turno y cobrar tickets, registra el efectivo disponible con el que abre la caja.</p>
                </div>
            </div>
            <button type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-mz-accent-primary to-mz-accent-hover hover:brightness-110 text-white px-5 py-2.5 text-xs font-extrabold shadow-lg shadow-mz-accent-primary/25 transition-all transform active:scale-95 whitespace-nowrap"
                    onclick="document.getElementById('start-day-dialog').showModal()">
                <x-icon name="banknotes" class="size-4" />
                Iniciar turno y caja
            </button>
        </div>
    @endif

    @if (! $permissions['sell'])
        <div class="mt-4">
            <x-alert variant="warning">
                <p class="font-semibold">Tu cuenta puede abrir el punto de venta, pero no cobrar.</p>
                <p class="mt-1 text-xs">Pide al administrador el permiso <strong>«Cobrar y registrar ventas»</strong>.</p>
            </x-alert>
        </div>
    @endif

    {{-- Contenedor dinámico de error --}}
    <div id="pos-error" class="mt-4 hidden rounded-2xl border border-mz-error/30 bg-gradient-to-r from-mz-error/15 via-mz-bg-surface to-mz-bg-surface px-5 py-4 text-sm text-mz-error shadow-xl shadow-black/20" role="alert"></div>

    {{-- Botón para reabrir corte cuando se cerró --}}
    <button id="pos-reopen-day" class="mt-3 hidden inline-flex items-center gap-2 rounded-xl border border-mz-warning/40 bg-mz-warning/10 px-4 py-2.5 text-xs font-bold text-mz-warning hover:bg-mz-warning/20 transition-all shadow-sm" type="button">
        <x-icon name="refresh" class="size-4 shrink-0" />
        Reabrir mi corte del día
    </button>

    {{-- CUERPO PRINCIPAL DEL POS: Layout de 2 columnas --}}
    <div class="mt-4 grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_26rem]">
        <!-- PANEL IZQUIERDO: Búsqueda, Detalle del Carrito y Liquidación -->
        <div class="min-w-0 space-y-4">
            <!-- Barra de Búsqueda y Escáner -->
            <section class="rounded-2xl border border-mz-border bg-mz-bg-surface p-4 sm:p-5 shadow-xl shadow-black/20">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="pos-search" class="block text-xs font-bold uppercase tracking-[0.1em] text-mz-text-muted flex items-center gap-1.5">
                                <x-icon name="search" class="size-3.5 text-mz-accent-primary" />
                                Escanear o buscar producto
                            </label>
                            <span class="text-[11px] text-mz-text-muted hidden sm:inline">Pistola lectora o teclado</span>
                        </div>
                        <div class="relative">
                            <div class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-mz-accent-primary">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5zM6.75 6.75h.008v.008H6.75V6.75zM6.75 16.5h.008v.008H6.75V16.5zM16.5 6.75h.008v.008H16.5V6.75zM13.5 13.5h.008v.008H13.5V13.5zM13.5 16.5h.008v.008H13.5V16.5zM13.5 19.5h.008v.008H13.5V19.5zM16.5 13.5h.008v.008H16.5V13.5zM16.5 16.5h.008v.008H16.5V16.5zM16.5 19.5h.008v.008H16.5V19.5zM19.5 13.5h.008v.008H19.5V13.5zM19.5 16.5h.008v.008H19.5V16.5zM19.5 19.5h.008v.008H19.5V19.5z" />
                                </svg>
                            </div>
                            <input id="pos-search" type="search" autocomplete="off"
                                   placeholder="Escanea código de barras 1 o 2, o busca por nombre o marca..."
                                   class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary/90 pl-11 pr-24 py-3 text-sm text-mz-text-primary placeholder-mz-text-muted focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/25 transition-all shadow-inner">
                            <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 hidden sm:flex items-center gap-1">
                                <kbd class="rounded bg-mz-bg-surface-2 px-2 py-0.5 text-[10px] font-mono text-mz-text-muted border border-mz-border-light">Enter ↵</kbd>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="inline-flex h-11 cursor-pointer select-none items-center gap-2 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/80 px-3.5 text-xs font-semibold text-mz-text-secondary hover:border-mz-accent-primary/40 transition-all">
                            <input id="pos-wholesale" type="checkbox" @disabled(! $permissions['wholesale']) class="size-4 rounded border-mz-border-light text-mz-accent-primary focus:ring-mz-accent-primary bg-mz-bg-surface">
                            <span>Mayoreo</span>
                        </label>

                        <button type="button"
                                onclick="document.getElementById('custom-product-dialog').showModal()"
                                class="inline-flex h-11 items-center justify-center gap-1.5 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/80 hover:bg-mz-bg-surface-2 px-3.5 text-xs font-bold text-mz-text-primary hover:text-white hover:border-mz-accent-primary/40 transition-all whitespace-nowrap active:scale-95">
                            <x-icon name="plus" class="size-3.5 text-mz-accent-primary" />
                            <span>Artículo libre</span>
                        </button>
                    </div>
                </div>

                {{-- Resultados desplegables de búsqueda --}}
                <div id="pos-results" class="pos-scrollbar mt-3 hidden max-h-80 overflow-y-auto rounded-xl border border-mz-border-light bg-mz-bg-surface shadow-2xl shadow-black/80 divide-y divide-mz-border-light/40"></div>
            </section>

            {{-- Banner venta pendiente --}}
            <div id="pending-banner" class="hidden rounded-2xl border border-mz-warning/40 bg-gradient-to-r from-mz-warning/15 via-mz-bg-surface to-mz-bg-surface p-4 text-sm text-mz-warning shadow-lg">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-mz-warning/20 text-mz-warning border border-mz-warning/30">
                            <x-icon name="pause" class="size-4" />
                        </div>
                        <div>
                            <p class="font-bold text-white text-sm">Cobrando ticket pendiente</p>
                            <p class="text-xs text-mz-warning/90 mt-0.5">Los precios y cantidades ya están guardados para esta venta.</p>
                        </div>
                    </div>
                    <button type="button" id="pos-new-sale" class="inline-flex items-center gap-1.5 rounded-xl border border-mz-border-light bg-mz-bg-surface-2 px-3.5 py-2 text-xs font-bold text-white hover:bg-mz-bg-surface hover:border-mz-accent-primary/40 transition-all active:scale-95">
                        <x-icon name="plus" class="size-3.5" />
                        Nueva venta
                    </button>
                </div>
            </div>

            <!-- Tabla de Detalle del Carrito -->
            <section class="rounded-2xl border border-mz-border bg-mz-bg-surface shadow-xl shadow-black/20 overflow-hidden">
                <div class="flex items-center justify-between border-b border-mz-border px-5 py-3.5 bg-mz-bg-surface-2/20">
                    <div class="flex items-center gap-2.5">
                        <div class="flex size-7 items-center justify-center rounded-lg bg-mz-accent-primary/10 text-mz-accent-primary border border-mz-accent-primary/20">
                            <x-icon name="cart" class="size-4" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-mz-text-primary leading-none">Detalle de la venta</h2>
                            <p class="text-[11px] text-mz-text-muted mt-0.5">Artículos agregados al ticket actual</p>
                        </div>
                    </div>
                    <span id="pos-items-count" class="rounded-full bg-mz-accent-primary/10 border border-mz-accent-primary/25 px-3 py-1 text-xs font-bold text-mz-accent-primary tabular-nums">0 artículos</span>
                </div>

                <div class="relative overflow-x-auto pos-scrollbar">
                    <table class="min-w-full divide-y divide-mz-border text-sm">
                        <thead class="bg-mz-bg-surface-2/40">
                            <tr class="text-left text-xs uppercase tracking-wider text-mz-text-muted">
                                <th scope="col" class="px-5 py-3 font-semibold">Producto</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold">Precio unitario</th>
                                <th scope="col" class="px-4 py-3 text-center font-semibold">Cantidad</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold">Importe</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold">Existencia</th>
                                <th scope="col" class="px-4 py-3 text-center"><span class="sr-only">Quitar</span></th>
                            </tr>
                        </thead>
                        <tbody id="pos-cart-body" class="divide-y divide-mz-border-light/40"></tbody>
                    </table>
                </div>

                <!-- Estado vacío del carrito -->
                <div id="pos-empty" class="px-5 py-16 text-center">
                    <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-mz-bg-surface-2/80 border border-mz-border-light text-mz-accent-primary shadow-inner">
                        <x-icon name="cart" class="size-8 text-mz-accent-primary/80" />
                    </div>
                    <p class="mt-4 text-base font-bold text-mz-text-primary">El ticket está vacío</p>
                    <p class="mt-1 text-xs text-mz-text-muted max-w-sm mx-auto">Escanea el código de barras con la lectora o busca un producto en la barra superior para comenzar a cobrar.</p>
                    <div class="mt-4 flex flex-wrap items-center justify-center gap-2 text-[11px] text-mz-text-muted">
                        <span class="inline-flex items-center gap-1 rounded-md bg-mz-bg-surface-2 px-2 py-0.5 border border-mz-border-light font-mono"><kbd>Enter</kbd> Buscar</span>
                        <span class="inline-flex items-center gap-1 rounded-md bg-mz-bg-surface-2 px-2 py-0.5 border border-mz-border-light font-mono"><kbd>F12</kbd> Cobrar</span>
                        <span class="inline-flex items-center gap-1 rounded-md bg-mz-bg-surface-2 px-2 py-0.5 border border-mz-border-light font-mono"><kbd>Esc</kbd> Cerrar diálogo</span>
                    </div>
                </div>
            </section>

            <!-- Totales Financieros y Liquidación -->
            <section class="rounded-2xl border border-mz-border bg-mz-bg-surface p-4 sm:p-5 shadow-xl shadow-black/20">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-mz-border-light/60 bg-mz-bg-surface-2/50 p-4 flex flex-col justify-between shadow-xs">
                        <span class="text-xs font-bold uppercase tracking-wider text-mz-text-muted">Subtotal</span>
                        <p id="pos-subtotal" class="mt-2 font-display text-2xl font-bold tabular-nums text-mz-text-secondary">$0.00</p>
                    </div>
                    <div class="rounded-xl border border-mz-border-light/60 bg-mz-bg-surface-2/50 p-4 flex flex-col justify-between shadow-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-mz-text-muted">IVA incluido</span>
                            <span class="rounded bg-mz-bg-surface px-1.5 py-0.5 text-[10px] font-mono text-mz-text-muted border border-mz-border-light/50">16%</span>
                        </div>
                        <p id="pos-tax" class="mt-2 font-display text-2xl font-bold tabular-nums text-mz-text-secondary">$0.00</p>
                    </div>
                    <div class="relative overflow-hidden rounded-xl border-2 border-mz-accent-primary/40 bg-gradient-to-br from-mz-accent-primary/20 via-mz-accent-primary/10 to-mz-bg-surface p-4 shadow-lg shadow-mz-accent-primary/10 flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-widest text-mz-accent-primary">TOTAL A COBRAR</span>
                            <span class="rounded-full bg-mz-accent-primary/20 border border-mz-accent-primary/30 px-2 py-0.5 font-mono text-[10px] font-bold text-mz-accent-primary">{{ $currency }}</span>
                        </div>
                        <p id="pos-total" class="mt-2 font-display text-3xl sm:text-4xl font-black tabular-nums text-white tracking-tight leading-none">$0.00</p>
                    </div>
                </div>

                <!-- Indicador de última venta realizada -->
                <div id="last-sale-indicator" class="hidden items-center justify-between mt-4 rounded-xl border border-mz-success/30 bg-mz-success/10 px-4 py-3 text-sm text-mz-success shadow-sm">
                    <div class="flex items-center gap-2.5">
                        <div class="flex size-6 items-center justify-center rounded-full bg-mz-success text-white">
                            <x-icon name="check" class="size-4" />
                        </div>
                        <span>Venta guardada con éxito: Folio <span id="last-sale-folio" class="font-bold text-white font-mono"></span></span>
                    </div>
                    <span class="text-xs text-mz-text-muted hidden sm:inline">Listo para imprimir ticket</span>
                </div>

                <!-- Botonera de Cobro y Acciones -->
                <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:items-center sm:justify-end">
                    <button type="button" id="pos-clear" class="inline-flex items-center justify-center gap-2 rounded-xl border border-transparent px-3.5 py-2.5 text-xs font-semibold text-mz-text-muted hover:border-mz-error/40 hover:bg-mz-error/10 hover:text-mz-error transition-all active:scale-95">
                        <x-icon name="trash" class="size-4" />
                        Eliminar venta
                    </button>
                    <button type="button" id="pos-pending" @disabled(! $permissions['sell']) class="inline-flex items-center justify-center gap-2 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/80 hover:bg-mz-bg-surface-2 px-4 py-2.5 text-xs font-bold text-mz-text-secondary hover:text-white transition-all disabled:opacity-50 disabled:cursor-not-allowed active:scale-95">
                        <x-icon name="pause" class="size-4 text-mz-warning" />
                        <span>Dejar pendiente</span>
                        <kbd class="hidden md:inline rounded bg-black/40 px-1.5 py-0.2 text-[10px] font-mono text-mz-text-muted">F6</kbd>
                    </button>
                    <button type="button" id="pos-print-ticket" disabled class="inline-flex items-center justify-center gap-2 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/80 hover:bg-mz-bg-surface-2 px-4 py-2.5 text-xs font-bold text-mz-text-secondary hover:text-white transition-all disabled:opacity-50 disabled:cursor-not-allowed active:scale-95">
                        <x-icon name="printer" class="size-4 text-mz-accent-primary" />
                        Imprimir ticket
                    </button>
                    <button type="button" id="pos-charge" @disabled(! $permissions['sell']) class="inline-flex items-center justify-center gap-2.5 rounded-xl bg-gradient-to-r from-mz-accent-primary to-mz-accent-hover hover:brightness-110 px-7 py-3 text-sm font-extrabold text-white shadow-xl shadow-mz-accent-primary/25 transition-all transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                        <x-icon name="banknotes" class="size-5" />
                        <span>Cobrar venta</span>
                        <kbd class="rounded bg-black/30 px-1.5 py-0.5 text-xs font-mono font-bold text-white/90">F12</kbd>
                    </button>
                </div>
            </section>
        </div>

        <!-- PANEL DERECHO: Resumen del turno, accesos directos y atajos -->
        <aside class="hidden xl:block space-y-4">
            <!-- Resumen del día -->
            <section class="rounded-2xl border border-mz-border bg-mz-bg-surface p-5 shadow-xl shadow-black/20">
                <div class="flex items-center justify-between pb-3 border-b border-mz-border">
                    <div class="flex items-center gap-2">
                        <x-icon name="chart" class="size-4 text-mz-accent-primary" />
                        <h2 class="text-xs font-bold uppercase tracking-wider text-mz-text-primary">Mi turno de hoy</h2>
                    </div>
                    <span class="size-2 rounded-full bg-mz-success animate-pulse" title="Sesión activa"></span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-mz-border-light/50 bg-mz-bg-surface-2/40 p-3">
                        <p class="text-[11px] font-medium text-mz-text-muted uppercase">Tickets</p>
                        <p class="mt-1 font-display text-2xl font-extrabold tabular-nums text-white">{{ $today['tickets'] }}</p>
                    </div>
                    <div class="rounded-xl border border-mz-border-light/50 bg-mz-bg-surface-2/40 p-3">
                        <p class="text-[11px] font-medium text-mz-text-muted uppercase">Vendido</p>
                        <p class="mt-1 font-display text-xl font-extrabold tabular-nums text-mz-accent-primary">${{ number_format((float) $today['sales'], 2) }}</p>
                    </div>
                </div>

                <!-- Accesos directos secundarios -->
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <a href="{{ route('pos.sales.pending') }}" class="group flex flex-col items-center justify-center p-3 rounded-xl border border-mz-border-light/60 bg-mz-bg-surface-2/40 hover:bg-mz-bg-surface-2 hover:border-mz-accent-primary/40 transition-all text-center">
                        <x-icon name="pause" class="size-4 text-mz-warning group-hover:scale-110 transition-transform" />
                        <span class="mt-1 text-xs font-semibold text-mz-text-secondary group-hover:text-white">Pendientes</span>
                    </a>
                    @if ($permissions['returns_request'])
                        <a href="{{ route('pos.returns.index') }}" class="group flex flex-col items-center justify-center p-3 rounded-xl border border-mz-border-light/60 bg-mz-bg-surface-2/40 hover:bg-mz-bg-surface-2 hover:border-mz-accent-primary/40 transition-all text-center">
                            <x-icon name="arrow-uturn-left" class="size-4 text-mz-accent-primary group-hover:scale-110 transition-transform" />
                            <span class="mt-1 text-xs font-semibold text-mz-text-secondary group-hover:text-white">Devoluciones</span>
                        </a>
                    @endif
                    @if ($permissions['cash_view'])
                        <a href="{{ route('pos.cash.index') }}" class="group flex flex-col items-center justify-center p-3 rounded-xl border border-mz-border-light/60 bg-mz-bg-surface-2/40 hover:bg-mz-bg-surface-2 hover:border-mz-accent-primary/40 transition-all text-center">
                            <x-icon name="banknotes" class="size-4 text-mz-success group-hover:scale-110 transition-transform" />
                            <span class="mt-1 text-xs font-semibold text-mz-text-secondary group-hover:text-white">Caja</span>
                        </a>
                    @endif
                    @if ($permissions['cut_own'])
                        <a href="{{ route('pos.cuts.mine') }}" class="group flex flex-col items-center justify-center p-3 rounded-xl border border-mz-border-light/60 bg-mz-bg-surface-2/40 hover:bg-mz-bg-surface-2 hover:border-mz-accent-primary/40 transition-all text-center">
                            <x-icon name="report" class="size-4 text-mz-accent-primary group-hover:scale-110 transition-transform" />
                            <span class="mt-1 text-xs font-semibold text-mz-text-secondary group-hover:text-white">Mi corte</span>
                        </a>
                    @endif
                </div>
            </section>

            <!-- Atajos de teclado -->
            <section class="rounded-2xl border border-mz-border bg-mz-bg-surface p-5 shadow-xl shadow-black/20">
                <div class="flex items-center gap-2 pb-3 border-b border-mz-border">
                    <svg class="size-4 text-mz-accent-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h12A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6zM6.75 7.5h.008v.008H6.75V7.5zm3.75 0h.008v.008H10.5V7.5zm3.75 0h.008v.008H14.25V7.5zm3.75 0h.008v.008H18V7.5zM6.75 10.5h.008v.008H6.75V10.5zm3.75 0h.008v.008H10.5V10.5zm3.75 0h.008v.008H14.25V10.5zm3.75 0h.008v.008H18V10.5zM8.25 14.25h7.5" />
                    </svg>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-mz-text-primary">Atajos de teclado</h2>
                </div>
                <ul class="mt-3.5 space-y-2.5 text-xs">
                    <li class="flex items-center justify-between text-mz-text-secondary">
                        <span>Buscar o escanear</span>
                        <kbd class="rounded-md border border-mz-border-light bg-mz-bg-surface-2 px-2 py-0.5 font-mono text-[11px] font-semibold text-white shadow-xs">Enter</kbd>
                    </li>
                    <li class="flex items-center justify-between text-mz-text-secondary">
                        <span class="font-bold text-mz-accent-primary">Cobrar ticket</span>
                        <kbd class="rounded-md border border-mz-accent-primary/40 bg-mz-accent-primary/20 px-2 py-0.5 font-mono text-[11px] font-bold text-mz-accent-primary shadow-xs">F12</kbd>
                    </li>
                    <li class="flex items-center justify-between text-mz-text-secondary">
                        <span>Cerrar diálogo</span>
                        <kbd class="rounded-md border border-mz-border-light bg-mz-bg-surface-2 px-2 py-0.5 font-mono text-[11px] font-semibold text-white shadow-xs">Esc</kbd>
                    </li>
                    <li class="flex items-center justify-between text-mz-text-secondary">
                        <span>Dejar pendiente</span>
                        <kbd class="rounded-md border border-mz-border-light bg-mz-bg-surface-2 px-2 py-0.5 font-mono text-[11px] font-semibold text-white shadow-xs">F6</kbd>
                    </li>
                    <li class="flex items-center justify-between text-mz-text-secondary">
                        <span>Entrada de caja</span>
                        <kbd class="rounded-md border border-mz-border-light bg-mz-bg-surface-2 px-2 py-0.5 font-mono text-[11px] font-semibold text-white shadow-xs">F7</kbd>
                    </li>
                    <li class="flex items-center justify-between text-mz-text-secondary">
                        <span>Salida de caja</span>
                        <kbd class="rounded-md border border-mz-border-light bg-mz-bg-surface-2 px-2 py-0.5 font-mono text-[11px] font-semibold text-white shadow-xs">F8</kbd>
                    </li>
                </ul>
            </section>
        </aside>
    </div>

    <!-- MODAL DE COBRO (Payment Dialog) -->
    <dialog id="payment-dialog" class="w-full max-w-lg rounded-2xl border border-mz-border-light bg-mz-bg-surface shadow-2xl shadow-black/95 p-0 overflow-hidden" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); box-sizing: border-box;">
        <!-- Header del Modal -->
        <div class="border-b border-mz-border-light px-6 py-4.5 bg-mz-bg-surface-2/40 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-xl bg-mz-accent-primary/15 text-mz-accent-primary border border-mz-accent-primary/25">
                    <x-icon name="banknotes" class="size-5" />
                </div>
                <div>
                    <h2 class="text-base font-bold text-white">Cobrar venta</h2>
                    <p class="text-xs text-mz-text-muted">Selecciona método de pago e ingresa el importe</p>
                </div>
            </div>
            <button type="button" data-close-dialog="payment-dialog" class="rounded-xl p-1.5 text-mz-text-muted hover:bg-mz-bg-surface-2 hover:text-white transition-colors" aria-label="Cerrar">
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
                <p id="payment-total" class="mt-1 font-display text-4xl font-black tabular-nums text-white">$0.00</p>
            </div>

            <!-- Pago con / Monto recibido -->
            <div id="payment-amount-group" class="space-y-2">
                <label for="payment-amount" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Pago con (Efectivo recibido)</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-bold text-mz-text-muted">$</span>
                    <input id="payment-amount" type="number" step="0.01" min="0" inputmode="decimal"
                           class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary/90 pl-9 pr-4 py-3 text-right text-3xl font-extrabold tabular-nums text-white placeholder-mz-text-muted focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/25 transition-all shadow-inner"
                           placeholder="0.00">
                </div>

                <!-- Botones rápidos de denominaciones -->
                <div class="flex flex-wrap gap-1.5 pt-1">
                    <button type="button" data-amount-exact class="inline-flex items-center rounded-lg border border-mz-accent-primary/40 bg-mz-accent-primary/15 hover:bg-mz-accent-primary hover:text-white px-3 py-1.5 text-xs font-bold text-mz-accent-primary transition-all active:scale-95">
                        Exacto
                    </button>
                    <button type="button" data-amount="50" class="inline-flex items-center rounded-lg border border-mz-border-light bg-mz-bg-surface-2 hover:border-mz-accent-primary/50 hover:bg-mz-bg-surface hover:text-white px-3 py-1.5 text-xs font-mono font-semibold text-mz-text-secondary transition-all active:scale-95">
                        $50
                    </button>
                    <button type="button" data-amount="100" class="inline-flex items-center rounded-lg border border-mz-border-light bg-mz-bg-surface-2 hover:border-mz-accent-primary/50 hover:bg-mz-bg-surface hover:text-white px-3 py-1.5 text-xs font-mono font-semibold text-mz-text-secondary transition-all active:scale-95">
                        $100
                    </button>
                    <button type="button" data-amount="200" class="inline-flex items-center rounded-lg border border-mz-border-light bg-mz-bg-surface-2 hover:border-mz-accent-primary/50 hover:bg-mz-bg-surface hover:text-white px-3 py-1.5 text-xs font-mono font-semibold text-mz-text-secondary transition-all active:scale-95">
                        $200
                    </button>
                    <button type="button" data-amount="500" class="inline-flex items-center rounded-lg border border-mz-border-light bg-mz-bg-surface-2 hover:border-mz-accent-primary/50 hover:bg-mz-bg-surface hover:text-white px-3 py-1.5 text-xs font-mono font-semibold text-mz-text-secondary transition-all active:scale-95">
                        $500
                    </button>
                    <button type="button" data-amount="1000" class="inline-flex items-center rounded-lg border border-mz-border-light bg-mz-bg-surface-2 hover:border-mz-accent-primary/50 hover:bg-mz-bg-surface hover:text-white px-3 py-1.5 text-xs font-mono font-semibold text-mz-text-secondary transition-all active:scale-95">
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
            <button type="button" data-close-dialog="payment-dialog" class="inline-flex items-center justify-center rounded-xl border border-mz-border-light px-4 py-2.5 text-xs font-bold text-mz-text-secondary hover:bg-mz-bg-surface-2 hover:text-white transition-all active:scale-95">
                Cancelar
            </button>
            <button type="button" id="payment-confirm" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-mz-success to-emerald-600 hover:brightness-110 px-6 py-2.5 text-xs font-extrabold text-white shadow-lg shadow-mz-success/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed active:scale-95">
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
                            <h2 id="cash-title" class="text-base font-bold text-white">Entrada de efectivo</h2>
                            <p class="text-xs text-mz-text-muted">Quedará registrado con tu usuario y la fecha actual.</p>
                        </div>
                    </div>
                    <button type="button" data-close-dialog="cash-dialog" class="rounded-xl p-1.5 text-mz-text-muted hover:bg-mz-bg-surface-2 hover:text-white transition-colors" aria-label="Cerrar">
                        <x-icon name="x" class="size-5" />
                    </button>
                </div>

                <div class="space-y-4 p-6">
                    <div>
                        <label for="cash-amount" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Cantidad ({{ $currency }})</label>
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-lg font-bold text-mz-text-muted">$</span>
                            <input id="cash-amount" name="amount" type="text" inputmode="decimal" step="0.01" min="0.01" required
                                   class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary/90 pl-8 pr-4 py-2.5 text-right text-xl font-bold tabular-nums text-white placeholder-mz-text-muted focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/25 transition-all"
                                   placeholder="0.00">
                        </div>
                    </div>

                    <div>
                        <label for="cash-concept" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Concepto / Motivo</label>
                        <textarea id="cash-concept" name="concept" rows="3" maxlength="255" required
                                  class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary/90 px-3.5 py-2.5 text-sm text-white placeholder-mz-text-muted focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/25 transition-all"
                                  placeholder="Describe brevemente la razón del movimiento de efectivo..."></textarea>
                    </div>

                    <p id="cash-error" class="hidden rounded-xl bg-mz-error/15 border border-mz-error/30 p-3 text-xs font-bold text-mz-error text-center" role="alert"></p>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-mz-border-light/70 bg-mz-bg-surface-2/30 px-6 py-4 sm:flex-row sm:justify-end">
                    <button type="button" data-close-dialog="cash-dialog" class="inline-flex items-center justify-center rounded-xl border border-mz-border-light px-4 py-2.5 text-xs font-bold text-mz-text-secondary hover:bg-mz-bg-surface-2 hover:text-white transition-all active:scale-95">
                        Cancelar
                    </button>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-mz-accent-primary hover:bg-mz-accent-hover text-white px-5 py-2.5 text-xs font-bold shadow-md shadow-mz-accent-primary/20 transition-all active:scale-95">
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
                            <h2 class="text-base font-bold text-white">Iniciar día y turno</h2>
                            <p class="text-xs text-mz-text-muted">Apertura con fondo de caja inicial</p>
                        </div>
                    </div>
                    <button type="button" class="rounded-xl p-1.5 text-mz-text-muted hover:bg-mz-bg-surface-2 hover:text-white transition-colors" onclick="document.getElementById('start-day-dialog').close()" aria-label="Cerrar">
                        <x-icon name="x" class="size-5" />
                    </button>
                </div>

                <div class="space-y-4 p-6">
                    <div>
                        <label for="start-day-amount" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Efectivo inicial disponible ({{ $currency }})</label>
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-xl font-bold text-mz-text-muted">$</span>
                            <input id="start-day-amount" name="amount" type="text" inputmode="decimal" step="0.01" min="0.01" required
                                   class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary/90 pl-8 pr-4 py-3 text-right text-2xl font-bold tabular-nums text-white placeholder-mz-text-muted focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/25 transition-all shadow-inner"
                                   placeholder="0.00">
                        </div>
                        <p class="mt-1.5 text-xs text-mz-text-muted">Ingresa las monedas y billetes disponibles en tu gaveta al comenzar.</p>
                    </div>

                    <p id="start-day-error" class="hidden rounded-xl bg-mz-error/15 border border-mz-error/30 p-3 text-xs font-bold text-mz-error text-center" role="alert"></p>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-mz-border-light/70 bg-mz-bg-surface-2/30 px-6 py-4 sm:flex-row sm:justify-end">
                    <button type="button" class="inline-flex items-center justify-center rounded-xl border border-mz-border-light px-4 py-2.5 text-xs font-bold text-mz-text-secondary hover:bg-mz-bg-surface-2 hover:text-white transition-all active:scale-95" onclick="document.getElementById('start-day-dialog').close()">
                        Cancelar
                    </button>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-mz-accent-primary to-mz-accent-hover hover:brightness-110 text-white px-5 py-2.5 text-xs font-bold shadow-md shadow-mz-accent-primary/20 transition-all active:scale-95">
                        <x-icon name="check" class="size-4" />
                        Guardar e iniciar día
                    </button>
                </div>
            </form>
        </dialog>
    @endif

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
                        <h2 class="text-base font-bold text-white">Artículo libre</h2>
                        <p class="text-xs text-mz-text-muted">Cobro directo que no descuenta inventario</p>
                    </div>
                </div>
                <button type="button" class="rounded-xl p-1.5 text-mz-text-muted hover:bg-mz-bg-surface-2 hover:text-white transition-colors" onclick="document.getElementById('custom-product-dialog').close()" aria-label="Cerrar">
                    <x-icon name="x" class="size-5" />
                </button>
            </div>

            <div class="space-y-4 p-6">
                <div>
                    <label for="custom-product-name" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Nombre o descripción</label>
                    <input id="custom-product-name" name="name" type="text" maxlength="255" required
                           class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary/90 px-3.5 py-2.5 text-sm text-white placeholder-mz-text-muted focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/25 transition-all mt-1.5"
                           placeholder="Ej. Servicio de ajuste, accesorio genérico...">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="custom-product-price" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Precio ({{ $currency }})</label>
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-mz-text-muted">$</span>
                            <input id="custom-product-price" name="price" type="text" inputmode="decimal" step="0.01" min="0.01" required
                                   class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary/90 pl-7 pr-3 py-2.5 text-right text-base font-bold tabular-nums text-white placeholder-mz-text-muted focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/25 transition-all"
                                   placeholder="0.00">
                        </div>
                    </div>

                    <div>
                        <label for="custom-product-quantity" class="block text-xs font-bold uppercase tracking-wider text-mz-text-muted">Cantidad</label>
                        <input id="custom-product-quantity" name="quantity" type="number" step="1" min="1" value="1" required
                               class="block w-full rounded-xl border border-mz-border-light bg-mz-bg-primary/90 px-3 py-2.5 text-center text-base font-bold tabular-nums text-white focus:border-mz-accent-primary focus:bg-mz-bg-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/25 transition-all mt-1.5">
                    </div>
                </div>

                <p id="custom-product-error" class="hidden rounded-xl bg-mz-error/15 border border-mz-error/30 p-3 text-xs font-bold text-mz-error text-center" role="alert"></p>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-mz-border-light/70 bg-mz-bg-surface-2/30 px-6 py-4 sm:flex-row sm:justify-end">
                <button type="button" class="inline-flex items-center justify-center rounded-xl border border-mz-border-light px-4 py-2.5 text-xs font-bold text-mz-text-secondary hover:bg-mz-bg-surface-2 hover:text-white transition-all active:scale-95" onclick="document.getElementById('custom-product-dialog').close()">
                    Cancelar
                </button>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-mz-accent-primary hover:bg-mz-accent-hover text-white px-5 py-2.5 text-xs font-bold shadow-md shadow-mz-accent-primary/20 transition-all active:scale-95">
                    <x-icon name="plus" class="size-4" />
                    Agregar al ticket
                </button>
            </div>
        </form>
    </dialog>

    {{-- Contenedor de impresión de tickets térmicos --}}
    <div id="ticket-print-container" class="hidden"></div>

    <style>
        #ticket-print-container {
            font-family: "Courier New", ui-monospace, monospace;
            font-size: 11px;
            line-height: 1.45;
            color: #0f172a;
        }
        #ticket-print-container .ticket-sheet {
            width: var(--ticket-width, 80mm);
            max-width: 100%;
            padding: 10mm 4mm;
            background: #fff;
        }
        #ticket-print-container .center { text-align: center; }
        #ticket-print-container .business { font-size: 15px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
        #ticket-print-container .muted { color: #475569; font-size: 10px; }
        #ticket-print-container .divider { border: 0; border-top: 1px dashed #94a3b8; margin: 8px 0; }
        #ticket-print-container table { width: 100%; border-collapse: collapse; }
        #ticket-print-container th { text-align: left; font-size: 10px; text-transform: uppercase; color: #475569; padding-bottom: 4px; }
        #ticket-print-container th.num, #ticket-print-container td.num { text-align: right; white-space: nowrap; padding-left: 2mm; }
        #ticket-print-container td { padding: 3px 0; vertical-align: top; }
        #ticket-print-container .item-name { font-weight: 700; }
        #ticket-print-container p { margin: 4px 0; overflow-wrap: anywhere; }
        #ticket-print-container table { table-layout: auto; }
        #ticket-print-container th:first-child { width: 100%; }
        #ticket-print-container td:first-child { overflow-wrap: anywhere; }
        #ticket-print-container td.num { font-size: 10px; }
        #ticket-print-container .totals td { padding: 2px 0; }
        #ticket-print-container .total-row td { padding-top: 6px; font-size: 14px; font-weight: 700; }
        #ticket-print-container .folio { font-size: 12px; font-weight: 700; letter-spacing: 0.08em; }
        @media print {
            body * { visibility: hidden !important; }
            #ticket-print-container, #ticket-print-container * { visibility: visible !important; }
            #ticket-print-container { position: absolute !important; top: 0; left: 0; width: 100%; margin: 0; padding: 0; background: #fff; }
            #ticket-print-container .ticket-sheet { width: calc(var(--ticket-width, 80mm) - 8mm); max-width: 100%; margin: 0 auto; padding: 0; }
            body { padding: 0; background: #fff; }
            @page { size: auto; margin: 4mm; }
        }
    </style>

    <script type="application/json" id="pos-config">
        {!! json_encode([
            'routes' => [
                'search' => route('pos.products.search'),
                'frequent' => route('pos.products.frequent'),
                'store' => route('pos.sales.store'),
                'charge' => route('pos.sales.charge', ['sale' => '__SALE__']),
                'show' => route('pos.sales.show', ['sale' => '__SALE__']),
                'cash' => $permissions['cash'] ? route('pos.cash.index') : null,
                'pending' => route('pos.sales.pending'),
                'ticket' => route('pos.sales.ticket', ['sale' => '__SALE__']),
                'ticketData' => route('pos.sales.ticketData', ['sale' => '__SALE__']),
                'reopen_my_day' => route('pos.cuts.reopen-my-day'),
            ],
            'permissions' => $permissions,
            'currency' => $currency,
            'csrf' => csrf_token(),
            'storeEnabled' => (bool) ($settings['enabled'] ?? true),
        ], JSON_UNESCAPED_UNICODE) !!}
    </script>
@endsection
