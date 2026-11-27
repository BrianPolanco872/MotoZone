import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';
import { formatearTicket, mountTicketPrint } from '../resources/js/ticket-text.js';

const example = {
    business: { name: 'MOTOZONE', address: 'Calle 24x13 y 15', phone: '9995648495', tax_id: 'UICM860807MBA' },
    ticket: {
        width_mm: 58, uppercase: false, lines_above: 0, lines_below: 5,
        show_unit_price: false, full_description: false, show_cashier: true, show_tax_id: true,
        footer: 'Gracias por su compra.', policy: 'Partes eléctricas no hay cambio ni devoluciones',
    },
    sale: {
        folio: 'V-000050', date: '26/11/2026', time: '14:38', cashier: 'motozone',
        total: '366.00', amount_paid: '400.00', change_amount: '34.00', payment_method: 'Efectivo',
        items: [
            { name: 'BALATA DISCO CS125, AT110, CROSS300, VENTO 350/BKP023', quantity: '1', unit_price: '35.00', line_total: '35.00' },
            { name: 'ACEITE RALOY 20W-50 4T MINERAL MOTO', quantity: '1', unit_price: '96.00', line_total: '96.00' },
            { name: 'CADENA REFORZADA 428H-136L/7503059050499', quantity: '1', unit_price: '165.00', line_total: '165.00' },
            { name: 'BUJÍA NGK C9E, FZ16 FZ2.0 CARGO150, YAMAHA150, SUZUKI', quantity: '1', unit_price: '55.00', line_total: '55.00' },
            { name: 'RONDANA', quantity: '3', unit_price: '5.00', line_total: '15.00' },
        ],
    },
};

const snapshot = [
    '            MOTOZONE',
    '        Calle 24x13 y 15',
    '        Tel. 9995648495',
    '       RFC UICM860807MBA',
    ' ',
    '26/11/2026                 14:38',
    'Folio:                  V-000050',
    'Atendio: motozone',
    ' ',
    'Cant. Producto           Importe',
    '================================',
    '    1 BALATA DISCO CS125  $35.00',
    '    1 ACEITE RALOY 20W-5  $96.00',
    '    1 CADENA REFORZADA 4 $165.00',
    '    1 BUJIA NGK C9E, FZ1  $55.00',
    '    3 RONDANA             $15.00',
    '================================',
    '      No. de articulos: 7',
    ' ',
    'Total                    $366.00',
    'Pago con                 $400.00',
    'Cambio                    $34.00',
    '    Metodo de pago: Efectivo',
    ' ',
    '     Gracias por su compra.',
    'Partes electricas no hay cambio',
    'ni devoluciones',
    ' ', ' ', ' ', ' ', ' ',
].join('\n');

test('V-000050 matches the exact 58 mm snapshot, including alignment and five feed rows', () => {
    assert.equal(formatearTicket(example), snapshot);
});

