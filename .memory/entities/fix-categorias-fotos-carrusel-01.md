---
entity: fix-categorias-fotos-carrusel-01
type: fix
---

- Objetivo: implementar el plan plan-categorias-fotos-carrusel-01 (fotos de categoría, carruseles y precio/existencias reales en Novedades).
- Archivos tocados: database/migrations/2026_11_11_000000_add_gallery_and_product_to_home_editorial.php; app/Models/{Category,HomePage,HomePublication}.php; app/Services/GalleryService.php; app/Http/Requests/Admin/{CategoryRequest,HomeContentRequest,HomePublicationRequest}.php; app/Http/Controllers/Admin/{CategoryController,HomeContentController,HomePublicationController}.php; routes/web.php; resources/views/components/carousel.blade.php; resources/views/admin/partials/photos.blade.php; resources/views/admin/categories/{form,index}.blade.php; resources/views/admin/home/edit.blade.php; resources/views/admin/home/partials/{publication-form,publications}.blade.php; resources/views/home.blade.php; resources/js/{app.js,carousel.js,home-editor.js}; tests/Feature/{AdminCategoryTest,AdminHomeContentTest,HomePublicationTest}.php.
- Cambios aplicados: columnas aditivas `gallery` json en home_pages/categories/home_publications y `product_id` nullable en home_publications; galerías con límite de 6 fotos (JPEG/PNG/WebP, 20 MB, 6000×6000) mediante GalleryService con borrado after-commit; componente `x-carousel` y JS de flechas sin dependencias; editor de fotos reutilizable; selector de producto por búsqueda JSON en la tarjeta; home pública con carrusel, foto de categoría con respaldo de icono y precio/existencias reales (`Existencias: N` cuando hay control de inventario).
- Verificación: `php artisan test` de las suites tocadas → 146 pruebas, 984 aserciones, todas pasan. Resto de la suite: 299 pruebas con 3 fallos ajenos a este cambio (AdminTest dashboard, PosAccessTest, PosCashHistoryFiltersTest), en archivos modificados por sesiones previas.
- Desviaciones: ninguna respecto al plan; Hero conserva su selector de foto principal y las fotos de galería se suman como diapositivas.
- Estado: IMPLEMENTADO
- Última actualización: 2026-11-11.
