---
entity: plan-productos-modalidades-venta-01
type: plan
---

- Tipo: feature.
- Estado: PROPUESTO; las tres decisiones de precisión, componentes y pendientes están confirmadas. NO IMPLEMENTADO ni aprobado para aplicar código.
- Objetivo: Permitir elegir y conservar cómo se vende cada producto de MotoZone —por unidad/pieza, a granel o como paquete (kit)—, adaptando Productos, Inventario y POS sin alterar los campos ni el diseño actuales.
- Fecha: 2026-11-21.

## Entrada y verificación

- Objetivo/alcance/criterios proceden de la solicitud detallada; fuera de alcance fundamentado en conservar diseño/campos y solo plan; módulos explícitos Productos, Inventario y POS, archivos concretados por lectura.
- Decisiones mediante question: 3 decimales para granel, piezas y granel como componentes sin anidamiento, snapshot de composición/casilla al guardar una pendiente sin reserva de stock.
- Versiones verificadas con composer show --direct: Laravel 13.38.0, PHPUnit 12.5.40, Pint 1.32.1; package.json declara Tailwind 4.3.3 y Vite 8. Sin paquetes nuevos.
- Skills disponibles: deploying-to-cloud, laravel-best-practices, opencode, ponytail, ponytail-audit, ponytail-debt, ponytail-gain, ponytail-help, ponytail-review, report, tailwindcss-development, testing-best-practices. Aplicadas: ponytail, laravel-best-practices, tailwindcss-development y testing-best-practices. Las demás no corresponden a esta planificación.
- No hay .ai/rules en el workspace. memory_* (search_nodes/open_nodes/add_observations/create_entities/create_relations/set_importance) y codesearch_* no están disponibles tras descubrimiento; fallback .memory/ y búsquedas glob/grep/read, más dos exploraciones delegadas exclusivamente de lectura.
- Git status --short inicialmente sin cambios. No se ejecutaron tests, build, Artisan, migraciones, escrituras de BD ni operaciones de historial Git. Esta sesión solo añade memoria del requerimiento/plan.

## Código real que condiciona el plan

- resources/views/admin/products/partials/form.blade.php:26-52 contiene Información del producto; alta y edición comparten la misma plantilla. Inventario :124-143 usa step=1. resources/js/admin/product-form.js solo administra cálculos actuales de costo/precio/IVA.
- app/Http/Requests/Admin/ProductRequest.php:54-55 exige enteros; app/Models/Product.php:47-48 castea stock/min_stock a entero; lowStockThreshold(): int en :135. No existe modalidad ni receta.
- app/Http/Controllers/Admin/ProductController.php:368-402 crea/actualiza directamente; productData :414-439 tiene lista de persistencia explícita. Importación mantiene contrato separado: nuevos productos enteros, existentes solo completan barcode vacío.
- app/Http/Requests/Pos/StoreSaleRequest.php:20 convierte cantidad a int antes de validar; SaleService.php:176,121,345,354 repite conversiones; PricingService.php:47 recibe int. Cambiar solo HTML o stock perdería fracciones.
- resources/js/pos/pos.js:272-312 agrega por producto; búsqueda/escáner/frecuentes comparten entrada. :233 input entero, :723 Math.floor. Ya existen dialog y guard de atajos con dialog abierto, a reutilizar.
- SaleService bloquea IDs ordenados y agrega demanda por producto, cobra y descuenta transaccionalmente; pendientes no reservan stock. Mantener idempotencia, auditoría/precios autorizados y no fusionar líneas con precios diferentes.
- ReturnService devuelve hoy el producto de la línea; los movimientos tienen sale_id pero no origen inequívoco por línea/componente. Es imprescindible preservar el consumo por línea nueva para devolver kits correctamente.
- DailyCutService.php:184 trunca cantidades, receipt.blade.php:43 trunca devuelto; listado de ventas/devoluciones y dashboard hablan de piezas. Ajustar precisión/etiquetas, no rediseñar contabilidad.

## Alcance y fuera de alcance

- Alcance: selector y persistencia; receta del kit; precisión de cantidades completa; modal granel; reglas de inventario; compatibilidad mínima indispensable de pendientes/devoluciones/tickets/reportes; pruebas y migración segura.
- Fuera de alcance: rediseño, refactor general, corrección de incidentes anteriores, precios automáticos por suma de componentes, cambios de IVA/costo/mayoreo, nuevas compras/recepciones/mermas, unidades convertibles, venta por importe, kits anidados, devolución independiente de piezas de un kit, importación CSV de recetas/modalidades, cambios a home editorial/cartera/caja ajenos a las cantidades y nueva infraestructura/dependencias. No ejecutar la implementación ni datos reales.

