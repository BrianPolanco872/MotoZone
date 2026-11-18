---
entity: feature-cartera-manual-01
type: feature
---

- Objetivo: Cartera manual compartida para administradores y empleados activos, independiente de ventas e inventario, con cobros mediante CustomerPayment.
- Estado: APROBADO PARA IMPLEMENTACIÓN.
- Aprobación: El usuario seleccionó «Sí, registrar y aplicar» en esta conversación.
- Alcance: cuentas, abonos iniciales/parciales/liquidación, historial compartido, buscador orientativo, centavos enteros, idempotencia, seguridad del modal de corte, preservación ante eliminación de empleados y coordinación de cierres.
- Fuera de alcance: sales, SaleService, carrito, stock, asociación automática de pagos históricos, cobros reales por pasarela, edición/anulación de pagos y migración general del ledger.
- Fecha: 2026-11-18.
- Estado: IMPLEMENTADO.
- Implementada en: fix-cartera-manual-01.
- Mejora posterior: fix-cartera-abonos-metodos-corte-01 muestra los abonos del corte separados por efectivo, tarjeta y transferencia.
