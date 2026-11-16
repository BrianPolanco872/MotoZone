---
entity: feature-pagos-entrantes-pos-01
type: feature
---

- Objetivo: Mostrar en el POS avisos confirmados de pagos por transferencia y tarjeta recibidos por el buzón configurado, y al aceptarlos finalizar el ticket abierto para que la venta, el inventario y el corte queden registrados.
- Alcance: Sincronizar un buzón IMAP configurable; filtrar remitentes/avisos admitidos y deduplicar mensajes; mostrar el feed en POS a usuarios con pos.access; aceptar el aviso contra el carrito actual sólo con importe exacto y permiso pos.sell; enlazar el aviso una sola vez con una venta pagada card o transfer; reflejar efectivo, tarjeta y transferencia por separado en cortes globales, por empleado e informes existentes; configurar credenciales sólo para administradores y almacenarlas cifradas.
- Fuera de alcance: Checkout o cobro en línea; cobrar de nuevo al cliente al aceptar el aviso; importar movimientos que no lleguen al buzón configurado; aceptar avisos sin ticket abierto o con importe distinto; re-procesar o recalcular cortes históricos cerrados; cambiar el flujo de pago mixto, cuyo desglose por componente no se persiste actualmente.
- Criterios de aceptación: (1) Empleados y administradores con pos.access ven los avisos admitidos en el POS, también en pantallas pequeñas. (2) Sólo avisos de remitentes/formatos configurados y con estado confirmado se pueden aceptar. (3) Cada Message-ID/referencia externa se registra y procesa una sola vez, incluso con concurrencia o reintentos. (4) Aceptar requiere permiso pos.sell y un carrito activo cuyo total coincida exactamente; crea una venta pagada con método card o transfer, descuenta inventario y enlaza el aviso; no crea una segunda venta ni doble movimiento. (5) Avisos rechazados, duplicados, sin ticket o con total distinto no alteran venta, inventario ni corte. (6) El corte presenta efectivo, tarjeta y transferencia como totales distintos; tarjeta/transferencia no incrementan el efectivo en gaveta. (7) Sólo administradores pueden ver o editar la conexión, y sus secretos no se muestran ni se guardan en texto plano.
- Solicitada por: usuario
- Última actualización: 2026-11-16
- Estado: Plan guardado; implementación pendiente de solicitud explícita.