for (const [width, columns] of [[58, 32], [80, 42]]) {
    test(`${width} mm stays within ${columns} ASCII columns with long words, decimals and large saved amounts`, () => {
        const receipt = structuredClone(example);
        const longWord = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.repeat(3);
        Object.assign(receipt.business, { name: 'Compañía Ángeles °\tMotos\r\nNorte', address: longWord });
        Object.assign(receipt.ticket, {
            width_mm: width, uppercase: true, full_description: true, show_unit_price: true,
            show_sku: true, show_tax: true, lines_above: 2, lines_below: 3,
        });
        Object.assign(receipt.sale, {
            total: '9999999999.99',
            tax_total: '1379310344.83',
            items: [
                { name: `Gránel ${longWord} 1.5 Kg`, quantity: '1.5', unit_price: '13.00', line_total: '1234567890.12', sku: 'SKU-Ñ', sale_mode: 'bulk', returned_quantity: '0.125' },
                { name: 'Paquete', quantity: '12', unit_price: '27.50', line_total: '330.00', sale_mode: 'kit', returned_quantity: '2' },
                { name: 'Bujía Grande', quantity: '2', unit_price: '10.00', line_total: '20.00' },
            ],
        });

        const output = formatearTicket(receipt);
        const lines = output.split('\n');
        assert.deepEqual(lines.slice(0, 2), [' ', ' ']);
        assert.deepEqual(lines.slice(-3), [' ', ' ', ' ']);
        assert.ok(lines.includes('='.repeat(columns)));
        for (const line of lines) {
            assert.ok(line.length <= columns, `${line.length} > ${columns}: ${line}`);
            assert.match(line, /^[\x20-\x7e]*$/);
        }
        assert.match(output, /COMPANIA ANGELES MOTOS NORTE/);
        assert.match(output, /1\.5 .*?\$1234567890\.12/);
        assert.match(output, /\$9999999999\.99/);
        assert.match(output, /\$1379310344\.83/);
        assert.match(output, /SKU: SKU-N/);
        const continuation = output.split('$1234567890.12\n')[1].split('SKU: SKU-N')[0].trim().replace(/\s+/g, ' ');
        assert.ok(continuation.endsWith(' 1.5 KG'));
        assert.equal(continuation.slice(0, -' 1.5 KG'.length).replaceAll(' ', ''), longWord);
        assert.match(output.replace(/\s+/g, ' '), /\$13\.00 POR UNIDAD/);
        assert.match(output.replace(/\s+/g, ' '), /\$27\.50 POR PAQUETE/);
        assert.match(output.replace(/\s+/g, ' '), /CANTIDAD DEVUELTA A GRANEL: 0\.125/);
        assert.match(output.replace(/\s+/g, ' '), /CANTIDAD DEVUELTA EN PAQUETES: 2/);
        assert.match(output, /NO\. DE ARTICULOS: 14/);
        assert.match(output, /PARTIDAS A GRANEL: 1/);
    });
}

test('complete descriptions and unit prices switch independently and optional header fields can be hidden', () => {
    const receipt = structuredClone(example);
    Object.assign(receipt.ticket, { show_cashier: false, show_tax_id: false, lines_above: 0, lines_below: 0 });
    const concise = formatearTicket(receipt);
    assert.doesNotMatch(concise, /BKP023|VENTO|por unidad|Atendio|RFC/);
    assert.match(concise, /BALATA DISCO CS125  \$35\.00/);
    assert.ok(concise.endsWith('ni devoluciones'));

    receipt.ticket.full_description = true;
    const full = formatearTicket(receipt);
    assert.match(full, /350\/BKP023/);
    assert.doesNotMatch(full, /por unidad/);

    receipt.ticket.full_description = false;
    receipt.ticket.show_unit_price = true;
    const withPrices = formatearTicket(receipt);
    assert.match(withPrices, /\$5\.00 por unidad/);
    assert.doesNotMatch(withPrices, /BKP023/);
});

test('the text document writes the formatted ticket as text before requesting one print', () => {
    const printed = [];
    const paper = {
        dataset: { receipt: JSON.stringify(example), ticketAutoPrint: '' },
        ownerDocument: { readyState: 'loading' },
        set innerHTML(_) { throw new Error('Do not parse ticket data as HTML'); },
    };
    const browser = Object.assign(new EventTarget(), { print: () => printed.push(paper.textContent) });

    mountTicketPrint(paper, browser);
    assert.equal(paper.textContent, snapshot);
    assert.deepEqual(printed, []);
    browser.dispatchEvent(new Event('load'));
    browser.dispatchEvent(new Event('load'));
    assert.deepEqual(printed, [snapshot]);
});

test('the embedded text document renders 32 columns and never requests printing itself', () => {
    const paper = { dataset: { receipt: JSON.stringify(example) }, ownerDocument: { readyState: 'complete' } };
    const browser = Object.assign(new EventTarget(), { print: () => assert.fail('The embedding page owns printing') });

    mountTicketPrint(paper, browser);
    browser.dispatchEvent(new Event('load'));
    assert.equal(paper.textContent, snapshot);
    assert.equal(Math.max(...paper.textContent.split('\n').map((line) => line.length)), 32);
});

