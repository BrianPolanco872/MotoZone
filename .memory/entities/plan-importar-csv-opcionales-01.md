---
entity: plan-importar-csv-opcionales-01
type: plan
---

- Tipo: bug-fix
- Aprobación explícita en este chat: usuario «si aprobado no tardes».
- Solicitud: usuario pide que marca y descripción dejen de ser obligatorias al importar para completarlas después manualmente.
- Objetivo: importar productos sin columnas marca/descripcion o con sus celdas vacías, manteniendo el resto de reglas y sin modificar el alta o edición manual.
- Diagnóstico: importAttributes mapea campos ausentes a null; importRules exige ambos. El esquema de products declara brand y description NOT NULL.
- Paso 1: mapear marca y descripcion ausentes/vacías a cadena vacía y hacer opcionales esas dos reglas exclusivamente en importRules, preservando string/max cuando haya valor. No usar null ni textos inventados, ni agregar migraciones.
- Paso 2: actualizar la ayuda de importación para marcar ambos campos como opcionales y conservar codigo/nombre/precio_venta requeridos.
- Paso 3: cubrir columnas ausentes y celdas vacías con pruebas de importación; ajustar el caso de errores múltiples para usar datos realmente inválidos según las nuevas reglas y comprobar que los errores restantes siguen visibles sin cargas parciales.
- Archivos: app/Http/Controllers/Admin/ProductController.php; resources/views/admin/products/index.blade.php; tests/Feature/AdminProductTest.php.
- Preservar: campos obligatorios restantes, transacción, unicidad, omisión de SKU existente, formatos CSV, mensajes y acciones ajenas al importador. No modificar ProductRequest ni registros existentes.
- Verificación: revisión de diff, php -l y Pint --dirty --format agent; Testing solo con solicitud explícita. Sin importación real, build, nuevas dependencias ni operaciones de historial git.
- Plan de rollback: revertir únicamente este ajuste en los tres archivos respetando reparaciones previas; no habrá cambios de esquema ni escrituras reales.
- Memoria: fallback .memory/ porque MCP de memoria no disponible en esta sesión.
- Fecha: 2026-11-17.
- Estado: IMPLEMENTADO.
- Cambios aplicados: marca y descripcion opcionales solo en importRules; importAttributes usa cadena vacía si faltan para respetar columnas NOT NULL. Ayuda actualizada. Añadidos dos casos de regresión para columnas ausentes/celdas vacías; caso de errores múltiples ajustado a nombre/precio faltantes y stock negativo.
- Verificación: Pint --dirty --format agent y php -l de controlador/pruebas correctos; git diff --check sin errores; revisión del diff confirma alcance y conservación de cambios previos. No se modificó ProductRequest ni se escribieron datos reales.
- Desviaciones del plan original: ninguna.
- Testing: no ejecutado; requiere solicitud explícita. Tampoco se probó el CSV real ni el navegador.
