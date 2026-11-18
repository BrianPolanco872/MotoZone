---
entity: bug-home-carrusel-quitar-01
type: bug
---

- Reporte: X de foto de carrusel no la quita visualmente en Recién llegados; usuario pide repararla también en Destacados, Presentación y Categorías, sin borrar datos/fotos durante la reparación.
- Estado: CAUSA RAÍZ CONFIRMADA.
- Evidencia: media-upload.blade.php:55-58 renderiza label+checkbox remove_gallery[] y cambia únicamente el color de la X al marcar. home-editor.js no escucha esas casillas guardadas ni oculta sus miniaturas. No confundir con botones de fotos nuevas, que sí eliminan el archivo del borrador.
- Persistencia existente inspeccionada: GalleryService.keep conserva array_diff(current, remove); los controladores ya reciben remove_gallery. El arreglo debe conservar el input marcado para enviarlo al guardar, no eliminarlo del DOM ni ejecutar peticiones/borrados por clicar X.
- Callers confirmados: media-upload compartido por hero de Presentación y por card-form para products/new/categories. El arreglo previo de principal queda intacto.
- Estado posterior: CORREGIDO y verificado con 4 pruebas JS y 8 PHP aisladas (71 aserciones PHP). Fuente actual servida por Vite local; sin borrados ni modificaciones de fotos/datos reales. Sin comprobación manual en navegador.
