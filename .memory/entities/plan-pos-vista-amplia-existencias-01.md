---
entity: plan-pos-vista-amplia-existencias-01
type: plan
---

- Tipo: feature.
- Objetivo: simplificar el punto de venta, ampliar y estabilizar Detalle de la venta, mostrar resultados de productos como la referencia y consultar existencias por ubicación con ampliación de foto.
- Estado: PROPUESTO; pendiente de solicitud explícita para implementar. Las confirmaciones recibidas son del alcance y del comportamiento del inventario, no autorización para aplicar.
- Fecha: 2026-11-22.

## Verificación inicial y antecedentes

- Skills disponibles: deploying-to-cloud, laravel-best-practices, opencode, ponytail, ponytail-audit, ponytail-debt, ponytail-gain, ponytail-help, ponytail-review, report, tailwindcss-development, testing-best-practices. Aplican Ponytail, Laravel, Tailwind y pruebas, activadas en la sesión; el resto no aplica.
- No existe .ai/rules; se comprobó al intentar leer su índice. No hay reglas de esa carpeta que buscar o aplicar.
- Descubrimiento previo en esta sesión no expuso memory_* ni codesearch_*. No se dispone de search_nodes/open_nodes/add_observations/create_entities/create_relations/set_importance. Se usa respaldo .memory/ y lectura/búsqueda local glob/grep/read; no hay análisis semántico automático de impacto.
- composer show --direct verificó Laravel 13.38.0 y PHPUnit 12.5.40; package.json declara Tailwind ^4.3.3 y Vite ^8.0.0. Sin dependencias nuevas.
- Se revisaron .memory/relations.md, entidades relacionadas, formularios, modelo, request, controladores, JS y pruebas actuales. No se ejecutaron pruebas, build, migraciones, escrituras de BD ni operaciones de historial Git. Únicas escrituras de esta sesión: memoria de planificación.
- Antecedente: plan-productos-modalidades-venta-01/fix-productos-modalidades-venta-01 ya incorporaron granel, kits y disponibilidad derivada. Mantener esas reglas.
- Antecedente pendiente: plan-pos-modal-granel-bidireccional comparte la vista, JS y pruebas del POS. Integrar contra la versión vigente sin sobrescribirlo ni implementar sus cambios no solicitados como parte de este plan.
- Antecedente: plan-quitar-categorias-01 está implementado. Categories y category_id se conservaron; no recrear CRUD ni selector. Mostrar categoría existente o celda vacía.
- Bug activo en archivos afectados: bug-productos-costo-vacio-01 mantiene pendiente el diagnóstico de pérdida del costo 7.50 pese a fix-productos-costo-vacio-01 de normalización. No declararlo resuelto ni cambiar cost/min_stock/precios durante esta feature.
- Bugs relacionados: importación CSV y códigos tienen diagnóstico/recuperación histórica pendientes en memoria; no modificar importación ni reparar datos en esta feature.

## Alcance y fuera de alcance

- Alcance: cambios visuales exclusivos del POS, metadata de producto necesaria para mostrarlos, dos cantidades opcionales en Productos, una migración aditiva y pruebas dirigidas.
- Fuera de alcance: gestión de sucursales, traspasos, descuentos automáticos de bodega o Tienda 2, nuevas dependencias, recuperar CRUD de categorías, modificar cálculos de IVA/cobro/tickets, CSV de campos nuevos, correcciones de bugs anteriores o rediseños ajenos.

## Decisiones funcionales confirmadas

1. Existencia en tienda reutiliza stock actual y availableStock(), sin crear stock de tienda duplicado ni reasignar datos. Venta/devolución conservan sus movimientos actuales; kits conservan disponibilidad derivada y productos sin control no inventan una cantidad.
2. Nuevos campos warehouse_stock y store_2_stock: cantidades manuales opcionales de consulta, no se suman al vendible ni se descuentan al cobrar. Alta y edición comparten el formulario existente.
3. Vacío se guarda como NULL, nunca se convierte a cero. Cero capturado se muestra. En tablas, celda vacía para valor nulo; Tienda 2 es columna condicional solo si al menos una fila visible tiene valor no nulo, incluido cero.
4. Categoría se lee de la relación existente, sin crear campos ni módulos adicionales. Productos sin categoría quedan con celda vacía.
5. Retirar Acción corresponde a los resultados de búsqueda de la referencia. La selección se hace pulsando nombre/código de producto, accesible también por teclado, sin botón Agregar. Conservar Quitar del carrito para corregir una venta, no eliminarlo por confusión con Acción del buscador.

