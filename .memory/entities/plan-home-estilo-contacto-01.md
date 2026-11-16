---
entity: plan-home-estilo-contacto-01
type: plan
---

- Tipo: feature.
- Feature: feature-home-estilo-contacto-01.
- Objetivo: Adaptar solo la home al estilo proporcionado y añadir en Editar inicio los campos o módulos faltantes.
- Estado: PROPUESTO; no aplicar código, configuración, migraciones, comandos de pruebas/build ni operaciones de historial Git en esta sesión.
- Aprobación posterior: usuario solicita «aplica el plan» en esta conversación. Estado: APROBADO para implementación, sin Testing ni build.
- Respaldo previo: C:/Users/Administrador/AppData/Local/Temp/opencode/home-estilo-contacto-01-20261115-161354, archivos del alcance y exportación JSON de home_pages; no se modificaron registros.

## Alcance y límites

- Estilo de la home: fondos crema #fbf9f5 y arena #f6f3eb, superficies blancas, acento naranja #ea580c, tipografía Inter/Space Grotesk solo en home, cabecera limpia sin franja oscura, hero cálido, tarjetas redondeadas, pie claro y burbujas de WhatsApp/correo. Usar Tailwind v4/Vite instalado, nunca pegar el CDN/configuración Tailwind v3 del ejemplo.
- Reutilizar las cuatro secciones, imágenes/carruseles, cards, precios editoriales, familias, vínculos manuales, orden y visibilidad existentes; no sustituirlos por productos, cifras o fotografías del ejemplo.
- Editar inicio conserva su interfaz CMS actual. Agregar únicamente formularios para datos y secciones presentes en el diseño pero ausentes: Contacto/mostrador; Ubicación/mapa; Información complementaria (beneficios, indicadores y asesoría).
- No modificar POS, inventario, caja, cortes, empleados, autenticación/permisos, fichas de producto ni otras páginas. No restaurar /catalogo. No crear un constructor de páginas ni añadir paquetes, tablas o migraciones.

## Evidencia y trabajo previo integrado

- app/Models/HomePage.php:16-55 ya define textos y contenido JSON; current() no escribe en GET y lockForEditing() protege escrituras editoriales. Guardar los nuevos campos en home_pages.content.
- app/Http/Requests/Admin/HomeContentRequest.php:11-19 limita campos por bloque y sus anclas; :41-71 valida las listas permitidas y tipos. Extender esto, manteniendo el contrato de guardado parcial y completo.
- app/Http/Controllers/Admin/HomeContentController.php:57-77 ya fusiona solo contenido validado en una transacción. No duplicar el guardado. reset():124-129 mezcla DEFAULT_CONTENT: impedir que una ampliación de defaults borre contactos/coordenadas reales al restablecer apariencia.
- resources/views/admin/home/edit.blade.php:17 y :160 contienen referencias fijas a cuatro bloques; adaptar resumen/ayuda a los nuevos bloques visibles. navigation.blade.php necesita nuevas pestañas y anclas de errores. home-editor.js descubre formularios/pestañas por atributos, por lo que no requiere reescritura para añadir bloques.
- resources/views/layouts/site.blade.php:153 contiene un WhatsApp fijo; además hay teléfono, dirección y horarios fijos en cabecera/pie. Es un layout compartido por home.blade.php y catalog/show.blade.php: el nuevo marcado/estilo/contacto se activa explícitamente desde la vista home, incluida vista previa, no de forma global ni solo mediante routeIs('home'). Mantener intacto el render de fichas.
- promo-card.blade.php se usa en la home; evitar modificar su contrato o componentes product-card/product-visual compartidos. Ajustar presentación mediante estilos locales de home y utilidades en sus contenedores.
- Historial integrado: plan-editor-inicio-cms-01, plan-home-cards-promocionales-01, plan-home-familias-editor-01 y plan-retirar-catalogo-publico-01. Sus implementaciones conservan CMS oscuro, cards independientes de inventario y familias manuales con anclas; /producto continúa disponible y /catalogo retirado.
- Bugs en memoria: bug-corte-reabrir-dia-01 afecta DailyCut/POS/routes. Ningún archivo propuesto de este plan coincide; routes/web.php queda excluido.
- Verificación de herramientas: MCP memory_* y codesearch_* no disponibles según descubrimiento; respaldo .memory/ y búsqueda local. .ai/rules no existe. Skills aplicables: ponytail activo, tailwindcss-development, laravel-best-practices y testing-best-practices. Laravel instalado 13.38.0 y PHPUnit 12.5.40, comprobados con composer show --direct; package.json declara Tailwind ^4.3.3.

