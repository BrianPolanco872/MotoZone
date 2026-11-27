let activeFrame;

export function printTicketFrame({ url, content, styles, onError = (message) => window.alert(message) }) {
    if (activeFrame) return;

    let destination;
    if (url) {
        try {
            destination = new URL(url, window.location.href);
            if (destination.origin !== window.location.origin) throw new Error();
            destination.searchParams.delete('print');
            destination.searchParams.set('embed', '1');
        } catch {
            onError('No se pudo abrir el ticket para imprimir.');
            return;
        }
    }

    const frame = document.createElement('iframe');
    frame.title = 'Impresión de ticket';
    frame.tabIndex = -1;
    frame.setAttribute('aria-hidden', 'true');
    frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
    activeFrame = frame;
    const previousFocus = document.activeElement;
    let timeout;

    const cleanup = () => {
        if (activeFrame !== frame) return;
        clearTimeout(timeout);
        const restoreFocus = document.activeElement === frame;
        frame.remove();
        activeFrame = null;
        if (restoreFocus) previousFocus?.focus();
    };
    const fail = () => {
        if (activeFrame !== frame) return;
        cleanup();
        onError('No se pudo imprimir el ticket. Inténtalo de nuevo.');
    };

    frame.addEventListener('load', async () => {
        if (activeFrame !== frame) return;
        try {
            const frameWindow = frame.contentWindow;
            const frameDocument = frame.contentDocument;
            if (!frameWindow || !frameDocument) return fail();

            if (destination) {
                const paper = frameDocument.querySelector('[data-ticket-print-document]');
                if (!frameDocument.body?.hasAttribute('data-ticket-embedded') ||
                    (paper ? !paper.textContent.trim() : !frameDocument.querySelector('.ticket-sheet'))) return fail();
            } else {
                const paper = frameDocument.importNode(content, true);
                paper.id = 'ticket-print-root';
                frameDocument.head.append(frameDocument.importNode(styles, true));
                frameDocument.body.classList.add('ticket-example-print');
                frameDocument.body.append(paper);
                // El QR es opcional: un fallo de imagen no impide imprimir el resto.
                await Promise.allSettled([...paper.querySelectorAll('img:not([hidden])')].map((image) => image.decode()));
            }

            if (activeFrame !== frame) return;
            clearTimeout(timeout);
            frameWindow.addEventListener('afterprint', cleanup, { once: true });
            timeout = setTimeout(cleanup, 60000);
            frameWindow.focus();
            frameWindow.print();
        } catch {
            fail();
        }
    }, { once: true });
    frame.addEventListener('error', fail, { once: true });
    if (destination) frame.src = destination.href;
    else frame.srcdoc = '<!doctype html><html lang="es"><meta charset="utf-8"><title>Ticket de ejemplo</title><body></body></html>';
    timeout = setTimeout(fail, 15000);
    document.body.append(frame);
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('[data-print-sale-ticket]').forEach((button) => {
        button.addEventListener('click', () => printTicketFrame({ url: button.dataset.printSaleTicket }));
    });
}
