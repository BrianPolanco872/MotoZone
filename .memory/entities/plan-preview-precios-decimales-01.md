---
entity: plan-preview-precios-decimales-01
type: plan
---

- Tipo: bug-fix
- Objetivo: corregir MathException en /admin/home/vista-previa al convertir los precios de ejemplo a decimal.
- Alcance: sustituir '1,249.00' por '1249.00' y '1,899.00' por '1899.00' en HomeController; conservar number_format en la vista y los datos persistidos.
- Archivos: app/Http/Controllers/HomeController.php y tests/Feature/HomeTest.php.
- Aprobación de ejecución: instrucción directa del usuario «reparalo» después de describir este arreglo; solicita proceder sin otra ronda de confirmación.
- Regresión: añadir caso de vista previa sin cards guardadas con precios de cuatro cifras, sin persistir ejemplos.
- Verificación: lint PHP y Pint. No ejecutar Testing sin solicitud explícita.
- Estado: APROBADO
- Estado: IMPLEMENTADO
- Cambios aplicados: corregidos ambos precios de ejemplo sin tocar datos guardados ni vistas; prueba de regresión añadida.
- Desviaciones del plan original: ninguna.
- Verificación: sintaxis PHP y formato correctos; Testing pendiente de solicitud explícita.
