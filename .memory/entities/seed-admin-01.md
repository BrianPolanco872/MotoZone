---
entity: seed-admin-01
type: seed
---

- Objetivo: Sembrar el administrador inicial en la base de datos MySQL local.
- Datos: name "motozone", email admin@motozone.com, rol admin, is_active true, email verificado.
- Contraseña: definida por el usuario; no se registra en memoria.
- Archivos: database/seeders/AdminUserSeeder.php (nuevo, idempotente con updateOrCreate + syncRoles); database/seeders/DatabaseSeeder.php (invoca AdminUserSeeder al inicio).
- Ejecución: php artisan db:seed --class=AdminUserSeeder --no-interaction.
- Verificación: name, email, rol admin, activo y Hash::check de la contraseña correctos; suite completa 44 tests en verde; Pint passed.
- Nota de seguridad: contraseña débil de bootstrap; cambiarla fuera de desarrollo.
- Estado: IMPLEMENTADO el 2026-11-02.
