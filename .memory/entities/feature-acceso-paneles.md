---
entity: feature-acceso-paneles
type: feature
---

- Objetivo: Que desde la home el administrador y los trabajadores inicien sesión en su panel (/admin o /pos) con un login que solicita nombre, correo y contraseña.
- Alcance: agregar el campo nombre al login y validarlo contra la cuenta; botón visible de acceso en el navbar de la home (desktop y móvil) y CTA en el hero; conservar la redirección por rol ya existente; actualizar pruebas.
- Fuera de alcance: registro público, recuperación de contraseña, verificación de email, 2FA, username único con migración, nuevo dashboard de empleado, login embebido o modal en la home, cambios al CRUD de usuarios.
- Criterios de aceptación: el login muestra nombre, correo y contraseña; falta de nombre o nombre que no coincide con la cuenta no autentica; credenciales válidas llevan a admin → /admin y empleado → /pos; la home muestra un botón visible y un CTA hacia /login; el rate limit de 5/min se conserva; AuthTest y HomeTest en verde con Pint limpio.
- Solicitada por: usuario
- Fecha: 2026-11-02
- Decisiones aprobadas 2026-11-02: el nombre se valida contra la cuenta (no es solo informativo); el acceso es un botón visible más la página /login existente (sin modal); el trabajador sigue yendo a /pos.
- Estado: IMPLEMENTADO el 2026-11-02.
- Implementada en: fix-acceso-paneles.
