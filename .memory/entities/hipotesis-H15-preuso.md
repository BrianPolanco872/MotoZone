---
entity: hipotesis-H15-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: app/Services/SaleService.php:287-290,359-362,391-405; app/Http/Requests/Pos/StoreSaleRequest.php:43.
- Mecanismo: normalización conserva IDs repetidos y compara cada cantidad con el mismo stock anterior; no compara la suma por producto. assertStock del cobro pendiente tiene el mismo supuesto.
- Prueba aislada ejecutada: ReflectionMethod(prepareItems) con Product/PricingService/User simulados aceptó cantidad total 6 con stock 5. No se invocaron actualizaciones ni BD.
