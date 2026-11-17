---
entity: bug-preuso-15
type: bug
---

- Auditoría: #15. Partidas repetidas superan el control agregado de stock.
- Estado: CAUSA RAÍZ CONFIRMADA mediante prepareItems real con dependencias simuladas, sin BD.
- Evidencia: app/Services/SaleService.php:184-215,287-290,359-362,391-405; app/Http/Requests/Pos/StoreSaleRequest.php:42-49.
- Caso: stock=5, dos partidas de 3 unidades se aceptan; la segunda resta intenta stock=-1 y MySQL unsigned puede devolver error y rollback en vez de validación.
- Causa confirmada: hipotesis-H15-preuso.
- Fecha: 2026-11-16. Sin implementación. No se afirma stock negativo persistido en MySQL.
