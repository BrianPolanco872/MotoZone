---
entity: plan-corte-salidas-panel-01
type: plan
---

- Tipo: bug-fix
- Objetivo: En el corte de día (vista de empleado), hacer que las salidas de efectivo aparezcan en «Pagos de contado → Pagos a proveedores / salidas», que el enlace abra un modal con el detalle de las salidas y que el modal se pueda abrir y cerrar correctamente.
- Archivos en alcance:
  - app/Services/DailyCutService.php
  - app/Http/Controllers/Pos/DailyCutController.php
  - resources/views/pos/cuts/index.blade.php
  - tests/Feature/PosCutSupplierPaymentsDetailTest.php (nuevo)
- Aprobado por el usuario en la conversación: 2026-11-09
- Plan de rollback: revertir los cambios de los 3 archivos y eliminar el test nuevo. Sin migraciones.
- Estado: IMPLEMENTADO
- Causa raíz: (1) el total y el endpoint de «Pagos a proveedores» solo consultaban la tabla `supplier_payments`, ignorando los movimientos de caja type=out (gastos, retiros, otros); (2) al empleado se le pasaba su propio `employee` al endpoint, que respondía 403; (3) el modal de abonos hacía `return` antes de `showModal()` cuando estaba vacío.
- Cambios aplicados: nuevo `DailyCutService::cashOutsForCut()` que une pagos a proveedores + salidas de caja; el endpoint devuelve ese detalle; se permite al empleado consultar su propio detalle; la fila muestra `cash_out.total`; el JS del modal usa descripción/categoría y ya no retorna antes de abrir.
- Verificación: PosCutSupplierPaymentsDetailTest 3/3, PosCut 26/26, PosCash 30/30, Pint passed, Blade cache OK.