## Diseño funcional y persistencia

1. Formulario: insertar una x-card inmediatamente después de Información del producto, antes de Identificación, con fieldset/legend ¿Cómo se vende este producto? y radios Por unidad/pieza / A granel / Como paquete (kit), un mismo name. Pieza default en alta y productos anteriores; old() prevalece tras validación, luego valor persistido. Conservar clases mz-*, campos, orden restante y cálculos actuales.
2. Unidad/granel: mantener checkbox actual de control de inventario. Cantidad/minimo step=1 para pieza, 0.001 para granel, sin truncar valores al alternar; rechazar valores incompatibles. Cantidad vendida positiva; enteros pieza/kit/artículo libre, granel 0.001..9999 con hasta 3 decimales, conservando el límite POS actual. Existencias/mínimos no negativos y dentro del rango del esquema. No crear conversión litros/ml: el precio corresponde a una unidad de medida del producto.
3. Kit: mostrar Contenido del paquete con búsqueda acotada y autorizada de productos, agregar/quitar y cantidad por kit. Permitir piezas y granel; cantidades positivas e integralidad según componente. Sin autorreferencias, duplicados ni kits anidados; al menos un componente. Mostrar casilla exacta Descontar del inventario los productos del paquete al vender; propuesta conservadora para kit nuevo: apagada, siempre explícita. Edición restaura su estado. Precio/costo/IVA/mayoreo propios del kit, sin sumar automáticamente componentes.
4. Kit nunca usa stock/minimo propios: ocultar sus entradas operativas de inventario y forzar no descontar padre en servidor. Un flag separado controla consumo de componentes; los componentes sin control de inventario conservan su exención y se identifican como tales. No confiar en inputs ocultos ni permitir que el POST active consumo doble.
5. Nuevas migraciones, sin editar antiguas: products.sale_mode (unit/bulk/kit, default unit), products.kit_deduct_components (boolean); product_kit_items con kit_product_id, component_product_id y quantity decimal, índice único por pareja e integridad referencial. Puede usarse relación belongsToMany con cantidad pivot sin añadir un modelo/capa innecesarios.
6. Convertir a DECIMAL(18,3) las ocho columnas: products.stock/min_stock; sale_items.quantity/returned_quantity; stock_movements.quantity/stock_before/stock_after; sale_return_items.quantity. Conservar rangos, defaults, nulabilidad, índices y signo negativo del movimiento de salida. No float en BD; cálculo físico en milésimas enteras para no acumular errores binarios. Dinero continúa con dos decimales y política de IVA incluido existente; alinear previsualización y servidor.
7. sale_items conserva sale_mode y snapshot JSON versionado de receta/casilla por línea, además del efecto de inventario realmente aplicado al pagar. Pendiente: receta/casilla fijadas y ningún efecto aplicado aún; cobro valida productos/cantidades/existencias y completa efecto aplicado. Pagada: efecto inmutable usado en devolución aunque cambien receta o casilla. Identificación y cantidades normalizadas sobreviven al borrado del catálogo. No aceptar estos snapshots ni modalidad desde el cliente como autoridad.
8. Legado: todos los productos existentes y líneas anteriores siguen unit, incluso si su nombre dice Kit; snapshot nuevo nullable con ruta legacy explícita. No inventar composición/consumos para ventas históricas. Las pendientes antiguas conservan reglas anteriores por pieza. CSV sigue creando productos unit y completando solo barcode vacío en existentes; no altera modalidad/receta/stock de existentes.
9. Proteger cambios de modalidad: no truncar granel fraccionario a piezas ni convertir automáticamente stock físico propio en kit/componentes. Validar referencias y operaciones pendientes incompatibles; impedir pasar a kit con existencia propia no nula o convertir a kit un componente vigente. Informar el bloqueo y exigir conciliación explícita fuera de esta ejecución. No resetear stock para forzar el cambio.
10. Borrado: impedir borrado individual de componente usado sin quitarlo de las recetas; receta vigente puede eliminarse en cascada al borrar el catálogo completo, pero snapshots de venta no deben borrarse ni tener FK restrictiva a products. Mantener limpieza física con historial; pendiente cuyo padre o componente requerido desapareció se rechaza sin cobrar. Devolución de nueva venta usa efectos históricos y repone solo IDs que aún existen, sin recrearlos ni enlazar por código.

