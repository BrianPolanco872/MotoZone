---
entity: plan-caja-historial-archivados-01
type: plan
---

- Tipo: feature
- Objetivo: Historial de movimientos de caja con filtro por empleado, día/mes/año y vista de solo archivados; archivado automático de los movimientos al cerrar el día; mover el control "Mostrar archivados" del panel de filtros al panel de historial.
- Archivos en alcance:
  - app/Http/Controllers/Pos/CashMovementController.php
  - app/Http/Controllers/Pos/DailyCutController.php
  - app/Services/CashMovementService.php
  - resources/views/pos/cash/index.blade.php
  - tests/Feature/PosCashHistoryFiltersTest.php (nuevo)
- Aprobado por el usuario en la conversación: 2026-11-09
- Supuestos aprobados: (1) archivado al cerrar el día / corte global que cierra el día, no por cron a medianoche; (2) filtros por date de happened_at; (3) restauración manual se mantiene.
- Plan de rollback: revertir los cambios de los 4 archivos y eliminar el test nuevo; el archivado creado se puede deshacer restaurando registros manualmente (archivado_at = null). No hay migraciones ni cambios de datos irreversibles.
- Estado: IMPLEMENTADO
- Cambios aplicados: filtros day/month/year y "solo archivados" en index/export; archiveDay en el servicio; archivado automático en closeDay y en el corte global que cierra el día; nuevo formulario de filtros en la cabecera del historial con empleado, día, mes, año y switch de archivados; eliminado el checkbox viejo del panel de filtros.
- Verificación: PosCashHistoryFiltersTest 5/5; PosCash* 29/29; suite 130/131 (1 fallo preexistente PosAccessTest); Pint passed.
- Ajuste posterior: eliminados los filtros Mes y Año (UI + backend) al quedar cubiertos por el campo Día; historial filtra por empleado y día.
