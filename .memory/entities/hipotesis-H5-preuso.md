---
entity: hipotesis-H5-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: app/Http/Controllers/Pos/SaleReturnController.php:64-65,87-95; app/Services/ReturnService.php:79-142.
- Mecanismo: solo store controla el cierre. approve escribe salida happened_at=now, inventario y approved_at sin volver a comprobar cierre; el snapshot cerrado queda anterior a esas escrituras.
- Verificación: controladores y servicio leídos. Falta prueba solicitar -> cerrar -> aprobar y prueba de exclusión mutua entre cierre y aprobación.
