---
entity: fix-productos-modalidades-venta-01
type: fix
---

- Objetivo: implementar en fuente la elección persistida de venta por pieza, granel a tres decimales y kits completos con composición, consumo opcional de componentes y compatibilidad de inventario/POS/pendientes/devoluciones/historial, según plan-productos-modalidades-venta-01 aprobado en esta conversación.
- Estado: IMPLEMENTADO EN FUENTE Y APLICADO EN LA BD MySQL LOCAL; aceptación funcional del usuario pendiente.
- Fecha: 2026-11-21.

## Archivos tocados

- Soporte: app/Support/Quantity.php (nuevo); app/Casts/QuantityCast.php (nuevo).
- Productos/modelos: app/Models/Product.php; app/Models/SaleItem.php; app/Models/StockMovement.php; app/Models/SaleReturnItem.php; database/factories/ProductFactory.php.
- Persistencia nueva: database/migrations/2026_11_21_155900_add_product_sale_modes_and_kit_items.php; database/migrations/2026_11_21_155922_support_fractional_inventory_and_sale_snapshots.php.
- Requests: app/Http/Requests/Admin/ProductRequest.php; app/Http/Requests/Pos/StoreSaleRequest.php; app/Http/Requests/Pos/StoreSaleReturnRequest.php.
- Controladores/rutas: app/Http/Controllers/Admin/ProductController.php; app/Http/Controllers/Admin/DashboardController.php; app/Http/Controllers/PosController.php; app/Http/Controllers/Pos/SaleController.php; app/Http/Controllers/CatalogController.php; app/Http/Controllers/HomeController.php; routes/web.php.
- Servicios: app/Services/SaleService.php; app/Services/ReturnService.php; app/Services/PricingService.php; app/Services/DailyCutService.php.
- JS: resources/js/admin/product-form.js; resources/js/pos/pos.js.
- Vistas admin: resources/views/admin/products/partials/form.blade.php; resources/views/admin/products/index.blade.php; resources/views/admin/dashboard.blade.php.
- Vistas POS: resources/views/pos/index.blade.php; resources/views/pos/tickets/receipt.blade.php; resources/views/pos/returns/create.blade.php; resources/views/pos/returns/index.blade.php; resources/views/pos/sales/day.blade.php; resources/views/pos/sales/pending.blade.php; resources/views/pos/sales/previous.blade.php; resources/views/pos/cuts/index.blade.php; resources/views/pos/cuts/print.blade.php.
- Vistas compartidas/públicas: resources/views/components/stock-badge.blade.php; resources/views/catalog/show.blade.php.
- Pruebas PHP: tests/Feature/AdminProductTest.php; tests/Feature/AdminTest.php; tests/Feature/CatalogTest.php; tests/Feature/PosCutTest.php; tests/Feature/PosProductSearchTest.php; tests/Feature/PosReturnsTest.php; tests/Feature/PosSalesTest.php; tests/Feature/TicketSettingsTest.php; tests/Feature/ProductSaleModeMigrationTest.php (nuevo); tests/Unit/QuantityTest.php (nuevo).
- Pruebas JS: tests/pos.test.js; tests/product-form.test.js (nuevo).
- Memoria: .memory/entities/feature-productos-modalidades-venta-01.md; .memory/entities/plan-productos-modalidades-venta-01.md; esta entidad fix y .memory/relations.md.

## Cambios reales