test('configuration prints the exact text preview or the same legacy graphic example according to the draft mode', () => {
    const source = readFileSync(new URL('../resources/js/ticket-settings.js', import.meta.url), 'utf8')
        .replace("import { formatearTicket } from './ticket-text.js';", '')
        .replace("import { printTicketFrame } from './ticket-print.js';", '');
    const printed = [];
    const element = (attributes = {}) => {
        const events = new Map();
        return Object.assign({
            dataset: {},
            hidden: false,
            addEventListener: (type, handler) => events.set(type, handler),
            emit: (type, event) => events.get(type)?.(event),
            removeAttribute() {},
        }, attributes);
    };
    const mode = element({ value: 'text' });
    const qrInput = element();
    const form = element({ elements: { namedItem: (name) => name === 'qr_image' ? qrInput : mode } });
    const textPreview = element({ tagName: 'PRE' });
    const paper = element({ tagName: 'ARTICLE', textContent: 'Ejemplo gráfico con MZ y QR', style: { setProperty() {} } });
    const textControls = element();
    const graphicSummary = element();
    const graphicPreview = element({ querySelector: () => graphicSummary });
    const printButton = element();
    const elements = {
        form, '#ticket-paper': paper, '#ticket-qr-status': element(), '[data-ticket-width-label]': element(),
        '[data-ticket-text-controls]': textControls, '[data-ticket-text-panel]': element(),
        '[data-ticket-graphic-preview]': graphicPreview, '[data-ticket-qr]': element(),
        '[data-ticket-text-preview]': textPreview,
    };
    const settings = element({
        dataset: { saved: JSON.stringify({ business: {}, ticket: { qr_image_url: null } }) },
        querySelector: (selector) => elements[selector],
        querySelectorAll: (selector) => selector === '[data-ticket-print]' ? [printButton] : [],
    });
    const styles = element({ id: 'ticket-preview-styles' });
    const document = {
        querySelector: () => settings,
        getElementById: (id) => id === styles.id ? styles : null,
    };
    const window = element({
        matchMedia: () => element({ matches: true }),
        print() { assert.fail('The settings page must not print itself'); },
    });
    const fields = new Map([
        ['business[name]', 'MotoZóne'], ['ticket[width_mm]', '58'], ['ticket[lines_below]', '0'], ['ticket[show_cashier]', '1'],
    ]);
    class FormData {
        get(key) { return fields.get(key) ?? null; }
        getAll(key) { return fields.has(key) ? [fields.get(key)] : []; }
    }
    const printTicketFrame = ({ content, styles: draftStyles, url }) => {
        assert.equal(draftStyles, styles);
        assert.equal(url, undefined);
        printed.push({ tagName: content.tagName, textContent: content.textContent, dataset: { ...content.dataset } });
    };
    vm.runInNewContext(source, { document, window, FormData, formatearTicket, printTicketFrame });
    const draft = textPreview.textContent;
    assert.match(draft, /MotoZone/);
    assert.match(draft, /Atendio: motozone/);
    printButton.emit('click');
    assert.equal(printed[0].tagName, 'PRE');
    assert.equal(printed[0].textContent, draft);
    assert.equal(printed[0].dataset.paperWidth, '58');

    mode.value = 'graphic';
    form.emit('change');
    assert.equal(textControls.disabled, true);
    assert.equal(graphicPreview.open, true);
    printButton.emit('click');
    assert.equal(printed[1].tagName, 'ARTICLE');
    assert.equal(printed[1].textContent, paper.textContent);

    mode.value = 'text';
    fields.set('ticket[width_mm]', '80');
    form.emit('input');
    assert.equal(textControls.disabled, false);
    assert.equal(graphicPreview.open, false);
    printButton.emit('click');
    assert.equal(printed[2].tagName, 'PRE');
    assert.equal(printed[2].textContent, textPreview.textContent);
    assert.equal(printed[2].dataset.paperWidth, '80');
});
