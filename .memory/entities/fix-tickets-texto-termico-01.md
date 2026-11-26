---
entity: fix-tickets-texto-termico-01
type: fix
---

- Objetivo: Imprimir tickets de venta como texto ASCII de 32/42 columnas, con respaldo operativo en modo Gráfico.
- Archivos tocados: `app/Http/Controllers/Pos/SaleController.php`, `app/Http/Controllers/Pos/TicketSettingsController.php`, `app/Http/Requests/Pos/UpdateTicketSettingsRequest.php`, `config/pos.php`, `resources/views/pos/tickets/{ticket,settings,text-print,text-styles}.blade.php`, `resources/js/{ticket-settings,ticket-text}.js`, `vite.config.js`, `tests/Feature/{PosAccessTest,TicketSettingsTest}.php`, `tests/ticket-text.test.js`, `public/build/manifest.json` y reemplazo de bundles JS.
- Estado: IMPLEMENTADO.
- Verificación: 60 pruebas PHP, 441 aserciones; 6 pruebas JS del formatter y configuración, 5 pruebas JS de impresión del POS; pasan todas las pruebas del plan. En ejecución ampliada del archivo del POS, fallan dos pruebas previas ajenas a impresión sobre cantidades a granel; fuentes del carrito y esas pruebas no se cambiaron.
- Build: `./build.sh` correcto, con `v1.0.1` sobre HEAD y metadatos alineados; sin migraciones ni cambio de versión. Pint y comprobación de diff correctos.
- Pendiente: Sin prueba física en Chrome/Windows con impresora 58/80 mm; la cola puede cambiar el resultado aunque la vista previa y el texto estén alineados. No publicado.
