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
    const cashTitle = document.getElementById('cash-title');
    const cashError = document.getElementById('cash-error');
    const startDayDialog = document.getElementById('start-day-dialog');
    const startDayForm = document.getElementById('start-day-form');
    const startDayAmount = document.getElementById('start-day-amount');
    const startDayError = document.getElementById('start-day-error');

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
    let submitting = false;
    let lastSaleId = null;
    let lastSaleFolio = null;
    let lastSaleData = null;

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
                    <p class="font-semibold text-white">No se pudo completar la operación</p>
                    <p class="mt-0.5 text-xs text-mz-text-secondary">${escapeHtml(message)}</p>
                </div>
                <button type="button" onclick="this.closest('#pos-error').classList.add('hidden')" class="text-mz-text-muted hover:text-white p-1 rounded-md transition" aria-label="Cerrar"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
        `;
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
            container.innerHTML = data.ticket_html;
            await Promise.all(Array.from(container.querySelectorAll('img'), (image) => image.decode()));

            container.classList.remove('hidden');
            window.print();
        } catch (error) {
            showError(error.message);
        } finally {
            container.classList.add('hidden');
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
        subtotalEl.textContent = formatMoney(result.subtotal);
        taxEl.textContent = formatMoney(result.tax);
        totalEl.textContent = formatMoney(result.total);
        itemsCount.textContent = `${items.length} ${items.length === 1 ? 'renglón' : 'renglones'}`;
        emptyState.classList.toggle('hidden', items.length > 0);
    };

    const renderCart = () => {
        cartBody.innerHTML = '';

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
                ? `${formatQuantity(item.stock ?? 0)} disp.`
                : 'Sin control';

            row.innerHTML = `
                <td class="px-4 py-3">
                    <p class="font-semibold text-sm text-mz-text-primary group-hover:text-mz-accent-primary transition-colors">${escapeHtml(item.name)}</p>
                    <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-mz-text-muted">
                        <span class="font-mono text-[11px] text-mz-text-muted/80">${escapeHtml(item.barcode || item.barcode2 || item.sku || 'S/C')}</span>
                    </div>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-right">${priceControl}${item.sale_mode === 'bulk' ? '<span class="block text-[11px] text-mz-text-muted">por unidad</span>' : ''}</td>
                <td class="whitespace-nowrap px-4 py-3 text-center">${quantityControl}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right font-bold tabular-nums text-mz-text-primary font-display text-base">${formatMoney(lineTotal(item))}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-xs">
                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-medium ${item.track_inventory && item.stock <= (item.min_stock ?? 0) ? 'bg-mz-warning/15 text-mz-warning border border-mz-warning/30' : 'bg-mz-bg-surface-2 text-mz-text-muted'}">${stockLabel}</span>
                </td>
                <td class="px-4 py-3 text-right">
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

        if (pendingSaleId !== null) {
            showError('Termina o cancela la venta pendiente antes de agregar otros productos.');
            return;
        }

        if (isBulkProduct(product) && !confirmedBulk) {
            return openBulkModal(product);
        }

        const stock = stockFor(product);

        if (stock !== null && stock <= 0) {
            showError(`«${product.name}» está agotado.`);
            return false;
        }

        const existing = items.find((item) => item.product_id === product.id);
        const nextQuantity = (Math.round((existing?.quantity ?? 0) * 1000) + Math.round(quantity * 1000)) / 1000;

        if (!validQuantity(quantity, product.sale_mode) || !validQuantity(nextQuantity, product.sale_mode)) {
            showError('La cantidad debe ser positiva, hasta 9999; solo granel permite hasta 3 decimales.');
            return false;
        }

        if (stock !== null && nextQuantity > stock) {
            showError(`No hay existencia suficiente de «${product.name}». Disponible: ${formatQuantity(stock)}.`);
            return false;
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
        resultsBox.classList.add('hidden');
        searchInput.focus();
        return true;
    };

    const openBulkModal = (product) => {
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
            bulkQuantity.value = '1.00';
        }
        const initialAmount = Math.round(1 * unitPrice * 100) / 100;
        if (bulkAmount) {
            bulkAmount.value = initialAmount.toFixed(2);
        }
        if (bulkTotal) {
            bulkTotal.textContent = formatMoney(initialAmount);
        }
        isUpdatingBulk = false;

        resultsBox?.classList.add('hidden');
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

    const validateBulkStock = (qty) => {
        if (!bulkProduct || !bulkError) {
            return true;
        }

        const stock = stockFor(bulkProduct);
        if (stock !== null) {
            const existing = items.find((item) => item.product_id === bulkProduct.id);
            const currentInCart = existing ? Number(existing.quantity) : 0;
            const totalNeeded = (Math.round(currentInCart * 1000) + Math.round(qty * 1000)) / 1000;
            if (totalNeeded > stock) {
                bulkError.textContent = `No hay existencia suficiente de «${bulkProduct.name}». Disponible: ${formatQuantity(stock)}${currentInCart > 0 ? ` (${formatQuantity(currentInCart)} en ticket)` : ''}.`;
                bulkError.classList.remove('hidden');
                return false;
            }
        }

        bulkError.classList.add('hidden');
        bulkError.textContent = '';
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

        const productToAdd = bulkProduct;
        closeBulkModal();

        if (!addProduct(productToAdd, qty, true)) {
            if (bulkError) {
                bulkError.textContent = 'No se pudo agregar. Revisa la cantidad y la existencia disponible.';
                bulkError.classList.remove('hidden');
            }
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

    const renderResults = (products) => {
        productCache = new Map(products.map((product) => [Number(product.id), product]));

        if (products.length === 0) {
            resultsBox.innerHTML = '<div class="px-4 py-8 text-center text-sm text-mz-text-muted"><svg class="size-8 mx-auto mb-2 text-mz-text-muted/50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>No se encontraron productos activos con ese término.</div>';
            resultsBox.classList.remove('hidden');
            return;
        }

        resultsBox.innerHTML = products.map((product) => `
            <button type="button" data-product-id="${product.id}" class="group flex w-full items-center justify-between gap-4 border-b border-mz-border-light/40 px-4 py-3 text-left transition-colors duration-150 last:border-b-0 hover:bg-mz-bg-surface-2/80">
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-mz-text-primary group-hover:text-mz-accent-primary transition-colors">${escapeHtml(product.name)}</span>
                    <span class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-mz-text-muted">
                        <span class="font-mono text-[11px] text-mz-text-muted/80">${escapeHtml(product.barcode || product.barcode2 || 'S/C')}</span>
                    </span>
                </span>
                <span class="shrink-0 text-right">
                    <span class="block text-base font-bold tabular-nums text-mz-text-primary font-display">${formatMoney(product.price)}</span>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider ${product.in_stock ? 'bg-mz-success/15 text-mz-success border border-mz-success/30' : 'bg-mz-error/15 text-mz-error border border-mz-error/30'}">${escapeHtml(product.stock_label)}</span>
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
                is_custom: Boolean(item.is_custom),
                custom_name: item.custom_name ?? null,
                custom_price: item.is_custom ? Number(item.custom_price ?? item.unit_price) : null,
                name: item.is_custom ? (item.custom_name ?? item.name) : item.name,
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

            paymentTotal.dataset.total = String(data.sale.total);
            if (paymentMethod) {
                paymentMethod.value = data.sale.payment_method || 'cash';
            }
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

            if (item.track_inventory && quantity > Number(item.stock)) {
                showError(`Solo hay ${formatQuantity(item.stock)} disponibles de «${item.name}».`);
                input.value = formatQuantity(item.quantity);
                return;
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

    renderCart();

    const pendingId = new URLSearchParams(window.location.search).get('pending');

    if (pendingId) {
        loadPendingSale(pendingId);
    }
}
