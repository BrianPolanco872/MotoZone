---
entity: fix-importar-csv-opcionales-01
type: fix
---

- Objetivo: permitir importar sin marca ni descripción para completarlas manualmente después.
- Archivos tocados: app/Http/Controllers/Admin/ProductController.php; resources/views/admin/products/index.blade.php; tests/Feature/AdminProductTest.php.
- Estado: IMPLEMENTADO.
- Cambios: reglas opcionales exclusivas del importador; valores ausentes almacenables como cadena vacía; ayuda alineada; cobertura de columnas ausentes/celdas vacías y ajuste de errores múltiples.
- Verificación: Pint, sintaxis PHP y revisión del diff correctos. Pruebas añadidas, no ejecutadas porque Testing requiere solicitud explícita. Sin migraciones ni escrituras de inventario.
- Desviaciones: ninguna; alta/edición manual y resto de reglas intactos.
- Fecha: 2026-11-17.
