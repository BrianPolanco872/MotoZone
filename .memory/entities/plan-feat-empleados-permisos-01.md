---
entity: plan-feat-empleados-permisos-01
type: plan
---

- Tipo: feature
- Objetivo: Plan técnico del módulo de empleados, permisos individuales y perfil, partiendo del estado real del repositorio.
- Estado previo verificado: el rol admin ya hereda los 14 permisos; el rol empleado traía 8 permisos fijos; /admin/usuarios ya tenía CRUD con rol; /admin/roles/{role}/editar ya tenía matriz por rol; el layout admin ya filtraba el menú por permiso; /admin/productos estaba bajo role:admin; ProductRequest ya autorizaba con admin.products.manage.
- Pasos: constantes de permisos en User; vaciar permisos base de empleado; rutas por permiso + perfil + permisos por usuario; UpdateEmployeePermissionsRequest; updatePermissions en UserController; rehacer admin.users.index (alta + matriz); ProfileController y sus FormRequests; vista admin/profile/edit; nav "Empleados" y "Mi perfil"; tests.
- Archivos previstos: app/Models/User.php; database/seeders/RolePermissionSeeder.php; routes/web.php; app/Http/Controllers/Admin/UserController.php; app/Http/Requests/Admin/UpdateEmployeePermissionsRequest.php; app/Http/Controllers/ProfileController.php; app/Http/Requests/Profile/UpdateProfileRequest.php; app/Http/Requests/Admin/UpdateEmployeeProfileRequest.php; resources/views/admin/users/index.blade.php; resources/views/admin/profile/edit.blade.php; resources/views/layouts/admin.blade.php; tests/Feature/{EmployeePermissionsTest,ProfileTest}.php y ajustes a AuthTest, PosAccessTest y AdminUserRoleTest.
- Plan de rollback: revertir archivos y re-sembrar RolePermissionSeeder para restaurar los 8 permisos base del rol empleado; limpiar permisos directos con syncPermissions([]). Sin migraciones.
- Riesgos: empleados existentes pierden POS hasta que el admin les asigne permiso; admin.products.manage otorga CRUD completo (no solo lectura); el nombre es credencial de login; doble sistema rol/usuario; caché de Spatie.
- Estado: IMPLEMENTADO el 2026-11-02 (ver fix-empleados-permisos).
- Cambios aplicados: rol empleado sin permisos base; permisos individuales pos.access y admin.products.manage desde /admin/usuarios (alta de empleado arriba + matriz por empleado); /admin/productos protegido por permiso; /admin/perfil con datos propios y bloque admin para editar empleados; nav "Empleados" y "Mi perfil".
- Verificación: 61 tests / 234 assertions en verde; Pint --dirty passed; BD local re-sembrada (admin 14 permisos, rol empleado 0).
- Desviaciones del plan original: se agregó la redirección post-login a admin.products.index para empleados con ese permiso y atajos de acceso en el navbar de la tienda; AuthTest ajustado.
- Ajustes 2026-11-02 (fix-empleados-ajustes-02): módulo de roles eliminado (rutas, controlador, request, vistas, nav y tests); opción de eliminar empleado con DELETE admin.users.destroy; catálogo de permisos agrupado por módulo y etiquetas naturalizadas; Spatie se mantiene como motor.
