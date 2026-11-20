---
entity: plan-productos-limpiar-con-historial-01
type: plan
---

- Tipo: feature.
- Estado: PROPUESTO; decisión arquitectónica confirmada, pendiente de aprobación del plan para implementación. No se aplicó código, configuración, migración ni limpieza real.
- Objetivo: Quiero que «Limpiar inventario» elimine todos los productos, tengan o no existencias o referencias.

## Alcance y exclusiones

- Alcance: cambiar únicamente la semántica y soportes de clearInventory para borrar físicamente productos aunque tengan movimientos, conservando la identificación e historial; mantener autorización, CSRF y confirmación; incluir respaldo previo, pruebas y rollback.
- Fuera de alcance: borrar movimientos de cualquier clase, incluidos ajustes automáticos antiguos; borrar ventas/caja/cortes/devoluciones/auditoría/cards/categorías; alterar costos/importación/borrado individual; eliminar archivos físicos; resetear IDs; desactivar FKs; nuevas dependencias; crear un visor nuevo de movimientos; limpiar datos reales desde esta sesión.

## Base técnica revisada

- Laravel 13.38.0 y PHPUnit 12.5.40, confirmados con composer show --direct. Workspace limpio según git status --short al comenzar esta revisión.
- Sin .ai/rules en el workspace. Skills aplicadas: ponytail, laravel-best-practices y testing-best-practices; reglas consultadas: migraciones, Eloquent, seguridad, controladores, excepciones, Blade, rendimiento de consultas, endpoints y aislamiento de pruebas.
- memory_* y codesearch_* no disponibles en el catálogo; fallback .memory y grep/glob/read. Esto no sustituye una comprobación del esquema productivo.
- ProductController.php:92-119: clearInventory elimina ajustes antiguos específicos, intenta DELETE products en transacción y transforma SQLSTATE 23000 en el mensaje genérico de movimientos asociados. Se revisa el código local; no se afirma que sea el mismo que está publicado ni que una consulta real haya demostrado la causa actual de producción.
- create_sales_tables.php:55-67: stock_movements.product_id no anulable y FK restrictOnDelete; movimiento tiene cantidades, fechas, usuario y venta, pero no nombre/SKU/código propios.
- create_sales_tables.php:31-53: sale_items.product_id nullable/nullOnDelete y snapshots existentes de product_name, sku, barcode, categoría, precios, impuestos, costo y totales. El ticket receipt.blade.php:32-35 usa esos snapshots; DailyCutService usa datos de venta y category_name, no el producto actual.
- home_cards y home_publications: referencias nullable/nullOnDelete; home_card_product: cascade solo de la vinculación editorial. Borrar productos desvincula referencias editoriales, no elimina cards/publicaciones/familias.
- Callers: único endpoint de clearInventory en routes/web.php:175-184, protegido por admin.products.manage; botón y formulario con CSRF en resources/views/admin/products/index.blade.php:52-61. Borrado individual separado en destroy conserva su política.
- Consumidores transitivos: SaleService crea movimientos y rechaza productos ausentes al cobrar pendientes; ReturnService permite devolución monetaria sin reponer cuando ya no existe producto; POS consulta products para búsqueda/frecuentes; Dashboard obtiene importes desde ventas y productos actuales para inventario/ranking. HomeCard tolera relación nula.
- Pruebas existentes de AdminProductTest.php:461-621: eliminación sin referencias, ajustes antiguos eliminados, bloqueo transaccional por movimientos, permisos y borrado individual. El nuevo contrato reemplaza explícitamente las dos expectativas de eliminar ajustes antiguos y bloquear por movimientos.

## Decisión y diseño mínimo

- Usuario eligió «Borrado real con historial». No usar SoftDeletes: ocultaría productos pero no vaciaría físicamente products.
- Nueva columna JSON nullable product_snapshot en stock_movements, con id original, name, sku, barcode y barcode2. Capturar datos vigentes al limpiar, sin inventar que representan el nombre/código al momento de un movimiento antiguo. No almacenar una copia completa de precios/stock ya documentados en otras columnas.
- Convertir stock_movements.product_id en nullable y FK ON DELETE SET NULL mediante migración nueva, sin editar migraciones ya desplegadas y sin eliminar el índice compuesto existente.
- Dentro de la transacción de limpieza, bloquear los productos del conjunto a eliminar en orden estable; rellenar product_snapshot de sus movimientos antes del DELETE; conservar snapshots ya existentes. Usar actualizaciones que no modifiquen timestamps históricos. El snapshot se completa en la limpieza, no exige cambios en todos los productores de movimientos.
- Después borrar solo el conjunto de productos cuya identificación se aseguró en esa transacción. Las FK desvinculan referencias válidamente. Ningún movimiento se elimina ni se crea por limpiar.
- Mantener errores reales con rollback y diagnóstico correcto. No esconder cualquier error de integridad bajo «existen movimientos», ni desactivar restricciones, usar TRUNCATE o prometer éxito ante fallos de infraestructura.

