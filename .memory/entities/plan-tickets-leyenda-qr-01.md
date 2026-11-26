---
entity: plan-tickets-leyenda-qr-01
type: plan
---

- Tipo: bug-fix.
- Aprobación explícita: «aplica el plan luego yo veo si emite graficos o no» (2026-11-25).
- Objetivo: quitar la leyenda automática «Facturación / Soporte» y conservar el QR que el usuario cargó.
- Causa confirmada: `hipotesis-H1-tickets-leyenda-qr-01`.
- Pasos: quitar solo el renglón visible en `resources/views/pos/tickets/receipt.blade.php` (venta real y HTML del comprobante) y `resources/views/pos/tickets/settings.blade.php` (ejemplo), con texto alternativo neutro y accesible. Conservar imagen, destino, casilla, carga, fallos opcionales, ancho y demás preferencias. Añadir cobertura focal de presencia de la imagen y ausencia de la leyenda en `tests/Feature/TicketSettingsTest.php`.
- Diagnóstico físico pendiente, no es un fix aprobado: con autorización de Testing, guardar como PDF el mismo recibo de ejemplo, verificar que el QR esté en el PDF e imprimir ese PDF en la misma 5SPRINTER con el mismo perfil térmico y escala 100 %. Si el PDF impreso tiene QR y la impresión directa no, comparar la configuración y el envío desde Chrome. Si el PDF lleva el QR pero tampoco lo imprime, verificar el controlador del modelo exacto, que no esté en modo Genérico/Solo texto y que la cola imprima gráficos. Si el QR falta ya en el PDF, comparar carga y representación del PNG en el documento de impresión. No cambiar el HTML del QR ni atribuir una causa sin esa comprobación.
- Aceptación: ni el borrador ni los tickets reales muestran la leyenda; el QR configurado se conserva y no aparece con la casilla desactivada. No declarar resuelta la impresión física hasta que una prueba en la caja muestre un QR legible y escaneable.
- Reversión: respaldar y restaurar únicamente las líneas afectadas de las vistas y pruebas. No modificar ventas, preferencias guardadas, impresora ni publicación.

- Estado: IMPLEMENTADO.
- Cambios aplicados: se retiró la leyenda visible del ejemplo y de la venta real; ambas imágenes conservan su comportamiento con texto alternativo neutro. Cobertura focal actualizada para QR habilitado y deshabilitado.
- Desviaciones del plan original: ninguna. La comprobación física queda a cargo del usuario según su solicitud; no se ejecutó Testing.
