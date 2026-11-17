---
entity: hipotesis-H2-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: app/Http/Controllers/Admin/UserController.php:129-150; database/migrations/2026_11_02_134747_create_sale_returns_tables.php:14.
- Mecanismo: múltiples DELETE previos a forceDelete no están en transacción; una FK requested_by de una devolución de venta ajena permanece y bloquea el último DELETE, sin revertir los anteriores.
- Verificación: controlador, esquema y permisos del flujo de devolución revisados. EmployeePermissionsTest solo cubre un empleado sin historial.