## Campos y comportamiento

- Contacto/mostrador: nombre y descripción breve del mostrador, teléfono para llamadas, WhatsApp con código de país, correo, horarios como texto y mensaje inicial para WhatsApp. Datos opcionales vacíos por defecto; interruptores independientes para burbuja de correo y WhatsApp. No confundir estos datos públicos con usuarios, SMTP o configuración de tickets.
- Ubicación: dirección, referencia, enlace para llegar en Google Maps, modalidad de mapa (Google insertado o pin por coordenadas), URL de inserción Google, latitud/longitud y mostrar/ocultar bloque. El pin es una ubicación real por coordenadas, no una etiqueta dibujada sobre el centro de un mapa que se mueve. Primera versión sin selector arrastrable ni geocodificación.
- Google: enlace compartido/de navegación para Abrir cómo llegar; URL oficial de Compartir > Insertar mapa para iframe. No prometer que un enlace corto se convierte automáticamente en iframe. Aceptar solo URLs HTTPS con host/ruta permitidos, sin HTML arbitrario, sin resolver redirecciones desde el servidor. El modo pin genera el iframe de OpenStreetMap y su marcador a partir de coordenadas, como el proveedor presente en el HTML de referencia; atribución del proveedor visible.
- Coordenadas: ambas juntas, latitud entre -90 y 90 y longitud entre -180 y 180; cero es válido. Requisitos condicionales según modalidad; validar URL de iframe por separado del enlace de navegación. No pedir credenciales API ni facturación.
- Información complementaria: posiciones fijas para hasta cuatro beneficios (título/texto), tres indicadores (valor/etiqueta), mensaje informativo de mostrador y título/descripción de asesoría, con visibilidad por sección. Usar claves planas y formularios simples; no constructor genérico ni estructuras editoriales nuevas. Reutilizar los mismos beneficios/datos en pie si se repiten visualmente.
- No publicar por defecto 12,500 SKUs, puntuaciones, garantía 100%, disponibilidad, tiempos de respuesta ni dirección del ejemplo. No calcular abierto/cerrado automáticamente a partir de horarios libres: el aviso es texto editorial, no un estado operativo automático.
- Construir wa.me, tel y mailto desde los datos validados; codificar mensaje/asunto y escapar salida. Las cotizaciones pueden incluir título/SKU real disponible sin cambiar el destino principal de las cards/familias. Si falta el dato o el control está apagado, ocultar el enlace/burbuja; nunca conservar un número ficticio como fallback de home. mailto abre el cliente de correo configurado, no necesariamente Gmail.
- Nuevos bloques opcionales/no visibles sin contenido. Guardar un bloque no borra otros, fotos, cards o vínculos; mostrar errores y recuperar borradores en la pestaña correspondiente.

## Pasos de implementación

1. Antes de implementar, guardar copias del estado actual de los archivos del alcance y exportar solo el contenido de home_pages; existen muchos cambios previos locales y archivos sin seguimiento. No usar HEAD como respaldo ni tocar cambios ajenos.
2. Ampliar los metadatos de campos/valores no comerciales de HomePage y pequeñas funciones de URL estrictamente necesarias para que todos los consumidores de contacto/mapa de la home compartan los mismos datos. Mantener las consultas editoriales actuales.
3. Extender HomeContentRequest con bloques Contacto, Ubicación e Información, sus anclas, validaciones tipadas, visibilidad y listas permitidas, también para guardado sin block. No añadir nuevas rutas ni permisos. Mantener la fusión transaccional existente; asegurar que restablecer apariencia conserve datos de contacto/mapa.
4. Incorporar formularios/pestañas en Editar inicio reutilizando componentes CMS, guardado por formulario y controles existentes. No rediseñar ni reescribir el editor ni su JavaScript. Usar inputs de email/tel/URL/coordenadas con límites adecuados, en lugar de forzar el partial genérico limitado a 160 caracteres sobre la URL del mapa.
5. Aplicar el estilo de referencia solo a la home y su vista previa: cabecera, hero, categorías, destacados, recién llegados y pie. Conservar datos, anclas, visibilidad, navegación de familias, galerías y acceso al panel. Evitar cambios globales de tema, reset CSS o scrollbar.
6. Renderizar bloques nuevos, mapa lazy con título accesible y enlaces de navegación reales; conectar cabecera, hero, mostrador, asesoría, pie y burbujas de la home al contacto único. Los enlaces opcionales de cotización no reemplazan los vínculos actuales de las cards.
7. Añadir regresiones focalizadas y revisar visualmente home/editor en móvil/escritorio, teclado, foco y movimiento reducido. Proponer ejecución específica al implementar; esta sesión no ejecuta pruebas ni build.

