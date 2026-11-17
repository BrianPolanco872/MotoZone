---
entity: fix-preuso-motozone-lote3-01
type: fix
---

- Objetivo: lote 3 (#10, #11, #14, #15, #17) de plan-preuso-motozone-01.
- Estado: IMPLEMENTADO, regresiones HTTP/backend y handlers JS verificadas; navegador real y assets reconstruidos pendientes.
- Archivos tocados: app/Services/SaleService.php; app/Http/Requests/Pos/StoreSaleRequest.php; app/Http/Controllers/PosController.php; app/Http/Controllers/Pos/SaleController.php; resources/js/pos/pos.js; resources/views/pos/index.blade.php (ayudas de Escape coherentes); tests/Feature/PosSalesTest.php; tests/pos.test.js.
- Cambios aplicados: filtrar IDs nulos antes de bloquear productos, conservar libres al cargar/cobrar pendientes, validar campos según tipo de partida, comprobar stock acumulado por producto en venta y pendiente sin fusionar precios distintos; filtro employee usa el ID real y mantiene aislamiento. JS conserva identidad/precios custom, cobra card/transfer con total y protege atajos en cualquier diálogo/campo editable, manteniendo escáner y confirmaciones explícitas.
- Verificación: baseline core 11/37; nuevas regresiones de ventas 8 fallos antes del fix. Backend integrado PosSalesTest/PosPaymentMethodsTest/TicketSettingsTest: 34 pruebas / 201 aserciones correctas. Node --test tests/pos.test.js: 8/8 correctos (antes 2/8); ejecuta archivo real con VM/DOM stub y numeral ya instalado, sin duplicar lógica de producción. node --check correcto.
- Desviaciones: se alinearon dos etiquetas de Escape como caller visible del handler modificado; ningún cambio de diseño. No se añadió framework/dependencia de test JS.
- Rollback: C:/Users/Administrador/AppData/Local/Temp/opencode/motozone-preuso-lote3-core-20261116-224011 y motozone-lote3-js-20261116-224039.
- Limitaciones: DOM stub no valida teclado nativo ni layout. mixed mantiene contrato anterior sin inventar reparto. Build bloqueado por ausencia de versión/tag; no afirmar assets de producción actualizados. #18 intacto.
- Fecha: 2026-11-16.
