---
entity: plan-caja-historial-solo-dia-01
type: plan
---

- Tipo: feature
- Objetivo: El historial de movimientos de caja muestra por defecto solo el día en curso (no días anteriores); los días anteriores quedan archivados y solo se consultan activando «Solo archivados», donde el filtro Día permite elegir la fecha.
- Archivos en alcance:
  - app/Http/Controllers/Pos/CashMovementController.php
  - resources/views/pos/cash/index.blade.php
  - tests/Feature/PosCashHistoryFiltersTest.php
- Aprobado por el usuario en la conversación: 2026-11-09
- Plan de rollback: revertir los cambios de los 3 archivos (volver a mostrar todos los no archivados y restaurar el input Día en la vista activa). Sin migraciones.
- Estado: IMPLEMENTADO
- Cambios aplicados: en `index()`/`export()` sin `show_archived` se fuerza `whereDate(happened_at, today)`; con `show_archived=1` se respeta el filtro `day` (o se muestran todos los archivados). El input Día solo se muestra en la vista de archivados; subtítulo «del día» y estado vacío actualizados.
- Verificación: PosCash* 30/30, Pint passed, Blade cache OK.