## Pasos de implementación

1. Confirmar este plan y revisar nuevamente el diff local. En el entorno de despliegue, verificar de solo lectura versión, migraciones aplicadas, FKs reales y volumen; detectar referencias adicionales no presentes en el repositorio antes de modificar esquema. Si cambia la superficie, detenerse y actualizar el plan, sin borrar filas para eludirla.
2. Respaldar los archivos afectados y la base completa de forma consistente, incluidas tablas de productos, referencias editoriales, ventas, caja, cortes y movimientos. Proteger el respaldo, registrar ubicación/fecha y verificar su restauración en una copia aislada. No habilitar el borrado irreversible sin respaldo verificado; este plan no ejecuta el respaldo ni el borrado real.
3. Crear con Artisan una migración nueva para product_snapshot y para product_id nullable/SET NULL; confirmar sintaxis contra Laravel instalado. No editar create_sales_tables ni nuevas dependencias. Probar tanto instalación limpia como actualización desde esquema anterior con movimientos existentes, en SQLite de pruebas y en clon MySQL.
4. Incorporar el cast array y la asignación controlada de product_snapshot en StockMovement si se usa ese modelo para persistirlo; mantener product() como belongsTo que admite resultado nulo. No introducir una nueva capa de servicios o un sistema de archivado.
5. Reescribir solo clearInventory: retirar DELETE de ajustes automáticos; bloquear productos en orden estable, conservar identificación en sus movimientos, eliminar los productos preparados y devolver el conteo real. Snapshot, desvinculación y DELETE de datos deben ser una sola transacción. Mantener timestamps e importes, y rollback íntegro ante cualquier fallo. Verificar carreras con venta/devolución e impedir que un DELETE amplio alcance productos nuevos sin snapshot.
6. Mantener ruta, permiso, POST y CSRF. Ajustar únicamente el texto de confirmación para comunicar borrado permanente de todos los productos, conservación de historial, desvinculación de web y necesidad de respaldo. Conservar error visible local y mensaje de éxito; no rediseñar la pantalla ni cambiar destroy.
7. Actualizar las regresiones del botón y añadir cobertura de productos con ventas, movimientos de venta/devolución/ajuste y ajustes antiguos; identidad histórica con ceros iniciales/símbolos, snapshot previo conservado, repetición y rollback real. Añadir aceptación de tickets/cortes, POS vacío, cards conservadas pero desvinculadas, cobro pendiente rechazado sin alterar dinero y devolución que no recrea existencias de producto eliminado.
8. Ejecutar pruebas dirigidas con APP_ENV=testing y conexión efectiva SQLite :memory:, sin reutilizar una conexión productiva ni caché de configuración; seguir convenciones PHPUnit existentes. Usar archivos de prueba y filtros concretos. Después validar upgrade/FKs y concurrencia en una BD MySQL desechable, no en producción. Ejecutar Pint para PHP modificado, lint PHP y revisión de diff.
9. Desplegar bajo autorización específica, con una ventana breve sin nuevas altas/importaciones/ventas/devoluciones durante migración y primer uso. Validar esquema y snapshots en clon antes de habilitar el botón. El usuario ejecutará la limpieza real únicamente tras comprobar el respaldo; no se autoriza a este agente a ejecutarla por haber solicitado el plan.

## Archivos a tocar

- app/Http/Controllers/Admin/ProductController.php: solo clearInventory y sus imports estrictamente necesarios; snapshots y borrado transaccional sin eliminar movimientos; errores fieles. No tocar import/store/update/productData.
- app/Models/StockMovement.php: soporte de lectura/persistencia controlada del snapshot JSON, sin cambiar cantidades, fechas ni semántica de la relación.
- database/migrations/<timestamp>_preserve_stock_movement_history_when_products_are_deleted.php (nuevo, nombre final generado con Artisan): agregar snapshot y cambiar nulabilidad/acción de la FK; rollback protegido.
- resources/views/admin/products/index.blade.php: únicamente confirmación/mensajes del botón masivo; mantener estructura, estilos, CSRF y borrado individual.
- tests/Feature/AdminProductTest.php: nuevo contrato de limpieza, movimientos preservados, referencias editoriales, permisos, confirmación, repetición, atomicidad y datos contables conservados.
- tests/Feature/PosSalesTest.php: regresiones de movimiento normal y venta pendiente cuyo producto fue eliminado; rechazo controlado sin cobro ni conversión a artículo manual.
- tests/Feature/PosReturnsTest.php: regresión de devolución posterior a la limpieza y movimiento normal cuando el producto aún existe.
- tests/Feature/PosCutTest.php y tests/Feature/HomeFamilyTest.php / HomeCardTest.php: ampliar solo si la misma aceptación no queda cubierta por pruebas del botón; comprobar corte/ticket y conservación de cards con relaciones desvinculadas. Son candidatos condicionados, no autorización para refactorizarlos.
- Sin cambios previstos en SaleService, ReturnService, PosController, DashboardController, HomeCard, plantillas de ticket, rutas, seeders o factories: su tratamiento actual de relaciones nulas y snapshots se verificará con pruebas; si falla, detenerse para delimitar un cambio adicional, no ampliar silenciosamente.

