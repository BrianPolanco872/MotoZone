import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { setImmediate } from 'node:timers/promises';
import test from 'node:test';
import vm from 'node:vm';
import numeral from 'numeral';

const source = readFileSync(new URL('../resources/js/pos/pos.js', import.meta.url), 'utf8')
    .replace("import numeral from 'numeral';", '');

async function pos({ pending = null, ticket = null, products = [{ id: 7, name: 'Aceite', price: 100, wholesale_price: 80, tax_rate: 0, track_inventory: false, sale_mode: 'unit' }] } = {}) {
    const elements = new Map();
    const requests = [];
    const confirmations = [];
    const prints = [];
    const timers = new Set();
    const element = (tagName = 'DIV') => {
        const listeners = new Map();
        const classes = new Set();
        return {
            tagName, value: '', textContent: '', dataset: {}, style: {}, children: [], open: false,
            disabled: false, isContentEditable: false,
            classList: {
                add: (...names) => names.forEach((name) => classes.add(name)),
                remove: (...names) => names.forEach((name) => classes.delete(name)),
                contains: (name) => classes.has(name),
                toggle: (name, enabled) => enabled ? classes.add(name) : classes.delete(name),
            },
            set innerHTML(value) { this.html = value; this.children = []; },
            get innerHTML() { return this.html || ''; },
            addEventListener(type, callback) {
                listeners.set(type, [...(listeners.get(type) || []), callback]);
            },
            async emit(type, event = {}) {
                event.type ??= type;
                event.target ??= this;
                event.preventDefault ??= () => { event.defaultPrevented = true; };
                for (const callback of listeners.get(type) || []) {
                    await callback(event);
                }
                await setImmediate();
                return event;
            },
            click() { return this.disabled ? Promise.resolve() : this.emit('click'); },
            appendChild(child) { this.children.push(child); },
            append(...children) { this.children.push(...children); },
            closest(selector) {
                if (selector === '[data-action]' && this.dataset.action) return this;
                if (selector === '[data-action="remove"]' && this.dataset.action === 'remove') return this;
                if (selector === '[data-product-id]' && this.dataset.productId) return this;
                if (selector === '[data-view-image]' && this.dataset.viewImage !== undefined) return this;
                if (selector === 'input, textarea, select' && ['INPUT', 'TEXTAREA', 'SELECT'].includes(this.tagName)) return this;
                return null;
            },
            querySelector() { return null; },
            querySelectorAll() { return []; },
            matches(selector) { return selector === '[data-product-thumbnail]' && this.dataset.productThumbnail !== undefined; },
            setAttribute(name, value) { this[name] = value; },
            focus() { document.activeElement = this; },
            select() {}, reset() {}, scrollIntoView() {},
            showModal() { this.open = true; },
            close() { this.open = false; void this.emit('close'); },
            async cancel() {
                const event = await this.emit('cancel');
                if (!event.defaultPrevented) this.close();
            },
        };
    };
    for (const id of ('pos-config pos-search pos-results pos-cart-table pos-cart-body pos-empty pos-empty-cell pos-cart-store2-heading pos-items-count pos-total '
        + 'product-image-dialog product-image-preview product-image-title product-image-error product-image-close '
        + 'pos-wholesale pos-error pending-banner payment-dialog payment-amount payment-total payment-change payment-error '
        + 'payment-confirm payment-method payment-amount-group cash-dialog cash-type cash-title cash-error start-day-dialog '
        + 'custom-product-dialog custom-product-form custom-product-name custom-product-price custom-product-quantity '
        + 'custom-product-error pos-charge pos-pending pos-new-sale pos-clear '
        + 'bulk-dialog bulk-form bulk-close-btn bulk-name bulk-price bulk-stock-info bulk-available-stock bulk-quantity bulk-amount bulk-total bulk-error bulk-cancel bulk-accept '
        + 'last-sale-indicator last-sale-folio pos-print-ticket ticket-print-container').split(' ')) {
        elements.set(id, element(id.endsWith('-dialog') ? 'DIALOG' : 'DIV'));
    }
    const get = (id) => elements.get(id);
    get('pos-search').tagName = 'INPUT';
    get('bulk-quantity').tagName = 'INPUT';
    get('bulk-amount').tagName = 'INPUT';
    get('bulk-accept').tagName = 'BUTTON';
    get('bulk-cancel').tagName = 'BUTTON';
    get('bulk-close-btn').tagName = 'BUTTON';
    get('bulk-cancel').dataset.closeDialog = 'bulk-dialog';
    get('product-image-close').dataset.closeDialog = 'product-image-dialog';
    get('product-image-error').hidden = true;
    get('payment-method').tagName = 'SELECT';
    get('payment-method').value = 'cash';
    get('ticket-print-container').classList.add('hidden');
    const cashIn = element('BUTTON');
    cashIn.dataset.cashOpen = 'in';
    const cashOut = element('BUTTON');
    cashOut.dataset.cashOpen = 'out';
    const exact = element('BUTTON');
    const frequentButtons = products.map((product) => Object.assign(element('BUTTON'), { dataset: { addProduct: JSON.stringify(product) } }));
    const document = Object.assign(element(), {
        head: element('HEAD'),
        getElementById: (id) => get(id) || null,
        createElement: element,
        querySelector(selector) {
            if (selector === 'dialog[open]') return [...elements.values()].find((entry) => entry.tagName === 'DIALOG' && entry.open) || null;
            if (selector === '[data-cash-open="in"]') return cashIn;
            if (selector === '[data-cash-open="out"]') return cashOut;
            if (selector === '[data-amount-exact]') return exact;
            return null;
        },
        querySelectorAll(selector) {
            if (selector === '[data-cash-open]') return [cashIn, cashOut];
            if (selector === '[data-add-product]') return frequentButtons;
            if (selector === '[data-close-dialog]') return [get('bulk-cancel'), get('product-image-close')];
            return [];
        },
    });
    get('pos-config').textContent = JSON.stringify({
        permissions: { wholesale: true, price_edit: true }, csrf: 'test',
        routes: { search: '/search', store: '/store', show: '/sale/__SALE__', charge: '/sale/__SALE__/charge', pending: '/pending', ticketData: ticket ? '/ticket/__SALE__' : undefined },
    });
    vm.runInNewContext(source, {
        document, numeral, URLSearchParams,
        setTimeout: (callback) => { timers.add(callback); return callback; },
        clearTimeout: (callback) => timers.delete(callback),
        window: {
            location: { search: pending ? '?pending=41' : '' },
            confirm: (message) => { confirmations.push(message); return true; },
            print: () => prints.push({
                html: get('ticket-print-container').innerHTML,
                hidden: get('ticket-print-container').classList.contains('hidden'),
                pageStyles: document.head.children.map((style) => style.textContent),
            }),
        },
        fetch: async (url, options) => {
            if (options?.method === 'POST') {
                requests.push({ url, payload: JSON.parse(options.body) });
                return { ok: true, json: async () => ({ sale: { id: 42, folio: 'TEST' } }) };
            }
            if (url === '/ticket/42') {
                return { ok: true, json: async () => ticket };
            }
            return { ok: true, json: async () => url.startsWith('/search') ? { products } : { sale: pending } };
        },
    }, { filename: 'resources/js/pos/pos.js' });
    await setImmediate();
    return {
        get, requests, confirmations, prints, document, element, exact, frequentButtons,
        async scan() {
            get('pos-search').value = '750123';
            await get('pos-search').emit('keydown', { key: 'Enter' });
        },
        async results() {
            get('pos-search').value = 'aceite';
            await get('pos-search').emit('input');
            for (const callback of timers) {
                timers.delete(callback);
                await callback();
            }
        },
        async search(index = 0) {
            await this.results();
            const button = element('BUTTON');
            button.dataset.productId = String(products[index].id);
            await get('pos-results').emit('click', { target: button });
        },
        async bulk(quantity) {
            get('bulk-quantity').value = String(quantity);
            await get('bulk-quantity').emit('input');
            await get('bulk-form').emit('submit');
        },
        async editQuantity(quantity, index = 0) {
            const input = element('INPUT');
            input.dataset = { action: 'quantity', index: String(index) };
            input.value = String(quantity);
            await get('pos-cart-body').emit('change', { target: input });
            return input;
        },
        async custom(name, price) {
            get('custom-product-name').value = name;
            get('custom-product-price').value = String(price);
            get('custom-product-quantity').value = '1';
            await get('custom-product-form').emit('submit');
        },
        async key(key, target = document) { return document.emit('keydown', { key, target }); },
    };
}