- Formulario compartido: card bajo Información del producto con radios exclusivos, default pieza, old()/edición persistidas; editor de receta con búsqueda admin autorizada, altas/bajas/cantidades, casilla de inventario; ocultar/deshabilitar stock propio del kit y precisión condicional sin truncar al alternar.
- Servidor: validación de modalidad, integridad/cantidades de componentes existentes y activos, sin duplicados/autorreferencias/anidamiento; producto+receta guardados transaccionalmente con bloqueos por ID y segunda validación. Búsqueda acotada a 12 resultados sin costos; permisos admin.products.manage, no exigir POS ni home-admin.
- Cantidades: columnas DECIMAL(18,3), cálculo físico en milésimas y casts numéricos conservando int cuando integral. Exceso de precisión/valores inválidos se rechaza, no se trunca ni reemplaza por cero. Dinero conserva dos decimales y precio/IVA/mayoreo propios; preview y cálculo por línea alineados.
- Venta: demanda global de venta directa+componentes, bloqueos ordenados y lectura de receta vigente bajo lock; fallos por cambios concurrentes requieren reintentar, sin aceptar componentes no bloqueados. Kit marcado consume solo componentes controlados, kit apagado no consume nada, padre nunca se descuenta. Movimientos/caja y snapshot efectivos se guardan en la misma transacción; idempotencia y precios por renglón conservados.
- Snapshots: sale_items.sale_mode e inventory_snapshot versionado con receta/casilla y efectos físicos reales por línea. Pendientes fijan receta/casilla sin reservar; cobran con stock/seguimiento actuales y una sola vez, sin consultar la receta nueva. Productos/componentes eliminados, inactivos o con modalidad incompatible bloquean cobro sin dinero/stock parcial.
- Devolución: granel decimal, pieza/kit entero; restituye solo IDs existentes y consumo efectivamente descontado; receta/casilla/track_inventory actuales no reinterpretan ventas nuevas pagadas. Fallback legacy explícito sin inventar recetas. Cantidades bloqueadas controlan saldo y diferencia de reembolsos acumulados a centavos reconcilia total, incluso en aprobaciones sucesivas; no SUM histórico inconsistente con la lectura actual del saldo.
- POS: escáner/búsqueda/frecuentes de granel abren dialog antes de mutar carrito, precio e importe en vivo, aceptar/cancelar/Escape/foco; reagregar suma milésimas solo al confirmar y editar conserva precisión. Piezas/kits/libres siguen enteros. Payload no acepta receta/snapshot del cliente. Contador de renglones, disponibilidad derivada del kit y sin costos expuestos.
- Disponibilidad/presentación: alertas físicas excluyen stock propio de kits; disponibilidad derivada de componentes, eager loading en consumidores; tickets, devoluciones, dashboard y cortes muestran fracciones y no llaman piezas a medidas mixtas. Cortes guardados no se recalculan; precio/costo del kit no se suma desde componentes.
- Historia: default unit para productos/ventas anteriores sin inferir nombre Kit; recetas vigentes cascade al limpiar, snapshots históricos sin FK a catálogo. Limpieza conserva contrato previo; CSV mantiene nuevos unit y actualización exclusiva de barcode vacío en existentes. Borrado individual bloqueado si es componente en uso.
- Cambios de modalidad protegidos: no truncar fracciones, convertir stock propio no nulo a kit ni convertir un componente en kit; no cambiar modalidades con operaciones pendientes incompatibles, saldos devolvibles o movimientos físicos. La restricción por movimientos históricos es conservadora e intencional.

## Ajustes comunicados e integración

- Soporte compartido Quantity/QuantityCast fuera de los nombres concretos del plan inicial: necesario para evitar conversiones repetidas y mantener contratos enteros legacy sin dependencia nueva.
- Salvaguardas comunicadas antes de aplicarlas: original_stock oculto en edición, validado y comparado bajo lock para rechazar ficha obsoleta; retiro del recálculo de precio al abrir, conservando fórmula/eventos de costo/margen/IVA/precio. Evitan sobrescribir ventas o cambiar precio al editar modalidad/receta. Clientes legacy sin original_stock solo reciben protección durante la petición, no desde que abrieron la ficha.
- Correcciones de integración dentro del alcance: no truncar product_id antes de validarlo; validar actividad al cobrar pendientes; reembolso por diferencia de acumulados sobre returned_quantity bloqueado, no SUM sujeto a lectura MVCC obsoleta; deshabilitar también ID oculto del renglón ya completamente devuelto; bloque PHP explícito de dashboard compatible con Blade instalado; formato de cantidades tolera artefactos float de agregación sin relajar validación de entrada.
- No se investiga ni declara corregido el incidente anterior de pérdida de un costo capturado. No cambios de dependencias, configuración, datos reales ni historial Git.

## Verificación y pendientes

