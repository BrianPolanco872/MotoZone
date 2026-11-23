---
entity: fix-pos-ventas-dia-devoluciones-01
type: fix
---

- Objetivo: agregar al POS el botón «Ventas del día y devoluciones» y un modal de dos paneles con listado de ventas, devolución inmediata de un artículo con recálculo de total, cancelación de venta pagada con devolución de stock y caja, sello CANCELADO e impresión de copia.
- Archivos tocados: routes/web.php; app/Http/Controllers/Pos/SaleController.php; app/Services/ReturnService.php; resources/views/pos/index.blade.php; resources/js/pos/pos.js; tests/Feature/PosSalesDayReturnsTest.php.
- Decisiones aplicadas: «Código» = folio de la venta; «Cliente» = nombre del cajero; devolución inmediata reutilizando ReturnService::request()+approve(); salida de caja TYPE_OUT en devolución y cancelación.
- Verificación: 5/5 pruebas focales (43 aserciones); 60/60 en PosPendingSalesTest + PosSalesTest + PosCutTest; Pint; npm run build (app-BJY6Ix_J.js).
- Estado: IMPLEMENTADO (2026-11-22). Sin desviaciones del plan aprobado.
