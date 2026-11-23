---
entity: plan-ventas-navidad-separadas-01
type: plan
---

- Tipo: feature.
- Objetivo: sacar el dinero de las ventas Navidad de "Dinero en caja" y "Entradas de efectivo" en el corte del día, manteniéndolo visible en "Ventas segunda tienda".
- Antecedente integrado: `fix-corte-navidad-panel-01` ya creó el panel "Ventas segunda tienda" filtrando `category_name === 'navidad'` (case-insensitive) y su test; este plan amplía ese trabajo sin rehacerlo.
- Pasos:
  1. Extraer en `DailyCutService` un predicado único para la categoría Navidad (el mismo `mb_strtolower(trim(...)) === 'navidad'` ya usado en el bloque de `navidadProducts`) y un helper que sume `line_total` de los ítems Navidad de una `Sale`. Reutilizar en ambos puntos para no duplicar la regla.
  2. Calcular `$navidadCashFromSales` sobre `$cashSales` y restarlo de `$computerCashSales` en el corte global; en el corte por empleado restar el monto Navidad de las ventas que cumplen el `whereHas('cashMovements'...)` existente.
  3. Calcular `$navidadCashFromMovements` desde los `CashMovement` `in/sale` de `$cash` (vía `$movement->sale`) y restarlo de `$boxSalesMovements`, de modo que solo se excluya la parte Navidad de cada ticket.
  4. No tocar `$salesTotalCash`, `$salesTotalAll`, `$salesSubtotal`, `$salesTax`, `$profit`, `$departments`, `$navidadProducts`, `$navidadTotal`, `$refunds` ni `$cash_in_total`. La conciliación sigue en 0 porque ambos paneles se ajustan por el mismo monto.
  5. Añadir tests en `tests/Feature/PosCutTest.php`: venta 100% Navidad en efectivo (sale de caja/computadora sin ese monto, `sales.total_cash` y `totals.sales` intactos), venta mixta (solo se descuenta el ítem Navidad), venta no Navidad (sin cambios), panel izquierdo `cash_in_total` = inicial + cambio, y `reconciliation.is_reconciled` en true.
  6. Verificar con `php artisan test --compact tests/Feature/PosCutTest.php` y `vendor/bin/pint --dirty --format agent`. Sin `npm run build` (no hay cambios JS/CSS).
- Archivos afectados: `app/Services/DailyCutService.php` (único cambio funcional: helper + dos restas); `tests/Feature/PosCutTest.php` (tests nuevos). Sin cambios: `resources/views/pos/cuts/index.blade.php` (el panel izquierdo ya no incluye ventas, solo se verifica con test), `SaleService.php`, migraciones.
- Criterios de aceptación: ver `feature-ventas-navidad-separadas-01`.
- Plan de rollback: revertir únicamente `DailyCutService.php` y `PosCutTest.php`. Sin migraciones ni datos que restaurar. Los cortes ya generados con los nuevos totals no se recalculan automáticamente; si se requiere deshacer el efecto en cortes nuevos, hay que regenerarlos desde la pantalla de corte.
- Riesgos: (1) `recordCashCounted()` compara contra `cash_box_panel.subtotal`, ahora menor: si el efectivo Navidad no se aparta físicamente, el conteo marcará sobrante; (2) las devoluciones de Navidad siguen restando de caja (fuera de alcance elegido), posible asimetría; (3) prorrateo por `line_total` asume que no hay descuentos a nivel de venta (hoy no existen); (4) `build()` también alimenta `employeeBreakdown()`, dashboard, `pos/cash`, PDF/impresión y conteo: esos paneles reflejarán el nuevo número para cortes nuevos sin editarse; los snapshots históricos quedan intactos; (5) ventas `mixed` no se tocan (hoy no entran en caja) y se conserva su advertencia.
- Alternativas consideradas: (a) no generar el movimiento de caja en `SaleService` — descartada, el usuario eligió "solo en el corte"; (b) excluir el ticket completo — descartada, eligió separación por producto; (c) filtrar en la vista Blade — descartada, dejaría `totals` y `employeeBreakdown()` incoherentes.
- Bugs activos que condicionen el plan: ninguno. Los `bug-preuso-*` que tocaban `DailyCutService` están resueltos; `bug-productos-costo-vacio-01` no afecta estos archivos.
- Estado: IMPLEMENTADO.
- Cambios aplicados: en `DailyCutService.php` se extrajeron `isNavidadCategory()` y `navidadAmount()` y se añadió `navidadFromCashMovements()`; se descontó la parte Navidad de `computer_panel.cash_sales` (global y por empleado) y de `cash_box_panel.sales_movements`. En `PosCutTest.php` se añadió el helper `makeCashSaleWithItems()` y 4 pruebas ( Navidad 100%, mixto, no Navidad y panel izquierdo inicial+cambio ), con reconciliación verificada en la primera. La vista del corte no se tocó: su panel izquierdo ya excluye ventas y se cubrió con test.
- Desviaciones del plan original: (1) se combinarón los 5 escenarios de test previstos en 4 pruebas al incluir `is_reconciled` en la prueba de venta 100% Navidad, sin perder cobertura; (2) se añadió un tercer helper privado `navidadFromCashMovements()` además del predicado y del sumador previstos, para no duplicar el filtro de movimientos entre el corte global y el de empleado. Sin otras desviaciones.
- Verificación: PosCutTest 18/18, 83 aserciones; Pint limpio. Sin build.
- Aplicado en: fix-ventas-navidad-separadas-01.
- Ampliación posterior: el usuario reportó que en "Mi corte" la fila "Ventas en efectivo" seguía mostrando el dinero de Navidad y eligió separarlo desde la venta. Se amplió el alcance a `SaleService.php` (no crear movimiento de caja para Navidad) y se ajustó el doble descuento en `DailyCutService`; implementado en fix-ventas-navidad-separadas-02. Los tests originales del plan se conservan y se añadieron 2 de flujo real en PosCutReconciliationTest.
- Fecha: 2026-11-23.
