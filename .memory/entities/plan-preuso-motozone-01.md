---
entity: plan-preuso-motozone-01
type: plan
---

## Contrato de traspaso a Fix

- Tipo: bug-fix — Modo A. No es un plan de feature ni autoriza rediseño.
- Objetivo: corregir los 19 hallazgos de la auditoría autorizados por el usuario y los dos defectos de responsividad confirmados antes de dar MotoZone por terminado.
- Estado: PLAN PROPUESTO LISTO PARA FIX; implementación y pruebas pendientes. El usuario autorizó preparar el traspaso, no se aplicó código desde este agente.
- Estado actualizado: PARCIALMENTE IMPLEMENTADO. Lote 1 IMPLEMENTADO Y VERIFICADO en fix-preuso-motozone-lote1-01; lotes 2 a 6 pendientes. Esta observación sustituye el estado propuesto anterior, no declara completado el plan completo.
- Fecha: 2026-11-16.
- Entrada: bug-preuso-01 a bug-preuso-17, bug-preuso-19, bug-preuso-20, bug-preuso-r01 y bug-preuso-r02; cada uno relacionado con su hipotesis CONFIRMADA en .memory/entities/.
- Exclusión expresa: hallazgo #18, contactos ficticios/fallbacks de fichas, a cargo del usuario. No modificar resources/views/catalog/show.blade.php ni cambiar sus destinos/fallbacks como parte de este plan.
- Guardado: fallback .memory/ porque no están disponibles las herramientas MCP de memoria. codesearch tampoco está disponible; superficie y callers se validaron con búsqueda y lectura local.
- Skills aplicables: ponytail, ponytail-audit, laravel-best-practices, testing-best-practices y tailwindcss-development. .ai/rules no existe en esta revisión; volver a comprobarlo antes de implementar.
- Versiones verificadas: PHP requerido ^8.3, Laravel instalado 13.38.0, Spatie Permission 8.3.2, Dompdf Laravel 3.1.2, PHPUnit 12.5.40; Tailwind declarado ^4.3.3 y Vite ^8.0.0. No añadir dependencias.
- Evidencia: tres causas se reprodujeron aisladamente sin BD (TypeError de artículos libres, filtro employee booleano, validación agregada de stock). Las restantes se confirmaron trazando código/esquema/CSS. No se afirma reproducción HTTP, visual ni concurrente de todas ellas.
- Pasos: ejecutar los lotes siguientes en orden; en cada uno reproducir con prueba de regresión, corregir la causa mínima compartida y verificar antes de continuar. No aplicar todos los lotes en una modificación opaca.

## Antecedentes y conflictos

- Reutilizar convenciones y componentes actuales, transacciones Laravel, relaciones Eloquent y JSON totals de DailyCut; no crear capas genéricas, módulos nuevos ni dependencias para estos fixes.
- Memoria relevante: plan-caja-02, plan-fase-6/fix-cierre-dia-fase6, plan-permisos-04, plan-corte-historial-pdf-rediseno-01 y plan-home-ui-02. Sus marcas IMPLEMENTADO son antecedentes, no prueba actual de corrección.
- Bug previo en archivos afectados: bug-corte-reabrir-dia-01 aún dice causa confirmada, pero fix-corte-reabrir-dia-01 registra IMPLEMENTADO. Preservar reabrir corte propio, bloqueo del cierre global y aislamiento por empleado; no darlo por pendiente ni eliminar su comportamiento.
- El hallazgo #18 puede ser editado paralelamente por el usuario: revisar su diff antes de tocar HomePage/layout/contactos y no sobrescribirlo. #20 solo afecta visibilidad de burbujas con helpers existentes, no valores de contacto.

## Lote 0 — Preparación segura

