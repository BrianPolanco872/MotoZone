---
entity: fix-tickets-termicos-5sprinter-etapa1-01
type: fix
---

- Estado: IMPLEMENTADO EN FUENTE — etapa 1.
- Objetivo: compartir el documento de impresión desde POS e historial, conservar el diálogo nativo y agregar el conteo de artículos al ticket térmico.
- Archivos tocados: `resources/views/pos/tickets/{receipt,receipt-styles,ticket,settings}.blade.php`, `resources/views/pos/index.blade.php`, `resources/js/pos/pos.js`, `tests/Feature/TicketSettingsTest.php`, `tests/pos.test.js`.
- Respaldo previo: `C:/Users/Administrador/AppData/Local/Temp/opencode/motozone-ticket-5sprinter-20261125-112506`.
- Verificación: sintaxis JavaScript y PHP, compilación y lint de cinco vistas Blade, Pint y `git diff --check`; no se ejecutaron las suites.
- Pendientes: aceptación física, Testing bajo solicitud, build/publicación y etapa 2. No tocar cobros, caja, ventas, base de datos ni preferencias persistidas en esta etapa.
