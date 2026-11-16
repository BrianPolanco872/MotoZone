---
entity: fix-inicio-simple
type: fix
---

- Objetivo: unificar la administración de la home en una sola pantalla Editar inicio con cinco bloques (Presentación, Categorías, Productos destacados, Recién llegados, Novedades y temporada), guardado parcial por bloque, selección sin posiciones visibles, imágenes por archivo o enlace HTTPS hasta 20MB y decoración mensual desactivable.
- Archivos tocados: app/Http/Controllers/Admin/HomeContentController.php; app/Http/Controllers/Admin/HomePublicationController.php; app/Http/Controllers/HomeController.php; app/Models/HomePage.php; app/Models/HomePublication.php; app/Http/Requests/Admin/HomeContentRequest.php; app/Http/Requests/Admin/HomeSelectionRequest.php; app/Http/Requests/Admin/HomePublicationRequest.php; app/Rules/HttpsImageUrl.php; database/migrations/2026_11_10_235301_add_image_url_to_home_publications_table.php; resources/views/admin/home/*; resources/views/home.blade.php; resources/views/layouts/admin.blade.php; resources/css/app.css; resources/js/home-editor.js; resources/js/app.js; routes/web.php; tests/Feature/HomeTest.php, AdminHomeContentTest.php, HomePublicationTest.php.
- Verificación: php -l sin errores en controladores/modelos/requests/reglas y pruebas de home; Blade compiló (view:cache y view:clear); rutas admin.home.* registradas (16); migración image_url aplicada; Pint --dirty solo corrigió un import no usado en HomeTest.php.
- Pendiente de entorno: public/build es del 2026-11-08 y no existe build.sh; los estilos/JS nuevos no se verán hasta ejecutar Vite (npm run dev) o un build autorizado. No se ejecutó Testing ni build.
- Estado: IMPLEMENTADO.
