---
entity: hipotesis-H3-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: app/Services/SaleService.php:376-384; app/Services/DailyCutService.php:87-129,447-452.
- Mecanismo: applyStockAndCash genera category=sale para cualquier payment_method; sumMovement no filtra el método. supplierPayments suma cualquier method y ese importe se resta de ambos subtotales de efectivo.
- Verificación: contratos de StoreSaleRequest, StoreCashMovementRequest, CashMovementService y cálculos revisados. No se ejecutaron pruebas. La auditoría no define un reparto nuevo para mixed.
