---
entity: fix-home-carrusel-quitar-01
type: fix
---

- Objetivo: hacer funcionar la X de fotos guardadas de carrusel en Recién llegados, Destacados, Presentación y Categorías, sin borrar datos reales durante reparación.
- Estado: IMPLEMENTADO.
- Archivos tocados: resources/views/admin/partials/media-upload.blade.php; resources/js/home-editor.js; tests/home-editor.test.js; tests/Feature/HomeCardTest.php; tests/Feature/AdminHomeContentTest.php.
- Cambios: ocultación visual de item marcado con checkbox aún presente/habilitado/enviable; recuperación al Descartar; hooks y nombre accesible compartidos, sin cambios de backend ni fotos.
- Testing: aprobado explícitamente por selección «Aplicar y probar aislado». Node 4/4 correctas y PHPUnit 8/8, 71 aserciones; SQLite :memory: y almacenamiento falso verificados. No se usó BD MySQL real ni archivos de usuario.
- Verificación: Pint, sintaxis PHP y diff correctos; Vite sirve nueva lógica con HTTP 200. No build de producción por discrepancia de versión/tag previamente documentada.
- Preservado: fotos no seleccionadas, principal, fotos del producto, formato de precios y controles de fotos nuevas. X no hace petición ni borrado automático; requiere Guardar.
- Desviaciones: ninguna.
- Limitación: no hubo prueba manual de navegador; puede requerir recarga del editor abierto.
- Fecha: 2026-11-17.