## Pasos de implementación

1. Antes de implementar, revisar modificaciones actuales de los archivos para preservar trabajo ajeno y conciliar el plan previo de granel. Confirmar esquema del entorno elegido y respaldo antes de autorizar una migración real; esta planificación no la ejecuta.
2. Crear migración nueva que añada únicamente warehouse_stock y store_2_stock como DECIMAL(18,3) nullable sin backfill. No editar migraciones anteriores ni tocar stock. Agregar fillable y casts que respeten NULL, preferiblemente decimal:3 del framework; no usar QuantityCast::set para NULL porque invoca Quantity::milli(null) y lo rechaza.
3. Extender ProductRequest con campos nullable y validación Quantity existente: no negativos, rango permitido, enteros para unidad/kit y hasta 3 decimales para granel. Persistir por lista permitida en productData. En edición, omitir conserva valor; enviar vacío lo limpia a NULL. No tocar normalización de cost/min_stock.
4. Renombrar Cantidad actual a Existencia en tienda y agregar Bodega/Tienda 2 al formulario compartido. Conservar old(), errores y permisos. Campos informativos fuera del fieldset que se deshabilita para kits: no constituyen stock propio operativo ni cambian su receta. Ajustar step al cambiar modalidad sin truncar cantidades.
5. Extender productPayload con category_name, URL de imagen construida por el servidor y las dos cantidades. Cargar category junto a kitItems en búsqueda y frecuentes, incluidos fallbacks, para evitar N+1. Conservar contratos actuales de precios, stock, búsquedas y autorización.
6. Preservar metadata en addProduct y renderCart. Al cargar pendientes, enriquecer mediante items.product y category solo foto/categoría/cantidades de consulta del catálogo disponible; no reemplazar nombre/precio/cantidad/modalidad/snapshots históricos ni habilitar su edición. Producto borrado o artículo libre: imagen y datos opcionales ausentes, sin fallar ni recrearlo.
7. Ocultar el aside administrativo y retirar lg:pl-64 solamente en pos.index. Conservar las demás pantallas y reutilizar el menú compacto de cabecera en escritorio POS para no dejar inaccesibles Productos/Caja/Perfil al ocultar sidebar. No cambiar permisos.
8. Retirar bloques Mi turno de hoy y Atajos de teclado; quitar segunda columna del POS. Mantener accesos autorizados en cabecera, haciendo visibles Pendientes/Devoluciones en escritorio y conservando acceso a Caja/Mi corte mediante menú compacto. No añadir nuevos paneles.
9. Ampliar Detalle de la venta al ancho disponible. En escritorio, zona de trabajo de altura estable respecto al viewport, carrito con scroll interno y encabezado visible; estado vacío dentro de la misma zona, sin sumar altura adicional. Total y botones no saltan al agregar líneas. Resultados de búsqueda en capa anclada y acotada bajo el filtro para no empujar el detalle. En móvil, contenido utilizable sin controles cortados y scroll horizontal de tablas cuando haga falta; no imponer un ancho fijo de escritorio.
10. Quitar tarjetas Subtotal e IVA del DOM y sus escrituras/referencias JS (renderTotals actualmente accede sin guardas a ambos nodos). Conservar totals(), total visible y política de IVA actual; no modificar servicios, tickets ni cálculos monetarios. Eliminar consulta/resumen today de PosController::index solo si deja de tener consumidores en la vista.
11. Reemplazar renderResults por tabla: foto, código, producto, categoría, precio unitario, cantidad, importe, existencia en tienda, existencia en bodega y Tienda 2 condicional. Código muestra barcode o barcode2 o SKU como fallback visual, sin ampliar el alcance de búsqueda. Sin columna Acción. Cantidad inicial 1 e importe según precio efectivo/mayoreo; entradas de cantidad no agregan accidentalmente al carrito. Pulsar nombre/código o activar su control por teclado agrega una sola vez con la cantidad indicada; escáner/Enter actuales siguen funcionando. Granel reutiliza su modal y conserva la cantidad propuesta al abrirlo, sin crear otro flujo de validación; kits conservan su disponibilidad derivada.
12. Añadir miniatura al carrito y un único dialog nativo reutilizando cierre existente. Clic/activación de miniatura abre foto completa con object-contain, título accesible, botón Cerrar y Escape. Restaurar foco; atajos globales ya se bloquean con dialog abierto. Foto ausente o rota muestra fallback, sin botón inútil. Escapar textos y atributos y usar URL generada por servidor; no insertar HTML arbitrario ni hacer clic de foto equivalente a agregar producto.
13. Ampliar pruebas existentes y, cuando se autorice su ejecución, correr cobertura dirigida PHP/JS, revisión manual de tamaños/foco y build. Formatear PHP modificado con Pint siguiendo AGENTS.md; publicación/migración real requieren autorización correspondiente.

