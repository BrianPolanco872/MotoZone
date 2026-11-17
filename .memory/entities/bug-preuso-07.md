---
entity: bug-preuso-07
type: bug
---

- Auditoría: #7. El guard de corte cerrado no protege movimientos manuales.
- Estado: CAUSA RAÍZ CONFIRMADA por lectura del guard y sus callers.
- Evidencia: app/Models/CashMovement.php:134-157; app/Services/CashMovementService.php:211-225; app/Http/Controllers/Admin/CajaController.php:45-59.
- Caso: restaurar un gasto archivado al cerrar y eliminarlo no reconoce su pertenencia al día cerrado si no tiene sale_id.
- Causa confirmada: hipotesis-H7-preuso.
- Fecha: 2026-11-16. Sin implementación; no se borraron datos para probar.
