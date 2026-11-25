---
entity: fix-tickets-58mm-predeterminado-01
type: fix
---

- Estado: IMPLEMENTADO.
- Objetivo: dejar 58 mm por defecto en Tickets de venta y en esta instalación local, conservando la opción de 80 mm.
- Archivos tocados: `app/Services/TicketSettings.php`, `resources/views/pos/tickets/settings.blade.php`, `tests/Feature/TicketSettingsTest.php` y, solo local e ignorado por Git, `storage/app/private/pos/ticket-settings.json`.
- Respaldo previo: `C:/Users/Administrador/AppData/Local/Temp/opencode/motozone-tickets-58mm-20261125-120618`.
