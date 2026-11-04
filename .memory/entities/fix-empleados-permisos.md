---
entity: fix-empleados-permisos
type: fix
---

- Objetivo: Módulo de empleados con permisos individuales (POS y Productos), acceso de empleados a /admin/productos por permiso, perfil propio y gestión de datos de empleados por el admin.
- Archivos tocados: app/Models/User.php (EMPLOYEE_PERMISSIONS y PERMISSION_LABELS); database/seeders/RolePermissionSeeder.php (rol empleado sin permisos base); routes/web.php (subgrupos por permiso + perfil + permisos por usuario); app/Http/Controllers/Admin/UserController.php (index con permisos + updatePermissions); app/Http/Requests/Admin/UpdateEmployeePermissionsRequest.php (nuevo); app/Http/Requests/Admin/UpdateEmployeeProfileRequest.php (nuevo); app/Http/Controllers/ProfileController.php (nuevo); app/Http/Requests/Profile/UpdateProfileRequest.php (nuevo); resources/views/admin/users/index.blade.php (alta + panel de permisos); resources/views/admin/profile/edit.blade.php (nuevo); resources/views/layouts/admin.blade.php (Empleados + Mi perfil); resources/views/layouts/site.blade.php (atajo Productos y fallback Mi perfil); app/Http/Controllers/Auth/LoginController.php (redirección a productos); tests/Feature/EmployeePermissionsTest.php y ProfileTest.php (nuevos); tests/Feature/AuthTest.php, PosAccessTest.php y AdminUserRoleTest.php (ajustes).
- Verificación: 61 tests / 234 assertions en verde; Pint passed; route:list confirma 18 rutas admin; BD local re-sembrada y verificada (admin con 14 permisos incluidos pos.access y admin.products.manage; rol empleado con 0 permisos).
- Desviaciones justificadas: (1) routeForUser envía a admin.products.index al empleado sin POS pero con permiso de productos, para que el permiso sea alcanzable tras el login; (2) layouts/site agrega atajo a Productos y un fallback "Mi perfil" para usuarios sin módulos; (3) AuthTest otorga pos.access explícito al empleado y añade la prueba de redirección a productos.
- Estado: IMPLEMENTADO el 2026-11-02.
