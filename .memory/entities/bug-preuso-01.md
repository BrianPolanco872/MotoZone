---
entity: bug-preuso-01
type: bug
---

- Auditoría: #1. Limpiar inventario elimina catálogo e historial aunque la confirmación promete poner existencias a cero.
- Estado: CAUSA RAÍZ CONFIRMADA por lectura del flujo vista -> ruta -> controlador; no se ejecutó la operación destructiva.
- Evidencia: resources/views/admin/products/index.blade.php:48; app/Http/Controllers/Admin/ProductController.php:88-100.
- Caso: con una venta de $100, limpiar elimina ventas/productos y conserva movimientos de caja, dejando fuentes contables divergentes.
- Causa confirmada: hipotesis-H1-preuso.
- Fecha: 2026-11-16. Alcance autorizado por usuario; sin implementación.
- Estado actualizado 2026-11-16: CORREGIDO Y VERIFICADO en fix-preuso-motozone-lote1-01. Limpieza solo modifica stock, registra ajustes y conserva historial; pruebas de preservación/rollback correctas. Concurrencia MySQL no ejecutada.
