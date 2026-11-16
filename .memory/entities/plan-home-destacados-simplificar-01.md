---
entity: plan-home-destacados-simplificar-01
type: plan
---

- Tipo: feature. Feature: feature-home-destacados-simplificar-01.
- Estado: IMPLEMENTADO (instrucción directa y explícita del usuario, sin ambigüedad de alcance).
- Alcance: card-form.blade.php condiciona subtítulo/badge/enlaces a Recién llegados; selector de producto disponible en Destacados y Recién llegados; botón «Ver más» automático para Destacados en components/promo-card.blade.php; regresión en AdminHomeContentTest.
- Diseño: $isNoveltySection = section === 'new' para subtítulo, badge y enlaces; descripción oculta solo en products; el selector de producto se comparte en products y new. En promo-card, las cards de section products ocultan badge, subtítulo y descripción, muestran su carrusel con data-carousel-auto=4000 y, si destination() existe, un botón «Ver más» (label fijo). carousel.js avanza cada 4 s, se pausa al pasar el puntero o enfocar y respeta prefers-reduced-motion.
- Fuera de alcance: validación, modelo, orden, Categorías, Recién llegados, home pública (ya soporta destino nulo).
- Rollback: revertir solo los fragmentos de card-form/edit/tests; sin cambios de BD.
- Verificación: php -l de la prueba, compilación Blade de card-form y edit, Pint y diff --check correctos. Testing no ejecutado.
