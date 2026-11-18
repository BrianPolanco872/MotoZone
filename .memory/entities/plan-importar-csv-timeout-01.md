---
entity: plan-importar-csv-timeout-01
type: plan
---

- Tipo: bug-fix
- Aprobación explícita en este chat: usuario «si» a la propuesta de aplicar este plan.
- Objetivo: evitar que cargas CSV superen el límite PHP de 30 segundos por trabajo repetido innecesario, preservando validación y atomicidad.
- Paso 1: revisar API instalada del validador para reutilizar reglas y la instancia, reiniciando correctamente los datos/errores por fila mediante setData. Mantener validación, unicidad y consultas contra la base con su collation; no reemplazarlas por comparaciones PHP que cambien el contrato.
- Paso 2: construir las reglas/validador una vez por importación y reutilizarlos para cada producto no omitido. Mantener todos los valores, conteos, mensajes, SKU existentes omitidos y transacción completa. Revisar efectos sobre validación de filas sucesivas.
- Paso 3: permitir un presupuesto finito de 300 segundos exclusivamente durante la importación validada cuando PHP permita set_time_limit. No modificar php.ini, límites globales ni desactivar el timeout indefinidamente. Informar si el servidor no permite ampliarlo; no prometer compatibilidad con volúmenes ilimitados.
- Paso 4: añadir cobertura de varias filas sucesivas, errores en fila posterior y unicidad entre filas con el validador reutilizado, aprovechando cobertura existente sin duplicación. Verificar sintaxis PHP, Pint y diff. Testing necesita solicitud explícita; no importar sobre datos reales.
- Archivos previstos: app/Http/Controllers/Admin/ProductController.php; tests/Feature/AdminProductTest.php solo si falta cobertura específica. Memoria en .memory/.
- Exclusiones: cambios en vendor, php.ini, esquema, nuevas dependencias, colas, inserciones masivas que omitan eventos, modificaciones en otras acciones, build, operaciones de historial git y escrituras reales.
- Plan de rollback: revertir exclusivamente este ajuste de rendimiento preservando los fixes anteriores y sin tocar registros existentes.
- Riesgo y límites: número de filas y tamaño del CSV no conocidos; sin medición no se garantiza que toda carga termine en 300 segundos. Proxies/servidores pueden tener límites propios. Si la carga sigue siendo demasiado grande, pedir aprobación independiente para procesamiento asíncrono o por lotes.
- Skills: ponytail, laravel-best-practices y testing-best-practices ya cargadas en la sesión. Herramientas de lectura/edición y PowerShell disponibles; memoria MCP no disponible, fallback .memory/.
- Fecha: 2026-11-17.
- Estado: IMPLEMENTADO.
- Cambios aplicados: set_time_limit(300) solo tras validar el archivo del endpoint de importación; si PHP no lo permite, error visible antes de leer/escribir productos. Reglas y validador construidos una vez por importación; setData y fails procesan cada fila y reinician los mensajes antes de validated. Se mantienen consultas de unicidad/existencia, creación Eloquent y transacción.
- Archivos tocados: app/Http/Controllers/Admin/ProductController.php; tests/Feature/AdminProductTest.php.
- Cobertura: añadido test de dos filas con datos diferentes que comprueba que marca, descripción, código, IVA, nombre, precio y existencias de la fila anterior no se arrastran. Se conservan pruebas existentes de rollback por fila posterior y códigos repetidos.
- Verificación básica: Laravel instalado v13.38.0 y PHP CLI 8.3.35; set_time_limit disponible en CLI. API de Validator inspeccionada: setData sustituye datos y vuelve a procesar reglas; passes reinicia mensajes y reglas fallidas. Pint --dirty --format agent y php -l en ambos archivos correctos; git diff --check sin errores; lectura final del código confirma alcance del plan.
- Desviaciones del plan original: ninguna. Advertido antes de editar que setData sigue procesando reglas internamente; no se afirma eliminación total del parsing ni de las consultas por fila.
- Testing: no ejecutado; requiere solicitud explícita. Sin importación real, medición de carga ni verificación en navegador. No se garantiza que el CSV desconocido termine dentro de 300 segundos ni que el servidor HTTP permita el ajuste solo porque CLI lo permite.
