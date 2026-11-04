---
entity: feature-corte-dia
type: feature
---

- Objetivo: Habilitar el Punto de Venta para operación real de los empleados e incorporar el módulo «Corte del día» con la estructura de la referencia Abarrotes PDV, listo para publicar en hosting con dominio propio.
- Alcance: búsqueda de productos por código/nombre/SKU, carrito, mayoreo, cobro en efectivo, tickets imprimibles y reimpresión, ventas pendientes, ventas del día, entradas/salidas de efectivo con categoría, devoluciones con solicitud y autorización, corte diario global con desglose por empleado e historial imprimible.
- Alcance de productos: agregar precio de costo, porcentaje de ganancia calculado, precio de venta, precio de mayoreo, mínimo de existencia y control opcional de inventario, con el formulario similar a la captura de referencia.
- Fuera de alcance: apertura/cierre formal de caja por turno, módulos completos de clientes y proveedores, ventas a crédito, pagos con tarjeta/transferencia, venta a granel o por paquete con cantidades decimales, impresión ESC/POS directa, facturación fiscal, cancelación de ventas ya pagadas (se resuelve con devolución), restauración de empleados eliminados.
- Decisiones confirmadas 2026-11-02: corte global por día compartido, con filtro y corte individual por empleado; sin sesión de caja, el dinero inicial se registra como entrada manual; pagos de clientes y a proveedores como movimientos de efectivo con categoría y concepto libre; se agrega costo al producto para calcular ganancia real; impresión por navegador (ticket 80 mm y corte en hoja).
- Criterios de aceptación: una venta cobrada descuenta stock y genera movimiento de caja en una sola transacción; el servidor ignora precios y totales enviados por el cliente; doble clic no duplica ventas; el corte cuadra con ventas y movimientos del día; el filtro por empleado muestra solo sus operaciones; el ticket y el corte se imprimen desde el navegador; un empleado sin permiso no puede cobrar, mover caja, autorizar devoluciones ni hacer corte.
- Solicitada por: usuario
- Fecha: 2026-11-02
- Estado: IMPLEMENTADO. Implementado por fix-feat-corte-dia-01 el 2026-11-02.
