---
entity: bug-preuso-08
type: bug
---

- Auditoría: #8. Exportador heredado de caja no limita los registros al usuario autorizado.
- Estado: CAUSA RAÍZ CONFIRMADA por rutas, autorización y consulta.
- Evidencia: routes/web.php:188-194; app/Http/Controllers/Admin/CajaController.php:91-120; app/Http/Controllers/Pos/CashMovementController.php:295-308.
- Caso: empleado con pos.caja.view y pos.cash.manage, sin pos.sales.day.any, descarga movimientos de otros por /admin/caja/exportar.
- Causa confirmada: hipotesis-H8-preuso.
- Fecha: 2026-11-16. Sin implementación ni descarga de datos reales.
