---
entity: fix-preview-precios-decimales-01
type: fix
---

- Objetivo: eliminar MathException causada por separadores de miles en los precios de ejemplo de la vista previa.
- Archivos tocados: app/Http/Controllers/HomeController.php, tests/Feature/HomeTest.php.
- Cambios: valores de ejemplo 1249.00 y 1899.00 sin comas; presentación con number_format conservada. Regresión añadida para vista previa con ejemplos y sin persistencia.
- Verificación: php -l correcto en ambos archivos; Pint y git diff --check correctos. No se ejecutaron tests ni comprobación HTTP autenticada.
