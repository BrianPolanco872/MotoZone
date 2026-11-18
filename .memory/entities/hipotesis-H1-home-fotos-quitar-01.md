---
entity: hipotesis-H1-home-fotos-quitar-01
type: hipotesis
---

- Estado: CONFIRMADA por secuencia de eventos síncrona inspeccionada, sin ejecución funcional ni borrados reales.
- Archivo/líneas: resources/js/home-editor.js:338-359 (auto-desmarcado de remove al despachar input); :244-245 y :235-237 (restaura preview anterior si URL vacía); resources/views/admin/partials/media-upload.blade.php:54-59 (checkbox de galería sin ocultar); resources/views/admin/home/partials/card-form.blade.php:103 (coverUrl en lugar de imageUrl para principal).
- Contrato backend confirmado: HomeCardController.payloadWithRemoved :242-253 conserva gallery menos remove_gallery y elimina principal si remove_image es true; al perder la bandera no se borra. HomeContentController usa el mismo patrón con remove_hero_image. No se encontró causa en GalleryService.keep.
- Mecanismo: estado visual de borrado y datos enviados divergen; al guardar vuelve la principal no eliminada y aparece también como primera imagen del carrusel público.
