---
entity: fix-productos-manual-opcionales-01
type: fix
---

- Objetivo: permitir añadir y editar productos manualmente sin marca ni descripción, para completarlas después.
- Archivos tocados: app/Http/Requests/Admin/ProductRequest.php; app/Http/Controllers/Admin/ProductController.php; resources/views/admin/products/partials/form.blade.php; tests/Feature/AdminProductTest.php.
- Estado: IMPLEMENTADO.
- Cambios: validación opcional, etiquetas y HTML alineados; normalización compatible con NOT NULL; edición omitiendo campos conserva datos existentes, edición con vacíos los limpia. Resto del formulario y reglas intactos.
- Cobertura: alta omitida/vacía, edición preservada/vaciada/completada y atributos HTML de creación/edición.
- Verificación: sintaxis PHP, Pint y revisión de diff correctos; pruebas no ejecutadas porque Testing requiere solicitud explícita. Sin escrituras reales ni cambios de esquema.
- Desviaciones: ninguna.
- Fecha: 2026-11-17.