## Pasos de implementación ordenados

1. Tras aprobación explícita, confirmar diff, esquema/migraciones reales y baseline en entorno aislado. Respaldar y verificar restauración antes de cambios de esquema; no asumir aplicada la migración previa de limpieza.
2. Crear migraciones aditivas, relación de composición, casts y lectura legacy; verificar actualización de datos existentes e instalación limpia sin recalcular precios ni existencias.
3. Extender ProductRequest y persistencia lista permitida; producto+receta+casilla se guardan en una transacción, con bloqueo consistente al editar. Agregar búsqueda bajo admin.products.manage, no reutilizar un endpoint exclusivo de administradores de home o exigir permiso POS a quien solo gestiona productos.
4. Extender Blade y product-form.js: selector, campos condicionales, búsqueda de componentes, validación/errores y repoblación; mantener el cálculo de precios existente sin cambios de fórmula.
5. Extender validación y PricingService para cantidades decimales sin conversiones previas a int ni redondeo silencioso de entradas inválidas; servidor confirma modalidad/receta/casilla desde catálogo o snapshot autorizado.
6. Ampliar SaleService: preparar demanda física por componente y venta directa, bloquear padres/componentes en orden determinista y asegurar receta estable frente a edición concurrente, agregar demanda global y validar antes de escribir. Reutilizar transacciones/idempotencia. Unidad/granel consumen su producto si controla inventario; kit marcado consume solo componentes controlados; kit apagado no mueve nada. Registrar efecto por línea y movimientos con cantidades reales.
7. Guardar/cobrar pendientes con receta/casilla congeladas, cantidades fraccionarias intactas, sin reserva ni cobro anticipado; cobrar una sola vez validando stock actual. Cambios actuales de seguimiento se resuelven al cobrar y el efecto real queda registrado; no sustituir composición guardada ni aceptar componentes inexistentes/incompatibles. Cancelar pendiente no mueve inventario.
8. Extender JSON de búsqueda/frecuentes/pendiente y punto común addProduct. Granel abre dialog antes de modificar carrito, precio vigente autorizado visible, importe actualizado, cantidad enfocada, aceptar/cancelar/Escape y retorno de foco. Aceptar agrega cantidad exacta; reagregar abre modal y suma solo al confirmar, edición permite precisión. Precio mayoreo/manual cambia el importe con reglas/permisos existentes. Mantener artículos libres enteros e independientes.
9. Adaptar ReturnService/requests/modelos/formulario: devolver granel fraccionado, pieza/kit enteros; kit completo reintegra proporcionalmente solo componentes realmente descontados. Casilla apagada no repone nada. Locks por productos y límites acumulados; reembolso parcial nunca supera total original, con reconciliación final de centavos y verificación al aprobar. Datos legacy mantienen tratamiento anterior sin inventar historia.
10. Ajustar disponibilidad e inventario: alertas físicas sobre componentes, no stock del kit. Disponibilidad vendible del kit marcado derivada del mínimo floor(stock/componente) entre componentes controlados; apagado sin límite de stock propio. Alinear badge/filtros/listas/POS, evitar N+1. Respetar mínimo cero como fallback actual a 5, no cambiar esa política sin solicitud.
11. Adaptar tickets/devoluciones/listados/dashboard/cortes para mostrar fracciones sin truncar y no etiquetar medidas heterogéneas como piezas; mantener totales monetarios y snapshots de cortes anteriores. No convertir componentes en renglones de ingreso ni duplicar costos; costo del kit sigue el propio capturado.
12. Ejecutar cobertura dirigida cuando se autorice, pruebas de migración/concurrencia en clon MySQL, revisión manual UI, Pint para PHP modificado y build frontend. Publicación/migración de entorno real requieren autorización separada; no activar nuevas modalidades con frontend/backend/esquema de versiones distintas.

## Archivos a tocar y razón

