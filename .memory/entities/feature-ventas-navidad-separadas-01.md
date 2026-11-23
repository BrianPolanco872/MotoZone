---
entity: feature-ventas-navidad-separadas-01
type: feature
---

- Objetivo: que el dinero de las ventas de productos de categoría Navidad (navidad/Navidad, sin importar mayúsculas) aparezca en el panel "Ventas segunda tienda" del corte del día y deje de sumarse a "Dinero en caja" y a "Entradas de efectivo".
- Alcance: separación por producto dentro de `DailyCutService::build()` (prorrateo por `line_total` de los ítems Navidad de cada venta en efectivo); ajuste de `computer_panel.cash_sales` (modo global y por empleado) y de `cash_box_panel.sales_movements`; verificación de que `cash_in_total` sigue siendo solo fondo inicial + cambio; panel "Ventas segunda tienda" existente se conserva igual.
- Fuera de alcance: Ventas totales, Pagos de contado, Ganancia, devoluciones, ventas mixtas (`mixed`), stock, tickets, snapshots históricos, `SaleService`, vistas de impresión/PDF/historial (cambio indirecto por compartir `build()`), generación de cortes y migraciones.
- Criterios de aceptación: (1) panel "Ventas segunda tienda" sigue mostrando los productos y el total Navidad; (2) venta 100% Navidad en efectivo no suma a `cash_box_panel.sales_movements` ni a `computer_panel.cash_sales`; (3) venta mixta se descuenta solo por el `line_total` del ítem Navidad; (4) `cash_in_total` del panel izquierdo sigue siendo inicial + cambio sin ventas; (5) `sales.total_cash`, `totals.sales` y `totals.profit` siguen incluyendo Navidad; (6) `reconciliation.difference` sigue en 0.
- Solicitada por: usuario.
- Decisiones del usuario (question): separar por producto; aplicar solo en el corte (`build()`), no desde la venta; "entradas de efectivo" = panel izquierdo del corte; los otros totales siguen incluyendo Navidad.
- Antecedente: `fix-corte-navidad-panel-01` implementó el panel "Ventas segunda tienda" el 2026-11-22 y dejó explícito que no tocó caja/entradas.
- Estado: IMPLEMENTADO.
- Implementada en: fix-ventas-navidad-separadas-01 y fix-ventas-navidad-separadas-02 (separación desde la venta, por reporte del usuario).
- Fecha: 2026-11-23.
