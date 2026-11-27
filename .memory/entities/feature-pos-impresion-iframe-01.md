---
entity: feature-pos-impresion-iframe-01
type: feature
---

- Objetivo: Imprimir tickets de venta del POS y el borrador de configuración desde un iframe temporal, sin abrir una pestaña nueva ni salir del carrito.
- Alcance: Cobro confirmado, Reimprimir último ticket, copia desde Ventas del día y botones Ticket de listados de ventas; Probar impresión en Configuración. Conservar la salida de los modos Texto y Gráfico.
- Fuera de alcance: Impresión silenciosa, controlador o tamaños físicos, cortes, cambios de inventario o permisos y enlaces de solo consulta como «Ver ticket original» en devoluciones.
- Criterio de aceptación: Un solo diálogo por acción, sin `print=1` en el iframe, no imprimir páginas de error, eliminar el iframe al terminar o por tiempo de seguridad, y conservar la pestaña y estado del POS.
- Estado: IMPLEMENTADO
- Implementada en: fix-pos-impresion-iframe-01
