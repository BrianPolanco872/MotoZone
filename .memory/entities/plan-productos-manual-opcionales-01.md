---
entity: plan-productos-manual-opcionales-01
type: plan
---

- Tipo: bug-fix
- Aprobación explícita en este chat: usuario «si» al ajuste para crear/editar con campos opcionales.
- Solicitud: permitir añadir productos manualmente sin completar marca y descripción.
- Diagnóstico: ProductRequest exige brand/description y el formulario compartido de creación/edición incluye required en ambos. La tabla products no admite null en esas columnas.
- Alcance: marca y descripción opcionales en alta manual y en el formulario compartido de edición, sin cambiar otros campos, importación ni registros existentes.
- Paso 1: cambiar exclusivamente brand/description a reglas opcionales en ProductRequest, conservando tipos y máximo de marca; retirar mensajes required de esos dos campos.
- Paso 2: retirar required de marca y descripción en resources/views/admin/products/partials/form.blade.php y etiquetarlas como opcionales, manteniendo componentes/estilos y demás campos.
- Paso 3: normalizar a cadena vacía los valores ausentes/vacíos al crear en productData para respetar NOT NULL, sin migraciones. En edición, preservar el valor existente si el campo no se envía; permitir borrarlo si se envía explícitamente vacío.
- Paso 4: añadir cobertura de alta con campos omitidos/vacíos, edición para completar o vaciar esos campos y ausencia de required en el formulario; conservar resto de pruebas. Verificar sintaxis y Pint; Testing solo con solicitud explícita.
- Archivos: app/Http/Requests/Admin/ProductRequest.php; app/Http/Controllers/Admin/ProductController.php (productData); resources/views/admin/products/partials/form.blade.php; tests/Feature/AdminProductTest.php.
- Preservar: autorización, validación de nombre/precio/IVA/existencias/códigos e imágenes, importación CSV y reparaciones anteriores; no modificar dependencias, esquema ni datos reales.
- Plan de rollback: revertir exclusivamente este ajuste de los cuatro archivos preservando cambios previos; sin cambios de esquema ni escrituras reales.
- Skills aplicables ya cargadas: ponytail, laravel-best-practices, tailwindcss-development y testing-best-practices. Memoria MCP no disponible, fallback .memory/; herramientas de lectura/edición y PowerShell disponibles.
- Fecha: 2026-11-17.
- Estado: IMPLEMENTADO.
- Cambios aplicados: ProductRequest permite marca/descripción vacías y conserva validación string/max; mensajes required retirados. Formulario compartido sin required en ambos y etiquetas opcionales. productData normaliza nulos/ausentes a cadena vacía al crear, conserva valores omitidos al editar y permite vaciarlos explícitamente.
- Archivos tocados: app/Http/Requests/Admin/ProductRequest.php; app/Http/Controllers/Admin/ProductController.php; resources/views/admin/products/partials/form.blade.php; tests/Feature/AdminProductTest.php.
- Cobertura: dos casos de alta con campos ausentes/vacíos; tres de edición (preservar, vaciar, completar); formulario existente ampliado para comprobar etiquetas y ausencia de required en creación/edición.
- Verificación: Pint --dirty --format agent correcto; php -l correcto en request/controlador/pruebas; git diff --check sin errores. API instalada de FormRequest::validated comprobada y lectura final de productData confirma tratamiento de ausencia frente a null. Búsqueda sin expectativas previas de required de esos campos en tests.
- Desviaciones del plan original: ninguna.
- Testing: cobertura añadida pero no ejecutada; requiere solicitud explícita. No se probó en navegador ni se escribieron datos reales; sin migraciones ni build.
