---
entity: fix-pos-mayoreo-por-renglon-01
type: fix
---

- Objetivo: Aplicar Mayoreo solo al renglón seleccionado del carrito y enviarlo por ítem al servidor.
- Archivos tocados: `resources/js/pos/pos.js`, `resources/views/pos/index.blade.php`, `tests/pos.test.js`, `tests/Feature/PosSalesTest.php`, `public/build/manifest.json` y assets `app-DV4jjQno.css` / `app-DzZD9wcC.js`.
- Estado: IMPLEMENTADO.
- Verificación básica: 81/81 pruebas JS, 44/44 pruebas PHP del POS, Pint, Blade compilada, `git diff --check` y build con `./build.sh`.
- Pendiente: Verificación visual en el navegador de caja y Testing por solicitud explícita. Sin despliegue.
