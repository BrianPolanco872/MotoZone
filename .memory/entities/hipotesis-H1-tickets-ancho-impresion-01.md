---
entity: hipotesis-H1-tickets-ancho-impresion-01
type: hipotesis
---

- Estado: CONFIRMADA, limitada al dimensionamiento definido en el código local leído en esta conversación.
- Archivo/líneas: resources/views/pos/tickets/ticket.blade.php:77-78.
- Mecanismo 1: al imprimir, la regla de .ticket reemplaza el ancho configurado en milímetros por width:auto, elimina max-width y pone margen cero; el bloque pasa a ocupar el ancho disponible de la página en vez de conservar la columna del ticket.
- Archivo/líneas: resources/views/pos/tickets/ticket.blade.php:78; resources/views/pos/index.blade.php:705; resources/js/pos/pos.js:186; resources/js/ticket-settings.js:147.
- Mecanismo 2: size:80mm auto y size:58mm auto combinan una longitud con auto, forma no válida de la propiedad CSS size de @page. La declaración de tamaño se descarta; no garantiza el papel térmico y queda vigente la selección del navegador/controlador.
- Evidencia previa: lectura de ambos flujos de impresión, receipt.blade.php, preferencias, controlador SaleController y assets compilados locales; captura de Chrome con columnas conservadas pero distribuidas sobre una hoja grande. No se ejecutaron pruebas ni se imprimió físicamente.
- Callers: botón de última venta en POS usa ticketData y receipt; historial de ventas y devoluciones enlazan la vista ticket; preferencias tiene una vista de ejemplo separada.
- Límite: no confirma configuración ni controlador de 5SPRINTER, medidas físicas del rollo, versión remota o causa del fragmento de política desplazado. No afirmar que cualquier impresora quedará configurada automáticamente.
