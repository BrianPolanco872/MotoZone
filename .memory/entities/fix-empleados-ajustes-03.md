---
entity: fix-empleados-ajustes-03
type: fix
---

- Objetivo: Soft delete de empleados, retiro del permiso admin.roles.manage y permisos con nombres claros para personal no técnico.
- Soft delete: migración 2026_11_02_133038_add_deleted_at_to_users_table (softDeletes en users, aplicada en MySQL); trait SoftDeletes en User; destroy ahora hace borrado lógico y conserva roles/permisos para una futura restauración. Un usuario borrado no puede autenticarse y el listado no lo muestra (scope global). No hay UI de restauración.
- Permisos: eliminado admin.roles.manage del RolePermissionSeeder y borrada la fila del permiso en BD (admin queda con 13 permisos).
- Nombres claros: User::PERMISSION_CATALOG ahora tiene label + description por permiso, agrupado por módulo ("Punto de venta" → "Usar el punto de venta" / "Abrir la caja para cobrar y registrar ventas."; "Productos" → "Ver y editar productos" / "Consultar el catálogo y actualizar productos, precios y existencias."). Métodos permissionCatalog(), permissionLabels() y assignablePermissions(). La UI muestra el nombre claro y la descripción; ya no se ve la clave interna.
- Spatie sigue siendo el motor de permisos; las claves internas (pos.access, admin.products.manage) no cambian.
- Verificación: 62 tests / 235 assertions en verde; Pint passed; BD verificada (deleted_at sí, admin 13 permisos, admin.roles.manage sin rol ni fila).
- Riesgo/nota: por el índice unique de email, un empleado con soft delete conserva su correo reservado; no se puede reutilizar sin purga o cambio de índice.
- Estado: IMPLEMENTADO el 2026-11-02.
