---
entity: bug-home-fotos-quitar-01
type: bug
---

- Reporte: al quitar foto principal y borrar su enlace en Fotos de la card, la foto del carrusel permanece y al quitarla no desaparece.
- Estado: CAUSA RAÍZ CONFIRMADA por lectura de eventos, render y persistencia.
- Causa principal: home-editor.js:346-359 marca eliminación, vacía URL y despacha input; el handler de input en :338-344 desmarca incondicionalmente mainRemove. El formulario termina sin bandera remove_image, por lo que el controlador conserva la principal. Borrar solo la URL también restaura la preview guardada sin indicar borrado.
- Evidencia adicional: media-upload.blade.php:54-59 usa checkbox para cada foto guardada del carrusel, pero no hay listener que quite/oculte la miniatura; solo cambia el color de la X. GalleryService.keep sí elimina correctamente los paths enviados en remove_gallery.
- Confusión de portada: card-form.blade.php usa card.coverUrl() como foto principal del editor; coverUrl puede devolver la primera de gallery cuando no hay principal. La UI puede mostrar una foto de galería como si la principal siguiera guardada. HomeCard.galleryUrls antepone la principal, por lo que borrar solo el item de galería no oculta una principal que realmente siga persistida.
- Alcance: controles de foto principal y galería del editor de inicio compartidos por cards y hero. No borrar automáticamente la galería al quitar principal ni tocar la foto propia del producto vinculado.
- Alcance finalmente aprobado: únicamente quitar principal; mejoras de carrusel retiradas.
- Estado posterior: CORREGIDO EN FUENTE y servidor Vite local activo por autorización explícita; pendiente validación de guardado en navegador y Testing. No se borraron fotos durante la implementación ni se publicó build de producción.