## Archivos a tocar y razón

- resources/views/layouts/admin.blade.php: sidebar/margen condicional solo en pos.index y menú compacto accesible.
- resources/views/pos/index.blade.php: retirar paneles/tarjetas, aprovechar ancho, zona estable y diálogo de imagen.
- resources/js/pos/pos.js: renderResults/renderCart/addProduct/renderTotals/loadPendingSale, cantidad de resultado y metadata de imagen; mantener rutas de cobro y validación comunes.
- resources/views/admin/products/partials/form.blade.php: etiqueta Tienda y campos opcionales Bodega/Tienda 2 con repoblación.
- resources/js/admin/product-form.js: precisión de cantidades opcionales al cambiar modalidad, sin tocar cálculo de precio/costo.
- app/Http/Requests/Admin/ProductRequest.php: validación y autorización existente para campos nuevos.
- app/Http/Controllers/Admin/ProductController.php: persistencia explícita, omisión frente a limpieza a NULL.
- app/Models/Product.php: fillable/casts nullable de campos nuevos; reutilizar category/availableStock.
- app/Http/Controllers/PosController.php: payload de búsqueda/frecuentes/pendientes, eager loading de metadata y posible retirada de today sin consumidores.
- database/migrations/<timestamp>_add_optional_location_stocks_to_products_table.php (nuevo, nombre definitivo mediante Artisan): solo dos columnas nullable.
- tests/Feature/AdminProductTest.php: crear/editar/vaciar/omitir/validar opcionales, permisos y stock operativo intacto.
- tests/Feature/PosProductSearchTest.php: metadata, categoría/foto ausentes y cantidades NULL/cero; no reescribir el contrato de búsqueda previo.
- tests/Feature/PosAccessTest.php: presentación de POS sin afectar navegación de otras pantallas.
- tests/Feature/PosSalesTest.php: pendiente con foto opcional y venta que descuenta tienda pero conserva bodega/Tienda 2, sin cambiar precio histórico.
- tests/pos.test.js: adaptar DOM simulado a nuevos controles; tabla, cantidades, ausencia de tarjetas, foto y regresiones de flujos POS.
- tests/product-form.test.js: campos nuevos y cambio de precisión sin pérdida de valores.
- No se prevén cambios en SaleService, ReturnService, PricingService, rutas, tickets, importación, dependencias ni configuración.

## Criterios de aceptación verificables

- CA1: no aparecen los paneles Atajos de teclado, Mi turno de hoy, Subtotal ni IVA incluido en pos.index; total/cobro/IVA siguen siendo correctos y JS no falla por nodos eliminados.
- CA2: sidebar y margen lateral desaparecen solo en pos.index; las demás pantallas mantienen su navegación y el POS conserva salida/navegación compacta accesible.
- CA3: detalle amplio mantiene su tamaño al pasar de carrito vacío a lleno y al abrir/cerrar búsqueda; filas excedentes usan scroll interno en escritorio, sin desplazar total/botonera.
- CA4: resultados contienen columnas solicitadas sin Acción, foto/categoría opcionales y precio/importe coherentes con mayoreo/cantidad; seleccionar no duplica y editar cantidad no agrega.
- CA5: crear y editar restaura campos opcionales; nulos permanecen nulos y se ven vacíos, cero se ve, negativos/precisión inválida se rechazan y permisos se conservan. Tienda 2 no se muestra en una tabla sin valores para esa columna.
- CA6: vender reduce únicamente la existencia operativa actual; cantidades manuales de bodega/Tienda 2 no cambian ni permiten vender por encima del stock de tienda. Kits y productos sin control mantienen su semántica actual.
- CA7: clic o teclado en foto del carrito abre imagen y Cerrar/Escape la cierra, devuelve foco y no altera carrito ni dispara cobro; imágenes ausentes/rotas, artículos libres y productos borrados no rompen flujo.
- CA8: escáner, ambos códigos, precio autorizado, mayoreo, granel, kits, pendientes, cobro e impresión conservan comportamiento. En pendientes la metadata nueva no cambia los valores guardados de venta.
- CA9: comprobación manual en escritorio/móvil y con muchas filas verifica estabilidad, scroll, legibilidad y accesibilidad; pruebas de DOM simulado no prueban geometría real.

