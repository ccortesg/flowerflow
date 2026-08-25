# ADR-0011 — Envío inmutable y reapertura append-only de evaluaciones

- **Estado:** Accepted — implementación local/test M7; release/producción bloqueados.
- **Fecha:** 2026-08-24
- **Sustituye:** la ausencia deliberada de estados M7 en ADR-0008; conserva ADR-0010 para asignación/rúbrica.

## Contexto

M6 permitió abrir y guardar un único borrador con cálculo decimal servidor. M6A hizo dinámico el contrato v1/v2 y mantuvo asignación manual sin mínimos. Faltaba confirmar el trabajo del juez, convertirlo en evidencia inmutable y permitir correcciones administrativas sin reescribir historia ni atribuir al juez acciones ejecutadas por otra cuenta.

La Mecánica v1.1 exige “al menos tres jueces”, mientras la operación autorizada no exige mínimo. M7 no resuelve la divergencia: continúa `OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED` y bloquea release/producción.

## Decisión

1. `Evaluation` usa `draft|reopened|submitted`; `EvaluationRevision` usa un enum separado `draft|submitted`.
2. El envío inicial sella la revisión 1 en sitio. Exige todos los criterios de la rúbrica fijada, total BCMath íntegro, comentario general Unicode-trimmed de 100–2,000, lock vigente y confirmación.
3. Toda revisión enviada conserva juez sujeto, usuario real de envío, UTC y modo `judge|administrative`; nunca vuelve a modificarse.
4. Reabrir sólo es posible sobre la revisión enviada vigente y crea `revision_number+1`, copia exacta de scores/componentes/comentarios/total y vínculo a la fuente. `evaluation_reopenings` conserva fuente/destino únicos, juez sujeto, admin real, motivo cifrado y fecha UTC.
5. El admin sólo edita/envía revisiones reabiertas. El juez puede editar/enviar su revisión inicial o una reabierta. Una carrera conserva al primer actor real y el segundo recibe conflicto sin overwrite.
6. `EvaluationWindow` fija `America/Hermosillo`: reapertura hasta `2026-08-27 20:00:00` y mutaciones hasta `2026-08-27 23:59:59`, ambos segundos inclusivos; la deriva falla cerrada.
7. El juez sólo ve un aviso genérico de reapertura. El admin autorizado ve juez sujeto, actores, motivo e historial. Auditoría nunca contiene motivo, scores, componentes, total, comentarios o PII.
8. M7 no añade correos ni deliveries. Comunicaciones de envío/reapertura pertenecen a M8.

## Consecuencias

- La historia es verificable por revisión y reapertura; una corrección administrativa ya no necesita sobrescribir evidencia.
- Las mutaciones administrativas elevan fricción de forma deliberada: rol/permiso exactos, contraseña reciente, contraseña actual, confirmación, razón, CSRF, throttle y lock.
- Con evidencia M7, no puede ejecutarse `down()` ni desplegarse código M6 incompatible. El rollback operativo es apagar `FLOWERFLOW_EVALUATION_FINALIZATION_ENABLED` y conservar lectura/evidencia.
- No existe consolidación, promedio, ranking, ganador o resultado; M8–M10 siguen separados.

## Alternativas descartadas

- Copiar la revisión al enviar: añade una revisión redundante sin valor probatorio.
- Editar una revisión enviada: destruye la trazabilidad.
- Atribuir acciones administrativas al juez: falsifica autoría.
- Enviar correo dentro de M7: mezcla la puerta M8 y amplía superficie operativa.
- Usar reloj/números del navegador: rompe autoridad y reproducibilidad.

## Verificación

La evidencia se conserva en `.agent/execplans/flowerflow-phase-02b-m7-immutable-submission-append-only-reopening.md` y `docs/28-phase-02b-m7-evaluation-submission-reopening-report-2026-08-24.md`.
