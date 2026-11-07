---
entity: fix-corte-empleado-01
type: fix
---

- Objetivo: Empleado solo puede ver su propio corte; sin botones de "Hacer corte"/"Rehacer corte"; "Finalizar día" cierra su jornada y guarda el corte.
- Archivos tocados: resources/views/pos/cuts/index.blade.php
- Cambios aplicados:
    1. Select de empleado y opción "Corte global" solo se renderizan cuando $canSeeAny (líneas 37, 84, 97).
    2. Botones "Hacer corte" y "Rehacer corte" envueltos en @if ($canSeeAny), por lo que un empleado común no los ve (líneas 80-107).
    3. "Finalizar día" (close-my-day) permanece disponible para el empleado cuando $canCloseDay (hoy y no cerrado); genera y cierra su corte si no existe.
- Estado: IMPLEMENTADO
- Última actualización: 07/11/2026
