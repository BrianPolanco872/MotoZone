---
entity: bug-importar-csv-timeout-01
type: bug
---

- Reporte: POST /admin/productos/importar termina con Maximum execution time of 30 seconds exceeded en Arr.php:274.
- Evidencia: log local del 2026-11-17 21:25:33 confirma Arr.php:274; segundo intento 21:26:24 termina en ValidationRuleParser.php:270. Ambos stacks solo contienen main, sin perfil de tiempos ni cantidad de filas.
- Inspección: import construye un validador y reglas de unicidad por cada fila; ejecuta consulta de existencia SKU, consultas de unicidad y creación individual dentro de una transacción.
- Estado: CUELLO DE BOTELLA POTENCIAL IDENTIFICADO; no se ha medido la contribución exacta de validación/SQL ni recibido CSV del usuario.
- Estado: MITIGADO EN FUENTE por fix-importar-csv-timeout-01; pendiente validación funcional y del archivo real.
