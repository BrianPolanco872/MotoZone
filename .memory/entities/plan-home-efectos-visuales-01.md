---
entity: plan-home-efectos-visuales-01
type: plan
---

- Tipo: feature.
- Objetivo: Hacer más atractiva y llamativa la home con efectos 3D en sus carruseles y un efecto hover más marcado en las cards, conservando el diseño, los colores y el contenido actuales.
- Alcance: Efecto 3D inspirado en el ejemplo en todos los carruseles de la home, adaptado por tamaño (hero sutil; galerías de cards más visibles); reforzar el hover/focus de las cards. Mantener colores, contenido, controles, swipe y autoavance actuales.
- Fuera de alcance: Copiar estilos/colores de Uiverse, rediseñar estructura/contenido, modificar CMS u otras páginas, dependencias nuevas, cambios de datos o migraciones.
- Pasos:
  1. Baseline de carruseles, hover de cards, selectores aislados bajo home-storefront y soporte actual de movimiento reducido; no duplicar reglas existentes.
  2. Marcar de forma explícita los carruseles de hero, categorías y promo-cards para activar el efecto solo en la home pública/vista previa correspondiente.
  3. Extender el componente y carousel.js con transiciones 3D adaptativas manteniendo flechas, swipe, autoavance, pausa por puntero/foco y prefers-reduced-motion; no añadir dependencia.
  4. Reforzar el efecto de elevación/sombra existente en cards y ofrecer respuesta equivalente a teclado/focus, sin alterar su contenido ni sus enlaces.
  5. Añadir regresiones focalizadas para el marcado de carruseles/card y la vista previa si comparte el efecto; ejecutar las pruebas afectadas.
  6. Compilar Tailwind/Vite, comprobar que el manifest referencia los assets recién generados y que CSS/JS cargan con HTTP 200 y MIME correcto; revisar visualmente escritorio/móvil y reduced-motion en navegador. Verificar también el entorno de staging/Hostinger antes de aceptar; sin URL/acceso, no declarar esa verificación completa.
- Archivos afectados:
  - resources/views/home.blade.php: activar el efecto de forma explícita en hero y carrusel de categorías.
  - resources/views/components/carousel.blade.php: exponer la información/estado por diapositiva necesario para la transición 3D sin cambiar el uso genérico por defecto.
  - resources/views/components/promo-card.blade.php: activar la variante de home en galerías de destacados y novedades.
  - resources/css/app.css: estilos y transiciones 3D bajo home-storefront; respetar reduced-motion y la paleta vigente.
  - resources/js/carousel.js: sincronizar la transición con controles, swipe y autoavance existentes, sin romper pausa por puntero/foco.
  - tests/Feature/HomeTest.php: regresiones de render y preservación de contenido/controles.
  - tests/Feature/AdminHomeContentTest.php: solo si la vista previa reutilizada refleja el efecto y requiere cobertura.
  - resources/views/layouts/site.blade.php, vite.config.js, resources/js/app.js y package.json: inspeccionar carga actual, sin cambios previstos; el layout ya usa @vite para CSS y JS, Vite ya procesa Tailwind v4 y app.js ya importa carousel.js.
- Criterios de aceptación:
  1. Los carruseles con múltiples fotos de la home muestran un efecto 3D visible; el hero conserva legibilidad con intensidad menor y las galerías compactas pueden mostrar una rotación más expresiva.
  2. Flechas, navegación táctil, autoavance existente y pausa por puntero/foco siguen funcionando.
  3. Las cards tienen elevación/sombra más perceptible al hover y focus; no se incorporan colores ni composición de Uiverse.
  4. No hay cambios visibles o funcionales fuera de la home; el contenido, enlaces y orden actuales se conservan.
  5. prefers-reduced-motion desactiva la rotación/transición, sin movimiento residual.
  6. npm run build finaliza; public/build/manifest.json apunta a CSS/JS existentes con los nuevos estilos; public/hot no se usa en producción.
  7. Home servida en el entorno de aceptación referencia CSS/JS actuales que responden HTTP 200 y MIME correcto; la revisión visual/network en navegador confirma que no se ve el estilo viejo, no hay recursos faltantes ni errores de consola.
- Plan de rollback: revertir solo los cambios de marcado/estilos/JS/tests de esta feature, conservando cambios ajenos; si el nuevo bundle falla, restaurar el directorio public/build previo completo. Sin cambios de base de datos ni eliminación de datos.
- Riesgos/dependencias: Un anillo 3D fuerte en el hero puede competir con el texto o recortar imágenes; calibrar y verificar en móvil/escritorio. La transformación no debe interferir con scroll-snap/controles. El repositorio registra un incidente previo de public/hot/assets y plan-hostinger-assets-01; la prueba remota necesita URL y acceso a staging/Hostinger. El movimiento debe respetar reduced-motion.
- Alternativas consideradas: anillo uniforme en todas las superficies (descartado por legibilidad del hero); dejar el hero sin efecto 3D (descartado tras elección del usuario de aplicarlo a todos); variante adaptada por tamaño seleccionada.
- Revisión técnica: búsqueda local al no estar disponibles codesearch_*; Tailwind CSS 4.3.3 vía Vite ya instalado, @vite carga CSS/JS, JS de carrusel ya importado y cards ya tienen hover básico. MCP memory_* no disponible; memoria guardada por fallback en .memory/.
- Se apoya en: plan-home-estilo-contacto-01, plan-categorias-fotos-carrusel-01 y plan-hostinger-assets-01.
- Estado: CANCELADO por solicitud del usuario; sustituido por plan-home-ui-02. No aplicar.
