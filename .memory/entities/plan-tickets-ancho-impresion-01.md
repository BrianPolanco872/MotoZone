---
entity: plan-tickets-ancho-impresion-01
type: plan
---

- Tipo: bug-fix.
- Estado: APROBADO explícitamente por el usuario mediante «aplicalo» el 2026-11-20, después de solicitar la retirada de la leyenda de cambios/devoluciones. Testing requiere solicitud explícita adicional; en esta implementación solo se comprueban sintaxis y formato.
- Fecha: 2026-11-20.
- Objetivo: corregir el tamaño y la distribución del ticket al imprimir en Chrome, conservando la información actual salvo la leyenda de cambios/devoluciones cuya retirada pide expresamente el usuario, y aproximando su estructura a la foto de referencia.
- Bug: bug-tickets-ancho-impresion-01.
- Causa confirmada: hipotesis-H1-tickets-ancho-impresion-01; el ancho de la vista independiente se vuelve automático al imprimir y las cuatro declaraciones size con milímetros y auto son inválidas. La confirmación abarca código local, no la configuración del controlador ni todos los síntomas físicos.

## Alcance

- Corregir impresión de última venta desde POS, reimpresión desde historial/enlaces de venta y prueba de impresión de preferencias.
- Mantener las opciones existentes de papel 58/80 mm y el valor predeterminado de 80 mm; limitar el ancho del contenido al área imprimible disponible, sin detección automática de impresora.
- Conservar campos, opciones de visibilidad, QR, precios, impuestos y totales actuales. Usar la referencia visual para estructura, no copiar sus datos ni agregar turnos, pagos u otras funciones.
- Encabezado centrado; descripción con salto de línea; cantidades/importes alineados; separación entre columnas; totales destacados y pie después del contenido.
- Reutilizar receipt.blade.php y los flujos actuales; no añadir dependencias ni un nuevo motor de impresión.
- Ampliación solicitada 2026-11-20 mediante «elimina lo de devoluciones entonces»: retirar la leyenda completa «Conserve este ticket para cambios y devoluciones» del comprobante. No eliminar el módulo de devoluciones, operaciones ni historial. Esta actualización del alcance no marca aprobado el resto del plan ni ejecuta código o configuración.

## Fuera de alcance

- Borrado de inventario, ventas, caja, cortes, migraciones, permisos, cambios contables y borrados reales. El bloqueo actual de inventario necesita diagnóstico separado de producción; no queda resuelto por este plan.
- Configuración remota del sistema operativo/controlador, ESC/POS, corte de papel, cajón y garantía de compatibilidad con toda impresora o papel de 50 mm. Las preferencias actuales solo admiten 58/80 mm.
- Atribuir al CSS el desplazamiento del fragmento «s y devoluciones» al inicio del papel. Su causa física sigue sin demostrarse; retirar la leyenda completa es ahora una petición expresa de contenido, no una afirmación de haber diagnosticado ese desplazamiento. No eliminar subcadenas ni alterar historial.

## Pasos ordenados de implementación

