---
entity: hipotesis-H14-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: app/Http/Controllers/Pos/SaleController.php:157.
- Mecanismo: when(canSeeAny && filled(employee), callback) entrega al callback el resultado booleano de la condición, no el input employee. (int)true vale 1.
- Prueba aislada ejecutada: Conditionable::when(true, callback) imprimió employee=1; sin aplicación, BD ni escrituras. Vista day sí envía un ID de empleado.
