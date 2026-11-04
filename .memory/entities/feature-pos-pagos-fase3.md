---
entity: feature-pos-pagos-fase3
type: feature
---

- Objetivo: Completar el módulo Punto de Venta de MotoZone con métodos de pago (efectivo/tarjeta/transferencia/mixto), campo "Pagó con", cálculo de cambio, atajos de teclado completos (F6/F7/F8/F11/DEL), reimpresión de ticket, y completar lo que falte comparado con Abarrotes PDV.
- Alcance: Métodos de pago en venta y cobro, campo pagó con y cambio, atajos F6/F7/F8/F11/DEL, reimprimir último ticket, tickets anteriores con folio consecutivo, cancelación/devolución con motivo, cambio de precio y cantidad por renglón, validación de existencia.
- Fuera de alcance: Artículo común/varios (mediante permiso) - sugerir solo, mayoreo ya existe parcialmente, búsqueda con comodín avanzada - ya existe búsqueda por nombre/SKU/marca.
- Criterios de aceptación: (1) Venta con tarjeta no suma al efectivo en corte pero sí a ventas totales. (2) Venta con transferencia no suma al efectivo. (3) Venta mixto efectivo+tarjeta registra correctamente. (4) Campo "Pagó con" y cambio funcionan. (5) Atajos F6/F7/F8/F11/DEL funcionan. (6) Reimprimir ticket muestra método de pago. (7) No se puede cobrar con monto insuficiente.
- Solicitada por: usuario
- Última actualización: 2026-11-04
