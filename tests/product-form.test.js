import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { setImmediate } from 'node:timers/promises';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../resources/js/admin/product-form.js', import.meta.url), 'utf8');

function productForm({ mode = 'unit', existing = [], products = [], fetcher = null, initial = {} } = {}) {
    let document;
    const element = (tagName = 'DIV') => {
        const listeners = new Map();
        return {
            tagName, dataset: {}, value: '', children: [], disabled: false, hidden: false,
            textContent: '', parent: null,
            append(...children) {
                for (const child of children) {
                    child.parent = this;
                    this.children.push(child);
                }
            },
            replaceChildren() { this.children = []; },
            remove() { this.parent.children = this.parent.children.filter((child) => child !== this); },
            querySelectorAll(selector) {
                const matches = (child) => {
                    if (selector === '[data-kit-row]') return Object.hasOwn(child.dataset, 'kitRow');
                    if (selector === '[data-component-id]') return Object.hasOwn(child.dataset, 'componentId');
                    if (selector === '[data-remove-component]') return Object.hasOwn(child.dataset, 'removeComponent');
                    return false;
                };
                return this.children.flatMap((child) => [...(matches(child) ? [child] : []), ...child.querySelectorAll(selector)]);
            },
            querySelector(selector) { return this.querySelectorAll(selector)[0] || null; },
            addEventListener(type, callback) { listeners.set(type, [...(listeners.get(type) || []), callback]); },
            async emit(type, event = {}) {
                event.preventDefault ??= () => { event.defaultPrevented = true; };
                for (const callback of listeners.get(type) || []) await callback(event);
                await setImmediate();
                return event;
            },
            click() { return this.disabled ? Promise.resolve() : this.emit('click'); },
            focus() { document.activeElement = this; },
        };
    };
    const fields = new Map();
    for (const selector of ['[data-product-inventory]', '[data-kit-fields]', '[data-kit-rows]', '#component-search',
        '[data-component-results]', '[data-component-status]', '#stock', '#min_stock', '#warehouse_stock', '#store_2_stock', '#cost', '#margin', '#price', '#tax_rate']) {
        fields.set(selector, element());
    }
    const get = (selector) => fields.get(selector);
    for (const [name, value] of Object.entries(initial)) {
        get(`#${name}`).value = value;
    }
    const radios = ['unit', 'bulk', 'kit'].map((value) => Object.assign(element('INPUT'), { value, checked: value === mode }));
    const form = Object.assign(element('FORM'), {
        dataset: { componentsConfig: JSON.stringify({ url: '/components', exclude: 42 }) },
        querySelector(selector) {
            if (selector === '[name="sale_mode"]:checked') return radios.find((radio) => radio.checked);
            return get(selector) || null;
        },
        querySelectorAll: (selector) => selector === '[name="sale_mode"]' ? radios : [],
    });
    for (const [index, component] of existing.entries()) {
        const row = element();
        row.dataset = { kitRow: '', index: String(index) };
        const id = element('INPUT');
        id.dataset.componentId = '';
        id.value = String(component.id);
        const quantity = element('INPUT');
        quantity.name = `kit_items[${index}][quantity]`;
        quantity.value = component.quantity;
        const remove = element('BUTTON');
        remove.dataset.removeComponent = '';
        row.append(id, quantity, remove);
        get('[data-kit-rows]').append(row);
    }
    document = { querySelector: () => form, createElement: element, activeElement: null };
    const requests = [];
    const timers = new Map();
    let timerId = 0;
    vm.runInNewContext(source, {
        document, URLSearchParams,
        setTimeout: (callback) => { const id = ++timerId; timers.set(id, callback); return id; },
        clearTimeout: (id) => timers.delete(id),
        fetch: async (url, options) => {
            requests.push({ url, options });
            return fetcher ? fetcher(url) : { ok: true, json: async () => ({ products }) };
        },
    });
    return {
        get, requests, document, radios,
        async mode(value) {
            radios.forEach((radio) => { radio.checked = radio.value === value; });
            await radios.find((radio) => radio.checked).emit('change');
        },
        async search(value) {
            get('#component-search').value = value;
            return get('#component-search').emit('keydown', { key: 'Enter' });
        },
        async flushSearch() {
            for (const callback of timers.values()) await callback();
            timers.clear();
            await setImmediate();
        },
    };
}

test('sale modes change precision and disable hidden fields without truncating inventory values', async () => {
    const ui = productForm();
    ui.get('#stock').value = '2.750';
    ui.get('#min_stock').value = '0.500';
    assert.equal(ui.get('#stock').step, '1');
    assert.equal(ui.get('[data-kit-fields]').disabled, true);

    await ui.mode('bulk');
    assert.equal(ui.get('#stock').step, '0.001');
    assert.equal(ui.get('#min_stock').step, '0.001');
    await ui.mode('kit');
    assert.equal(ui.get('[data-product-inventory]').hidden, true);
    assert.equal(ui.get('[data-product-inventory]').disabled, true);
    assert.equal(ui.get('[data-kit-fields]').disabled, false);
    await ui.mode('unit');
    assert.equal(ui.get('#stock').step, '1');
    assert.equal(ui.get('#stock').value, '2.750');
    assert.equal(ui.get('#min_stock').value, '0.500');
    assert.equal(ui.get('[data-product-inventory]').disabled, false);
});

