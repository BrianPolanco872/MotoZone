---
entity: fix-pos-reabrir-corte-visibilidad-01
type: fix
---

- Objetivo: mostrar «Reabrir mi corte del día» solo cuando el usuario tiene su propio corte del día cerrado; corregir que Tailwind v4 generara `.inline-flex` después de `.hidden` y dejara el botón siempre visible.
- Archivos tocados: app/Http/Controllers/PosController.php; resources/views/pos/index.blade.php; tests/Feature/PosReopenCutVisibilityTest.php.
- Cambios: nuevo flag `permissions.own_day_closed` con `DailyCut::isDayClosedForUser`; clase `hidden` condicional en el botón; regla CSS `#pos-reopen-day.hidden { display: none; }`; toggle dinámico por 422 en pos.js se conserva sin cambios.
- Verificación: 3/3 pruebas focales (11 aserciones), Pint y build app-uIAdEu89.js.
- Estado: IMPLEMENTADO (2026-11-22). Sin desviaciones del plan aprobado.
