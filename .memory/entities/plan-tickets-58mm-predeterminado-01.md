---
entity: plan-tickets-58mm-predeterminado-01
type: plan
---

- Tipo: feature.
- Estado: IMPLEMENTADO.
- Aprobación: el usuario respondió «sí» a dejar 58 mm como valor inicial de Tickets y como ancho guardado local, manteniendo 80 mm seleccionable, sin cambiar cortes ni Windows y con el rollback descrito.
- Objetivo: dejar 58 mm como valor inicial del módulo Tickets de venta y como ancho guardado en esta instalación local. Mantener 80 mm como opción manual.
- Hallazgo: `TicketSettings::current()` superpone `ticket-settings.json` sobre la configuración; esta instalación tiene 80 mm guardados. `DailyCutController` usa `config('pos')` directamente, por lo que cambiar el ancho compartido de `config/pos.php` afectaría el formato térmico del corte.
- Implementación: aplicar el valor inicial de 58 mm en `app/Services/TicketSettings.php` al servir tickets de venta, respetando una selección guardada. Cambiar solo `ticket.width_mm` de 80 a 58 en la preferencia local tras respaldarla. Hacer que el indicador inicial del ejemplo en `resources/views/pos/tickets/settings.blade.php` muestre el ancho efectivo en vez de un 80 fijo; añadir cobertura acotada a `tests/Feature/TicketSettingsTest.php` para valor predeterminado y opción 80 guardada, sin ejecutar Testing sin solicitud explícita.
- Fuera de alcance: cambiar el formato de cortes, tocar ajustes de otros entornos, impresora predeterminada, controlador de Windows o opciones del panel del navegador; no modificar otros campos de Tickets, datos de ventas ni permisos.
- Aceptación: Tickets abre con 58 mm en local y en instalaciones sin preferencia; una elección explícita posterior de 80 mm funciona. Comprobante y ejemplo usan el ancho guardado. La 5Sprinter y el papel del diálogo se eligen por separado en el navegador/controlador.
- Plan de rollback: restaurar el código cambiado y devolver solo el valor previo de ancho en la preferencia local, sin tocar el resto de los campos.
- Cambios aplicados: Tickets inicia con 58 mm sin preferencia guardada; el ejemplo y el selector reflejan el ancho efectivo; se actualizó exclusivamente `ticket.width_mm` a 58 en la preferencia local. Cobertura de 58 inicial y 80 guardado añadida.
- Desviaciones del plan original: ninguna. Pasaron Pint, sintaxis, compilación Blade, comparación con el respaldo local y `git diff --check`; no se ejecutó Testing.
