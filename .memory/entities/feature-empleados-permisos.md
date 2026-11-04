---
entity: feature-empleados-permisos
type: feature
---

- Objetivo: Que el admin cree empleados y les asigne permisos individuales (Punto de venta y Productos), que el empleado acceda a esos módulos con el layout admin filtrado, y que exista un módulo de perfil para cambiar nombre, correo y contraseña (propio y de empleados).
- Alcance: rol empleado sin permisos base; permisos por usuario (pos.access, admin.products.manage); /admin/usuarios con alta de empleado y matriz de permisos; /admin/productos por permiso; /admin/perfil propio + bloque admin para empleados; navegación actualizada.
- Fuera de alcance: permisos granulares del POS, permiso de solo lectura de productos, módulo /admin/empleados independiente, invitaciones por correo, verificación de email, 2FA, auditoría de cambios.
- Criterios de aceptación: empleado sin permisos recibe 403 en /pos y /admin/productos; alta de empleado desde /admin/usuarios; matriz de 2 permisos por empleado; acceso efectivo según permiso; empleado con productos entra a /admin/productos pero no a usuarios/roles/dashboard; admin conserva los 14 permisos; perfil propio con contraseña actual para cambiarla; admin edita datos de empleados; empleado no edita a otros.
- Solicitada por: usuario
- Fecha: 2026-11-02
- Decisiones aprobadas 2026-11-02: reutilizar /admin/usuarios como módulo de empleados; permisos asignables solo POS + Productos; rol empleado sin permisos base; /admin/productos protegido por permission:admin.products.manage; perfil común en /admin/perfil con bloque admin.
- Estado: IMPLEMENTADO el 2026-11-02.
- Implementada en: fix-empleados-permisos.
- Ajustes 2026-11-02 (fix-empleados-ajustes-02): módulo de roles eliminado; empleados se pueden eliminar (solo rol empleado, borrado físico con limpieza de permisos/roles); permisos naturalizados y agrupados por módulo con User::PERMISSION_CATALOG; Spatie sigue siendo el motor de permisos.
- Ajustes 2026-11-02 (fix-empleados-ajustes-03): borrado de empleados ahora es lógico (SoftDeletes); admin.roles.manage eliminado; etiquetas de permisos en lenguaje claro con descripción ("Usar el punto de venta", "Ver y editar productos").
