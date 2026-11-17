---
entity: hipotesis-H16-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: app/Models/User.php:164-170; routes/web.php:35,111-113.
- Mecanismo: ACCESS_MODULES.cut omite el permiso de acceso exigido por el grupo padre. Los helpers de pruebas de corte lo conceden siempre y no reproducen el alta con solo ese módulo.
- Verificación: resolución de permisos y grupos de rutas contrastados. No se crearon usuarios.