1. Leer este plan, los bugs e hipótesis enlazados, AGENTS.md y reglas/skills aplicables vigentes. Inspeccionar cambios del usuario antes de tocar archivos.
2. Comprobar aislamiento efectivo de pruebas: APP_ENV=testing, SQLite :memory: y sin configuración cacheada de producción. No ejecutar RefreshDatabase, importaciones, limpiezas o migraciones sobre datos reales.
3. Ejecutar baseline de las pruebas relevantes y registrar fallos previos sin atribuirlos al fix. Tener una copia verificable de BD/archivos antes de probar operaciones destructivas fuera de fixtures.
4. Antes de cada lote seguir callers, rutas alternativas, requests, vistas, fixtures y exportadores; al modificar PHP ejecutar Pint sobre archivos cambiados según AGENTS.md. No cambiar formato de archivos ajenos.

## Lote 1 — Evitar pérdida de datos (#1, #2)

1. #1: reemplazar el borrado de catálogo/ventas por puesta a cero de existencias conforme a la confirmación. Mantener productos, SKU, precios, ventas, partidas, caja, devoluciones y cortes. Usar transacción y registrar el ajuste de inventario siguiendo el patrón de StockMovement existente; coordinar filas de producto con las ventas para no sobrescribir descuentos concurrentes.
2. #2: validar referencias que impiden eliminar al empleado antes de la primera escritura y envolver la operación completa en transacción. Si una restricción impide el borrado, devolver error comprensible sin perder ningún registro. No rediseñar la política actual de baja ni convertir eliminaciones en desactivaciones sin aprobación.
3. Probar el éxito de #1 con catálogo e historial, y el rechazo de #2 con requested_by en devolución de venta ajena; inyectar un fallo tardío para demostrar rollback completo.
- Archivos afectados: app/Http/Controllers/Admin/ProductController.php (clearInventory); resources/views/admin/products/index.blade.php (texto coherente si hace falta); app/Http/Controllers/Admin/UserController.php (atomicidad/errores); tests/Feature/AdminProductTest.php y EmployeePermissionsTest.php/AdminUserTest.php (regresiones).
- Criterios de aceptación: limpiar deja stock cero y conserva IDs/historial; una baja fallida no cambia cuenta ni operaciones; no ampliar permisos ni borrar cortes/auditoría inadvertidamente.

## Lote 2 — Aislamiento y estados cerrados (#5, #6, #7, #8)

1. #8: aplicar al exportador heredado el filtro obligatorio por propietario cuando falte pos.sales.day.any. Conservar filtros y autorizaciones actuales, y verificar que employee ajeno no elude la restricción.
2. #7: resolver pertenencia a cierre a partir de happened_at y el alcance del día, sin depender de sale_id. Compartir la misma restricción en los callers POS/admin; restaurar o archivar no debe habilitar borrado de un día cerrado. Respetar la diferencia entre corte generado y cierre efectivo.
3. #6: rechazar dentro de transacción, recargando y bloqueando la fila antes de comprobar pending, igual que aprobar. Solo una transición terminal puede confirmar y no debe haber movimientos duplicados.
4. #5: comprobar cierre al aprobar y coordinar aprobación/cierre usando bloqueo común de BD en las rutas que compiten; no basta exists fuera de la escritura ni una transacción sin bloqueo compartido. Revisar generación/versionado del corte y comprobar el caso sin corte global previo. Mantener la transacción de devolución con su inventario/caja.
5. Validar autorización y secuencias solicitar -> cerrar -> aprobar, manual sin sale_id -> cerrar -> restaurar -> borrar, y aprobar/rechazar con modelos cargados antes de la transición contraria.
- Archivos afectados: app/Http/Controllers/Admin/CajaController.php (exportador y guard de borrado); app/Http/Controllers/Pos/CashMovementController.php y app/Services/CashMovementService.php (callers equivalentes); app/Models/CashMovement.php (guard); app/Services/ReturnService.php (transiciones); app/Http/Controllers/Pos/SaleReturnController.php y Pos/DailyCutController.php (aprobación/cierre); app/Services/DailyCutService.php y app/Models/DailyCut.php solo donde el protocolo compartido de cierre lo requiera.
- Pruebas afectadas: tests/Feature/PosCashHistoryFiltersTest.php, PosCashArchiveTest.php, PosReturnsTest.php y PosCutTest.php; extender fixtures existentes.
- Criterios de aceptación: usuario limitado no exporta registros ajenos por ninguna URL; movimiento manual de día cerrado no se elimina; devolución aprobada nunca termina rechazada con movimientos persistidos; día cerrado no acepta aprobación posterior.
- Dependencia de verificación: SQLite no demuestra exclusión de filas concurrentes. Probar intercalados con MySQL/MariaDB de pruebas aislado y autorización de acceso. Si hace falta una infraestructura de bloqueo nueva o no se puede garantizar el caso sin filas existentes, detener ese paso y presentar decisión técnica antes de implementarla; no introducirla a escondidas.

