---
entity: plan-importar-csv-01
type: plan
---

- Tipo: bug-fix
- Aprobación explícita en este chat: usuario «si aprobado aplica».
- Solicitud: el usuario pide reparar rápidamente la importación o crear un plan rápido y revisar todas las funciones del flujo de importación.
- Objetivo: corregir el flujo CSV para que envíe el archivo, importe datos válidos y muestre claramente los errores y resultados sin perder datos.
- Paso 1: revisar formulario, ruta/permisos, controlador y validación, ayuda visible, mensajes y pruebas existentes; confirmar la causa antes de modificar el código. Examinar el CSV del usuario si lo proporciona; no presumir su formato.
- Paso 2: mostrar errores de importación junto al formulario y exigir seleccionar archivo antes de enviarlo; conservar CSRF, multipart y permisos actuales. Alinear ayuda y campos requeridos con las reglas vigentes.
- Paso 3: corregir únicamente causas confirmadas dentro del lector CSV y envío. Si se confirma incompatibilidad con CSV exportado por Excel, admitir coma o punto y coma y encabezado UTF-8 con BOM sin modificar campos ni reglas comerciales. Mantener lectura CSV estándar para celdas entrecomilladas.
- Paso 4: conservar transacción completa, validación por fila, unicidad cruzada de códigos y política de omitir SKU vacío/existente; no actualizar inventario existente ni importar parcialmente. Conservar precios masivos, limpieza, búsqueda y demás acciones sin cambios.
- Paso 5: añadir cobertura mínima de la causa corregida y de errores visibles en tests/Feature/AdminProductTest.php. Verificar sintaxis PHP y aplicar Pint según reglas del proyecto. Ejecutar pruebas únicamente tras solicitud explícita y con entorno SQLite aislado comprobado; no cargar archivos sobre datos reales.
- Archivos previstos: resources/views/admin/products/index.blade.php; app/Http/Controllers/Admin/ProductController.php solo si el diagnóstico confirma un fallo; tests/Feature/AdminProductTest.php. Inspeccionar routes/web.php, resources/views/layouts/admin.blade.php y scripts asociados sin cambios previstos. Notificar cualquier ampliación necesaria antes de realizarla.
- Criterios de aceptación: archivo válido crea productos y comunica conteo; errores de archivo/fila visibles; CSV inválido no deja escrituras parciales; códigos existentes no se actualizan; envío protegido; restantes acciones de productos intactas.
- Exclusiones: Excel XLS/XLSX, nuevas dependencias, cambios de esquema, importación real sobre inventario, rediseño, cambios fuera del flujo de importación, build y operaciones de historial git.
- Rollback: revertir exclusivamente los cambios de esta reparación conservando cambios previos del usuario; no realizar escrituras de inventario durante la implementación.
- Herramientas: read/glob/grep, patch y shell PowerShell disponibles; bash no confirmado. MCP de memoria no disponible tras búsqueda de herramientas; fallback .memory/.
- Skills aplicables: ponytail y laravel-best-practices; tailwindcss-development si se modifican clases del formulario; testing-best-practices al añadir cobertura.
- Fecha: 2026-11-17.
- Estado: IMPLEMENTADO.
- Cambios aplicados: errores de import_file visibles junto al formulario con todos los mensajes y aviso de rollback; selección de archivo requerida y atributos accesibles; ayuda con encabezados y política de omisión reales; mensajes de archivo en español; detección de coma/punto y coma; BOM UTF-8 retirado antes de interpretar comillas. Se mantuvieron validación por fila, transacción, códigos únicos y SKU existentes omitidos.
- Archivos tocados: app/Http/Controllers/Admin/ProductController.php; resources/views/admin/products/index.blade.php; tests/Feature/AdminProductTest.php; entidades/relaciones de este arreglo en .memory/.
- Cobertura añadida: cuatro variantes de separador/BOM con encabezados entrecomillados y delimitadores en celdas; errores múltiples visibles sin guardar productos; rechazo sin archivo y selección requerida.
- Verificación básica: Pint --dirty --format agent correcto; php -l correcto en controlador y pruebas; ruta POST admin/productos/importar registrada; git diff --check sin errores. Revisión de diff confirma que las acciones ajenas al importador conservan los cambios previos existentes.
- Pruebas funcionales: añadidas pero no ejecutadas; Testing requiere solicitud explícita. No se recibió ni importó el CSV real del usuario; no se verificó en navegador ni se construyeron assets.
- Desviaciones del plan original: ninguna. La compatibilidad con punto y coma y BOM entrecomillado se implementó por fallos confirmables en el lector existente; no se afirma que sean la causa del archivo no proporcionado.
