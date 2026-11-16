---
entity: plan-retirar-catalogo-publico-01
type: plan
---

- Tipo: feature
- Objetivo: retirar la página pública /catalogo, la clasificación por categorías y los enlaces al catálogo; conservar categorías manuales en home mediante Editar inicio.
- Aprobación: usuario confirma «si elimina la pagina catalogo y categorias y enlaces al catalogo» después de aclarar el alcance.
- Alcance: eliminar ruta/index/filtros del catálogo; limpiar menú y pie públicos, CTA del panel/productos, ficha de producto y visual compartido; quitar exposición de categorías de producto en POS sin tocar snapshots de ventas; eliminar composer navCategories; adaptar textos por defecto y no renderizar destinos guardados a /catalogo.
- Archivos: routes/web.php; app/Http/Controllers/CatalogController.php y PosController.php; app/Providers/AppServiceProvider.php; app/Models/HomePage.php y HomeCard.php; resources/views/layouts/site.blade.php, home.blade.php, catalog/show.blade.php, catalog/index.blade.php, catalog/partials/filters.blade.php, components/product-visual.blade.php, admin/dashboard.blade.php, admin/products/index.blade.php, admin/home/partials/card-form.blade.php; resources/js/pos/pos.js; mensajes de HomeCardRequest/HomeContentRequest; tests/Feature/CatalogTest.php, HomeTest.php, HomeCardTest.php y AdminHomeContentTest.php.
- Exclusiones: conservar /producto y nombre catalog.show para compatibilidad, home y su editor, modelos/tablas/columnas históricos de categorías, snapshots sale_items y servicios de ventas/cortes. Sin migraciones ni borrado de datos.
- Plan de rollback: restaurar solo los cambios de esta retirada desde las copias previas de los archivos afectados; recuperar archivos eliminados; no restablecer desde HEAD ni sobrescribir cambios posteriores. Sin operaciones sobre la base de datos.
- Verificación: sintaxis PHP/Blade/JS, rutas y búsquedas estáticas. Añadir/adaptar regresiones sin ejecutarlas; Testing requiere solicitud explícita. Usar Vite activo para cambios de desarrollo, sin construir artefactos.
- Estado: APROBADO
- Ajuste necesario comunicado: limpiar también etiquetas residuales de «catálogo» en login, alta/formulario de productos y calidad del dashboard; actualizar su aserción en AdminTest.php. Sin retirar funciones de inventario ni publicación de fichas.
- Estado: IMPLEMENTADO
- Cambios aplicados: /catalogo eliminado con su action, vista y filtros; navegación pública/admin sin enlaces al catálogo; ficha /producto y POS sin categorías visibles; composer navCategories eliminado; cards manuales y editor conservados; URLs de catálogo guardadas se suprimen al renderizar sin modificar registros; textos por defecto actualizados.
- Desviaciones del plan original: solo limpieza de etiquetas y aserción adicional comunicadas. No se alteró el esquema ni los snapshots de ventas.
- Verificación: PHP/Blade/JS sin errores de sintaxis; Pint y diff --check de archivos del alcance correctos; sin referencias catalog.index/navCategories en app/resources/routes/tests; GET / devuelve 200 sin enlaces catálogo; GET /catalogo devuelve 404; ruta catalog.show permanece. Tests adaptados y añadidos, NO ejecutados.
- Requisito previo pendiente detectado y comunicado: 2026_11_11_130000_make_products_category_id_nullable figura Pending en la BD local. Sin aplicarla, crear productos sin categoría puede fallar por NOT NULL. No se ejecutó por exclusión explícita de cambios de BD.
- Rollback: copias previas en C:/Users/Administrador/AppData/Local/Temp/opencode/retirar-catalogo-publico-01 conservando rutas relativas.
- Ampliación autorizada: el usuario solicita «aplicalo» respecto al comando de migración mostrado. Se autoriza aplicar exclusivamente database/migrations/2026_11_11_130000_make_products_category_id_nullable.php en la BD configurada, sin ejecutar otras migraciones ni tests.
- Rollback de la ampliación: down restaura NOT NULL únicamente si no existen productos con category_id null; no rellenar ni borrar datos automáticamente y no ejecutar rollback sin autorización.
- Migración aplicada: 2026_11_11_130000_make_products_category_id_nullable finalizó DONE; migrate:status confirma [16] Ran. Requisito previo de BD resuelto, sin ejecutar otras migraciones ni tests.
