@extends('layouts.admin')

@section('title', 'Punto de venta')
@section('subtitle', 'Cobra, imprime tickets y controla la caja del día')

@section('content')
    @php
        $user = auth()->user();
        $currency = $settings['currency'] ?? 'MXN';
        $cashCategories = \App\Models\CashMovement::manualCategories();
    @endphp

    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex-1 min-w-0">
            <p class="text-sm text-mz-text-secondary">
                Atiende en <span class="font-semibold text-mz-text-primary">{{ $settings['business']['name'] }}</span>
            </p>
            <p class="mt-1 text-xs text-mz-text-secondary">
                Usuario: <span class="font-medium text-mz-text-primary">{{ $user?->name }}</span>
                · {{ now()->format('d/m/Y H:i') }}
                · Moneda: {{ $currency }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($permissions['cash'])
                <button type="button" data-cash-open="in" class="x-button x-button-secondary size-sm">
                    <x-icon name="arrow-down" class="size-4" />
                    Entrada de efectivo
                </button>
                <button type="button" data-cash-open="out" class="x-button x-button-secondary size-sm">
                    <x-icon name="arrow-up" class="size-4" />
                    Salida de efectivo
                </button>
            @endif

            <a href="{{ route('pos.sales.day') }}" class="x-button x-button-secondary size-sm">
                <x-icon name="chart" class="size-4" />
                Ventas del día
            </a>
            @if ($permissions['cut'])
                <a href="{{ route('pos.cuts.index') }}" class="x-button x-button-primary size-sm">
                    <x-icon name="report" class="size-4" />
                    Corte del día
                </a>
            @endif

            @if ($settings['enabled'] === false)
                <span class="inline-flex items-center gap-1.5 rounded-full border border-mz-error/20 bg-mz-error/10 px-3 py-1 text-xs font-medium text-mz-error ring-1 ring-inset ring-mz-error/20">
                    <x-icon name="alert" class="size-3.5" />
                    POS desactivado
                </span>
            @endif
        </div>
    </div>

    @if (! $permissions['sell'])
        <x-alert variant="warning">
            <p class="font-semibold">Tu cuenta puede abrir el punto de venta, pero no cobrar.</p>
            <p class="mt-1 text-xs">Pide al administrador el permiso <strong>«Cobrar y registrar ventas»</strong>.</p>
        </x-alert>
    @endif

    <div id="pos-error" class="mt-4 hidden rounded-md border border-mz-error/20 bg-mz-error/10 px-4 py-3 text-sm text-mz-error" role="alert"></div>

    <button id="pos-reopen-day" class="mt-3 hidden x-button x-button-outline size-sm" type="button">
        <x-icon name="refresh" class="size-4 shrink-0" />
        Reabrir mi corte del día
    </button>

    <div class="mt-4 grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_26rem]">
        <!-- PANEL IZQUIERDO: Búsqueda y productos frecuentes -->
        <div class="space-y-4">
            <section class="mz-card mz-card-padded">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1 min-w-0">
                        <label for="pos-search" class="block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Buscar o escanear producto</label>
                        <div class="relative mt-1.5">
                            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-mz-text-muted" />
                            <input id="pos-search" type="search" autocomplete="off" placeholder="Escanea el código de barras o escribe nombre, SKU o marca" class="mz-input pl-9">
                        </div>
                    </div>
                    <label class="inline-flex h-11 cursor-pointer items-center gap-2 rounded-md border border-mz-border-light bg-mz-bg-surface-2 px-3 text-sm font-medium text-mz-text-secondary">
                        <input id="pos-wholesale" type="checkbox" @disabled(! $permissions['wholesale']) class="mz-checkbox">
                        Mayoreo
                    </label>
                </div>

                <div id="pos-results" class="mt-3 hidden max-h-80 overflow-y-auto rounded-md border border-mz-border bg-mz-bg-surface shadow-lg"></div>

                @if ($frequentProducts !== [])
                    <div class="mt-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Productos frecuentes</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($frequentProducts as $product)
                                <button type="button"
                                        data-add-product='@json($product)'
                                        class="x-button x-button-ghost size-sm w-full text-left">
                                    <x-icon name="plus" class="size-3.5 shrink-0 text-mz-accent-primary" />
                                    <span class="truncate">{{ $product['name'] }}</span>
                                    <span class="shrink-0 tabular-nums text-mz-text-muted">${{ number_format($product['price'], 2) }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            <div id="pending-banner" class="hidden rounded-md border border-mz-warning/30 bg-mz-warning/10 px-4 py-3 text-sm text-mz-warning">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="flex items-center gap-2">
                        <x-icon name="pause" class="size-4" />
                        Estás cobrando una venta pendiente. Los precios y cantidades ya están guardados.
                    </p>
                    <button type="button" id="pos-new-sale" class="x-button x-button-secondary size-sm">
                        Nueva venta
                    </button>
                </div>
            </div>

            <section class="mz-card mz-card-padded overflow-hidden">
                <div class="flex items-center justify-between border-b border-mz-border px-4 py-3">
                    <h2 class="text-sm font-semibold text-mz-text-primary">Detalle de la venta</h2>
                    <span id="pos-items-count" class="rounded-full bg-mz-bg-surface-2 px-2.5 py-0.5 text-xs font-medium text-mz-text-secondary">0 artículos</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-mz-border text-sm">
                        <thead class="bg-mz-bg-surface">
                            <tr class="text-left text-xs uppercase tracking-wide text-mz-text-muted">
                                <th scope="col" class="px-4 py-3 font-medium">Producto</th>
                                <th scope="col" class="px-4 py-3 text-right font-medium">Precio</th>
                                <th scope="col" class="px-4 py-3 text-center font-medium">Cantidad</th>
                                <th scope="col" class="px-4 py-3 text-right font-medium">Importe</th>
                                <th scope="col" class="px-4 py-3 text-right font-medium">Existencia</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Quitar</span></th>
                            </tr>
                        </thead>
                        <tbody id="pos-cart-body" class="divide-y divide-mz-border-light"></tbody>
                    </table>
                </div>

                <div id="pos-empty" class="px-4 py-14 text-center">
                    <div class="mz-empty-state-icon">
                        <x-icon name="cart" class="size-6" />
                    </div>
                    <p class="mt-3 text-sm font-medium text-mz-text-secondary">La venta está vacía</p>
                    <p class="mt-1 text-xs text-mz-text-muted">Busca un producto o escanea su código de barras para comenzar.</p>
                </div>
            </section>

            <section class="mz-card mz-card-padded">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-lg bg-mz-bg-surface-2 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-mz-text-muted">Subtotal</p>
                        <p id="pos-subtotal" class="mt-1 font-display text-xl font-semibold tabular-nums text-mz-text-secondary">$0.00</p>
                    </div>
                    <div class="rounded-lg bg-mz-bg-surface-2 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-mz-text-muted">IVA incluido</p>
                        <p id="pos-tax" class="mt-1 font-display text-xl font-semibold tabular-nums text-mz-text-secondary">$0.00</p>
                    </div>
                    <div class="rounded-lg bg-mz-accent-primary/10 px-4 py-3 ring-1 ring-inset ring-mz-accent-primary/20">
                        <p class="text-xs font-semibold uppercase tracking-wide text-mz-accent-primary/70">Total</p>
                        <p id="pos-total" class="mt-1 font-display text-2xl font-bold tabular-nums text-mz-accent-primary">$0.00</p>
                    </div>
                </div>

                <div id="last-sale-indicator" class="hidden flex items-center gap-2 mb-3 rounded-lg border border-mz-accent-primary/20 bg-mz-accent-primary/5 px-3 py-2 text-sm text-mz-accent-primary shadow-sm">
                    <x-icon name="check" class="size-4 shrink-0" />
                    <span>Venta guardada: <span id="last-sale-folio" class="font-semibold"></span></span>
                </div>

                <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <button type="button" id="pos-clear" class="x-button x-button-ghost size-sm">
                        <x-icon name="trash" class="size-4" />
                        Eliminar venta
                    </button>
                    <button type="button" id="pos-pending" @disabled(! $permissions['sell']) class="x-button x-button-secondary size-sm" disabled:cursor-not-allowed disabled:opacity-50>
                        <x-icon name="pause" class="size-4" />
                        Dejar pendiente
                    </button>
                    <button type="button" id="pos-print-ticket" disabled class="x-button x-button-secondary size-sm" disabled>
                        <x-icon name="printer" class="size-4" />
                        Imprimir ticket
                    </button>
                    <button type="button" id="pos-charge" @disabled(! $permissions['sell']) class="x-button x-button-primary size-sm" disabled:cursor-not-allowed disabled:opacity-50>
                        <x-icon name="banknotes" class="size-4" />
                        Cobrar
                    </button>
                </div>
            </section>
        </div>

        <!-- PANEL DERECHO: Resumen rápido y atajos (opcional, si se quiere más info) -->
        <aside class="hidden xl:block space-y-4">
            <section class="mz-card mz-card-padded">
                <h2 class="text-sm font-semibold text-mz-text-primary">Resumen del día</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-mz-text-muted">Tickets cobrados</dt>
                        <dd class="font-semibold tabular-nums text-mz-text-primary">{{ $today['tickets'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-mz-text-muted">Ventas propias</dt>
                        <dd class="font-semibold tabular-nums text-mz-text-primary">${{ number_format((float) $today['sales'], 2) }}</dd>
                    </div>
                </dl>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <a href="{{ route('pos.sales.pending') }}" class="x-button x-button-secondary size-sm xs:w-full">
                        <x-icon name="pause" class="size-4" />
                        Pendientes
                    </a>
                    @if ($permissions['returns_request'])
                        <a href="{{ route('pos.returns.index') }}" class="x-button x-button-secondary size-sm xs:w-full">
                            <x-icon name="arrow-uturn-left" class="size-4" />
                            Devoluciones
                        </a>
                    @endif
                    @if ($permissions['cash'])
                        <a href="{{ route('pos.cash.index') }}" class="x-button x-button-secondary size-sm xs:w-full">
                            <x-icon name="banknotes" class="size-4" />
                            Movimientos
                        </a>
                    @endif
                    @if ($permissions['cut_own'])
                        <a href="{{ route('pos.cuts.mine') }}" class="x-button x-button-secondary size-sm xs:w-full">
                            <x-icon name="report" class="size-4" />
                            Mi corte
                        </a>
                    @endif
                </div>
            </section>

            <section class="mz-card mz-card-padded">
                <h2 class="text-sm font-semibold text-mz-text-primary">Atajos de teclado</h2>
                <ul class="mt-3 space-y-2 text-xs text-mz-text-muted">
                    <li class="flex justify-between gap-3"><span>Buscar o escanear</span><kbd class="rounded border border-mz-border-light bg-mz-bg-surface-2 px-1.5 py-0.5 font-mono text-[10px]">Enter</kbd></li>
                    <li class="flex justify-between gap-3"><span>Cobrar</span><kbd class="rounded border border-mz-border-light bg-mz-bg-surface-2 px-1.5 py-0.5 font-mono text-[10px]">F12</kbd></li>
                    <li class="flex justify-between gap-3"><span>Nueva venta</span><kbd class="rounded border border-mz-border-light bg-mz-bg-surface-2 px-1.5 py-0.5 font-mono text-[10px]">Esc</kbd></li>
                    <li class="flex justify-between gap-3"><span>Dejar pendiente</span><kbd class="rounded border border-mz-border-light bg-mz-bg-surface-2 px-1.5 py-0.5 font-mono text-[10px]">F6</kbd></li>
                    <li class="flex justify-between gap-3"><span>Entrada de caja</span><kbd class="rounded border border-mz-border-light bg-mz-bg-surface-2 px-1.5 py-0.5 font-mono text-[10px]">F7</kbd></li>
                    <li class="flex justify-between gap-3"><span>Salida de caja</span><kbd class="rounded border border-mz-border-light bg-mz-bg-surface-2 px-1.5 py-0.5 font-mono text-[10px]">F8</kbd></li>
                </ul>
            </section>
        </aside>
    </div>

    <!-- Modal de cobro -->
    <dialog id="payment-dialog" class="w-full max-w-md rounded-lg border border-mz-border bg-mz-bg-surface shadow-lg p-0" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); box-sizing: border-box;">
        <div class="border-b border-mz-border-light px-5 py-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-mz-text-primary">Cobrar venta</h2>
                <button type="button" data-close-dialog="payment-dialog" class="rounded-md p-1 text-mz-text-muted hover:bg-mz-bg-surface-2 hover:text-mz-text-primary" aria-label="Cerrar">
                    <x-icon name="x" class="size-5" />
                </button>
            </div>
            <div>
                <label for="payment-method" class="block text-sm font-medium text-mz-text-secondary">Método de pago</label>
                <select id="payment-method" name="payment_method" class="mz-select mt-1.5 h-11">
                    <option value="cash">Efectivo</option>
                    <option value="card">Tarjeta</option>
                    <option value="transfer">Transferencia</option>
                    <option value="mixed">Mixto (efectivo + otro)</option>
                </select>
            </div>
        </div>

        <div class="space-y-4 px-5 py-4">
            <div class="rounded-lg bg-mz-accent-primary/10 px-4 py-3 ring-1 ring-inset ring-mz-accent-primary/20">
                <p class="text-xs font-semibold uppercase tracking-wide text-mz-accent-primary/70">Total a cobrar</p>
                <p id="payment-total" class="mt-1 font-display text-3xl font-bold tabular-nums text-mz-accent-primary">$0.00</p>
            </div>

            <div id="payment-amount-group">
                <label for="payment-amount" class="block text-sm font-medium text-mz-text-secondary">Pago con</label>
                <input id="payment-amount" type="number" step="0.01" min="0" inputmode="decimal" class="mz-input mt-1.5 h-12 text-right text-2xl font-semibold tabular-nums text-mz-text-primary" placeholder="0.00">
                <div class="mt-2 flex flex-wrap gap-2">
                    <button type="button" data-amount-exact class="x-button x-button-ghost size-sm">
                        Exacto
                    </button>
                    <button type="button" data-amount="100" class="x-button x-button-ghost size-sm">
                        $100
                    </button>
                    <button type="button" data-amount="200" class="x-button x-button-ghost size-sm">
                        $200
                    </button>
                    <button type="button" data-amount="500" class="x-button x-button-ghost size-sm">
                        $500
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between rounded-lg bg-mz-bg-surface-2 px-4 py-3">
                <span class="text-sm text-mz-text-secondary">Cambio</span>
                <span id="payment-change" class="font-display text-2xl font-semibold tabular-nums text-mz-text-primary">$0.00</span>
            </div>

            <p id="payment-error" class="hidden rounded-md bg-mz-error/10 px-3 py-2 text-xs font-medium text-mz-error"></p>
        </div>

        <div class="flex flex-col-reverse gap-2 border-t border-mz-border-light px-5 py-4 sm:flex-row sm:justify-end">
            <button type="button" data-close-dialog="payment-dialog" class="x-button x-button-secondary size-sm">
                Cancelar
            </button>
            <button type="button" id="payment-confirm" class="x-button x-button-primary size-sm">
                <x-icon name="check" class="size-4" />
                Confirmar cobro
            </button>
        </div>
    </dialog>

    <!-- Modal de movimiento de caja -->
    @if ($permissions['cash'])
        <dialog id="cash-dialog" class="w-full max-w-md rounded-lg border border-mz-border bg-mz-bg-surface shadow-lg p-0" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); box-sizing: border-box;">
            <form id="cash-form" action="{{ route('pos.cash.store') }}" method="POST">
                @csrf
                <input type="hidden" name="type" id="cash-type" value="in">

                <div class="border-b border-mz-border-light px-5 py-4">
                    <div class="flex items-center justify-between">
                        <h2 id="cash-title" class="text-base font-semibold text-mz-text-primary">Entrada de efectivo</h2>
                        <button type="button" data-close-dialog="cash-dialog" class="rounded-md p-1 text-mz-text-muted hover:bg-mz-bg-surface-2 hover:text-mz-text-primary" aria-label="Cerrar">
                            <x-icon name="x" class="size-5" />
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-mz-text-muted">El movimiento queda registrado con tu usuario y la fecha de hoy.</p>
                </div>

                <div class="space-y-4 px-5 py-4">
                    <div>
                        <label for="cash-category" class="block text-sm font-medium text-mz-text-secondary">Tipo de movimiento</label>
                        <select id="cash-category" name="category" required class="mz-select mt-1.5 h-11">
                            <optgroup label="Entradas">
                                @foreach ($cashCategories as $key => $category)
                                    <option value="{{ $key }}" data-type="{{ $category['type'] }}">{{ $category['label'] }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Salidas">
                                @foreach ($cashCategories as $key => $category)
                                    @if ($category['type'] === 'out')
                                        <option value="{{ $key }}" data-type="{{ $category['type'] }}">{{ $category['label'] }}</option>
                                    @endif
                                @endforeach
                            </optgroup>
                        </select>
                    </div>

                    <div>
                        <label for="cash-amount" class="block text-sm font-medium text-mz-text-secondary">Monto ({{ $currency }})</label>
                        <input id="cash-amount" name="amount" type="text" inputmode="decimal" step="0.01" min="0.01" required class="mz-input mt-1.5 h-11 text-right text-lg font-semibold tabular-nums text-mz-text-primary" placeholder="0.00">
                    </div>

                    <div>
                        <label for="cash-concept" class="block text-sm font-medium text-mz-text-secondary">Motivo o concepto</label>
                        <input id="cash-concept" name="concept" type="text" maxlength="255" class="mz-input mt-1.5 h-11 text-sm text-mz-text-secondary" placeholder="Ej. cambio para la caja, pago a proveedor...">
                    </div>

                    <p id="cash-error" class="hidden rounded-md bg-mz-error/10 px-3 py-2 text-xs font-medium text-mz-error" role="alert"></p>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-mz-border-light px-5 py-4 sm:flex-row sm:justify-end">
                    <button type="button" data-close-dialog="cash-dialog" class="x-button x-button-secondary size-sm">
                        Cancelar
                    </button>
                    <button type="submit" class="x-button x-button-primary size-sm">
                        <x-icon name="check" class="size-4" />
                        Guardar movimiento
                    </button>
                </div>
            </form>
        </dialog>
    @endif

    <div id="ticket-print-container" class="hidden"></div>

    <style>
        #ticket-print-container {
            font-family: "Courier New", ui-monospace, monospace;
            font-size: 11px;
            line-height: 1.45;
            color: #0f172a;
        }
        #ticket-print-container .ticket-sheet {
            width: {{ (int) ($settings['ticket']['width_mm'] ?? 80) }}mm;
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
        #ticket-print-container th.num, #ticket-print-container td.num { text-align: right; white-space: nowrap; }
        #ticket-print-container td { padding: 3px 0; vertical-align: top; }
        #ticket-print-container .item-name { font-weight: 700; }
        #ticket-print-container .totals td { padding: 2px 0; }
        #ticket-print-container .total-row td { padding-top: 6px; font-size: 14px; font-weight: 700; }
        #ticket-print-container .folio { font-size: 12px; font-weight: 700; letter-spacing: 0.08em; }
        @media print {
            body * { visibility: hidden !important; }
            #ticket-print-container, #ticket-print-container * { visibility: visible !important; }
            #ticket-print-container { position: absolute !important; top: 0; left: 0; width: 100%; margin: 0; padding: 0; background: #fff; }
            body { padding: 0; background: #fff; }
            @page { size: {{ (int) ($settings['ticket']['width_mm'] ?? 80) }}mm auto; margin: 4mm; }
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
            'reopen_my_day' => route('cuts.reopen-my-day'),
            ],
            'permissions' => $permissions,
            'currency' => $currency,
            'csrf' => csrf_token(),
            'storeEnabled' => (bool) ($settings['enabled'] ?? true),
        ], JSON_UNESCAPED_UNICODE) !!}
    </script>
@endsection
