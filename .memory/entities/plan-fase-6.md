---
entity: plan-fase-6
type: plan
---

- Tipo: feature
- Estado: APROBADO
- Objetivo: Cierre de día sin borrado. Una vez hecho el corte final de una fecha, ese día queda cerrado (no acepta más ventas, devoluciones ni movimientos de caja). Al día siguiente empiezan contadores desde cero. Los datos de días cerrados permanecen en BD para auditoría y reportes.
- Pasos:
  1. Agregar campo closed_at (nullable, timestamp) a tabla daily_cuts mediante migración reversible.
  2. Actualizar DailyCut.php: fillable, casts, alcance de consulta días abiertos/cerrados.
  3. Validar en SaleController (store/charge), SaleReturnController (store) y CashMovementController (store) que la fecha no pertenezca a un día cerrado -> 422 si está cerrado.
  4. En DailyCutController::store (hacer corte final), establecer closed_at = now() en el corte global de esa fecha.
  5. Exponer en PosController si el día está cerrado para la vista POS.
  6. Ejecutar php artisan migrate, vendor/bin/pint, php artisan test.
- Archivos afectados:
  - database/migrations/2026_11_04_030000_add_closed_at_to_daily_cuts.php (nueva)
  - app/Models/DailyCut.php (modificar)
  - app/Http/Controllers/Pos/SaleController.php (modificar)
  - app/Http/Controllers/Pos/SaleReturnController.php (modificar)
  - app/Http/Controllers/Pos/CashMovementController.php (modificar)
  - app/Http/Controllers/Pos/DailyCutController.php (modificar)
  - app/Http/Controllers/PosController.php (modificar)
- Criterios de aceptación: Día cerrado no acepta transacciones nuevas; día nuevo empieza en $0.00; datos de días cerrados intactos; mensaje claro al intentar operar en día cerrado.
- Plan de rollback: php artisan migrate:rollback --step=1; revertir cambios en controladores/modelo.
- Última actualización: 2026-11-04
- Estado: IMPLEMENTADO
- Cambios aplicados: Se agregó campo closed_at (nullable timestamp) a daily_cuts mediante migración reversible 2026_11_04_030000_add_closed_at_to_daily_cuts.php. Modelo DailyCut: fillable, casts, método isClosed(), scope OpenDays, método estático isDayClosed(string $date). Controladores bloquean transacciones en días cerrados: SaleController::store/charge, SaleReturnController::store, CashMovementController::store (lanza ValidationException con mensaje claro). DailyCutController::store marca closed_at=now() en el corte global al hacer corte final. PosController expone day_closed en la vista. Se ejecutó php artisan migrate, vendor/bin/pint, php artisan test (91/92; 1 fallo pre-existing ajeno al plan).
- Desviaciones del plan original: Ninguna.
