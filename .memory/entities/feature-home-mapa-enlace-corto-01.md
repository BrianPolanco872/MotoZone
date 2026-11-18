---
entity: feature-home-mapa-enlace-corto-01
type: feature
---

- Estado: PLANIFICADA; implementación pendiente de aprobación de plan.
- Objetivo: permitir pegar un enlace compartido maps.app.goo.gl en el apartado del mapa y obtener un mapa Google insertado sin copiar otra URL manualmente.
- Solicitud explícita: usuario proporciona https://maps.app.goo.gl/r6GZ2qGtkTtopaqz5 y confirma que quiere verlo dentro de la página, no solo como botón Cómo llegar.
- Estado actual: HomePage.isGoogleMapsUrl acepta enlaces cortos para navegación, pero embed solo admite HTTPS Google /maps/embed con pb; HomeContentRequest rechaza cortos como inserción y la ayuda lo prohíbe. Cambio es una feature nueva, no desactivar validación sin convertir.
- Evidencia externa de solo lectura: HEAD del enlace del usuario respondió 302 a Google Maps /maps/place/Motorefacciones+Motozone con punto !3d21.0049966!4d-89.3959592. GET a maps.google.com/maps con q=esas coordenadas y output=embed respondió 301 a URL Google /maps/embed?origin=mfe&pb=... compatible con la validación actual. Esa URL de inserción respondió 200 sin X-Frame-Options SAMEORIGIN. No se guardó ninguna ubicación ni se siguieron redirecciones automáticas en el diagnóstico.
- Criterio: seleccionar Google, pegar el enlace corto y Guardar ubicación debe persistir un src de inserción seguro para el mismo punto; Cómo llegar, pin manual y otras secciones conservan sus datos.
- Límite: convertir únicamente cuando se puedan determinar coordenadas fiables del lugar, no usar centro de cámara como ubicación ni inventar coordenadas. Ante fallo, informar y conservar ubicación previa.
- Estado: IMPLEMENTADO.
- Implementada en: fix-home-mapa-enlace-corto-01.
- Uso: Editar inicio > Ubicación, seleccionar Google Maps (enlace corto o insertado), pegar corto en Enlace corto o URL de inserción de Google Maps y Guardar ubicación. No se guarda automáticamente la ubicación del enlace aportado durante implementación.
- Verificación: 44 pruebas relacionadas aisladas y 239 aserciones correctas; sin prueba visual en navegador. Fallo separado de fixture de Contacto registrado sin cambios de esa prueba.