## Archivos a tocar y razón

- app/Models/HomePage.php: textos/datos de contacto/mapa/secciones nuevas y generación de URLs compartida dentro del dominio home; reutilizar content JSON.
- app/Http/Requests/Admin/HomeContentRequest.php: bloques/anclas nuevos, listas permitidas y validaciones de correo/teléfono/mapa/coordenadas/visibilidad.
- app/Http/Controllers/Admin/HomeContentController.php: únicamente ajuste focalizado de restablecer para preservar datos operativos si se incluyen nuevas claves en defaults; update ya sirve para persistencia parcial.
- resources/views/home.blade.php: diseño crema y naranja, secciones faltantes, contacto y activación explícita del estilo home para público/vista previa.
- resources/views/layouts/site.blade.php: variante de cabecera/pie/burbujas limitada a home; preservar exactamente la rama de otras páginas, datos recibidos explícitamente sin consultas en Blade.
- resources/css/app.css: añadir reglas bajo un selector exclusivo de home y cargar fuentes solo allí; no modificar tokens globales ni .home-cms.
- resources/views/admin/home/edit.blade.php: incluir formularios faltantes y actualizar resumen de bloques/ayuda sin rediseñar el editor.
- resources/views/admin/home/partials/navigation.blade.php: nuevas pestañas, anclas y recuperación de errores sin alterar navegación existente.
- resources/views/admin/home/partials/contact.blade.php (nuevo): formulario de contacto/mostrador y burbujas.
- resources/views/admin/home/partials/location.blade.php (nuevo): formulario de dirección y mapa con instrucciones diferenciadas para Google/pin.
- resources/views/admin/home/partials/information.blade.php (nuevo): campos fijos de beneficios, indicadores y asesoría.
- resources/views/components/icon.blade.php: solo añadir icono de correo si se utiliza x-icon para la burbuja; no cambiar iconos existentes ni importar Material Symbols.
- tests/Feature/AdminHomeContentTest.php: persistencia/validación/roles/guardado parcial/restablecer y pestañas nuevas.
- tests/Feature/HomeTest.php: contacto/mapa/burbujas, ocultación vacía, preservación de datos y vista previa sin escrituras.
- tests/Feature/CatalogTest.php: regresión para que la ficha de producto no adopte el estilo exclusivo de home; no cambiar implementación de ficha.
- No se prevén cambios en HomeController, HomeCard, rutas, migraciones, factories, paquetes, home-editor.js ni componentes compartidos de productos; HomePageFactory ya hereda DEFAULT_CONTENT.

## Criterios de aceptación verificables

- La home y su vista previa usan la estética crema/arena/naranja de referencia y se adaptan a móvil/escritorio sin desbordamiento.
- Las fichas de producto y demás pantallas mantienen su presentación y comportamiento anteriores.
- Editar inicio conserva las opciones previas y permite guardar contacto, ubicación/mapa y los contenidos complementarios que se muestran en la home.
- Un correo/WhatsApp configurado es el mismo en burbuja, botones y bloques correspondientes; campos vacíos o interruptores apagados no producen enlaces rotos ni contactos de ejemplo.
- Google insertado y pin por coordenadas muestran la ubicación guardada; enlaces cortos no se presentan como iframes. Coordenadas inválidas, pares incompletos, HTML y URLs no permitidas se rechazan sin guardar cambios.
- El correo usa mailto y WhatsApp wa.me con número internacional y mensaje codificado.
- Guardar un bloque conserva el resto; restablecer presentación no elimina el contacto/mapa reales; permisos actuales siguen vigentes.
- Se preservan cards, galerías, categorías manuales, precios editoriales, vínculos, orden y visibilidad; GET/vista previa no escriben datos ni publican ejemplos.
- La home no añade enlaces a /catalogo ni afirmaciones comerciales ficticias; cifras editoriales son manuales y contadores de familias, si se muestran, provienen de vínculos reales.

