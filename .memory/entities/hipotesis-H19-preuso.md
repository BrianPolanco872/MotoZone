---
entity: hipotesis-H19-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: app/Http/Controllers/HomeController.php:69; app/Models/HomeCard.php:115-116; app/Http/Controllers/CatalogController.php:12.
- Mecanismo: cards carga product sin restringir actividad y destination enlaza cualquier producto existente; show rechaza el inactivo. Validar activo al crear el vínculo no cubre desactivación posterior.
- Verificación: flujo completo y pruebas HomeCard/Catalog contrastados; falta el caso desactivar después de enlazar.