## Verificación prevista, no ejecutada

- PHP dirigido: AdminProductTest, PosProductSearchTest, PosAccessTest, PosSalesTest; ampliar a PosReturnsTest si las regresiones de inventario lo requieren. No reescribir pruebas ajenas para forzar verde.
- JS: node --test tests/pos.test.js tests/product-form.test.js.
- Formato: vendor/bin/pint --dirty --format agent mediante el ejecutable PHP adecuado al entorno.
- Frontend: npm run build y comprobación visual; no añadir navegador/dependencias de testing.

## Plan de rollback

- Retirar solo los cambios propios en vistas/JS/PHP, conservando trabajo ajeno, y recompilar assets al restaurar la UI anterior.
- Preferir conservar las dos columnas nullable al revertir código: el código anterior no las usa y sus datos no se pierden.
- Si se requiere eliminarlas, exportar primero los valores y verificar respaldo recuperable; quitar columnas pobladas es destructivo y requiere autorización específica. No hacer rollback masivo de otras migraciones ni restaurar toda la BD sobre ventas posteriores.
- No se recalculan ni revierten ventas/stock: la feature no modifica el motor de inventario ni contabilidad.

## Riesgos y dependencias externas

- Riesgo principal: interpretar Bodega/Tienda 2 como inventario automático cuando son consulta manual; conservar esta limitación visible en el formulario.
- NULL y cero no son equivalentes; no usar fallback || 0 ni el cast no nullable al persistir campos nuevos.
- No inferir existencia física de kits sumando bodega/componentes ni cambiar snapshot de pendientes por catálogo actual.
- Sidebar es layout compartido; condicionar a ruta exacta y preservar navegación evita afectar otras pantallas.
- Retirada de IDs subtotal/tax exige cambios coordinados en JS y pruebas; el bundle debe recompilarse para que la interfaz cambie.
- Categorías pueden no tener datos porque el módulo fue retirado; mostrar celda vacía es intencional, no crear categorías como parte de esta feature.
- Diagnóstico activo de costo y plan previo del modal de granel comparten archivos: preservar ambos, sin mezclar fixes ni ampliar funciones.
- Sin APIs, librerías, workers ni servicios externos nuevos. No se verificó BD real, despliegue, navegador ni pruebas ejecutadas.

## Alternativas consideradas

- Reutilizar stock actual + dos cantidades nullable: elegida explícitamente; mínimo cambio, sin duplicar inventario.
- Inventario por sucursal/ubicación con traspasos y descuentos: descartado por alcance y elección del usuario; requeriría otra feature.
- Rehacer categorías o instalar librería de zoom: descartado; relación conservada y dialog nativo cubren lo pedido.
- Altura del detalle dependiente del número de filas/resultados: descartada porque provoca los movimientos que el usuario quiere eliminar.

## Implementación aprobada y aplicada