## Lote 3 — Cobro, artículos y filtros (#10, #11, #14, #15, #17)

1. #10: filtrar IDs nulos antes del mapeo tipado y conservar la distinción is_custom en creación, representación de pendientes y cobro. Excluir libres de bloqueo/validación/descuento de productos; validar nombre/precio condicionalmente y preservar totales/ticket. No resolver solo el primer TypeError dejando roto chargePending.
2. #15: validar cantidades acumuladas por product_id bajo los bloqueos existentes tanto en venta directa como cobro pendiente; conservar partidas/precios cuando sean diferentes. No fusionar artículos libres por identificador nulo.
3. #14: leer y validar explícitamente employee para el filtro de ventas del día, sin usar el booleano pasado por when como ID. Mantener aislamiento no administrador.
4. #11: al elegir tarjeta/transferencia usar el total coherente con el contrato del backend y recalcular confirmación/cambio. Revisar apertura, reapertura, cambio de método y venta pendiente; conservar comportamiento de efectivo y sus pagos insuficientes.
5. #17: impedir atajos globales destructivos mientras haya cualquier diálogo abierto o se esté editando un campo. Escape cancela el diálogo sin resetear carrito; mantener el flujo explícito de vaciado y sus confirmaciones.
- Archivos afectados: app/Services/SaleService.php (IDs, cantidades y pendientes); app/Http/Requests/Pos/StoreSaleRequest.php (validación condicional y estructura); app/Http/Controllers/PosController.php (payload pendiente); app/Http/Controllers/Pos/SaleController.php (employee); resources/js/pos/pos.js (pagos/atajos); resources/views/pos/index.blade.php solo si necesita atributos de diálogo.
- Pruebas afectadas: tests/Feature/PosSalesTest.php, PosPaymentMethodsTest.php y tests de detalle/ticket pertinentes. Sin nuevas librerías JS o navegador automatizado no instalado; verificar los handlers en navegador como requisito separado.
- Criterios de aceptación: libre solo/mixto con catálogo se vende y se cobra pendiente sin stock propio; stock=5 y cantidades 3+3 reciben 422 y no alteran caja/stock; filtro usa empleado distinto de ID 1; elegir card/transfer envía y registra la venta; Escape no pierde el carrito.
- Regresión transversal: idempotencia, precio autorizado/mayoreo, autorización de tickets y bloqueo/reapertura del corte propio no deben romperse.

## Lote 4 — Exactitud de caja y PDF (#3, #4, #9)