for (const width of [58, 80]) {
    test(`printing ${width} mm uses the receipt HTML without overriding the printer page size`, async () => {
        const html = `<div class="ticket ticket-sheet" style="--ticket-width: ${width}mm">Ticket de prueba</div>`;
        const ui = await pos({ ticket: { ticket_html: html, settings: { ticket: { width_mm: width } } } });
        await ui.scan();
        await ui.get('pos-charge').click();
        await ui.get('payment-confirm').click();

        await ui.get('pos-print-ticket').click();

        assert.deepEqual(ui.prints, [{ html, hidden: false, pageStyles: [] }]);
        assert.equal(ui.get('ticket-print-container').classList.contains('hidden'), true);
        assert.equal(ui.requests.length, 1);
    });
}

test('an image loading failure prevents printing and hides the print container again', async () => {
    const ui = await pos({ ticket: { ticket_html: '<img src="qr.png">' } });
    await ui.scan();
    await ui.get('pos-charge').click();
    await ui.get('payment-confirm').click();
    ui.get('ticket-print-container').querySelectorAll = () => [{ decode: async () => { throw new Error('No se pudo cargar el QR.'); } }];

    await ui.get('pos-print-ticket').click();

    assert.equal(ui.prints.length, 0);
    assert.match(ui.get('pos-error').innerHTML, /No se pudo cargar el QR\./);
    assert.equal(ui.get('ticket-print-container').classList.contains('hidden'), true);
});

