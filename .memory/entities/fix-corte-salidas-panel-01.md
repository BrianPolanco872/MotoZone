---
entity: fix-corte-salidas-panel-01
type: fix
---

- Objetivo: Mostrar las salidas de efectivo en el panel «Pagos de contado → Pagos a proveedores / salidas» del corte de día y permitir verlas/cerrarlas en un modal.
- Archivos tocados: app/Services/DailyCutService.php, app/Http/Controllers/Pos/DailyCutController.php, resources/views/pos/cuts/index.blade.php, tests/Feature/PosCutSupplierPaymentsDetailTest.php (nuevo)
- Estado: IMPLEMENTADO
- Cambios:
  - `cashOutsForCut()` combina `SupplierPayment` + `CashMovement` type=out (expense/withdrawal/other) del día/empleado.
  - `supplierPaymentsDetail()` usa `cashOutsForCut()` y permite al empleado consultar su propio `employee_id` (antes 403).
  - La fila «Pagos a proveedores / salidas» usa `cash_out.total`.
  - El modal muestra `descripcion`/`categoria` y el modal de abonos ya abre aunque esté vacío.
- Verificación: PosCutSupplierPaymentsDetailTest 3/3, PosCut 26/26, PosCash 30/30, Pint passed.
