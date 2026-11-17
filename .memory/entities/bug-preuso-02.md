---
entity: bug-preuso-02
type: bug
---

- Auditoría: #2. El borrado de un empleado puede fallar después de eliminar parte de sus datos.
- Estado: CAUSA RAÍZ CONFIRMADA por código y restricciones de esquema; escenario no ejecutado.
- Evidencia: app/Http/Controllers/Admin/UserController.php:129-150; database/migrations/2026_11_02_134747_create_sale_returns_tables.php:14.
- Caso: empleado con operaciones propias y devolución solicitada para venta ajena. Se borran sus operaciones, forceDelete falla por requested_by restrictOnDelete.
- Causa confirmada: hipotesis-H2-preuso.
- Fecha: 2026-11-16. Sin implementación.
- Estado actualizado 2026-11-16: CORREGIDO Y VERIFICADO en fix-preuso-motozone-lote1-01. Prevalidación de devoluciones ajenas y baja transaccional; pruebas pending/approved/rejected y rollback ante excepción/veto final correctas. Política de baja física previa conservada.