- Aprobación explícita del usuario en esta conversación: «si esta bien aplicalo».
- Estado: IMPLEMENTADO.
- Entrega: código fuente local; pendiente de migración del entorno, build y Testing autorizado.
- Cambios aplicados: retirados paneles de turno/atajos y tarjetas subtotal/IVA, sin tocar cálculos; sidebar/margen omitidos solo en pos.index, menú compacto conservado; detalle a ancho completo y altura estable de escritorio con scroll interno y búsqueda superpuesta; resultados en tabla con cantidad/importe, foto/código/categoría y existencias; Tienda 2 condicional y selección sin Acción; foto del carrito en diálogo nativo con cierre/foco/fallback; campos opcionales warehouse_stock/store_2_stock, validación/persistencia nullable y migración aditiva; metadata de búsqueda/frecuentes/pendientes sin cambiar snapshots históricos.
- Archivos tocados: 16 archivos de aplicación/migración/pruebas, detallados en fix-pos-vista-amplia-existencias-01. Sin cambios en motor de ventas/devoluciones/precios, rutas, tickets, CSV o dependencias.
- Verificación: php -l en todos los PHP/Blade y migración modificados, node --check en ambos scripts y ambas pruebas JS, php vendor/bin/pint --dirty --format agent y git diff --check, todos correctos. Revisión de diff contra alcance y de referencias eliminadas; no quedan pos-subtotal/pos-tax en vista/JS ni resumen today en vista.
- Pruebas PHP/JS agregadas o adaptadas, NO ejecutadas: Testing requiere solicitud explícita. No se comprobó geometría ni interacción en navegador.
- Desviaciones del plan original: build previsto NO ejecutado por regla obligatoria de versiones; composer.json y package.json declaran 1.0.0, pero HEAD c09425a66edc1cf3b95079c911c2ee4083d844af no tiene tag exacto correspondiente. v1.0.0 existe en otra referencia. No se modifica versión ni historial; cuando se resuelva, construir exclusivamente mediante ./build.sh (script sin argumentos de versión). No se usó npm run build directamente.
- Migración creada: database/migrations/2026_11_22_113802_add_optional_location_stocks_to_products_table.php; NO aplicada a BD. Antes de usar el guardado de campos nuevos hay que aplicar el esquema en el entorno autorizado con respaldo.
- Riesgo previo preservado: search() permite SKU mientras una prueba antigua de PosProductSearchTest exige excluirlo; no se corrigió ni se declara suite verde.
- Fecha de implementación: 2026-11-22.

## Ajuste visual solicitado y aprobado posteriormente

- Usuario solicita explícitamente corregir dos fallos visuales de la implementación actual, sin ampliar funciones ni alterar lógica. Se aplica como ajuste del plan de feature ya aprobado, no como feature nueva.
- Sustituye el criterio anterior del buscador: columnas exactas Foto, Código, Producto, Categoría, Precio unitario, Existencia en tienda, Existencia en bodega y Existencia Tienda 2 condicional. Quitar Cantidad e Importe únicamente del buscador; conservarlas en Detalle de la venta. Selección por clic en cualquier parte de la fila y teclado.
- Bodega NULL/vacío muestra «-»; Tienda 2 vacía no habilita columna ni muestra valor, cero sí cuenta como registrado. Mantener modalidad granel/kits/mayoreo y agregar mediante addProduct existente.
- Detalle: sustituir altura dependiente de clases no compiladas por contenedor de tamaño fijo (cuerpo mínimo de 340px), desplazamiento interno, encabezados separados del mensaje vacío centrado y foto con modal existente. Altura estable vacía/con filas y en móvil/escritorio.
- Endpoint inspeccionado: productPayload ya incluye image_path, image_url, category_name, stock/available_stock, warehouse_stock y store_2_stock; eager loading category presente. Sin necesidad de cambiar backend.
- Causa de lista vieja/colapso local confirmada por assets del manifest: app-De4akJGp.js no contiene tabla ni product-image-dialog; CSS app-Bh2Yz9JU.css no contiene min-h-72 ni altura del detalle nueva. Sin public/hot. No se inspeccionó navegador/servidor publicado.
- Archivos en alcance del ajuste: resources/js/pos/pos.js, resources/views/pos/index.blade.php, tests/pos.test.js, tests/Feature/PosAccessTest.php y memoria. Preservar el resto de cambios previos.
- Build continúa bloqueado por tag desalineado; no construir, cambiar versiones/tags ni editar bundles generados manualmente. Usar exclusivamente ./build.sh cuando se resuelva la precondición.
- Rollback del ajuste: restaurar solo el diff de esta corrección en los cuatro archivos fuente/pruebas y recompilar con ./build.sh cuando esté autorizado; sin cambios de BD.
- Estado: IMPLEMENTADO.
- Cambios aplicados del ajuste: buscador reducido a columnas solicitadas y selección por fila/teclado, bodega vacía «-»/Tienda 2 condicional, viewport fijo 388px con encabezado48/cuerpo vacío340 centrado sin superposición y modal conservado. Implementado en fix-pos-vista-amplia-existencias-02.
- Desviaciones del plan original: ajuste funcional expresamente solicitado por el usuario reemplaza Cantidad/Importe del buscador; altura local explícita en lugar de utilidades dependientes de build. Compilación sigue detenida por tag desalineado. Sin cambios en lógica ni JSON existente.
- Verificación del ajuste: sintaxis PHP/JS, Pint y diff correctos; Testing/navegador NO ejecutados. El buscador y modal nuevos no llegarán al bundle cargado hasta resolver el bloqueo de ./build.sh.
