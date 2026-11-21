const form = document.querySelector('[data-product-form]');

if (form) {
    const modes = form.querySelectorAll('[name="sale_mode"]');
    const inventoryFields = form.querySelector('[data-product-inventory]');
    const kitFields = form.querySelector('[data-kit-fields]');
    const rows = form.querySelector('[data-kit-rows]');
    const search = form.querySelector('#component-search');
    const results = form.querySelector('[data-component-results]');
    const status = form.querySelector('[data-component-status]');
    const config = JSON.parse(form.dataset.componentsConfig);
    let nextIndex = Math.max(-1, ...Array.from(rows.querySelectorAll('[data-kit-row]'), (row) => Number(row.dataset.index) || 0)) + 1;
    let searchVersion = 0;
    let searchTimer;

    const updateMode = () => {
        const mode = form.querySelector('[name="sale_mode"]:checked')?.value || 'unit';
        const isKit = mode === 'kit';
        inventoryFields.hidden = isKit;
        inventoryFields.disabled = isKit;
        kitFields.hidden = !isKit;
        kitFields.disabled = !isKit;
        for (const field of ['#stock', '#min_stock']) {
            form.querySelector(field).step = mode === 'bulk' ? '0.001' : '1';
        }
        searchVersion++;
        results.replaceChildren();
    };
    modes.forEach((radio) => radio.addEventListener('change', updateMode));
    updateMode();

    const wireRemove = (row) => {
        row.querySelector('[data-remove-component]').addEventListener('click', () => {
            row.remove();
            status.textContent = 'Componente quitado del paquete.';
            results.replaceChildren();
            search.focus();
        });
    };
    rows.querySelectorAll('[data-kit-row]').forEach(wireRemove);

    const isSelected = (id) => Array.from(rows.querySelectorAll('[data-component-id]')).some((input) => input.value === String(id));

    const addComponent = (product) => {
        if (isSelected(product.id)) return;
        const index = nextIndex++;
        const row = document.createElement('div');
        row.dataset.kitRow = '';
        row.dataset.index = index;
        row.className = 'rounded-md border border-mz-border p-3';
        const name = document.createElement('p');
        name.className = 'text-sm font-medium text-mz-text-primary';
        name.textContent = product.name;
        const detail = document.createElement('p');
        detail.className = 'text-xs text-mz-text-secondary';
        detail.textContent = `${product.sale_mode === 'bulk' ? 'A granel' : 'Por unidad/pieza'}${product.track_inventory ? '' : ' · Sin control de inventario'}`;
        const id = document.createElement('input');
        id.type = 'hidden';
        id.dataset.componentId = '';
        id.name = `kit_items[${index}][product_id]`;
        id.value = String(product.id);
        const label = document.createElement('label');
        label.className = 'mt-2 block text-sm font-medium text-mz-text-secondary';
        label.htmlFor = `kit_quantity_${index}`;
        label.textContent = `Cantidad por paquete de ${product.name}`;
        const quantity = document.createElement('input');
        quantity.id = label.htmlFor;
        quantity.name = `kit_items[${index}][quantity]`;
        quantity.type = 'number';
        quantity.step = product.sale_mode === 'bulk' ? '0.001' : '1';
        quantity.min = quantity.step;
        quantity.max = '4294967295';
        quantity.value = '1';
        quantity.required = true;
        quantity.className = 'mz-input mt-1.5 h-11 w-full';
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.dataset.removeComponent = '';
        remove.className = 'x-button x-button-secondary size-sm mt-2';
        remove.textContent = `Quitar ${product.name}`;
        row.append(name, detail, id, label, quantity, remove);
        wireRemove(row);
        rows.append(row);
        results.replaceChildren();
        search.value = '';
        searchVersion++;
        status.textContent = `${product.name} agregado al paquete.`;
        quantity.focus();
    };

    const searchComponents = async () => {
        const version = ++searchVersion;
        const term = search.value.trim();
        results.replaceChildren();
        status.textContent = '';
        if (!term || kitFields.disabled) return;
        status.textContent = 'Buscando componentes…';
        const params = new URLSearchParams({ q: term });
        if (config.exclude) params.set('exclude', config.exclude);

        try {
            const response = await fetch(`${config.url}?${params}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('No se pudieron buscar los componentes. Intenta de nuevo.');
            const data = await response.json();
            if (version !== searchVersion) return;
            for (const product of data.products) {
                const item = document.createElement('li');
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'x-button x-button-secondary size-sm w-full text-left';
                button.textContent = `${product.name}${product.barcode ? ` · ${product.barcode}` : ''} · ${product.sale_mode === 'bulk' ? 'A granel' : 'Pieza'}${product.track_inventory ? '' : ' · Sin control de inventario'}`;
                button.disabled = isSelected(product.id);
                button.addEventListener('click', () => addComponent(product));
                item.append(button);
                results.append(item);
            }
            status.textContent = data.products.length ? 'Elige un producto para agregarlo.' : 'No se encontraron componentes.';
        } catch (error) {
            if (version === searchVersion) status.textContent = error.message;
        }
    };

    search.addEventListener('input', () => {
        searchVersion++;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(searchComponents, 250);
    });
    search.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            clearTimeout(searchTimer);
            searchComponents();
        }
    });

    const cost = form.querySelector('#cost');
    const margin = form.querySelector('#margin');
    const price = form.querySelector('#price');
    const taxRate = form.querySelector('#tax_rate');

    let updating = false;

    const round = (value) => Math.round(value * 100) / 100;

    const calculatePrice = () => {
        if (!cost || !margin || !price || updating) {
            return;
        }

        const costValue = Number.parseFloat(cost.value);
        const marginValue = Number.isNaN(Number.parseFloat(margin.value)) ? 0 : Number.parseFloat(margin.value);
        const taxRateValue = taxRate && !Number.isNaN(Number.parseFloat(taxRate.value)) ? Number.parseFloat(taxRate.value) : 0;

        if (!Number.isFinite(costValue) || costValue <= 0) {
            return;
        }

        updating = true;
        // Costo + Ganancia (%)
        let basePrice = costValue * (1 + marginValue / 100);
        
        // Sobre el resultado, aplicamos el IVA (%)
        let finalPrice = basePrice * (1 + taxRateValue / 100);
        
        price.value = round(finalPrice).toFixed(2);
        updating = false;
    };

    const calculateMargin = () => {
        if (!cost || !margin || !price || updating) {
            return;
        }

        const costValue = Number.parseFloat(cost.value);
        const priceValue = Number.parseFloat(price.value);

        if (!Number.isFinite(costValue) || !Number.isFinite(priceValue) || costValue <= 0) {
            return;
        }

        updating = true;
        margin.value = round(((priceValue - costValue) / costValue) * 100).toFixed(2);
        updating = false;
    };

    cost?.addEventListener('input', () => {
        calculatePrice();
    });

    margin?.addEventListener('input', calculatePrice);
    taxRate?.addEventListener('input', calculatePrice);
    price?.addEventListener('input', calculateMargin);

}
