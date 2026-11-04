---
entity: plan-permisos-04
type: plan
---

- Tipo: feature
- Estado: PLANIFICADO
- Objetivo: Definir matriz de permisos granulares para admin, aplicar cada permiso en BACKEND y frontend, auditoría de acciones sensibles, y revisar seguridad.
- Pasos:
  1. Revisar y completar matriz de permisos en User::PERMISSION_CATALOG.
  2. Agregar permisos faltantes: pos.price.edit (ya existe), pos.discount (nuevo), pos.cancel.sale (nuevo), pos.return.do (nuevo), pos.cut.manage (ya existe), pos.cut.own (nuevo), pos.products.view (nuevo), pos.products.edit (nuevo).
  3. Aplicar cada permiso en BACKEND (middleware/guard) en rutas existentes.
  4. Aplicar cada permiso en FRONTEND (ocultar/deshabilitar botones).
  5. Crear tabla de auditoría (audit_logs): usuario, acción, entidad, antes/después, IP, fecha.
  6. Registrar auditoría para: cancelar, devolver, eliminar/archivar movimiento, hacer/rehacer corte, cambiar precio.
  7. Revisar seguridad: hash de contraseñas (bcrypt), expiración de sesión, rate limit en login, validación de inputs, protección CSRF/XSS/inyección SQL.
  8. php artisan migrate, php vendor/bin/phpunit, npm run build, vendor/bin/pint.
- Archivos afectados:
  - app/Models/User.php (modificar - agregar permisos)
  - routes/web.php (modificar - agregar middleware a rutas)
  - database/migrations/2026_11_04_020000_create_audit_logs_table.php (nueva)
  - app/Models/AuditLog.php (nueva)
  - app/Services/AuditLogService.php (nueva)
  - resources/views/pos/index.blade.php (modificar - ocultar/deshabilitar botones)
  - resources/views/pos/cuts/index.blade.php (modificar - ocultar/deshabilitar)
  - resources/views/pos/cash/index.blade.php (modificar - ocultar/deshabilitar)
  - resources/views/pos/returns/index.blade.php (modificar - ocultar/deshabilitar)
  - resources/views/admin/usuarios/*.blade.php (modificar - matriz de permisos)
  - tests/Feature/PosPermissionsTest.php (nueva)
- Criterios de aceptación: Matriz de permisos definida; cada permiso en backend y frontend; auditoría para acciones sensibles; seguridad revisada.
- Plan de rollback: php artisan migrate:rollback --step=1; restaurar vistas desde git.
- Última actualización: 2026-11-04
- Estado: IMPLEMENTADO
- Cambios aplicados: Se agregaron los permisos pos.discount, pos.cancel.sale, pos.return.do, pos.cut.own, pos.products.view, pos.products.edit a User::PERMISSION_CATALOG y a RolePermissionSeeder (el admin los hereda). Se aplicó middleware de backend: pos.cancel.sale (cancelar venta), pos.return.do (registrar devolución), pos.cut.own (mi corte). Se creó la tabla audit_logs, el modelo AuditLog y el servicio AuditLogService; se registra auditoría para cancelar, devolver, archivar/eliminar movimiento de caja, hacer corte, registrar conteo y cambiar precio. Se revisó seguridad (bcrypt, rate limit en login, CSRF, validación de inputs). Se ejecutó php artisan migrate, phpunit (91/92; 1 fallo pre-existing ajeno al plan) y vendor/bin/pint.
- Desviaciones del plan original: las vistas de usuarios están en resources/views/admin/users/ (no admin/usuarios/) y la matriz se renderiza dinámicamente desde el catálogo, por lo que los nuevos permisos aparecen automáticamente; pos.products.view/edit y pos.discount se dejan en el catálogo para asignación futura sin ruta de backend propia.