- Verificado: php vendor/bin/pint --dirty --format agent aprobado; php -l en 33 archivos PHP de fuente/pruebas/migraciones; node --check en los dos JS productivos y dos JS de pruebas; php artisan view:cache --no-interaction y php -l sobre 132 archivos Blade compilados sin fallos; git diff --check correcto. route:list confirma búsqueda de componentes y rutas existentes.
- Revisión estática independiente de servicios, snapshots, devolución, UI, formularios y contratos. Hallazgos concretos corregidos y comprobados por lectura; no acredita concurrencia ni comportamiento en ejecución.
- Cobertura añadida sin ejecutar: modalidades/defaults/edición/old; precision y límites; modal por las tres entradas/cancelación/foco/precios/IVA; demanda combinada/idempotencia; kits con granel/no-control/apagados; pendientes congeladas e inactivos/ausentes; devoluciones/saldos/centavos/historia; permisos/búsqueda; CSV/limpieza; tickets/cortes/catálogo; actualización de esquema y rollback protegido; ficha obsoleta y precio al abrir.
- NO ejecutado: PHPUnit/node --test, navegador/escáner real, verificación de concurrencia o migración en MySQL, migraciones reales, lecturas/escrituras de BD real, respaldo/restauración de BD, build.sh/build de producción ni despliegue.
- Activación pendiente: solicitar Testing explícitamente, verificar esquema real y migración previa de limpieza, respaldo consistente/restaurable y pruebas de upgrade/locks en clon MySQL; autorizar/aplicar migraciones y publicar backend/frontend compatibles. No usar modalidades nuevas ni considerar desplegada esta fuente hasta completar esas fases.
- Rollback: conservar respaldo de archivos indicado en plan; down rechaza fracciones/recetas/snapshots o valores fuera del rango original. Tras operar con nuevas modalidades no volver a código entero ni restaurar BD sobre ventas posteriores sin conciliación/autorización; preferir corrección hacia adelante.

## Ejecución real en MySQL local (2026-11-21)

- Error corregido: SQLSTATE[42S02] Table 'motozone.product_kit_items' doesn't exist en portada y ficha; causa = migraciones del plan pendientes, no HomeController.
- Respaldo verificado: mysqldump --single-transaction --quick --routines --triggers --events --default-character-set=utf8mb4; archivo C:\Users\Administrador\AppData\Local\Temp\opencode\motozone-db-backup\motozone-20261121-164919.sql (1.18 MB). Restaurado en motozone_backup_check con redirección nativa de cmd + utf8mb4 (la tubería de PowerShell corrompía acentos): 31 tablas con hash lógico idéntico.
- Aplicadas solo 2026_11_21_155900_add_product_sale_modes_and_kit_items y 2026_11_21_155922_support_fractional_inventory_and_sale_snapshots (batch 3) vía migrate --path. 2026_11_20_114507_preserve_stock_movement_history_when_products_are_deleted sigue Pending.
- Esquema confirmado: product_kit_items existe (0 filas); stock/min_stock/quantity/returned_quantity/stock_before/stock_after en decimal(18,3); sale_items.sale_mode default unit; inventory_snapshot json nullable; kit_deduct_components default 0; sale_mode unit en 5368 productos y 3 sale_items.
- Datos intactos: comparación fila por fila contra respaldo = 0 diferencias en products (5368), sale_items (3), stock_movements (3), sale_return_items (0), sales (3), cash_movements (7). Los hashes MD5 difieren solo por formato decimal.
- HTTP real: portada 200 sin SQLSTATE ni product_kit_items y con fichas; /producto/7500091001090-6afd21c5c844c 200; sin errores nuevos en laravel.log (último 16:43, anterior a la migración de las 16:52).
- Pruebas ejecutadas: QuantityTest+PosSalesTest+PosReturnsTest 114/114 (716 aserciones); ProductSaleModeMigrationTest 5/5 (34); AdminProductTest (import) 19/19; node --test tests/pos.test.js tests/product-form.test.js 35/35; pint --format agent en las pruebas modificadas.
- Ajuste de arnés en ProductSaleModeMigrationTest: migrate:fresh en setUp (RefreshDatabase impedía PRAGMA foreign_keys=0 y DatabaseMigrations fallaba al revertir 2026_11_04_000000).
- Ajuste de arnés en AdminProductTest (2 pruebas de importación): se quitó assertSessionHasErrors antes del GET porque iniciar la sesión en TestResponse consume el flash y la vista ya no mostraba el mensaje; la página se sigue verificando con assertSee.
- Fallos restantes NO relacionados (preexistentes, verificados contra HEAD): 5 errores audit_logs.deleted_at (la migración 2026_11_04_020000 no crea deleted_at y AuditLog usa SoftDeletes); AdminTest:418 assertSee con & sin escapar vs &amp;; CatalogTest:40 assertDontSee('Frenos') y CatalogTest:103 assertDontSee('wa.me/...') contradicen la vista ya existente en HEAD.
- Sin commits ni cambios de historial; cambios de código, migraciones y .memory siguen sin commit.
