---
entity: fix-cierre-dia-fase6
type: fix
---

- Objetivo: Implementar cierre de día sin borrado para la fase 6 (plan-fase-6). Una vez hecho el corte final de una fecha, ese día queda cerrado y no acepta más ventas, devoluciones ni movimientos de caja. Al día siguiente empiezan contadores desde cero. Los datos de días cerrados permanecen en BD para auditoría y reportes.
- Archivos tocados: database/migrations/2026_11_04_030000_add_closed_at_to_daily_cuts.php, app/Models/DailyCut.php, app/Http/Controllers/Pos/SaleController.php, app/Http/Controllers/Pos/SaleReturnController.php, app/Http/Controllers/Pos/CashMovementController.php, app/Http/Controllers/Pos/DailyCutController.php, app/Http/Controllers/PosController.php
- Estado: IMPLEMENTADO
