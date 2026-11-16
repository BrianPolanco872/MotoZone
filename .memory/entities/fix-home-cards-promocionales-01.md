---
entity: fix-home-cards-promocionales-01
type: fix
---

- Objetivo: implementar el editor "Editar inicio" como tarjetas promocionales independientes del inventario/POS, con carga de medios con preview inmediata, reordenamiento server-side y home pública leyendo cards.
- Archivos creados: database/migrations/2026_11_11_120000_create_home_cards_table.php; app/Models/HomeCard.php; database/factories/HomeCardFactory.php; app/Http/Requests/Admin/HomeCardRequest.php; app/Http/Controllers/Admin/HomeCardController.php; resources/views/admin/home/partials/card-form.blade.php; resources/views/admin/partials/media-upload.blade.php; resources/views/components/promo-card.blade.php; tests/Feature/HomeCardTest.php.
- Archivos modificados: app/Models/HomePage.php; app/Http/Requests/Admin/HomeContentRequest.php; app/Http/Controllers/Admin/HomeContentController.php; app/Http/Controllers/Admin/DashboardController.php; app/Http/Controllers/HomeController.php; routes/web.php; resources/views/admin/home/edit.blade.php; resources/views/admin/home/partials/{navigation,text-field,block-settings,visibility}.blade.php; resources/views/home.blade.php; resources/views/admin/dashboard.blade.php; resources/js/home-editor.js; tests/Feature/{HomeTest,AdminHomeContentTest,AdminTest}.php.
- Archivos eliminados: HomePublicationController, HomePublicationRequest, HomeSelectionRequest, Home model/factory de publicación, vistas publication-form/publications/selection-block/selection-row/image-fields/selection, app/Rules/HttpsImageUrl.php, tests/Feature/HomePublicationTest.php y el directorio admin/home/publications.
- Base de datos: migración aditiva `home_cards` (section/sort_order/is_active + campos tipados + product_id nullable FK set null) aplicada en local con backfill one-time; quedaron 4 cards en `new`, 0 en categories/products.
- Verificación: HomeCardTest 9/9, HomeTest 9/9, AdminHomeContentTest 19/19, AdminTest y AdminCategoryTest/CatalogTest en verde; suite 213 tests con 3 fallos preexistentes no relacionados; pint --dirty y pint de archivos nuevos OK; route:list con 9 rutas admin/home; view:cache y view:clear OK.
- Estado: IMPLEMENTADO.
- Nota: public/build desactualizado; requiere `npm run dev` para ver JS/CSS. Sin commits (prohibido por instrucciones).
