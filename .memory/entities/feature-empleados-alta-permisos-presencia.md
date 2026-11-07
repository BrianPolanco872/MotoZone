---
entity: feature-empleados-alta-permisos-presencia
type: feature
---

- Objetivo: Que el admin cree a un empleado con sus módulos (punto de venta, corte del día y productos) en un solo alta, que el empleado pueda iniciar sesión y usar esos módulos, y que el admin vea en la barra superior quién tiene sesión activa.
- Alcance: alta de empleado con selección de módulos/permisos en el mismo formulario; preset "Punto de venta" (pos.access, pos.sell, pos.products.view, pos.tickets.print.own, pos.sales.day.own), preset "Corte del día" (pos.cut.own + pos.sales.day.own, con generación de corte propio), preset "Productos" (admin.products.manage); ajuste de acceso del empleado a Mi corte y a Productos; indicador de usuarios en línea en el header del layout admin basado en la tabla sessions (últimos 5 min).
- Fuera de alcance: datos laborales extendidos (teléfono, puesto, turno, fecha de ingreso), foto/avatar, invitaciones por correo, 2FA, recuperación de contraseña, permisos granulares nuevos, rediseño del módulo de corte, notificaciones en tiempo real por websockets.
- Criterios de aceptación: (1) el admin crea un empleado eligiendo módulos y el empleado queda con exactamente esos permisos, sin admin.users.manage; (2) el empleado con POS entra al POS al iniciar sesión y puede cobrar; (3) el empleado con corte puede generar y ver únicamente su propio corte; (4) el empleado con productos ve y administra el catálogo pero no usuarios ni dashboard; (5) el admin ve "N en línea" con nombres en el header y solo cuentan sesiones de los últimos 5 minutos; (6) un empleado inactivo no puede iniciar sesión; (7) tests y Pint en verde.
- Decisiones aprobadas 2026-11-04: el "perfil" es cuenta + permisos (sin datos laborales ni foto); "activos" significa con sesión activa (no solo is_active); el empleado recibe permisos en productos, punto de venta y corte del día.
- Solicitada por: usuario
- Fecha: 2026-11-04
- Multi-caja 2026-11-04: varios empleados operan el POS simultáneamente desde sus cuentas. Ventas, movimientos de caja y cortes ya se aíslan por user_id, y el corte global consolida; el plan debe impedir que un empleado cierre el día o genere cortes ajenos, evitar colisiones de versión al generar cortes concurrentes y mostrar a todos los conectados en el header del admin.
- Estado: IMPLEMENTADA el 2026-11-04.
- Implementada en: fix-feat-empleados-alta-permisos-presencia-01.
