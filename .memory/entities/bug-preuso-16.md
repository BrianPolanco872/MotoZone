---
entity: bug-preuso-16
type: bug
---

- Auditoría: #16. Módulo Corte del día asignado solo no permite acceder a sus rutas.
- Estado: CAUSA RAÍZ CONFIRMADA por catálogo de permisos y middleware.
- Evidencia: app/Models/User.php:164-170; routes/web.php:35,111-113.
- Caso: alta de empleado con modules=[cut] concede pos.cut.own pero no pos.access y su corte devuelve 403.
- Causa confirmada: hipotesis-H16-preuso.
- Fecha: 2026-11-16. Sin implementación.
