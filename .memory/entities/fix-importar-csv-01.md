---
entity: fix-importar-csv-01
type: fix
---

- Objetivo: reparar errores silenciosos del flujo de importación y compatibilidad CSV UTF-8 exportado desde Excel sin sobrescribir inventario existente.
- Archivos tocados: app/Http/Controllers/Admin/ProductController.php; resources/views/admin/products/index.blade.php; tests/Feature/AdminProductTest.php.
- Estado: IMPLEMENTADO.
- Cambios: detección coma/punto y coma; consumo de BOM UTF-8 antes del parser CSV; mensajes de archivo en español; todos los errores de fila visibles y escapados; archivo obligatorio y ayuda/accesibilidad alineadas al contrato vigente.
- Preservado: CSRF, permisos, formulario multipart, transacción y rollback completo, validación/alias, unicidad de códigos y SKU existentes omitidos. No se modificaron otras acciones de productos ni cambios preexistentes.
- Verificación: sintaxis PHP, Pint, ruta registrada y revisión de diff correctos. Se añadieron seis casos de regresión; no ejecutados por requerir solicitud explícita de Testing. Sin carga de datos reales, sin navegador y sin build.
- Limitación: no se recibió el archivo que falló; no se garantiza su compatibilidad si tiene otro formato, codificación o valores inválidos.
- Fecha: 2026-11-17.
