---
entity: feature-home-cards-promocionales
type: feature
---

- Objetivo: editor de inicio basado en tarjetas promocionales por sección (categorías, productos, recién llegados), creadas a mano con foto principal, carrusel opcional, textos, precio/oferta manuales, badge y orden, sin depender del inventario ni del POS.
- Alcance: hero y textos siguen en `home_pages`; tres secciones de cards en `home_cards` con visibilidad y reordenamiento; home pública renderiza cards activas; dashboard muestra las cards de productos destacados.
- Límites: 6 fotos por card (portada incluida), JPEG/PNG/WebP, 20 MB y 6000×6000; oferta ≤ precio; enlace interno o HTTPS.
- Estado: IMPLEMENTADO.
- Implementada en: fix-home-cards-promocionales-01.
- Última actualización: 2026-11-11.
