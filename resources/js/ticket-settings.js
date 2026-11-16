const ticketSettings = document.querySelector('[data-ticket-settings]');

if (ticketSettings) {
    const form = ticketSettings.querySelector('form');
    const saved = JSON.parse(ticketSettings.dataset.saved);
    const tabs = [...ticketSettings.querySelectorAll('[data-ticket-tab]')];
    const panels = [...ticketSettings.querySelectorAll('[data-ticket-panel]')];
    const desktop = window.matchMedia('(min-width: 1280px)');
    const paper = ticketSettings.querySelector('#ticket-paper');
    const qrInput = form.elements.namedItem('qr_image');
    const qrImage = ticketSettings.querySelector('[data-ticket-qr]');
    const qrStatus = ticketSettings.querySelector('#ticket-qr-status');
    let activeTab = 'fields';
    let qrUrl = saved.ticket.qr_image_url;
    let localQrUrl = null;
    let qrLoaded = false;

    const updatePreview = () => {
        const values = new FormData(form);
        ticketSettings.querySelectorAll('[data-ticket-text]').forEach((element) => {
            const [group, key] = element.dataset.ticketText.split('.');
            element.textContent = values.get(`${group}[${key}]`) ?? '';
        });
        ticketSettings.querySelectorAll('[data-ticket-visible]').forEach((element) => {
            const key = element.dataset.ticketVisible;
            element.hidden = values.getAll(`ticket[${key}]`).at(-1) !== '1' || (key === 'show_qr' && !qrLoaded);
        });
        const width = values.get('ticket[width_mm]') === '58' ? '58' : '80';
        paper.style.setProperty('--ticket-width', `${width}mm`);
        ticketSettings.querySelector('[data-ticket-width-label]').textContent = width;
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
            let pageStyle = document.getElementById('ticket-settings-page-style');
            if (!pageStyle) {
                pageStyle = document.createElement('style');
                pageStyle.id = 'ticket-settings-page-style';
                document.head.append(pageStyle);
            }
            const width = new FormData(form).get('ticket[width_mm]') === '58' ? 58 : 80;
            pageStyle.textContent = `@media print { @page { size: ${width}mm auto; margin: 4mm; } }`;
            window.print();
        });
    });
    desktop.addEventListener('change', updatePanels);
    loadQr();
    updatePanels();
}
