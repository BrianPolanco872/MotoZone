---
entity: hipotesis-H1-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: app/Http/Controllers/Admin/ProductController.php:88-100; resources/views/admin/products/index.blade.php:48.
- Mecanismo: clearInventory ejecuta delete sobre StockMovement, SaleItem, Sale y Product en vez de actualizar stock; el texto confirma otra operación. No existe transacción ni prueba del endpoint localizada.
- Verificación: lectura completa del método, formulario, rutas y consumidores contables. No reproducción destructiva.
