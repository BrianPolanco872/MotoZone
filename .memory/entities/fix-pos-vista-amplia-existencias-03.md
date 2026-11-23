---
entity: fix-pos-vista-amplia-existencias-03
type: fix
---

- Objetivo: compactar total/botonera y ampliar fotos/información del filtro POS según solicitud explícita del usuario, sin lógica adicional.
- Archivos tocados: resources/views/pos/index.blade.php; resources/js/pos/pos.js. Memoria: plan-pos-vista-amplia-existencias-01, feature-pos-vista-amplia-existencias-01, esta entidad y relaciones.
- Cambios: total de dos niveles/gran panel a resumen compacto en línea con botonera adaptable; menos padding/márgenes y total de 24px, indicador de venta exitoso conservado al final. Fotos de resultados 80x80px con placeholder del mismo tamaño, texto de 14px, espacios alineados de 16px, mínimos de columna, código con corte seguro, nombre/categoría completos en varias líneas y números sin cortar; scroll horizontal del contenedor existente evita aplastar columnas.
- Alcance: solo clases de presentación; selección por fila, modal de imagen, precios, totales, inventario, columnas opcionales y altura fija del carrito intactos. Sin cambios backend, tests, dependencias ni datos.
- Verificación: node --check, php -l, Pint dirty y git diff --check correctos; diff revisado contra alcance. No tests nuevos para cambios triviales de estilo, no Testing ni navegador ejecutados.
- Build: NO ejecutado; HEAD está etiquetado v1.0.1 pero composer.json/package.json declaran 1.0.0. La regla de versionado exige detener ./build.sh hasta resolver discrepancia. No se modifican tags/versiones ni assets compilados a mano.
- Estado: IMPLEMENTADO EN FUENTE.
- Fecha: 2026-11-22.