## Verificación prevista

- Casos en AdminHomeContentTest: guardado válido de cada bloque y DB; roles invitado/empleado; email/teléfono/URL/coordenadas inválidos; campos desconocidos o de otro bloque; coordenada cero; alternancia de modalidad de mapa; guardar contacto no cambia hero/cards; restablecer conserva contacto y ubicación.
- Casos en HomeTest: URLs wa.me/tel/mailto codificadas y escapadas; ocultación vacía/apagada; iframe Google permitido y pin OSM correcto; textos escapados; mismos datos en todos los consumidores; vista previa aplica diseño y no escribe; contratos de cards y familias intactos.
- Regresión CatalogTest con producto existente: no aplica selector/variante visual de home. Considerar HomeFamilyTest/HomeCardTest existentes para conservación de navegación/contenido.
- En implementación, ejecutar solo las suites del alcance con php artisan test --compact y rutas de archivos, cuando corresponda autorizar Testing; comprobar PHP/JS y ejecutar Pint --dirty --format agent si se modifica PHP, evitando incorporar formateos ajenos. Revisión visual de móvil/escritorio obligatoria para validar fidelidad, no sustituible por aserciones de clases.
- En esta sesión solo hubo lecturas, búsquedas, git status --short y composer show --direct; sin pruebas, build, datos ni código modificados.

## Rollback

- Volver únicamente los fragmentos de esta adaptación desde las copias inmediatamente anteriores, comparando cualquier modificación posterior; retirar únicamente los partials nuevos de este plan. No git reset/restore global ni restauración desde HEAD.
- Conservar HomePage.content con las claves nuevas si se revierte la presentación: el código anterior las ignora. No migraciones/down, eliminación de fotos/cards o restauración total de BD sobre ventas nuevas. Exportar los datos de contacto/mapa antes de cualquier limpieza posterior y solicitar autorización para borrarlos.

## Riesgos y dependencias

- Layout/CSS compartidos: activar variante/selector solo desde home y cubrir la ficha con regresión; no cambiar el tema del resto del sistema.
- Muchos cambios locales previos: respaldar estado actual y aplicar cambios puntuales; no sobrescribir archivos completos ni mezclar otras tareas.
- Mapas y fuentes son servicios externos y pueden estar bloqueados o no disponibles; conservar dirección/enlace de ruta y tipografía de respaldo. Mapa tiene carga lazy y atribución. No añadir claves/API ni seguimiento nuevo.
- Datos comerciales no confirmados: dejarlos vacíos/ocultos; no copiar contactos, valoraciones ni promesas del ejemplo. Horarios son informativos, sin cálculo de apertura automática.
- Pin de primera versión por coordenadas y Google por URL oficial de inserción; sin arrastrar un marcador en el editor, autocompletar direcciones o conversión automática de enlaces cortos. Comunicar este límite, no ocultarlo.
- mailto depende del cliente de correo del visitante; no asegura apertura en Gmail.

## Alternativas consideradas

- Reutilizar content JSON y formularios actuales (elegida): sin migraciones ni nueva infraestructura y conserva guardado parcial.
- Añadir CMS genérico, tablas/contacto global o SDK de mapas: descartado por exceder la petición y añadir dependencias/decisiones innecesarias.
- Pegar todo el HTML/CDN o cambiar tokens globales: descartado por incompatibilidad con Tailwind v4, datos ficticios y riesgo de alterar otras pantallas.
- No hay decisión arquitectónica pendiente que obligue a ampliar alcance: se soportan dos formas de ubicación dentro del mismo bloque y se mantiene el sistema editorial existente.

- Última actualización: 2026-11-15.
- Estado: IMPLEMENTADO.
- Cambios aplicados: home y vista previa con variante crema/naranja aislada de fichas; editor con Contacto, Ubicación e Información; validación/persistencia JSON por bloque; enlaces y burbujas desde contacto único; Google insertado o pin OSM por coordenadas; bloques opcionales sin datos comerciales ficticios. Detalle real y archivos en fix-home-estilo-contacto-01.
- Desviaciones del plan original: ninguna ampliación. No hizo falta modificar HomeContentController: mantener nuevas claves fuera de DEFAULT_CONTENT ya conserva contacto/ubicación al restablecer; tampoco fue necesario cambiar el JavaScript del editor.
- Verificación final: sintaxis PHP y ocho vistas Blade correctas, Tailwind compilado solo en memoria, Pint acotado y diff --check del alcance correctos; home y CSS HTTP200 vía Vite; home_pages idéntico al respaldo. No Testing, build ni revisión visual en navegador.
- Implementado en: fix-home-estilo-contacto-01.

