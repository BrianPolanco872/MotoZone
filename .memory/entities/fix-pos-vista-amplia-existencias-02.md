---
entity: fix-pos-vista-amplia-existencias-02
type: fix
---

- Objetivo: corregir el buscador y el colapso/solapamiento del detalle del POS según el ajuste explícito del usuario al plan aprobado.
- Archivos tocados en esta corrección: resources/js/pos/pos.js; resources/views/pos/index.blade.php; tests/pos.test.js; tests/Feature/PosAccessTest.php; memoria de plan/feature/relaciones.
- Cambios: buscador con columnas exactas Foto/Código/Producto/Categoría/Precio unitario/Existencia en tienda/Existencia en bodega/Existencia Tienda 2 condicional; retirados controles/mapa de cantidad e importe del buscador, conservados en carrito. Fila completa seleccionable por clic o Enter/Espacio mediante addProduct existente. Bodega nula/vacía «-», Tienda 2 nula/vacía no habilita columna; cero se conserva.
- Detalle: viewport fijo de 388px, encabezado de 48px y estado vacío en tbody/td de 340px centrado verticalmente. Sin overlay absoluto sobre encabezados; scroll interno y altura estable en todos los tamaños. Altura explícita local para que no dependa de una utilidad Tailwind ausente del CSS compilado vigente.
- Foto: se conserva el único modal existente con miniatura de carrito, Cerrar/Escape, retorno de foco y fallback. No se altera cobro, precios, inventario, granel, kits ni pendientes.
- Endpoint verificado mediante lectura: PosController::productPayload incluye image_path/image_url/category_name/stock/available_stock/warehouse_stock/store_2_stock, con carga de categoría. No se necesita editar backend ni crear otro endpoint.
- Causa adicional confirmada: manifest referencia app-De4akJGp.js, que no contiene tabla nueva ni product-image-dialog; CSS app-Bh2Yz9JU.css no incluye min-h-72 ni altura previa. public/hot ausente. Assets locales viejos; no atribuir comprobación al servidor publicado.
- Verificación: node --check para script/prueba JS; php -l en vista/prueba PHP; Pint dirty y git diff --check correctos. Pruebas adaptadas para columnas/NULL/vacío/cero/selección por fila/teclado, modal y estructura fija; NO ejecutadas.
- Build: bloqueado por desalineación v1.0.0/HEAD, igual que la entrega anterior. No se ejecutó build, no se editaron bundles, dependencias ni tags. Se requiere resolver versión/tag y ejecutar ./build.sh para que el navegador use el nuevo buscador y el modal.
- Estado: IMPLEMENTADO EN FUENTE; sin verificación en navegador, migración ni Testing.
- Fecha: 2026-11-22.