for (const method of ['card', 'transfer']) {
    test(`${method}: changing from insufficient cash enables confirmation and submits the total`, async () => {
        const ui = await pos();
        await ui.scan();
        await ui.get('pos-charge').click();
        ui.get('payment-amount').value = '10';
        await ui.get('payment-amount').emit('input');
        assert.equal(ui.get('payment-confirm').disabled, true);
        ui.get('payment-method').value = method;
        await ui.get('payment-method').emit('change');
        assert.equal(ui.get('payment-amount').value, '100.00');
        assert.equal(ui.get('payment-confirm').disabled, false);
        assert.equal(ui.get('payment-change').textContent, '$0.00');
        assert.equal(ui.get('payment-amount-group').style.display, 'none');
        ui.get('payment-dialog').close();
        await ui.get('pos-charge').click();
        ui.get('payment-amount').value = '0';
        await ui.get('payment-confirm').click();
        assert.equal(ui.requests.length, 1);
        assert.equal(ui.requests[0].payload.amount_paid, 100);
        assert.equal(ui.requests[0].payload.payment_method, method);
    });
}

test('cash and mixed retain insufficient-payment checks, change and exact amount', async () => {
    const ui = await pos();
    await ui.scan();
    await ui.get('pos-charge').click();
    ui.get('payment-method').value = 'card';
    await ui.get('payment-method').emit('change');
    for (const method of ['cash', 'mixed']) {
        ui.get('payment-method').value = method;
        await ui.get('payment-method').emit('change');
        assert.equal(ui.get('payment-amount-group').style.display, 'block');
        ui.get('payment-amount').value = '99';
        await ui.get('payment-amount').emit('input');
        await ui.get('payment-confirm').click();
        assert.equal(ui.requests.length, 0);
        assert.equal(ui.get('payment-confirm').disabled, true);
        assert.match(ui.get('payment-error').textContent, /Faltan \$1\.00/);
        ui.get('payment-amount').value = '150';
        await ui.get('payment-amount').emit('input');
        assert.equal(ui.get('payment-change').textContent, '$50.00');
        assert.equal(ui.get('payment-confirm').disabled, false);
        await ui.exact.click();
        assert.equal(ui.get('payment-amount').value, '100.00');
    }
    await ui.get('payment-confirm').click();
    assert.equal(ui.requests[0].payload.amount_paid, 100);
});

test('custom lines keep separate prices and submit the edited custom price', async () => {
    const ui = await pos();
    await ui.custom('Mano de obra', 20);
    await ui.custom('Mano de obra', 30);
    await ui.custom('Mano de obra', 30);
    await ui.custom('Otro trabajo', 20);
    assert.equal(ui.get('pos-cart-body').children.length, 3);
    const input = ui.element('INPUT');
    input.dataset = { action: 'price', index: '0' };
    input.value = '25';
    await ui.get('pos-cart-body').emit('input', { target: input });
    await ui.scan();
    await ui.get('pos-pending').click();
    assert.deepEqual(ui.requests[0].payload.items.map((item) => [item.product_id, item.is_custom, item.custom_name, item.custom_price, item.quantity]), [
        [null, true, 'Mano de obra', 25, 1],
        [null, true, 'Mano de obra', 30, 2],
        [null, true, 'Otro trabajo', 20, 1],
        [7, false, null, null, 1],
    ]);
});

test('pending custom lines preserve identity and charge the saved total, including reopening', async () => {
    const ui = await pos({ pending: {
        id: 41, total: 75, payment_method: 'transfer',
        items: [20, 30].map((price) => ({ product_id: null, is_custom: true, name: `Trabajo ${price}`, custom_name: `Trabajo ${price}`, custom_price: price, unit_price: price, quantity: 1, tax_rate: 0 })),
    } });
    assert.equal(ui.get('pos-cart-body').children.length, 2);
    assert.match(ui.get('pos-cart-body').children[0].innerHTML, /Trabajo 20/);
    assert.match(ui.get('pos-cart-body').children[1].innerHTML, /Trabajo 30/);
    assert.equal(ui.get('payment-method').value, 'transfer');
    assert.equal(ui.get('payment-change').textContent, '$0.00');
    ui.get('payment-dialog').close();
    await ui.get('pos-charge').click();
    await ui.get('payment-confirm').click();
    assert.deepEqual(ui.requests, [{ url: '/sale/41/charge', payload: { amount_paid: 75, payment_method: 'transfer' } }]);
});

test('any open dialog blocks all global shortcuts without preventing native Escape cancellation', async () => {
    const ui = await pos();
    await ui.scan();
    for (const id of ['payment-dialog', 'cash-dialog', 'custom-product-dialog', 'start-day-dialog', 'bulk-dialog']) {
        const dialog = ui.get(id);
        dialog.showModal();
        for (const key of ['Escape', 'Delete', 'F12', 'F6', 'F7', 'F8', 'F11']) {
            const event = await ui.key(key);
            assert.equal(event.defaultPrevented, undefined);
            assert.equal(ui.get('pos-cart-body').children.length, 1);
            assert.equal(ui.get('pos-wholesale').checked, undefined);
        }
        dialog.close(); // Native cancellation itself requires a browser, not this DOM stub.
    }
    await ui.key('Escape');
    assert.equal(ui.get('pos-cart-body').children.length, 1);
    assert.equal(ui.confirmations.length, 0);
    assert.equal(ui.requests.length, 0);
});

