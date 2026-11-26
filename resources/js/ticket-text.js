export function formatearTicket({ business, ticket, sale }) {
    const cols = Number(ticket.width_mm) === 80 ? 42 : 32;
    const lines = [];
    const clean = (value) => {
        const ascii = String(value ?? '').normalize('NFD').replace(/\p{M}/gu, '')
            .replace(/\s+/g, ' ').replace(/[^\x20-\x7e]/g, '').replace(/ +/g, ' ').trim();
        return ticket.uppercase ? ascii.toUpperCase() : ascii;
    };
    const money = (amount) => `$${Number(amount ?? 0).toFixed(2)}`;
    const blank = ' ';
    const feed = (value) => Array(Math.max(0, Math.min(20, Math.trunc(Number(value)) || 0))).fill(blank);

    function wrap(text, width = cols) {
        const wrapped = [];
        let line = '';
        for (let word of clean(text).split(' ')) {
            if (!word) continue;
            if (line && line.length + 1 + word.length > width) {
                wrapped.push(line);
                line = '';
            }
            while (word.length > width) {
                wrapped.push(word.slice(0, width));
                word = word.slice(width);
            }
            if (word) line = line ? `${line} ${word}` : word;
        }
        if (line || !wrapped.length) wrapped.push(line);
        return wrapped;
    }

    const center = (text) => lines.push(...wrap(text).map((line) => line.padStart(Math.floor((cols + line.length) / 2))));
    const pair = (left, right) => {
        left = clean(left);
        right = clean(right);
        if (left.length + right.length < cols) {
            lines.push(left.padEnd(cols - right.length) + right);
        } else {
            lines.push(...wrap(left), ...wrap(right));
        }
    };

    lines.push(...feed(ticket.lines_above ?? 0));
    if (clean(business.name)) center(business.name);
    if (clean(business.address)) center(business.address);
    if (clean(business.phone)) center(`Tel. ${business.phone}`);
    if (ticket.show_tax_id && clean(business.tax_id)) center(`RFC ${business.tax_id}`);
    lines.push(blank);
    pair(sale.date, sale.time);
    if (sale.folio) pair('Folio:', sale.folio);
    if (ticket.show_cashier && clean(sale.cashier)) lines.push(...wrap(`Atendió: ${sale.cashier}`));
    lines.push(blank);

    const items = sale.items.map((item) => ({
        ...item,
        quantity: clean(item.quantity),
        amount: money(item.line_total),
    }));
    const quantityWidth = Math.max(5, ...items.map((item) => item.quantity.length));
    const amountWidth = Math.max(7, ...items.map((item) => item.amount.length));
    const descriptionWidth = cols - quantityWidth - amountWidth - 2;
    const aligned = descriptionWidth > 0;
    const itemLine = (quantity, description, amount) =>
        `${quantity.padStart(quantityWidth)} ${description.padEnd(descriptionWidth)} ${amount.padStart(amountWidth)}`;
    const indent = aligned ? ' '.repeat(quantityWidth + 1) : '';
    const detail = (text) => lines.push(...wrap(text, cols - indent.length).map((line) => indent + line));

    if (aligned) {
        lines.push(itemLine(clean('Cant.'), clean('Producto').slice(0, descriptionWidth), clean('Importe')));
    } else {
        pair('Cant.', 'Importe');
    }
    lines.push('='.repeat(cols));

    let articles = 0;
    let bulkLines = 0;
    for (const item of items) {
        const bulk = item.sale_mode === 'bulk';
        const kit = item.sale_mode === 'kit';
        if (bulk) bulkLines++;
        else articles += Number(item.quantity);

        const nameWidth = aligned ? descriptionWidth : cols;
        const description = ticket.full_description ? wrap(item.name, nameWidth) : [clean(item.name).slice(0, nameWidth)];
        if (aligned) {
            lines.push(itemLine(item.quantity, description[0], item.amount));
            lines.push(...description.slice(1).map((line) => indent + line));
        } else {
            pair(item.quantity, item.amount);
            lines.push(...description);
        }
        if (ticket.show_sku && clean(item.sku)) detail(`SKU: ${item.sku}`);
        if (ticket.show_unit_price) detail(`${money(item.unit_price)} ${kit ? 'por paquete' : 'por unidad'}`);
        if (bulk) detail('A granel');
        else if (kit && !ticket.show_unit_price) detail('Paquete');
        if (Number(item.returned_quantity) > 0) {
            detail(`Cantidad devuelta ${bulk ? 'a granel' : kit ? 'en paquetes' : 'en piezas'}: ${item.returned_quantity}`);
        }
    }

    lines.push('='.repeat(cols));
    center(`No. de artículos: ${Number(articles.toFixed(3))}`);
    if (bulkLines) center(`Partidas a granel: ${bulkLines}`);
    lines.push(blank);
    if (ticket.show_tax) pair('IVA incluido', money(sale.tax_total));
    pair('Total', money(sale.total));
    pair('Pago con', money(sale.amount_paid));
    pair('Cambio', money(sale.change_amount));
    center(`Método de pago: ${sale.payment_method}`);
    lines.push(blank);
    if (clean(ticket.footer)) center(ticket.footer);
    if (clean(ticket.policy)) lines.push(...wrap(ticket.policy));
    lines.push(...feed(ticket.lines_below ?? 5));

    return lines.join('\n');
}

export function mountTicketPrint(paper, browser) {
    paper.textContent = formatearTicket(JSON.parse(paper.dataset.receipt));
    const print = () => browser.print();
    if (paper.ownerDocument.readyState === 'complete') print();
    else browser.addEventListener('load', print, { once: true });
}

const printDocument = typeof document !== 'undefined' && document.querySelector('[data-ticket-print-document]');
if (printDocument) mountTicketPrint(printDocument, window);
