---
entity: bug-test-contacto-atributos-01
type: bug
---

- Hallazgo: test_contact_save_preserves_other_content_images_and_cards falla en corrida ampliada de AdminHomeContentTest.
- Evidencia: snapshot inicial usa card->getAttributes inmediatamente tras factory y luego compara con card->fresh()->getAttributes. El diff añade columnas null/default, cambia is_active de bool a entero y orden de campos al recargar, aunque no prueba por sí mismo una escritura en la card.
- Estado: PENDIENTE; probable defecto de fixture/comparación, no atribuirlo automáticamente a mutación funcional de Contacto. No se modificó esta prueba ni aplicación de contacto.
- Contexto: 44/45 en corrida ampliada; mapa y regresión hero pasan por separado 44/44. Fallo informado al usuario durante ejecución; no cerrar como suite completa verde.
- Alcance futuro sugerido: comparar snapshots ambos recargados antes/después, únicamente tras aprobación de plan específico.
