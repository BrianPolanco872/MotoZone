---
entity: plan-cartera-abonos-metodos-corte-01
type: plan
---

- Tipo: feature (mejora acotada, Modo B).
- Estado: IMPLEMENTADO.
- Objetivo: Mostrar en el corte los abonos de cartera separados por método, incluidos tarjeta de débito/crédito y transferencia, igual que ya se muestran los abonos en efectivo.
- Alcance: desglose visual en el corte global y por empleado; desglose en la impresión del corte; etiquetas legibles en el modal de abonos. Solo lectura/visualización.
- Fuera de alcance: cambiar fórmulas de efectivo, sumar tarjeta/transferencia a la caja física, tocar ventas, inventario, SaleService o el ledger; editar/anular abonos.
- Criterios de aceptación: el corte muestra Efectivo, Tarjeta y Transferencia; el modal muestra etiquetas en español; la impresión incluye el desglose; pruebas en verde.
- Archivos: app/Services/DailyCutService.php, resources/views/pos/cuts/index.blade.php, resources/views/pos/cuts/print.blade.php, tests/Feature/CustomerCreditTest.php.
- Plan de rollback: revertir las cuatro ediciones; no hay cambios de esquema ni de datos.
- Riesgos: ninguno monetario; tarjeta/transferencia siguen excluidas del efectivo esperado en caja.
- Fecha: 2026-11-18.
