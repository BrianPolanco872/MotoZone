---
entity: bug-preuso-19
type: bug
---

- Auditoría: #19. Desactivar producto enlazado conserva enlace público a una ficha que devuelve 404.
- Estado: CAUSA RAÍZ CONFIRMADA por carga de cards, destino y controlador de ficha.
- Evidencia: app/Http/Controllers/HomeController.php:69; app/Models/HomeCard.php:108-116; app/Http/Controllers/CatalogController.php:12.
- Caso: enlazar producto activo, desactivarlo después y visitar la tarjeta de inicio.
- Causa confirmada: hipotesis-H19-preuso.
- Fecha: 2026-11-16. Sin implementación. El hallazgo #18 está excluido por el usuario.
