---
entity: fix-corte-pdf-preview-01
type: fix
---

- Objetivo: Generar PDF de cortes con previsualización previa y filtro único de día.
- Archivos tocados: app/Http/Controllers/Pos/DailyCutController.php, resources/views/pos/cuts/pdf.blade.php (nuevo), resources/views/pos/cuts/history.blade.php, routes/web.php, composer.json
- Estado: IMPLEMENTADO
- Cambios:
  - `DailyCutController@pdf()` genera PDF con dompdf; `?preview=1` usa `stream()` (inline en navegador), sin él usa `download()`.
  - Nueva ruta `pos.cuts.pdf`.
  - Nueva vista `resources/views/pos/cuts/pdf.blade.php` con resumen de ventas, paneles, conciliación, efectivo contado, departamentos y desglose por empleado.
  - Historial: un solo input de fecha para buscar cortes de un día; paginado de 8; botones Vista previa (PDF inline) y PDF (descarga).
- Verificación: PosCut 26/26, Pint passed, Blade cache OK, PDF Facade OK.