- resources/views/admin/products/partials/form.blade.php: selector, composición, casilla, precisión y errores.
- resources/js/admin/product-form.js: estados y búsqueda de componentes, sin reescribir cálculos actuales.
- app/Http/Requests/Admin/ProductRequest.php: validación condicional de modo, cantidades, composición y referencias.
- app/Http/Controllers/Admin/ProductController.php: alta/edición transaccional, persistencia explícita, búsqueda de componentes, guardas de conversión/borrado y compatibilidad de limpieza/importación.
- routes/web.php: búsqueda de componentes en grupo admin.products.manage, antes de rutas dinámicas que pudieran interferir.
- app/Models/Product.php: campos/casts, relación pivot, reglas compartidas de modalidad/disponibilidad/alerta.
- database/migrations/<timestamp>_add_product_sale_modes_and_kit_items.php (nuevo): columnas/receta/defaults/índices/FKs.
- database/migrations/<timestamp>_support_fractional_inventory_and_sale_snapshots.php (nuevo): ocho cantidades DECIMAL y metadata/snapshot por línea; reversión protegida. Nombres/timestamps finales con Artisan durante implementación.
- app/Models/SaleItem.php, StockMovement.php, SaleReturnItem.php: cantidades decimales, snapshot y saldo retornable sin int.
- app/Http/Requests/Pos/StoreSaleRequest.php y StoreSaleReturnRequest.php: cantidades intactas y reglas por modalidad; conservar permisos.
- app/Services/PricingService.php: cálculo monetario con cantidad fraccionaria y redondeo consistente.
- app/Services/SaleService.php: demanda expandida/agregada, bloqueo, consumo, snapshot y pendientes.
- app/Services/ReturnService.php: reversión de efectos físicos históricos y reembolsos fraccionarios seguros.
- app/Http/Controllers/PosController.php: contratos de búsqueda/frecuentes/pendientes y disponibilidad.
- resources/views/pos/index.blade.php y resources/js/pos/pos.js: dialog granel, cantidades, edición/agregación, foco y atajos.
- app/Http/Controllers/Pos/SaleController.php y resources/views/pos/tickets/receipt.blade.php: payload/recibo sin cast entero; conservar impresión 58/80 mm.
- resources/views/pos/returns/create.blade.php y index.blade.php; resources/views/pos/sales/day.blade.php, pending.blade.php y previous.blade.php: inputs/etiquetas/saldos con modalidad y precisión.
- resources/views/admin/products/index.blade.php: modalidad y stock significativo, no stock propio del kit; ayuda CSV conservada.
- app/Services/DailyCutService.php y resources/views/pos/cuts/index.blade.php, print.blade.php: retirar truncamiento y etiquetas engañosas, sin alterar fórmulas ni cortes guardados.
- app/Http/Controllers/Admin/DashboardController.php y resources/views/admin/dashboard.blade.php: alertas y ranking sin truncamiento; conservar significado comercial, no sumar consumo de componentes como ventas.
- resources/views/components/stock-badge.blade.php y resources/views/catalog/show.blade.php: solo si hace falta adaptar texto/precio por unidad/disponibilidad a nueva semántica; modelo compartido/eager loading en consumidores para evitar N+1, sin rediseño público. CatalogController/HomeController/HomeContentController son candidatos de carga de relaciones únicamente si lo exige disponibilidad derivada.
- database/factories/ProductFactory.php: default unit y estados bulk/kit para tests; no ejecutar ni modificar seeder de datos reales.
- tests/Feature/AdminProductTest.php, PosSalesTest.php, PosReturnsTest.php, PosProductSearchTest.php, PosCutTest.php, AdminTest.php, CatalogTest.php y TicketSettingsTest.php: pruebas del contrato nuevo y regresiones relevantes. PosCutReconciliationTest/PosCutPdfTest como regresión contable/histórica cuando aplique.
- tests/pos.test.js: modal granel y cantidades con DOM simulado existente; tests/product-form.test.js (nuevo si necesario, con node:test existente) para radios/componentes/repoblación.

## Criterios de aceptación y pruebas

