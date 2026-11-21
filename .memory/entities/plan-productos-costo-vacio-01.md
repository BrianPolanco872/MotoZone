---
entity: plan-productos-costo-vacio-01
type: plan
---

- Tipo: bug-fix
- Estado: PAUSADO; propuesta previa sin aprobar. Usuario aclaró que introdujo 7.50 y exige conservarlo. No implementar el plan basado solo en costo vacío ni generar uno nuevo hasta confirmar la pérdida del dato.
- Objetivo: permitir guardar productos con costo vacío sin error SQL; corregir también min_stock por la misma causa confirmada.
- Causa raíz: hipotesis-H1-productos-costo-vacio-01 CONFIRMADA.
- Paso 1: normalizar cost y min_stock en productData: en alta, vacío/null/ausente -> 0; en edición, campo no enviado conserva valor existente y campo explícitamente vacío/null -> 0. Valores válidos proporcionados se conservan sin recalcular precio ni IVA. Usar datos validados y API existente de FormRequest::validated.
- Paso 2: añadir cobertura de alta con omitidos, vacíos, null y valores válidos; edición preservando omitidos y normalizando vacíos, sin alterar precio/códigos ni el resto de atributos. Mantener wholesale_price nullable.
- Paso 3: verificar sintaxis PHP, Pint --dirty --format agent y diff. Testing requiere solicitud explícita; no ejecutar pruebas ni escribir inventario real por esta aprobación de implementación.
- Archivos: app/Http/Controllers/Admin/ProductController.php (solo productData); tests/Feature/AdminProductTest.php.
- Preservar: autorización, tipos/números no negativos, nombre/precio/IVA/existencias obligatorios, códigos editables, importación y recuperaciones anteriores. Sin nuevas dependencias, esquema, migraciones, cambios de frontend, build ni operaciones de historial git.
- Criterios: alta con cost vacío persiste 0; min_stock vacío persiste 0; omitir campos en actualización conserva datos; enviar valores válidos los respeta; wholesale_price vacío sigue nullable; precio/códigos no cambian por normalización.
- Plan de rollback: revertir exclusivamente estas normalizaciones y pruebas, conservando cambios previos; sin escrituras reales durante implementación ni cambio de esquema.
- Herramientas: memoria MCP no disponible en la sesión, fallback .memory/; lectura/edición y PowerShell disponibles. Skills ya cargadas aplicables: ponytail, laravel-best-practices y testing-best-practices.
- Fecha: 2026-11-17.
- Investigación posterior: defecto de repoblación del input cost confirmado en Blade; pendiente confirmar si hubo redirección previa. Conservar 7.50 tiene prioridad sobre el fallback a cero. Cualquier plan revisado deberá incluir la causa confirmada de pérdida y prueba con 7.50; no tratar la aclaración como aprobación.
- Reactivación aprobada explícitamente en esta conversación: tras presentar el alcance (costo vacío a 0, conservar precio de venta y costos válidos incluido 7.50, normalizar min_stock y añadir pruebas sin ejecutarlas), el usuario confirmó «aplicalo entonces».
- Alcance de la reactivación: caso actual de costo dejado vacío deliberadamente. No se declara resuelto ni diagnosticado el reporte anterior de pérdida de un costo introducido.
- Estado: APROBADO PARA IMPLEMENTACIÓN. Fecha: 2026-11-20.
- Estado: IMPLEMENTADO.
- Cambios aplicados: dos normalizaciones en productData para cost/min_stock usando valores validados; alta con omitidos y campos vacíos/null guarda 0, edición con omitidos conserva datos y campos vacíos/null guarda 0; sin recalcular precio ni alterar valores válidos. Añadidos dos métodos de prueba con 12 escenarios en AdminProductTest.php.
- Verificación: Pint --dirty --format agent, sintaxis PHP de ambos archivos y git diff --check aprobados; diff cotejado con los pasos y archivos del plan. Pruebas sin ejecutar: Testing requiere solicitud explícita.
- Desviaciones del plan original: ninguna en el alcance de código aprobado. Reactivación explícita para el nuevo caso de costo deliberadamente vacío; no se atribuye a esta reparación la resolución del incidente anterior de costo 7.50 perdido.
- Implementación local registrada en fix-productos-costo-vacio-01 el 2026-11-20; sin publicación, migraciones ni cambios de historial Git.
