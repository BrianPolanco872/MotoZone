---
entity: fix-pos-busqueda-prefijo-01
type: fix
---

- Objetivo: Mostrar todos los productos activos cuyo nombre empieza con el término en el POS, cargados progresivamente al bajar, sin cambiar la búsqueda exacta de códigos.
- Archivos tocados: `app/Models/Product.php`, `app/Http/Controllers/PosController.php`, `resources/js/pos/pos.js`, `resources/views/pos/index.blade.php`, `tests/Feature/PosProductSearchTest.php`, `tests/pos.test.js`; `public/build/manifest.json` y bundle JS regenerados con `./build.sh`.
- Estado: IMPLEMENTADO.
- Verificación básica: Pint, sintaxis PHP/JS, `git diff --check` y `./build.sh` correctos. Metadatos de versión `1.0.1` y tag `v1.0.1` coinciden con HEAD; manifiesto apunta al bundle local que contiene la paginación del POS. Vite avisa sobre `fontaine` opcional, sin fallo de build.
- Pendiente: Testing por solicitud explícita, validación en navegador y escáner físico, y publicación.
