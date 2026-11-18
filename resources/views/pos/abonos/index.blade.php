@extends('layouts.admin')

@section('title', 'Abonos / Créditos')
@section('subtitle', 'Cartera manual de deudas de clientes')

@php
    $money = function (int $cents): string {
        return '$'.number_format(intdiv($cents, 100)).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    };
    $oldForm = old('form', 'create');
    $toCents = function (string $amount): int {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    };
@endphp

@section('content')
    <div class="mb-6 flex flex-col gap-2 border-b border-mz-border-light pb-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-bold uppercase tracking-tight text-mz-text-primary">Cartera manual</h2>
            <p class="mt-1 text-sm text-mz-text-muted">Registra deudas y abonos sin crear ventas, tickets ni mover inventario.</p>
        </div>
        <a href="{{ route('pos.cuts.index') }}" class="x-button x-button-secondary size-sm">Ver corte del día</a>
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    @if ($errors->any() && $oldForm === 'create')
        <div class="mz-alert mz-alert-error mb-4">
            <p class="font-semibold">No se registró la cuenta:</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        {{-- Alta de cuenta --}}
        <section class="mz-card mz-card-padded lg:col-span-1">
            <h3 class="text-lg font-semibold text-mz-text-primary">Nueva cuenta</h3>
            <p class="mt-1 text-xs text-mz-text-muted">El buscador solo agrega texto y un precio de referencia. No descuenta stock.</p>

            <form method="POST" action="{{ route('pos.abonos.store') }}" class="mt-4 flex flex-col gap-4" data-create-form>
                @csrf
                <input type="hidden" name="form" value="create">
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">

                <div>
                    <label for="customer_name" class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Nombre del cliente</label>
                    <input id="customer_name" name="customer_name" type="text" maxlength="150" required
                           value="{{ old('customer_name') }}" class="mz-input" placeholder="Nombre completo">
                </div>

                <div>
                    <label for="customer_phone" class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Teléfono (opcional)</label>
                    <input id="customer_phone" name="customer_phone" type="text" maxlength="30"
                           value="{{ old('customer_phone') }}" class="mz-input" placeholder="10 dígitos">
                </div>

                <div class="relative">
                    <label for="product-search" class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Agregar producto de referencia</label>
                    <input id="product-search" type="search" autocomplete="off" class="mz-input"
                           placeholder="Buscar por nombre, SKU o código"
                           data-products-url="{{ route('pos.abonos.products') }}">
                    <div id="product-results" class="mz-card absolute inset-x-0 z-20 mt-1 hidden max-h-56 overflow-y-auto p-1" role="listbox"></div>
                </div>

                <div>
                    <label for="description" class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Qué se llevó</label>
                    <textarea id="description" name="description" rows="4" required maxlength="2000" class="mz-input"
                              placeholder="Detalle manual o productos agregados">{{ old('description') }}</textarea>
                </div>

                <div>
                    <label for="total_amount" class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Total de la deuda (MXN)</label>
                    <input id="total_amount" name="total_amount" type="text" inputmode="decimal" required
                           value="{{ old('total_amount', '0.00') }}" class="mz-input" placeholder="0.00">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="initial_payment" class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Abono inicial</label>
                        <input id="initial_payment" name="initial_payment" type="text" inputmode="decimal"
                               value="{{ old('initial_payment') }}" class="mz-input" placeholder="0.00">
                    </div>
                    <div>
                        <label for="method" class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Método</label>
                        <select id="method" name="method" class="mz-select">
                            <option value="cash" @selected(old('method') === 'cash')>Efectivo</option>
                            <option value="card" @selected(old('method') === 'card')>Tarjeta</option>
                            <option value="transfer" @selected(old('method') === 'transfer')>Transferencia</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="x-button x-button-primary size-md">Crear cuenta y deuda</button>
            </form>
        </section>

        {{-- Cuentas --}}
        <section class="flex flex-col gap-4 lg:col-span-2">
            <form method="GET" action="{{ route('pos.abonos.index') }}" class="mz-card mz-card-padded flex flex-col gap-3 sm:flex-row sm:items-end" data-filter-form>
                <div class="flex-1">
                    <label for="credit-q" class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Buscar persona</label>
                    <input id="credit-q" type="search" name="q" value="{{ $filters['q'] }}" class="mz-input" placeholder="Nombre del cliente">
                </div>
                <label class="flex items-center gap-2 pb-2 text-sm text-mz-text-secondary">
                    <input type="checkbox" name="settled" value="1" class="mz-checkbox" @checked($filters['settled'])>
                    Mostrar liquidadas ($0.00)
                </label>
                <button type="submit" class="x-button x-button-secondary size-sm">Filtrar</button>
            </form>

            <div id="credit-list">
                <p class="mb-3 text-xs text-mz-text-muted">
                    {{ $credits->total() }} cuenta(s) · {{ $filters['settled'] ? 'incluye liquidadas' : 'solo pendientes' }}
                </p>

                @forelse ($credits as $credit)
                    <article class="mz-card mb-4 p-4 sm:p-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="truncate text-base font-semibold text-mz-text-primary">{{ $credit->customer_name }}</h3>
                                    @if ($credit->isSettled())
                                        <span class="mz-badge mz-badge-approved">Liquidado</span>
                                    @else
                                        <span class="mz-badge mz-badge-pending">Pendiente</span>
                                    @endif
                                </div>
                                @if ($credit->customer_phone)
                                    <p class="mt-1 text-xs text-mz-text-muted">{{ $credit->customer_phone }}</p>
                                @endif
                                <p class="mt-2 whitespace-pre-line text-sm text-mz-text-secondary">{{ $credit->description }}</p>
                            </div>

                            @if (! $credit->isSettled())
                                <button type="button" class="x-button x-button-primary size-sm shrink-0"
                                        data-open-payment
                                        data-credit-id="{{ $credit->id }}"
                                        data-customer="{{ $credit->customer_name }}"
                                        data-balance="{{ \App\Services\CustomerCreditService::decimal($credit->balance_cents) }}"
                                        data-action="{{ route('pos.abonos.payments.store', ['customerCredit' => '__CREDIT__']) }}">
                                    + Abonar
                                </button>
                            @endif
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-mz-border-light pt-4 text-sm sm:grid-cols-4">
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-mz-text-muted">Total</dt>
                                <dd class="mt-0.5 text-mz-text-secondary">{{ $money($credit->total_cents) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-mz-text-muted">Abonado</dt>
                                <dd class="mt-0.5 text-mz-success">{{ $money($credit->paidCents()) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-mz-text-muted">Debe</dt>
                                <dd class="mt-0.5 text-lg font-bold {{ $credit->isSettled() ? 'text-mz-success' : 'text-mz-accent-primary' }}">{{ $money($credit->balance_cents) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-mz-text-muted">Registró</dt>
                                <dd class="mt-0.5 text-mz-text-secondary">{{ $credit->creator?->name ?? '—' }}</dd>
                            </div>
                        </dl>

                        <details class="mt-4 border-t border-mz-border-light pt-3">
                            <summary class="cursor-pointer text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Ver historial</summary>
                            @if ($credit->payments->isEmpty())
                                <p class="mt-2 text-sm text-mz-text-muted">Sin abonos registrados.</p>
                            @else
                                <div class="mt-3 overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="text-xs uppercase text-mz-text-muted">
                                                <th class="py-2 text-left">Fecha</th>
                                                <th class="py-2 text-left">Hora</th>
                                                <th class="py-2 text-left">Cobró</th>
                                                <th class="py-2 text-left">Método</th>
                                                <th class="py-2 text-right">Monto</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-mz-border-light">
                                            @foreach ($credit->payments as $payment)
                                                <tr class="text-mz-text-secondary">
                                                    <td class="py-2 whitespace-nowrap">{{ $payment->happened_at?->format('d/m/Y') }}</td>
                                                    <td class="py-2 whitespace-nowrap">{{ $payment->happened_at?->format('H:i') }}</td>
                                                    <td class="py-2">{{ $payment->user?->name ?? '—' }}</td>
                                                    <td class="py-2">{{ ['cash' => 'Efectivo', 'card' => 'Tarjeta', 'transfer' => 'Transferencia'][$payment->method] ?? $payment->method }}</td>
                                                    <td class="py-2 text-right">{{ $money($toCents($payment->amount)) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </details>
                    </article>
                @empty
                    <div class="mz-card mz-card-padded text-center text-sm text-mz-text-muted">
                        No hay cuentas que coincidan. Todo aparece en <b>$0.00</b>.
                    </div>
                @endforelse

                <div class="mt-4">
                    {{ $credits->links() }}
                </div>
            </div>
        </section>
    </div>

    {{-- Modal de abono --}}
    <dialog id="payment-modal" class="mz-card mz-card-padded w-full max-w-md" @if ($oldForm === 'payment') data-open-on-load="1" @endif>
        <form method="POST" action="{{ old('form') === 'payment' && old('credit_id') ? route('pos.abonos.payments.store', ['customerCredit' => old('credit_id')]) : '#' }}" class="flex flex-col gap-4" data-payment-form>
            @csrf
            <input type="hidden" name="form" value="payment">
            <input type="hidden" name="credit_id" value="{{ old('credit_id') }}">
            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">

            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-mz-text-primary">Registrar abono</h3>
                    <p class="mt-1 text-sm text-mz-text-secondary" id="payment-customer">{{ $oldCredit?->customer_name ?? '—' }}</p>
                </div>
                <button type="button" class="text-sm text-mz-text-muted hover:text-mz-text-primary" data-close-payment>Cerrar</button>
            </div>

            <p class="text-sm text-mz-text-muted">Saldo actual: <b class="text-mz-accent-primary" id="payment-balance">{{ $oldCredit ? $money($oldCredit->balance_cents) : '$0.00' }}</b></p>

            @if ($errors->any() && $oldForm === 'payment')
                <div class="mz-alert mz-alert-error">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label for="payment-amount" class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Cantidad a abonar</label>
                <input id="payment-amount" name="amount" type="text" inputmode="decimal" required value="{{ old('form') === 'payment' ? old('amount') : '' }}" class="mz-input" placeholder="0.00">
            </div>

            <div class="flex gap-2">
                <button type="button" class="x-button x-button-secondary size-xs" data-liquidate>Liquidar todo</button>
            </div>

            <div>
                <label for="payment-method" class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Método de pago</label>
                <select id="payment-method" name="method" class="mz-select">
                    <option value="cash" @selected(old('form') === 'payment' && old('method') === 'cash')>Efectivo</option>
                    <option value="card" @selected(old('form') === 'payment' && old('method') === 'card')>Tarjeta</option>
                    <option value="transfer" @selected(old('form') === 'payment' && old('method') === 'transfer')>Transferencia</option>
                </select>
            </div>

            <button type="submit" class="x-button x-button-primary size-md">Confirmar abono</button>
        </form>
    </dialog>

    <script>
        (function () {
            const paymentModal = document.getElementById('payment-modal');
            const paymentForm = document.querySelector('[data-payment-form]');
            const customerEl = document.getElementById('payment-customer');
            const balanceEl = document.getElementById('payment-balance');
            const amountInput = document.getElementById('payment-amount');
            const filterForm = document.querySelector('[data-filter-form]');
            const creditList = document.getElementById('credit-list');
            const productSearch = document.getElementById('product-search');
            const productResults = document.getElementById('product-results');
            const description = document.getElementById('description');
            const totalInput = document.getElementById('total_amount');
            let balanceDecimal = '0.00';
            let filterTimer = null;
            let productTimer = null;
            let productController = null;

            function openPayment(button) {
                balanceDecimal = button.dataset.balance || '0.00';
                customerEl.textContent = button.dataset.customer || '';
                balanceEl.textContent = '$' + balanceDecimal;
                amountInput.value = '';
                amountInput.max = balanceDecimal;
                paymentForm.action = button.dataset.action.replace('__CREDIT__', encodeURIComponent(button.dataset.creditId));
                paymentForm.querySelector('[name="credit_id"]').value = button.dataset.creditId;
                const keyInput = paymentForm.querySelector('[name="idempotency_key"]');
                if (keyInput && window.crypto && crypto.randomUUID) {
                    keyInput.value = crypto.randomUUID();
                }
                amountInput.focus();
                if (!paymentModal.open) paymentModal.showModal();
            }

            function closePayment() {
                if (paymentModal.open) paymentModal.close();
            }

            document.addEventListener('click', function (event) {
                const openButton = event.target.closest('[data-open-payment]');
                if (openButton) {
                    event.preventDefault();
                    openPayment(openButton);
                    return;
                }

                if (event.target.closest('[data-close-payment]')) {
                    closePayment();
                    return;
                }

                if (event.target.closest('[data-liquidate]')) {
                    amountInput.value = balanceDecimal;
                    return;
                }

                const productButton = event.target.closest('[data-product-name]');
                if (productButton) {
                    const text = '1x ' + productButton.dataset.productName + ' ($' + productButton.dataset.productPrice + ')';
                    description.value = description.value.trim() === '' ? text : description.value.replace(/\s+$/, '') + '\n' + text;
                    const current = totalInput.value.trim() === '' ? 0 : totalInput.value.trim();
                    const cents = toCents(current);
                    const priceCents = toCents(productButton.dataset.productPrice);
                    if (cents !== null && priceCents !== null) {
                        totalInput.value = fromCents(cents + priceCents);
                    }
                    productResults.classList.add('hidden');
                    productResults.replaceChildren();
                    productSearch.value = '';
                }
            });

            function toCents(value) {
                const match = String(value).trim().match(/^(\d{1,7})(?:\.(\d{1,2}))?$/);
                if (!match) return null;
                return Number(match[1]) * 100 + Number((match[2] || '').padEnd(2, '0'));
            }

            function fromCents(cents) {
                return Math.floor(cents / 100) + '.' + String(cents % 100).padStart(2, '0');
            }

            if (productSearch) {
                productSearch.addEventListener('input', function () {
                    clearTimeout(productTimer);
                    const term = productSearch.value.trim();
                    if (term.length < 2) {
                        productResults.classList.add('hidden');
                        productResults.replaceChildren();
                        return;
                    }
                    productTimer = setTimeout(async function () {
                        if (productController) productController.abort();
                        productController = new AbortController();
                        try {
                            const url = new URL(productSearch.dataset.productsUrl, window.location.origin);
                            url.searchParams.set('q', term);
                            const response = await fetch(url, {
                                headers: { 'Accept': 'application/json' },
                                signal: productController.signal,
                            });
                            if (!response.ok) throw new Error('search failed');
                            const data = await response.json();
                            productResults.replaceChildren();
                            (data.products || []).forEach(function (product) {
                                const button = document.createElement('button');
                                button.type = 'button';
                                button.className = 'flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-left text-sm text-mz-text-secondary hover:bg-mz-bg-surface-2 hover:text-mz-text-primary';
                                button.dataset.productName = product.name;
                                button.dataset.productPrice = product.price;
                                const name = document.createElement('span');
                                name.textContent = product.name;
                                const price = document.createElement('span');
                                price.className = 'shrink-0 text-mz-text-muted';
                                price.textContent = '$' + product.price;
                                button.append(name, price);
                                productResults.appendChild(button);
                            });
                            productResults.classList.toggle('hidden', productResults.childElementCount === 0);
                        } catch (error) {
                            if (error.name !== 'AbortError') {
                                productResults.classList.add('hidden');
                            }
                        }
                    }, 250);
                });
            }

            if (filterForm && creditList) {
                filterForm.addEventListener('input', function () {
                    clearTimeout(filterTimer);
                    filterTimer = setTimeout(async function () {
                        const params = new URLSearchParams(new FormData(filterForm));
                        if (!params.get('settled')) params.delete('settled');
                        try {
                            const response = await fetch(filterForm.action + '?' + params.toString(), {
                                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                            });
                            if (!response.ok) return;
                            const html = await response.text();
                            const parsed = new DOMParser().parseFromString(html, 'text/html');
                            const next = parsed.getElementById('credit-list');
                            if (next) creditList.replaceWith(next);
                            window.history.replaceState(null, '', filterForm.action + (params.toString() ? '?' + params.toString() : ''));
                        } catch (error) {
                            // El filtro normal del formulario sigue disponible.
                        }
                    }, 300);
                });
            }

            if (paymentModal.dataset.openOnLoad === '1') {
                paymentModal.showModal();
            }

            paymentForm.addEventListener('submit', function (event) {
                if (paymentForm.getAttribute('action') === '#') {
                    event.preventDefault();
                    return;
                }
                const button = paymentForm.querySelector('button[type="submit"]');
                if (button) button.disabled = true;
            });

            paymentModal.addEventListener('click', function (event) {
                if (event.target === paymentModal) closePayment();
            });
        })();
    </script>
@endsection
