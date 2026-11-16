---
entity: plan-caja-efectivo-en-caja-01
type: plan
---

- Tipo: bug-fix
- Objetivo: En el tablero de cajeros del módulo Caja, corregir la columna «Efectivo en Caja» para que muestre las ventas en efectivo más los ingresos de efectivo del día, y eliminar la columna «Saldo en Gaveta».
- Archivos en alcance:
  - resources/views/pos/cash/partials/employee-cut-board.blade.php
- Aprobado por el usuario en la conversación: 2026-11-09
- Plan de rollback: revertir el cambio del parcial (volver a mostrar $row['cash'] y restaurar la columna Saldo en Gaveta). Sin migraciones ni cambios de datos.
- Estado: IMPLEMENTADO
- Cambios aplicados: «Efectivo en Caja» = cash (ventas en efectivo) + cash_in (ingresos de efectivo: fondo inicial, cambio y otros ingresos) − cash_out (salidas de efectivo: pagos a proveedores, gastos, retiros y otros); columna «Saldo en Gaveta» eliminada de cabecera, filas y total consolidado.
- Verificación: vistas Blade compiladas, PosCash* 29/29, Pint passed.
