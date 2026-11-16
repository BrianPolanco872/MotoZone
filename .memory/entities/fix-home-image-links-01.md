---
entity: fix-home-image-links-01
type: fix
---

- Objetivo: añadir enlaces HTTPS directos, previsualización y fallback para las imágenes en todos los módulos de foto de Editar inicio, manteniendo la carga de archivos.
- Archivos tocados: resources/views/admin/partials/media-upload.blade.php; resources/views/admin/home/edit.blade.php; resources/views/admin/home/partials/card-form.blade.php; resources/js/home-editor.js; app/Http/Requests/Admin/HttpsImageUrl.php; app/Http/Requests/Admin/HomeContentRequest.php; app/Http/Requests/Admin/HomeCardRequest.php; app/Http/Controllers/Admin/HomeContentController.php; app/Http/Controllers/Admin/HomeCardController.php; app/Models/HomePage.php; app/Models/HomeCard.php; app/Services/GalleryService.php; tests/Feature/AdminHomeContentTest.php; tests/Feature/HomeCardTest.php.
- Estado: IMPLEMENTADO.
- Cambios aplicados: se agregaron campos HTTPS para foto principal y carrusel, validación de enlaces y combinaciones ambiguas, preview con error/fallback en navegador, persistencia sin migración, lectura segura de enlaces guardados y limpieza que nunca intenta borrar URL como archivo. Los enlaces no modifican fotos de inventario.
- Desviaciones del plan original: el archivo local anterior de Presentación no se elimina al cambiar a URL, para mantener el comportamiento existente.
- Verificación: Pint focalizado, lint PHP, Blade cache y sintaxis JavaScript pasaron; no se ejecutaron pruebas.
