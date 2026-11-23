---
entity: fix-pos-vista-amplia-existencias-05
type: fix
---

- Objetivo: mostrar también en Detalle de la venta los campos reales del producto y ampliar su foto, según captura y solicitud explícita del usuario.
- Archivos tocados en esta corrección: resources/views/pos/index.blade.php; resources/js/pos/pos.js; tests/pos.test.js; tests/Feature/PosAccessTest.php. Artefactos regenerados mediante ./build.sh en public/build/; memoria de plan/feature/relaciones.
- Cambios: columnas Foto/Código/Producto/Categoría/Precio unitario/Cantidad/Importe/Existencia en tienda/Existencia en bodega, Tienda 2 condicional y Quitar existente. Foto/placeholder de 96px en columna propia, con ampliación por el modal ya existente. Metadatos del producto reutilizados, sin backend nuevo ni datos inventados. Bodega ausente «-», Tienda 2 ausente no activa columna, cero sí se muestra. Colspan del estado vacío sincronizado (10/11).
- Compatibilidad indispensable: editCart actualizaba cells[3], que ahora corresponde a Categoría; se sustituyó por selector data-line-total para mantener edición de cantidades/precios e importe sin sobrescribir otros campos. No se cambió fórmula ni validación.
- Presentación: anchos mínimos y scroll horizontal preservan información; carrito conserva viewport fijo 388px y estado vacío340px, total compacto sin cambios.
- Cobertura adaptada: encabezados/colspan/ocultación inicial en PosAccessTest, campos y foto grande con tienda2 NULL/vacío/cero/valor y eliminación, actualización de importe sin sobrescribir categoría en pruebas JS. Pruebas NO ejecutadas.
- Verificación: php -l y node --check de archivos modificados, Pint dirty y git diff --check correctos. Revisión de consumidores/posiciones de columnas: eliminado el único acceso cells[3].
- Build: versiones raíz 1.0.1 y HEAD/tag v1.0.1 coincidentes, public/hot ausente; ./build.sh mediante Git Bash completado. Nuevos assets app-AaGSIAnC.js y app-C6qwpyB0.css. Advertencia no bloqueante de fontaine opcional, sin añadir dependencia.
- Estado: IMPLEMENTADO EN FUENTE Y BUILD LOCAL. Sin Testing, navegador, migraciones, despliegue ni operaciones de historial Git.
- Fecha: 2026-11-22.
