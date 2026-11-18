---
entity: fix-cartera-abonos-metodos-corte-01
type: fix
---

- Objetivo: Exponer los abonos de cartera por método en el corte sin alterar los totales de caja.
- Cambios: DailyCutService calcula customer_payments_card y customer_payments_transfer; el corte muestra total y subfilas de efectivo/tarjeta/transferencia; la impresión agrega la sección "Abonos de clientes por método"; el modal traduce cash/card/transfer a etiquetas legibles.
- Verificación: CustomerCreditTest + PosCutTest con 20 pruebas y 96 aserciones en verde; Pint aplicado.
- Fecha: 2026-11-18.
