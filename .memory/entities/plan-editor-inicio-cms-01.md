---
entity: plan-editor-inicio-cms-01
type: plan
---

- Tipo: feature
- Objetivo: reemplazar la interfaz de Editar inicio por el diseño CMS oscuro y adaptable proporcionado por el usuario.
- Aprobación: alcance confirmado con «si aplicalo» y rollback confirmado con «si» en esta conversación.
- Alcance: cabecera, cuatro pestañas, controles de visibilidad, presentación, cards con imágenes y búsqueda, edición de descripción, acciones inferiores; reutilizar endpoints, validaciones y permisos existentes.
- Archivos: resources/views/admin/home/edit.blade.php, resources/views/admin/home/partials/*.blade.php, resources/views/layouts/home-editor.blade.php, resources/js/home-editor.js, resources/css/app.css, tests/Feature/AdminHomeContentTest.php.
- Exclusiones: no modificar home pública, base de datos, categorías de productos, dependencias ni permisos.
- Aceptación: interfaz con datos reales, navegación por bloques, formularios operativos y conservación de carga de fotos, edición, eliminación, vista previa y ordenación.
- Plan de rollback: revertir únicamente los cambios de este rediseño, conservando datos y cambios previos. No restaurar archivos completos desde HEAD porque existen cambios previos sin registrar.
- Adaptación comunicada: guardar por formulario mediante backend existente; barra inferior guarda formulario activo; ordenación accesible por subir/bajar en lugar de arrastre simulado.
- Verificación: sintaxis básica y formato; Testing solo bajo solicitud explícita. No construir artefactos sin build.sh.
- Estado: APROBADO
- Estado: IMPLEMENTADO
- Cambios aplicados: layout CMS aislado, navegación accesible de cuatro pestañas con hashes existentes, contadores reales, formularios de presentación y cards, edición de descripción en dialog, barra para guardar/descartar formulario activo, confirmaciones para evitar pérdida de otros borradores y restauración de previews/vínculos al descartar.
- Desviaciones del plan original: ninguna adicional a las adaptaciones comunicadas (guardado por formulario, ordenación subir/bajar, iconos locales en lugar de Material Symbols). No se modificó el backend ni la home pública.
- Verificación: siete plantillas Blade compiladas a carpeta temporal y validadas con php -l; node --check correcto; Tailwind compilado en memoria sin artefactos; Pint correcto; pruebas de interfaz y regresión añadidas pero no ejecutadas.
- Rollback disponible: copias previas de archivos modificados en C:/Users/Administrador/AppData/Local/Temp/opencode/editor-inicio-cms-01; el layout resources/views/layouts/home-editor.blade.php es nuevo. Revisar cambios posteriores antes de restaurar.
- Pendiente: Testing requiere solicitud explícita; build no realizado por ausencia de build.sh; sin comprobación visual en navegador.
- Incidencia posterior: captura del usuario muestra plantilla nueva sin tema CMS ni pestañas; public/build/manifest.json referencia assets app-GnCMTbnJ.css y app-DxE3CW-7.js del 08/11/2026, ambos sin los selectores/código del rediseño. No existía public/hot.
- Corrección de entrega local: iniciar npm run dev -- --host 127.0.0.1 para servir fuentes actuales vía Vite, sin generar build ni cambiar dependencias. La ausencia de assets actualizados impedía considerar verificada la entrega inicial.
