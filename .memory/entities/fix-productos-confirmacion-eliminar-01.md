---
entity: fix-productos-confirmacion-eliminar-01
type: fix
---

- Objetivo: reparar el botón individual Eliminar de Productos, cuya confirmación inline quedaba truncada al parsearse como HTML; verificar los endpoints de limpieza/eliminación sin tocar productos reales.
- Archivos tocados: resources/views/admin/products/index.blade.php; tests/Feature/AdminProductTest.php. Registros en .memory/entities/plan-preuso-motozone-01.md y .memory/relations.md.
- Causa reproducida: el formulario contenía comillas literales con barra invertida dentro de un atributo HTML delimitado por comillas dobles. La barra no escapa comillas HTML. DOMDocument obtiene un onsubmit truncado, incluso antes de evaluar un nombre con comillas/apóstrofos. El problema anterior de stockMovements inexistente ya estaba corregido y no se volvió a modificar.
- Cambios aplicados: mensaje dinámico escapado en data-confirm, con comillas como &quot;, y handler fijo return confirm(this.dataset.confirm). Se conserva confirmación cancelable, POST+DELETE, CSRF, ruta, permisos y rechazo del servidor para productos con ventas/movimientos. No se modificaron controladores, modelos, reglas de borrado ni limpiar inventario.
- Estado: IMPLEMENTADO y verificado con pruebas dirigidas.
- Aislamiento comprobado antes de pruebas: APP_ENV=testing, DB_CONNECTION=sqlite, DB_DATABASE=:memory:, DB_URL vacío, conexión efectiva SQLite :memory:, configuración no cacheada. Variables forzadas por proceso; no se editaron .env ni phpunit.xml ni se accedió a la BD motozone.
- Baseline: 6 pruebas existentes correctas, 65 aserciones; comprueban limpieza a cero, preservación del catálogo/historial, rollback de ajustes, rechazo sin permiso y borrado individual sin historial/rechazo con movimientos.
- Regresión roja: test_product_delete_confirmation_preserves_names_with_quotes falló antes del cambio; DOM parseado devolvió handler cortado en lugar de confirmación completa.
- Regresión verde: 7 pruebas correctas, 69 aserciones, tras corregir el formulario. PHPUnit filtrado en AdminProductTest; sin suite completa.
- Verificación adicional: php vendor/bin/pint --dirty --format agent passed; php -l de AdminProductTest correcto; git diff --check correcto. No se ejecutó build ni se modificó historial Git.
- Desviación comunicada: el fallo estaba en el marcado del botón y no en destroy(); ajuste mínimo de caller de borrado registrado en plan-preuso-motozone-01 antes de editar. Sin ampliar la política de eliminación.
- Respaldo previo: C:/Users/Administrador/AppData/Local/Temp/opencode/motozone-productos-eliminar-20261117-204239/index.blade.php y AdminProductTest.php. Rollback solo de cambios propios; no descartar cambios ajenos.
- Limitaciones: se verificó HTML parseado y endpoints en SQLite, no clic real en navegador ni concurrencia MySQL. Limpiar inventario continúa poniendo existencias a cero y conservando productos/historial; no implementa borrado masivo. El producto con historial no se elimina por diseño.
- Fecha: 2026-11-17.
