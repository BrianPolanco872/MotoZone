---
entity: fix-tickets-leyenda-qr-01
type: fix
---

- Objetivo: eliminar la leyenda automática «Facturación / Soporte», manteniendo el QR configurado.
- Archivos tocados: `resources/views/pos/tickets/receipt.blade.php`, `resources/views/pos/tickets/settings.blade.php` y `tests/Feature/TicketSettingsTest.php`.
- Estado: IMPLEMENTADO EN FUENTE LOCAL.
- Respaldo: `C:/Users/Administrador/AppData/Local/Temp/opencode/motozone-leyenda-qr-20261125-190536`.
- Verificación: Pint, compilación Blade, sintaxis PHP y `git diff --check` correctos. Se amplió la cobertura sin ejecutar Testing. Sin build ni publicación; no se comprobó la impresión física.
