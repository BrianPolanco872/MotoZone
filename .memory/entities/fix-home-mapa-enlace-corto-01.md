---
entity: fix-home-mapa-enlace-corto-01
type: fix
---

- Objetivo: permitir enlaces maps.app.goo.gl para generar un mapa Google insertado al guardar Ubicación.
- Estado: IMPLEMENTADO.
- Archivos tocados: app/Services/GoogleMapsLinkResolver.php; app/Http/Requests/Admin/HomeContentRequest.php; app/Http/Controllers/Admin/HomeContentController.php; resources/views/admin/home/partials/location.blade.php; tests/Feature/AdminHomeContentTest.php.
- Cambios: resolver acotado a Google con redirecciones manuales y punto fiable; HEAD de origen, GET stream de conversión para obtener embed canónico; candidato aceptado solo en location; persistencia después de resolución y sin cambios si falla; ayuda/editor actualizados. No se insertan URLs cortas directamente ni se amplían iframes/dominios permitidos.
- Testing: aprobado explícitamente, 44/44 correctas y 239 aserciones. Red simulada y SQLite en memoria; fallos/riesgos/compatibilidad cubiertos. Una comprobación extra de Contacto falló por comparación de atributos factory/fresh y está registrada aparte, no arreglada dentro de esta feature.
- Verificación: sintaxis, Pint y diff correctos. Diagnósticos externos de solo lectura comprobaron ruta de conversión real; suites no contactaron Google.
- Desviaciones de alcance: ninguna. Ajuste de GET frente a HEAD requerido por respuesta real de Google, informado antes de cambiar.
- Limitaciones: ubicación real sin modificar y sin verificación visual en navegador; enlaces sin punto o Google inaccesible producen error y preservan datos. No hubo cambios de dependencias, esquema, JS/CSS, build o historial git.
- Fecha: 2026-11-17.
