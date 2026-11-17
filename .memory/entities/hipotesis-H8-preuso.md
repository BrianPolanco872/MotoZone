---
entity: hipotesis-H8-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: app/Http/Controllers/Admin/CajaController.php:91-120; routes/web.php:188-194.
- Mecanismo: ruta admin no exige rol admin; export exige gestionar caja pero filtra propietario solo si el usuario lo solicita, sin restricción obligatoria cuando falta pos.sales.day.any. La ruta POS sí impone esa restricción.
- Verificación: ambos exportadores y middleware contrastados. No se ejecutaron peticiones contra datos reales.
