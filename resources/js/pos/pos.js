import numeral from 'numeral';

const configElement = document.getElementById('pos-config');

if (configElement) {
    const config = JSON.parse(configElement.textContent);
    const permissions = config.permissions;
    const csrf = config.csrf;

    const searchInput = document.getElementById('pos-search');
    const resultsBox = document.getElementById('pos-results');
    const cartBody = document.getElementById('pos-cart-body');
    const emptyState = document.getElementById('pos-empty');
    const itemsCount = document.getElementById('pos-items-count');
    const subtotalEl = document.getElementById('pos-subtotal');
    const taxEl = document.getElementById('pos-tax');
    const totalEl = document.getElementById('pos-total');
    const wholesaleToggle = document.getElementById('pos-wholesale');
    const errorBox = document.getElementById('pos-error');
    const reopenDayBtn = document.getElementById('pos-reopen-day');
    const pendingBanner = document.getElementById('pending-banner');
    const paymentDialog = document.getElementById('payment-dialog');
    const paymentAmount = document.getElementById('payment-amount');
    const paymentTotal = document.getElementById('payment-total');
    const paymentChange = document.getElementById('payment-change');
    const paymentError = document.getElementById('payment-error');
    const paymentConfirm = document.getElementById('payment-confirm');
    const paymentMethod = document.getElementById('payment-method');
    const cashDialog = document.getElementById('cash-dialog');
    const cashType = document.getElementById('cash-type');
    const cashCategory = document.getElementById('cash-category');
    const cashTitle = document.getElementById('cash-title');
    const cashError = document.getElementById('cash-error');

    // Estado para detectar escáner
    let isScanning = false;
    let scannerBuffer = '';

    const money = new Intl.NumberFormat('es-MX', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const formatMoney = (value) => `$${money.format(Number(value) || 0)}`;
    const round = (value) => Math.round((Number(value) || 0) * 100) / 100;

    // Formatea el monto de movimiento de caja con separador de miles y dos decimales
    const formatCashAmount = (value) => {
        const num = Number(value) || 0;
        return numeral(num).format('0,0.00');
    };

    // Devuelve el número plano a partir de un monto formateado (ej. "1,234.56" -> 1234.56)
    const parseCashAmount = (value) => {
        return numeral(value || '0').value();
    };

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const uuid = () => {
        if (window.crypto?.randomUUID) {
            return window.crypto.randomUUID();
        }

        return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
    };

    let items = [];
    let pendingSaleId = null;
    let wholesaleMode = false;
    let idempotencyKey = uuid();
    let productCache = new Map();
    let searchTimer = null;
    let submitting = false;
    let lastSaleId = null;
    let lastSaleFolio = null;
    let lastSaleData = null;

    const showError = (message) => {
        if (!errorBox) {
            return;
        }

        errorBox.innerHTML = `<p class="font-semibold">No se pudo completar la operación</p><p class="mt-1">${escapeHtml(message)}</p>`;
        errorBox.classList.remove('hidden');
        errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    const clearError = () => errorBox?.classList.add('hidden');

    const showLastSaleIndicator = (id, folio) => {
        lastSaleId = id;
        lastSaleFolio = folio;

        const indicator = document.getElementById('last-sale-indicator');
        const folioEl = document.getElementById('last-sale-folio');
        const printBtn = document.getElementById('pos-print-ticket');

        if (indicator && folioEl && printBtn) {
            folioEl.textContent = folio;
            indicator.classList.remove('hidden');
            printBtn.disabled = false;
        }
    };

    const clearLastSale = () => {
        lastSaleId = null;
        lastSaleFolio = null;
        lastSaleData = null;

        const indicator = document.getElementById('last-sale-indicator');
        const printBtn = document.getElementById('pos-print-ticket');

        if (indicator) {
            indicator.classList.add('hidden');
        }

        if (printBtn) {
            printBtn.disabled = true;
        }
    };

    const printLastSaleTicket = async () => {
        if (!lastSaleId || !config.routes.ticketData) {
            return;
        }

        const container = document.getElementById('ticket-print-container');

        if (!container) {
            return;
        }

        try {
            const response = await fetch(config.routes.ticketData.replace('__SALE__', lastSaleId), {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error('No se pudo obtener los datos del ticket.');
            }

            const data = await response.json();
            const sale = data.sale;
            const settings = data.settings || config;

            const money = new Intl.NumberFormat('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
            const formatMoney = (value) => `$${money.format(Number(value) || 0)}`;

            const itemsHtml = sale.items.map((item) => {
                const returned = item.returned_quantity > 0
                    ? `<tr><td colspan="3" class="muted">Devueltas: ${item.returned_quantity} pieza(s)</td></tr>`
                    : '';
                return `
                    <tr>
                        <td>
                            <span class="item-name">${escapeHtml(item.product_name)}</span>
                            <span class="muted" style="display:block">
                                ${escapeHtml(item.sku || item.barcode || '')}${item.category ? ` · ${escapeHtml(item.category)}` : ''}
                                · ${formatMoney(item.unit_price)} c/u
                            </span>
                        </td>
                        <td class="num">${item.quantity}</td>
                        <td class="num">${formatMoney(item.line_total)}</td>
                    </tr>
                    ${returned}
                `;
            }).join('');

            const business = settings.business || {};

            container.innerHTML = `
                <div class="ticket-sheet">
                    <div class="center">
                        <p class="business">${business.name ?? 'MotoZone'}</p>
                        ${business.address ? `<p class="muted">${business.address}</p>` : ''}
                        ${business.phone ? `<p class="muted">Tel. ${business.phone}</p>` : ''}
                        ${business.tax_id ? `<p class="muted">RFC ${business.tax_id}</p>` : ''}
                    </div>

                    <hr class="divider">

                    <div class="center">
                        <p class="folio">${sale.folio}</p>
                        <p class="muted">${sale.paid_at ?? ''}</p>
                        <p class="muted">Atendió: ${sale.user ?? ''}</p>
                    </div>

                    <hr class="divider">

                    <table>
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="num">Cant.</th>
                                <th class="num">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                    </table>

                    <hr class="divider">

                    <table class="totals">
                        <tr>
                            <td class="label">Subtotal</td>
                            <td class="num">${formatMoney(sale.subtotal)}</td>
                        </tr>
                        <tr>
                            <td class="label">IVA incluido</td>
                            <td class="num">${formatMoney(sale.tax_total)}</td>
                        </tr>
                        <tr class="total-row">
                            <td>Total</td>
                            <td class="num">${formatMoney(sale.total)}</td>
                        </tr>
                        <tr>
                            <td class="label">Pago con</td>
                            <td class="num">${formatMoney(sale.amount_paid)}</td>
                        </tr>
                        <tr>
                            <td class="label">Cambio</td>
                            <td class="num">${formatMoney(sale.change_amount)}</td>
                        </tr>
                    </table>

                    <hr class="divider">

                    <div class="center">
                        <p class="muted">Método de pago: efectivo</p>
                        <p class="footer">${settings.ticket?.footer ?? 'Gracias por su compra.'}</p>
                        <p class="muted">Conserve este ticket para cambios y devoluciones.</p>
                    </div>
                </div>
            `;

            container.classList.remove('hidden');
            window.print();
            container.classList.add('hidden');
        } catch (error) {
            showError(error.message);
        }
    };

    const lineTotal = (item) => round(item.unit_price * item.quantity);

    const totals = () => {
        let subtotal = 0;
        let tax = 0;
        let total = 0;

        items.forEach((item) => {
            const line = lineTotal(item);
            const rate = Number(item.tax_rate) || 0;
            const lineSubtotal = line / (1 + rate);

            total += line;
            subtotal += lineSubtotal;
            tax += line - lineSubtotal;
        });

        return {
            subtotal: round(subtotal),
            tax: round(tax),
            total: round(total),
        };
    };

    const renderTotals = () => {
        const result = totals();
        subtotalEl.textContent = formatMoney(result.subtotal);
        taxEl.textContent = formatMoney(result.tax);
        totalEl.textContent = formatMoney(result.total);
        itemsCount.textContent = `${items.reduce((sum, item) => sum + item.quantity, 0)} ${items.length === 1 ? 'artículo' : 'artículos'}`;
        emptyState.classList.toggle('hidden', items.length > 0);
    };

    const renderCart = () => {
        cartBody.innerHTML = '';

        items.forEach((item, index) => {
            const row = document.createElement('tr');
            const locked = pendingSaleId !== null;
            const priceControl = permissions.price_edit && !locked
                ? `<input type="number" step="0.01" min="0" value="${item.unit_price.toFixed(2)}" data-action="price" data-index="${index}" class="h-9 w-24 rounded-md border border-mz-border-light bg-mz-bg-surface-2 px-2 text-right text-sm font-medium tabular-nums text-mz-text-primary focus:border-mz-accent-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/20">`
                : `<span class="tabular-nums text-mz-text-secondary">${formatMoney(item.unit_price)}</span>`;

            const quantityControl = !locked
                ? `<input type="number" step="1" min="1" value="${item.quantity}" data-action="quantity" data-index="${index}" class="h-9 w-20 rounded-md border border-mz-border-light bg-mz-bg-surface-2 px-2 text-center text-sm font-medium tabular-nums text-mz-text-primary focus:border-mz-accent-primary focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/20">`
                : `<span class="tabular-nums text-mz-text-secondary">${item.quantity}</span>`;

            const stockLabel = item.track_inventory
                ? `${item.stock ?? 0} disponibles`
                : 'Sin control';

            row.innerHTML = `
                <td class="px-4 py-3">
                    <p class="font-medium text-mz-text-primary">${escapeHtml(item.name)}</p>
                    <p class="text-xs text-mz-text-muted">${escapeHtml(item.sku || item.barcode || '')}${item.category ? ` · ${escapeHtml(item.category)}` : ''}</p>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-right">${priceControl}</td>
                <td class="whitespace-nowrap px-4 py-3 text-center">${quantityControl}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-mz-text-primary">${formatMoney(lineTotal(item))}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-xs ${item.track_inventory && item.stock <= (item.min_stock ?? 0) ? 'text-mz-warning' : 'text-mz-text-muted'}">${stockLabel}</td>
                <td class="px-4 py-3 text-right">
                    ${locked ? '' : `<button type="button" data-action="remove" data-index="${index}" class="inline-flex size-8 items-center justify-center rounded-lg border border-mz-border-light text-mz-text-muted transition hover:border-mz-error/40 hover:bg-mz-error/10 hover:text-mz-error" aria-label="Quitar ${escapeHtml(item.name)}"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>`}
                </td>
            `;

            cartBody.appendChild(row);
        });

        renderTotals();
    };

    const priceFor = (product) => {
        if (wholesaleMode && product.wholesale_price !== null && product.wholesale_price !== undefined) {
            return round(product.wholesale_price);
        }

        return round(product.price);
    };

    const addProduct = (product, quantity = 1) => {
        clearError();

        if (pendingSaleId !== null) {
            showError('Termina o cancela la venta pendiente antes de agregar otros productos.');
            return;
        }

        if (product.track_inventory && Number(product.stock) <= 0) {
            showError(`«${product.name}» está agotado.`);
            return;
        }

        const existing = items.find((item) => item.product_id === product.id);

        if (existing) {
            const nextQuantity = existing.quantity + quantity;

            if (existing.track_inventory && nextQuantity > Number(existing.stock)) {
                showError(`No hay existencia suficiente de «${existing.name}». Disponible: ${existing.stock}.`);
                return;
            }

            existing.quantity = nextQuantity;
        } else {
            items.push({
                product_id: product.id,
                name: product.name,
                sku: product.sku,
                barcode: product.barcode,
                category: product.category,
                price: round(product.price),
                wholesale_price: product.wholesale_price === null ? null : round(product.wholesale_price),
                unit_price: priceFor(product),
                manual_price: null,
                quantity,
                tax_rate: Number(product.tax_rate) || 0,
                stock: product.track_inventory ? Number(product.stock) : null,
                min_stock: Number(product.min_stock) || 0,
                track_inventory: Boolean(product.track_inventory),
            });
        }

        renderCart();
        searchInput.value = '';
        resultsBox.classList.add('hidden');
        searchInput.focus();
    };

    const setWholesale = (enabled) => {
        wholesaleMode = enabled;

        items.forEach((item) => {
            if (item.manual_price !== null) {
                return;
            }

            item.unit_price = enabled && item.wholesale_price !== null ? item.wholesale_price : item.price;
        });

        renderCart();
    };

    const renderResults = (products) => {
        productCache = new Map(products.map((product) => [Number(product.id), product]));

        if (products.length === 0) {
            resultsBox.innerHTML = '<p class="px-4 py-6 text-center text-sm text-mz-text-muted">No se encontraron productos activos.</p>';
            resultsBox.classList.remove('hidden');
            return;
        }

        resultsBox.innerHTML = products.map((product) => `
            <button type="button" data-product-id="${product.id}" class="flex w-full items-center justify-between gap-4 border-b border-mz-border px-4 py-3 text-left transition last:border-b-0 hover:bg-mz-bg-surface-2">
                <span class="min-w-0">
                    <span class="block truncate text-sm font-medium text-mz-text-primary">${escapeHtml(product.name)}</span>
                    <span class="block truncate text-xs text-mz-text-muted">${escapeHtml(product.sku || product.barcode || '')}${product.category ? ` · ${escapeHtml(product.category)}` : ''}</span>
                </span>
                <span class="shrink-0 text-right">
                    <span class="block text-sm font-semibold tabular-nums text-mz-text-primary">${formatMoney(product.price)}</span>
                    <span class="block text-[11px] ${product.in_stock ? 'text-mz-success' : 'text-mz-error'}">${escapeHtml(product.stock_label)}</span>
                </span>
            </button>
        `).join('');

        resultsBox.classList.remove('hidden');
    };

    const searchNow = async () => {
        const term = searchInput.value.trim();

        if (term === '') {
            resultsBox.classList.add('hidden');
            return [];
        }

        try {
            const response = await fetch(`${config.routes.search}?q=${encodeURIComponent(term)}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error('No se pudo buscar.');
            }

            const data = await response.json();
            renderResults(data.products ?? []);

            return data.products ?? [];
        } catch (error) {
            showError('No se pudo buscar productos. Revisa tu conexión e inténtalo de nuevo.');
            return [];
        }
    };

    const resetSale = () => {
        items = [];
        pendingSaleId = null;
        wholesaleMode = false;
        idempotencyKey = uuid();
        wholesaleToggle.checked = false;
        wholesaleToggle.disabled = !permissions.wholesale;
        pendingBanner.classList.add('hidden');
        searchInput.value = '';
        resultsBox.classList.add('hidden');
        clearError();
        renderCart();
        searchInput.focus();
    };

    const buildPayload = (status, amountPaid) => ({
        status,
        amount_paid: status === 'paid' ? round(amountPaid) : 0,
        idempotency_key: idempotencyKey,
        payment_method: paymentMethod?.value || 'cash',
        items: items.map((item) => ({
            product_id: item.product_id,
            quantity: item.quantity,
            unit_price: item.manual_price,
            wholesale: wholesaleMode,
        })),
    });

    const postJson = async (url, payload) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json().catch(() => ({}));

        // Detectar día cerrado para el usuario: mostrar botón de reabrir
        if (data.day_closed_for_user) {
            reopenDayBtn?.classList.remove('hidden');
        } else {
            reopenDayBtn?.classList.add('hidden');
        }

        if (response.status === 422) {
            const errors = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Revisa los datos de la venta.');
            throw new Error(errors);
        }

        if (!response.ok) {
            throw new Error(data.message || 'No se pudo registrar la venta.');
        }

        return data;
    };

    const submitSale = async (status, amountPaid = 0) => {
        if (submitting) {
            return;
        }

        if (items.length === 0 && pendingSaleId === null) {
            showError('Agrega al menos un producto a la venta.');
            return;
        }

        submitting = true;
        paymentConfirm.disabled = true;

        try {
            let data;

            if (pendingSaleId !== null && status === 'paid') {
                data = await postJson(config.routes.charge.replace('__SALE__', pendingSaleId), {
                    amount_paid: round(amountPaid),
                    payment_method: paymentMethod?.value || 'cash',
                });
            } else {
                data = await postJson(config.routes.store, buildPayload(status, amountPaid));
            }

            paymentDialog.close();

            if (data?.sale) {
                showLastSaleIndicator(data.sale.id, data.sale.folio);

                if (config.routes.ticketData) {
                    try {
                        const ticketResponse = await fetch(config.routes.ticketData.replace('__SALE__', data.sale.id), {
                            headers: { Accept: 'application/json' },
                        });

                        if (ticketResponse.ok) {
                            lastSaleData = await ticketResponse.json();
                        }
                    } catch (error) {
                        // No bloquea el flujo si no se pueden cargar los detalles del ticket
                    }
                }

                resetSale();
            } else if (data?.redirect) {
                window.location.href = data.redirect;
            }
        } catch (error) {
            if (paymentError) {
                paymentError.textContent = error.message;
                paymentError.classList.remove('hidden');
            }

            showError(error.message);
        } finally {
            submitting = false;
            paymentConfirm.disabled = false;
        }
    };

    const updateChange = () => {
        const total = totals().total;
        const paid = Number(paymentAmount.value) || 0;
        const change = round(paid - total);
        const missing = change < 0;

        paymentChange.textContent = missing ? formatMoney(0) : formatMoney(change);
        paymentChange.classList.toggle('text-mz-error', missing);
        paymentChange.classList.toggle('text-mz-text-primary', !missing);
        paymentError.classList.toggle('hidden', !missing);
        paymentError.textContent = missing ? `Faltan ${formatMoney(Math.abs(change))} para cubrir el total.` : '';
        paymentConfirm.disabled = missing || submitting;
    };

    const openPayment = () => {
        if (items.length === 0 && pendingSaleId === null) {
            showError('Agrega al menos un producto a la venta.');
            return;
        }

        const total = pendingSaleId !== null ? Number(paymentTotal.dataset.total || 0) : totals().total;
        paymentTotal.textContent = formatMoney(total);
        paymentTotal.dataset.total = String(total);
        paymentAmount.value = total.toFixed(2);
        paymentError.classList.add('hidden');
        updateChange();

        // Mostrar/ocultar campo "Pago con" según método de pago
        if (paymentMethod) {
            const showAmount = paymentMethod.value === 'cash' || paymentMethod.value === 'mixed';
            const amountGroup = document.getElementById('payment-amount-group');
            if (amountGroup) {
                amountGroup.style.display = showAmount ? 'block' : 'none';
            }
        }

        paymentDialog.showModal();
        paymentAmount.focus();
        paymentAmount.select();
    };

    const loadPendingSale = async (saleId) => {
        try {
            const response = await fetch(config.routes.show.replace('__SALE__', saleId), {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error('La venta pendiente ya no está disponible.');
            }

            const data = await response.json();
            pendingSaleId = Number(data.sale.id);
            items = data.sale.items.map((item) => ({
                product_id: item.product_id,
                name: item.name,
                sku: item.sku,
                barcode: item.barcode,
                category: item.category,
                price: Number(item.unit_price),
                wholesale_price: null,
                unit_price: Number(item.unit_price),
                manual_price: null,
                quantity: Number(item.quantity),
                tax_rate: Number(item.tax_rate) || 0,
                stock: null,
                min_stock: 0,
                track_inventory: false,
            }));

            paymentTotal.dataset.total = String(data.sale.total);
            pendingBanner.classList.remove('hidden');
            wholesaleToggle.disabled = true;
            renderCart();
            openPayment();
        } catch (error) {
            showError(error.message);
        }
    };

    searchInput?.addEventListener('input', () => {
        // Si detectamos un input muy rápido (típico de escáner), no queremos usar el debounce largo
        // Pero para mantener la lógica original, reduciremos el tiempo a 50ms para respuesta inmediata
        clearTimeout(searchTimer);
        searchTimer = setTimeout(searchNow, 50);
    });

    searchInput?.addEventListener('keydown', async (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            
            // Forzar búsqueda inmediata al presionar Enter (escáner)
            clearTimeout(searchTimer);
            const products = await searchNow();

            if (products.length > 0) {
                addProduct(products[0]);
            } else {
                showError('Producto no encontrado.');
            }
        }
    });

    resultsBox?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-product-id]');

        if (!button) {
            return;
        }

        const product = productCache.get(Number(button.dataset.productId));

        if (product) {
            addProduct(product);
        }
    });

    document.addEventListener('click', (event) => {
        if (!resultsBox || resultsBox.classList.contains('hidden')) {
            return;
        }

        if (!resultsBox.contains(event.target) && event.target !== searchInput) {
            resultsBox.classList.add('hidden');
        }
    });

    document.querySelectorAll('[data-add-product]').forEach((button) => {
        button.addEventListener('click', () => {
            try {
                addProduct(JSON.parse(button.dataset.addProduct));
            } catch (error) {
                showError('No se pudo agregar el producto.');
            }
        });
    });

    cartBody?.addEventListener('input', (event) => {
        const input = event.target.closest('[data-action]');

        if (!input) {
            return;
        }

        const index = Number(input.dataset.index);
        const item = items[index];

        if (!item) {
            return;
        }

        if (input.dataset.action === 'quantity') {
            let quantity = Math.max(1, Math.floor(Number(input.value) || 1));

            if (item.track_inventory && quantity > Number(item.stock)) {
                quantity = Number(item.stock);
                showError(`Solo hay ${item.stock} piezas de «${item.name}».`);
            }

            item.quantity = quantity;
            input.value = String(quantity);
        }

        if (input.dataset.action === 'price') {
            item.unit_price = round(Math.max(0, Number(input.value) || 0));
            item.manual_price = item.unit_price;
        }

        renderTotals();

        const row = input.closest('tr');

        if (row) {
            const cells = row.querySelectorAll('td');
            cells[3].textContent = formatMoney(lineTotal(item));
        }
    });

    cartBody?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action="remove"]');

        if (!button) {
            return;
        }

        items.splice(Number(button.dataset.index), 1);
        renderCart();
    });

    wholesaleToggle?.addEventListener('change', () => setWholesale(wholesaleToggle.checked));

    document.querySelector('[data-amount-exact]')?.addEventListener('click', () => {
        paymentAmount.value = Number(paymentTotal.dataset.total || 0).toFixed(2);
        updateChange();
    });

    document.querySelectorAll('[data-amount]').forEach((button) => {
        button.addEventListener('click', () => {
            paymentAmount.value = Number(button.dataset.amount).toFixed(2);
            updateChange();
        });
    });

    paymentAmount?.addEventListener('input', updateChange);

    const reopenMyDay = async () => {
        try {
            const response = await fetch(config.routes.reopen_my_day, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
            });

            if (response.ok) {
                window.location.reload();
            } else {
                const data = await response.json().catch(() => ({}));
                showError(data.message || 'No se pudo reabrir el corte.');
            }
        } catch (error) {
            showError('No se pudo reabrir el corte. Revisa tu conexión.');
        }
    };

    document.getElementById('pos-reopen-day')?.addEventListener('click', reopenMyDay);

    document.getElementById('pos-charge')?.addEventListener('click', openPayment);

    document.getElementById('pos-pending')?.addEventListener('click', async () => {
        if (items.length === 0) {
            showError('Agrega al menos un producto a la venta.');
            return;
        }

        if (!window.confirm('¿Dejar esta venta pendiente? No se descontará stock ni se moverá caja hasta cobrarla.')) {
            return;
        }

        submitting = true;

        try {
            await postJson(config.routes.store, buildPayload('pending', 0));
            window.location.href = config.routes.pending;
        } catch (error) {
            showError(error.message);
        } finally {
            submitting = false;
        }
    });

    document.getElementById('pos-new-sale')?.addEventListener('click', () => {
        resetSale();
        clearLastSale();
    });
    document.getElementById('pos-clear')?.addEventListener('click', () => {
        if (items.length > 0 && !window.confirm('¿Eliminar la venta actual?')) {
            return;
        }

        resetSale();
        clearLastSale();
    });

    document.getElementById('pos-print-ticket')?.addEventListener('click', printLastSaleTicket);

    paymentConfirm?.addEventListener('click', () => {
        const total = Number(paymentTotal.dataset.total || 0);
        const paid = Number(paymentAmount.value) || 0;

        if (paid < total) {
            updateChange();
            return;
        }

        submitSale('paid', paid);
    });

    document.querySelectorAll('[data-close-dialog]').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById(button.dataset.closeDialog)?.close();
        });
    });

    document.querySelectorAll('[data-cash-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const type = button.dataset.cashOpen;
            cashType.value = type;
            cashTitle.textContent = type === 'in' ? 'Entrada de efectivo' : 'Salida de efectivo';

            Array.from(cashCategory.options).forEach((option) => {
                const matches = option.dataset.type === type || option.value === 'other';
                option.hidden = !matches;
                option.disabled = !matches;
            });

            const first = Array.from(cashCategory.options).find((option) => !option.disabled);
            cashCategory.value = first?.value ?? '';

            cashError?.classList.add('hidden');

            cashDialog.showModal();
        });
    });

    cashCategory?.addEventListener('change', () => {
        if (cashCategory.value === 'other') {
            return;
        }

        cashType.value = cashCategory.selectedOptions[0]?.dataset.type ?? cashType.value;
    });

    // Formato de miles y centavos al salir del campo de monto
    const cashAmount = document.getElementById('cash-amount');
    cashAmount?.addEventListener('blur', () => {
        cashAmount.value = formatCashAmount(cashAmount.value);
    });

    // Envía el movimiento por AJAX para mostrar los errores de validación dentro del modal
    const cashForm = document.getElementById('cash-form');
    const cashSaveButton = cashForm?.querySelector('button[type="submit"]');
    let cashSubmitting = false;

    const setCashSubmitting = (value) => {
        cashSubmitting = value;

        if (value) {
            cashSaveButton?.setAttribute('disabled', 'disabled');
            cashSaveButton?.classList.add('opacity-75', 'cursor-not-allowed');
        } else {
            cashSaveButton?.removeAttribute('disabled');
            cashSaveButton?.classList.remove('opacity-75', 'cursor-not-allowed');
        }
    };

    const showCashError = (message) => {
        if (!cashError) {
            return;
        }

        cashError.textContent = message;
        cashError.classList.remove('hidden');
    };

    cashForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (cashSubmitting) {
            return; // Evita doble envío por clic rápido
        }

        cashError?.classList.add('hidden');

        const raw = Number(parseCashAmount(cashAmount?.value));

        if (!Number.isFinite(raw) || raw <= 0) {
            showCashError('Escribe un monto válido mayor a cero.');
            cashAmount?.focus();
            return;
        }

        cashAmount.value = String(raw);
        setCashSubmitting(true);

        try {
            const response = await fetch(cashForm.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: new FormData(cashForm),
                redirect: 'manual',
            });

            // Laravel responde con un redirect (302) cuando el movimiento se guarda
            if (response.type === 'opaqueredirect' || response.redirected || response.ok) {
                cashForm.reset();
                cashDialog?.close();
                window.location.href = config.routes.cash;
                return;
            }

            const data = await response.json().catch(() => ({}));
            const errors = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : (data.message || 'No se pudo registrar el movimiento.');

            showCashError(errors);
        } catch (error) {
            showCashError(error.message || 'No se pudo registrar el movimiento. Revisa tu conexión.');
        } finally {
            setCashSubmitting(false);
        }
    });

    // Re-habilitar el botón si el diálogo se cierra sin enviar (evita quedar bloqueado)
    cashDialog?.addEventListener('close', () => {
        setCashSubmitting(false);
        cashForm?.reset();
        cashError?.classList.add('hidden');
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'F12' && !paymentDialog.open && !cashDialog?.open) {
            event.preventDefault();
            openPayment();
        }

        if (event.key === 'F6' && !paymentDialog.open && !cashDialog?.open) {
            event.preventDefault();
            if (items.length > 0) {
                if (window.confirm('¿Dejar esta venta pendiente? No se descontará stock ni se moverá caja hasta cobrarla.')) {
                    document.getElementById('pos-pending')?.click();
                }
            }
        }

        if (event.key === 'F7' && !paymentDialog.open && !cashDialog?.open) {
            event.preventDefault();
            document.querySelector('[data-cash-open="in"]')?.click();
        }

        if (event.key === 'F8' && !paymentDialog.open && !cashDialog?.open) {
            event.preventDefault();
            document.querySelector('[data-cash-open="out"]')?.click();
        }

        if (event.key === 'F11' && !paymentDialog.open && !cashDialog?.open) {
            event.preventDefault();
            if (permissions.wholesale) {
                wholesaleToggle.checked = !wholesaleToggle.checked;
                setWholesale(wholesaleToggle.checked);
            }
        }

        if (event.key === 'Delete' && !paymentDialog.open && !cashDialog?.open) {
            event.preventDefault();
            if (items.length > 0 && !pendingSaleId) {
                if (window.confirm('¿Eliminar el último artículo de la venta?')) {
                    items.pop();
                    renderCart();
                }
            }
        }

        if (event.key === 'Escape' && !paymentDialog.open && !cashDialog?.open) {
            resetSale();
        }
    });

    // Mostrar/ocultar campo "Pago con" según método de pago
    paymentMethod?.addEventListener('change', () => {
        const showAmount = paymentMethod.value === 'cash' || paymentMethod.value === 'mixed';
        const amountGroup = document.getElementById('payment-amount-group');
        if (amountGroup) {
            amountGroup.style.display = showAmount ? 'block' : 'none';
        }
        if (!showAmount) {
            paymentAmount.value = '0.00';
            paymentChange.textContent = '$0.00';
            paymentError.classList.add('hidden');
        } else {
            updateChange();
        }
    });

    renderCart();

    const pendingId = new URLSearchParams(window.location.search).get('pending');

    if (pendingId) {
        loadPendingSale(pendingId);
    }
}
