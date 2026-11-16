---
entity: plan-feat-pos-iniciar-dia-reabrir-venta-01
type: plan
---

- Tipo: feature
- Objetivo: Implementar "Iniciar día" con efectivo inicial y "Reabrir venta" para reabrir el corte del empleado, sin cambios de esquema y sin bloquear ventas antes del efectivo inicial.
- Pasos:
  1. Agregar a `PosController::index` el estado `initial_cash_registered` consultando `CashMovement` categoría `initial` del usuario en el día.
  2. Agregar en `resources/views/pos/index.blade.php` una franja centrada con "Iniciar día" visible solo con permiso de caja, sin efectivo inicial y sin día global cerrado.
  3. Agregar un modal dedicado que pide solo el monto y publica a `pos.cash.store` con `category=initial` y `type=in`.
  4. Agregar el handler JS del modal para guardar por AJAX, mostrar errores y recargar el POS al éxito.
  5. Reforzar en `CashMovementController::store` que no exista otro movimiento `initial` del mismo usuario en el día; devolver error de validación.
  6. En `resources/views/pos/cuts/index.blade.php`, calcular `isOwnCut`, `canFinalizeDay`, `canReopenSale` y `globalDayClosed`.
  7. Mostrar "Finalizar día" y "Reabrir venta" también en "Mi corte" para el dueño, fuera del bloque `! $readOnly`; "Reabrir venta" solo con corte propio cerrado y día global abierto.
  8. Agregar el formulario POST a `pos.cuts.reopen-my-day` junto a "Finalizar día" con confirmación.
  9. Pasar `globalDayClosed` desde `DailyCutController::index` y `::mine` a la vista.
  10. Corregir `DailyCutController::closeMyDay`: si ya existe un corte abierto, generar una nueva versión con los totales actuales antes de marcarlo cerrado, para no guardar totales obsoletos tras reabrir y vender.
  11. Actualizar textos del modal y mensaje de cierre para reflejar "cerrar turno" y "Reabrir venta".
  12. Agregar tests de visibilidad de botones, efectivo inicial único, reflejo en corte, cierre, reapertura, venta posterior y regeneración de totales.
- Archivos afectados:
  - `app/Http/Controllers/PosController.php`: estado de efectivo inicial del día.
  - `resources/views/pos/index.blade.php`: botón centrado, modal y formulario.
  - `resources/js/pos/pos.js`: envío AJAX del nuevo modal y recarga.
  - `app/Http/Controllers/Pos/CashMovementController.php`: guardia de un solo `initial` por usuario/día.
  - `resources/views/pos/cuts/index.blade.php`: botones Finalizar/Reabrir, condiciones y copia.
  - `app/Http/Controllers/Pos/DailyCutController.php`: `globalDayClosed` y regeneración de versión al cerrar un corte abierto.
  - `tests/Feature/PosCashTest.php`: efectivo inicial y guardia de duplicado.
  - `tests/Feature/PosCutTest.php`: cierre, reapertura, visibilidad y totales actualizados.
- Criterios de aceptación: los diez criterios de `feature-pos-iniciar-dia-reabrir-venta`.
- Plan de rollback: revertir los cambios de controladores, vistas y JS; no hay migraciones ni datos nuevos que deshacer. Si una versión de corte generada por el nuevo cierre resulta incorrecta, usar el borrado de la versión más reciente ya existente en el historial.
- Riesgos: el cambio en `closeMyDay` crea una versión nueva al cerrar un corte abierto y puede aumentar el historial; restringir "Finalizar día" al dueño cambia el comportamiento actual del admin en la vista global; la guardia de efectivo inicial no elimina una carrera concurrente sin índice único; si el movimiento `initial` se elimina, el botón vuelve a aparecer.
- Dependencias: ninguna externa; no se agregan paquetes ni migraciones. Ejecutar `npm run build` si se modifica JS y `vendor/bin/pint --dirty --format agent` antes de cerrar.
- Estado: IMPLEMENTADO
- Cambios aplicados: botón centrado "Iniciar día" con modal de solo monto (efectivo inicial) en POS; guardia de un solo `initial` por usuario/día en `CashMovementController`; visibilidad de botones "Finalizar día" y "Reabrir venta" en "Mi corte" para el dueño del turno; `closeMyDay` regenera versión antes de cerrar; `globalDayClosed` pasado a vistas; tests de caja y corte añadidos.
- Desviaciones del plan original: ninguna.
- Última actualización: 2026-11-08
