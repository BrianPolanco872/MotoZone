---
entity: plan-corte-reporte-consolidado-01
type: plan
---

- Tipo: feature
- Objetivo: Implementar reporte consolidado en PDF de cortes diarios de caja con tarjetas de resumen general y tabla desglosada por empleado/cajero, incluyendo botón "Exportar PDF" y "Vista previa Reporte" en el frontend del historial de cortes.
- Archivos en alcance:
  - app/Http/Controllers/Pos/DailyCutController.php (método consolidatedPdf)
  - resources/views/pos/cuts/consolidated.blade.php (nueva vista PDF)
  - resources/views/pos/cuts/history.blade.php (botones Exportar PDF / Vista previa Reporte + scripts JS)
  - routes/web.php (ruta cuts.consolidated-pdf)
- Aprobado por el usuario en la conversación: 2026-11-09
- Plan de rollback: eliminar el método consolidatedPdf, la ruta, la vista consolidated.blade.php y los botones/scripts del historial. Sin migraciones.
- Estado: IMPLEMENTADO
- Cambios aplicados:
  - `DailyCutController@consolidatedPdf()` genera PDF con dompdf usando `employeeBreakdown()` para los datos por empleado y totales consolidados.
  - Vista `pos.cuts.consolidated` con encabezado, tarjetas de resumen (tickets, ventas, efectivo, electrónico, devoluciones, salidas/gastos, en gaveta, neto) y tabla por empleado (nombre, correo, estado turno/presencia, tickets, ventas, efectivo, electrónico, devoluciones, salidas, en gaveta, estado del corte con hora).
  - Ruta `pos.cuts.consolidated-pdf`.
  - Historial: botón "Exportar PDF" (descarga directa) y "Vista previa Reporte" (abre modal con iframe), ambos usan la fecha seleccionada en el filtro (o hoy si está vacío).
- Verificación: vistas Blade compiladas, PosCut 26/26, Pint passed.
