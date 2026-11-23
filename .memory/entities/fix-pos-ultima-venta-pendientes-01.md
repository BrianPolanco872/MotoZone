---
entity: fix-pos-ultima-venta-pendientes-01
type: fix
---

- Objetivo: mostrar «Pago con:» y «Cambio:» de la última venta cobrada en el panel inferior del POS; renombrar el botón a «Reimprimir último ticket» (siempre imprime la última venta cobrada); «Dejar pendiente» guarda y limpia el panel sin navegar; botón pequeño al lado abre un modal de tickets pendientes con «Traer a mi panel», que copia los productos al carrito como venta nueva editable sin borrar el pendiente.
- Archivos tocados: resources/views/pos/index.blade.php; resources/js/pos/pos.js; app/Http/Controllers/Pos/SaleController.php; routes/web.php; tests/Feature/PosPendingSalesTest.php.
- Cambios aplicados: indicador de última venta con renglón Pago con/Cambio alimentado por ticketData (`lastSaleData.sale.amount_paid` / `change_amount`); texto del botón de impresión cambiado (la lógica de `lastSaleId` ya imprimía la última venta cobrada, no se tocó); handler de «Dejar pendiente» ahora hace resetSale() y refresca el contador en vez de redirigir; nuevo endpoint JSON `pos.sales.pendingList` con el mismo alcance por usuario que la vista de pendientes (20 más recientes); modal con lista y botón «Traer a mi panel» que reutiliza el mapeo de ítems extraído de `loadPendingSale`, sin fijar pendingSaleId ni bloquear artículos.
- Decisiones respetadas: imprimir la tele (última cobrada), copiar productos dejando el pendiente guardado, sin migraciones ni BD nueva (se reusa tabla `sales`).
- Verificación: node --check OK; npm run build OK (app-DjkXbWMv.js); PosPendingSalesTest 2/2; PosSalesTest + PosPaymentMethodsTest + TicketSettingsTest + PosPendingSalesTest 72/72; Pint pasa.
- Rollback disponible: copias en C:\Users\Administrador\AppData\Local\Temp\opencode\backup-pos-ultima-venta-pendientes-01\. No se usó git checkout.
- Fecha: 2026-11-22.
- Estado: IMPLEMENTADO.
