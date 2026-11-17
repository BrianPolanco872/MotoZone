---
entity: hipotesis-H13-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: app/Http/Controllers/Admin/ProductController.php:47; app/Models/Product.php:57-65.
- Mecanismo: llamada stockMovements inexistente; búsqueda en app encontró solo su uso en ProductController, sin definición ni resolveRelationUsing. El cortocircuito evita el fallo únicamente cuando ya hay ventas.
- Verificación: modelo completo y referencias de app leídos. Falta prueba del endpoint de borrado sin historial.
