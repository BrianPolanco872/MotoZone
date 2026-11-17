---
entity: hipotesis-H7-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: app/Models/CashMovement.php:134-157; app/Services/CashMovementService.php:211-225; app/Http/Controllers/Admin/CajaController.php:45-59.
- Mecanismo: belongsToClosedCut retorna false sin sale_id y usa paid_at de la venta en vez de happened_at del movimiento. El servicio y el controlador heredado confían en ese guard para permitir borrar.
- Verificación: modelo y callers revisados. PosCashArchiveTest cubre movimiento ligado a venta, no manual del día cerrado.
