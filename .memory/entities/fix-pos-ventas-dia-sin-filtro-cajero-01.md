---
entity: fix-pos-ventas-dia-sin-filtro-cajero-01
type: fix
---

- Objetivo: quitar el filtro «Cajero:» del modal «Ventas del día y devoluciones» y asegurar que el listado solo muestre las ventas del usuario autenticado, incluidos administradores.
- Archivos tocados: app/Http/Controllers/Pos/SaleController.php; resources/views/pos/index.blade.php; resources/js/pos/pos.js; tests/Feature/PosSalesDayReturnsTest.php.
- Verificación: 8/8 pruebas (PosSalesDayReturnsTest + PosPendingSalesTest), Pint y build app-uIAdEu89.js.
- Estado: IMPLEMENTADO (2026-11-22). Desviación solicitada explícitamente por el usuario tras aprobar plan-pos-ventas-dia-devoluciones-01.
