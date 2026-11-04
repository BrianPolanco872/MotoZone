---
entity: fix-acceso-paneles
type: fix
---

- Objetivo: Login con nombre, correo y contraseña desde la home, con acceso visible a los paneles de administrador (/admin) y trabajador (/pos).
- Archivos tocados: app/Http/Requests/Auth/LoginRequest.php; app/Http/Controllers/Auth/LoginController.php; resources/views/auth/login.blade.php; resources/views/layouts/site.blade.php; resources/views/home.blade.php; tests/Feature/AuthTest.php; tests/Feature/HomeTest.php.
- Implementación: regla name required|string|max:255 con trim en prepareForValidation; comparación normalizada (squish + lower) contra $user->name tras Auth::attempt, con cierre de sesión y error en name si no coincide; helper logoutCurrentSession reutilizado por destroy; campo Nombre completo en el login con autofocus; botón visible Iniciar sesión en el navbar (icono + texto desde sm); CTA Acceso al panel en el hero de la home.
- Verificación: php artisan test --compact → 44 tests / 176 assertions en verde; Pint --dirty passed.
- Desviaciones: ninguna respecto del plan aprobado.
- Estado: IMPLEMENTADO el 2026-11-02.
