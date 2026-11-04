---
entity: fix-empleados-ajustes-02
type: fix
---

- Objetivo: Ajustar el módulo de empleados: quitar el módulo de roles, permitir eliminar empleados y naturalizar/agrupar los permisos por módulo.
- Cambios: eliminado app/Http/Controllers/Admin/RoleController.php, app/Http/Requests/Admin/UpdateRoleRequest.php y resources/views/admin/roles/*; quitadas las rutas admin.roles.* y el ítem "Roles" del layout admin; eliminado tests/Feature/AdminUserRoleTest.php y creado tests/Feature/AdminUserTest.php.
- Permisos: Spatie se mantiene como motor. Se reemplazaron EMPLOYEE_PERMISSIONS/PERMISSION_LABELS por User::PERMISSION_CATALOG agrupado por módulo ("Punto de venta" → "Acceder al punto de venta"; "Productos" → "Acceder a productos") y los métodos permissionLabels() y assignablePermissions(); la UI ya no muestra la clave cruda.
- Eliminar empleado: ruta DELETE admin.users.destroy dentro del permiso admin.users.manage; solo usuarios con rol empleado (403 para admin), limpia permisos y roles directos antes del borrado físico.
- Tests: 62 tests / 232 assertions en verde; Pint passed; route:list muestra 16 rutas admin sin roles.
- Riesgo abierto: el borrado es físico; cuando existan ventas u otros registros con user_id habrá que migrar a soft delete o restringir el borrado.
- Estado: IMPLEMENTADO el 2026-11-02.