test('optional location precision follows mode without truncating values or disabling kit metadata', async () => {
    const ui = productForm({ mode: 'bulk', initial: { warehouse_stock: '2.750', store_2_stock: '0' } });

    for (const mode of ['bulk', 'kit', 'unit', 'bulk']) {
        await ui.mode(mode);
        for (const [field, value] of [['#warehouse_stock', '2.750'], ['#store_2_stock', '0']]) {
            assert.equal(ui.get(field).step, mode === 'bulk' ? '0.001' : '1');
            assert.equal(ui.get(field).value, value);
            assert.equal(ui.get(field).disabled, false);
            assert.equal(ui.get(field).hidden, false);
        }
    }
    ui.get('#warehouse_stock').value = '';
    await ui.mode('kit');
    assert.equal(ui.get('#warehouse_stock').value, '');
    assert.equal(ui.get('#store_2_stock').value, '0');
});

test('restored kit rows survive mode changes and remain removable with keyboard buttons', async () => {
    const ui = productForm({ mode: 'kit', existing: [{ id: 7, quantity: '0.125' }] });
    const rows = ui.get('[data-kit-rows]');
    await ui.mode('bulk');
    await ui.mode('kit');
    assert.equal(rows.children[0].children[1].value, '0.125');
    await rows.children[0].querySelector('[data-remove-component]').click();
    assert.equal(rows.children.length, 0);
    assert.equal(ui.document.activeElement, ui.get('#component-search'));
});

test('component search encodes barcodes, excludes self, adds bulk quantities and prevents duplicates', async () => {
    const product = { id: 7, name: '<Aceite & especial>', barcode: '00(ABC)+#', sale_mode: 'bulk', track_inventory: false };
    const ui = productForm({ mode: 'kit', products: [product] });
    const event = await ui.search(product.barcode);
    assert.equal(event.defaultPrevented, true);
    const url = new URL(ui.requests[0].url, 'https://example.test');
    assert.equal(url.searchParams.get('q'), product.barcode);
    assert.equal(url.searchParams.get('exclude'), '42');
    const result = ui.get('[data-component-results]').children[0].children[0];
    assert.match(result.textContent, /Sin control de inventario/);
    await result.click();
    const row = ui.get('[data-kit-rows]').children[0];
    assert.equal(row.children[0].textContent, product.name);
    assert.equal(row.querySelector('[data-component-id]').name, 'kit_items[0][product_id]');
    const quantity = row.children.find((input) => input.name === 'kit_items[0][quantity]');
    assert.equal(quantity.step, '0.001');
    assert.equal(quantity.min, '0.001');
    assert.equal(ui.document.activeElement, quantity);
    await ui.search('Aceite');
    assert.equal(ui.get('[data-component-results]').children[0].children[0].disabled, true);
    await row.querySelector('[data-remove-component]').click();
    await ui.search('Aceite');
    assert.equal(ui.get('[data-component-results]').children[0].children[0].disabled, false);
});

test('piece components use positive whole quantities and restored indices are not reused', async () => {
    const ui = productForm({ mode: 'kit', existing: [{ id: 8, quantity: '2' }], products: [
        { id: 9, name: 'Bujía', sale_mode: 'unit', track_inventory: true },
    ] });
    await ui.search('Bujía');
    await ui.get('[data-component-results]').children[0].children[0].click();
    const quantity = ui.get('[data-kit-rows]').children[1].children.find((input) => input.name === 'kit_items[1][quantity]');
    assert.equal(quantity.step, '1');
    assert.equal(quantity.min, '1');
});

test('debounced searches do not show stale results after another query or mode change', async () => {
    const pending = [];
    const ui = productForm({ mode: 'kit', fetcher: () => new Promise((resolve) => pending.push(resolve)) });
    await ui.search('primero');
    await ui.search('segundo');
    pending[1]({ ok: true, json: async () => ({ products: [{ id: 2, name: 'Segundo', sale_mode: 'unit', track_inventory: true }] }) });
    await setImmediate();
    pending[0]({ ok: true, json: async () => ({ products: [{ id: 1, name: 'Primero', sale_mode: 'unit', track_inventory: true }] }) });
    await setImmediate();
    assert.match(ui.get('[data-component-results]').children[0].children[0].textContent, /Segundo/);
    await ui.mode('unit');
    assert.equal(ui.get('[data-component-results]').children.length, 0);
    ui.get('#component-search').value = 'tercero';
    await ui.get('#component-search').emit('input');
    await ui.flushSearch();
    assert.equal(ui.requests.length, 2);
});

test('lookup errors are announced without changing an existing recipe', async () => {
    const ui = productForm({ mode: 'kit', existing: [{ id: 7, quantity: '2' }], fetcher: async () => ({ ok: false }) });
    await ui.search('Aceite');
    assert.match(ui.get('[data-component-status]').textContent, /No se pudieron buscar/);
    assert.equal(ui.get('[data-kit-rows]').children.length, 1);
});

test('editing the final price keeps the margin the user entered', async () => {
    const ui = productForm({ initial: { cost: '80', margin: '30', price: '120.64', tax_rate: '16' } });

    ui.get('#price').value = '121';
    await ui.get('#price').emit('input');

    assert.equal(ui.get('#price').value, '121');
    assert.equal(ui.get('#margin').value, '30');
});

test('sale mode selection leaves the existing price calculation unchanged', async () => {
    const ui = productForm();
    ui.get('#cost').value = '7.50';
    ui.get('#margin').value = '30';
    ui.get('#tax_rate').value = '16';
    await ui.get('#cost').emit('input');
    assert.equal(ui.get('#price').value, '11.31');
    await ui.mode('kit');
    assert.equal(ui.get('#price').value, '11.31');
});

test('opening an existing kit and editing its mode does not recalculate its saved selling price', async () => {
    const ui = productForm({ mode: 'kit', initial: { cost: '10', margin: '100', price: '20', tax_rate: '16' } });

    assert.equal(ui.get('#price').value, '20');
    await ui.mode('unit');
    await ui.mode('kit');
    assert.equal(ui.get('#price').value, '20');
    await ui.get('#cost').emit('input');
    assert.equal(ui.get('#price').value, '23.20');
});
