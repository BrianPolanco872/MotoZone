---
entity: bug-pos-reimpresion-01
type: bug
---

- Reporte: «Reimprimir último ticket» aparece deshabilitado en el POS; «Imprimir copia» no abre la impresión al seleccionar V-000016 en Ventas del día.
- Evidencia estática: `PosController::index` no entrega la última venta cobrada a `pos-config`. En `resources/js/pos/pos.js` la última venta empieza en `null`, se registra tras un cobro en esa página y se borra con «Nueva venta» y «Limpiar». Esto explica que el botón se deshabilite al entrar, recargar o limpiar aunque exista una venta elegible.
- Evidencia estática: `day-sales-print` sí pasa la venta seleccionada a `printTicketById`, compartido con el otro botón. La función imprime desde un iframe oculto, cancela cuando no encuentra el recibo o no puede decodificar una imagen y reporta el error fuera del diálogo modal. No se confirmó cuál de esos puntos falla para V-000016 en el navegador del usuario. La autorización de `SaleController::authorizeTicket` no debe cambiar.
- Estado: PLAN PROPUESTO; sin cambios en la aplicación ni pruebas en la caja.
- Estado actualizado 2026-11-25: CORREGIDO EN FUENTE LOCAL mediante `fix-pos-reimpresion-01`. Pendiente comprobación de ambos botones con Testing explícito en la caja.
