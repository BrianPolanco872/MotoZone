---
entity: plan-quitar-categorias-01
type: plan
---

- Tipo: feature
- Objetivo: eliminar el módulo de categorías de productos (CRUD admin y clasificación por categoría) y dejar de mostrar secciones/enlaces/buscador del catálogo en la home, manteniendo /catalogo y /producto funcionando.
- Alcance: quitar solo el módulo de categorías; no tocar snapshots en sale_items ni DailyCutService; conservar tabla `categories` y columnas `category_id`.
- Decisión DB: hacer `products.category_id` nullable para permitir crear productos sin categoría, sin borrar datos existentes.
- Decisión catálogo: en home solo quitar CTA/buscador al catálogo (buscador del hero, enlace "Todas las categorías", botones "Ver catálogo completo"); conservar las secciones.
- Estado: IMPLEMENTADO