test('editing blocks shortcuts; focused scanner search still supports function keys and Enter', async () => {
    const ui = await pos();
    await ui.scan();
    for (const tag of ['INPUT', 'TEXTAREA', 'SELECT', 'SPAN']) {
        const target = ui.element(tag);
        target.isContentEditable = tag === 'SPAN';
        for (const key of ['Delete', 'Escape', 'F12', 'F6', 'F7', 'F8', 'F11']) {
            await ui.key(key, target);
            assert.equal(ui.get('pos-cart-body').children.length, 1);
            assert.equal(ui.get('payment-dialog').open, false);
            assert.equal(ui.get('cash-dialog').open, false);
        }
    }
    await ui.key('Delete', ui.get('pos-search'));
    await ui.key('Escape', ui.get('pos-search'));
    assert.equal(ui.get('pos-cart-body').children.length, 1);
    assert.equal(ui.confirmations.length, 0);
    await ui.key('F12', ui.get('pos-search'));
    assert.equal(ui.get('payment-dialog').open, true);
    ui.get('payment-dialog').close();
    await ui.key('F7', ui.get('pos-search'));
    assert.equal(ui.get('cash-dialog').open, true);
    assert.equal(ui.get('cash-type').value, 'in');
    ui.get('cash-dialog').close();
    await ui.key('F8', ui.get('pos-search'));
    assert.equal(ui.get('cash-type').value, 'out');
    ui.get('cash-dialog').close();
    await ui.key('F11', ui.get('pos-search'));
    assert.equal(ui.get('pos-total').textContent, '$80.00');
    await ui.key('F6', ui.get('pos-search'));
    assert.equal(ui.requests[0].payload.status, 'pending');
    assert.equal(ui.confirmations.length, 1);
});

test('Delete outside editing still confirms removal and explicit clear still confirms', async () => {
    const ui = await pos();
    await ui.scan();
    await ui.key('Delete');
    assert.equal(ui.get('pos-cart-body').children.length, 0);
    assert.equal(ui.confirmations.length, 1);
    await ui.scan();
    await ui.get('pos-clear').click();
    assert.equal(ui.get('pos-cart-body').children.length, 0);
    assert.equal(ui.confirmations.length, 2);
});

const bulkProduct = { id: 8, name: 'Aceite a granel', sale_mode: 'bulk', price: 14.10, wholesale_price: 12, tax_rate: 0.16, track_inventory: true, stock: 2.75, available_stock: 2.75 };

for (const entry of ['scan', 'search', 'frequent']) {
    test(`${entry}: bulk asks for quantity before mutating the cart and submits exact quantity and money`, async () => {
        const ui = await pos({ products: [bulkProduct] });

        if (entry === 'frequent') {
            await ui.frequentButtons[0].click();
        } else {
            await ui[entry]();
        }

        assert.equal(ui.get('bulk-dialog').open, true);
        assert.equal(ui.document.activeElement, ui.get('bulk-quantity'));
        assert.equal(ui.get('bulk-name').textContent, 'Aceite a granel');
        assert.equal(ui.get('bulk-price').textContent, '$14.10');
        assert.equal(ui.get('pos-cart-body').children.length, 0);
        ui.get('bulk-quantity').value = '0.500';
        await ui.get('bulk-quantity').emit('input');
        assert.equal(ui.get('bulk-total').textContent, '$7.05');

        await ui.get('bulk-form').emit('submit');
        await ui.get('bulk-form').emit('submit');
        assert.equal(ui.get('bulk-dialog').open, false);
        assert.equal(ui.document.activeElement, ui.get('pos-search'));
        assert.equal(ui.get('pos-total').textContent, '$7.05');
        assert.equal(ui.get('pos-items-count').textContent, '1 renglón');
        assert.match(ui.get('pos-cart-body').children[0].innerHTML, /step="0\.001" min="0\.001" max="9999" value="0\.5"/);
        await ui.get('pos-charge').click();
        await ui.get('payment-confirm').click();
        assert.equal(ui.requests[0].payload.amount_paid, 7.05);
        assert.equal(ui.requests[0].payload.items[0].quantity, 0.5);
        assert.equal('sale_mode' in ui.requests[0].payload.items[0], false);
        assert.equal('inventory_snapshot' in ui.requests[0].payload.items[0], false);
    });
}

