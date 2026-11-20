---
entity: bug-tickets-ancho-impresion-01
type: bug
---

- Fecha: 2026-11-20.
- Origen: diagnóstico de solo lectura documentado previamente en esta conversación; fotos del ticket físico y captura de la vista previa de Chrome aportadas por el usuario.
- Síntoma: el comprobante se distribuye en una hoja grande en la vista previa, con texto muy pequeño y columnas muy separadas; el papel fotografiado pierde la distribución deseada.
- Estado: CAUSA RAÍZ CONFIRMADA para los defectos de dimensionamiento del código local; no se ha verificado que la versión publicada sea idéntica ni que expliquen por sí solos toda la salida física.
- Causa: hipotesis-H1-tickets-ancho-impresion-01.
- Archivos: resources/views/pos/tickets/ticket.blade.php:74-78; resources/js/pos/pos.js:177-186; resources/views/pos/index.blade.php:699-705; resources/js/ticket-settings.js:137-147; resources/views/pos/tickets/settings.blade.php:23-29.
- Objetivo del usuario: conservar la información actual y obtener la distribución de su foto de referencia: encabezado centrado, columnas separadas e importes alineados, sin extender el ticket por toda una hoja.
- Pendiente independiente: el fragmento «s y devoluciones» al inicio del papel no tiene causa demostrada. La plantilla receipt.blade.php:60-63 sitúa la política al final y la captura de Chrome también. No atribuirlo automáticamente al CSS ni a un controlador específico.
- Alcance excluido: bloqueo actual de eliminación de inventario en producción; tiene antecedentes en plan-productos-limpiar-tabla-01 pero no diagnóstico actualizado del caso actual.
- Estado actualizado 2026-11-20: CORREGIDO EN FUENTE LOCAL mediante fix-tickets-ancho-impresion-01. Pendiente Testing, aceptación visual/física, build por discrepancia de tag y publicación. No declarar resuelta la salida del servidor/impresora por los chequeos de sintaxis.
