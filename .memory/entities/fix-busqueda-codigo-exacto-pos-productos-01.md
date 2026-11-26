---
entity: fix-busqueda-codigo-exacto-pos-productos-01
type: fix
---

- Objetivo: Exigir coincidencia completa por código en POS y Productos, sin cambiar la búsqueda parcial por nombre y marca.
- Archivos tocados: `app/Models/Product.php`, `app/Http/Controllers/Admin/ProductController.php`, `app/Http/Controllers/PosController.php`, `tests/Feature/AdminProductTest.php`, `tests/Feature/PosProductSearchTest.php`.
- Estado: IMPLEMENTADO.
- Verificación: Pint, sintaxis PHP de los cinco archivos y revisión del diff aprobados. Pruebas de regresión y lector físico pendientes de solicitud explícita de Testing.
