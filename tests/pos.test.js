import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { setImmediate } from 'node:timers/promises';
import test from 'node:test';
import vm from 'node:vm';
import numeral from 'numeral';

const source = readFileSync(new URL('../resources/js/pos/pos.js', import.meta.url), 'utf8')
    .replace("import numeral from 'numeral';", '');

async function pos({ pending = null, ticket = null } = {}) {
    const elements = new Map();
    const requests = [];
    const confirmations = [];
    const prints = [];
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
                if (selector === 'input, textarea, select' && ['INPUT', 'TEXTAREA', 'SELECT'].includes(this.tagName)) return this;
                return null;
            },
            querySelector() { return null; },
            querySelectorAll() { return []; },
            focus() { document.activeElement = this; },
            select() {}, reset() {}, scrollIntoView() {},
            showModal() { this.open = true; },
            close() { this.open = false; },
        };
    };
    for (const id of ('pos-config pos-search pos-results pos-cart-body pos-empty pos-items-count pos-subtotal pos-tax pos-total '
        + 'pos-wholesale pos-error pending-banner payment-dialog payment-amount payment-total payment-change payment-error '
        + 'payment-confirm payment-method payment-amount-group cash-dialog cash-type cash-title cash-error start-day-dialog '
        + 'custom-product-dialog custom-product-form custom-product-name custom-product-price custom-product-quantity '
        + 'custom-product-error pos-charge pos-pending pos-new-sale pos-clear '
        + 'last-sale-indicator last-sale-folio pos-print-ticket ticket-print-container').split(' ')) {
        elements.set(id, element(id.endsWith('-dialog') ? 'DIALOG' : 'DIV'));
    }
    const get = (id) => elements.get(id);
    get('pos-search').tagName = 'INPUT';
    get('payment-method').tagName = 'SELECT';
    get('payment-method').value = 'cash';
    get('ticket-print-container').classList.add('hidden');
    const cashIn = element('BUTTON');
    cashIn.dataset.cashOpen = 'in';
    const cashOut = element('BUTTON');
    cashOut.dataset.cashOpen = 'out';
    const exact = element('BUTTON');
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
        querySelectorAll: (selector) => selector === '[data-cash-open]' ? [cashIn, cashOut] : [],
    });
    get('pos-config').textContent = JSON.stringify({
        permissions: { wholesale: true, price_edit: true }, csrf: 'test',
        routes: { search: '/search', store: '/store', show: '/sale/__SALE__', charge: '/sale/__SALE__/charge', pending: '/pending', ticketData: ticket ? '/ticket/__SALE__' : undefined },
    });
    const product = { id: 7, name: 'Aceite', price: 100, wholesale_price: 80, tax_rate: 0, track_inventory: false };
    vm.runInNewContext(source, {
        document, numeral, URLSearchParams, setTimeout, clearTimeout,
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
            return { ok: true, json: async () => url.startsWith('/search') ? { products: [product] } : { sale: pending } };
        },
    }, { filename: 'resources/js/pos/pos.js' });
    await setImmediate();
    return {
        get, requests, confirmations, prints, document, element, exact,
        async scan() {
            get('pos-search').value = '750123';
            await get('pos-search').emit('keydown', { key: 'Enter' });
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
    for (const id of ['payment-dialog', 'cash-dialog', 'custom-product-dialog', 'start-day-dialog']) {
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
