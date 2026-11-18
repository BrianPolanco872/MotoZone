---
entity: bug-importar-csv-01
type: bug
---

- Reporte: el usuario pulsa Importar archivo y su CSV no se carga.
- Estado: PENDIENTE DE DIAGNÓSTICO; no se recibió el archivo que falla.
- Evidencia inicial: formulario multipart con CSRF y ruta POST existentes. La página de productos y el layout admin no muestran el error bag de validación; el layout sí muestra session(status). El lector actual separa únicamente por coma. No se ha confirmado qué condición rechazó el archivo del usuario.
- Fecha: 2026-11-17.
- Estado: CORREGIDO EN FUENTE; pendiente validación funcional solicitada explícitamente.
- Causas corregidas: errores de validación invisibles, separador punto y coma no reconocido y BOM previo a encabezado entrecomillado. La causa específica del CSV reportado sigue sin confirmar al no disponer del archivo.