1. Inspeccionar estado y diferencias locales; respaldar exclusivamente los archivos afectados para conservar cambios ajenos. Confirmar que siguen presentes las líneas identificadas y que la versión a publicar corresponde a la revisada.
2. Sustituir todas las declaraciones inválidas de tamaño por una política CSS válida que respete el papel seleccionado en Chrome/controlador. Usar un margen coherente de 4 mm y no inventar una altura fija para tickets de longitud variable. El tamaño de papel del dispositivo seguirá siendo una selección externa.
3. Mantener al imprimir un ancho de contenido correspondiente al ancho guardado menos los márgenes; limitarlo al espacio disponible y centrarlo horizontalmente. Eliminar el crecimiento a toda la hoja en la vista independiente. En Carta/A4 debe permanecer una columna de ticket, no estirarse a todo el papel; en papel menor debe respetar el límite disponible.
4. Aplicar las mismas reglas de ancho y márgenes a POS y al ejemplo de preferencias. Ajustar el CSS existente de las tablas sin nueva abstracción: descripción flexible, columnas numéricas con espacio y alineación a la derecha, totales en dos columnas. Evitar que nombres largos empujen cantidades/importes fuera del ticket. Conservar el orden y valores actuales de receipt.
5. Retirar la leyenda completa de cambios/devoluciones: dejar vacía la política predeterminada en config/pos.php y vaciar el campo existente «Política de cambios y devoluciones» de la configuración guardada de la instalación afectada, conservando todos los demás valores y QR. La configuración guardada prevalece sobre el valor predeterminado: cambiar solo config/pos.php no elimina una leyenda ya guardada. Usar el formulario/servicio existentes, sin ocultar el campo ni suprimir la capacidad futura de personalización. Conservar «Gracias por su compra» y el resto del pie. No tocar producción durante implementación local sin coordinar acceso y respaldo de esa preferencia.
6. Añadir regresiones dirigidas a las pruebas existentes: salida de ambas rutas con 58/80 mm, preferencias de visibilidad y campos vacíos, contenido escapado, nombres largos e importes grandes, y mantenimiento de importes de venta. Ampliar el arnés JS existente de POS solo lo necesario para verificar el flujo real de impresión y que no vuelva a inyectar una regla inválida. Estas pruebas no demuestran geometría física: requerir también revisión visual.
7. Ejecutar las pruebas dirigidas únicamente en entorno aislado, nunca con la BD de producción, y revisar visualmente las tres rutas en Chrome para 58/80 mm y una hoja Carta/A4. Comprobar ancho, orden, recortes y separación de columnas con nombres largos y varias partidas. Imprimir físicamente con el usuario antes de declarar resueltos los síntomas del papel; si la vista previa queda correcta pero el papel no, continuar el diagnóstico del controlador sin culpar de nuevo al HTML.
8. Preparar los assets de producción con el flujo existente tras autorización de implementación; publicar Blade, JS/CSS compilados y manifest como una misma versión. No editar assets minificados a mano. Confirmar que no se despliegue public/hot y que la instalación publicada sirva los assets correspondientes; la publicación no se ejecuta durante planificación.

## Archivos afectados y razón

- resources/views/pos/tickets/ticket.blade.php: corregir regla de ancho en impresión, tamaño de página y distribución del comprobante independiente.
- resources/views/pos/index.blade.php: hacer coherentes ancho, márgenes y tablas del contenedor impreso desde POS.
- resources/views/pos/tickets/settings.blade.php: alinear dimensiones y columnas de la impresión de ejemplo sin alterar el formulario; la política vacía debe reflejarse en su vista previa.
- config/pos.php: cambiar exclusivamente la política predeterminada a texto vacío; conservar mensaje de agradecimiento, ancho y restantes opciones.
- Configuración persistida pos/ticket-settings.json del disco local: vaciar únicamente ticket.policy mediante el mecanismo existente en la instalación afectada, con respaldo del valor previo y autorización operativa antes de acceder a producción. No sustituir el archivo completo por valores locales.
- resources/js/pos/pos.js: quitar/corregir la declaración inválida inyectada antes de imprimir; conservar carga del HTML, imágenes y manejo de errores.
- resources/js/ticket-settings.js: quitar/corregir la regla inválida de la impresión de ejemplo manteniendo su ancho seleccionado.
- tests/Feature/TicketSettingsTest.php: cobertura del contrato HTML, datos y preferencias en ambas rutas; no presentar aserciones de texto como prueba de alineación visual.
- tests/pos.test.js: ampliar el arnés ya existente para cubrir el clic de imprimir y la preparación del documento, sin nueva librería.
- public/build/manifest.json y assets generados: resultado del build para publicación consistente, no edición manual.
- resources/views/pos/tickets/receipt.blade.php: dependencia compartida revisada; conservar sus datos/orden. No modificar salvo que sea imprescindible para la distribución acordada; cualquier cambio debe afectar coherentemente ambos consumidores.

## Criterios de aceptación

- Última venta, reimpresión y ejemplo no contienen declaraciones de tamaño inválidas ni expanden el ticket a todo el ancho de Carta/A4.
- Con 58/80 mm configurados y seleccionados en el controlador, el comprobante cabe en el área imprimible, sin pérdida de columnas ni encabezados pegados.
- Encabezado centrado, descripción legible, cantidades/importes separados y alineados, total destacado y pie al final en la vista previa.
- Los nombres largos se envuelven sin tapar números; se conservan todas las partidas y los importes exactos, también con miles y decimales.
- Campos desactivados o vacíos siguen sin mostrarse, QR solo si está habilitado/cargado, y no se incorporan los datos del ticket de referencia.
- La leyenda completa y su fragmento «s y devoluciones» no aparecen en el HTML de última venta/reimpresión ni en la vista previa de preferencias una vez vaciada la política; se conserva el agradecimiento. Verificar tanto el valor predeterminado vacío como la configuración persistida vaciada, sin quitar soporte a políticas personalizadas futuras.
- No se modifica información de ventas, productos, caja, cortes ni permisos. En preferencias solo se cambia ticket.policy, expresamente solicitado; conservar los demás valores y el QR.
- Se verifica que la versión publicada sirve la corrección y sus assets; una captura local no basta para declarar arreglada producción.
- La salida física queda pendiente de aceptación hasta comprobarla en la impresora del usuario; retirar el texto de origen no demuestra la causa del desplazamiento anterior ni descarta contenido retenido por la cola/controlador.

