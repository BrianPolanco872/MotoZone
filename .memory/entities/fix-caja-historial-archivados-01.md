---
entity: fix-caja-historial-archivados-01
type: fix
---

- Objetivo: Implementar plan-caja-historial-archivados-01 (feature de historial de caja y archivado al cierre del día).
- Archivos tocados: app/Http/Controllers/Pos/CashMovementController.php, app/Http/Controllers/Pos/DailyCutController.php, app/Services/CashMovementService.php, resources/views/pos/cash/index.blade.php, tests/Feature/PosCashHistoryFiltersTest.php (nuevo), tests/Feature/PosCashArchiveTest.php (assertion obsoleta corregida).
- Estado: IMPLEMENTADO
- Cambios:
  - `CashMovementService::archiveDay()` archiva (soft) los movimientos no archivados de una fecha con auditoría.
  - `DailyCutController@closeDay` y el corte global que auto-cierra el día archivan los movimientos de esa fecha; el cierre de turno individual (`closeMyDay`) no archiva.
  - `CashMovementController@index/export` aceptan `day`, `month`, `year`; `show_archived=1` ahora significa solo archivados (`whereNotNull`), sin el flag solo activos.
  - Vista: se eliminó el checkbox «Mostrar archivados» y el selector de empleado del panel Filtros; el panel Historial tiene ahora formulario con Empleado, Día, Mes, Año y switch «Solo archivados», preservando los demás filtros con inputs ocultos.
- Desviaciones del plan original: se corrigió la assertion preexistente `0 movimiento(s)` → `0 movimientos` en PosCashArchiveTest (el texto ya no existía en la vista). Además, a petición del usuario se eliminaron los filtros Mes y Año (el campo Día ya los cubre) del backend (`index`/`export`) y del formulario del historial; el test pasó a `test_history_can_be_filtered_by_day`. No hay migraciones.
- Verificación: PosCashHistoryFiltersTest 5/5, PosCash* 29/29; suite completa 130/131 con 1 fallo preexistente ajeno (PosAccessTest espera «La venta está vacía» y la vista dice «El ticket está vacío»). Pint passed.
