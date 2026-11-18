---
entity: fix-cartera-manual-01
type: fix
---

- Objetivo: Implementar la cartera manual de deudas y créditos a clientes sobre CustomerPayment, con montos en centavos, idempotencia, coordinación con el corte diario y protección del historial de empleados.
- Archivos tocados: app/Models/CustomerCredit.php, app/Models/CustomerPayment.php, app/Services/CustomerCreditService.php, app/Services/CashMovementService.php, app/Services/DailyCutService.php, app/Http/Controllers/Pos/CustomerCreditController.php, app/Http/Controllers/Pos/DailyCutController.php, app/Http/Controllers/Admin/UserController.php, app/Http/Requests/Pos/StoreCustomerCreditRequest.php, app/Http/Requests/Pos/StoreCreditPaymentRequest.php, database/migrations/2026_11_18_012106_create_customer_credits_table.php, database/migrations/2026_11_18_012107_add_credit_fields_to_customer_payments_table.php, database/factories/CustomerCreditFactory.php, routes/web.php, resources/views/pos/abonos/index.blade.php, resources/views/pos/cuts/index.blade.php, resources/views/layouts/admin.blade.php, tests/Feature/CustomerCreditTest.php.
- Estado: IMPLEMENTADO.
- Verificación: migraciones aplicadas en MySQL; 8 pruebas nuevas en verde; 27 pruebas de POS/cortes/cartera en verde; concurrencia real en MySQL sin sobreabono.
