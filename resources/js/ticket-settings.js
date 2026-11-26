import { formatearTicket } from './ticket-text.js';

const ticketSettings = document.querySelector('[data-ticket-settings]');

if (ticketSettings) {
    const form = ticketSettings.querySelector('form');
    const saved = JSON.parse(ticketSettings.dataset.saved);
    const tabs = [...ticketSettings.querySelectorAll('[data-ticket-tab]')];
    const panels = [...ticketSettings.querySelectorAll('[data-ticket-panel]')];
    const desktop = window.matchMedia('(min-width: 1280px)');
    const paper = ticketSettings.querySelector('#ticket-paper');
    const printMode = form.elements.namedItem('ticket[print_mode]');
    const textControls = ticketSettings.querySelector('[data-ticket-text-controls]');
    const textPanel = ticketSettings.querySelector('[data-ticket-text-panel]');
    const textPreview = ticketSettings.querySelector('[data-ticket-text-preview]');
    const graphicPreview = ticketSettings.querySelector('[data-ticket-graphic-preview]');
    const graphicSummary = graphicPreview.querySelector('summary');
    const qrInput = form.elements.namedItem('qr_image');
    const qrImage = ticketSettings.querySelector('[data-ticket-qr]');
    const qrStatus = ticketSettings.querySelector('#ticket-qr-status');
    let activeTab = 'fields';
    let qrUrl = saved.ticket.qr_image_url;
    let localQrUrl = null;
    let qrLoaded = false;
    let lastMode;

    const exampleSale = {
        folio: 'V-000050', date: '26/11/2026', time: '14:38', cashier: 'motozone',
        total: 366, tax_total: 50.48, amount_paid: 400, change_amount: 34, payment_method: 'Efectivo',
        items: [
            { name: 'BALATA DISCO CS125, AT110, CROSS300, VENTO 350/BKP023', sku: 'BKP023', quantity: 1, unit_price: 35, line_total: 35 },
            { name: 'ACEITE RALOY 20W-50 4T MINERAL MOTO', sku: 'ACE-001', quantity: 1, unit_price: 96, line_total: 96 },
            { name: 'CADENA REFORZADA 428H-136L/7503059050499', sku: 'CAD-428', quantity: 1, unit_price: 165, line_total: 165 },
            { name: 'BUJÍA NGK C9E, FZ16 FZ2.0 CARGO150, YAMAHA150, SUZUKI', sku: 'NGK-C9E', quantity: 1, unit_price: 55, line_total: 55 },
            { name: 'RONDANA', sku: 'RON-001', quantity: 3, unit_price: 5, line_total: 15 },
        ],
    };

    const updatePreview = () => {
        const isGraphic = printMode.value === 'graphic';
        textControls.hidden = isGraphic;
        textControls.disabled = isGraphic;
        textPanel.hidden = isGraphic;
        graphicSummary.hidden = isGraphic;
        if (lastMode !== printMode.value) graphicPreview.open = isGraphic;
        lastMode = printMode.value;

        const values = new FormData(form);
        const checked = (key) => values.getAll(`ticket[${key}]`).at(-1) === '1';
        ticketSettings.querySelectorAll('[data-ticket-text]').forEach((element) => {
            const [group, key] = element.dataset.ticketText.split('.');
            element.textContent = values.get(`${group}[${key}]`) ?? '';
        });
        ticketSettings.querySelectorAll('[data-ticket-visible]').forEach((element) => {
            const key = element.dataset.ticketVisible;
            element.hidden = !checked(key) || (key === 'show_qr' && !qrLoaded);
        });
        const width = values.get('ticket[width_mm]') === '58' ? '58' : '80';
        paper.style.setProperty('--ticket-width', `${width}mm`);
        ticketSettings.querySelector('[data-ticket-width-label]').textContent = width;

        if (!isGraphic) {
            const business = Object.fromEntries(['name', 'address', 'phone', 'tax_id'].map((key) => [key, values.get(`business[${key}]`)]));
            const textPreferences = Object.fromEntries(['show_tax_id', 'show_cashier', 'show_sku', 'show_tax', 'uppercase', 'show_unit_price', 'full_description']
                .map((key) => [key, checked(key)]));
            textPreview.dataset.paperWidth = width;
            textPreview.textContent = formatearTicket({
                business, sale: exampleSale,
                ticket: {
                    ...textPreferences,
                    width_mm: width,
                    lines_above: values.get('ticket[lines_above]'),
                    lines_below: values.get('ticket[lines_below]'),
                    footer: values.get('ticket[footer]'),
                    policy: values.get('ticket[policy]'),
                },
            });
        }
    };

    const updatePanels = () => {
        tabs.forEach((tab) => {
            const selected = tab.dataset.ticketTab === activeTab;
            tab.setAttribute('aria-selected', String(selected));
            tab.tabIndex = selected ? 0 : -1;
        });
        panels.forEach((panel) => {
            panel.hidden = panel.dataset.ticketPanel !== activeTab && !(desktop.matches && panel.dataset.ticketPanel === 'preview');
        });
    };

    const loadQr = () => {
        qrLoaded = false;
        qrImage.hidden = true;
        if (qrUrl) {
            qrImage.src = qrUrl;
        } else {
            qrImage.removeAttribute('src');
        }
        updatePreview();
    };

    qrImage.addEventListener('load', () => {
        qrLoaded = true;
        qrImage.hidden = false;
        updatePreview();
    });
    qrImage.addEventListener('error', () => {
        qrLoaded = false;
        qrImage.hidden = true;
        qrStatus.textContent = 'No se pudo cargar la imagen QR. Selecciona otro PNG.';
        updatePreview();
    });

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => {
            activeTab = tab.dataset.ticketTab;
            updatePanels();
        });
        tab.addEventListener('keydown', (event) => {
            let next;
            if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
            if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
            if (event.key === 'Home') next = 0;
            if (event.key === 'End') next = tabs.length - 1;
            if (next === undefined) return;
            event.preventDefault();
            tabs[next].click();
            tabs[next].focus();
        });
    });

    qrInput.addEventListener('change', () => {
        const file = qrInput.files[0];
        qrInput.setCustomValidity('');
        if (localQrUrl) URL.revokeObjectURL(localQrUrl);
        localQrUrl = null;
        qrUrl = saved.ticket.qr_image_url;
        if (file && (file.type !== 'image/png' || file.size > 1024 * 1024)) {
            const message = 'Selecciona una imagen PNG de máximo 1 MB.';
            qrInput.setCustomValidity(message);
            qrStatus.textContent = message;
            qrInput.reportValidity();
        } else if (file) {
            localQrUrl = URL.createObjectURL(file);
            qrUrl = localQrUrl;
            qrStatus.textContent = `${file.name} · Pendiente de guardar`;
        } else {
            qrStatus.textContent = qrUrl ? 'Hay una imagen guardada.' : 'No hay una imagen guardada.';
        }
        loadQr();
    });

    form.addEventListener('input', updatePreview);
    form.addEventListener('change', updatePreview);
    form.addEventListener('invalid', (event) => {
        const panel = event.target.closest('[data-ticket-panel]');
        if (panel) {
            activeTab = panel.dataset.ticketPanel;
            updatePanels();
        }
    }, true);
    form.addEventListener('reset', (event) => {
        event.preventDefault();
        for (const group of ['business', 'ticket']) {
            for (const [key, value] of Object.entries(saved[group])) {
                form.querySelectorAll(`[name="${group}[${key}]"]`).forEach((field) => {
                    if (field.type === 'checkbox') {
                        field.checked = value === true || value === 1 || value === '1';
                    } else if (field.type !== 'hidden') {
                        field.value = value ?? '';
                    }
                });
            }
        }
        qrInput.value = '';
        qrInput.setCustomValidity('');
        if (localQrUrl) URL.revokeObjectURL(localQrUrl);
        localQrUrl = null;
        qrUrl = saved.ticket.qr_image_url;
        qrStatus.textContent = qrUrl ? 'Hay una imagen guardada.' : 'No hay una imagen guardada.';
        loadQr();
        updatePreview();
    });
    ticketSettings.querySelectorAll('[data-ticket-print]').forEach((button) => {
        button.addEventListener('click', () => {
            updatePreview();
            window.print();
        });
    });
    window.addEventListener('beforeprint', () => {
        if (document.getElementById('ticket-print-root')) return;

        const printRoot = (printMode.value === 'graphic' ? paper : textPreview).cloneNode(true);
        printRoot.id = 'ticket-print-root';
        document.body.append(printRoot);
        document.body.classList.add('ticket-example-print');
    });
    window.addEventListener('afterprint', () => {
        document.getElementById('ticket-print-root')?.remove();
        document.body.classList.remove('ticket-example-print');
    });
    desktop.addEventListener('change', updatePanels);
    loadQr();
    updatePanels();
}