1. #3: separar total comercial y efectivo físico. Filtrar los movimientos de ventas por el método de la venta asociada y los pagos a proveedor por method=cash al calcular efectivo; conservar trazabilidad de los movimientos electrónicos ya existentes y el total general de proveedores. No eliminar movimientos históricos para ocultar el desfase ni modificar snapshots previos.
2. #4: atribuir operación física al cobrador/aprobador registrado en movimientos existentes, de forma coherente entre paneles, sin cambiar sale.user_id ni borrar la autoría original. Mantener las cifras comerciales etiquetadas como tales y el desglose/should_have alineados con la responsabilidad de caja. Revisar listas de empleados con actividad y generation de cortes para no omitir responsables.
3. Probar el caso A crea pendiente/B cobra efectivo, y A vende/B aprueba devolución, comparando cortes de A, B y global, no solamente el global. Probar proveedor electrónico con caja física intacta y venta electrónica que no añade efectivo.
4. #9: PDF de versión usa snapshot.totals y las mismas claves/fórmulas que la vista correspondiente. Para versiones futuras, persistir el desglose necesario en el JSON del snapshot al generarlo, de forma consistente con sus totales; reutilizar almacenamiento existente, sin migración inicial.
5. Para un snapshot antiguo sin desglose persistido, mostrar los totales que realmente contiene y avisar que no dispone de desglose histórico. Nunca reconstruir una versión pasada con actividad actual ni inventar datos; conservar vista previa/descarga e histórico global.
- Archivos afectados: app/Services/DailyCutService.php (métodos build, generate/storeVersion, employeeBreakdown y sumas); app/Models/Sale.php/CashMovement.php/SaleReturn.php solo si hace falta precisar relaciones existentes; app/Services/SaleService.php y ReturnService.php solo para coherencia del contrato de movimiento, sin duplicar registros; app/Http/Controllers/Pos/DailyCutController.php (datos PDF); resources/views/pos/cuts/pdf.blade.php y las vistas index/print/consolidated/partials que consuman las claves tocadas.
- Pruebas afectadas: tests/Feature/PosCutReconciliationTest.php, PosPaymentMethodsTest.php, PosCutTest.php, PosReturnsTest.php, PosCutSupplierPaymentsDetailTest.php y PosBitacoraTest.php; añadir prueba de contenido del PDF/su plantilla y estabilidad v1 tras generar v2.
- Criterios de aceptación: venta card/transfer de $100 deja ambos paneles de efectivo en $0 sin otros movimientos; proveedor transfer $200 no reduce los $500 físicos; cobrador y aprobador no producen diferencias artificiales entre empleados; suma de efectivo por empleado coincide con global; PDF v1 no cambia al registrar actividad posterior; PDF individual no sale vacío por ausencia del desglose global.
- Límite explícito: payment_method=mixed no guarda reparto efectivo/electrónico. No inventar ese reparto, presentar todo como efectivo ni afirmar validado. Si el uso real requiere cobro mixto desglosado, pedir la regla y autorización de diseño en sesión separada antes de añadir campos o ampliar funcionalidad. Registrar ese subcaso como pendiente, no marcar todo medio de pago validado.

## Lote 5 — Importación, borrado y permisos (#12, #13, #16)

1. #12: limitar formulario/servidor a CSV/TXT realmente soportado; validar encabezados y filas con reglas compatibles con ProductRequest, incluido segundo código y unicidad cruzada si está presente. Normalizar vacíos a null y envolver toda la carga en transacción; ante error informar fila/campo y no guardar carga parcial. Mantener la política actual documentada para omitir SKU existente, sin cambiarla silenciosamente.
2. #13: definir stockMovements(): HasMany en Product siguiendo saleItems; probar borrado sin historial y rechazo cuando existen ventas o movimientos.
3. #16: incluir acceso POS en el módulo cut para permitir sus rutas, sin conceder pos.sell. Verificar usuario nuevo con solo ese módulo y revocación posterior.
- Archivos afectados: app/Http/Controllers/Admin/ProductController.php (import/destroy); app/Http/Requests/Admin/ProductRequest.php (reglas a reutilizar; no extraer abstracción sin necesidad); resources/views/admin/products/index.blade.php (formatos/ayuda); app/Models/Product.php (relación); app/Models/User.php (módulo cut); requests/seeders de empleados solo si son callers que deban alinearse.
- Pruebas afectadas: tests/Feature/AdminProductTest.php, EmployeePermissionsTest.php, AdminUserTest.php y PosAccessTest.php/PosCutTest.php.
- Criterios de aceptación: dos SKU sin barcode importan con null; error en fila posterior no deja filas previas nuevas; Excel se rechaza claramente; códigos duplicados se informan antes de una excepción SQL; producto nuevo se elimina sin 500 y producto con historial no; empleado solo cut accede a su corte pero no vende ni consulta cortes ajenos.

## Lote 6 — Página pública y responsividad (#19, #20, R01, R02)

