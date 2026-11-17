---
entity: bug-preuso-06
type: bug
---

- Auditoría: #6. Rechazar puede sobrescribir una devolución aprobada simultáneamente.
- Estado: CAUSA RAÍZ CONFIRMADA por secuencia causal del código; carrera no ejecutada.
- Evidencia: app/Services/ReturnService.php:79-89,148-158.
- Caso: dos peticiones cargan pending; una aprueba y mueve caja/stock; la otra rechaza usando su modelo obsoleto y conserva esos movimientos.
- Causa confirmada: hipotesis-H6-preuso.
- Fecha: 2026-11-16. Sin implementación.
