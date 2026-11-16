---
entity: fix-home-familias-editor-01
type: fix
---

- Objetivo: reemplazar el apartado Categorías de Editar inicio por familias visuales y vinculación editorial manual de productos, sin importación ni categorías de inventario.
- Archivos tocados: app/Models/HomeCard.php; app/Http/Controllers/Admin/HomeCardController.php; app/Http/Controllers/Admin/HomeContentController.php; app/Http/Controllers/HomeController.php; app/Http/Requests/Admin/HomeCardRequest.php; routes/web.php; resources/views/admin/home/edit.blade.php; resources/views/admin/home/partials/card-form.blade.php; resources/views/home.blade.php; resources/js/home-editor.js; tests/Feature/HomeCardTest.php.
- Archivos nuevos: app/Http/Controllers/Admin/HomeCategoryProductController.php; database/migrations/2026_11_11_234009_add_families_to_home_cards.php; resources/views/admin/home/partials/categories.blade.php; tests/Feature/HomeFamilyTest.php.
- Conservado: resto de apartados del editor, datos de inventario, precios/stock/category_id, snapshots y servicios de ventas; /catalogo continúa eliminado.
- Verificación: migración batch17 aplicada; sintaxis y formato correctos; render básico del editor vacío/actual y con miembros en memoria correcto; HTTP home200/catálogo404; assets actuales entregados por Vite. Regresiones añadidas, no ejecutadas; Testing requiere solicitud explícita. No hay comprobación visual en navegador.
- Estado: IMPLEMENTADO
