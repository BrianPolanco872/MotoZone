---
entity: fix-editor-inicio-cms-01
type: fix
---

- Objetivo: rediseñar Editar inicio conforme al HTML del usuario, manteniendo funcionalidad y datos existentes.
- Archivos tocados: resources/views/layouts/home-editor.blade.php (nuevo), resources/views/admin/home/edit.blade.php, resources/views/admin/home/partials/navigation.blade.php, resources/views/admin/home/partials/block-settings.blade.php, resources/views/admin/home/partials/visibility.blade.php, resources/views/admin/home/partials/card-form.blade.php, resources/js/home-editor.js, resources/css/app.css, tests/Feature/AdminHomeContentTest.php.
- Correcciones de integración: aislar old input por card, restaurar product_id al descartar, mostrar primera foto subida, proteger borradores al navegar/guardar otro formulario, evitar resultados de búsqueda obsoletos.
- Conservado: permisos, rutas, validaciones, carga y eliminación de fotos, CRUD de cards, ordenación existente. Categorías siguen siendo cards promocionales; sin cambios en home pública ni categorías de productos.
- Verificaciones: sintaxis Blade/PHP/JS, compilación Tailwind en memoria y formato Pint correctos. Tests y revisión visual no ejecutados. Build pendiente (no existe build.sh).
- Estado: IMPLEMENTADO
- Corrección posterior de assets: diagnóstico confirmado de compilados obsoletos (08/11/2026) y ausencia de public/hot; se inicia Vite en modo desarrollo para cargar el tema y las pestañas actuales en localhost:8000. El proceso Vite debe permanecer activo; no sustituye un build de despliegue.
- Verificación HTTP posterior: public/hot apunta a http://127.0.0.1:5173; CSS y JS responden 200 e incluyen .home-cms, .cms-panel, data-editor-tab y data-editor-discard. La respuesta de Laravel en /login incluye @vite/client. No se verificó la pantalla autenticada en navegador.
