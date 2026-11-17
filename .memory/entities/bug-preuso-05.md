---
entity: bug-preuso-05
type: bug
---

- Auditoría: #5. Aprobar una devolución pendiente modifica un día globalmente cerrado.
- Estado: CAUSA RAÍZ CONFIRMADA por ausencia del control en el flujo efectivo; sin ejecución integrada.
- Evidencia: app/Http/Controllers/Pos/SaleReturnController.php:62-102; app/Services/ReturnService.php:79-142.
- Caso: solicitar devolución, cerrar globalmente el día y aprobar ese mismo día produce nuevos movimientos pese al cierre.
- Causa confirmada: hipotesis-H5-preuso.
- Fecha: 2026-11-16. Sin implementación.
