---
entity: plan-cartera-manual-01
type: plan
---

- Tipo: feature (Modo B).
- Estado: APROBADO.
- Aprobación explícita: El usuario seleccionó «Sí, registrar y aplicar» en esta conversación.
- Objetivo: Implementar la especificación de cartera manual proporcionada por el usuario, reutilizando CustomerPayment y sin modificar ventas/carrito/inventario.
- Paso 1: Migración nueva customer_credits con nombre/teléfono/descripción, total_cents/balance_cents, estado pending/settled, created_by nullable e idempotency_key única para proteger también altas sin abono inicial; migración aditiva customer_payments con customer_credit_id nullable e idempotency_key nullable única. No asociar pagos históricos.
- Paso 2: CustomerCredit, relaciones CustomerPayment, requests y CustomerCreditService; validación monetaria hasta dos decimales y máximo compatible con pagos existentes; conversión exacta a centavos, transacción, bloqueo de deuda, idempotencia persistida y atribución al cobrador con happened_at efectivo. Crear pago solo cuando se recibe dinero y reutilizar CashMovementService con valores decimales canónicos y método explícito.
- Paso 3: Rutas /pos/abonos para admin y empleado autenticados activos sin depender de pos.access; búsqueda acotada de productos de referencia; navegación y vista oscura con alta, filtros, liquidados, modal de abono e historial seguro compartido.
- Paso 4: Reutilizar withDayLock para nuevos abonos y cierres; cierre personal atómico y coordinado; comprobar día/corte propio cerrado dentro del bloqueo. Preservar snapshots previos y fórmulas de DailyCutService. Revisar cierre global para que su snapshot incluya pagos confirmados antes del cierre.
- Paso 5: Impedir borrado de empleado con pagos vinculados (mantener pagos legacy no vinculados); reemplazar innerHTML inseguro en modal existente por DOM textContent y mostrar desglose de efectivo en corte con el mismo filtro usuario/fecha.
- Paso 6: CustomerCreditTest y prueba MySQL de concurrencia aislada/documentada; regresiones POS/cortes/usuarios pertinentes. El usuario solicitó expresamente ejecutar migraciones y php artisan test. Confirmar base objetivo, ejecutar solo las nuevas migraciones y no usar migraciones destructivas; si el entorno no permite ejecutar, informar bloqueo.
- Archivos previstos: database/migrations/*customer_credits* y *credit_fields*; app/Models/CustomerCredit.php, CustomerPayment.php; app/Services/CustomerCreditService.php, CashMovementService.php (solo si requiere integración), DailyCutService.php (solo coordinación necesaria); app/Http/Requests/Pos/*CustomerCredit* y *CreditPayment*; app/Http/Controllers/Pos/CustomerCreditController.php, DailyCutController.php; app/Http/Controllers/Admin/UserController.php; routes/web.php; resources/views/pos/abonos/index.blade.php, pos/cuts/index.blade.php, layouts/admin.blade.php; tests/Feature/CustomerCreditTest.php y pruebas de concurrencia; documentación de ejecución solicitada por el usuario.
- Precisión notificada: idempotency_key adicional en customer_credits para altas sin primer pago; dinero nuevo en centavos, interfaz decimal canónica hacia el ledger existente.
- Plan de rollback: Desactivar rutas/enlace/interfaz y restaurar código anterior conservando cuentas, customer_payments y snapshots; no borrar tablas/columnas pobladas ni restaurar una BD completa sobre operaciones nuevas. down() debe rechazar retirada del esquema si hay datos vinculados/poblados. Desactivar el módulo no equivale a ejecutar migrate:rollback.
- Verificación de construcción: Si se requiere generar assets, usar exclusivamente ./build.sh tras comprobar versiones y tag correspondiente; no construir con discrepancias.
- Fecha: 2026-11-18.
- Estado: IMPLEMENTADO.
- Migraciones aplicadas en MySQL `motozone`: 2026_11_18_012106_create_customer_credits_table y 2026_11_18_012107_add_credit_fields_to_customer_payments_table.
- Cambios aplicados: CustomerCredit + customer_credit_id/idempotency_key en customer_payments; CustomerCreditService con centavos, lockForUpdate, withDayLock, idempotencia y auditoría; CashMovementService acepta vínculo/idempotencia; requests y controlador Pos\CustomerCreditController; rutas y navegación /pos/abonos; vista oscura con alta, buscador orientativo, filtro vivo, abono e historial seguro; corte muestra abonos en efectivo y usa textContent; cierre personal bajo withDayLock; UserController bloquea empleados con abonos vinculados.
- Desviaciones: se añadió idempotency_key única en customer_credits (notificado y aceptado); se amplió CashMovementService para transportar customer_credit_id/idempotency_key; no se tocaron sales, SaleService, carrito ni inventario.
- Pruebas: tests/Feature/CustomerCreditTest.php con 9 pruebas y 50 aserciones en verde; PosCutTest + PosReturnsTest + CustomerCreditTest: 28 pruebas en verde.
- Regresiones no atribuibles: PosAccessTest, PosBitacoraTest, PosProductCostTest y AdminProductTest (audit_logs.deleted_at) fallan igual sin estos cambios; verificado con stash para los tres primeros.
- Concurrencia MySQL real: deuda de $100.00, dos procesos simultáneos abonando $60.00 con claves idempotentes distintas. Resultado: un abono aceptado (saldo final $40.00) y el otro rechazado por superar el saldo; nunca hubo saldo negativo ni doble cargo. Datos temporales eliminados al terminar.
- Formato: vendor/bin/pint --dirty aplicado.
