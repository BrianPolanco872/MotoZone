---
entity: plan-corte-01
type: plan
---

- Tipo: feature
- Estado: COMPLETADO
- Objetivo: Rediseñar el módulo Corte del Día de MotoZone con dos paneles (dinero registrado en computadora y caja), conciliación con badge Cuadra/Diferencia, campo de efectivo contado físico, snapshots inmutables con versiones, y corte global = suma de cortes por empleado.
- Pasos completados:
  1. ✅ Nueva migración reversible: altera tabla daily_cuts (quitar unique cut_key, agregar version, reason, cash_counted, cash_counted_at, difference, is_reconciled, previous_cut_id, índice compuesto cut_key+version)
  2. ✅ Modelo DailyCut actualizado con nuevos casts, relaciones (previousCut, versions), métodos (nextVersionFor, latestVersion, isLatestVersion)
  3. ✅ DailyCutService reescrito: build() calcula Panel 1 (computadora) y Panel 2 (caja) de forma independiente, reconciliation con diferencia, recordCashCounted(), generate() crea nuevas versiones, storeVersion() crea en lugar de sobrescribir
  4. ✅ DailyCutController actualizado: index() con dos paneles + conciliación, store() con nueva versión, recordCashCounted() para conteo físico, print() con datos del snapshot, history() con versiones
  5. ✅ Rutas agregadas: POST /pos/corte/{dailyCut}/efectivo-contado (cuts.record-cash)
  6. ✅ Vista index.blade.php rediseñada: dos paneles, conciliación, campo efectivo contado, tabla de versiones, botón Hacer corte, desglose colapsable
  7. ✅ Vista print.blade.php actualizada: formato ticket térmico (58/80mm) con conciliación y efectivo contado
  8. ✅ Vista history.blade.php actualizada: muestra versiones con estado de conciliación
  9. ✅ Partial summary.blade.php actualizado: muestra computer_panel y cash_box_panel
  10. ✅ Pruebas PosCutReconciliationTest: 10 escenarios, todos pasando
- Archivos modificados:
  - database/migrations/2026_11_04_000000_alter_daily_cuts_for_versions.php (nueva)
  - app/Models/DailyCut.php (modificar)
  - app/Services/DailyCutService.php (reescribir)
  - app/Http/Controllers/Pos/DailyCutController.php (modificar)
  - routes/web.php (modificar)
  - resources/views/pos/cuts/index.blade.php (rediseñar)
  - resources/views/pos/cuts/print.blade.php (modificar)
  - resources/views/pos/cuts/history.blade.php (modificar)
  - resources/views/pos/cuts/partials/summary.blade.php (modificar)
  - tests/Feature/PosCutReconciliationTest.php (nueva)
- Criterios de aceptación verificados: Día sin movimientos → $0.00 y "Cuadra"; $500 inicial + $100 + $250.50 + $80 − $200 salida = $730.50 ambos paneles; corte global = suma empleados; empleado ajeno → 403; rehacer corte = nueva versión sin borrar anterior; efectivo contado → sobrante/faltante guardado; badge Cuadra/Diferencia; impresión ticket térmico y PDF.
- Plan de rollback: php artisan migrate:rollback --step=1 deshace la migración; datos de cortes previos se conservan (columnas nullable).
- Última actualización: 2026-11-04
