---
entity: bug-preuso-14
type: bug
---

- Auditoría: #14. Filtro por cajero en ventas del día usa siempre user_id=1.
- Estado: CAUSA RAÍZ CONFIRMADA mediante código y reproducción aislada de Conditionable instalado.
- Evidencia: app/Http/Controllers/Pos/SaleController.php:157; vendor/laravel/framework/src/Illuminate/Conditionable/Traits/Conditionable.php:21-34.
- Caso: admin selecciona empleado ID 7, when recibe true y su callback convierte ese booleano en 1.
- Causa confirmada: hipotesis-H14-preuso.
- Fecha: 2026-11-16. Sin implementación.