## Ampliación aprobada: quitar botón manual de presentación

- Solicitud: «en el de presentacion quita el boton de ver novedades y el de enlace de nuevo destino». Confirmación posterior «si» a retirar los campos del editor y el botón de la home pública.
- Alcance: quitar los inputs hero_button y hero_button_url de Presentación y el enlace manual correspondiente del hero público/vista previa; conservar contacto, acceso al panel y demás contenido. Mantener datos y validación histórica sin cambios ni borrados en BD.
- Archivos: resources/views/admin/home/edit.blade.php; resources/views/home.blade.php; tests/Feature/AdminHomeContentTest.php y HomeTest.php.
- Pasos: retirar únicamente los dos fragmentos Blade; adaptar cobertura de campos que dejaron de renderizarse y añadir regresiones de ausencia/preservación de datos; comprobar sintaxis/compilación sin ejecutar Testing ni build.
- Rollback: recuperar solo los fragmentos de esta ampliación desde C:/Users/Administrador/AppData/Local/Temp/opencode/home-quitar-boton-presentacion-20261115-171128, sin sobrescribir cambios posteriores ni tocar BD.
- Estado de la ampliación: APROBADA.
- Estado de la ampliación: IMPLEMENTADA.
- Cambios aplicados en la ampliación: retirados los campos Texto del botón/Enlace de destino del editor y el botón manual Ver novedades del hero público y de vista previa. Datos guardados y backend conservados; botones de contacto y acceso al panel intactos. Regresiones de ausencia y preservación añadidas; fixture de escape ajustada a campos que todavía se renderizan.
- Desviaciones de la ampliación: ninguna. Verificación: dos vistas Blade compilan y sus PHP temporales pasan php -l; dos archivos de pruebas pasan php -l; Pint acotado y diff --check del alcance correctos. Sin Testing, build ni cambios de BD o historial Git.

## Complementación visual

- Estado: IMPLEMENTADO.
- Cambios aplicados: home con fondos más vivos; hero usa la galería real como fondo/carrusel y parallax con respeto a movimiento reducido; header y contacto basados en datos configurados; tarjetas y fondos de categorías, destacados, recién llegados, ubicación y footer con mayor contraste; filtro de categorías con reset accesible.
- Desviaciones del plan original: ninguna de alcance. No se ejecutaron tests ni build, tal como establece el plan. La revisión visual en navegador queda pendiente.
- Verificación básica: vistas home/layout compiladas en memoria con Blade; `node --check` pasó para `app.js`, `home-categories.js` y `home-parallax.js`.

## Foto principal como fondo

- Solicitud: reflejar en Presentación del editor una composición con la foto a pantalla ancha, degradado claro y textos encima; conservar los datos y acciones reales.
- Cambios aplicados: vista previa CMS ahora usa la galería guardada como fondo con overlay lateral, muestra presentación/subtítulo y aclara que la foto principal queda de fondo; hero público adopta 550 px mínimos en escritorio y degradado blanco más sutil.
- Archivos: resources/views/admin/home/edit.blade.php; resources/views/home.blade.php; resources/css/app.css.
- Verificación básica: ambas vistas Blade compilaron en memoria; `git diff --check` pasó en el alcance.
- No se ejecutó Testing ni build, según el plan. Revisión en navegador pendiente.
- Diagnóstico posterior: la foto principal sí está configurada (1 URL de galería); las vistas compiladas contienen el nuevo marcado, pero `public/build/manifest.json` y `app-DpVxQZd1.css` son anteriores a los fuentes, no incluyen los selectores del hero/preview y no existe `public/hot`. La presentación no llega al navegador porque faltó regenerar/servir los assets; el plan aprobado prohíbe build y el workspace no tiene `build.sh`.
- Corrección local sin build: iniciado Vite dev en `127.0.0.1:5173`; `public/hot` apunta al dev server. La home `127.0.0.1:8000/` responde 200, carga los módulos actuales y el CSS servido incluye los selectores del hero; imagen guardada responde HTTP 200. No se verificó en navegador.
