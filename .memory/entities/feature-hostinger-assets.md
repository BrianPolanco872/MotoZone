---
entity: feature-hostinger-assets
type: feature
---

- Objetivo: Evitar que el sitio de MotoZone se muestre sin estilos en Hostinger y comprobar la instalación y entrega de sus assets de producción.
- Alcance: Revisar Composer/npm desde sus lockfiles, Tailwind v4/Vite, el build de CSS/JS y la publicación de assets en Hostinger; corregir únicamente problemas de instalación o entrega de assets, manteniendo las funciones actuales. Usar como ruta pública de Laravel `public` y preferir build local reproducible más publicación de `public/build`.
- Fuera de alcance: Cambiar funciones del programa, reglas de negocio, autenticación, base de datos/migraciones o rediseñar pantallas; actualizar versiones o agregar dependencias sin demostrar que es necesario; revelar o sobrescribir secretos de producción.
- Criterios de aceptación: `npm ci` y la instalación de Composer basada en `composer.lock` finalizan correctamente; `npm run build` genera un manifest válido y assets presentes; `public/hot` no se entrega ni existe en el hosting; las solicitudes de CSS/JS de la home y login en Hostinger responden HTTP 200 con tipo de contenido correcto; revisión visual de ambas pantallas sin estilos predeterminados ni errores de carga de CSS/JS; no cambia el comportamiento de las funciones existentes.
- Solicitada por: usuario.
- Plataforma seleccionada: Hostinger; document root a `public`, confirmado por el usuario.
- Estrategia: compilar localmente y publicar `public/build`; no compilar en Hostinger salvo que la implementación descubra que el flujo existente lo exige y se confirme acceso compatible.
- Dependencia pendiente: URL pública/staging de Hostinger para realizar la prueba HTTP y visual real.
