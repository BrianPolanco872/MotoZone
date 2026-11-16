---
entity: plan-categorias-fotos-carrusel-01
type: plan
---

- Tipo: feature
- Feature: feature-categorias-fotos-carrusel.
- Objetivo: simplificar la administración de categorías, permitir subir fotos desde el dispositivo en cada bloque con imagen (presentación, categorías y novedades) con carrusel, y que las tarjetas de Novedades puedan mostrar precio público y existencias reales de un producto del inventario.
- Decisiones aprobadas por el usuario: la foto de categoría se administra en el panel de Categorías; si no hay foto se conserva el icono; precio y existencias salen de un producto real elegido (sin duplicar inventario); fotos múltiples tipo carrusel en presentación, categorías y novedades.
- Fuera de alcance: precios o existencias escritos a mano, duplicar inventario, modificar cobro/cortes/POS, fotos múltiples en productos, nuevas dependencias JS, edición de cabecera/pie, reclasificación automática.
- Pasos: 1) migración aditiva gallery json en home_pages/categories/home_publications y product_id nullable en home_publications; 2) modelos con galería, relación producto y portada; 3) requests con límite de fotos, validación de imágenes 20MB y producto activo; 4) controladores guardan/eliminan archivos con coherencia transaccional; 5) component carousel reutilizable y JS mínimo de flechas; 6) editor de fotos reutilizable en presentación, tarjetas y categorías; 7) selector de producto por búsqueda en la tarjeta; 8) home pública con carrusel, foto de categoría con respaldo de icono y precio/existencias reales; 9) pruebas de subida, carrusel y producto; 10) lint, Pint y Blade.
- Límites: hasta 6 fotos por galería (principal incluidas), JPEG/PNG/WebP 20480KB y 6000×6000 píxeles; el límite efectivo del servidor web se comunica, no se promete.
- Plan de rollback: exportar galerías y product_id antes de migrar; revertir vistas/consultas y dejar columnas aditivas mientras se conservan archivos; no borrar imágenes existentes sin confirmar; nunca restaurar BD completa sobre ventas nuevas.
- Archivos previstos: migración nueva; app/Models/{HomePage,HomePublication,Category}.php; app/Http/Requests/Admin/{HomeContentRequest,HomePublicationRequest,CategoryRequest}.php; app/Http/Controllers/Admin/{HomeContentController,HomePublicationController,CategoryController}.php; routes/web.php; resources/views/components/carousel.blade.php; resources/views/admin/partials/photos.blade.php; resources/views/admin/{categories/form,home/edit,home/partials/publication-form,home/partials/publications}.blade.php; resources/views/home.blade.php; resources/js/{app.js,carousel.js,home-editor.js}; pruebas AdminCategoryTest, AdminHomeContentTest y HomePublicationTest.
- Última actualización: 2026-11-11.
- Aprobación explícita: usuario respondió las dos preguntas de alcance y pidió «mientras más simple mejor».
- Estado: IMPLEMENTADO
- Cambios aplicados: migración aditiva aplicada; modelos con galería y producto; requests con límite de fotos y producto activo; GalleryService con borrado after-commit; controladores de categorías, presentación y tarjetas; componente carrusel y JS de flechas; editor de fotos en panel; selector de producto; home pública con carrusel, precio y existencias reales; pruebas verdes (146 pruebas / 984 aserciones en las suites tocadas).
- Desviaciones del plan original: ninguna. Los 3 fallos restantes de la suite completa están en archivos de sesiones previas (dashboard/pos) y quedan fuera de este alcance.
