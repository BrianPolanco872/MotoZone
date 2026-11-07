---
entity: fix-feat-empleados-alta-permisos-presencia-01
type: fix
---

- Objetivo: Implementar la alta de empleado con módulos (punto de venta, corte del día y productos) en un solo paso, acceso efectivo del empleado a esos módulos, corte propio aislado en modo multicaja, cierre del día con salvaguardas, desglose por empleado con sumatoria final y presencia de usuarios en línea en el header del admin.
- Archivos tocados: app/Models/User.php; app/Http/Requests/Admin/StoreUserRequest.php; app/Http/Controllers/Admin/UserController.php; resources/views/admin/users/index.blade.php; resources/views/admin/users/partials/form.blade.php; resources/views/layouts/admin.blade.php; resources/views/admin/partials/presence.blade.php; app/Services/PresenceService.php; app/Providers/AppServiceProvider.php; routes/web.php; app/Http/Requests/Pos/GenerateDailyCutRequest.php; app/Http/Controllers/Pos/DailyCutController.php; app/Services/DailyCutService.php; resources/views/pos/cuts/index.blade.php; resources/views/pos/cuts/partials/employee-breakdown.blade.php; resources/views/pos/cuts/print.blade.php; tests/Feature/PosPaymentMethodsTest.php.
- Estado: IMPLEMENTADO el 2026-11-04.
- Verificación: suite completa 92 tests / 335 assertions en verde; Pint --dirty --format agent limpio.
