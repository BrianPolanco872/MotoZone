---
entity: feature-home-efectos-visuales-01
type: feature
---

- Objetivo: Hacer más atractiva y llamativa la home con efectos 3D en sus carruseles y un efecto hover más marcado en las cards, conservando el diseño, los colores y el contenido actuales.
- Alcance: Home pública; efecto 3D inspirado en el ejemplo para todos los carruseles, sutil en el hero y más visible en galerías de categorías/productos/novedades; reforzar elevación y sombra de las cards. Mantener controles, navegación táctil y comportamiento de carrusel existente.
- Fuera de alcance: Copiar la paleta o el diseño visual de Uiverse; rediseñar la home o cambiar contenido/datos; cambios al editor CMS u otras páginas; dependencias nuevas, cambios de esquema o funcionalidad de negocio.
- Criterios de aceptación: Efectos 3D visibles en carruseles de la home con intensidad adaptada al hero y tarjetas; cards mantienen un estado hover/focus perceptible sin duplicar estilos; paleta, contenido, controles y navegación existentes se conservan; movimiento reducido desactiva animaciones; build de Tailwind/Vite y manifest vigentes; CSS/JS servidos con HTTP 200 y MIME correcto en el entorno de aceptación; revisión visual responsive sin recortes, texto tapado ni errores de consola.
- Módulo/archivos: Home pública; resources/views/home.blade.php, resources/views/components/carousel.blade.php, resources/views/components/promo-card.blade.php, resources/css/app.css, resources/js/carousel.js y tests/Feature/HomeTest.php. resources/views/admin/home/edit.blade.php y tests/Feature/AdminHomeContentTest.php solo si el preview comparte el efecto.
- Solicitada por: usuario.
- Decisión visual confirmada: adaptar el efecto a todos los carruseles; hero sutil para proteger legibilidad y galerías de cards más expresivas.
- Contexto integrado: los carruseles ya tienen controles, autoavance opcional, pausa por puntero/foco y soporte prefers-reduced-motion; cards ya tienen elevación hover. No duplicar estos mecanismos. plan-hostinger-assets-01 cubre la entrega remota de assets y requiere URL/acceso para verificación real.