## Criterios de aceptación

- CA1: tras limpieza válida, products queda físicamente vacío; se incluyen agotados, inactivos, no controlados y productos con ventas/movimientos.
- CA2: se conservan IDs y cantidades de todos los movimientos, incluidos ajustes antiguos, con user_id/sale_id/type/quantity/stock_before/stock_after/note/timestamps intactos. Solo se agrega snapshot y product_id pasa a NULL; snapshot identifica inequívocamente el producto original y no se sobrescribe.
- CA3: ventas, partidas, devoluciones, caja, cortes, auditoría y sus importes/fechas siguen existiendo; partidas conservan snapshots y solo product_id se desvincula por la FK. Tickets y totales de corte siguen consultables e iguales.
- CA4: cards/publicaciones/familias y sus textos/imágenes siguen existiendo; referencias directas a producto quedan nulas y pivotes a productos eliminados desaparecen, sin referencias colgantes ni errores al visualizar.
- CA5: listado de Productos, búsqueda/frecuentes del POS y contadores actuales quedan sin productos. Una importación/alta posterior genera registros nuevos y no hereda movimientos de productos eliminados, aunque repita SKU/código. No restablecer IDs.
- CA6: se conservan confirmación cancelable, CSRF, autenticación y permiso admin.products.manage; sin permiso no se cambia ningún dato. La confirmación advierte sobre irreversibilidad, referencias e historial conservado.
- CA7: tabla vacía y pulsación repetida son válidas; muestran el conteo correcto y no eliminan ni duplican movimientos.
- CA8: fallo inducido después de guardar snapshots y antes de completar el borrado revierte snapshots, desvinculaciones y productos, sin eliminación parcial. Una referencia histórica del esquema soportado no provoca el antiguo bloqueo.
- CA9: una venta pendiente con productos eliminados sigue consultable pero el cobro existente devuelve validación sin caja/stock/venta pagada; no se transforma silenciosamente en concepto libre. La devolución mantiene reglas existentes y no resucita productos ni repone existencias inexistentes.
- CA10: actualización desde esquema previo con datos y FK nueva comprobadas en clon MySQL; pruebas dirigidas aisladas, autorización de despliegue y respaldo restaurable verificado antes de uso real.

## Plan de rollback

- Antes de migrar o limpiar: restaurar únicamente cambios propios desde respaldo de archivos, respetando modificaciones ajenas. Si no se ha ejecutado la limpieza y no existen product_id nulos, el cambio de FK puede revertirse tras validación; exportar snapshots antes de retirar su columna.
- Después de limpiar: revertir PHP no recupera productos ni sus vínculos. No ejecutar down que convierta NULL a NOT NULL ni borrar movimientos para hacer que funcione; down debe rechazar el rollback si existen referencias nulas o datos que perdería.
- Recuperación real: detener escrituras y, con autorización específica, restaurar coordinadamente base/respaldo completo y versión compatible; evaluar y preservar operaciones posteriores al respaldo antes de cualquier restauración para no perderlas. Preferir una corrección hacia adelante manteniendo snapshot/FK nullable si solo falla código.

## Riesgos y dependencias externas

- Borrado permanente: precios/configuración del catálogo, relaciones editoriales y existencia actual desaparecen con products; su recuperación exige respaldo. Las imágenes físicas no se eliminan en este alcance.
- El snapshot de movimientos antiguos refleja identificación disponible al limpiar, no garantiza recuperar nombres/códigos que cambiaron antes. No hay visor de movimientos nuevo en este plan.
- Quitar el vínculo vivo hace imposible reponer automáticamente un producto eliminado; una venta pendiente no se cobra como si aún existiera. Comportamiento comunicado y cubierto en aceptación, sin ampliar reglas de negocio.
- MySQL puede bloquear o producir deadlocks durante snapshot/DELETE; verificar aislamiento, orden de bloqueos y tratamiento acotado de reintentos. Ante fallo, rollback y error, nunca borrado de historial. Altas realizadas después de terminar la limpieza constituyen nuevo inventario y no se promete mantener la tabla vacía para siempre.
- FKs activas en producción podrían diferir del código. No se consultó la BD real en esta sesión. Verificación previa y ventana de operación son dependencias obligatorias, no una promesa de éxito incondicional ante cualquier esquema/infraestructura.
- Laravel 13.38.0 ya instalado: no se requieren paquetes, cambios de dependencias, workers, comandos nuevos ni configuración permanente adicional.

