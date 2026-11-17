---
entity: fix-preuso-motozone-lote1-01
type: fix
---

- Objetivo: aplicar únicamente el lote 1 de plan-preuso-motozone-01, evitando borrados de historial al limpiar inventario (#1) y bajas parciales de empleados (#2).
- Estado: IMPLEMENTADO Y VERIFICADO en pruebas aisladas SQLite :memory:. El resto del plan no está implementado.
- Aprobación: usuario solicitó explícitamente leer el plan, implementar y verificar lote 1, detenerse antes del siguiente y excluir #18.
- Archivos tocados: app/Http/Controllers/Admin/ProductController.php; app/Http/Controllers/Admin/UserController.php; tests/Feature/AdminProductTest.php; tests/Feature/EmployeePermissionsTest.php. También se actualizaron entidades y relaciones de .memory/ para trazabilidad.
- Cambio #1: clearInventory recibe Request y en transacción bloquea productos no vacíos en orden de ID, cambia únicamente stock a cero y agrega StockMovement TYPE_ADJUSTMENT con autor, cantidad y stock antes/después. Conserva catálogo, precios, IDs, ventas, partidas, movimientos anteriores, devoluciones y cortes. Actualiza mensaje de éxito acorde con la operación. No ajusta nuevamente productos ya en cero.
- Cambio #2: destroy recarga/bloquea empleado en transacción, valida rol y detecta devoluciones solicitadas para ventas ajenas antes de escribir. Devuelve error de validación comprensible para esa referencia restrictiva. Conserva la política de baja física previa, pero las eliminaciones y forceDelete son atómicas; excepción o veto del evento final revierten todo.
- Pruebas añadidas: preservación de catálogo/historial y ajustes para admin y empleado autorizado; producto ya en cero; rollback de limpieza al fallar segundo ajuste; denegación sin permiso; baja con historial propio; bloqueo por requested_by ajeno en pending/approved/rejected; rollback ante excepción final y ante forceDelete cancelado.
- Baseline antes de editar: AdminProductTest + EmployeePermissionsTest + AdminUserTest: 37 pruebas, 226 aserciones, todas correctas.
- Regresión antes del fix: 11 casos nuevos, 9 fallos y 2 correctos; reprodujeron pérdida/bloqueo y ausencia de rollback sin tocar datos reales.
- Regresión después del fix: 11 casos nuevos y 93 aserciones correctos.
- Verificación final: php vendor/bin/phpunit --do-not-cache-result tests/Feature/AdminProductTest.php tests/Feature/EmployeePermissionsTest.php tests/Feature/AdminUserTest.php: 48 pruebas y 319 aserciones correctas. APP_ENV=testing, DB_CONNECTION=sqlite, DB_DATABASE=:memory:, DB_URL vacío, sesión/cache en array; no había bootstrap/cache/config.php.
- Formato: php vendor/bin/pint --dirty --format agent correcto. git diff --check correcto. Revisión independiente de solo lectura sin defectos nuevos ni desviaciones de lote detectadas.
- Desviaciones del plan original: ninguna funcional. No hizo falta modificar la vista de productos: su confirmación ya prometía poner existencias en cero. Se verificó solo el lote autorizado, no la suite global ni los otros lotes.
- Exclusiones respetadas: resources/views/catalog/show.blade.php (#18) sin diff; sin cambios de responsividad ni de otros hallazgos; sin migraciones, dependencias, build o cambios de historial Git.
- Copia de rollback previa: C:\Users\Administrador\AppData\Local\Temp\opencode\motozone-preuso-lote1-20261116-214806 (los cuatro archivos de aplicación/pruebas).
- Limitaciones: SQLite no prueba bloqueos concurrentes MySQL/MariaDB. La baja exitosa mantiene el borrado de historial de la política anterior, no una política nueva de conservación. No se recuperan datos que se hayan borrado antes de este fix. Testing integral, concurrencia real y validación de producción requieren solicitud/entorno explícitos.
- Fecha: 2026-11-16.
