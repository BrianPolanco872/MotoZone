---
entity: fix-corte-reporte-consolidado-01
type: fix
---

- Objetivo: Generar reporte consolidado en PDF de cortes diarios de caja.
- Archivos tocados: app/Http/Controllers/Pos/DailyCutController.php, resources/views/pos/cuts/consolidated.blade.php (nuevo), resources/views/pos/cuts/history.blade.php, routes/web.php
- Estado: IMPLEMENTADO
- Cambios:
  - `consolidatedPdf()` construye los datos con `DailyCutService::employeeBreakdown()` y renderiza `pos.cuts.consolidated` vía dompdf.
  - Vista PDF rediseñada completamente: hoja CARTA vertical, encabezado profesional (MotoZone, título, fecha), resumen ejecutivo (ventas, efectivo, electrónico, salidas, en gaveta, estado conciliación), tabla desglosada por empleado con fila de totales, sin estilo de ticket térmico, sin "Gracias por su compra".
  - Historial: botones "Exportar PDF" y "Vista previa Reporte" junto al filtro de fecha.
  - Modal de vista previa: iframe con width 100% y height 80vh para que el documento se vea completo y legible.
- Verificación: PosCut 26/26, Pint passed, Blade cache OK.
