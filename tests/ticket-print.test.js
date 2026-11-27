import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { setImmediate } from 'node:timers/promises';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../resources/js/ticket-print.js', import.meta.url), 'utf8').replace('export function', 'function');

function fixture({ embedded = true, text = 'Ticket válido', failPrint = false } = {}) {
    const timers = new Map();
    const errors = [];
    const frames = [];
    const printed = [];
    const contents = [];
    const sheets = [];
    const classes = new Set();
    const frameDocument = {
        querySelector: (selector) => selector === '[data-ticket-print-document]' && text !== null ? { textContent: text }
            : selector === '.ticket-sheet' && text === null ? {} : null,
        head: { append: (styles) => sheets.push(styles) },
        body: {
            hasAttribute: (name) => embedded && name === 'data-ticket-embedded',
            append: (paper) => contents.push(paper),
            classList: { add: (name) => classes.add(name) },
        },
        importNode: (node) => ({ ...node }),
    };
    const frameWindow = Object.assign(new EventTarget(), {
        focus() {},
        print() {
            if (failPrint) throw new Error('Printing denied');
            printed.push(contents.at(-1) ?? text);
        },
    });
    const button = Object.assign(new EventTarget(), { dataset: { printSaleTicket: '/pos/ventas/99/ticket' } });
    const document = {
        querySelectorAll: (selector) => selector === '[data-print-sale-ticket]' ? [button] : [],
        body: { append: (frame) => frames.push(frame) },
        createElement: (tag) => Object.assign(new EventTarget(), {
            tag, style: {}, setAttribute() {}, contentWindow: frameWindow, contentDocument: frameDocument,
            remove() { this.removed = true; },
        }),
    };
    const location = new URL('https://motozone.test/pos');
    const printTicketFrame = vm.runInNewContext(`${source}\nprintTicketFrame;`, {
        window: { location, alert: (message) => errors.push(message) }, document, URL,
        setTimeout: (action, delay) => { timers.set(delay, action); return delay; },
        clearTimeout: (id) => timers.delete(id),
    });
    return { button, frames, timers, errors, printed, contents, sheets, classes, frameWindow, printTicketFrame };
}

test('a listing button embeds only the selected ticket, prints once, and removes the iframe after print', async () => {
    const ui = fixture();
    ui.button.dispatchEvent(new Event('click'));
    ui.button.dispatchEvent(new Event('click'));
    assert.equal(ui.frames.length, 1);
    const [frame] = ui.frames;
    assert.equal(frame.tag, 'iframe');
    assert.equal(frame.src, 'https://motozone.test/pos/ventas/99/ticket?embed=1');
    assert.deepEqual(ui.printed, []);
    frame.dispatchEvent(new Event('load'));
    frame.dispatchEvent(new Event('load'));
    await setImmediate();
    assert.deepEqual(ui.printed, ['Ticket válido']);
    ui.frameWindow.dispatchEvent(new Event('afterprint'));
    assert.equal(frame.removed, true);
    assert.equal(ui.timers.size, 0);
});

test('a legacy print parameter is removed and a graphic document uses the timeout if afterprint does not fire', () => {
    const ui = fixture({ text: null });
    ui.printTicketFrame({ url: '/pos/ventas/23/ticket?print=1' });
    assert.equal(new URL(ui.frames[0].src).search, '?embed=1');
    ui.frames[0].dispatchEvent(new Event('load'));
    assert.equal(ui.printed.length, 1);
    ui.timers.get(60000)();
    assert.equal(ui.frames[0].removed, true);
    assert.deepEqual(ui.errors, []);
});

for (const [reason, options] of [
    ['login page or an unauthorized response', { embedded: false }],
    ['unrendered text', { text: '' }],
    ['a browser that rejects printing', { failPrint: true }],
]) {
    test(`the iframe is removed and an error is reported for ${reason}`, () => {
        const ui = fixture(options);
        ui.printTicketFrame({ url: '/pos/ventas/23/ticket' });
        ui.frames[0].dispatchEvent(new Event('load'));
        assert.equal(ui.frames[0].removed, true);
        assert.match(ui.errors[0], /No se pudo imprimir/);
        assert.deepEqual(ui.printed, []);
    });
}

test('the iframe reports initial load timeout and refuses an external ticket URL', () => {
    const ui = fixture();
    ui.printTicketFrame({ url: 'https://another.test/ticket' });
    assert.equal(ui.frames.length, 0);
    assert.match(ui.errors[0], /No se pudo abrir/);
    ui.printTicketFrame({ url: '/pos/ventas/23/ticket' });
    ui.timers.get(15000)();
    assert.equal(ui.frames[0].removed, true);
    assert.match(ui.errors[1], /No se pudo imprimir/);
});

test('draft printing copies the preview and styles and an optional QR decode failure does not block it', async () => {
    const ui = fixture();
    const content = { id: 'ticket-paper', tagName: 'ARTICLE', textContent: 'Borrador', querySelectorAll: () => [
        { decode: () => Promise.reject(new Error('QR unavailable')) },
    ] };
    const styles = { id: 'ticket-preview-styles' };
    ui.printTicketFrame({ content, styles });
    const [frame] = ui.frames;
    assert.equal(frame.src, undefined);
    assert.match(frame.srcdoc, /^<!doctype html>/);
    frame.dispatchEvent(new Event('load'));
    await setImmediate();
    assert.equal(ui.printed[0].id, 'ticket-print-root');
    assert.equal(ui.printed[0].textContent, 'Borrador');
    assert.notEqual(ui.printed[0], content);
    assert.equal(content.id, 'ticket-paper');
    assert.deepEqual(ui.sheets, [styles]);
    assert.ok(ui.classes.has('ticket-example-print'));
    ui.frameWindow.dispatchEvent(new Event('afterprint'));
    assert.equal(frame.removed, true);
});
