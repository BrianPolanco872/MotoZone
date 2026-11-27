---
entity: plan-pos-scroll-ventas-dia-01
type: plan
---

- Tipo: bug-fix.
- Objetivo: Permitir bajar por todas las filas cargadas en el panel izquierdo de «Ventas del día y devoluciones» del POS.
- Evidencia: En `resources/views/pos/index.blade.php`, la cuadrícula del diálogo tiene solo `max-height` y usa `overflow-hidden` en escritorio. Su fila automática puede crecer con la tabla más allá de la altura visible, de modo que el panel recorta filas en vez de activar el scroll interno. El servidor limita el endpoint a 100 ventas del cajero para la fecha elegida, no a las ~11 visibles en la captura; no cambiar ese límite en este plan.
- Paso 1: En escritorio, acotar la altura del cuerpo del diálogo al viewport, con una fila de grid que pueda encogerse (`minmax(0, 1fr)`), para que el `overflow-auto` existente de la lista funcione. Conservar buscador y fecha visibles; no introducir un scroll adicional innecesario. Mantener el desplazamiento de la vista móvil y el detalle seleccionable.
- Paso 2: Revisar que la lista pueda desplazarse con rueda, panel táctil y teclado; añadir `tabindex` y etiqueta accesible solo si son necesarios para el área desplazable. Sin cambiar resultados, permisos, filtros, impresión, cobro, devoluciones ni backend.
- Alcance de código: `resources/views/pos/index.blade.php`; si procede, cobertura del diálogo existente en `tests/Feature/PosAccessTest.php`. Si se introducen utilidades Tailwind nuevas, regenerar assets solo mediante `./build.sh`, previa comprobación de versión y tag. No modificar estilos globales.
- Aceptación: Con más filas que las visibles, se puede alcanzar la última fila cargada al bajar en el panel; la cabecera de la tabla y los controles de fecha no desaparecen; se puede seleccionar una venta en la parte inferior. El diálogo sigue usable con pantalla baja y en móvil. No presentar una verificación visual en el equipo de caja como realizada hasta comprobarla.
- Reversión: Restaurar las líneas del diálogo y, si se regeneró, el manifiesto y el asset anterior correspondiente. Sin cambios de datos.
- Estado: Propuesto, pendiente de aprobación explícita. Solo se revisaron código y memoria; sin tocar aplicación, build o pruebas.
- Estado: IMPLEMENTADO
- Cambios aplicados: El cuerpo del diálogo queda acotado al viewport y el grid de escritorio usa una fila `minmax(0, 1fr)` para activar el scroll de la lista existente. La lista recibe foco y etiqueta para desplazamiento por teclado. Móvil y pantallas bajas conservan el desplazamiento del cuerpo; sin cambios de consulta ni de impresión. CSS regenerado y Blade compilada.
- Desviaciones del plan original: Ninguna en el alcance. No se agregó una prueba estática de clases; queda pendiente verificar interacción en el navegador de caja por solicitud explícita.