- CA1 UI/persistencia: tres radios siempre visibles bajo Información, selección exclusiva; unit por defecto y productos anteriores unit; crear/editar cada modalidad y devolver error de validación conserva selección y contenido. Pruebas AdminProductTest y JS del formulario; revisión manual oscura en móvil/escritorio y teclado.
- CA2 pieza: stock 10, venta 3 produce cobro 3 x precio y stock 7 con movimiento -3; sin control no mueve stock; cantidad 0.5 rechazada, nunca truncada. PosSalesTest y validación de producto.
- CA3 granel: stock 2.750/minimo 0.500; vender 0.500 a 14.10 da 7.05 y stock 2.250; 0.001 válido, 0.0001/0/negativo inválidos. Carrito/servidor/ticket coinciden y alerta respeta mínimo decimal. Producto, venta, ticket y catálogo.
- CA4 modal: escáner por ambos códigos, búsqueda y frecuentes abren dialog antes de agregar; edición/segunda incorporación precisas; importe en vivo, aceptar agrega una vez, cancelar/Escape no modifica carrito y devuelve foco, atajos no vacían venta. node --test tests/pos.test.js y revisión manual con escáner tipo teclado.
- CA5 kit: crear con 2 bujías y 1 filtro; buscar/agregar/quitar/reabrir conserva; venta 3 cobra 3 kits y mueve -6/-3, cero movimiento del kit. Apagado cobra igual y no cambia ninguna existencia. Permitir componente bulk 0.125 por kit; prohibir 0.5 bujías, kit vacío, duplicados, autorreferencia, anidamiento o IDs inexistentes.
- CA6 atomicidad/concurrencia: componente directo + dos kits que comparten componente exceden stock agregado y se rechaza todo sin caja ni movimientos; otro cajero no puede consumir stock ya utilizado; idempotencia no duplica kit/efectos. PHP aislado y clon MySQL para locking real.
- CA7 pendientes: guardar granel/kit sin mover stock; cambiar receta/casilla después no cambia snapshot guardado; cobrar consume composición original una vez con stock actual; faltante/componente eliminado rechaza sin cobro. Cancelar no mueve stock.
- CA8 devoluciones: granel parcial/sucesivo respeta cantidad y centavos; devolver 1 de 3 kits restituye 2 bujías y 1 filtro efectivamente debitados aun si cambió receta/casilla; kit apagado nunca incrementa padre/componentes; kit fraccionario no permitido; producto borrado no se recrea.
- CA9 legado/regresiones: precios/IVA/mayoreo/costo 7.50/códigos con ceros/símbolos/imagen/publicación no se modifican; artículos libres siguen iguales; limpieza conserva historia y snapshots; CSV no altera modo/receta de existentes; tickets/cortes antiguos legibles sin recálculo; permisos y CSRF se conservan.
- CA10 migración: instalación limpia y upgrade con productos/ventas/movimientos anteriores conservan valores/default unit/FKs; DECIMAL y cantidades negativas correctos en clon MySQL; down rechaza pérdida de datos. No asumir que SQLite :memory: demuestra precisión/locks MySQL.
- Ejecución prevista por archivos con php artisan test --compact <archivo>; JS con node --test tests/pos.test.js y archivo nuevo si se crea. Verificar conexión testing efectiva antes de correr; ninguna prueba se ejecuta en esta etapa. Tras implementar PHP, vendor/bin/pint --dirty --format agent; npm run build solo en fase de implementación/verificación autorizada.

## Riesgos, dependencias y antecedentes

- Bug activo en archivos afectados: bug-productos-costo-vacio-01 aún deja sin explicar la pérdida de un costo capturado 7.50. fix-productos-costo-vacio-01 normaliza vacíos/omitidos pero no declara resuelto ese incidente. No corregirlo incidentalmente ni ocultar pérdida con cero; agregar regresión de valor válido y abrir investigación separada si falla.
- fix-productos-limpiar-con-historial-01 está en fuente; migración/aprobación funcional/despliegue pendientes. Preservar product_snapshot, product_id nullable SET NULL, historial monetario, pendientes que rechazan producto eliminado y devoluciones sin recreación. Verificar esquema real antes de activar.
- fix-importar-csv-barcodes-01 corrigió fuente, recuperación de datos sigue pendiente; no reparar ni reimportar durante esta feature. Conservar búsqueda POS por barcode/barcode2, no por SKU.
- fix-preuso-motozone-lote3-01 agrega demanda y conserva líneas de precios diferentes; no perder estas protecciones al expandir kits.
- DECIMAL obliga a revisar casts, firmas, comparaciones estrictas, JSON, asserts y formateo; no resolver con floats acumulativos ni casts int. Migración de tablas grandes puede bloquear y requiere ventana operativa.
- Recetas editadas, catálogo borrado y seguimiento de inventario cambiado no deben reinterpretar efectos pagados. Snapshot versionado y ruta legacy son necesarios; no es un ledger nuevo completo.
- Costo del kit continúa siendo el costo propio capturado: utilidad no se recalcula automáticamente desde componentes, por conservación de campos/reglas existentes. La precisión de dinero sigue a centavos.
- No se inspeccionó BD real ni esquema productivo, no se verificó publicación y no se prometen compras/ajustes inexistentes. No dependencias externas nuevas, workers ni APIs.

## Plan de rollback

