---
entity: plan-corte-pdf-preview-01
type: plan
---

- Tipo: feature
- Objetivo: Agregar generación de PDF del corte (global o por empleado) con previsualización inline del PDF y descarga directa. Filtro de historial por un solo día de calendario, paginado de 8 por página, y limpieza al cambiar de jornada.
- Archivos en alcance:
  - app/Http/Controllers/Pos/DailyCutController.php (métodos pdf y history)
  - resources/views/pos/cuts/pdf.blade.php (vista optimizada para PDF)
  - resources/views/pos/cuts/history.blade.php (filtro único de fecha, botones Vista previa/PDF, paginado 8)
  - routes/web.php (ruta cuts.pdf)
  - composer.json (dependencia barryvdh/laravel-dompdf)
- Aprobado por el usuario en la conversación: 2026-11-09
- Plan de rollback: desinstalar barryvdh/laravel-dompdf, eliminar ruta cuts.pdf, eliminar vista pdf.blade.php, revertir controlador y historial. Sin migraciones.
- Estado: IMPLEMENTADO
- Cambios aplicados:
  - Instalado barryvdh/laravel-dompdf v3.1.2
  - `DailyCutController@pdf()` soporta `?preview=1` para stream inline (vista previa del PDF en el navegador) y descarga normal sin el parámetro.
  - `DailyCutController@history()` filtra por un solo `date` (no desde/hasta), paginado de 8.
  - Nueva ruta `pos.cuts.pdf` para descarga y vista previa.
  - Nueva vista `resources/views/pos/cuts/pdf.blade.php` con resumen de ventas, paneles computadora/caja, conciliación, efectivo contado, departamentos y desglose por empleado.
  - Historial: input único de fecha, botón "Vista previa" abre el PDF inline en nueva pestaña (`preview=1`), botón "PDF" descarga. Paginado de 8.
- Verificación: vistas Blade compiladas, PosCut 26/26, Pint passed, PDF Facade OK.
