---
entity: fix-tickets-ancho-default-unificado-01
type: fix
---

- Objetivo: Unificar el ancho predeterminado de tickets de venta en 58 mm/32 columnas sin alterar preferencias guardadas ni el corte de 80 mm.
- Archivos tocados: `config/pos.php`, `app/Services/TicketSettings.php`, `resources/views/pos/cuts/print.blade.php`, `tests/Feature/TicketSettingsTest.php`.
- Estado: IMPLEMENTADO.
- Verificación: JS completo 79 pasaron, 0 fallaron; PHP dirigido a tickets, configuración y corte 129 pasaron, 0 fallaron, 791 aserciones. Pint, sintaxis PHP y diff correctos. Se comprobó que el SHA-256 de `storage/app/private/pos/ticket-settings.json` no cambió.
- Despliegue: Los tres archivos de producción listados; sin migraciones ni assets nuevos. Refrescar la caché de configuración en el servidor si se usa. Prueba física no realizada.