- Antes de activar: copia consistente de BD/archivos y restauración ensayada en clon; registrar versión y migraciones reales. Si falla antes de usar nuevas modalidades, revertir solo archivos/cambios propios y esquema nuevo cuando no haya datos dependientes, conservando modificaciones ajenas.
- Después de ventas decimales/kits: detener escrituras, no volver al código entero ni convertir DECIMAL a int. Preferir corrección hacia adelante con esquema y snapshots conservados. down debe bloquear si hay fracciones, recetas/snapshots o valores que se perderían.
- Restauración completa solo con autorización y conciliación de operaciones posteriores al respaldo; revertir código no reintegra automáticamente stock/dinero y no se borran ventas para permitir rollback. Desactivar temporalmente altas/ventas de modalidades nuevas si se necesita contener el fallo.

## Alternativas consideradas

- Columnas de modalidad y pivot de receta + snapshot JSON por línea: solución mínima coherente con snapshots existentes; no tabla extra de historial de componentes para un reporte no solicitado.
- Stock propio del kit además de componentes: descartado, contradice no doble descuento; kit apagado tampoco consume padre.
- Recalcular receta al cobrar pendiente: descartado por elección explícita; conservar composición/casilla guardadas.
- Cantidades float o solo cambiar HTML: descartado por errores/truncamientos; DECIMAL y milésimas enteras de cálculo físico.
- Kits anidados y conversiones de unidades: fuera de alcance confirmado; añaden recursión/ciclos que no resuelven el requerimiento mínimo.

## Aprobación de implementación

- Usuario en esta conversación: «Aplica el plan guardado en .memory/entities/plan-productos-modalidades-venta-01.md».
- Estado: APROBADO PARA IMPLEMENTACIÓN EN FUENTE. No autoriza Testing, migraciones de base real, restauraciones ni despliegue.
- Secuencia comunicada: preparar código/migraciones/cobertura sin tocar datos reales. Verificación de esquema, respaldo de BD restaurable, migraciones y aceptación requieren entorno y autorización separados.
- Ajuste técnico comunicado: soporte compartido Quantity y QuantityCast para operar físicamente en milésimas y conservar cantidades PHP enteras cuando sean integrales; sin dependencia nueva ni cambio de precisión acordada.
- Copia de archivos del workspace: C:/Users/Administrador/AppData/Local/Temp/opencode/motozone-modalidades-20261121-155951; no es un respaldo de base de datos ni una restauración verificada.
- Ajuste mínimo comunicado durante integración: proteger el guardado de la ficha con referencia original_stock frente a ventas simultáneas; retirar únicamente el recálculo automático del precio al abrir, manteniendo fórmulas y eventos de edición. Ambos evitan que editar modalidad/receta sobrescriba existencias vendidas o altere un precio sin intervención. Se añaden regresiones sin ejecutar; no se declara resuelto el incidente previo de pérdida de costo.

## Cierre de implementación en fuente

- Estado: IMPLEMENTADO.
- Implementado en: fix-productos-modalidades-venta-01.
- Cambios aplicados: selector persistido, receta y casilla, dos migraciones nuevas con cantidades DECIMAL/default unit/snapshots y rollback protegido; soporte de milésimas, consumo agregado transaccional, pendientes congeladas, devoluciones por efectos históricos y centavos acumulados; modal granel y carrito preciso; tickets/disponibilidad/reportes/compatibilidad legacy; cobertura añadida sin ejecutar.
- Desviaciones del plan original: soporte Quantity/QuantityCast compartido y salvaguardas de original_stock/precio inicial comunicadas arriba; ningún cambio de dependencias ni alcance a compras/conversiones/kits anidados. La implementación es en fuente: fases de Testing, respaldo/restauración de BD, validación MySQL, migración y publicación quedan pendientes de autorización/entorno.
- Verificación: Pint aprobado; sintaxis 33 PHP y 4 JS; 132 vistas Blade compiladas y lint correctos; rutas inspeccionadas; diff sin errores de whitespace; revisión estática independiente completada. No tests ejecutados ni datos reales tocados.
- Archivos reales y detalles: fix-productos-modalidades-venta-01. No se declara aceptada funcionalmente ni desplegada la feature; no volver al código entero después de registrar fracciones/kits.

- Aplicado en la BD MySQL local el 2026-11-21: respaldo verificado (31 tablas), migraciones 2026_11_21_155900 y 2026_11_21_155922 en batch 3, esquema y datos comprobados, portada y ficha HTTP 200 sin SQLSTATE. Detalle en fix-productos-modalidades-venta-01.
