---
entity: fix-corte-historial-pdf-rediseno-01
type: fix
---

- Objetivo: Historial de Cortes Globales — solo botón de descarga PDF por fila (sin vista previa ni modal), y PDF simplificado con columnas exactas y sumatoria real por empleado.
- Archivos tocados: resources/views/pos/cuts/history.blade.php, resources/views/pos/cuts/pdf.blade.php, app/Http/Controllers/Pos/DailyCutController.php
- Estado: IMPLEMENTADO
- Cambios:
  - history.blade.php: eliminados botones "Exportar PDF" y "Vista previa Reporte" del filtro; celda de acciones con ÚNICAMENTE botón "Descargar PDF" (zinc) con ruta `pos.cuts.pdf` (date, version); eliminado HTML del modal #pdfPreviewModal y sus funciones JS.
  - pdf.blade.php: plantilla simplificada al máximo — encabezado MotoZone, tabla con 5 columnas (EMPLEADO, VENTAS TOTALES, INGRESO DE DINERO, SALIDA, TOTAL EMPLEADO), fila final TOTAL DE TODOS JUNTOS con sumatoria real vía `$employees->sum(...)`. Fecha del corte usando `$cutDate` (no `$cut->date` que no existe como campo). Variables Blade normalizadas en @php.
  - DailyCutController.php (método pdf()): CORRECCIÓN CRÍTICA — cuando se solicita una versión específica (historial), forzar `$employee = null` y buscar con `DailyCut::SCOPE_GLOBAL` en lugar de permitir que un empleado sin permiso `pos.sales.day.any` busque su propio corte de empleado (SCOPE_EMPLOYEE). Esto evitaba que el PDF se generara/descargara correctamente desde el historial.
  - Sin cambios en routes/web.php (ruta `pos.cuts.pdf` ya existente y funcional).
- Nota sobre ruta: se usó `pos.cuts.pdf` (existente dentro del grupo name('pos.')) en lugar de `cuts.consolidated-pdf` porque esta última no acepta parámetro `id`.
- Verificación: Vistas Blade leídas y confirmadas; no quedan referencias huérfanas al modal; controlador actualizado para buscar corte global por versión.
