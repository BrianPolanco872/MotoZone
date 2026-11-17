---
entity: hipotesis-H10-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: app/Services/SaleService.php:222-225; callers create:55 y chargePending:113-121.
- Mecanismo: array_map con retorno int se aplica antes de filtrar IDs nulos de artículos libres. La adaptación de chargePending/assertStock también asume producto de catálogo y no conserva is_custom.
- Prueba aislada ejecutada: ReflectionMethod(lockProducts)->invoke con product_id=null produjo TypeError: Return value must be of type int, null returned. Sin BD, archivos de prueba ni cambios de aplicación.
- Alcance de validación: creación pagada, pendiente, cobro pendiente y tickets; no basta corregir solo el primer array_map.
