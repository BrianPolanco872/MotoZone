---
entity: bug-corte-reabrir-dia-01
type: bug
---

- Síntoma: Empleado cierra su día y al intentar vender ve "Este día ya está cerrado. No se pueden registrar ventas." sin opción de reabrir.
- Causa raíz: DailyCut::isDayClosed() solo verifica corte GLOBAL cerrado; no distingue si el empleado individual cerró su propio corte, y no existe mecanismo para reabrirlo.
- Archivos implicados: app/Models/DailyCut.php, app/Http/Controllers/Pos/SaleController.php, app/Http/Controllers/Pos/DailyCutController.php, routes/web.php, vista POS (por identificar).
- Estado: CONFIRMADA CAUSA RAÍZ
- Última actualización: 07/11/2026
