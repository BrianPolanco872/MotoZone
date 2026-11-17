---
entity: fix-preuso-motozone-lote4-01
type: fix
---

- Objetivo: lote 4 (#3, #4, #9) de plan-preuso-motozone-01: exactitud de caja fisica y PDF fiel al snapshot.
- Estado: IMPLEMENTADO; regresiones backend y PDF con Dompdf real verificadas. Concurrencia MySQL y validacion visual pendientes.
- Archivos tocados: app/Services/DailyCutService.php; app/Models/CashMovement.php; app/Services/SaleService.php; app/Services/ReturnService.php; app/Http/Controllers/Admin/CajaController.php; app/Http/Controllers/Pos/DailyCutController.php; resources/views/pos/cash/partials/employee-cut-board.blade.php; resources/views/pos/cuts/index.blade.php; consolidated.blade.php; pdf.blade.php; print.blade.php; resources/views/pos/cuts/partials/employee-breakdown.blade.php; tests/Feature/PosCutReconciliationTest.php; PosCashArchiveTest.php; PosCashHistoryFiltersTest.php; PosReturnsTest.php; tests/Feature/PosCutPdfTest.php (nuevo).
- Cambios aplicados: se separo total comercial de efectivo fisico; los movimientos de venta se filtran por el metodo de pago de la venta y los pagos a proveedor solo por method=cash, conservando el total general. La operacion fisica se atribuye al cobrador/aprobador registrado en los movimientos existentes, sin cambiar sale.user_id. Se persiste el desglose congelado totals['employee_breakdown'] en el JSON del snapshot, incluidos usuarios con solo pagos, y PDF/print leen exclusivamente el snapshot.
- Verificacion: 60 pruebas / 246 aserciones correctas en PosCutReconciliationTest, PosCashArchiveTest, PosCashHistoryFiltersTest, PosReturnsTest y relacionados. PosCutPdfTest: 16 pruebas / 70 aserciones con Dompdf real, incluida estabilidad de v1 tras generar v2 y aviso de snapshot antiguo sin desglose.
- Desviaciones: ninguna funcional. No se modificaron snapshots previos ni se reconstruyo actividad pasada.
- Limite registrado: payment_method=mixed no guarda reparto efectivo/electronico; se mantiene como advertencia con is_reconciled=false, sin inventar split ni etiquetarlo como electronico puro.
- Rollback: copia en C:/Users/Administrador/AppData/Local/Temp/opencode/motozone-preuso-lote4-finance-* y motozone-preuso-lote4-pdf-*.
- Limitaciones: SQLite no demuestra aislamiento concurrente; validacion visual de PDF/print y caja pendiente. #18 intacto.
- Fecha: 2026-11-16.
