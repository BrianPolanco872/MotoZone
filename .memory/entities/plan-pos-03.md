---
entity: plan-pos-03
type: plan
---

- Tipo: feature
- Estado: COMPLETADO (pendiente permisos en FASE 4)
- Objetivo: Completar el módulo Punto de Venta de MotoZone con métodos de pago, campo pagó con, cambio, atajos de teclado, reimpresión de ticket.
- Pasos completados:
  1. ✅ config/pos.php: habilitados card, transfer y mixed
  2. ✅ app/Models/Sale.php: payment_method acepta cash/card/transfer/mixed
  3. ✅ app/Services/SaleService.php: accept payment_method en create() y chargePending()
  4. ✅ app/Http/Controllers/Pos/SaleController.php: pasar payment_method al servicio
  5. ✅ app/Http/Controllers/Pos/PosController.php: incluir payment_method en show() JSON
  6. ✅ app/Http/Requests/Pos/StoreSaleRequest.php: validar payment_method
  7. ✅ app/Http/Requests/Pos/ChargePendingSaleRequest.php: validar payment_method
  8. ✅ resources/views/pos/index.blade.php: diálogo cobro con selector método de pago, campo pagó con, cambio, botón reimprimir
  9. ✅ resources/views/pos/tickets/ticket.blade.php: mostrar método de pago en ticket (etiquetas en español desde config)
  10. ✅ resources/js/pos/pos.js: atajos F6/F7/F8/F11/DEL, lógica método de pago, cálculo cambio, mostrar/ocultar campo pagó con
  11. ✅ tests/Feature/PosPaymentMethodsTest.php: pruebas de métodos de pago (6 de 7 pasando)
- Archivos afectados:
  - config/pos.php (modificar)
  - app/Models/Sale.php (modificar)
  - app/Services/SaleService.php (modificar)
  - app/Http/Controllers/Pos/SaleController.php (modificar)
  - app/Http/Controllers/Pos/PosController.php (modificar)
  - app/Http/Requests/Pos/StoreSaleRequest.php (modificar)
  - app/Http/Requests/Pos/ChargePendingSaleRequest.php (modificar)
  - resources/views/pos/index.blade.php (modificar)
  - resources/views/pos/tickets/ticket.blade.php (modificar)
  - resources/js/pos/pos.js (modificar)
  - tests/Feature/PosPaymentMethodsTest.php (nueva)
- Criterios de aceptación verificados: Venta con tarjeta no suma a efectivo pero sí a ventas totales ✅; transferencia igual ✅; mixto ✅; pagó con y cambio funcionan ✅; reimprimir ticket muestra método de pago ✅; no se puede cobrar con monto insuficiente ✅.
- Test pendiente: test_empleado_sin_permiso_no_puede_cobrar (problema preexistente, se aborda en FASE 4)
- Última actualización: 2026-11-04
