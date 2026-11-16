---
entity: fix-home-estilo-contacto-01
type: fix
---

- Objetivo: Adaptar solo la home al estilo proporcionado y añadir en Editar inicio los campos o módulos faltantes.
- Plan aprobado: plan-home-estilo-contacto-01; autorización del usuario «aplica el plan».
- Archivos tocados: app/Models/HomePage.php; app/Http/Requests/Admin/HomeContentRequest.php; resources/views/home.blade.php; resources/views/layouts/site.blade.php; resources/css/app.css; resources/views/components/icon.blade.php; resources/views/admin/home/edit.blade.php; resources/views/admin/home/partials/navigation.blade.php; nuevos partials contact.blade.php, location.blade.php, information.blade.php en resources/views/admin/home/partials/; tests/Feature/AdminHomeContentTest.php, HomeTest.php y CatalogTest.php. Además, registros .memory de este plan/feature/fix y relaciones.
- Implementación: variante explícita home-style para home y vista previa; paleta crema/arena/naranja, fuentes Inter/Space Grotesk limitadas a home, cabecera/pie claros y cards adaptadas mediante CSS bajo .home-storefront. Rama anterior de fichas conservada.
- Implementación: tres pestañas nuevas Contacto, Ubicación e Información en el CMS oscuro existente, con formularios PUT independientes, recuperación de borradores y anclas de errores. Datos públicos de mostrador, horarios, teléfono, WhatsApp, correo y mensaje; dirección/referencias y mapas; cuatro beneficios, tres indicadores, aviso y asesoría opcionales.
- Implementación: persistencia reutiliza home_pages.content, listas permitidas y fusión transaccional existentes. Enlaces wa.me/tel/mailto generados desde datos validados; burbujas de correo y WhatsApp independientes y ocultas sin dato válido. Sin contactos ficticios ni promesas comerciales por defecto.
- Mapas: URL oficial HTTPS Google /maps/embed?pb=... validada por host/ruta, separada del enlace de navegación/corto; pin OpenStreetMap mediante coordenadas emparejadas con rangos y soporte de cero, bbox acotado y atribución visible. Sin HTML arbitrario, resolución de redirecciones, SDK ni claves API.
- Conservado: cards, familias/anclas/vínculos manuales, precios editoriales, galerías, orden, visibilidad, acceso al panel, permisos y rutas. Sin migraciones, cambios de dependencias, inventario/POS/caja/cortes o configuración productiva.
- Ajuste mínimo respecto a archivos candidatos: HomeContentController no necesitó modificación. Los nuevos datos y switches no se agregaron a DEFAULT_CONTENT, por lo que reset() ya conserva contacto, mapa e información complementaria; se añadió regresión y ayuda explícita en el editor. Sin reescribir home-editor.js ni componentes de productos.
- Verificación: PHP -l correcto en modelo, request y tres archivos de pruebas; ocho vistas Blade compiladas a archivos temporales y PHP -l correcto. Tailwind compilado en memoria sin escribir artefactos (43963 caracteres en comprobación); Pint --dirty --format agent con filtro temporal solo del alcance y Pint explícito de los cinco archivos PHP correctos; diff --check del alcance sin errores.
- Entrega local: no existían public/hot ni build.sh. Se inició npm run dev -- --host 127.0.0.1 como servidor de desarrollo, sin build; public/hot referencia http://127.0.0.1:5173. CSS HTTP200 incluye .home-storefront; home en http://127.0.0.1:8000/ HTTP200 con variante home y @vite/client, sin WhatsApp ficticio anterior.
- Integridad de datos: exportación actual de home_pages comparada con respaldo JSON, idéntica tras aplicar código. No se guardó ni publicó configuración comercial real; el usuario debe completar y activar los nuevos campos/bloques.
- Pendiente: Testing requiere solicitud explícita. Regresiones añadidas, NO ejecutadas. Sin herramienta de navegador disponible, no se verificaron aspecto visual/clics autenticados ni mapas externos en navegador. No se generó build ni se modificó historial Git.
- Incidencia ajena: git diff --check global detecta espacios finales en resources/views/pos/cuts/history.blade.php:107-117. Archivo fuera del plan, no modificado ni corregido por esta implementación.
- Rollback: copias del estado previo de archivos y home-pages.json en C:/Users/Administrador/AppData/Local/Temp/opencode/home-estilo-contacto-01-20261115-161354. Revertir solo fragmentos de esta tarea; retirar tres partials nuevos si corresponde; conservar/exportar claves nuevas de content, nunca restaurar BD completa ni archivos desde HEAD sobre cambios previos/posteriores.
- Estado: IMPLEMENTADO; comprobación visual y Testing pendientes.
- Última actualización: 2026-11-15.