for (const cancellation of ['button', 'Escape']) {
    test(`bulk ${cancellation} discards the addition and preserves existing quantity, price and shortcuts`, async () => {
        const ui = await pos({ products: [bulkProduct] });
        await ui.scan();
        await ui.bulk(0.5);
        const before = ui.get('pos-cart-body').children[0].innerHTML;
        await ui.scan();
        ui.get('bulk-quantity').value = '0.125';
        for (const key of ['Delete', 'F12', 'F6', 'F7', 'F8', 'F11', 'Escape']) {
            const event = await ui.key(key, ui.get('bulk-quantity'));
            assert.equal(event.defaultPrevented, undefined);
        }

        if (cancellation === 'button') {
            await ui.get('bulk-cancel').click();
        } else {
            await ui.get('bulk-dialog').cancel();
        }

        assert.equal(ui.get('bulk-dialog').open, false);
        assert.equal(ui.document.activeElement, ui.get('pos-search'));
        assert.equal(ui.get('pos-cart-body').children[0].innerHTML, before);
        assert.equal(ui.get('pos-total').textContent, '$7.05');
        assert.equal(ui.requests.length, 0);
        assert.equal(ui.confirmations.length, 0);
    });
}

test('bulk readding uses wholesale or overridden price and sums thousandths only on confirmation', async () => {
    const ui = await pos({ products: [bulkProduct] });
    await ui.key('F11');
    await ui.scan();
    assert.equal(ui.get('bulk-price').textContent, '$12.00');
    await ui.bulk(0.1);
    const price = ui.element('INPUT');
    price.dataset = { action: 'price', index: '0' };
    price.value = '15.25';
    await ui.get('pos-cart-body').emit('input', { target: price });
    await ui.scan();
    assert.equal(ui.get('bulk-price').textContent, '$15.25');
    assert.equal(ui.get('pos-total').textContent, '$1.53');
    await ui.bulk(0.2);
    assert.equal(ui.get('pos-total').textContent, '$4.58');
    assert.equal(ui.get('pos-cart-body').children.length, 1);
    await ui.get('pos-pending').click();
    assert.equal(ui.requests[0].payload.items[0].quantity, 0.3);
    assert.equal(ui.requests[0].payload.items[0].unit_price, 15.25);
    assert.equal(ui.requests[0].payload.items[0].wholesale, true);
});

test('bulk edits retain fractions and invalid quantities never truncate or clamp', async () => {
    const ui = await pos({ products: [bulkProduct] });
    await ui.scan();
    for (const invalid of ['0', '-1', '0.0001', '1.2345', '10000', '1e-3', '']) {
        await ui.bulk(invalid);
        assert.equal(ui.get('bulk-dialog').open, true);
        assert.equal(ui.get('pos-cart-body').children.length, 0);
        assert.equal(ui.get('bulk-error').classList.contains('hidden'), false);
    }
    await ui.bulk(0.001);
    await ui.editQuantity('0.125');
    assert.equal(ui.get('pos-total').textContent, '$1.76');
    for (const invalid of ['0.0001', '0', '10000', '2.751']) {
        const input = await ui.editQuantity(invalid);
        assert.equal(input.value, '0.125');
        assert.equal(ui.get('pos-total').textContent, '$1.76');
    }
    await ui.scan();
    await ui.bulk(2.75);
    assert.equal(ui.get('bulk-dialog').open, false);
    assert.equal(ui.get('pos-total').textContent, '$40.54');
    await ui.get('pos-pending').click();
    assert.equal(ui.requests[0].payload.items[0].quantity, 2.875);
});

test('bulk readding checks the accumulated 9999 limit', async () => {
    const product = { ...bulkProduct, available_stock: 9999, stock: 9999 };
    const ui = await pos({ products: [product] });
    await ui.scan();
    await ui.bulk(9998.999);
    await ui.scan();
    await ui.bulk(0.002);
    assert.equal(ui.get('bulk-dialog').open, true);
    await ui.bulk(0.001);
    assert.equal(ui.get('bulk-dialog').open, false);
    await ui.get('pos-pending').click();
    assert.equal(ui.requests[0].payload.items[0].quantity, 9999);
});

test('bulk readding accumulates quantities even when displayed availability drops', async () => {
    const product = { ...bulkProduct };
    const ui = await pos({ products: [product] });
    await ui.scan();
    await ui.bulk(0.5);
    product.available_stock = 0.625;
    await ui.scan();
    await ui.bulk(0.25);
    assert.equal(ui.get('bulk-dialog').open, false);
    assert.equal(ui.get('pos-total').textContent, '$10.58');
    await ui.get('pos-pending').click();
    assert.equal(ui.requests[0].payload.items[0].quantity, 0.75);
});

for (const available of [3, null]) {
    test(`kit availability ${available} ignores zero parent stock and keeps quantities integral`, async () => {
        const ui = await pos({ products: [{ id: 9, name: 'Paquete', sale_mode: 'kit', price: 30, wholesale_price: null, stock: 0, available_stock: available, track_inventory: available !== null, kit_deduct_components: available !== null }] });
        await ui.scan();
        assert.equal(ui.get('bulk-dialog').open, false);
        assert.equal(ui.get('pos-cart-body').children.length, 1);
        const input = await ui.editQuantity(0.5);
        assert.equal(input.value, '1');
        assert.equal(ui.get('pos-total').textContent, '$30.00');
        await ui.editQuantity(3);
        await ui.scan();
        assert.equal(ui.get('pos-total').textContent, '$120.00');
        await ui.get('pos-pending').click();
        assert.equal(ui.requests[0].payload.items[0].quantity, 4);
    });
}

