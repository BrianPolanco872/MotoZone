---
entity: bug-preuso-09
type: bug
---

- Auditoría: #9. PDF histórico usa datos actuales e importes distintos a su snapshot.
- Estado: CAUSA RAÍZ CONFIRMADA por controlador y plantilla; no se generaron PDFs.
- Evidencia: app/Http/Controllers/Pos/DailyCutController.php:413-425; resources/views/pos/cuts/pdf.blade.php:36-84.
- Caso: guardar corte v1, cambiar actividad y descargar v1; la tabla se reconstruye con employeeBreakdown actual y suma ventas totales + entradas - salidas.
- Causa confirmada: hipotesis-H9-preuso.
- Fecha: 2026-11-16. Sin implementación; no se asume recuperable el desglose histórico que nunca fue persistido.
