---
entity: feature-tickets-texto-termico-01
type: feature
---

- Objetivo: Imprimir los tickets de venta de MotoZone como texto ASCII monoespaciado y mostrar exactamente ese formato en la vista previa de impresión.
- Alcance: Ticket y reimpresión, prueba y vista previa del módulo, papel de 58 mm (32 columnas) y 80 mm (42 columnas), datos históricos de venta, cinco preferencias de texto y selector persistente «Modo de impresión»: Texto predeterminado o Gráfico anterior.
- Fuera de alcance: Rediseño del ticket gráfico, cambios de permisos, datos de venta, inventario, impuestos, pagos, cortes, dependencias y automatización de Windows/ESC/POS.
- Criterios de aceptación: En Texto, una sola salida `<pre>` con ASCII imprimible, sin imágenes, con cantidades e importes completos y mismo formateador para venta, vista previa y prueba; isotipo y QR siguen visibles en pantalla. En Gráfico, la impresión, vista previa y prueba conservan el comportamiento anterior, incluidos isotipo y QR según las preferencias. Las cinco preferencias y el modo se guardan sin migraciones. Pruebas automáticas obligatorias, incluido snapshot exacto de V-000050 a 32 columnas. Validación física posterior, no disponible ahora.
- Solicitada por: usuario.
- Estado: Aprobada con interruptor y pruebas; pendiente de implementación.
- Estado: IMPLEMENTADO
- Implementada en: fix-tickets-texto-termico-01
