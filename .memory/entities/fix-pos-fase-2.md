---
entity: fix-pos-fase-2
type: fix
---

- Objetivo: Implementar Fase 2 del plan plan-feat-pos-01: Spatie, autenticación, roles/permisos, CRUD de usuarios/roles/productos, protección de /admin y /pos, timezone y shell del POS.
- Fecha: 2026-10-26.
- Spatie: versión 8.3.2 instalada; migraciones create_permission_tables, add_is_active_to_users_table y add_pos_fields_to_products_table aplicadas en MySQL local.
- Auth: login/logout nativo, rate limit, middleware EnsureUserIsActive, redirección por rol y comando app:create-admin.
- Roles/permisos: admin con todos los permisos; empleado con pos.access, pos.sell, pos.wholesale, pos.cash.manage, pos.tickets.print.own, pos.sales.day.own y pos.returns.request. Rol admin inmutable; matriz de permisos editable para otros roles.
- CRUD: productos con barcode, image_path, tax_rate, slug y sku; usuarios con rol e is_active; roles/permisos.
- POS: ruta pos.index protegida con pos.access; shell Blade sin lógica de ventas; config/pos.php.
- Timezone: config/app.php usa APP_TIMEZONE; .env y .env.example con America/Mexico_City.
- Archivos clave: app/Http/Controllers/Auth/LoginController.php, app/Http/Middleware/EnsureUserIsActive.php, app/Http/Controllers/Admin/UserController.php, app/Http/Controllers/Admin/RoleController.php, app/Http/Controllers/Admin/ProductController.php, app/Http/Controllers/PosController.php, resources/views/pos/index.blade.php, database/seeders/RolePermissionSeeder.php.
- Verificación: 39 tests / 154 assertions; vendor/bin/pint --dirty --format agent passed; npm run build correcto.
- Desviaciones: extensión GD no instalada (el test de imagen usa un PNG real); no se ejecutó db:seed completo; no se implementaron ventas, caja, tickets, devoluciones ni mayoreo.
- Repositorio: cambios sin commit; el repo raíz solo versiona README.md y existe una carpeta anidada MotoZone con un .git vacío.
- Estado: FASE 2 IMPLEMENTADA.
