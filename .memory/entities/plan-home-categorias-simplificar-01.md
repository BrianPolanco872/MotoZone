---
entity: plan-home-categorias-simplificar-01
type: plan
---

- Tipo: feature.
- Feature: feature-home-categorias-simplificar-01.
- Estado: PROPUESTO, requiere confirmación del usuario antes de modificar código.
- Aprobación: usuario confirma «si» y luego «continua lo que pedi»; alcance aprobado y aplicado.
- Respaldo previo: C:/Users/Administrador/AppData/Local/Temp/opencode/home-categorias-simplificar-01-20261115-171958 (ocho archivos y home-cards.json con orden/anclas). Sin escrituras de datos.
- Estado final: IMPLEMENTADO.
- Objetivo: simplificar solo los formularios de categorías editoriales y posicionar primero las categorías recién publicadas.
- Alcance propuesto: crear/editar categoría de Editar inicio sin subtitle, slug, sort_order, badge, link_url ni link_label; conservar nombre, descripción, icono/fotos, activar/ocultar y vínculos de productos. Mantener los demás tipos de cards intactos.
- Alta/publicación: una categoría creada activa aparece primero; una categoría creada oculta entra primero al activarse. Editar una categoría que ya está activa no altera su posición. Conservar las flechas de orden existentes y el orden relativo de las demás categorías.
- Evidencia: card-form.blade.php comparte campos con productos; hay que condicionar su retirada solo para categorías. categories.blade.php anuncia ancla y presets con data-slug. home-editor.js preset recorre title/slug/icon, por lo que retirar el input slug sin adaptar esa asignación rompería los botones sugeridos.
- Evidencia: HomeCardController::store añade max(sort_order)+1 y admite posición manual de categorías. El orden es ascendente y el campo usa límites unsigned: no usar min-1 ni incrementar posiciones máximas sin normalizar.
- Evidencia: HomeCardRequest genera slug en prepareForValidation y aplica unicidad antes de la escritura. Al ocultar slug, resolver automáticamente duplicados en servidor para evitar errores imposibles de corregir desde el formulario; conservar las anclas existentes al editar nombre.
- Pasos: 1) respaldar los archivos afectados y exportar orden/anclas actuales de home_cards sin modificar registros; 2) ocultar los seis campos solo en categorías y adaptar sus ayudas/presets; 3) generar slug seguro y único al crear bajo bloqueo editorial, conservando el slug existente en actualizaciones; 4) aplicar orden automático al principio en alta activa/activación inicial, reindexando de forma acotada dentro de la transacción editorial para no exceder unsigned y preservando el orden relativo; ignorar posición manual de categorías nuevas; 5) añadir/adaptar regresiones focalizadas; 6) verificar sintaxis PHP/Blade/JS y Pint sin ejecutar Testing o build.
- Archivos previstos: resources/views/admin/home/partials/card-form.blade.php; resources/views/admin/home/partials/categories.blade.php; resources/js/home-editor.js; app/Http/Requests/Admin/HomeCardRequest.php; app/Http/Controllers/Admin/HomeCardController.php; tests/Feature/HomeFamilyTest.php, HomeCardTest.php y AdminHomeContentTest.php según aserciones afectadas. No se prevén migraciones, paquetes, modelo Product, rutas o cambios de la presentación pública.
- Compatibilidad: no borrar los datos históricos de subtítulo/badge/enlaces, imágenes ni vínculos de categorías; no cambiar el destino actual de categorías existentes, clasificación de productos ni inventario. Las nuevas categorías navegan por sus productos vinculados con ancla interna existente.
- Fuera de alcance: rediseño de home, otros formularios de cards, eliminación masiva o migraciones; ni pruebas automáticas ni commits.
- Plan de rollback: copias puntuales anteriores y exportación de orden/anclas; restaurar solo fragmentos de esta tarea sin sobrescribir cambios posteriores. No restaurar BD completa ni borrar categorías/productos. Si hubiera que recuperar posiciones, comparar cambios posteriores y solicitar autorización específica antes de escribir datos.
- Verificación pendiente de implementación: campos ausentes solo en formularios de categorías; sugerencias funcionan sin input slug; slug automático único y estable al renombrar; altas activas y activación inicial de borradores se anteponen sin overflow; edición normal no mueve categorías; tarjetas de productos/novedades conservan sus campos/orden; datos históricos y permisos intactos. Testing requiere solicitud explícita.
