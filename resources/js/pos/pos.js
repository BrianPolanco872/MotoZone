import numeral from 'numeral';

const configElement = document.getElementById('pos-config');

if (configElement) {
    const config = JSON.parse(configElement.textContent);
    const permissions = config.permissions;
    const csrf = config.csrf;

    const searchInput = document.getElementById('pos-search');
    const resultsBox = document.getElementById('pos-results');
    const cartBody = document.getElementById('pos-cart-body');
    const cartTable = document.getElementById('pos-cart-table');
    const emptyState = document.getElementById('pos-empty');
    const emptyCell = document.getElementById('pos-empty-cell');
    const cartStore2Heading = document.getElementById('pos-cart-store2-heading');
    const itemsCount = document.getElementById('pos-items-count');
    const totalEl = document.getElementById('pos-total');
    const imageDialog = document.getElementById('product-image-dialog');
    const imagePreview = document.getElementById('product-image-preview');
    const imageTitle = document.getElementById('product-image-title');
    const imageError = document.getElementById('product-image-error');
    let imageTrigger = null;
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
    const cashTitle = document.getElementById('cash-title');
    const cashError = document.getElementById('cash-error');
    const startDayDialog = document.getElementById('start-day-dialog');
    const startDayForm = document.getElementById('start-day-form');
    const startDayAmount = document.getElementById('start-day-amount');
    const startDayError = document.getElementById('start-day-error');

    const daySalesDialog = document.getElementById('day-sales-dialog');
    const daySalesSearch = document.getElementById('day-sales-search');
    const daySalesDate = document.getElementById('day-sales-date');
    const daySalesRows = document.getElementById('day-sales-rows');
    const daySalesEmpty = document.getElementById('day-sales-empty');
    const daySalesItems = document.getElementById('day-sales-items');
    const daySalesItemsEmpty = document.getElementById('day-sales-items-empty');
    const daySalesCancelled = document.getElementById('day-sales-cancelled');
    const daySalesReturn = document.getElementById('day-sales-return');
    const daySalesCancel = document.getElementById('day-sales-cancel');
    const daySalesPrint = document.getElementById('day-sales-print');
    let daySales = [];
    let selectedDaySaleId = null;
    let selectedDayItemId = null;
    let daySalesTimer = null;

    // Producto común (artículo libre)
    const customProductDialog = document.getElementById('custom-product-dialog');
    const customProductForm = document.getElementById('custom-product-form');
    const customProductName = document.getElementById('custom-product-name');
    const customProductPrice = document.getElementById('custom-product-price');
    const customProductQuantity = document.getElementById('custom-product-quantity');
    const customProductError = document.getElementById('custom-product-error');
    const bulkDialog = document.getElementById('bulk-dialog');
    const bulkForm = document.getElementById('bulk-form');
    const bulkCloseBtn = document.getElementById('bulk-close-btn');
    const bulkName = document.getElementById('bulk-name');
    const bulkPrice = document.getElementById('bulk-price');
    const bulkStockInfo = document.getElementById('bulk-stock-info');
    const bulkAvailableStock = document.getElementById('bulk-available-stock');
    const bulkQuantity = document.getElementById('bulk-quantity');
    const bulkAmount = document.getElementById('bulk-amount');
    const bulkTotal = document.getElementById('bulk-total');
    const bulkError = document.getElementById('bulk-error');
    const bulkCancel = document.getElementById('bulk-cancel');
    const bulkAccept = document.getElementById('bulk-accept');
    let bulkProduct = null;
    let isUpdatingBulk = false;

    // Estado para detectar escáner
    let isScanning = false;
    let scannerBuffer = '';

    const money = new Intl.NumberFormat('es-MX', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const formatMoney = (value) => `$${money.format(Number(value) || 0)}`;
    const round = (value) => Math.round((Number(value) || 0) * 100) / 100;

    // Formatea el monto con separador de miles, sin forzar decimales (respeta "1,000")
    const formatCashAmount = (value) => {
        if (value === null || value === undefined || value === '') return value;
        const num = Number(String(value).replace(/,/g, ''));
        if (!Number.isFinite(num)) return value;
        const str = String(num);
        const [intPart, decPart] = str.split('.');
        const formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return decPart !== undefined ? `${formattedInt}.${decPart}` : formattedInt;
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
    let currentSearch = null;

    const closeSearch = () => {
        clearTimeout(searchTimer);
        searchTimer = null;
        currentSearch = null;
        productCache.clear();
        resultsBox?.classList.add('hidden');
    };
    let submitting = false;
    let lastSale = config.lastSale ?? null;

    const showError = (message) => {
        if (!errorBox) {
            return;
        }

        errorBox.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="size-6 shrink-0 rounded-full bg-mz-error/20 flex items-center justify-center text-mz-error">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-mz-text-primary">No se pudo completar la operación</p>
                    <p class="mt-0.5 text-xs text-mz-text-secondary">${escapeHtml(message)}</p>
                </div>
                <button type="button" onclick="this.closest('#pos-error').classList.add('hidden')" class="text-mz-text-muted hover:text-mz-text-primary p-1 rounded-md transition" aria-label="Cerrar"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
        `;
        errorBox.classList.remove('hidden');
        errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    const clearError = () => errorBox?.classList.add('hidden');

    const renderLastSale = () => {
        const indicator = document.getElementById('last-sale-indicator');
        const folio = document.getElementById('last-sale-folio');
        const printButton = document.getElementById('pos-print-ticket');
        const paid = document.getElementById('last-sale-paid');
        const change = document.getElementById('last-sale-change');

        indicator?.classList.toggle('hidden', ! lastSale);
        if (folio) {
            folio.textContent = lastSale?.folio ?? '';
        }
        if (printButton) {
            printButton.disabled = ! lastSale?.id || ! permissions.tickets_own || ! config.routes.ticket;
        }
        if (paid) {
            paid.textContent = lastSale?.amount_paid == null ? '—' : formatMoney(lastSale.amount_paid);
        }
        if (change) {
            change.textContent = lastSale?.change_amount == null ? '—' : formatMoney(lastSale.change_amount);
        }
    };

    const printTicketById = (saleId) => {
        if (! saleId || ! permissions.tickets_own || ! config.routes.ticket) {
            return;
        }

        const url = `${config.routes.ticket.replace('__SALE__', saleId)}?print=1`;
        const ticketWindow = window.open('', '_blank');
        if (ticketWindow) {
            ticketWindow.opener = null;
            ticketWindow.location.replace(url);
            return;
        }

        const warning = items.length > 0 ? ' Se perderán los productos no guardados del carrito.' : '';
        if (window.confirm(`El navegador bloqueó la nueva pestaña. ¿Abrir el ticket en esta pestaña?${warning}`)) {
            window.location.assign(url);
        }
    };

    const selectedDaySale = () => daySales.find((sale) => sale.id === selectedDaySaleId) ?? null;

    const loadDaySales = async () => {
        if (!config.routes.dayList) {
            return;
        }

        const params = new URLSearchParams();
        if (daySalesDate?.value) params.set('date', daySalesDate.value);
        if (daySalesSearch?.value.trim()) params.set('search', daySalesSearch.value.trim());

        const response = await fetch(`${config.routes.dayList}?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('No se pudieron cargar las ventas del día.');
        }

        const data = await response.json();
        daySales = data.sales ?? [];

        if (!daySales.some((sale) => sale.id === selectedDaySaleId)) {
            selectedDaySaleId = null;
            selectedDayItemId = null;
        }

        renderDaySales();
        renderDaySaleDetail();
    };

    const renderDaySales = () => {
        if (!daySalesRows) {
            return;
        }

        daySalesRows.innerHTML = daySales.map((sale) => `
            <tr data-day-sale="${sale.id}" class="cursor-pointer border-t border-mz-border-light/60 transition-colors ${sale.id === selectedDaySaleId ? 'bg-mz-accent-primary/20' : 'hover:bg-mz-bg-surface-2'}">
                <td class="px-3 py-2 font-mono font-bold text-mz-text-primary">${escapeHtml(sale.code)}${sale.cancelled ? ' <span class="text-[9px] font-bold text-mz-error">CANCELADO</span>' : ''}</td>
                <td class="px-2 py-2 tabular-nums text-mz-text-secondary">${sale.item_count}</td>
                <td class="px-2 py-2 font-mono text-mz-text-secondary">${escapeHtml(sale.time ?? '—')}</td>
                <td class="px-3 py-2 text-right font-mono font-bold text-mz-text-primary">${formatMoney(sale.total)}</td>
            </tr>
        `).join('');

        if (daySalesEmpty) {
            daySalesEmpty.hidden = daySales.length > 0;
        }
    };

    const renderDaySaleDetail = () => {
        const sale = selectedDaySale();
        const setText = (id, value) => {
            const element = document.getElementById(id);
            if (element) element.textContent = value;
        };

        setText('day-sales-detail-code', sale ? sale.code : '—');
        setText('day-sales-detail-cashier', sale ? (sale.cashier ?? '—') : '—');
        setText('day-sales-detail-client', sale ? (sale.client ?? '—') : '—');
        setText('day-sales-total', sale ? formatMoney(sale.total) : '$0.00');
        setText('day-sales-paid', sale ? formatMoney(sale.amount_paid) : '$0.00');

        if (daySalesCancelled) {
            daySalesCancelled.hidden = ! sale?.cancelled;
        }

        if (!daySalesItems) {
            return;
        }

        const items = sale?.items ?? [];
        daySalesItems.innerHTML = items.map((item) => {
            const available = Number(item.available_quantity) > 0;
            return `<tr data-day-item="${item.id}" class="cursor-pointer border-t border-mz-border-light/60 transition-colors ${item.id === selectedDayItemId ? 'bg-mz-warning/20' : 'hover:bg-mz-bg-surface-2'} ${available ? '' : 'opacity-50'}">
                <td class="px-3 py-2 tabular-nums text-mz-text-primary">${formatQuantity(item.quantity)}</td>
                <td class="px-2 py-2 text-mz-text-secondary">${escapeHtml(item.description ?? '')}${item.sku ? ` <span class="font-mono text-[10px] text-mz-text-muted">${escapeHtml(item.sku)}</span>` : ''}${Number(item.returned_quantity) > 0 ? ` <span class="text-[10px] font-bold text-mz-success">Devuelto: ${formatQuantity(item.returned_quantity)}</span>` : ''}</td>
                <td class="px-3 py-2 text-right font-mono text-mz-text-primary">${formatMoney(item.line_total)}</td>
            </tr>`;
        }).join('');

        if (daySalesItemsEmpty) {
            daySalesItemsEmpty.hidden = items.length > 0;
        }

        if (daySalesReturn) {
            daySalesReturn.disabled = ! sale || sale.cancelled
                || ! items.some((item) => item.id === selectedDayItemId && Number(item.available_quantity) > 0);
        }
        if (daySalesCancel) {
            daySalesCancel.disabled = ! sale || sale.cancelled;
        }
        if (daySalesPrint) {
            daySalesPrint.disabled = ! sale || ! permissions.tickets_own || ! config.routes.ticket;
        }
    };

    const selectDaySale = (saleId) => {
        selectedDaySaleId = saleId;
        selectedDayItemId = null;
        renderDaySales();
        renderDaySaleDetail();
    };

    const returnSelectedItem = async () => {
        const sale = selectedDaySale();
        if (!sale || !selectedDayItemId || !config.routes.returnItem) {
            return;
        }

        if (! window.confirm('¿Devolver este artículo al inventario y descontarlo de la venta?')) {
            return;
        }

        const url = config.routes.returnItem
            .replace('__SALE__', sale.id)
            .replace('__ITEM__', selectedDayItemId);

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({}),
            });

            if (! response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.message || 'No se pudo devolver el artículo.');
            }

            await loadDaySales();
            selectedDaySaleId = sale.id;
            const updated = selectedDaySale();
            const stillAvailable = updated?.items.some((item) => item.id === selectedDayItemId && Number(item.available_quantity) > 0);
            if (! stillAvailable) {
                selectedDayItemId = null;
            }
            renderDaySales();
            renderDaySaleDetail();
        } catch (error) {
            showError(error.message);
        }
    };

    const cancelSelectedSale = async () => {
        const sale = selectedDaySale();
        if (!sale || !config.routes.cancel) {
            return;
        }

        if (! window.confirm(`¿Cancelar la venta ${sale.code}? Se devolverán todos sus artículos al inventario.`)) {
            return;
        }

        try {
            const response = await fetch(config.routes.cancel.replace('__SALE__', sale.id), {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            });

            if (! response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.message || 'No se pudo cancelar la venta.');
            }

            await loadDaySales();
            selectedDaySaleId = sale.id;
            selectedDayItemId = null;
            renderDaySales();
            renderDaySaleDetail();
        } catch (error) {
            showError(error.message);
        }
    };

    const lineTotal = (item) => Math.round(Math.round(item.unit_price * 100) * Math.round(item.quantity * 1000) / 1000) / 100;
    const formatQuantity = (value) => String(Number(Number(value).toFixed(3)));
    const validQuantity = (value, mode = 'unit') => {
        return /^\d+(?:\.\d{1,3})?$/.test(String(value)) && Number(value) > 0 && Number(value) <= 9999
            && (mode === 'bulk' || Number.isInteger(Number(value)));
    };
    const stockFor = (product) => product.sale_mode === 'kit'
        ? (product.available_stock == null ? null : Number(product.available_stock))
        : (product.track_inventory ? Number(product.available_stock ?? product.stock) : null);

    const totals = () => {
        let subtotal = 0;
        let tax = 0;
        let total = 0;

        items.forEach((item) => {
            const line = lineTotal(item);
            const rate = Number(item.tax_rate) || 0;
            const lineSubtotal = round(line / (1 + Math.max(0, rate)));

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
        totalEl.textContent = formatMoney(result.total);
        itemsCount.textContent = `${items.length} ${items.length === 1 ? 'renglón' : 'renglones'}`;
        emptyState.classList.toggle('hidden', items.length > 0);
    };

    const renderCart = () => {
        cartBody.innerHTML = '';
        cartTable.classList.toggle('h-full', items.length === 0);
        const showStore2 = items.some((item) => item.store_2_stock != null && item.store_2_stock !== '');
        cartStore2Heading.hidden = !showStore2;
        emptyCell.colSpan = showStore2 ? 11 : 10;

        items.forEach((item, index) => {
            const row = document.createElement('tr');
            row.className = 'group transition-colors duration-150 hover:bg-mz-bg-surface-2/40 border-b border-mz-border-light/50 last:border-b-0';
            const locked = pendingSaleId !== null;
            const priceControl = permissions.price_edit && !locked
                ? `<input type="number" step="0.01" min="0" value="${item.unit_price.toFixed(2)}" data-action="price" data-index="${index}" class="h-9 w-24 rounded-lg border border-mz-border-light bg-mz-bg-surface-2 px-2.5 text-right text-sm font-semibold tabular-nums text-mz-text-primary focus:border-mz-accent-primary focus:bg-mz-bg-surface focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/20 transition-all">`
                : `<span class="tabular-nums font-medium text-mz-text-secondary">${formatMoney(item.unit_price)}</span>`;

            const quantityControl = !locked
                ? `<input type="number" step="${item.sale_mode === 'bulk' ? '0.001' : '1'}" min="${item.sale_mode === 'bulk' ? '0.001' : '1'}" max="9999" value="${formatQuantity(item.quantity)}" aria-label="Cantidad de ${escapeHtml(item.name)}" data-action="quantity" data-index="${index}" class="h-9 w-24 rounded-lg border border-mz-border-light bg-mz-bg-surface-2 px-2 text-center text-sm font-bold tabular-nums text-mz-text-primary focus:border-mz-accent-primary focus:bg-mz-bg-surface focus:outline-none focus:ring-2 focus:ring-mz-accent-primary/20 transition-all">`
                : `<span class="tabular-nums font-bold text-mz-text-secondary">${formatQuantity(item.quantity)}</span>`;

            const stockLabel = item.track_inventory
                ? formatQuantity(item.stock ?? 0)
                : 'Sin control';

            row.innerHTML = `
                <td class="w-32 px-4 py-3 align-middle">
                    ${item.image_url ? `<button type="button" data-view-image="${index}" class="flex size-24 shrink-0 items-center justify-center rounded-lg border border-mz-border-light bg-mz-bg-primary focus-visible:outline-2 focus-visible:outline-mz-accent-primary" aria-label="Ampliar foto de ${escapeHtml(item.name)}"><img data-product-thumbnail src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.name)}" class="size-24 rounded-lg object-contain"><span hidden class="text-xs text-mz-text-muted">Sin foto</span></button>` : '<span class="flex size-24 shrink-0 items-center justify-center rounded-lg border border-mz-border-light bg-mz-bg-primary text-xs text-mz-text-muted">Sin foto</span>'}
                </td>
                <td class="min-w-36 max-w-48 break-all px-4 py-3 align-middle font-mono text-xs text-mz-text-muted">${escapeHtml(item.barcode || item.barcode2 || item.sku || 'S/C')}</td>
                <td class="min-w-72 max-w-sm whitespace-normal break-words px-4 py-3 align-middle font-semibold leading-relaxed text-mz-text-primary">${escapeHtml(item.name)}</td>
                <td class="min-w-40 max-w-56 whitespace-normal break-words px-4 py-3 align-middle text-mz-text-secondary">${escapeHtml(item.category_name || '-')}</td>
                <td class="min-w-32 whitespace-nowrap px-4 py-3 align-middle text-right">${priceControl}${item.sale_mode === 'bulk' ? '<span class="block text-[11px] text-mz-text-muted">por unidad</span>' : ''}</td>
                <td class="whitespace-nowrap px-4 py-3 align-middle text-center">${quantityControl}</td>
                <td data-line-total class="min-w-32 whitespace-nowrap px-4 py-3 align-middle text-right font-bold tabular-nums text-mz-text-primary font-display text-base">${formatMoney(lineTotal(item))}</td>
                <td class="min-w-32 whitespace-nowrap px-4 py-3 align-middle text-right text-sm">
                    <span class="tabular-nums ${item.track_inventory && item.stock <= (item.min_stock ?? 0) ? 'text-mz-warning' : 'text-mz-text-secondary'}">${stockLabel}</span>
                </td>
                <td class="min-w-32 whitespace-nowrap px-4 py-3 align-middle text-right tabular-nums text-mz-text-secondary">${item.warehouse_stock == null || item.warehouse_stock === '' ? '-' : formatQuantity(item.warehouse_stock)}</td>
                ${showStore2 ? `<td class="min-w-32 whitespace-nowrap px-4 py-3 align-middle text-right tabular-nums text-mz-text-secondary">${item.store_2_stock == null || item.store_2_stock === '' ? '' : formatQuantity(item.store_2_stock)}</td>` : ''}
                <td class="px-4 py-3 align-middle text-right">
                    ${locked ? '' : `<button type="button" data-action="remove" data-index="${index}" class="inline-flex size-8 items-center justify-center rounded-lg border border-mz-border-light text-mz-text-muted transition-all duration-150 hover:border-mz-error/50 hover:bg-mz-error/15 hover:text-mz-error active:scale-95" title="Quitar artículo" aria-label="Quitar ${escapeHtml(item.name)}"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>`}
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

    const isBulkProduct = (product) => {
        if (!product) return false;
        const mode = String(product.sale_mode || '').trim().toLowerCase();
        return mode === 'bulk' || mode === 'granel';
    };

    const addProduct = (product, quantity = 1, confirmedBulk = false) => {
        clearError();

        const rejectAddition = (message) => {
            if (confirmedBulk && bulkError) {
                bulkError.textContent = message;
                bulkError.classList.remove('hidden');
            } else {
                showError(message);
            }
            return false;
        };

        if (pendingSaleId !== null) {
            return rejectAddition('Termina o cancela la venta pendiente antes de agregar otros productos.');
        }

        if (isBulkProduct(product) && !confirmedBulk) {
            return openBulkModal(product, quantity);
        }

        const stock = stockFor(product);

        const existing = items.find((item) => item.product_id === product.id);
        const nextQuantity = (Math.round((existing?.quantity ?? 0) * 1000) + Math.round(quantity * 1000)) / 1000;

        if (!validQuantity(quantity, product.sale_mode) || !validQuantity(nextQuantity, product.sale_mode)) {
            return rejectAddition(confirmedBulk
                ? 'La cantidad acumulada no puede superar 9999.'
                : 'La cantidad debe ser positiva, hasta 9999; solo granel permite hasta 3 decimales.');
        }

        if (existing) {
            existing.quantity = nextQuantity;
            existing.stock = stock;
            existing.track_inventory = stock !== null;
        } else {
            items.push({
                product_id: product.id,
                name: product.name,
                sku: product.sku,
                barcode: product.barcode,
                barcode2: product.barcode2,
                image_url: product.image_url ?? null,
                category_name: product.category_name ?? null,
                warehouse_stock: product.warehouse_stock ?? null,
                store_2_stock: product.store_2_stock ?? null,
                price: round(product.price),
                wholesale_price: product.wholesale_price === null ? null : round(product.wholesale_price),
                unit_price: priceFor(product),
                manual_price: null,
                sale_mode: product.sale_mode ?? 'unit',
                quantity,
                tax_rate: Number(product.tax_rate) || 0,
                stock,
                min_stock: Number(product.min_stock) || 0,
                track_inventory: stock !== null,
            });
        }

        renderCart();
        searchInput.value = '';
        closeSearch();
        if (!confirmedBulk) searchInput.focus();
        return true;
    };

    const openBulkModal = (product, quantity = 1) => {
        if (!bulkDialog) {
            return false;
        }

        if (bulkDialog.open) {
            return false;
        }

        bulkProduct = product;
        if (bulkName) {
            bulkName.textContent = product.name;
        }

        const existingItem = items.find((item) => item.product_id === bulkProduct.id);
        const unitPrice = existingItem ? existingItem.unit_price : priceFor(bulkProduct);
        if (bulkPrice) {
            bulkPrice.textContent = formatMoney(unitPrice);
        }

        const stock = stockFor(bulkProduct);
        if (bulkAvailableStock) {
            bulkAvailableStock.textContent = stock !== null ? formatQuantity(stock) : 'Ilimitada';
        }
        if (bulkStockInfo) {
            bulkStockInfo.classList.remove('hidden');
        }

        if (bulkError) {
            bulkError.classList.add('hidden');
            bulkError.textContent = '';
        }

        isUpdatingBulk = true;
        if (bulkQuantity) {
            bulkQuantity.value = Number(quantity) === 1 ? '1.00' : String(quantity);
        }
        const initialAmount = lineTotal({ unit_price: unitPrice, quantity });
        if (bulkAmount) {
            bulkAmount.value = initialAmount.toFixed(2);
        }
        if (bulkTotal) {
            bulkTotal.textContent = formatMoney(initialAmount);
        }
        isUpdatingBulk = false;

        closeSearch();
        if (searchInput) {
            searchInput.value = '';
        }

        bulkDialog.showModal();
        bulkQuantity?.focus();
        bulkQuantity?.select();
        setTimeout(() => {
            bulkQuantity?.focus();
            bulkQuantity?.select();
        }, 50);

        return false;
    };

    const validateBulkStock = () => {
        bulkError?.classList.add('hidden');
        if (bulkError) {
            bulkError.textContent = '';
        }
        return true;
    };

    const closeBulkModal = () => {
        bulkProduct = null;
        if (bulkDialog?.open) {
            bulkDialog.close();
        }
        searchInput?.focus();
    };

    const confirmBulk = () => {
        if (!bulkProduct) {
            return;
        }

        const rawVal = bulkQuantity?.value.trim() ?? '';
        const qty = parseFloat(rawVal);

        if (isNaN(qty) || qty <= 0 || !validQuantity(rawVal, 'bulk')) {
            if (bulkError) {
                bulkError.textContent = 'Escribe una cantidad de 0.001 a 9999, con hasta 3 decimales.';
                bulkError.classList.remove('hidden');
            }
            bulkQuantity?.focus();
            return;
        }

        if (!validateBulkStock(qty)) {
            bulkQuantity?.focus();
            return;
        }

        if (addProduct(bulkProduct, qty, true)) {
            closeBulkModal();
        } else {
            bulkQuantity?.focus();
        }
    };

    bulkQuantity?.addEventListener('input', () => {
        if (isUpdatingBulk || !bulkProduct) {
            return;
        }

        isUpdatingBulk = true;
        const raw = bulkQuantity.value.trim();
        const qty = parseFloat(raw);
        const existingItem = items.find((item) => item.product_id === bulkProduct.id);
        const unitPrice = existingItem ? existingItem.unit_price : priceFor(bulkProduct);

        if (!isNaN(qty) && qty > 0 && unitPrice > 0) {
            const amount = Math.round(qty * unitPrice * 100) / 100;
            if (bulkAmount) {
                bulkAmount.value = amount.toFixed(2);
            }
            if (bulkTotal) {
                bulkTotal.textContent = formatMoney(amount);
            }
            validateBulkStock(qty);
        } else {
            if (bulkAmount) {
                bulkAmount.value = '';
            }
            if (bulkTotal) {
                bulkTotal.textContent = formatMoney(0);
            }
            if (raw !== '') {
                if (bulkError) {
                    bulkError.textContent = 'Escribe una cantidad válida mayor a 0.';
                    bulkError.classList.remove('hidden');
                }
            } else if (bulkError) {
                bulkError.classList.add('hidden');
            }
        }
        isUpdatingBulk = false;
    });

    bulkAmount?.addEventListener('input', () => {
        if (isUpdatingBulk || !bulkProduct) {
            return;
        }

        isUpdatingBulk = true;
        const raw = bulkAmount.value.trim();
        const amt = parseFloat(raw);
        const existingItem = items.find((item) => item.product_id === bulkProduct.id);
        const unitPrice = existingItem ? existingItem.unit_price : priceFor(bulkProduct);

        if (!isNaN(amt) && amt > 0 && unitPrice > 0) {
            const rawQty = amt / unitPrice;
            const qty = Math.round(rawQty * 1000) / 1000;
            const decimals = (qty * 100) % 1 === 0 ? 2 : 3;
            if (bulkQuantity) {
                bulkQuantity.value = qty.toFixed(decimals);
            }
            if (bulkTotal) {
                bulkTotal.textContent = formatMoney(amt);
            }
            validateBulkStock(qty);
        } else {
            if (bulkQuantity) {
                bulkQuantity.value = '';
            }
            if (bulkTotal) {
                bulkTotal.textContent = formatMoney(0);
            }
            if (raw !== '') {
                if (bulkError) {
                    bulkError.textContent = 'Escribe un importe válido mayor a 0.';
                    bulkError.classList.remove('hidden');
                }
            } else if (bulkError) {
                bulkError.classList.add('hidden');
            }
        }
        isUpdatingBulk = false;
    });

    const handleBulkKeydown = (event) => {
        if (event.key === 'Enter') {
            event.preventDefault?.();
            event.stopPropagation?.();
            confirmBulk();
        } else if (event.key === 'Escape') {
            event.preventDefault?.();
            event.stopPropagation?.();
            closeBulkModal();
        }
    };

    bulkQuantity?.addEventListener('keydown', handleBulkKeydown);
    bulkAmount?.addEventListener('keydown', handleBulkKeydown);
    bulkAccept?.addEventListener('click', confirmBulk);
    bulkCancel?.addEventListener('click', closeBulkModal);
    bulkCloseBtn?.addEventListener('click', closeBulkModal);
    bulkForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        confirmBulk();
    });
    bulkDialog?.addEventListener('cancel', (event) => {
        event.preventDefault();
        closeBulkModal();
    });
    bulkDialog?.addEventListener('close', () => {
        bulkProduct = null;
        searchInput?.focus();
    });

    const showCustomProductError = (message) => {
        if (!customProductError) {
            return;
        }

        customProductError.textContent = message;
        customProductError.classList.remove('hidden');
    };

    const addCustomProduct = (name, price, quantity = 1) => {
        clearError();

        if (pendingSaleId !== null) {
            showError('Termina o cancela la venta pendiente antes de agregar otros productos.');
            return;
        }

        const existing = items.find((item) => item.is_custom && item.name === name && item.unit_price === round(price));

        if (!validQuantity(quantity) || !validQuantity((existing?.quantity ?? 0) + quantity)) {
            showCustomProductError('La cantidad debe ser un entero de 1 a 9999.');
            return;
        }

        if (existing) {
            existing.quantity = existing.quantity + quantity;
        } else {
            items.push({
                product_id: null,
                is_custom: true,
                custom_name: name,
                custom_price: round(price),
                name: name,
                sku: null,
                barcode: null,
                price: round(price),
                wholesale_price: null,
                unit_price: round(price),
                manual_price: round(price),
                sale_mode: 'unit',
                quantity,
                tax_rate: 0,
                stock: null,
                min_stock: 0,
                track_inventory: false,
            });
        }

        renderCart();
        customProductForm?.reset();
        customProductDialog?.close();
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

        if (!resultsBox.classList.contains('hidden')) {
            resultsBox.querySelectorAll('[data-result-price]').forEach((cell) => {
                cell.textContent = formatMoney(priceFor(productCache.get(Number(cell.dataset.resultPrice))));
            });
        }

        if (bulkDialog?.open && bulkProduct) {
            const unitPrice = priceFor(bulkProduct);
            if (bulkPrice) {
                bulkPrice.textContent = formatMoney(unitPrice);
            }
            const qty = parseFloat(bulkQuantity?.value) || 1;
            const amount = Math.round(qty * unitPrice * 100) / 100;
            if (bulkAmount) {
                bulkAmount.value = amount.toFixed(2);
            }
            if (bulkTotal) {
                bulkTotal.textContent = formatMoney(amount);
            }
        }
    };

    const hasStockValue = (value) => value != null && value !== '';
    const store2Heading = '<th scope="col" class="p-4 text-right">Existencia Tienda 2</th>';
    const store2Cell = (value) => `<td class="min-w-32 whitespace-nowrap p-4 align-middle text-right text-mz-text-secondary">${hasStockValue(value) ? formatQuantity(value) : ''}</td>`;

    const searchResultRow = (product, showStore2) => {
        const id = Number(product.id);
        const price = priceFor(product);
        const stock = stockFor(product);

        return `<tr data-product-id="${id}" tabindex="0" aria-label="Agregar ${escapeHtml(product.name)} al ticket" class="cursor-pointer hover:bg-mz-accent-primary/5 focus-visible:outline-2 focus-visible:outline-mz-accent-primary">
            <td class="w-28 p-4 align-middle"><div class="flex size-20 shrink-0 items-center justify-center rounded-lg border border-mz-border-light bg-mz-bg-primary">${product.image_url ? `<img data-product-thumbnail src="${escapeHtml(product.image_url)}" alt="${escapeHtml(product.name)}" class="size-20 rounded-lg object-contain"><span hidden class="text-xs text-mz-text-muted">Sin foto</span>` : '<span class="text-xs text-mz-text-muted">Sin foto</span>'}</div></td>
            <td class="min-w-40 max-w-52 break-all p-4 align-middle font-mono text-mz-accent-primary">${escapeHtml(product.barcode || product.barcode2 || product.sku || 'S/C')}</td>
            <td class="min-w-72 max-w-sm whitespace-normal break-words p-4 align-middle font-semibold text-mz-text-primary">${escapeHtml(product.name)}</td>
            <td class="min-w-44 max-w-56 whitespace-normal break-words p-4 align-middle text-mz-text-secondary">${escapeHtml(product.category_name)}</td>
            <td data-result-price="${id}" class="min-w-32 whitespace-nowrap p-4 align-middle text-right font-semibold">${formatMoney(price)}</td>
            <td class="min-w-32 whitespace-nowrap p-4 align-middle text-right ${stock === null ? 'text-mz-text-muted' : stock > 0 ? 'text-mz-success' : 'text-mz-error'}">${stock === null ? 'Sin control' : formatQuantity(stock)}</td>
            <td class="min-w-32 whitespace-nowrap p-4 align-middle text-right text-mz-text-secondary">${hasStockValue(product.warehouse_stock) ? formatQuantity(product.warehouse_stock) : '-'}</td>
            ${showStore2 ? store2Cell(product.store_2_stock) : ''}
        </tr>`;
    };

    const renderResults = (products, search) => {
        productCache = new Map(products.map((product) => [Number(product.id), product]));

        if (products.length === 0) {
            resultsBox.innerHTML = '<div class="px-4 py-8 text-center text-sm text-mz-text-muted"><svg class="size-8 mx-auto mb-2 text-mz-text-muted/50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>No se encontraron productos activos con ese término.</div>';
            resultsBox.classList.remove('hidden');
            return;
        }

        search.showStore2 = products.some((product) => hasStockValue(product.store_2_stock));
        resultsBox.innerHTML = `
            <p class="border-b border-mz-border px-4 py-3 text-xs font-bold uppercase text-mz-accent-primary">Resultados de búsqueda</p>
            <table class="w-full min-w-[76rem] text-left text-sm leading-relaxed">
                <thead class="sticky top-0 bg-mz-bg-surface-2 text-[11px] uppercase text-mz-text-muted">
                    <tr><th scope="col" class="p-4">Foto</th><th scope="col" class="p-4">Código</th><th scope="col" class="p-4">Producto</th><th scope="col" class="p-4">Categoría</th><th scope="col" class="p-4 text-right">Precio unitario</th><th scope="col" class="p-4 text-right">Existencia en tienda</th><th scope="col" class="p-4 text-right">Existencia en bodega</th>${search.showStore2 ? store2Heading : ''}</tr>
                </thead>
                <tbody class="divide-y divide-mz-border-light/40">${products.map((product) => searchResultRow(product, search.showStore2)).join('')}</tbody>
            </table>
            ${search.nextPage ? `<div class="border-t border-mz-border px-4 py-3 text-xs text-mz-text-muted">
                <p data-search-status role="status"></p>
                <button type="button" data-search-more class="rounded-md border border-mz-border px-3 py-2 font-semibold text-mz-accent-primary hover:bg-mz-accent-primary/5">Cargar más</button>
            </div>` : ''}`;

        resultsBox.classList.remove('hidden');
        resultsBox.scrollTop = 0;
    };

    const fetchSearchPage = async (term, page) => {
        const params = new URLSearchParams({ q: term });
        if (page) params.set('page', page);
        const response = await fetch(`${config.routes.search}?${params}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) throw new Error('No se pudo buscar.');

        return response.json();
    };

    const searchNow = async () => {
        const term = searchInput.value.trim();
        closeSearch();

        if (term === '') return [];

        const search = currentSearch = { term };
        try {
            const data = await fetchSearchPage(term);
            if (search !== currentSearch) return null;

            search.nextPage = data.next_page;
            const products = data.products ?? [];
            renderResults(products, search);
            return products;
        } catch {
            if (search === currentSearch) {
                showError('No se pudo buscar productos. Revisa tu conexión e inténtalo de nuevo.');
            }
            return null;
        }
    };

    const loadMoreSearchResults = async (retry = false) => {
        const search = currentSearch;
        if (!search?.nextPage || search.loading || (search.failed && !retry)) return;

        const status = resultsBox.querySelector('[data-search-status]');
        const button = resultsBox.querySelector('[data-search-more]');
        search.loading = true;
        status.textContent = 'Cargando más productos…';

        try {
            const data = await fetchSearchPage(search.term, search.nextPage);
            if (search !== currentSearch) return;

            const body = resultsBox.querySelector('tbody');
            const newProducts = [];
            for (const product of data.products ?? []) {
                const id = Number(product.id);
                if (!productCache.has(id)) {
                    productCache.set(id, product);
                    newProducts.push(product);
                }
            }

            if (!search.showStore2 && newProducts.some((product) => hasStockValue(product.store_2_stock))) {
                search.showStore2 = true;
                resultsBox.querySelector('thead tr').insertAdjacentHTML('beforeend', store2Heading);
                for (const row of body.rows) row.insertAdjacentHTML('beforeend', store2Cell(null));
            }

            body.insertAdjacentHTML('beforeend', newProducts.map((product) => searchResultRow(product, search.showStore2)).join(''));
            search.nextPage = data.next_page;
            search.failed = false;
            status.textContent = search.nextPage ? '' : 'No hay más resultados.';

            if (document.activeElement === button && (newProducts.length || !search.nextPage)) {
                const firstNewRow = newProducts.length && body.querySelector(`[data-product-id="${Number(newProducts[0].id)}"]`);
                (firstNewRow || body.lastElementChild)?.focus();
            }
            button.hidden = !search.nextPage;
        } catch {
            if (search === currentSearch) {
                search.failed = true;
                status.textContent = 'No se pudieron cargar más productos. Usa «Cargar más» para reintentar.';
            }
        } finally {
            search.loading = false;
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
        closeSearch();
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
            is_custom: item.is_custom || false,
            custom_name: item.custom_name || null,
            custom_price: item.is_custom ? item.unit_price : null,
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
                lastSale = data.sale;
                renderLastSale();

                if (permissions.tickets_own && config.routes.ticketData) {
                    try {
                        const ticketResponse = await fetch(config.routes.ticketData.replace('__SALE__', data.sale.id), {
                            headers: { Accept: 'application/json' },
                        });

                        if (ticketResponse.ok) {
                            const { sale } = await ticketResponse.json();
                            lastSale.amount_paid = sale.amount_paid;
                            lastSale.change_amount = sale.change_amount;
                            renderLastSale();
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
        const total = pendingSaleId !== null ? Number(paymentTotal.dataset.total || 0) : totals().total;
        const showAmount = !paymentMethod || paymentMethod.value === 'cash' || paymentMethod.value === 'mixed';
        const amountGroup = document.getElementById('payment-amount-group');

        if (amountGroup) {
            amountGroup.style.display = showAmount ? 'block' : 'none';
        }

        if (!showAmount) {
            paymentAmount.value = total.toFixed(2);
        }

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

        paymentDialog.showModal();
        if (!paymentMethod || paymentMethod.value === 'cash' || paymentMethod.value === 'mixed') {
            paymentAmount.focus();
            paymentAmount.select();
        } else {
            paymentMethod.focus();
        }
    };

    const fetchSaleForPanel = async (saleId) => {
        const response = await fetch(config.routes.show.replace('__SALE__', saleId), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('La venta pendiente ya no está disponible.');
        }

        return (await response.json()).sale;
    };

    const mapSaleItems = (saleItems) => saleItems.map((item) => ({
        product_id: item.product_id,
        is_custom: Boolean(item.is_custom),
        custom_name: item.custom_name ?? null,
        custom_price: item.is_custom ? Number(item.custom_price ?? item.unit_price) : null,
        name: item.is_custom ? (item.custom_name ?? item.name) : item.name,
        image_url: item.image_url ?? null,
        category_name: item.category_name ?? null,
        warehouse_stock: item.warehouse_stock ?? null,
        store_2_stock: item.store_2_stock ?? null,
        sku: item.sku,
        barcode: item.barcode,
        price: Number(item.unit_price),
        wholesale_price: null,
        unit_price: Number(item.unit_price),
        manual_price: null,
        sale_mode: item.sale_mode ?? 'unit',
        quantity: Number(item.quantity),
        tax_rate: Number(item.tax_rate) || 0,
        stock: null,
        min_stock: 0,
        track_inventory: false,
    }));

    const loadPendingSale = async (saleId) => {
        try {
            const sale = await fetchSaleForPanel(saleId);

            pendingSaleId = Number(sale.id);
            items = mapSaleItems(sale.items);
            paymentTotal.dataset.total = String(sale.total);
            if (paymentMethod) {
                paymentMethod.value = sale.payment_method || 'cash';
            }
            pendingBanner.classList.remove('hidden');
            wholesaleToggle.disabled = true;
            renderCart();
            openPayment();
        } catch (error) {
            showError(error.message);
        }
    };

    const pendingSalesDialog = document.getElementById('pending-sales-dialog');
    let pendingSales = [];

    const renderPendingSales = () => {
        const count = document.getElementById('pos-pending-count');

        if (count) {
            count.textContent = String(pendingSales.length);
            count.classList.toggle('hidden', pendingSales.length === 0);
        }

        const list = document.getElementById('pending-sales-list');

        if (!list) {
            return;
        }

        const empty = document.getElementById('pending-sales-empty');
        list.replaceChildren();
        empty?.classList.toggle('hidden', pendingSales.length > 0);

        pendingSales.forEach((sale) => {
            const row = document.createElement('div');
            row.className = 'flex flex-wrap items-center justify-between gap-3 rounded-xl border border-mz-border-light bg-mz-bg-surface-2/50 px-3 py-2.5';

            const info = document.createElement('div');
            info.className = 'min-w-0';

            const folio = document.createElement('p');
            folio.className = 'font-mono text-xs font-bold text-mz-text-primary';
            folio.textContent = sale.folio;

            const detail = document.createElement('p');
            detail.className = 'text-xs text-mz-text-muted';
            detail.textContent = `${sale.created_at} · ${sale.item_count} producto${sale.item_count === 1 ? '' : 's'} · ${formatMoney(sale.total)}`;

            info.append(folio, detail);

            const bringButton = document.createElement('button');
            bringButton.type = 'button';
            bringButton.className = 'inline-flex items-center gap-1.5 rounded-lg bg-orange-700 px-3 py-1.5 text-xs font-bold text-white transition-all hover:bg-orange-800 active:scale-95';
            bringButton.textContent = 'Traer a mi panel';
            bringButton.addEventListener('click', () => bringPendingToPanel(sale.id));

            const actions = document.createElement('div');
            actions.className = 'flex items-center gap-2';
            actions.appendChild(bringButton);

            if (permissions.cancel_sale) {
                const deleteButton = document.createElement('button');
                deleteButton.type = 'button';
                deleteButton.className = 'inline-flex items-center rounded-lg border border-mz-border-light px-2.5 py-1.5 text-xs font-bold text-mz-text-muted transition-all hover:border-mz-error/40 hover:bg-mz-error/10 hover:text-mz-error';
                deleteButton.textContent = 'Eliminar';
                deleteButton.addEventListener('click', () => deletePendingSale(sale.id));
                actions.appendChild(deleteButton);
            }

            row.append(info, actions);
            list.appendChild(row);
        });
    };

    const refreshPendingSales = async () => {
        if (!config.routes.pendingList) {
            return;
        }

        const response = await fetch(config.routes.pendingList, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('No se pudieron cargar los tickets pendientes.');
        }

        pendingSales = (await response.json()).sales ?? [];
        renderPendingSales();
    };

    const bringPendingToPanel = async (saleId) => {
        try {
            const sale = await fetchSaleForPanel(saleId);
            resetSale();
            items = mapSaleItems(sale.items);
            renderCart();
            pendingSalesDialog?.close();
        } catch (error) {
            showError(error.message);
        }
    };

    const deletePendingSale = async (saleId) => {
        if (! window.confirm('¿Eliminar este ticket pendiente? No se podrá cobrar después.')) {
            return;
        }

        try {
            const response = await fetch(config.routes.cancel.replace('__SALE__', saleId), {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            });

            if (! response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.message || 'No se pudo eliminar el ticket pendiente.');
            }

            await refreshPendingSales();
        } catch (error) {
            showError(error.message);
        }
    };

    searchInput?.addEventListener('input', () => {
        closeSearch();
        if (searchInput.value.trim()) searchTimer = setTimeout(searchNow, 50);
    });

    searchInput?.addEventListener('keydown', async (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            
            // Forzar búsqueda inmediata al presionar Enter (escáner)
            const products = await searchNow();

            if (products?.length) {
                addProduct(products[0]);
            } else if (products) {
                showError('Producto no encontrado.');
            }
        }
    });

    resultsBox?.addEventListener('scroll', () => {
        if (resultsBox.scrollHeight - resultsBox.scrollTop - resultsBox.clientHeight <= 120) {
            return loadMoreSearchResults();
        }
    });

    resultsBox?.addEventListener('click', (event) => {
        if (event.target.closest('[data-search-more]')) return loadMoreSearchResults(true);

        const row = event.target.closest('[data-product-id]');

        if (!row) {
            return;
        }

        const product = productCache.get(Number(row.dataset.productId));

        if (product) {
            addProduct(product);
        }
    });

    resultsBox?.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        const row = event.target.closest('[data-product-id]');
        if (!row) return;
        event.preventDefault();
        const product = productCache.get(Number(row.dataset.productId));
        if (product) addProduct(product);
    });

    cartBody?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-view-image]');
        if (!button || button.disabled) return;
        const item = items[Number(button.dataset.viewImage)];
        if (!item?.image_url || !imageDialog) return;
        imageTrigger = button;
        imageTitle.textContent = item.name;
        imagePreview.alt = item.name;
        imagePreview.hidden = false;
        imageError.hidden = true;
        imagePreview.src = item.image_url;
        imageDialog.showModal();
    });

    imagePreview?.addEventListener('error', () => {
        imagePreview.hidden = true;
        imageError.hidden = false;
    });
    imageDialog?.addEventListener('close', () => {
        imageTrigger?.focus();
        imageTrigger = null;
    });
    [cartBody, resultsBox].forEach((container) => container?.addEventListener('error', (event) => {
        const image = event.target;
        if (!image.matches('[data-product-thumbnail]')) return;
        image.hidden = true;
        image.nextElementSibling.hidden = false;
        const button = image.closest('[data-view-image]');
        if (button) {
            button.disabled = true;
            button.setAttribute('aria-label', 'Foto no disponible');
        }
    }, true));

    document.addEventListener('click', (event) => {
        if ((currentSearch || searchTimer !== null) && event.target !== searchInput && !resultsBox.contains(event.target)) {
            closeSearch();
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

    const editCart = (event) => {
        const input = event.target.closest('[data-action]');

        if (!input) {
            return;
        }

        const index = Number(input.dataset.index);
        const item = items[index];

        if (!item || pendingSaleId !== null) {
            return;
        }

        if (input.dataset.action === 'quantity') {
            if (event.type === 'input') {
                return;
            }

            if (!validQuantity(input.value, item.sale_mode)) {
                showError('La cantidad debe ser positiva, hasta 9999; solo granel permite hasta 3 decimales.');
                input.value = formatQuantity(item.quantity);
                return;
            }

            const quantity = Number(input.value);

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
            row.querySelector('[data-line-total]').textContent = formatMoney(lineTotal(item));
        }
    };
    cartBody?.addEventListener('input', editCart);
    cartBody?.addEventListener('change', editCart);

    cartBody?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action="remove"]');

        if (!button) {
            return;
        }

        items.splice(Number(button.dataset.index), 1);
        renderCart();
    });

    wholesaleToggle?.addEventListener('change', () => setWholesale(wholesaleToggle.checked));

    // Producto común: abrir modal y agregar artículo libre al carrito
    customProductForm?.addEventListener('submit', (event) => {
        event.preventDefault();

        const name = customProductName?.value.trim();
        const price = Number(customProductPrice?.value);
        const quantity = Number(customProductQuantity?.value);

        if (!name) {
            showCustomProductError('Escribe el nombre del producto.');
            customProductName?.focus();
            return;
        }

        if (!Number.isFinite(price) || price <= 0) {
            showCustomProductError('Escribe un precio válido mayor a cero.');
            customProductPrice?.focus();
            return;
        }

        if (!validQuantity(customProductQuantity?.value)) {
            showCustomProductError('La cantidad debe ser un entero de 1 a 9999.');
            customProductQuantity?.focus();
            return;
        }

        addCustomProduct(name, price, quantity);
    });

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

        if (!window.confirm('¿Dejar esta venta pendiente? No se descontarán existencias ni se moverá caja hasta cobrarla.')) {
            return;
        }

        submitting = true;

        try {
            await postJson(config.routes.store, buildPayload('pending', 0));
            resetSale();
            // El ticket ya quedó guardado aunque falle la actualización del conteo.
            await refreshPendingSales().catch(() => {});
        } catch (error) {
            showError(error.message);
        } finally {
            submitting = false;
        }
    });

    document.getElementById('pos-pending-list')?.addEventListener('click', () => {
        pendingSalesDialog?.showModal();
        refreshPendingSales().catch((error) => showError(error.message));
    });

    const localToday = () => {
        const date = new Date();
        date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
        return date.toISOString().slice(0, 10);
    };

    document.getElementById('pos-day-sales')?.addEventListener('click', () => {
        if (daySalesDate && ! daySalesDate.value) {
            daySalesDate.value = localToday();
        }
        daySalesDialog?.showModal();
        loadDaySales().catch((error) => showError(error.message));
    });

    daySalesSearch?.addEventListener('input', () => {
        clearTimeout(daySalesTimer);
        daySalesTimer = setTimeout(() => loadDaySales().catch((error) => showError(error.message)), 250);
    });

    daySalesDate?.addEventListener('change', () => loadDaySales().catch((error) => showError(error.message)));

    document.getElementById('day-sales-today')?.addEventListener('click', () => {
        if (daySalesDate) {
            daySalesDate.value = localToday();
        }
        loadDaySales().catch((error) => showError(error.message));
    });

    daySalesRows?.addEventListener('click', (event) => {
        const row = event.target.closest('[data-day-sale]');
        if (row) {
            selectDaySale(Number(row.dataset.daySale));
        }
    });

    daySalesItems?.addEventListener('click', (event) => {
        const row = event.target.closest('[data-day-item]');
        if (! row) {
            return;
        }

        const id = Number(row.dataset.dayItem);
        const item = selectedDaySale()?.items.find((candidate) => candidate.id === id);
        if (! item || Number(item.available_quantity) <= 0) {
            return;
        }

        selectedDayItemId = id;
        renderDaySaleDetail();
    });

    daySalesReturn?.addEventListener('click', returnSelectedItem);
    daySalesCancel?.addEventListener('click', cancelSelectedSale);
    daySalesPrint?.addEventListener('click', () => {
        const sale = selectedDaySale();
        if (sale) {
            printTicketById(sale.id);
        }
    });

    document.getElementById('pos-new-sale')?.addEventListener('click', resetSale);
    document.getElementById('pos-clear')?.addEventListener('click', () => {
        if (items.length > 0 && !window.confirm('¿Eliminar la venta actual?')) {
            return;
        }

        resetSale();
    });

    document.getElementById('pos-print-ticket')?.addEventListener('click', () => printTicketById(lastSale?.id));

    paymentConfirm?.addEventListener('click', () => {
        updateChange();

        if (paymentConfirm.disabled) {
            return;
        }

        submitSale('paid', Number(paymentAmount.value) || 0);
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

            const cashConcept = document.getElementById('cash-concept');
            if (cashConcept) {
                cashConcept.placeholder = type === 'in'
                    ? '¿De dónde entra el efectivo?'
                    : '¿Por qué sale el efectivo?';
            }

            cashError?.classList.add('hidden');

            cashDialog.showModal();
        });
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

    // Iniciar día: registra únicamente el efectivo inicial del turno.
    const startDaySaveButton = startDayForm?.querySelector('button[type="submit"]');
    let startDaySubmitting = false;

    const setStartDaySubmitting = (value) => {
        startDaySubmitting = value;

        if (value) {
            startDaySaveButton?.setAttribute('disabled', 'disabled');
            startDaySaveButton?.classList.add('opacity-75', 'cursor-not-allowed');
        } else {
            startDaySaveButton?.removeAttribute('disabled');
            startDaySaveButton?.classList.remove('opacity-75', 'cursor-not-allowed');
        }
    };

    const showStartDayError = (message) => {
        if (!startDayError) {
            return;
        }

        startDayError.textContent = message;
        startDayError.classList.remove('hidden');
    };

    startDayAmount?.addEventListener('blur', () => {
        startDayAmount.value = formatCashAmount(startDayAmount.value);
    });

    startDayForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (startDaySubmitting) {
            return;
        }

        startDayError?.classList.add('hidden');

        const raw = Number(parseCashAmount(startDayAmount?.value));

        if (!Number.isFinite(raw) || raw <= 0) {
            showStartDayError('Escribe un monto válido mayor a cero.');
            startDayAmount?.focus();
            return;
        }

        startDayAmount.value = String(raw);
        setStartDaySubmitting(true);

        try {
            const response = await fetch(startDayForm.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: new FormData(startDayForm),
                redirect: 'manual',
            });

            if (response.type === 'opaqueredirect' || response.redirected || response.ok) {
                startDayForm.reset();
                startDayDialog?.close();
                window.location.reload();
                return;
            }

            const data = await response.json().catch(() => ({}));
            const errors = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : (data.message || 'No se pudo registrar el efectivo inicial.');

            showStartDayError(errors);
        } catch (error) {
            showStartDayError(error.message || 'No se pudo registrar el efectivo inicial. Revisa tu conexión.');
        } finally {
            setStartDaySubmitting(false);
        }
    });

    startDayDialog?.addEventListener('close', () => {
        setStartDaySubmitting(false);
        startDayForm?.reset();
        startDayError?.classList.add('hidden');
    });

    document.addEventListener('keydown', (event) => {
        if (event.defaultPrevented || document.querySelector('dialog[open]')) {
            return;
        }

        const editing = event.target?.isContentEditable || event.target?.closest?.('input, textarea, select');
        // El buscador mantiene los atajos de función del flujo de escáner, nunca Delete/Escape.
        const scannerShortcut = event.target === searchInput && ['F12', 'F6', 'F7', 'F8', 'F11'].includes(event.key);

        if (editing && !scannerShortcut) {
            return;
        }

        if (event.key === 'F12') {
            event.preventDefault();
            openPayment();
        }

        if (event.key === 'F6') {
            event.preventDefault();
            document.getElementById('pos-pending')?.click();
        }

        if (event.key === 'F7') {
            event.preventDefault();
            document.querySelector('[data-cash-open="in"]')?.click();
        }

        if (event.key === 'F8') {
            event.preventDefault();
            document.querySelector('[data-cash-open="out"]')?.click();
        }

        if (event.key === 'F11') {
            event.preventDefault();
            if (permissions.wholesale) {
                wholesaleToggle.checked = !wholesaleToggle.checked;
                setWholesale(wholesaleToggle.checked);
            }
        }

        if (event.key === 'Delete') {
            event.preventDefault();
            if (items.length > 0 && !pendingSaleId) {
                if (window.confirm('¿Eliminar el último artículo de la venta?')) {
                    items.pop();
                    renderCart();
                }
            }
        }
    });

    paymentMethod?.addEventListener('change', updateChange);

    renderLastSale();
    renderCart();
    refreshPendingSales().catch(() => {});

    const pendingId = new URLSearchParams(window.location.search).get('pending');

    if (pendingId) {
        loadPendingSale(pendingId);
    }
}
