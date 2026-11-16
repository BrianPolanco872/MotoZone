---
entity: feature-home-ui-02
type: feature
---

- Objetivo: Mejorar visualmente la home con ajustes de paleta, bordes, sombras, espaciados y efectos hover/transiciones mediante edición estática de los archivos frontend existentes.
- Alcance: resources/views/home.blade.php, resources/css/app.css, resources/js/carousel.js y vistas/componentes relacionados con hero, carruseles, categorías y tarjetas; refinar la paleta y añadir efectos inspirados en la rotación 3D. El contenedor de imagen del hero se integra con el fondo crema mediante bordes redondeados, sombra/degradado y jerarquía visual para tarjetas y botones.
- Fuera de alcance: Ejecutar Bash o comandos de verificación; npm run build o cualquier compilación de assets; validar tags/versiones Git; ejecutar pruebas; desplegar; modificar dashboard, AdminTest.php, build.sh, backend, base de datos o dependencias.
- Criterio de aceptación único: Los cambios de los archivos frontend acordados quedan guardados correctamente en el repositorio local.
- Solicitada por: usuario.
- Estado: IMPLEMENTADA por fix-home-ui-02; aprobada por el usuario el 2026-11-16.
- Vista previa: `/admin/home/vista-previa` usa HomeController::preview() y retorna `view('home')`; comparte resources/views/home.blade.php, sin plantilla pública separada.
- Reemplaza por solicitud del usuario a plan-home-efectos-visuales-01, ahora cancelado.
