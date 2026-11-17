---
entity: bug-preuso-03
type: bug
---

- Auditoría: #3. El efectivo esperado mezcla ventas y pagos electrónicos con dinero físico.
- Estado: CAUSA RAÍZ CONFIRMADA por trazado de escrituras y sumas; sin prueba integrada ejecutada.
- Evidencia: app/Services/SaleService.php:376-384; app/Services/DailyCutService.php:47-51,87-129,447-452.
- Casos: venta con tarjeta de $100 genera movimiento contado en caja aunque computadora cuenta $0 de efectivo; pago a proveedor por transferencia de $200 reduce una caja física de $500 a $300 esperados.
- Causa confirmada: hipotesis-H3-preuso.
- Fecha: 2026-11-16. Sin implementación; el método mixed no tiene reparto persistido y no se presume validado.
