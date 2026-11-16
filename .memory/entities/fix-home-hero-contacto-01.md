---
entity: fix-home-hero-contacto-01
type: fix
---

- Objetivo: Actualizar la presentación pública de la home con botones WhatsApp/Gmail, sin el CTA de catálogo ni la franja roja/amarilla superior.
- Archivos tocados: app/Models/HomePage.php; resources/views/home.blade.php; resources/css/app.css; tests/Feature/HomeTest.php.
- Cambios aplicados: añadido enlace de redacción Gmail validado y codificado al correo de contacto configurado; botones independientes WhatsApp/Gmail en el hero; retirados el CTA “Explorar catálogo” y el marcado/estilo de la franja hazard; preservados otros enlaces mailto, ubicación y resto de módulos.
- Buscador: ya estaba ausente del hero público, por lo que no se modificó; buscadores de CMS/POS/admin quedan intactos.
- Verificación: Pint focalizado; php -l en modelo y prueba; compilación Blade con `php artisan view:cache --no-interaction`; `git diff --check` de los archivos del plan, sin errores. No se ejecutaron pruebas.
- Diagnóstico posterior: `http://127.0.0.1:8000/` responde 200 y no contiene buscador, CTA de catálogo ni franja; Vite en `127.0.0.1:5173` responde 200. En la configuración local no hay WhatsApp ni correo válidos, por eso las acciones se ocultan según el plan. No se reprodujo el buscador en el entorno local; falta URL/entorno si sigue visible en otra instancia. Sin cambios de código adicionales.
- Ampliación aprobada e implementada: se ocultó en la home pública el recuadro que imprime `hero_description`; se conserva para el editor preview, datos y metadata SEO. HTTP local 200 confirma que no aparece el párrafo del hero.
- Estado: IMPLEMENTADO.
