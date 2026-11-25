---
entity: plan-tickets-sin-subtotal-ni-folio-01
type: plan
---

- Tipo: feature.
- Estado: IMPLEMENTADO EN FUENTE.
- Aprobación: el usuario respondió «sí» al retiro exclusivo de Subtotal y folio visible, manteniendo IVA si está habilitado, Pago con y Cambio, con rollback de las vistas.
- Objetivo: quitar del comprobante la fila Subtotal y el folio visible tipo `V-00004`; mantener Total, número de artículos y los datos de pago. El IVA permanece solo cuando la preferencia existente lo habilita, salvo que el usuario confirme otro alcance.
- Alcance: `resources/views/pos/tickets/receipt.blade.php` y `settings.blade.php` para retirar ambas líneas en el ticket compartido y su ejemplo; `ticket.blade.php` para usar un título genérico sin folio; `receipt-styles.blade.php` para retirar la regla exclusiva del folio si queda sin uso; `tests/Feature/TicketSettingsTest.php` para comprobar que ya no se muestran y el Total se conserva.
- Fuera de alcance: cambiar el valor o cálculo de precios/impuestos, folio almacenado, historial, JSON estructurado de venta, códigos SKU, permisos, preferencias persistidas, método de impresión o pagos mixtos. El diálogo del navegador y la reimpresión permanecen.
- Aceptación: desde POS, historial y ejemplo de Tickets el comprobante no muestra Subtotal ni el folio en el contenido/título propio; Total exacto y demás campos acordados siguen visibles. El folio interno sigue disponible para localizar la venta en el historial. Verificar sintaxis/compilación básica; Testing solo a petición.
- Plan de rollback: respaldar y restituir solo las líneas afectadas de las vistas y cobertura desde el respaldo; no se necesitan migraciones, cambios de preferencias ni revertir ventas.
- Cambios aplicados: se quitaron el folio visible y Subtotal del comprobante y del ejemplo; la pestaña usa el título genérico «Ticket de venta» y se retiró la regla CSS sin uso. Se actualizaron aserciones de contenido y de conservación del folio en el JSON.
- Desviaciones del plan original: ninguna. Pasaron Pint, la compilación y el lint de las vistas y del archivo de pruebas; no se ejecutó Testing ni se hizo build o publicación.
