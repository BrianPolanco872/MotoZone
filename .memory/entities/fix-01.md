---
entity: fix-01
type: fix
---

- Objetivo: Al eliminar un empleado, borrar físicamente todos sus datos relacionados y liberar el correo electrónico para poder volver a registrarlo con el mismo correo.
- Archivos tocados: app/Http/Controllers/Admin/UserController.php, tests/Feature/EmployeePermissionsTest.php
- Estado: IMPLEMENTADO
- Cambios aplicados:
  1. Se cambió $user->delete() (soft delete) por lógica de borrado en cascada manual seguida de $user->forceDelete().
  2. Se borran primero cash_movements y sales (que tienen restrictOnDelete y bloquean el borrado físico), luego stock_movements, daily_cuts, customer_payments, supplier_payments y audit_logs.
  3. Se usa \DB::table() para evitar conflictos con SoftDeletes en modelos como AuditLog (que no tiene columna deleted_at) y CashMovement.
  4. Finalmente forceDelete() elimina el registro de users y libera el email.
  5. Se actualizó el test test_admin_can_delete_employee para verificar borrado físico en lugar de soft delete.
- Pruebas: 16 tests pasan (AdminUserTest + EmployeePermissionsTest), 85 assertions.
- Desviaciones del plan original: N/A