## Rollback

- Restaurar únicamente los archivos propios modificados desde el respaldo previo, sin descartar cambios locales ajenos. Reponer la pareja anterior de manifest y assets junto a sus vistas en caso de publicación fallida.
- No requiere rollback de BD: no hay migraciones ni modificaciones contables. Si se vacía la preferencia persistida, respaldar previamente su texto y restablecer solo ticket.policy desde ese valor, sin pisar otras preferencias. No borrar el archivo de configuración ni imágenes QR.

## Riesgos y alternativas

- Chrome/controlador controlan papel, escala y márgenes físicos; CSS válido no instala un controlador ni garantiza autoajuste universal. Puede ser necesario seleccionar papel 58/80 mm, escala 100 % y desactivar encabezados/pies del navegador.
- Las reglas de impresión están duplicadas en tres vistas y dos preparadores JS: deben quedar coherentes para que una ruta no siga fallando. Se conserva la estructura existente por ser el cambio más pequeño; no se crea motor nuevo de plantillas.
- El margen de 4 mm puede necesitar ajuste posterior según el área imprimible real; no asumir el ancho exacto del rollo ni dar por resuelto el papel con pruebas de HTML.
- Alternativa descartada: imponer una altura de página arbitraria para hacer válida una pareja de longitudes; puede cortar tickets largos o generar espacio excesivo.
- Alternativa descartada: PDF o ESC/POS con nuevas dependencias; excede la corrección demostrada y no elimina la necesidad de un controlador adecuado.
- Trabajo previo integrado: plan-pos-03 y pruebas TicketSettingsTest existentes; se preservan métodos de pago y preferencias. plan-productos-limpiar-tabla-01 se mantiene separado y no se reabre por este plan.

## Estado de esta sesión

- Solo se registró el diagnóstico anterior y este plan en .memory/ con sus relaciones. No se aplicó código, configuración, migración, build, despliegue ni prueba.

## Aplicación autorizada 2026-11-20

- Estado: IMPLEMENTADO.
- Alcance del estado: código fuente y preferencia LOCAL implementados; no se da por cumplida la aceptación de producción ni impresión física. Testing, build y publicación permanecen pendientes.
- Implementado en: fix-tickets-ancho-impresion-01.
- Cambios aplicados: ancho imprimible acotado/centrado en tres rutas, reglas @page válidas, tablas flexibles con separación numérica y retirada de inyecciones JS inválidas. Política predeterminada y preferencia local vacías; agradecimiento y demás valores conservados. Pruebas de regresión añadidas pero no ejecutadas.
- Archivos tocados: config/pos.php; resources/views/pos/tickets/ticket.blade.php; resources/views/pos/tickets/receipt.blade.php; resources/views/pos/tickets/settings.blade.php; resources/views/pos/index.blade.php; resources/js/pos/pos.js; resources/js/ticket-settings.js; tests/Feature/TicketSettingsTest.php; tests/pos.test.js; storage/app/private/pos/ticket-settings.json (solo local).
- Verificación básica: lint PHP/JS correcto, compilación Blade correcta, Pint passed, git diff --check correcto y diff revisado contra el alcance.
- Desviaciones del plan original: no se ejecuta el paso de Testing sin solicitud explícita; no se realiza build ni publicación porque el tag v1.0.0 no apunta al HEAD actual. No se accede a producción ni se vacía su política persistida durante esta aplicación local.
- Requisito de build pendiente: package.json 1.0.0, HEAD b36c51361dc576e6b4c2290ed8df9b1c2384fdee, v1.0.0 en b787b899c0fabbd94af6cf037935a76124a850fd. No se cambia historial Git; construir únicamente mediante ./build.sh cuando se resuelva la discrepancia.
- Preferencia publicada pendiente: al desplegar, respaldar el valor actual y dejar vacío únicamente el campo Política de cambios y devoluciones en Tickets, conservando restantes preferencias y QR. Cambiar el valor predeterminado no anula una política ya guardada en el servidor.
- Respaldo: C:/Users/Administrador/AppData/Local/Temp/opencode/motozone-ticket-print-20261120-112624.