## Ampliación: retirar botón de presentación

- Confirmación del usuario: «si», tras consultar retirada de los dos campos y del botón público.
- Objetivo: quitar Texto del botón (Ver novedades) y Enlace de destino de Presentación, y el botón manual del hero público/vista previa.
- Archivos de esta ampliación: resources/views/admin/home/edit.blade.php; resources/views/home.blade.php; tests/Feature/HomeTest.php y AdminHomeContentTest.php.
- Cambios: eliminados los dos fragmentos Blade, añadidas regresiones de ausencia y conservación de configuración al editar presentación, ajustada fixture del test de escape a los campos aún renderizados. No se modificó BD, modelo, validación ni otros botones.
- Rollback: respaldo previo de cuatro archivos en C:/Users/Administrador/AppData/Local/Temp/opencode/home-quitar-boton-presentacion-20261115-171128; restaurar solo esta ampliación, sin sobrescribir cambios posteriores.
- Verificación: compilación Blade y sintaxis PHP correctas en ambas vistas y pruebas; Pint --dirty con filtro del alcance más Pint explícito correctos; diff --check del alcance sin errores. Pruebas NO ejecutadas, Testing requiere solicitud explícita.
- Estado de la ampliación: IMPLEMENTADO.

## Complementación visual

- Objetivo: intensificar el color y los efectos de la home sin alterar fichas ni otras páginas.
- Archivos tocados en esta ampliación: resources/views/home.blade.php; resources/views/layouts/site.blade.php; resources/css/app.css; resources/js/app.js; resources/js/home-categories.js; resources/js/home-parallax.js.
- Cambios: hero con galería real de fondo, carrusel y parallax que respeta movimiento reducido; header/contacto responsive; superficies, categorías, destacados, novedades, ubicación y footer con gradientes, sombras y estados hover; filtro de categorías con reset y desplazamiento accesible.
- Verificación: compilación en memoria de las dos vistas Blade y `node --check` en los tres módulos JS pasaron. No se ejecutaron tests ni build según el plan; validación visual en navegador pendiente.
- Estado: IMPLEMENTADO.

## Diagnóstico de no aplicación visual

- Hallazgo: la foto principal está guardada y `galleryUrls()` devuelve una imagen; las vistas compiladas de Blade contienen el nuevo marcado.
- Causa: no hay `public/hot`; el manifest apunta a `app-DpVxQZd1.css`/`app-Pw9YJOvP.js` de las 18:44, anteriores a los cambios de las 19:27–19:32, y el CSS servido no contiene `.home-hero-overlay` ni `.cms-hero-preview-overlay`.
- Acción: no se reconstruyeron ni sirvieron assets en esta revisión. El plan prohíbe build y no existe `build.sh`; queda pendiente autorizar/resolver el paso de assets para que el navegador reciba el CSS actualizado.
- Corrección local: Vite dev quedó iniciado en `127.0.0.1:5173`; `public/hot` enruta Laravel al CSS/JS fuente actuales sin build. La home en `127.0.0.1:8000/` devuelve 200, su HTML usa `home-storefront` y el módulo CSS incluye los selectores nuevos; la imagen responde HTTP 200. No se abrió navegador.

## Foto principal como fondo

- Objetivo: mostrar en Presentación y en la home la foto principal como fondo ancho, con degradado y texto superpuesto.
- Archivos tocados: resources/views/admin/home/edit.blade.php; resources/views/home.blade.php; resources/css/app.css.
- Cambios: el preview del editor presenta la galería como fondo con degradado de izquierda a derecha, muestra los textos actuales y aclara el uso de la foto principal; el hero público usa altura mínima de 550 px en escritorio y overlay blanco más cercano a la referencia. Se conservan CTAs existentes y no se agregan textos comerciales de ejemplo.
- Verificación: compilación Blade en memoria y `git diff --check` correctos. Sin Testing/build; vista de navegador pendiente.
- Estado: IMPLEMENTADO.