test('kit with zero derived availability can be added; stock is checked when charging', async () => {
    const ui = await pos({ products: [{ id: 9, name: 'Paquete agotado', sale_mode: 'kit', price: 30, stock: 100, available_stock: 0, track_inventory: true }] });
    await ui.scan();
    assert.equal(ui.get('pos-cart-body').children.length, 1);
    assert.equal(ui.get('pos-total').textContent, '$30.00');
    assert.equal(ui.get('pos-error').classList.contains('hidden'), true);
});

test('bulk without inventory accepts fractions despite zero parent stock and cancellation leaves an empty cart', async () => {
    const ui = await pos({ products: [{ ...bulkProduct, track_inventory: false, stock: 0, available_stock: null }] });
    await ui.scan();
    await ui.get('bulk-cancel').click();
    assert.equal(ui.get('pos-cart-body').children.length, 0);
    assert.equal(ui.document.activeElement, ui.get('pos-search'));
    await ui.scan();
    await ui.bulk(0.5);
    assert.equal(ui.get('pos-total').textContent, '$7.05');
});

test('unit and custom quantities reject fractions instead of silently flooring', async () => {
    const ui = await pos();
    await ui.scan();
    assert.equal((await ui.editQuantity(1.5)).value, '1');
    await ui.custom('Servicio', 20);
    assert.equal((await ui.editQuantity(0.5, 1)).value, '1');
    ui.get('custom-product-name').value = 'Otro';
    ui.get('custom-product-price').value = '10';
    ui.get('custom-product-quantity').value = '1.5';
    await ui.get('custom-product-form').emit('submit');
    assert.equal(ui.get('pos-cart-body').children.length, 2);
    await ui.get('pos-pending').click();
    assert.deepEqual(ui.requests[0].payload.items.map((item) => item.quantity), [1, 1]);
});

test('mixed quantities keep the total and row count without subtotal or tax cards', async () => {
    const ui = await pos({ products: [bulkProduct, { ...bulkProduct, id: 10, name: 'Otro aceite' }] });
    await ui.frequentButtons[0].click();
    await ui.bulk(0.001);
    await ui.frequentButtons[1].click();
    await ui.bulk(0.001);
    assert.equal(ui.get('pos-subtotal'), undefined);
    assert.equal(ui.get('pos-tax'), undefined);
    assert.equal(ui.get('pos-total').textContent, '$0.02');
    assert.equal(ui.get('pos-items-count').textContent, '2 renglones');
});

test('pending bulk stays fractional and locked while charge sends only payment information', async () => {
    const ui = await pos({ pending: { id: 41, total: 1.76, payment_method: 'cash', items: [{ product_id: 8, sale_mode: 'bulk', name: 'Aceite guardado', unit_price: 14.10, quantity: 0.125, tax_rate: 0.16 }] } });
    const row = ui.get('pos-cart-body').children[0].innerHTML;
    assert.match(row, />0\.125<\/span>/);
    assert.doesNotMatch(row, /data-action="quantity"/);
    ui.get('payment-dialog').close();
    await ui.scan();
    assert.equal(ui.get('bulk-dialog').open, false);
    assert.equal(ui.get('pos-cart-body').children[0].innerHTML, row);
    await ui.get('pos-charge').click();
    await ui.get('payment-confirm').click();
    assert.deepEqual(ui.requests, [{ url: '/sale/41/charge', payload: { amount_paid: 1.76, payment_method: 'cash' } }]);
});

test('bulk modal bidirectional calculation updates amount from quantity and quantity from amount', async () => {
    const ui = await pos({ products: [bulkProduct] });
    await ui.scan();

    assert.equal(ui.get('bulk-dialog').open, true);
    assert.equal(ui.get('bulk-quantity').value, '1.00');
    assert.equal(ui.get('bulk-amount').value, '14.10');
    assert.equal(ui.get('bulk-price').textContent, '$14.10');
    assert.equal(ui.get('bulk-available-stock').textContent, '2.75');

    // Modifying quantity updates amount
    ui.get('bulk-quantity').value = '2.00';
    await ui.get('bulk-quantity').emit('input');
    assert.equal(ui.get('bulk-amount').value, '28.20');

    // Modifying amount updates quantity
    ui.get('bulk-amount').value = '14.10';
    await ui.get('bulk-amount').emit('input');
    assert.equal(ui.get('bulk-quantity').value, '1.00');

    // Enter key on amount confirms and adds to cart
    await ui.get('bulk-amount').emit('keydown', { key: 'Enter' });
    assert.equal(ui.get('bulk-dialog').open, false);
    assert.equal(ui.document.activeElement, ui.get('pos-search'));
    assert.equal(ui.get('pos-cart-body').children.length, 1);
    assert.equal(ui.get('pos-total').textContent, '$14.10');
});

