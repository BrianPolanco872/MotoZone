---
entity: fix-pos-impresion-iframe-01
type: fix
---

- Objetivo: Imprimir tickets reales y borradores en un iframe oculto, sin pestañas nuevas ni autoimpresión duplicada.
- Archivos tocados: `app/Http/Controllers/Pos/SaleController.php`; `resources/views/pos/tickets/{ticket,text-print,settings}.blade.php`; `resources/views/pos/sales/{day,previous}.blade.php`; `resources/js/{pos/pos,ticket-settings,ticket-text,ticket-print}.js`; `tests/Feature/TicketSettingsTest.php`; `tests/{pos,ticket-text,ticket-print}.test.js`; manifiesto y dos bundles en `public/build/`.
- Estado: IMPLEMENTADO.
- Verificación local: Prueba PHP dirigida del modo embebido pasó con 32 aserciones, incluida transición guardada de 80 a 58 mm, máximo de 32 columnas, Gráfico intacto, permisos y ausencia de autoimpresión. JS completo: 79 pasaron, 0 fallaron. Pint, sintaxis, `git diff --check` y `./build.sh` correctos. Bundle compilado verificado a 32 columnas para 58 mm.
- Artefactos publicados localmente por build: `public/build/assets/app-sduwOxde.js`, `public/build/assets/ticket-text-BDxmx9uX.js` y `public/build/manifest.json`. CSS y fuentes no cambiaron.
- PHP completo: 752 pruebas; 730 pasaron, 17 fallaron y 5 tuvieron errores; PHPUnit también marcó 1 prueba como riesgosa. Incidencias fuera de impresión (inicio, login, catálogo, administración, bitácora y auditoría de inventario). PHP dirigido a tickets, acceso POS y medios de pago: 61 pasaron, 473 aserciones.
- Pendiente: Prueba física en caja; no se hizo despliegue.
