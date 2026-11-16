---
entity: plan-home-familias-editor-01
type: plan
---

- Tipo: feature
- Objetivo: sustituir exclusivamente el apartado Categorías de Editar inicio por el diseño del usuario con selector visual, creación/edición de familias y vinculación manual de productos.
- Aprobación: usuario confirma «si, lo de importacion no lo pongas» al alcance de agrupaciones exclusivamente editoriales de home, sin restaurar catálogo ni clasificar el inventario.
- Alcance: familias sugeridas Motor y Transmisión, Frenos y Suspensión, Filtros y Aceites, Eléctrico y Baterías, Accesorios y Cascos; contadores reales; nombre, slug/ancla, descripción, icono/foto, posición y visibilidad; búsqueda por SKU/nombre, vincular/desvincular y ordenar productos dentro de la familia. Conservar las cards actuales y los otros tres apartados sin rediseñarlos.
- Decisión de navegación: el slug identifica un ancla de la misma home (/\#familia-slug), donde se muestran únicamente los productos activos seleccionados; sin nueva página de catálogo ni rutas de categoría de inventario.
- Persistencia: añadir slug e icon a home_cards y tabla pivote home_card_product con orden; no escribir products.category_id ni modificar existencias/precios/snapshots. Aplicar exclusivamente la migración aditiva nueva en el entorno local.
- Archivos: app/Models/HomeCard.php; controllers Admin/HomeCardController.php, Admin/HomeContentController.php, HomeController.php y nuevo Admin/HomeCategoryProductController.php; HomeCardRequest.php; routes/web.php; migración aditiva; resources/views/admin/home/edit.blade.php, nuevo partial categories.blade.php y card-form.blade.php; resources/views/home.blade.php; resources/js/home-editor.js; tests de familias/editor/home.
- Exclusiones: ninguna importación CSV/Excel, catálogo público, asignación de categorías a inventario, cambios de Presentación/Destacados/Recién llegados, dependencias nuevas ni cambios en ventas.
- Plan de rollback: copias previas de archivos del alcance y retirada de archivos nuevos; no sobrescribir cambios posteriores. La migración down elimina solo pivote y columnas nuevas, pero destruye vínculos editoriales/slug/icono creados desde su aplicación: exportarlos antes, pedir autorización específica y preferir forward-fix. No ejecutar rollback automáticamente ni borrar productos/cards.
- Verificación: sintaxis PHP/Blade/JS y rutas; regresiones añadidas sin ejecutarlas. No iniciar Testing sin solicitud explícita. Comprobación básica de entrega de assets por Vite, sin build.
- Estado: APROBADO
- Estado: IMPLEMENTADO
- Cambios aplicados: apartado Categorías aislado en partial nuevo con selector de familia, creación/edición, cinco plantillas, iconos/fotos, posición/visibilidad y productos vinculados con búsqueda, desvinculación y orden. Contadores reales, sin importación. Home muestra las agrupaciones mediante bloques desplegables y únicamente miembros activos.
- Persistencia aplicada: migración 2026_11_11_234009_add_families_to_home_cards DONE, registrada en batch 17. Slug/icono en home_cards y pivote home_card_product. Sin datos de ejemplo persistidos ni cambios en products.category_id/inventario.
- Ajustes de integración: ancla familia-ID-slug para evitar colisiones con cards antiguas; cinco familias son plantillas voluntarias, no registros sembrados; flechas accesibles en lugar de arrastre decorativo; corrección de posición vacía y protección de borradores recuperados de validación.
- Verificación: PHP/Blade/JS y Pint correctos; compilación Tailwind en memoria correcta; editor renderizado con datos actuales y con familia de dos productos exclusivamente en memoria; home HTTP200 y /catalogo HTTP404; Vite sirve CSS/JS actuales HTTP200; tres rutas editoriales inspeccionadas. Sin suite de tests ni revisión visual/clics en navegador.
- Rollback: archivos previos en C:/Users/Administrador/AppData/Local/Temp/opencode/home-familias-editor-01. No se ejecutó rollback ni cambios de historial Git.
