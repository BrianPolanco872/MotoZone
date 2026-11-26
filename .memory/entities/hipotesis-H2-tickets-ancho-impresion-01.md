---
entity: hipotesis-H2-tickets-ancho-impresion-01
type: hipotesis
---

- Estado: CONFIRMADA solo para la página grande vista en el destino PDF local y la diferencia nominal de ancho entre pantalla e impresión. No confirma la causa de defectos en papel de la 5SPRINTER.
- Evidencia: en las capturas de `localhost:8000/pos/tickets`, el borrador indica 58 mm y el diálogo tiene Microsoft Print to PDF seleccionado. La cola local declara Carta predeterminada (215.9 × 279.4 mm); sus tamaños enumerados no incluyen perfiles de 58 ni 80 mm. La 5SPRINTER no figura en este equipo.
- Mecanismo: `resources/js/ticket-settings.js:137-141` abre el diálogo con `window.print()`. `resources/views/pos/tickets/receipt-styles.blade.php:3-6,36-38` deja el tamaño de la página en `auto` y limita únicamente la caja del recibo. Con 58 mm, el borrador tiene 50 mm de contenido nominal (58−2×4) y la impresión 48 mm (58−10); con 80 mm, 72 y 70 mm. La diferencia de 2 mm puede cambiar el salto de líneas, no explica una hoja Carta ni la deformación física.
- La imagen PDF conserva columnas y pie. La captura de caja confirma un caso distinto: destino 5SPRINTER seleccionado, diseño Horizontal y recibo pequeño dentro de una hoja amplia. «Más opciones de configuración» está cerrado, por lo que no se conoce el papel efectivo ni la escala; no se puede afirmar que la cola 5SPRINTER use Carta. Para atribuir el defecto físico falta esa configuración y la salida en papel del mismo comprobante. No confundirlo con el caso del PDF o de los botones.
