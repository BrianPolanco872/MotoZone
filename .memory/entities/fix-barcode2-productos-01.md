---
entity: fix-barcode2-productos-01
type: fix
---

- Objetivo: Implementar el registro de dos códigos de barras por producto y su búsqueda en POS.
- Archivos tocados: database/migrations/2026_11_16_011541_add_barcode2_to_products_table.php; app/Http/Controllers/Admin/ProductController.php; app/Http/Requests/Admin/ProductRequest.php; app/Models/Product.php; app/Http/Controllers/PosController.php; resources/views/admin/products/partials/form.blade.php; resources/views/admin/products/index.blade.php; resources/views/pos/index.blade.php; resources/js/pos/pos.js; tests/Feature/AdminProductTest.php; tests/Feature/PosProductSearchTest.php.
- Estado: IMPLEMENTADO
- Verificación: Pint, PHP syntax checks, JavaScript syntax check y git diff --check pasaron. Las pruebas automatizadas y la migración no se ejecutaron; el escáner físico no se verificó.
