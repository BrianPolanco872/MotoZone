---
entity: plan-home-ui-02
type: plan
---

- Tipo: feature.
- Estado: IMPLEMENTADO; aprobado por el usuario el 2026-11-16.
- Objetivo: Mejorar visualmente la home con ajustes de paleta, bordes, sombras, espaciados y efectos hover/transiciones mediante edición estática de los archivos frontend existentes.
- Alcance: Edición de resources/views/home.blade.php, resources/css/app.css, resources/js/carousel.js y vistas/componentes frontend relacionados con presentación, carruseles, categorías y cards. Refinar la paleta vigente de MotoZone, estilos de bordes/sombras/espaciados y hover; incluir el efecto 3D de carrusel ya solicitado, adaptado a hero y galerías compactas. Integrar visualmente el contenedor de imagen del hero con el fondo crema mediante bordes redondeados, sombra/degradado y dar mayor jerarquía a cards y botones. Conservar datos, enlaces y comportamiento de navegación existente.
- Fuera de alcance: Ejecutar Bash o comandos de verificación; compilar assets con npm run build u otro mecanismo; validar tags/versiones Git; ejecutar pruebas; desplegar; modificar dashboard, tests/Feature/AdminTest.php, build.sh, backend, base de datos o dependencias.
- Criterio de aceptación único: Los cambios de los archivos frontend acordados quedan guardados correctamente en el repositorio local. No se exige ni se declara compilación, pruebas, despliegue o verificación de ejecución.
- Pasos de implementación:
  1. Editar únicamente los archivos frontend dentro del alcance y conservar cualquier cambio local ajeno.
  2. Refinar clases Tailwind y reglas ya existentes en app.css para colores, bordes, sombras, espaciado, hover y transiciones bajo el scope de la home.
  3. Ajustar carousel.js y los componentes de carrusel solo para efectos visuales; mantener controles y navegación existentes sin ampliar funcionalidad.
  4. Guardar los archivos fuente en el repositorio local. No ejecutar build, pruebas, validaciones Git ni despliegue.
- Archivos previstos:
  - resources/views/home.blade.php: clases visuales de secciones y tarjetas de categorías/productos/novedades.
  - `/admin/home/vista-previa`: la ruta usa HomeController::preview() y `view('home')`; los cambios en home.blade.php se reflejan también ahí, no hay plantilla distinta para este endpoint.
  - resources/views/admin/home/edit.blade.php: no es la plantilla del endpoint anterior; su preview embebido solo se modifica si resulta necesario para mantener coherente la presentación del hero.
  - resources/views/layouts/site.blade.php: solo la rama de home, si se requiere ajustar la cabecera/pie vinculados a la home; mantener el resto intacto.
  - resources/views/components/carousel.blade.php: presentación visual de slides y controles, sin cambiar su contrato genérico.
  - resources/views/components/promo-card.blade.php: estilos de tarjetas promocionales y sus galerías.
  - resources/views/components/product-card.blade.php: no tocar salvo necesidad; es compartido y cualquier estilo debe quedar limitado a la home.
  - resources/css/app.css: paleta/efectos visuales limitados al selector home existente.
  - resources/js/carousel.js: transiciones visuales del carrusel, sin cambios de contenido ni de navegación.
- Plan de rollback: restaurar únicamente los archivos frontend tocados a su contenido anterior, sin borrar cambios ajenos ni modificar assets generados, datos o historial Git.
- Riesgos y dependencias: Los cambios Tailwind/app.css no serán visibles hasta que el usuario compile y publique los assets manualmente; esta sesión no confirmará su carga. Los estilos de componentes compartidos pueden afectar otras pantallas, por lo que se deben limitar al scope de home. La interacción del carrusel no se validará en runtime según el alcance solicitado. No hay dependencias externas nuevas.
- Alternativas consideradas: Mantener los colores existentes con nuevos efectos (no elegida; se autorizó ajustar la paleta). Crear un tema global (descartado para mantener la edición limitada a la home). No hay decisión arquitectónica pendiente.
- Contexto integrado: la home ya tiene estilos bajo home-storefront y las cards tienen hover básico; el carrusel conserva autoavance opcional, flechas y pausa por puntero/foco. El plan previo plan-home-efectos-visuales-01 fue cancelado por solicitud del usuario y se conserva para trazabilidad.
- Cambios aplicados: Hero con marco cálido redondeado y sombreado; paleta, tarjetas, botones y controles de carrusel reforzados; efecto de profundidad 3D en galerías de la home respetando movimiento reducido. `/admin/home/vista-previa` refleja los cambios porque reutiliza `home.blade.php`.
- Desviaciones del plan original: Ninguna. No se ejecutaron build ni pruebas, según el alcance aprobado.