1. #19: HomeCard.destination no debe enlazar un producto inactivo; mantener fallback existente solo cuando sea válido, sin reintroducir catálogo eliminado ni exponer ficha desactivada. Probar desactivación posterior al vínculo.
2. #20: respetar show_whatsapp_bubble/show_email_bubble con helpers actuales y destinos válidos; comprobar elementos reales y etiquetas actuales, no textos obsoletos. No tocar valores/fallbacks de ficha reservados al #18.
3. R01: hacer accesibles Pendientes y Devoluciones por navegación compartida/móvil o ubicación visible existente bajo xl, con las mismas condiciones de permiso. Evitar duplicados de navegación en escritorio y no conceder permisos nuevos.
4. R02: limitar lg:w-72 al layout horizontal que lo necesita; en cards verticales/estrechas imagen y controles deben ocupar el ancho disponible. Reutilizar carousel, sin sustituirlo ni cambiar librería.
5. Validar visualmente a 320, 375, 768, 1024 y 1280/1440px: inicio, ficha de producto sin modificar #18, POS, pendientes, devoluciones, productos, usuarios, caja y cortes. Comprobar zoom, textos largos, desplazamiento de tablas local, navegación por teclado y controles táctiles.
6. Comprobar diálogo de cobro a 320x568 y 375x667, con teclado virtual abierto, y también caja/artículo libre/iniciar día. Su overflow-hidden y centrado son un riesgo, no un recorte reproducido. Si se demuestra recorte, registrar bug/hipótesis confirmada y ampliar este plan antes de aplicar su fix; no marcarlo verificado por lectura CSS o por node --check.
- Archivos afectados: app/Models/HomeCard.php (destino); resources/views/layouts/site.blade.php (burbujas); resources/views/layouts/admin.blade.php y resources/views/pos/index.blade.php (accesos); resources/views/home.blade.php (ancho de cards); resources/css/app.css y resources/views/components/carousel.blade.php solo si el contenedor mínimo no basta; resources/js/app.js solo si hay comportamiento de navegación que deba alinearse.
- Pruebas afectadas: tests/Feature/HomeCardTest.php, HomeTest.php, HomeFamilyTest.php y PosAccessTest.php; pruebas visuales manuales registradas por viewport/permiso. Mantener CatalogTest existente sin cambiar expectativas del #18.
- Criterios de aceptación: ningún enlace a ficha inactiva; burbujas respetan cada combinación de interruptor/destino; los accesos siguen visibles/utilizables en móvil/tablet; ninguna tarjeta recorta flechas por ancho rígido; los controles críticos del cobro son alcanzables con teclado móvil o se reporta bloqueo pendiente.

## Verificación final y entrega

- Repetir pruebas de regresión después de cada cambio, primero archivo/filtro concreto con php artisan test --compact; al terminar ejecutar suite completa únicamente en entorno aislado. Registrar resultados reales, no solo número de métodos encontrados.
- Ejecutar vendor/bin/pint --dirty --format agent cuando cambie PHP; inspeccionar que no toque archivos previos ajenos y seguir AGENTS.md vigente.
- Ejecutar npm run build tras cambios frontend y comprobar manifest/assets y ausencia de public/hot en paquete de producción. No ejecutar build.sh directamente sobre instalación usada por empleados sin autorización, porque incluye npm ci.
- PHP lint y node --check son comprobaciones complementarias, no sustituyen pruebas HTTP/JS/visual.
- No incorporar Dusk, Playwright, nuevas dependencias o cambios de infraestructura sin aprobación. Si Fix no dispone de navegador o MySQL de pruebas, reportar esas validaciones como pendientes y solicitar acceso; no declarar listo para producción.
- Entregar matriz por hallazgo: pendiente/en curso/corregido/verificado/bloqueado, archivos cambiados, prueba concreta y resultado; separar siempre exclusión #18 y riesgo móvil no reproducido.
- Cierre: los 19 hallazgos y R01/R02 deben tener regresión pertinente, sin datos perdidos ni ampliación de permisos; reabrir día propio/idempotencia/versionado/tickets deben conservarse. El entorno de producción requiere validación aparte.

## Plan de rollback

