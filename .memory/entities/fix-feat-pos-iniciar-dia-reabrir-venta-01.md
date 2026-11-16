---
entity: fix-feat-pos-iniciar-dia-reabrir-venta-01
type: fix
---

- Objetivo: Implementar "Iniciar día" con efectivo inicial y "Reabrir venta" para reabrir el corte del empleado, sin cambios de esquema y sin bloquear ventas antes del efectivo inicial.
- Archivos tocados:
  - app/Http/Controllers/PosController.php: estado `initial_cash_registered` y prop en `pos.index`.
  - resources/views/pos/index.blade.php: botón centrado "Iniciar día" y modal `#start-day-dialog`/`#start-day-form` (solo monto, `category=initial`, `type=in`).
  - resources/js/pos/pos.js: handler AJAX del modal de inicio, formateo de monto y recarga al guardar.
  - app/Http/Controllers/Pos/CashMovementController.php: guardia para impedir segundo movimiento `initial` del mismo usuario/día.
  - resources/views/pos/cuts/index.blade.php: cálculo de `$isOwnCut`, `$canFinalizeDay`, `$canReopenSale`; botones "Finalizar día" y "Reabrir venta" fuera de `!$readOnly`; formulario POST a `pos.cuts.reopen-my-day`; modal y textos actualizados.
  - app/Http/Controllers/Pos/DailyCutController.php: `globalDayClosed` en `index` y `mine`; `closeMyDay` genera nueva versión antes de cerrar; mensaje actualizado.
  - tests/Feature/PosCashTest.php: cobertura de efectivo inicial, visibilidad y guardia de duplicado.
  - tests/Feature/PosCutTest.php: cobertura de cierre, reapertura, venta posterior y totales.
- Estado: IMPLEMENTADO