## Antecedentes y bugs relacionados

- Integra plan-productos-limpiar-tabla-01 / fix-productos-limpiar-tabla-01, sustituyendo solo el contrato del botón masivo cuando se apruebe y aplique este plan. La implementación anterior sigue siendo la efectiva hasta entonces.
- Bug activo en el mismo ProductController: bug-productos-costo-vacio-01, diagnóstico del valor 7.50 pendiente. No normalizar costo a cero, ni tocar productData/store/update/import o el formulario de alta/edición como parte de este trabajo.
- bug-importar-csv-barcodes-01 corregido en fuente, recuperación de datos pendiente. No reimportar, reparar códigos ni afirmar recuperados los datos por limpiar inventario.
- No se crea hipótesis CONFIRMADA ni se declara resuelto el bloqueo actual de producción: el modo es feature y no se consultó esa base.

## Alternativas consideradas

- Borrado real + snapshot + FK nullable: elegido expresamente por usuario; vacía products y conserva historial con una migración.
- SoftDeletes/archivado: descartado por usuario; conserva vínculo más fácilmente, pero no vacía físicamente la tabla y amplía consultas/uniqueness.
- Borrar movimientos o CASCADE de historial: descartado por violar conservación; deshabilitar FKs: descartado por referencias inconsistentes; archivar catálogo completo en tablas nuevas: descartado por complejidad no solicitada.

- Fecha: 2026-11-20.
- Verificado en esta sesión: lectura de código/migraciones/pruebas/memoria, versiones directas, estado git y ruta con Artisan route:list. No se ejecutaron tests, migraciones, respaldos, escrituras de BD ni operaciones de historial git.

## Aprobación de implementación

- Aprobación explícita: usuario responde «aplicalo» al plan presentado en esta conversación, 2026-11-20.
- Estado: APROBADO PARA IMPLEMENTACIÓN EN FUENTE. No autoriza ejecutar borrados reales, despliegue, restauraciones de BD ni Testing por iniciativa propia.
- Respaldo de archivos antes de editar: C:/Users/Administrador/AppData/Local/Temp/opencode/motozone-limpiar-con-historial-20261120-114504; contiene controlador, modelo, vista y los tres archivos de pruebas principales con sus rutas originales.
- Ajuste de secuencia comunicado antes de editar: preparar código/migración/pruebas sin migrar la base real; respaldo de datos consistente y restauración comprobada, aplicación de migración, Testing y despliegue quedan pendientes de solicitud y entorno autorizado. No se habilita la nueva semántica en una BD real desde esta ejecución.

## Cierre de implementación en fuente

- Estado: IMPLEMENTADO.
- Implementado en: fix-productos-limpiar-con-historial-01.
- Cambios aplicados: migración nueva de snapshot/FK nullable SET NULL con down protegido; limpieza transaccional por chunks con snapshots sin modificar timestamps ni borrar movimientos, límite de IDs inicial y tres intentos; cast de StockMovement; confirmación explícita; regresiones de limpieza/historial/rollback/identidad/migración, ventas pendientes y devoluciones. No se cambiaron métodos de alta/importación/costo, borrado individual, servicios, rutas ni dependencias.
- Archivos tocados: app/Http/Controllers/Admin/ProductController.php; app/Models/StockMovement.php; resources/views/admin/products/index.blade.php; database/migrations/2026_11_20_114507_preserve_stock_movement_history_when_products_are_deleted.php; tests/Feature/AdminProductTest.php; tests/Feature/PosSalesTest.php; tests/Feature/PosReturnsTest.php.
- Verificación estática: Pint --dirty --format agent, sintaxis de seis PHP afectados y git diff --check correctos; revisión estática independiente sin hallazgos; ruta y middleware conservados. No se ejecutaron las pruebas.
- Desviaciones del plan original: ajuste de secuencia comunicado, sin desviaciones de diseño: Testing requiere solicitud explícita; respaldo/restauración verificada, clon MySQL, aplicación de migración y despliegue pendientes. No se alteró la base real ni se ejecutó limpieza.
- Estado de aceptación/despliegue: PENDIENTE. Antes del uso real verificar respaldo de datos y restauración, esquema real y pruebas en entorno aislado; aplicar la nueva migración bajo autorización. IMPLEMENTADO refiere a fuente, no a publicación ni limpieza efectiva.
