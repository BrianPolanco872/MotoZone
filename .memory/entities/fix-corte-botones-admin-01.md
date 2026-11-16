---
entity: fix-corte-botones-admin-01
type: fix
---

- Objetivo: Ajustar botones del módulo Corte del día según solicitud del admin.
- Archivos tocados: resources/views/pos/cuts/index.blade.php
- Estado: IMPLEMENTADO
- Cambios:
  - Eliminado el botón/formulario "Rehacer corte" (no sirve); se conserva "Hacer corte".
  - Eliminado el botón global "Cerrar día" de la sección "Cajeros en línea" (cierre masivo de todos los empleados). Ahora esa sección solo muestra los usuarios con sesión activa y advierte que cada cajero cierra su propio día desde su perfil.
  - El botón "Finalizar día" (closeMyDay) permanece disponible cuando el usuario autenticado ve su propio corte abierto, lo que permite al admin cerrar su propio turno si opera/vende con su perfil de empleado.
- Verificación: PosCut 26/26, Pint passed, Blade cache OK.
