---
entity: fix-pos-pendientes-eliminar-01
type: fix
---

- Objetivo: permitir eliminar (cancelar) un ticket pendiente desde el modal del POS, además de verlo y traerlo al panel.
- Archivos tocados: resources/views/pos/index.blade.php; resources/js/pos/pos.js; tests/Feature/PosPendingSalesTest.php.
- Cambios aplicados: se expuso la ruta `pos.sales.cancel` en pos-config; cada fila del modal muestra «Eliminar» solo si `permissions.cancel_sale`; pide confirmación, hace DELETE con CSRF al endpoint existente y refresca la lista. No se agregó controlador, ruta ni tabla: el servicio `cancelPending` marca el ticket como cancelado, conserve historial/auditoría y no mueve stock ni caja.
- Decisión del usuario: cancelar (no borrado físico) el 2026-11-22.
- Verificación: Pint pasa; node --check OK; PosPendingSalesTest 3/3; PosSalesTest + TicketSettingsTest + PosPendingSalesTest 66/66; npm run build OK (app-u3MGwucP.js).
- Rollback disponible: copias en C:\Users\Administrador\AppData\Local\Temp\opencode\backup-pos-pendientes-eliminar-01\. Sin git checkout.
- Fecha: 2026-11-22.
- Estado: IMPLEMENTADO.
