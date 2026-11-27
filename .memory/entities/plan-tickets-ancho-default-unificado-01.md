---
entity: plan-tickets-ancho-default-unificado-01
type: plan
---

- Tipo: bug-fix.
- Objetivo: Una sola fuente del valor predeterminado de 58 mm/32 columnas para el ticket de venta. Conservar 80 mm/42 columnas como opción y no modificar el JSON de preferencias existente.
- Evidencia: `config('pos.ticket.width_mm')` declara 80; `TicketSettings::current()` superpone 58 antes del JSON. La ruta y la vista de ajustes ya consumen el ancho efectivo, y el formatter JS ya usa 32 columnas salvo cuando el ancho es 80. La vista imprimible de corte lee directamente el mismo `config('pos.ticket.width_mm')`, por lo que cambiarlo sin separar los valores también cambiaría el corte de 80 a 58.
- Paso 1: Cambiar `ticket.width_mm` a 58 en `config/pos.php` y retirar la sobreescritura redundante de `TicketSettings::current()`. La configuración guardada mantiene prioridad. No escribir el JSON ni tocar paginación, cobro, permisos, impresoras o dependencias.
- Paso 2 (requiere confirmación explícita por alcance): Conservar el ancho actual de 80 mm para el corte con una configuración separada de 80 mm en `config/pos.php`, y hacer que `resources/views/pos/cuts/print.blade.php` lea exclusivamente ese valor para el ancho. No cambiar otros estilos, controladores, datos ni comportamiento del corte.
- Paso 3: Mantener selector y vista previa alimentados por el ancho efectivo; no editar Blade de Configuración o JS si la prueba confirma que seleccionan 58 sin preferencia y respetan 80 guardado. Reutilizar el formatter existente.
- Paso 4: Extender `tests/Feature/TicketSettingsTest.php` para comprobar la ausencia de JSON en disco falso, valor base 58, selector y vista previa de 58, ruta embebida con 58 y máximo de 32 columnas ejecutando el formatter con su payload real. Conservar cobertura de 80 guardado y su correspondencia con 42 columnas. Verificar el hash del JSON real antes/después sin publicar su contenido.
- Verificación explícita: Ejecutar `node --test` completo y PHP de tickets/configuración en SQLite de memoria sin caché; informar resultados reales. No regenerar assets si no se modifican JS ni estilos de Vite; reportar qué archivos añadir al despliegue del iframe y advertir que el servidor debe refrescar la caché de configuración si la usa.
- Rollback: Restituir archivos de configuración, servicio, vista de corte acotada y pruebas; no hay migraciones ni datos que revertir.
- Estado: Pendiente de confirmar la excepción mínima para aislar el ancho de cortes; no se modificó la aplicación.
- Estado: IMPLEMENTADO
- Cambios aplicados: Default único `pos.ticket.width_mm=58`; las preferencias persistidas prevalecen. Ancho separado de corte `pos.cut.width_mm=80`. La prueba mide texto embebido sin preferencias a 32 columnas y texto con 80 guardado a 42, y valida ancho de corte de 80.
- Desviaciones del plan original: Ninguna. Se comprobó el selector y vista previa sin modificarlos. JSON real intacto y sin build nuevo.
