---
entity: fix-corte-historial-global-01
type: fix
---

- Objetivo: Historial de cortes solo para cortes globales, con filtro de calendario.
- Archivos tocados: app/Http/Controllers/Pos/DailyCutController.php, resources/views/pos/cuts/history.blade.php
- Estado: IMPLEMENTADO
- Cambios:
  - @history ahora filtra solo `scope = 'global'` y elimina el filtro de empleado/$employees.
  - Vista historial: solo columnas Fecha, Versión, Generado el, Por, Ventas, Caja, Diferencia, Estado, Acciones. Impresión de corte global (employee=null). Mensaje "Día cerrado" cuando aplica.
- Verificación: PosCut 26/26, Pint passed, Blade cache OK.
