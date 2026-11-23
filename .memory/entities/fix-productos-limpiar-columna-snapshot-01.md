---
entity: fix-productos-limpiar-columna-snapshot-01
type: fix
---

- Objetivo: reparar el error `SQLSTATE[42S22] Unknown column 'product_snapshot' in 'where clause'` al pulsar «Limpiar inventario».
- Causa: la migración `2026_11_20_114507_preserve_stock_movement_history_when_products_are_deleted` seguía en estado Pending en la base real (`motozone`, MySQL 127.0.0.1:3306), aunque el código de clearInventory ya esperaba la columna.
- Cambios aplicados: ningún cambio de código. Se ejecutó `php artisan migrate --force` y la migración quedó aplicada (606 ms).
- Verificación: `SHOW CREATE TABLE stock_movements` confirma `product_snapshot` json nullable y FK `product_id` SET NULL; `migrate:status` ya no la marca como pendiente. Recuento previo: 3 movimientos y 5369 productos. Todas las FK hacia products son SET NULL o CASCADE, sin RESTRICT.
- Archivos tocados: ninguno del repositorio (solo esquema de la base de datos).
- Fecha: 2026-11-22.
- Estado: RESUELTO EN ESQUEMA. No se ejecutó la limpieza real para no borrar los 5369 productos.
