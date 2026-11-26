---
entity: fix-pos-granel-limite-acumulado-01
type: fix
---

- Objetivo: Mostrar dentro del diálogo el rechazo del acumulado a granel cuando rebasa 9999 y cerrarlo solo tras agregar la cantidad.
- Archivos tocados: `resources/js/pos/pos.js`, `tests/pos.test.js`.
- Estado: IMPLEMENTADO.
- Verificación: Suite completa `node --test`: 71 pasaron, 0 fallaron, 0 omitidas. Sintaxis y `git diff --check` sobre los dos archivos correctos. Sin build ni ejecución de PHP.
