---
entity: plan-corte-botones-admin-01
type: plan
---

- Tipo: bug-fix / ajuste UI
- Objetivo: Ajustar botones del módulo Corte del día: (1) quitar el botón "Rehacer corte" del panel admin porque no sirve; (2) quitar el botón "Cerrar día" global de la sección "Cajeros en línea" para que cada cajero cierre su propio día desde su perfil; (3) mantener el botón "Finalizar día" (closeMyDay) para que el admin, cuando opera con su propio perfil de empleado, pueda cerrar su propio turno si quiere usar el POS y vender.
- Archivos en alcance:
  - resources/views/pos/cuts/index.blade.php
- Aprobado por el usuario en la conversación: 2026-11-09
- Plan de rollback: revertir el cambio del blade (restaurar el bloque de "Rehacer corte" y el formulario global de "Cerrar día"). Sin migraciones.
- Estado: IMPLEMENTADO
- Cambios aplicados: eliminado el formulario de "Rehacer corte" (se conserva "Hacer corte"); eliminado el botón global "Cerrar día" de la sección cajeros en línea (ahora solo lista quién está en línea); el botón "Finalizar día" sigue disponible cuando el usuario ve su propio corte abierto ($canFinalizeDay).
- Verificación: vistas Blade compiladas, PosCut 26/26, Pint passed.
