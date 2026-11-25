---
entity: plan-productos-quitar-boton-limpiar-01
type: plan
---

- Tipo: feature. Estado: alcance aprobado; implementación pendiente.
- Objetivo: quitar solo el botón «Limpiar inventario» de Productos, ningún otro.
- Paso 1 / vista: en resources/views/admin/products/index.blade.php:55-65 retirar el contenedor exclusivo del botón y el formulario oculto clear-inventory-form. Conservar el formulario de precios que envuelve el botón vecino «Aplicar a todos», con su cierre y CSRF.
- Paso 2 / prueba: en tests/Feature/AdminProductTest.php:1039 adaptar la prueba de interfaz que actualmente exige el botón, el formulario y la confirmación. Verificar por GET autenticado que no se ofrecen la acción ni el formulario, y que «Aplicar a todos» sigue en su formulario. No cambiar las pruebas del POST ni del borrado individual.
- Paso 3 / comprobación: ejecutar la prueba de interfaz en entorno de pruebas aislado y revisar que el diff de la vista no retire ningún otro control. No invocar la limpieza.
- Aceptación: «Limpiar inventario» ya no aparece ni tiene formulario en el listado.
- Aceptación: todos los demás controles, incluido el borrado individual, siguen disponibles bajo las mismas condiciones.
- Fuera de alcance: modificar rutas, controlador, permisos, existencias, historial o datos; no hay migraciones ni dependencias nuevas.
- Antecedentes: plan-productos-limpiar-con-historial-01 y plan-productos-limpiar-tabla-01 definen la operación masiva; se sustituye solo su acceso desde el listado. bug-preuso-12 refiere la importación en la misma vista: no tocar el formulario CSV ni considerar resuelta esa incidencia por este cambio.
- Riesgo: retirar el control no deshabilita el POST autorizado. Se conserva por decisión de alcance; no usar la interfaz como control de acceso.
- Rollback: restituir solo los bloques retirados y la expectativa de la prueba de interfaz, sin restaurar ni modificar datos.
- Estado: IMPLEMENTADO.
- Cambios aplicados: retirados el botón y el formulario exclusivo de limpieza. La prueba de vista adaptada comprueba que no hay control ni URL de limpieza y que «Aplicar a todos» conserva su formulario.
- Verificación: prueba dirigida en SQLite en memoria (1 prueba, 5 aserciones), Pint, sintaxis PHP y diff --check correctos. No se ejecutó la limpieza ni la suite completa.
- Desviaciones del plan original: ninguna.
