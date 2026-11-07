---
entity: plan-caja-03
type: plan
---

- Tipo: feature
- Objetivo: Corregir y unificar el panel de caja para el administrador, mejorando la visibilidad de acciones, funcionalidad del filtrado y claridad en reportes por trabajador.
- Pasos: 
  1. Unificar y limpiar la vista `resources/views/admin/caja/index.blade.php` (eliminar duplicados, añadir resumen por empleado).
  2. Ajustar estilos CSS para mejorar contraste del botón "Corte del día" (usar estilo emerald con border en lugar de bg-black).
  3. Mejorar visibilidad del botón "Filtrar" para aplicación de filtros por empleado (usar color emerald con icono SVG).
  4. Diferenciar visualmente el botón "Limpiar" del de "Filtrar" (estilo secundario con borde y icono).
  5. Mejorar visibilidad de acciones de tabla (editar/eliminar) agregando background `bg-slate-100 dark:bg-slate-700` para que sean visibles sin hover.
  6. Agregar método `archive` en `CajaController.php` y ruta `caja.archive` en `routes/web.php`.
  7. Agregar botón "Ver archivados en general" en la zona de acciones principales que alterna `show_archived=1` en los filtros.
  8. Confirmar que la lógica del controlador `CajaController.php` ya proporciona los datos necesarios (`$employeeBreakdown`, `$filters`, totales).
  9. Verificar funcionamiento de filtros, botones de archive/delete y visualización en navegador.
- Archivos afectados: 
  - `resources/views/admin/caja/index.blade.php` (limpieza y nuevo UI - estilos de botones, sección de empleado, acciones de tabla, botón archivados).
  - `app/Http/Controllers/Admin/CajaController.php` (agregado método `archive`).
  - `routes/web.php` (agregada ruta `caja.archive`).
- Riesgos: Bajo. Los cambios son principalmente de vista y rutas; se agregó nuevo método de controller y routes, pero la lógica de negocio subyacente (campos `archivado_at`) ya existía en el modelo.