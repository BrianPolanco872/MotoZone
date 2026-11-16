---
entity: feature-admin-dashboard-equipo-cortes-01
type: feature
---

- Objetivo: Integrar al dashboard actual del administrador un resumen de presencia y cortes diarios por empleado usando datos existentes de MotoZone.
- Alcance: Añadir un panel de equipo y corte diario a la vista existente; presencia obtenida con `PresenceService::onlineUsers()` (sesiones activas en los últimos 5 minutos); desglose de ventas/cortes de hoy mediante `DailyCutService::employeeBreakdown()`; mostrar únicamente empleados con sesión activa o actividad/corte hoy; enlaces a `pos.cuts.index` con fecha y empleado; mantener los módulos actuales y hacer el panel usable en móvil. La consulta desde el dashboard fue autorizada aunque el encabezado mantiene su composer de presencia.
- Fuera de alcance: Cambios al sidebar, rutas o navegación; mover, reemplazar o duplicar ventas, inventario, calidad del catálogo, más vendidos y destacados de home; cambios al módulo Caja, cortes, servicios de presencia/cortes o proveedor de vistas; pagos entrantes; turnos programados, asistencia física, descansos, terminal asignada, sincronización biométrica o latencia de base de datos sin fuente interna existente.
- Criterios de aceptación: (1) datos provenientes solo de sesiones y registros existentes; (2) presencia descrita como sesión activa, no asistencia; (3) resumen y filas distinguen sesión, actividad/corte del día; (4) fila de empleado enlaza al corte existente del mismo día; (5) estado vacío sin empleados con sesión/actividad/corte; (6) sidebar, navegación y módulos actuales conservan su ubicación y comportamiento; (7) presentación responsive.
- Archivos/módulos: `app/Http/Controllers/Admin/DashboardController.php`, `resources/views/admin/dashboard.blade.php`, `tests/Feature/AdminTest.php`; se reutiliza sin cambios `AppServiceProvider`/`PresenceService`, `DailyCutService`, rutas y pantallas existentes de corte.
- Solicitada por: usuario.
- Trabajo previo integrado: `plan-dashboard-pos-catalogo` y `plan-caja-05`; el plan previo de pagos entrantes queda fuera de esta solicitud.
- Estado: IMPLEMENTADO.
- Implementada en: `fix-admin-dashboard-equipo-cortes-01`.
- Última actualización: 2026-11-16.
