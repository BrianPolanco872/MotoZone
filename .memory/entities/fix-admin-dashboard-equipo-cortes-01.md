---
entity: fix-admin-dashboard-equipo-cortes-01
type: fix
---

- Objetivo: Integrar al dashboard actual un resumen de sesiones activas y actividad/cortes diarios por empleado con datos existentes.
- Archivos tocados: `app/Http/Controllers/Admin/DashboardController.php`, `resources/views/admin/dashboard.blade.php`, `tests/Feature/AdminTest.php`.
- Cambios aplicados: Se pasan al dashboard el desglose de `DailyCutService::employeeBreakdown()` para hoy y las sesiones de `PresenceService::onlineUsers()`; se añade un panel responsive al final de la vista, filtrado a empleados con sesión activa o actividad/corte del día, con métricas, estado vacío y links al corte existente. Se añadieron pruebas para registros del día, sesiones recientes/expiradas, enlaces y estado vacío.
- Desviación autorizada: El controlador consulta `PresenceService` directamente porque los datos compartidos por el composer de `layouts.admin` no están disponibles al renderizar el contenido hijo. El composer sigue alimentando el encabezado, por lo que hay una lectura adicional de sesiones en `/admin`; `AppServiceProvider`, rutas, sidebar y vistas de Caja/corte no se modificaron.
- Verificación: `vendor/bin/pint --dirty --format agent`, `php -l` en controlador y test, `php artisan view:cache` + `view:clear` y `git diff --check` pasaron. No se ejecutaron pruebas.
- Estado: IMPLEMENTADO.
