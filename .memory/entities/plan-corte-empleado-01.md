---
entity: plan-corte-empleado-01
type: plan
---

- Objetivo: Un empleado sin permiso pos.sales.day.any solo ve su propio corte. Se ocultan los botones "Hacer corte" y "Rehacer corte"; al terminar su jornada usa "Finalizar día", que genera (si no existe) y cierra su corte del día, estampando daily_cut_id en sus pagos. "Ver corte" sigue funcionando con el selector de fecha para hoy y días anteriores.
- Tipo: ajuste de comportamiento / permisos de corte
- Archivos tocados: resources/views/pos/cuts/index.blade.php
- Estado: IMPLEMENTADO
- Última actualización: 07/11/2026
