---
entity: fix-feat-corte-dia-01
type: fix
---

- Objetivo: Implementar el plan plan-feat-corte-dia-01 (feature): POS operativo para empleados (búsqueda, carrito, cobro, tickets, caja, devoluciones) y módulo «Corte del día» global con desglose por empleado, listo para hosting con dominio.
- Fecha: 2026-11-02.
- Tipo: feature (extiende plan-feat-pos-01 fases 3-6).
- Migraciones aplicadas en MySQL: add_cost_and_inventory_fields_to_products_table, create_sales_tables, create_cash_movements_table, create_sale_returns_tables, create_daily_cuts_table.
- Seeder ejecutado: RolePermissionSeeder (incluye pos.cut.manage).
- Modelos: Product (cost, wholesale_price, min_stock, track_inventory, margen, stock), Sale, SaleItem, StockMovement, CashMovement, SaleReturn, SaleReturnItem, DailyCut.
- Servicios: PricingService, FolioGenerator, SaleService, CashMovementService, ReturnService, DailyCutService.
- Controladores: PosController, Pos\SaleController, Pos\CashMovementController, Pos\SaleReturnController, Pos\DailyCutController, Admin\ProductController.
- FormRequests: StoreSaleRequest, ChargePendingSaleRequest, StoreCashMovementRequest, StoreSaleReturnRequest, GenerateDailyCutRequest, ProductRequest.
- Rutas: 23 rutas pos.* registradas en routes/web.php con middleware de permisos específicos.
- Vistas creadas/actualizadas: pos/index, pos/sales/pending, pos/sales/day, pos/sales/previous, pos/tickets/ticket, pos/cash/index, pos/returns/index, pos/returns/create, pos/cuts/index, pos/cuts/print, pos/cuts/history, pos/cuts/partials/summary, admin/products/partials/form, admin/products/index, layouts/admin (navegación Caja, Corte del día, Mi corte).
- JS: resources/js/pos/pos.js, resources/js/admin/product-form.js, importados en resources/js/app.js.
- Permisos: PERMISSION_CATALOG agrupado en Ventas, Caja y corte, Tickets y consultas, Devoluciones, Productos.
- Pruebas nuevas: PosSalesTest, PosCashTest, PosReturnsTest, PosCutTest, PosProductCostTest; PosAccessTest actualizado.
- Verificación: 63 tests / 247 assertions pasando; vendor/bin/pint --dirty --format agent aplicado; npm run build correcto.
- Archivos clave: app/Services/SaleService.php, app/Services/DailyCutService.php, app/Http/Controllers/Pos/SaleController.php, app/Http/Controllers/Pos/DailyCutController.php, resources/views/pos/index.blade.php, resources/js/pos/pos.js, routes/web.php.
- Desviaciones del plan original: se agregó prueba unitaria de producto con costo; las vistas de corte incluyen partial compartido de secciones; el seeder se ejecutó en MySQL junto con migraciones.
- Estado: IMPLEMENTADO.
