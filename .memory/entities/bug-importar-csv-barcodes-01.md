---
entity: bug-importar-csv-barcodes-01
type: bug
---

- Reporte: productos importados sin código de barras visible pese a que el CSV original los contenía; afecta el escaneo para ventas. Usuario solicita plan y ofrece borrar/reimportar si fuera necesario; no autoriza borrado inmediato.
- Estado: PENDIENTE DE CAUSA RAÍZ CONFIRMADA PARA EL CSV REAL. No se recibió archivo ni muestras; glob de CSV en workspace sin resultados. No crear plan ejecutable hasta confirmar el origen de los códigos y su mapeo.
- Evidencia de código: ProductController.php:171 usa sku o codigo como SKU interno; líneas 252-253 solo reconocen codigo_barras|barcode y codigo_barras_2|barcode2 como códigos escaneables. Encabezados no reconocidos pueden quedar ignorados y esas columnas terminar en null sin error porque los códigos son opcionales.
- Contrato POS: PosController.php:77-78 busca coincidencia por barcode/barcode2, no por SKU; tests/Feature/PosProductSearchTest.php:22-43 exige ese comportamiento. No restaurar búsqueda por SKU como parche ni copiar SKU a barcode automáticamente.
- Recuperación: import actual omite SKU existentes en ProductController.php:174; subir de nuevo el mismo archivo no rellena códigos. Evaluar reparación dirigida tras respaldo y previsualización, asociando por identificador inequívoco y preservando IDs, existencias, precios y ventas. No borrar registros ni sobrescribir códigos existentes sin autorización específica.
- Hipótesis pendiente: columna codigo usada como identificador escaneable pero guardada únicamente en SKU; o encabezado real de códigos distinto a los aliases admitidos. Ceros iniciales/notación científica deben verificarse en el archivo original, nunca inventarse ni convertirse a número.
- Entrada pendiente: CSV original o encabezado más dos filas y confirmación de cuál columna es el código que debe leer el escáner.
- Antecedentes: plan-barcode2-productos-01 (dos códigos y SKU interno no escaneable); planes de importación, campos opcionales y timeout. Las muestras anteriores de la conversación contenían codigo sin codigo_barras; no afirmar que el archivo del usuario sea idéntico.
- Verificación realizada: solo lectura del controlador, contrato POS, pruebas existentes y memoria offline. Skills disponibles/aplicables ya cargadas: ponytail y laravel-best-practices; testing-best-practices para futura cobertura. Sin MCP memory/codesearch accesible en esta sesión; fallback .memory/ y grep/read/glob.
- Fecha: 2026-11-17.
- Aclaración posterior del usuario: acepta que el código original siga siendo SKU interno, pero exige que también se guarde/muestre como Código de barras principal; Código de barras 2 lo decide él. Esto define explícitamente el mapeo solicitado y reemplaza la restricción previa de no copiar el código de origen al principal.
- Estado actualizado: CAUSA RAÍZ CONFIRMADA PARA EL CONTRATO ACLARADO, por inspección de importador, formulario y POS. El importador asigna codigo|sku al SKU pero no lo usa como principal cuando falta codigo_barras|barcode; el formulario muestra product.barcode y el POS solo busca barcode/barcode2. No se ha inspeccionado el CSV real ni se afirma que sus encabezados sean conocidos.
- Estado posterior: CORREGIDO EN FUENTE por fix-importar-csv-barcodes-01; recuperación del inventario existente y validación funcional pendientes de solicitud explícita, CSV y respaldo. No declarar reparados los datos existentes.