test('bulk modal allows exceeding displayed availability; stock is checked when charging', async () => {
    const ui = await pos({ products: [bulkProduct] }); // available_stock: 2.75
    await ui.scan();

    ui.get('bulk-quantity').value = '5.00';
    await ui.get('bulk-quantity').emit('input');

    assert.equal(ui.get('bulk-error').classList.contains('hidden'), true);

    await ui.get('bulk-accept').click();
    assert.equal(ui.get('bulk-dialog').open, false);
    assert.equal(ui.get('pos-cart-body').children.length, 1);
    assert.equal(ui.get('pos-total').textContent, '$70.50');
});

test('search renders product metadata without an action column and hides empty store 2', async () => {
    const product = { id: 12, name: 'Aceite <4T>', barcode: 'MOT-001', price: 380, wholesale_price: 350, sale_mode: 'unit', category_name: 'Motor & fluidos', image_url: '/storage/motul.jpg', stock: 14, track_inventory: true, warehouse_stock: '0.000', store_2_stock: null };
    const ui = await pos({ products: [product] });
    await ui.results();
    const html = ui.get('pos-results').innerHTML;
    const headings = (markup) => [...markup.matchAll(/<th\b[^>]*>([^<]*)<\/th>/g)].map((match) => match[1]);
    const columns = ['Foto', 'Código', 'Producto', 'Categoría', 'Precio unitario', 'Existencia en tienda', 'Existencia en bodega'];
    assert.deepEqual(headings(html), columns);
    assert.match(html, /Aceite &lt;4T&gt;/);
    assert.match(html, /Motor &amp; fluidos/);
    assert.match(html, /src="\/storage\/motul\.jpg"/);
    assert.match(html, />14<\/td>/);
    assert.match(html, />0<\/td>/);
    assert.doesNotMatch(html, /Acción|Cantidad|Importe|Tienda 2|<input/);
    assert.equal(ui.get('pos-cart-body').children.length, 0);
    product.store_2_stock = '0.000';
    await ui.results();
    assert.deepEqual(headings(ui.get('pos-results').innerHTML), [...columns, 'Existencia Tienda 2']);
    product.store_2_stock = null;
    product.warehouse_stock = null;
    await ui.results();
    assert.doesNotMatch(ui.get('pos-results').innerHTML, /Existencia Tienda 2|>0<\/td>/);
    assert.match(ui.get('pos-results').innerHTML, />-<\/td>/);
    product.store_2_stock = '';
    product.warehouse_stock = '';
    await ui.results();
    assert.deepEqual(headings(ui.get('pos-results').innerHTML), columns);
    assert.match(ui.get('pos-results').innerHTML, />-<\/td>/);
});

test('clicking any result cell adds one product at its effective wholesale price', async () => {
    const ui = await pos();
    await ui.results();
    await ui.key('F11');
    assert.match(ui.get('pos-results').innerHTML, /\$80\.00/);
    assert.equal(ui.get('pos-cart-body').children.length, 0);
    const row = ui.element('TR');
    row.dataset.productId = '7';
    const cell = ui.element('TD');
    cell.closest = (selector) => selector === '[data-product-id]' ? row : null;
    await ui.get('pos-results').emit('click', { target: cell });
    assert.equal(ui.get('pos-cart-body').children.length, 1);
    assert.equal(ui.get('pos-total').textContent, '$80.00');
    await ui.get('pos-pending').click();
    assert.equal(ui.requests[0].payload.items[0].quantity, 1);
});

for (const key of ['Enter', ' ']) {
    test(`result rows can be selected with ${JSON.stringify(key)} without affecting global shortcuts`, async () => {
        const ui = await pos();
        await ui.results();
        const row = ui.element('TR');
        row.dataset.productId = '7';
        const event = await ui.get('pos-results').emit('keydown', { key, target: row });
        await ui.document.emit('keydown', event);
        assert.equal(event.defaultPrevented, true);
        assert.equal(ui.get('pos-cart-body').children.length, 1);
        assert.equal(ui.get('pos-total').textContent, '$100.00');
        assert.equal(ui.get('payment-dialog').open, false);
        assert.equal(ui.requests.length, 0);
    });
}

test('selecting a bulk result keeps quantity capture in the existing modal', async () => {
    const ui = await pos({ products: [bulkProduct] });
    await ui.results();
    const row = ui.element('TR');
    row.dataset.productId = String(bulkProduct.id);
    await ui.get('pos-results').emit('click', { target: row });
    assert.equal(ui.get('bulk-dialog').open, true);
    assert.equal(ui.get('bulk-quantity').value, '1.00');
    assert.equal(ui.get('bulk-amount').value, '14.10');
    assert.equal(ui.get('pos-cart-body').children.length, 0);
    await ui.bulk(0.125);
    assert.equal(ui.get('pos-total').textContent, '$1.76');
});

