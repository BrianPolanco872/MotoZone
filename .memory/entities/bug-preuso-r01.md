---
entity: bug-preuso-r01
type: bug
---

- Responsividad R01. Pendientes y Devoluciones no tienen acceso visible bajo 1280px.
- Estado: CAUSA RAÍZ CONFIRMADA por clases responsive y ausencia de alternativa en navegación; no prueba visual.
- Evidencia: resources/views/pos/index.blade.php:301,325-333; resources/views/layouts/admin.blade.php:12-52,169-184.
- Caso: viewports 320/375/768/1024px ocultan todo el aside con hidden xl:block y el menú compartido no contiene esos accesos.
- Causa confirmada: hipotesis-H21-preuso.
- Fecha: 2026-11-16. Sin implementación. No se permite ampliar permisos al hacer visibles enlaces.
