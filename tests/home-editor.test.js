import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../resources/js/home-editor.js', import.meta.url), 'utf8');

function mediaEditor() {
    const element = (values = {}) => {
        const listeners = new Map();
        const classes = new Set();
        return {
            value: '', checked: false, defaultChecked: false, disabled: false, hidden: false, files: [], dataset: {},
            classList: {
                add: (name) => classes.add(name),
                remove: (name) => classes.delete(name),
                contains: (name) => classes.has(name),
                toggle: (name, enabled) => enabled ? classes.add(name) : classes.delete(name),
            },
            addEventListener(type, callback) {
                listeners.set(type, [...(listeners.get(type) || []), callback]);
            },
            dispatchEvent(event) {
                for (const callback of listeners.get(event.type) || []) callback(event);
            },
            getAttribute(name) { return this[name] ?? null; },
            removeAttribute(name) { delete this[name]; },
            setCustomValidity(message) { this.validationMessage = message; },
            replaceChildren() {},
            ...values,
        };
    };
    const originalUrl = 'https://images.example.test/original.jpg';
    const elements = new Map([
        ['main', element()],
        ['main-url', element({ value: originalUrl, dataset: { originalUrl } })],
        ['main-url-status', element()],
        ['main-wrap', element()],
        ['main-preview', element({ src: originalUrl })],
        ['main-remove', element({ name: 'remove_image', value: '1' })],
    ]);
    const savedGallery = [1, 2].map((index) => {
        const remove = element({ name: 'remove_gallery[]', value: `https://images.example.test/gallery-${index}.jpg` });
        const item = element({ querySelector: () => remove });
        return { item, remove };
    });
    const form = element();
    form.reset = () => {
        form.dispatchEvent(new Event('reset'));
        savedGallery.forEach(({ remove }) => { remove.checked = remove.defaultChecked; });
    };
    const media = {
        querySelector: (selector) => elements.get(selector.slice('[data-media-'.length, -1)) ?? null,
        querySelectorAll: (selector) => selector === '[data-media-gallery-item]' ? savedGallery.map(({ item }) => item) : [],
        closest: () => form,
    };
    const editor = {
        querySelector: () => null,
        querySelectorAll: (selector) => selector === '[data-media]' ? [media] : [],
        addEventListener() {},
    };
    vm.runInNewContext(source, {
        document: { querySelectorAll: () => [editor], getElementById: () => null },
        window: { location: { hash: '' }, addEventListener() {} },
        URL, Event,
    });

    return { get: (name) => elements.get(name), savedGallery, form };
}

test('quitar la principal conserva la orden de borrado y limpia su vista previa', () => {
    const { get } = mediaEditor();
    get('main-remove').checked = true;

    get('main-remove').dispatchEvent(new Event('change'));

    assert.equal(get('main-remove').checked, true);
    assert.equal(get('main-remove').value, '1');
    assert.equal(get('main-url').value, '');
    assert.equal(get('main-url').validationMessage, '');
    assert.equal(get('main').value, '');
    assert.equal(get('main-wrap').hidden, true);
    assert.equal(get('main-preview').getAttribute('src'), null);
});

test('una vista previa pendiente no restaura una principal marcada para quitar', () => {
    const { get } = mediaEditor();
    get('main-url').value = 'https://images.example.test/replacement.jpg';
    get('main-url').dispatchEvent(new Event('input'));
    const onload = get('main-preview').onload;
    const onerror = get('main-preview').onerror;

    get('main-remove').checked = true;
    get('main-remove').dispatchEvent(new Event('change'));
    onload();
    onerror();

    assert.equal(get('main-remove').checked, true);
    assert.equal(get('main-url').validationMessage, '');
    assert.equal(get('main-wrap').hidden, true);
    assert.equal(get('main-preview').getAttribute('src'), null);
});

test('la X oculta la foto guardada sin perder la casilla que se enviará al guardar', () => {
    const { savedGallery } = mediaEditor();
    const { item, remove } = savedGallery[0];
    remove.checked = true;

    remove.dispatchEvent(new Event('change'));

    assert.equal(item.hidden, true);
    assert.equal(item.classList.contains('hidden'), true);
    assert.equal(remove.checked, true);
    assert.equal(remove.disabled, false);
    assert.equal(remove.name, 'remove_gallery[]');
    assert.equal(remove.value, 'https://images.example.test/gallery-1.jpg');
    assert.equal(savedGallery[1].item.hidden, false);
    assert.equal(savedGallery[1].remove.checked, false);
});

test('descartar restaura la miniatura y desmarca la eliminación', () => {
    const { savedGallery, form } = mediaEditor();
    const { item, remove } = savedGallery[0];
    remove.checked = true;
    remove.dispatchEvent(new Event('change'));

    form.reset();

    assert.equal(item.hidden, false);
    assert.equal(item.classList.contains('hidden'), false);
    assert.equal(remove.checked, false);
    assert.equal(savedGallery[1].item.hidden, false);
});
