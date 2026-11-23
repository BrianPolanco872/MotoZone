---
entity: fix-filtro-empleado-corte-solo-admin-01
type: fix
---

- Objetivo: en "Corte del día", el selector "Empleado" para ver el corte de otro empleado (o el corte global) debe ser exclusivo del administrador. Los empleados solo ven su nombre y el botón "Ver corte" para su propio corte.
- Archivos tocados: app/Http/Controllers/Pos/DailyCutController.php; app/Models/User.php; tests/Feature/PosCutTest.php.
- Cambio aplicado: en DailyCutController::index() se cambió $canSeeAny de $user->can('pos.sales.day.any') a $user->hasRole('admin'). Eso oculta el selector y obliga a cualquier empleado (incluso con el permiso antiguo) a ver solo su propio corte aunque envíe ?employee=otro. Se quitó 'pos.sales.day.any' del PERMISSION_CATALOG de User, por lo que ya no aparece como permiso asignable al editar empleados.
- Alcance: el permiso sigue creado en RolePermissionSeeder porque otros módulos (ventas, devoluciones, caja, PDF) lo usan para administradores. No se revocó de usuarios existentes: solo deja de poder asignarse y deja de habilitar el filtro del corte.
- Verificación: prueba nueva test_employee_filter_is_only_available_to_admins (empleado con el permiso antiguo no ve cut-employee y se fuerza su propio corte; admin sí lo ve) pasa. PosCutTest 14/14; EmployeePermissionsTest + PosCutReconciliationTest + PosCutPdfTest 57/57; Pint pasa. Sin build: solo PHP/Blade sin cambios de JS/CSS (Blade no cambió).
- Fecha: 2026-11-22.
- Estado: IMPLEMENTADO.
