---
entity: fix-ventas-navidad-separadas-02
type: fix
---

- Objetivo: que el dinero de Navidad no se registre nunca como entrada de caja, ni siquiera en el flujo real de venta, porque el/user reportó que la fila "Ventas en efectivo" de "Mi corte" seguía mostrándolo tras el fix anterior.
- Archivos tocados: app/Services/SaleService.php; app/Services/DailyCutService.php; tests/Feature/PosCutReconciliationTest.php; tests/Feature/PosCutTest.php.
- Cambios aplicados: SaleService ya no crea el CashMovement 'sale' para ventas 100% Navidad y en ventas mixtas crea el movimiento solo por la parte no Navidad (calculada desde `category_name` y `line_total` de los items preparados). DailyCutService mantiene el desajuste del panel de computadora y ajustó `navidadFromCashMovements()` para descontar solo lo que aún queda dentro del movimiento, evitando el doble descuento en movimientos nuevos; el corte por empleado alinea la resta con las ventas que sí tienen movimiento.
- Cambio de decisión: el usuario initially eligió "solo en el corte", pero al reportar el bug eligió "separar desde la venta". Esto amplía el alcance del plan original; se conserva el fix anterior como defensa para ventas históricas.
- Verificación: PosCutReconciliationTest + PosCutTest 45/45, 198 aserciones; suite amplia 247/252 con 5 errores preexistentes ajenos (audit_logs.deleted_at en tests de reimportación CSV); Pint limpio. Sin build.
- Fecha: 2026-11-23.
- Estado: IMPLEMENTADO.
