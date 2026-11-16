---
entity: feature-home-hero-contacto-01
type: feature
---

- Objetivo: Ajustar la presentación pública de la home quitando el buscador y la llamada a explorar catálogo, incorporando los accesos de WhatsApp y Gmail, y retirando la franja superior roja/amarilla sin alterar el resto.
- Alcance: En el hero público, quitar “Explorar catálogo”, mostrar por separado WhatsApp y Gmail usando los datos de contacto configurados, y eliminar la franja decorativa superior.
- Fuera de alcance: Mantener intacto el resto de la home, el editor CMS, catálogo/POS/admin, otras páginas, formularios de búsqueda de otros módulos, contenido, fotos, categorías, tarjetas, ubicación, pie y enlaces de correo existentes fuera del hero.
- Criterios de aceptación: El hero no muestra buscador ni “Explorar catálogo”; muestra botones WhatsApp y Gmail cuando existen datos de contacto válidos; WhatsApp abre el enlace wa.me configurado; Gmail web abre un borrador dirigido al correo configurado del administrador y no envía automáticamente; desaparece la franja superior roja/amarilla; el resto de la home y las demás páginas conserva su comportamiento.
- Solicitada por: usuario.
- Módulo afectado: presentación pública de la home; sin otros módulos.
- Dependencia: el campo de correo configurado para contacto debe ser el destino Gmail del administrador y debe existir un WhatsApp válido.
- Contexto técnico: en resources/views/home.blade.php no hay un buscador público actualmente; los buscadores de CMS/POS/admin quedan intactos. El CTA y la franja sí existen en el hero/home.
- Complementa: feature-home-estilo-contacto-01.
- Última actualización: 2026-11-15.
- Estado: IMPLEMENTADO.
- Implementada en: fix-home-hero-contacto-01.
- Archivos tocados: app/Models/HomePage.php; resources/views/home.blade.php; resources/css/app.css; tests/Feature/HomeTest.php.
- Ampliación aprobada: ocultar el recuadro de descripción solo en la home pública, manteniendo el dato, la metadata SEO y la vista previa del editor.
- Ampliación implementada en resources/views/home.blade.php; regresión en tests/Feature/HomeTest.php.
