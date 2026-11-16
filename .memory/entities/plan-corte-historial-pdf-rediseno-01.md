---
entity: plan-corte-historial-pdf-rediseno-01
type: plan
---

- Tipo: feature
- Objetivo: Historial de Cortes Globales — eliminar botones inútiles de filtros, agregar botones de Vista previa y Descarga PDF por fila con modal iframe, y rediseñar pdf.blade.php en formato Hoja Carta/A4 con resumen ejecutivo y desglose por empleado.
- Archivos en alcance:
  - resources/views/pos/cuts/history.blade.php (líneas 47-54: eliminar botones Exportar PDF/Vista previa Reporte; líneas 103-115: reemplazar celda de acciones; agregar modal #pdfPreviewModal y JS)
  - resources/views/pos/cuts/pdf.blade.php (rediseño completo Hoja Carta/A4: encabezado MotoZone, resumen ejecutivo, tabla por empleado, firmas)
  - app/Http/Controllers/Pos/DailyCutController.php (confirmar: método pdf() ya soporta ?preview=1 con stream()/download())
  - routes/web.php (confirmar: rutas cuts.pdf y cuts.consolidated-pdf ya existen)
- Aprobado por el usuario en la conversación: 2026-11-09
- Plan de rollback: restaurar versiones anteriores de history.blade.php y pdf.blade.php desde git. Sin migraciones.
- Estado: IMPLEMENTADO
- Cambios aplicados:
  - Eliminados botones "Exportar PDF" y "Vista previa Reporte" del bloque de filtros superiores (quedan solo Buscar y Limpiar).
  - Reemplazada celda de acciones de cada fila por botones modernos: "Vista previa" (abre modal iframe con ?preview=1 en ruta cuts.pdf) y "Descargar PDF" (enlace directo con descarga).
  - Agregado modal #pdfPreviewModal con iframe w-full h-[85vh] y funciones JS openPdfPreviewModal / closePdfPreviewModal.
  - Cerrado modal con tecla Escape.
  - Rediseñado pdf.blade.php: Hoja Carta/A4, encabezado "MotoZone - Reporte Consolidado de Corte Diario", tarjetas de resumen ejecutivo (Ventas Totales, Efectivo, Salidas/Gastos, En Gaveta), tabla desglosada por empleado con fila de totales, firmas de auditoría. Sin estilo ticket térmico, sin textos superfluos.
  - Controlador y rutas verificados: no requieren cambios de código (pdf() ya maneja ?preview=1).
- Desviaciones del plan original: La ruta cuts.consolidated-pdf no acepta parámetro id; se usó cuts.pdf con date, version y preview=1 para cortes individuales del historial.
- Verificación: Archivos editados y leídos correctamente; no quedan referencias huérfanas a exportConsolidatedPdf/previewConsolidatedPdf/pdf-preview-modal.
