---
entity: fix-preuso-motozone-lote5-01
type: fix
---

- Objetivo: lote 5 (#12, #13, #16) de plan-preuso-motozone-01: importacion segura, relacion de movimientos y modulo de corte.
- Estado: IMPLEMENTADO; importacion, borrado y permisos verificados con pruebas dirigidas.
- Archivos tocados: app/Http/Controllers/Admin/ProductController.php; app/Models/Product.php; app/Models/User.php; resources/views/admin/products/index.blade.php; tests/Feature/AdminProductTest.php; tests/Feature/AdminUserTest.php.
- Cambios aplicados: #12 importacion limitada a CSV/TXT en formulario y servidor; encabezados validados (sku|codigo + nombre|producto|descripcion); cada fila se valida con reglas equivalentes a ProductRequest y se crea dentro de una unica transaccion, de modo que codigos duplicados entre filas se informan como error de fila antes de una excepcion SQL; cualquier error revierte toda la carga. Valores vacios de cost/stock/min_stock caen a 0 por columnas NOT NULL; price sigue obligatorio. Se conserva la politica documentada de omitir SKU existente o vacio. #13 se agrego Product::stockMovements() como HasMany siguiendo saleItems y el borrado ya no falla cuando no hay ventas. #16 el modulo cut incluye pos.access sin conceder pos.sell.
- Verificacion: AdminProductTest + AdminUserTest + EmployeePermissionsTest + PosAccessTest: 61/62 correctas, 372 aserciones; el unico fallo es el preexistente de PosAccessTest por el texto del carrito vacio. Subconjunto de importacion: 6/6 correctas, 26 aserciones (null de barcode, rollback por fila invalida, duplicados entre filas, rechazo de Excel, encabezados requeridos, SKU existente omitido).
- Desviaciones: ninguna funcional. Se ajusto la ayuda visible del formulario para declarar solo CSV/TXT.
- Rollback: C:/Users/Administrador/AppData/Local/Temp/opencode/motozone-preuso-lote5-20261116-230457.
- Limitaciones: la importacion acepta solo el CSV simple ya soportado; no se agrego parser Excel ni dependencia. #18 intacto.
- Fecha: 2026-11-16.
