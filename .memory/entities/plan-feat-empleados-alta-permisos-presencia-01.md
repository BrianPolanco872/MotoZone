---
entity: plan-feat-empleados-alta-permisos-presencia-01
type: plan
---

- Tipo: feature
- Objetivo: Plan técnico para alta de empleado con módulos en un solo paso, acceso efectivo del empleado a POS/corte/productos y presencia de usuarios en línea en el header del admin.
- Estado previo verificado: existe feature-empleados-permisos implementada; admin.users.index ya tiene alta inline y acordeón de permisos; StoreUserRequest no acepta permisos; UserController@store solo hace syncRoles; rol empleado sin permisos base; LoginController redirige a pos.index si pos.access y a admin.products.index si admin.products.manage; layouts.admin arma $panelHome sin la rama de productos; nav "Mi corte" usa pos.sales.day.own mientras la ruta cuts.mine exige además pos.cut.own; cuts.mine es readOnly y cuts.store exige pos.cut.manage; session.driver=database en .env y tabla sessions con last_activity indexado; en tests SESSION_DRIVER=array.
- Pasos: (1) añadir User::ACCESS_MODULES + permissionsForModules(); (2) aceptar modules[] y permissions[] en StoreUserRequest; (3) syncPermissions en UserController@store solo para rol empleado; (4) checkboxes de módulos en admin.users.index y en admin.users.partials.form; (5) corregir $panelHome y nav "Mi corte" a pos.cut.own; (6) permitir cuts.mine con pos.cut.manage|pos.cut.own; (7) permitir cuts.store con pos.cut.own, forzar empleado propio y redirigir a cuts.mine, con DailyCutService::generate(?User $onlyEmployee) para no generar cortes globales/ajenos; (8) PresenceService + view composer + parcial de presencia en el header; (9) tests y Pint.
- Archivos afectados: app/Models/User.php; app/Http/Requests/Admin/StoreUserRequest.php; app/Http/Requests/Admin/UpdateEmployeePermissionsRequest.php (regla compartida opcional); app/Http/Controllers/Admin/UserController.php; app/Http/Requests/Pos/GenerateDailyCutRequest.php; app/Http/Controllers/Pos/DailyCutController.php; app/Services/DailyCutService.php; routes/web.php; app/Providers/AppServiceProvider.php; app/Services/PresenceService.php (nuevo); resources/views/layouts/admin.blade.php; resources/views/admin/partials/presence.blade.php (nuevo); resources/views/admin/users/index.blade.php; resources/views/admin/users/partials/form.blade.php; tests/Feature/{AdminUserTest,EmployeePermissionsTest,PosCutTest,PresenceTest}.php.
- Plan de rollback: revertir los archivos del cambio; no hay migraciones ni datos nuevos; las cuentas creadas con permisos conservan sus permisos (se pueden vaciar con syncPermissions([]) desde la UI si hiciera falta).
- Riesgos: el driver de sesión debe ser database en producción (si no, el indicador muestra 0); la consulta a sessions corre en cada request del layout admin; dar pos.cut.own a un empleado exige impedir que genere cortes globales o de otros; admin.products.manage da CRUD completo, no solo lectura; el acordeón de permisos finos sigue siendo la vía para ajustes posteriores.
- Alternativas consideradas: presencia con users.last_seen_at (requiere migración y escritura por request) vs sessions (sin migración, multi-sesión); presets de módulos vs matriz granular desde el alta (se combinan: módulos en alta + ajuste fino después); permitir corte propio por pos.cut.manage (simple pero expone cortes ajenos) vs pos.cut.own con generación aislada (elegido).
- Ajustes multicaja 2026-11-04: (a) PresenceService agrupa por usuario para listar a todos los cajeros conectados a la vez; (b) el corte propio sigue aislado por user_id y nunca genera global ni ajeno; (c) separar "generar corte global" de "cerrar el día": nueva acción POST pos.cuts.close-day con salvaguarda que bloquea si hay otros usuarios con sesión activa salvo confirmación forzada, porque el cierre global actual (`closed_at`) bloquea las ventas de todos; (d) reintentar storeVersion ante colisión del unique (cut_key, version) cuando admin y empleado generan cortes concurrentes; (e) pruebas con dos cajeros: cada corte aislado, global = suma, corte propio no cierra el día, cierre bloqueado con otro cajero en línea.
- Archivos multicaja añadidos: app/Http/Controllers/Pos/DailyCutController.php (close-day), routes/web.php (pos.cuts.close-day), app/Http/Requests/Pos/GenerateDailyCutRequest.php (force), resources/views/pos/cuts/index.blade.php (aviso de cajeros en línea + botón cerrar día), app/Services/DailyCutService.php (retry de versión), tests/Feature/PosCutTest.php.
- Estado: IMPLEMENTADO el 2026-11-04.
- Cambios aplicados:
  * app/Models/User.php: ACCESS_MODULES (pos, cut, products) + permissionsForModules().
  * app/Http/Requests/Admin/StoreUserRequest.php: valida modules[] y permissions[].
  * app/Http/Controllers/Admin/UserController@store: syncPermissions para rol empleado (módulos + permisos finos, nunca admin.users.manage).
  * resources/views/admin/users/index.blade.php y partials/form.blade.php: checkboxes de módulos en alta.
  * resources/views/layouts/admin.blade.php: $panelHome incluye admin.products.manage; nav "Mi corte" usa pos.cut.own; incluye presencia en header.
  * resources/views/admin/partials/presence.blade.php (nuevo): lista de usuarios en línea (últimos 5 min) solo para admin.
  * app/Services/PresenceService.php (nuevo): consulta tabla sessions, ventana 5 min, agrupa por usuario.
  * app/Providers/AppServiceProvider.php: view composer + View::share de onlineUsers.
  * routes/web.php: cuts.store y cuts.mine con role_or_permission pos.cut.manage|pos.cut.own; nueva ruta pos.cuts.close-day (pos.cut.manage).
  * app/Http/Requests/Pos/GenerateDailyCutRequest.php: authorize canAny(cut.manage, cut.own).
  * app/Http/Controllers/Pos/DailyCutController.php: store forzar corte propio (sólo user_id, sin global ni cierre), closeDay con salvaguarda (otros en línea / cortes pendientes), breakdown en index y print.
  * app/Services/DailyCutService.php: generate(?User $onlyEmployee) aislado; storeVersion con reintento ante colisión de versión; employeeBreakdown() (solo activos ese día + sumatoria global).
  * resources/views/pos/cuts/index.blade.php: desglose por empleado + TOTAL DEL DÍA, aviso de cajeros en línea, botón cerrar día.
  * resources/views/pos/cuts/partials/employee-breakdown.blade.php (nuevo): tabla por empleado y sumatoria.
  * resources/views/pos/cuts/print.blade.php: desglose y sumatoria en impresión global.
  * tests/Feature/PosPaymentMethodsTest.php: corregido test preexistente (empleado sin pos.sell realmente no lo tenía).
- Desviaciones del plan original: se agregó close-day como acción separada (el corte global dejó de cerrar el día automáticamente) para evitar bloquear a cajeros concurrentes; el desglose solo incluye empleados con actividad ese día; se corrigió un test preexistente roto en PosPaymentMethodsTest.
- Verificación: suite completa 92 tests / 335 assertions en verde; Pint --dirty --format agent limpio.
