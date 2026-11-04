---
entity: plan-caja-02
type: plan
---

- Tipo: feature
- Estado: PLANIFICADO
- Objetivo: Mejorar el módulo Caja de MotoZone con filtros, paginación, archivado soft de movimientos, eliminación con motivo y auditoría, exportación CSV, y coincidencia de tarjetas con Panel 2 del corte.
- Pasos:
  1. Migración reversible: agregar archivado_at, archivado_por, deleted_at, deleted_by, deletion_reason a cash_movements
  2. Actualizar modelo CashMovement: soft deletes, scopes archived/notArchived, relaciones archivedBy/deletedBy, método canBeDeleted()
  3. Reescribir CashMovementService: archive(), archiveBefore(), restore(), delete() con validación de corte cerrado, auditoría
  4. Actualizar CashMovementController: index con filtros+paginación, archive, archiveMass, restore, destroy con motivo, exportar CSV
  5. Rutas: archivar individual, archivar masivo, restaurar, eliminar, exportar, vista archivados
  6. Vista index.blade.js: filtros, tabla con estado archivado, modales confirmación, botón exportar, sección archivados
  7. Pruebas PosCashArchiveTest: archivar, eliminar bloqueado en corte cerrado, export CSV, filtros
  8. php artisan migrate, php vendor/bin/phpunit, npm run build, vendor/bin/pint
- Archivos afectados:
  - database/migrations/2026_11_04_010000_add_archive_fields_to_cash_movements.php (nueva)
  - app/Models/CashMovement.php (modificar)
  - app/Services/CashMovementService.php (reescribir)
  - app/Http/Controllers/Pos/CashMovementController.php (modificar)
  - routes/web.php (modificar)
  - resources/views/pos/cash/index.blade.php (rediseñar)
  - tests/Feature/PosCashArchiveTest.php (nueva)
- Criterios de aceptación: Filtros funcionan; archivado soft no borra datos; movimiento en corte cerrado no se puede eliminar (solo archivar); export CSV funciona; tarjetas coinciden con Panel 2 del corte.
- Plan de rollback: php artisan migrate:rollback --step=1; restaurar vistas desde git.
- Última actualización: 2026-11-04
