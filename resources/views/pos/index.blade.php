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
        <div>
            <p class="text-sm text-slate-500">
                Atiende en <span class="font-semibold text-slate-900">{{ $settings['business']['name'] }}</span>
            </p>
            <p class="mt-1 text-xs text-slate-500">
                Usuario: <span class="font-medium text-slate-700">{{ $user?->name }}</span>
                · {{ now()->format('d/m/Y H:i') }}
                · Moneda: {{ $currency }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($permissions['cash'])
                <button type="button" data-cash-open="in" class="inline-flex h-9 items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                    <x-icon name="arrow-down" class="size-4" />
                    Entrada de efectivo
                </button>
                <button type="button" data-cash-open="out" class="inline-flex h-9 items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 text-xs font-semibold text-amber-700 transition hover:bg-amber-100">
                    <x-icon name="arrow-up" class="size-4" />
                    Salida de efectivo
                </button>
            @endif

            <a href="{{ route('pos.sales.previous') }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                <x-icon name="receipt" class="size-4" />
                Tickets anteriores
            </a>
            <a href="{{ route('pos.sales.day') }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                <x-icon name="chart" class="size-4" />
                Ventas del día
            </a>
            @if ($permissions['cut'])
                <a href="{{ route('pos.cuts.index') }}" class="inline-flex h-9 items-center gap-2 rounded-lg bg-ink-900 px-3 text-xs font-semibold text-white transition hover:bg-ink-800">
                    <x-icon name="report" class="size-4" />
                    Corte del día
                </a>
            @endif

            @if ($settings['enabled'] === false)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">
                    <x-icon name="alert" class="size-3.5" />
                    POS desactivado
                </span>
            @endif
        </div>
    </div>

    @if (! $permissions['sell'])
        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
            <p class="flex items-start gap-2">
                <x-icon name="alert" class="mt-0.5 size-4 shrink-0" />
                <span>Tu cuenta puede abrir el punto de venta, pero no cobrar. Pide al administrador el permiso <strong>«Cobrar y registrar ventas»</strong>.</span>
            </p>
        </div>
    @endif

    <div id="pos-error" class="mt-5 hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert"></div>

    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-5">
            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <label for="pos-search" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Buscar o escanear producto</label>
                        <div class="relative mt-1.5">
                            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                            <input id="pos-search" type="search" autocomplete="off" placeholder="Escanea el código de barras o escribe nombre, SKU o marca" class="h-12 w-full rounded-lg border border-slate-200 bg-slate-50 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                        </div>
                    </div>
                    <label class="inline-flex h-12 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-medium text-slate-700">
                        <input id="pos-wholesale" type="checkbox" @disabled(! $permissions['wholesale']) class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        Mayoreo
                    </label>
                </div>

                <div id="pos-results" class="mt-3 hidden max-h-80 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg"></div>

                @if ($frequentProducts !== [])
                    <div class="mt-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Productos frecuentes</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($frequentProducts as $product)
                                <button type="button"
                                        data-add-product='@json($product)'
                                        class="inline-flex max-w-full items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-left text-xs font-medium text-slate-700 transition hover:border-brand-400 hover:bg-brand-50">
                                    <x-icon name="plus" class="size-3.5 shrink-0 text-brand-600" />
                                    <span class="truncate">{{ $product['name'] }}</span>
                                    <span class="shrink-0 tabular-nums text-slate-500">${{ number_format($product['price'], 2) }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            <div id="pending-banner" class="hidden rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="flex items-center gap-2">
                        <x-icon name="pause" class="size-4" />
                        Estás cobrando una venta pendiente. Los precios y cantidades ya están guardados.
                    </p>
                    <button type="button" id="pos-new-sale" class="rounded-lg border border-sky-300 bg-white px-3 py-1.5 text-xs font-semibold text-sky-800">Nueva venta</button>
                </div>
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <h2 class="text-sm font-semibold text-slate-900">Detalle de la venta</h2>
                    <span id="pos-items-count" class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">0 artículos</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                                <th scope="col" class="px-4 py-3 font-medium">Producto</th>
                                <th scope="col" class="px-4 py-3 text-right font-medium">Precio</th>
                                <th scope="col" class="px-4 py-3 text-center font-medium">Cantidad</th>
                                <th scope="col" class="px-4 py-3 text-right font-medium">Importe</th>
                                <th scope="col" class="px-4 py-3 text-right font-medium">Existencia</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Quitar</span></th>
                            </tr>
                        </thead>
                        <tbody id="pos-cart-body" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>

                <div id="pos-empty" class="px-4 py-14 text-center">
                    <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <x-icon name="cart" class="size-6" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-slate-700">La venta está vacía</p>
                    <p class="mt-1 text-xs text-slate-500">Busca un producto o escanea su código de barras para comenzar.</p>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-lg bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Subtotal</p>
                        <p id="pos-subtotal" class="mt-1 font-display text-xl font-semibold tabular-nums text-slate-700">$0.00</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">IVA incluido</p>
                        <p id="pos-tax" class="mt-1 font-display text-xl font-semibold tabular-nums text-slate-700">$0.00</p>
                    </div>
                    <div class="rounded-lg bg-brand-50 px-4 py-3 ring-1 ring-inset ring-brand-200">
                        <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Total</p>
                        <p id="pos-total" class="mt-1 font-display text-2xl font-bold tabular-nums text-brand-800">$0.00</p>
                    </div>
                </div>

                <div id="last-sale-indicator" class="hidden flex items-center gap-2 mb-3 rounded-lg border border-brand-200 bg-brand-50 px-3 py-2 text-sm text-brand-800 shadow-sm">
                    <x-icon name="check" class="size-4 shrink-0" />
                    <span>Venta guardada: <span id="last-sale-folio" class="font-semibold"></span></span>
                </div>

                <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <button type="button" id="pos-clear" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                        <x-icon name="trash" class="size-4" />
                        Eliminar venta
                    </button>
                    <button type="button" id="pos-pending" @disabled(! $permissions['sell']) class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                        <x-icon name="pause" class="size-4" />
                        Dejar pendiente
                    </button>
                    <button type="button" id="pos-print-ticket" disabled class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-ink-900 bg-white px-4 text-sm font-semibold text-ink-900 transition hover:bg-ink-50 disabled:cursor-not-allowed disabled:opacity-50 disabled:border-slate-300 disabled:text-slate-400">
                        <x-icon name="printer" class="size-4" />
                        Imprimir ticket
                    </button>
                    <button type="button" id="pos-charge" @disabled(! $permissions['sell']) class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-700 px-6 text-sm font-semibold text-white transition hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-50">
                        <x-icon name="banknotes" class="size-4" />
                        Cobrar
                    </button>
                </div>
            </section>
        </div>

        <aside class="space-y-5">
            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Resumen del día</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Tickets cobrados</dt>
                        <dd class="font-semibold tabular-nums text-slate-900">{{ $today['tickets'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Ventas propias</dt>
                        <dd class="font-semibold tabular-nums text-slate-900">${{ number_format((float) $today['sales'], 2) }}</dd>
                    </div>
                </dl>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <a href="{{ route('pos.sales.pending') }}" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                        <x-icon name="pause" class="size-4" />
                        Pendientes
                    </a>
                    @if ($permissions['returns_request'])
                        <a href="{{ route('pos.returns.index') }}" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                            <x-icon name="arrow-uturn-left" class="size-4" />
                            Devoluciones
                        </a>
                    @endif
                    @if ($permissions['cash'])
                        <a href="{{ route('pos.cash.index') }}" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                            <x-icon name="banknotes" class="size-4" />
                            Movimientos
                        </a>
                    @endif
                    @if ($permissions['cut_own'])
                    <a href="{{ route('pos.cuts.mine') }}" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                        <x-icon name="report" class="size-4" />
                        Mi corte
                    </a>
                    @endif
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Atajos</h2>
                <ul class="mt-3 space-y-2 text-xs text-slate-500">
                    <li class="flex justify-between gap-3"><span>Buscar o escanear</span><kbd class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 font-mono text-[10px]">Enter</kbd></li>
                    <li class="flex justify-between gap-3"><span>Cobrar</span><kbd class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 font-mono text-[10px]">F12</kbd></li>
                    <li class="flex justify-between gap-3"><span>Nueva venta</span><kbd class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 font-mono text-[10px]">Esc</kbd></li>
                </ul>
            </section>
        </aside>
    </div>

    <dialog id="payment-dialog" class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-900/50">
        <div class="border-b border-slate-100 px-5 py-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Cobrar venta</h2>
                <button type="button" data-close-dialog="payment-dialog" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Cerrar">
                    <x-icon name="x" class="size-5" />
                </button>
            </div>
            <div>
                <label for="payment-method" class="block text-sm font-medium text-slate-700">Método de pago</label>
                <select id="payment-method" name="payment_method" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                    <option value="cash">Efectivo</option>
                    <option value="card">Tarjeta</option>
                    <option value="transfer">Transferencia</option>
                    <option value="mixed">Mixto (efectivo + otro)</option>
                </select>
            </div>

        <div class="space-y-4 px-5 py-4">
            <div class="rounded-xl bg-brand-50 px-4 py-3 ring-1 ring-inset ring-brand-200">
                <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Total a cobrar</p>
                <p id="payment-total" class="mt-1 font-display text-3xl font-bold tabular-nums text-brand-800">$0.00</p>
            </div>

            <div id="payment-amount-group">
                <label for="payment-amount" class="block text-sm font-medium text-slate-700">Pago con</label>
                <input id="payment-amount" type="number" step="0.01" min="0" inputmode="decimal" class="mt-1.5 h-12 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-right text-2xl font-semibold tabular-nums text-slate-900 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20" placeholder="0.00">
                <div class="mt-2 flex flex-wrap gap-2">
                    <button type="button" data-amount-exact class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">Exacto</button>
                    <button type="button" data-amount="100" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">$100</button>
                    <button type="button" data-amount="200" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">$200</button>
                    <button type="button" data-amount="500" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">$500</button>
                </div>
            </div>

            <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3">
                <span class="text-sm text-slate-600">Cambio</span>
                <span id="payment-change" class="font-display text-2xl font-semibold tabular-nums text-slate-900">$0.00</span>
            </div>

            <p id="payment-error" class="hidden rounded-lg bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700"></p>
        </div>

        <div class="flex flex-col-reverse gap-2 border-t border-slate-100 px-5 py-4 sm:flex-row sm:justify-end">
            <button type="button" data-close-dialog="payment-dialog" class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 px-4 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
            <button type="button" id="payment-confirm" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-700 px-6 text-sm font-semibold text-white hover:bg-brand-800">
                <x-icon name="check" class="size-4" />
                Confirmar cobro
            </button>
        </div>
    </dialog>

    @if ($permissions['cash'])
        <dialog id="cash-dialog" class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-900/50">
            <form action="{{ route('pos.cash.store') }}" method="POST">
                @csrf
                <input type="hidden" name="type" id="cash-type" value="in">

                <div class="border-b border-slate-100 px-5 py-4">
                    <div class="flex items-center justify-between">
                        <h2 id="cash-title" class="text-base font-semibold text-slate-900">Entrada de efectivo</h2>
                        <button type="button" data-close-dialog="cash-dialog" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Cerrar">
                            <x-icon name="x" class="size-5" />
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">El movimiento queda registrado con tu usuario y la fecha de hoy.</p>
                </div>

                <div class="space-y-4 px-5 py-4">
                    <div>
                        <label for="cash-category" class="block text-sm font-medium text-slate-700">Tipo de movimiento</label>
                        <select id="cash-category" name="category" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                            @foreach ($cashCategories as $key => $category)
                                <option value="{{ $key }}" data-type="{{ $category['type'] }}">{{ $category['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="cash-amount" class="block text-sm font-medium text-slate-700">Monto ({{ $currency }})</label>
                        <input id="cash-amount" name="amount" type="number" step="0.01" min="0.01" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-right text-lg font-semibold tabular-nums text-slate-900 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20" placeholder="0.00">
                    </div>

                    <div>
                        <label for="cash-concept" class="block text-sm font-medium text-slate-700">Motivo o concepto</label>
                        <input id="cash-concept" name="concept" type="text" required maxlength="255" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20" placeholder="Ej. cambio para la caja, pago a proveedor...">
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-100 px-5 py-4 sm:flex-row sm:justify-end">
                    <button type="button" data-close-dialog="cash-dialog" class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 px-4 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-ink-900 px-5 text-sm font-semibold text-white hover:bg-ink-800">
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
            ],
            'permissions' => $permissions,
            'currency' => $currency,
            'csrf' => csrf_token(),
            'storeEnabled' => (bool) ($settings['enabled'] ?? true),
        ], JSON_UNESCAPED_UNICODE) !!}
    </script>
@endsection
