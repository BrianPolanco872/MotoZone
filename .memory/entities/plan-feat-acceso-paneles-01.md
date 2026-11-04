---
entity: plan-feat-acceso-paneles-01
type: plan
---

- Tipo: feature
- Objetivo: Login con nombre, correo y contraseña desde la home, con acceso visible a los paneles de administrador (/admin) y trabajador (/pos).
- Estado actual verificado: /login ya existe con correo + contraseña, throttle:5,1, roles admin/empleado vía spatie/laravel-permission y redirección por rol en LoginController::routeForUser; la home ya tiene un icono discreto de login en el navbar y un enlace en el menú móvil; el CRUD de usuarios ya captura name; el modelo User expone name.
- Pasos: (1) agregar name a LoginRequest con trim y colapso de espacios; (2) validar el nombre contra la cuenta en LoginController tras Auth::attempt, cerrando sesión con error en name si no coincide; (3) añadir el campo nombre a auth/login.blade.php; (4) convertir el icono del navbar en botón visible con texto; (5) agregar CTA de acceso en el hero de home.blade.php; (6) actualizar y ampliar AuthTest y HomeTest; (7) Pint y pruebas.
- Archivos afectados: app/Http/Requests/Auth/LoginRequest.php (regla name); app/Http/Controllers/Auth/LoginController.php (comparación normalizada del nombre y error); resources/views/auth/login.blade.php (campo nombre); resources/views/layouts/site.blade.php (botón visible desktop/móvil); resources/views/home.blade.php (CTA hero); tests/Feature/AuthTest.php y tests/Feature/HomeTest.php (cobertura).
- Criterios de aceptación: login con los tres campos; name requerido; nombre incorrecto no autentica; admin → admin.dashboard y empleado → pos.index; botón y CTA visibles en la home; rate limit intacto; pruebas en verde.
- Plan de rollback: revertir los cinco archivos de producción con git checkout -- (sin tocar historial). No aplica: no hay migraciones, datos ni dependencias nuevas.
- Riesgos: fricción si el nombre registrado difiere (tildes, orden de apellidos) — comparación normalizada a minúsculas y espacios colapsados, pero sensible a acentos; homónimos no son problema porque email es único; los tests existentes fallarán hasta enviar name; el error se muestra solo después de contraseña válida, evitando enumeración de usuarios.
- Alternativas descartadas: Auth::attempt con name+email+password (collation case-sensitive en SQLite vs MySQL y mensajes pobres); nombre solo informativo (sin valor de seguridad, rechazada por el usuario); modal en la home (duplica validación y estado).
- Handoff: la implementación corresponde al agente Fix/Implementación (03-fix.md), no al agente Plan.
- Estado: IMPLEMENTADO el 2026-11-02 (ver fix-acceso-paneles).
- Cambios aplicados: name obligatorio y normalizado en LoginRequest; validación del nombre contra la cuenta en LoginController con cierre de sesión y error en name; campo nombre en auth/login.blade.php; botón visible Iniciar sesión en navbar desktop/móvil; CTA Acceso al panel en el hero de la home; AuthTest y HomeTest actualizados y ampliados (15 tests en esos archivos).
- Verificación: 44 tests / 176 assertions en verde; Pint --dirty passed.
- Desviaciones del plan original: ninguna.
