---
entity: fix-importar-csv-timeout-01
type: fix
---

- Objetivo: reducir construcción repetida del validador y ampliar de 30 a 300 segundos el presupuesto del importador CSV, sin quitar validaciones.
- Archivos tocados: app/Http/Controllers/Admin/ProductController.php; tests/Feature/AdminProductTest.php.
- Estado: IMPLEMENTADO.
- Cambios: validador y reglas inicializados una vez; datos reemplazados con setData y comprobados con fails por fila; timeout finito exclusivamente para importación validada con rechazo visible si no se puede ampliar. Resto del flujo y cambios preexistentes preservados.
- Cobertura añadida: importación de dos filas distintas con valores opcionales vacíos en la segunda, para detectar datos/IVA/códigos heredados indebidamente.
- Verificación: Pint, php -l y git diff --check correctos; inspección de API instalada y lectura final del cambio. Testing no ejecutado porque requiere solicitud explícita.
- Riesgos: sin CSV real ni medición, no se garantiza completar toda carga en 300 segundos; siguen existiendo validación y consultas por fila. El servidor puede imponer otros límites.
- Fecha: 2026-11-17.
