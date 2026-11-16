---
entity: fix-retirar-catalogo-publico-01
type: fix
---

- Objetivo: eliminar catálogo público, categorías de productos y enlaces al catálogo; preservar categorías manuales del inicio y fichas de productos.
- Archivos tocados: routes/web.php; app/Http/Controllers/CatalogController.php; app/Http/Controllers/PosController.php; app/Providers/AppServiceProvider.php; app/Models/HomePage.php; app/Models/HomeCard.php; app/Http/Requests/Admin/HomeCardRequest.php; app/Http/Requests/Admin/HomeContentRequest.php; resources/views/layouts/site.blade.php; resources/views/home.blade.php; resources/views/catalog/show.blade.php; resources/views/components/product-visual.blade.php; resources/views/admin/dashboard.blade.php; resources/views/admin/products/index.blade.php; resources/views/admin/products/create.blade.php; resources/views/admin/products/partials/form.blade.php; resources/views/admin/home/partials/card-form.blade.php; resources/views/auth/login.blade.php; resources/js/pos/pos.js; tests/Feature/CatalogTest.php; tests/Feature/HomeTest.php; tests/Feature/HomeCardTest.php; tests/Feature/AdminHomeContentTest.php; tests/Feature/AdminTest.php.
- Archivos eliminados: resources/views/catalog/index.blade.php, resources/views/catalog/partials/filters.blade.php.
- Conservado: tablas/modelos/columnas históricas de categorías, relación Product::category para snapshots, SaleService y DailyCutService, /producto, cards promocionales y editor del inicio.
- Verificación: sintaxis PHP/Blade/JS y formato correctos; rutas inspeccionadas; home HTTP200 sin enlaces catálogo, catálogo HTTP404. No se ejecutó suite de pruebas ni revisión visual en navegador.
- Pendiente: migración previa 2026_11_11_130000_make_products_category_id_nullable está sin aplicar. Se informó, sin ejecutar migraciones.
- Pendiente resuelto con autorización posterior «aplicalo»: se ejecutó exclusivamente la migración nullable de products.category_id; resultado DONE y estado [16] Ran. Sin cambios en archivos de código ni borrado de datos. No se ejecutaron tests.
