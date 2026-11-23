---
entity: fix-ventas-navidad-separadas-01
type: fix
---

- Objetivo: separar por producto el dinero de las ventas de categoría Navidad en el corte del día, de modo que aparezca en "Ventas segunda tienda" y deje de sumarse a "Dinero en caja" ni a "Entradas de efectivo", sin tocar los demás totales.
- Archivos tocados: app/Services/DailyCutService.php; tests/Feature/PosCutTest.php.
- Cambios aplicados: extraídos `isNavidadCategory()` (única regla case-insensitive, reutilizada por el panel existente) y `navidadAmount()` (suma de `line_total` de ítems Navidad por venta). `computer_panel.cash_sales` descuenta ese importe: en corte global desde las ventas cash del día, en corte por empleado desde los movimientos 'sale' de ese empleado. `cash_box_panel.sales_movements` descuenta la parte Navidad calculada desde los propios movimientos 'in/sale' ya filtrados por venta pagada en efectivo. No se tocaron `sales.total_cash`, `totals.sales`, ganancia, devoluciones ni `cash_in_total`, por lo que la conciliación sigue en 0.
- Verificación: `php artisan test --compact tests/Feature/PosCutTest.php` 18/18, 83 aserciones; `vendor/bin/pint --dirty --format agent` limpio. Sin build: solo PHP y Blade sin cambios de JS/CSS (la vista del corte no se modificó).
- Fecha: 2026-11-23.
- Estado: IMPLEMENTADO.
