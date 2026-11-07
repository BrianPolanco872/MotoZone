---
entity: feature-caja-mejoras
type: feature
---

- Objetivo: Corregir y unificar el panel de caja para el administrador, mejorando la visibilidad de acciones, funcionalidad del filtrado y claridad en reportes por trabajador.
- Alcance: Vistas blade (`index.blade.php`), controlador (`CajaController.php`), rutas (`routes/web.php`). Sin cambios en esquema de base de datos.
- Fuera de alcance: Cambios en arquitectura de base de datos o migración de lógica a servicios externos.
- Criterios de aceptación:
  1. Panel de caja único y limpio (sin secciones duplicadas de filtros/acciones).
  2. Filtros funcionales (fecha, empleado, tipo) manteniendo estado tras envío.
  3. Botón "Corte del día" claramente visible con contraste adecuado en modo claro y oscuro.
  4. Acciones de tabla (archivar/eliminar) visibles y funcionales.
  5. Nueva sección de resumen por empleado con totales en la vista principal.
  6. Botón "Filtrar" claramente distinguible para aplicar filtros por empleado.
  7. Botón "Limpiar" visualmente distinto del de "Filtrar".
  8. Buttons de archivar y eliminar en tabla con tooltips y funcionalidad.
  9. Botón "Ver archivados en general" para alternar ver/ocultar movimientos archivados.
- Solicitada por: usuario (directive to fix UI/UX issues y añadir funcionalidad de archive)
- Actualizaciones realizadas:
  - Botón "Corte del día": cambiado de `bg-black` a estilo emerald con border para mejor contraste en dark/light mode.
  - Botón "Filtrar": estilo mejorado con icono SVG y color emerald, clase `flex items-center gap-2` para mejor usabilidad.
  - Botón "Limpiar": estilo secundario con borde slate, icono de escoba, claramente distinto al de filtrar.
  - Acciones de tabla: botones de archivar/eliminar ahora tienen `bg-slate-100 dark:bg-slate-700` con iconos fill=currentColor y tooltips descriptivos (`title="Archivar movimiento"` / `title="Eliminar movimiento"`).
  - Arquitectura: Agregado método `archive` en `CajaController.php` y ruta `caja.archive` en `routes/web.php`.
  - Nuevo botón "Ver archivados en general": alterna `show_archived=1` en los filtros para mostrar/ocultar movimientos archivados.