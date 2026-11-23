---
entity: fix-pos-vista-amplia-existencias-06
type: fix
---

- Objetivo: ampliar la tabla del POS hasta aprovechar el espacio de pantalla y bajar la barra de total/cobro al borde inferior, según capturas del usuario.
- Archivos tocados en este ajuste: resources/views/layouts/admin.blade.php; resources/views/pos/index.blade.php; resources/js/pos/pos.js; tests/Feature/PosAccessTest.php; tests/pos.test.js. Memoria de plan/feature/relaciones; artefactos public/build/ generados.
- Cambios: main solo en pos.index con min-height del viewport descontando cabecera64px+borde1px, flex vertical y padding inferior8px; cuerpo POS y sección del detalle flex-grow. Búsqueda/cabecera/cobro no se comprimen; detalle mínimo28rem y área de filas con height0, min-height388px, flex-grow y overflowauto sin antiguo tope388px. Filas no aumentan altura exterior; en pantallas sin espacio mínimo, página desplazable sin controles cortados. Reabrir conserva ancho propio mediante self-start.
- Estado vacío: tabla con h-full solo sin items para centrar su mensaje en toda la zona ampliada; h-full retirado al agregar para no estirar filas. Colspan y campos existentes conservados.
- Lógica: sin cambios de cálculos, inventario, metadatos, modal, campos ni APIs. Nuevos tests adaptan estructura POS y clase h-full vacía/agregar/quitar, sin ejecutarlos.
- Verificación: php -l y node --check de archivos cambiados, Pint dirty y git diff --check correctos. ./build.sh completado tras verificar versiones raíz1.0.1, HEAD/tag v1.0.1 coincidentes y ausencia public/hot.
- Artefactos: app-_TdHXwkN.js y app-Di__5T_w.css. Advertencia de fontaine opcional no bloqueante, sin instalar paquetes nuevos.
- Estado: IMPLEMENTADO EN FUENTE Y BUILD LOCAL. Sin Testing, navegador, migraciones, despliegue ni historial Git modificado.
- Fecha: 2026-11-22.
