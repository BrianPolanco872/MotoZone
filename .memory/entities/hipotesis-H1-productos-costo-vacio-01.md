---
entity: hipotesis-H1-productos-costo-vacio-01
type: hipotesis
---

- Estado: PARCIAL; explica por qué NULL falla contra el esquema, no por qué el 7.50 informado por el usuario terminó como NULL.
- Archivos: app/Http/Requests/Admin/ProductRequest.php:51,55; app/Http/Controllers/Admin/ProductController.php:394-419; database/migrations/2026_11_02_134744_add_cost_and_inventory_fields_to_products_table.php:12-14.
- Mecanismo: campos vacíos pasan como null la validación nullable y se incluyen explícitamente en el INSERT/UPDATE pese al esquema NOT NULL. Deben normalizarse a 0 sin relajar validación ni cambiar el esquema.
- Aclaración: no aplicar normalización a cero como reparación principal del reporte hasta confirmar dónde se pierde el costo válido.
