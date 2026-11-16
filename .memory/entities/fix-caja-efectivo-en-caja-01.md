---
entity: fix-caja-efectivo-en-caja-01
type: fix
---

- Objetivo: Corregir «Efectivo en Caja» y retirar «Saldo en Gaveta» del tablero de cajeros de Caja.
- Archivos tocados: resources/views/pos/cash/partials/employee-cut-board.blade.php
- Estado: IMPLEMENTADO
- Cambios: se calcula `$cashInDrawer = cash + cash_in - cash_out` por empleado y se usa en la columna; el total del pie usa `$sumCash + $sumCashIn - $sumCashOut`; se eliminó la celda/cabecera de «Saldo en Gaveta».
- Verificación: PosCash* 29/29, Pint passed, Blade cache OK.
