---
entity: bug-preuso-10
type: bug
---

- Auditoría: #10. Venta con artículo libre lanza TypeError antes de guardarse.
- Estado: CAUSA RAÍZ CONFIRMADA mediante ejecución aislada del método real sin conexión a BD.
- Evidencia: resources/js/pos/pos.js:353-358; app/Services/SaleService.php:184-225.
- Caso: product_id=null e is_custom=true alcanza lockProducts y la closure declarada int devuelve null.
- Causa confirmada: hipotesis-H10-preuso.
- Fecha: 2026-11-16. Sin implementación ni ventas creadas.
