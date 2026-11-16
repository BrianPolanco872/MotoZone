---
entity: fix-quitar-categorias-01
type: fix
---

- Objetivo: retirar CRUD de categorías y clasificación de productos, y limpiar accesos al catálogo en la home.
- Archivos tocados: routes/web.php, app/Http/Controllers/Admin/ProductController.php, app/Http/Requests/Admin/ProductRequest.php, resources/views/layouts/admin.blade.php, resources/views/admin/products/{index,create,edit}.blade.php, resources/views/admin/products/partials/form.blade.php, database/seeders/DatabaseSeeder.php, resources/views/home.blade.php, app/Http/Controllers/CatalogController.php, resources/views/catalog/show.blade.php, tests/Feature/AdminProductTest.php, tests/Feature/AdminTest.php.
- Eliminados: Admin/CategoryController.php, Admin/CategoryRequest.php, Admin/ReclassifyProductsRequest.php, resources/views/admin/categories/*, admin/products/classify*.blade.php, tests/Feature/AdminCategoryTest.php.
- Migración añadida: 2026_11_11_130000_make_products_category_id_nullable.php (products.category_id nullable; conserva columna e índice).
- Desviaciones: se eliminó la aserción obsoleta `assertDontSee('Productos activos')` en AdminTest porque el texto ahora existe legítimamente en el caption accesible del dashboard.
- Verificación: suite objetivo 74/74 en verde; suite completa 188/190 (2 fallos ajenos: PosAccessTest texto "La venta está vacía" y PosCashHistoryFiltersTest 403).
- Estado: IMPLEMENTADO
