---
entity: feature-home-categorias-panel-01
type: feature
---

- Objetivo: Refactorizar y mejorar el diseño visual de la sección de tarjetas de categorías de productos en la vista actual.
- Alcance: panel suave con borde y padding; grid simétrico 1/2/3 columnas; tarjetas verticales de altura uniforme; acento naranja, hover y foco; texto con ancho completo; descripciones de dos líneas; cabecera con icono y badge; acción sutil; marcos de imagen 16:9 con object-cover. Conservar contenido dinámico, destinos, galerías, filtros y aviso de ejemplo.
- Fuera de alcance: controladores, rutas, lógica de backend, datos dinámicos, archivos de imágenes originales y otras secciones fuera del bloque de tarjetas de categorías. Sin dependencias nuevas ni cambios al componente compartido de carrusel.
- Criterios de aceptación: panel bg-slate-50/90, rounded-2xl, border-slate-200/80 y p-6 sm:p-8; grid de una columna en móvil, dos desde md y tres desde lg, gap-6; tarjetas de igual altura con texto legible sin colapso horizontal; imágenes 16:9 sin deformación; icono, badge veraz y acción coherente con destino; descripciones line-clamp-2 con espacio reservado; contenido, orden, galerías y enlaces existentes conservados; resto de la vista intacto.
- Archivos afectados: resources/views/home.blade.php; localización aprobada por usuario. La misma vista sirve a la home pública y /admin/home/vista-previa.
- Categorías solicitadas: Llantas y Cámaras, Accesorios y Cascos, Eléctrico y Baterías, Filtros y Aceites, Frenos y Suspensión, Motor y Transmisión. No hardcodear ni insertar registros: renderizar la colección actual. No se consultó la base de datos; no está verificada la presencia actual de seis registros. CATEGORY_PRESETS contiene cinco ejemplos y no contiene Llantas y Cámaras; no modificar esos presets.
- Solicitada por: usuario.
- Estado: PLANIFICADA; implementación no realizada por el agente de planes.
- Fecha: 2026-11-17.
- Estado: IMPLEMENTADO.
- Implementada en: fix-home-categorias-panel-01.
- Verificación pendiente: apariencia y controles en navegador con assets actualizados; Testing requiere solicitud explícita.
- Ajuste solicitado e implementado 2026-11-17: todas las fotos y fallbacks en marcos h-48 sm:h-52 con object-cover centrado; panel slate-100/90 con sombra interior y cards blancas con sombra hover moderada. Este criterio reemplaza el aspecto 16:9 inicial. Estado: IMPLEMENTADO; mismo fix-home-categorias-panel-01.
- Segundo refinamiento autorizado e implementado 2026-11-17: !h-48 idéntico a todos los anchos, imágenes !h-full !w-full object-cover con object-top solo en Accesorios y Cascos; panel !bg-slate-100 border-slate-300/80 y cards !bg-white p-5. Sustituye el criterio h-48 sm:h-52 anterior. Estado: IMPLEMENTADO en fuente, no validado visualmente; assets locales obsoletos y build bloqueado por tag/HEAD desalineados.
- Ampliación bug-fix autorizada e implementada 2026-11-17 en fix-home-categorias-panel-01: footer usa colección editorial o vacía; retirada consulta inválida a categories.is_active. Archivo adicional resources/views/layouts/site.blade.php; sin cambios de datos/esquema. Estado: IMPLEMENTADO. Pint y sintaxis Blade/PHP correctos; sin Testing ni comprobación HTTP.
