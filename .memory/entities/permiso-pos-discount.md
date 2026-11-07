---
entity: permiso-pos-discount
type: fix
---

- Objetivo: Crear/sembrar el permiso `pos.discount` (y todos los permisos del panel de empleados) que faltaban en la base de datos, resolviendo `Spatie\Permission\Exceptions\PermissionDoesNotExist` al asignar permisos a un empleado.
- Archivos tocados:
  - `database/seeders/DatabaseSeeder.php` (se agregó llamada a `RolePermissionSeeder::class`)
  - `database/seeders/RolePermissionSeeder.php` (ya contenía `'pos.discount'` y los demás permisos; no requirió cambios)
- Estado: IMPLEMENTADO
- Última actualización: 2026-11-05