for (const close of ['button', 'Escape']) {
    test(`product photo opens and closes by ${close} without changing the sale`, async () => {
        const ui = await pos({ products: [{ id: 7, name: 'Foto <aceite>', price: 100, wholesale_price: null, image_url: '/storage/aceite.jpg', track_inventory: false }] });
        await ui.scan();
        const before = ui.get('pos-cart-body').children[0].innerHTML;
        assert.match(before, /data-view-image="0"/);
        const trigger = ui.element('BUTTON');
        trigger.dataset.viewImage = '0';
        await ui.get('pos-cart-body').emit('click', { target: trigger });
        assert.equal(ui.get('product-image-dialog').open, true);
        assert.equal(ui.get('product-image-preview').src, '/storage/aceite.jpg');
        assert.equal(ui.get('product-image-title').textContent, 'Foto <aceite>');
        await ui.key('F12');
        await ui.key('Delete');
        assert.equal(ui.get('payment-dialog').open, false);
        if (close === 'button') {
            await ui.get('product-image-close').click();
        } else {
            await ui.get('product-image-dialog').cancel();
        }
        assert.equal(ui.get('product-image-dialog').open, false);
        assert.equal(ui.document.activeElement, trigger);
        assert.equal(ui.get('pos-cart-body').children[0].innerHTML, before);
        assert.equal(ui.get('pos-total').textContent, '$100.00');
        assert.equal(ui.requests.length, 0);
    });
}

test('missing or broken photos show a fallback and cannot trigger an empty image dialog', async () => {
    const ui = await pos();
    await ui.scan();
    assert.match(ui.get('pos-cart-body').children[0].innerHTML, /Sin foto/);
    assert.doesNotMatch(ui.get('pos-cart-body').children[0].innerHTML, /data-view-image/);
    const trigger = ui.element('BUTTON');
    trigger.dataset.viewImage = '0';
    const image = ui.element('IMG');
    image.dataset.productThumbnail = '';
    image.nextElementSibling = { hidden: true };
    image.closest = () => trigger;
    await ui.get('pos-cart-body').emit('error', { target: image });
    assert.equal(image.hidden, true);
    assert.equal(image.nextElementSibling.hidden, false);
    assert.equal(trigger.disabled, true);
    await ui.get('pos-cart-body').emit('click', { target: trigger });
    assert.equal(ui.get('product-image-dialog').open, false);
    await ui.get('product-image-preview').emit('error');
    assert.equal(ui.get('product-image-error').hidden, false);
    assert.equal(ui.get('product-image-preview').hidden, true);
});

for (const store2 of [null, '', '0.000', '12.000']) {
    test(`sale detail displays product fields and large photo with store 2 ${JSON.stringify(store2)}`, async () => {
        const ui = await pos({ products: [{
            id: 7, name: 'Foco LED completo', barcode: 'FOCO-01', category_name: 'Motor & luces',
            price: 325, wholesale_price: null, stock: 3, track_inventory: true,
            warehouse_stock: '8.000', store_2_stock: store2, image_url: '/storage/foco.jpg',
        }] });
        assert.equal(ui.get('pos-cart-table').classList.contains('h-full'), true);
        await ui.scan();
        assert.equal(ui.get('pos-cart-table').classList.contains('h-full'), false);
        const row = ui.get('pos-cart-body').children[0].innerHTML;
        const showStore2 = store2 !== null && store2 !== '';
        assert.match(row, /src="\/storage\/foco\.jpg"/);
        assert.match(row, /class="size-24 rounded-lg object-contain"/);
        assert.match(row, />FOCO-01<\/td>/);
        assert.match(row, />Foco LED completo<\/td>/);
        assert.match(row, />Motor &amp; luces<\/td>/);
        assert.match(row, />8<\/td>/);
        assert.equal([...row.matchAll(/<td\b/g)].length, showStore2 ? 11 : 10);
        assert.equal(ui.get('pos-cart-store2-heading').hidden, !showStore2);
        assert.equal(ui.get('pos-empty-cell').colSpan, showStore2 ? 11 : 10);
        assert.equal(ui.get('pos-total').textContent, '$325.00');
        const remove = ui.element('BUTTON');
        remove.dataset = { action: 'remove', index: '0' };
        await ui.get('pos-cart-body').emit('click', { target: remove });
        assert.equal(ui.get('pos-cart-body').children.length, 0);
        assert.equal(ui.get('pos-cart-store2-heading').hidden, true);
        assert.equal(ui.get('pos-empty-cell').colSpan, 10);
        assert.equal(ui.get('pos-cart-table').classList.contains('h-full'), true);
    });
}

test('editing quantity updates the amount field instead of overwriting the new category column', async () => {
    const ui = await pos();
    await ui.scan();
    const amount = { textContent: '$100.00' };
    const category = { textContent: 'Motor' };
    const row = ui.element('TR');
    row.querySelector = (selector) => selector === '[data-line-total]' ? amount : category;
    const input = ui.element('INPUT');
    input.dataset = { action: 'quantity', index: '0' };
    input.value = '2';
    input.closest = (selector) => selector === 'tr' ? row : selector === '[data-action]' ? input : null;
    await ui.get('pos-cart-body').emit('change', { target: input });
    assert.equal(amount.textContent, '$200.00');
    assert.equal(category.textContent, 'Motor');
    assert.equal(ui.get('pos-total').textContent, '$200.00');
});
