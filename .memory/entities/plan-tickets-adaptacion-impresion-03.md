---
entity: plan-tickets-adaptacion-impresion-03
type: plan
---

- Tipo: bug-fix; propuesto, no aprobado ni aplicado.
- Objetivo: mantener el mismo diseño útil de los tickets en pantalla e impresión, con perfil térmico de 58 mm por defecto, 80 mm seleccionable y salida legible en papel Carta/A4, sin prometer detección de impresora ni impresión física universal.
- Causa y evidencia: `hipotesis-H2-tickets-ancho-impresion-01` distingue ancho del contenido y del medio de impresión. La hoja Carta del destino mostrado es esperable, no un motivo para restaurar tamaños CSS inválidos, agrandar el recibo o forzar una altura fija.
- Aclaración del usuario: la captura con PDF era local. En la caja, Chrome sí reconoce y selecciona la 5SPRINTER, pero la vista previa usa orientación Horizontal y una hoja amplia. Primero verificar «Tamaño de papel» y «Escala» en esa cola; no trasladar el diagnóstico de Carta del PDF a la 5SPRINTER ni asumir que cambiar a Vertical corrige por sí solo el tamaño.

## Pasos

1. Ajustar `resources/views/pos/tickets/receipt-styles.blade.php` para usar el mismo ancho útil nominal en pantalla e impresión, limitado por el área disponible. Conservar 58/80 mm como anchos del papel, márgenes prudentes y `@page size: auto`; no estirar el recibo a toda Carta/A4. Mantener cantidades e importes completos, descripciones que envuelven, pie al final y posibilidad de paginar tickets largos.
2. En `resources/views/pos/tickets/settings.blade.php`, distinguir «ancho del ticket» de «tamaño de papel elegido en el diálogo». Aclarar que Microsoft Print to PDF puede guardar una hoja Carta con el ticket dentro; para papel, elegir la cola física y su perfil de rollo. No cambiar valores guardados, elegir impresora desde la web ni reescribir el flujo ya compartido por el ejemplo y los tickets de venta. `tests/Feature/TicketSettingsTest.php`: actualizar la cobertura de ambos anchos y de la coincidencia de formato.
3. Con Testing explícito, validar el mismo comprobante por tres vías: Microsoft Print to PDF con Carta para comprobar que no se estira, 5SPRINTER con el ancho real y su perfil térmico soportado, y 80 mm en una cola compatible. En caja, abrir «Más opciones»: comparar con escala 100 % cuando quepa, vertical, sin encabezados/pies y con márgenes del controlador; medir el rollo por su ancho. Validar ejemplo y venta real, nombres largos, importes y más de una página cuando corresponda. Si el PDF está bien pero la vista previa de la 5SPRINTER sale mal, revisar el perfil/escala que negocian navegador y controlador. Si ambas vistas previas están bien y solo falla el papel, revisar controlador, modo gráfico o modo de solo texto y área imprimible antes de tocar CSS. La compatibilidad física queda limitada a los equipos probados.

## Límites y reversión

- Aceptación: 58 mm sin preferencias, 80 mm si se eligió; misma distribución para pantalla y papel cuando el área disponible la admite; salida compacta y legible en Carta/A4 y rollos compatibles; sin cambiar ventas, preferencias existentes, caja ni permisos. No se promete una hoja térmica en Microsoft Print to PDF de Carta.
- Alternativa fuera de este plan: exportación de PDF con página de ancho térmico y altura calculada si se necesita un archivo de ese tamaño; tampoco sustituye el perfil del controlador. No añadir automatización nativa ni dependencias.
- Rollback: respaldar y restaurar solo los estilos/vistas y actualización de pruebas; revertir por separado cualquier perfil de impresora autorizado que se llegue a tocar.
- Estado de esta revisión: `v1.0.1` coincide con HEAD (`0f06bdc`), el manifest local referencia un JS presente. Sin nuevo build, publicación ni pruebas de Chrome o impresora.
