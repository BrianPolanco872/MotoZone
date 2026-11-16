---
entity: fix-home-categorias-simplificar-01
type: fix
---

- Objetivo: simplificar el formulario de categorías de Editar inicio (sin subtítulo, slug, posición, badge, enlace alternativo ni texto del enlace) y publicar primero cada categoría nueva.
- Archivos tocados: resources/views/admin/home/partials/card-form.blade.php; resources/views/admin/home/partials/categories.blade.php; resources/js/home-editor.js; app/Models/HomeCard.php; app/Http/Requests/Admin/HomeCardRequest.php; app/Http/Controllers/Admin/HomeCardController.php; tests/Feature/HomeFamilyTest.php; tests/Feature/HomeCardTest.php.
- Comportamiento: los seis campos solo se ocultan en categorías; productos y novedades los conservan. El ancla se genera en servidor al crear (slug único con sufijo -2, -3…) y no cambia al renombrar. Una categoría creada activa, o un borrador que se activa, pasa a la posición 0 y las demás se reindexan desde 1 conservando su orden relativo. Editar una categoría ya visible no la mueve. Las flechas Subir/Bajar siguen disponibles.
- Datos: no se borran subtítulo, badge, enlaces, slug, posición, imágenes ni vínculos históricos; simplemente dejan de recibirse desde el formulario (validación con exclude) y se preservan en la actualización.
- Rollback: restaurar solo los fragmentos de esta tarea desde C:/Users/Administrador/AppData/Local/Temp/opencode/home-categorias-simplificar-01-20261115-171958 sin sobrescribir cambios posteriores; no requiere tocar BD.
- Verificación: php -l correcto en modelo, request, controlador y dos archivos de pruebas; node --check del JS correcto; card-form y categories compilan a PHP válido; Pint acotado aprobado; git diff --check del alcance sin errores. Testing NO ejecutado (requiere solicitud explícita); sin build ni commits.
- Estado: IMPLEMENTADO.