- Antes de cada lote, conservar copia de los archivos que realmente vaya a cambiar y documentar su estado previo, sin sobrescribir modificaciones del usuario.
- Si falla un lote, detener la ejecución y restaurar solo sus archivos desde esa copia o artefacto anterior conocido. No modificar historial Git ni revertir trabajo ajeno.
- No hay migración de esquema prevista: usar JSON/relaciones existentes. Si Fix necesita una, debe justificarla, presentar alternativas y obtener aprobación antes de ampliar el plan.
- No borrar ni recalcular silenciosamente snapshots, ventas o movimientos anteriores para revertir. La reparación de datos históricos potencialmente erróneos requiere análisis separado y copia de BD; una restauración puede perder operaciones posteriores y exige confirmación explícita.
- Validar el rollback en datos de prueba; nunca probar una baja/limpieza con datos reales para comprobar este plan.

## Riesgos, dependencias y alternativas

- Riesgos: datos históricos ya borrados no se recuperan con código; snapshots antiguos pueden carecer de desglose; los cierres/versiones/concurrencia necesitan motor real aislado; las pruebas actuales de pago HTTP no verifican eventos JS; SQLite no demuestra locks MySQL.
- Dependencias: navegador para comprobación visual/teclado móvil, entorno de prueba equivalente al motor productivo para concurrencia y autorización antes de usar datos/infraestructura reales. No se necesita servicio externo nuevo para los fixes confirmados.
- Alternativa rechazada: arreglar solo UI/confirmaciones; deja endpoints y causas destructivas abiertos.
- Alternativa rechazada: regenerar todo el historial con datos actuales; destruye la fidelidad de versiones.
- Alternativa rechazada: instalar un parser Excel o una suite de navegador nueva sin solicitud; CSV existente y validación con herramientas disponibles cubren el alcance sin dependencia adicional.
- Decisiones que requieren detenerse si aparecen: nuevo reparto de mixed, nueva infraestructura de exclusión mutua, cambios de política de baja o nuevas tablas/migraciones. No inventar estas decisiones ni mezclar fixes con features.

## Ejecución autorizada — lote 1 (2026-11-16)

