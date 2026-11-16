---
entity: plan-corte-historial-global-01
type: plan
---

- Tipo: feature / ajuste UI
- Objetivo: (1) El botón "Hacer corte" del panel admin genera un corte global con el total de todos los empleados juntos. (2) El historial de cortes solo muestra cortes globales (scope global), filtrados por calendario (desde/hasta), sin filtro de empleado. (3) Cada corte global guarda el total del negocio por día para auditoría y arqueo de caja.
- Archivos en alcance:
  - app/Http/Controllers/Pos/DailyCutController.php (@history)
  - resources/views/pos/cuts/history.blade.php
- Aprobado por el usuario en la conversación: 2026-11-09
- Plan de rollback: revertir el controlador @history (restaurar filtro de empleado y scope sin filtro global) y la vista historial (restaurar columnas Alcance/Cajero y select de empleado). Sin migraciones.
- Estado: IMPLEMENTADO
- Cambios aplicados: @history filtra por scope=global, elimina filtro de empleado y la variable $employees. Vista historial: título/subtítulo actualizados, icono cambiado a report, eliminada columna Empleado/Alcance, tabla con 9 columnas, fila vacía con colspan 9, enlace de impresión con employee=null.
- Verificación: vistas Blade compiladas, PosCut 26/26, Pint passed.