- Aprobación explícita en este chat: implementar y verificar lote 1 (#1 y #2), detenerse antes de continuar, excluir #18.
- Estado lote 1: IMPLEMENTADO; verificación dirigida: 48 pruebas / 319 aserciones correctas, frente a baseline 37 / 226. Pint y diff --check correctos.
- Cambios aplicados: limpieza de stock con ajustes auditables sin borrado de catálogo/historial; baja de empleados con prevalidación de FK y transacción/rollback completo ante error o veto final.
- Archivos de aplicación/pruebas: app/Http/Controllers/Admin/ProductController.php; app/Http/Controllers/Admin/UserController.php; tests/Feature/AdminProductTest.php; tests/Feature/EmployeePermissionsTest.php.
- Desviaciones del plan original: ninguna funcional; vista de productos no modificada porque la confirmación ya coincide con la puesta a cero. No se aplicó ningún lote posterior ni #18.
- Limitaciones pendientes: concurrencia MySQL real y Testing integral no ejecutados. No se afirma que la aplicación completa esté lista para producción.
- Implementación: fix-preuso-motozone-lote1-01, relacionada mediante implementado_por.

## Continuación autorizada — 2026-11-16

- Usuario autoriza continuar los lotes pendientes automáticamente al terminar/verificar cada uno, manteniendo exclusión #18. No autoriza inventar decisiones fuera de los límites del plan.
- Estado actualizado: lotes 1 y 2 implementados, lote 2 con 64 pruebas / 251 aserciones integradas correctas. Concurrencia MySQL real pendiente. Lotes 3 a 6 pendientes en este punto.
- Cambios lote 2: ver fix-preuso-motozone-lote2-01. Reutilización de tabla cache_locks existente con transacción en conexión de locks, sin migración/infraestructura nueva.
- Gate de build detectado antes de frontend: composer.json/package.json no declaran versión de aplicación y git tag --points-at HEAD no devuelve tag. No ejecutar build.sh ni scripts alternativos hasta resolver la versión/tag requeridos por instrucciones de construcción. Esto no impide editar/verificar código fuente; impide entregar assets reconstruidos como verificados.
- Estado actualizado: lotes 1 a 3 implementados. Lote 3: 34 pruebas / 201 aserciones backend y 8 pruebas Node correctas. Validación visual, concurrencia real y build siguen pendientes; se continúa con lote 4.

## Ejecución autorizada - lotes 4 a 6 (2026-11-16)

- Aprobación vigente: el usuario autorizó continuar automáticamente los lotes pendientes; se mantiene la exclusión expresa del #18.
- Lote 4 (#3, #4, #9): IMPLEMENTADO en fix-preuso-motozone-lote4-01. 60 pruebas / 246 aserciones; PosCutPdfTest 16/70 con Dompdf real. `mixed` sigue sin reparto inventado y marcado como no conciliado.
- Lote 5 (#12, #13, #16): IMPLEMENTADO en fix-preuso-motozone-lote5-01. 61/62 pruebas / 372 aserciones; único fallo preexistente de PosAccessTest. Importación 6/6. Se conserva la política de omitir SKU existente.
- Lote 6 (#19, #20, R01, R02): IMPLEMENTADO en fix-preuso-motozone-lote6-01. 55 pruebas / 308 aserciones en Home/Card/Catalog/PosAccess; 49 correctas. Pasan la prueba de burbujas y la nueva de producto desactivado. Fallos restantes preexistentes: 3 de HomeTest por copia obsoleta, 2 de CatalogTest dependientes del #18 y 1 de PosAccessTest por texto del carrito.
- Pendientes que no bloquean el código pero impiden declarar producción: validación visual 320/375/768/1024/1280px, teclado virtual del diálogo de cobro, concurrencia con MySQL/MariaDB real y build/versionado (sin versión de app ni tag que apunte a HEAD).
- Estado de datos: no se tocaron snapshots, ventas ni movimientos históricos; no se ejecutaron migraciones. Todos los cambios siguen sin commit, por indicación del usuario y de las reglas del agente.
- Cierre: los 18 hallazgos autorizados más R01/R02 quedan aplicados. El #18 permanece a cargo del usuario y no se modificó `resources/views/catalog/show.blade.php`.

## Verificación de cierre - 2026-11-16 (concurrencia real y responsividad)

- Concurrencia real MySQL: verificada sobre MySQL 8.0.43 en la BD aislada `motozone_preuso_test` (no se usó `motozone`). Dos procesos simultáneos de `DailyCutService::withDayLock` con sección crítica de 8s no se solapan (B entra al salir A, overlap=False). Pruebas dirigidas sobre MySQL: 33/33, 179 aserciones.
- Responsividad: verificada con Chrome headless + CDP en 320/375/768/1024/1280/1440. Portada sin desbordamiento horizontal; burbujas WhatsApp/correo con aria-label; carruseles contenidos; R02 sin recorte a 1024. R01 correcto por rol y por ancho. Detalle en fix-preuso-motozone-lote6-01.
- Bug real corregido en la revisión visual: /pos expandía el layout a 519px en 320/375px por un `sr-only` absoluto fuera del scroller del carrito; se añadió `relative` al scroller y `min-w-0` a la columna izquierda. Ahora 320/375 exactos, overflow 0. Re-verificación PosAccessTest+PosSalesTest: 20 pruebas / 101 aserciones, 19 correctas (fallo preexistente de texto del carrito). Pint passed.
- Gate de build sigue abierto: no hay versión de aplicación en composer.json/package.json ni tag git que apunte a HEAD, y no se pueden crear tags (regla de no modificar historial). No se ejecutó `build.sh` ni `npm run build`. El CSS compilado actual es anterior al lote 6 (le falta `xl:hidden`), por lo que la responsividad de R01 se validó inyectando esa utilidad solo para la medición; hasta un build real esa clase no existirá en producción.
- Pasos que debe ejecutar el usuario: fijar la versión de app (composer.json/package.json) y crear el tag `vX.Y.Z` sobre HEAD, y después ejecutar `./build.sh`. Hasta entonces no se puede entregar un build verificado.
