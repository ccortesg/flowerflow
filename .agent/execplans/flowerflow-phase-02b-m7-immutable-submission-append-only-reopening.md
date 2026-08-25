# Fase 02B M7 — envío inmutable y reapertura append-only

Este ExecPlan es un documento vivo. Debe mantenerse actualizado durante la implementación conforme a `.agent/PLANS.md`.

## Propósito

Implementar exclusivamente la confirmación y el sellado inmutable de evaluaciones, junto con reaperturas administrativas append-only que preserven al juez sujeto y registren al actor real. M8–M10, comunicaciones de evaluación, consolidación, ranking, resultados y producción permanecen fuera de alcance.

## Baseline y guard

- Repositorio: `/home/ccortesg/workspace/flowerflow`.
- Rama: `codex/submission-deadline-extension`.
- HEAD/upstream/merge-base verificados: `8da3ed0cb19e3548380ca0732db11d3d205e4612`.
- Árbol inicial: limpio; 22 migraciones y 95 rutas propias.
- Guard verificado el 2026-08-24 sin exponer secretos: `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_DATABASE=flowerflow_testing`, `DB_USERNAME=flowerflow_testing_user` y `SELECT DATABASE()=flowerflow_testing`.
- Regresión previa: 50 pruebas y 601 aserciones M6/M6A verdes.

## Alcance

- Estados agregados `draft|reopened|submitted` y estados de revisión `draft|submitted` separados.
- Actor de envío, juez sujeto, modo y fecha de envío persistidos.
- Reaperturas append-only con motivo cifrado y vínculo fuente/destino.
- Confirmación del juez, edición y reenvío de una revisión reabierta por juez o admin.
- Ventanas exactas Hermosillo, lock optimista, cálculo decimal servidor y auditoría redactada.
- Panel administrativo de evaluaciones y UX juez sólo lectura/historial.

## Exclusiones

- Sin correos, deliveries o jobs de evaluación.
- Sin M8–M10, cobertura mínima, consolidación, promedio, empate, ranking, ganadores, resultados, retención o purga.
- Sin cambios a PDFs, hashes, propuestas, snapshots, folios, archivos privados, datos reales o producción.
- Sin stage, commit, push o despliegue.

## Modelo e invariantes

- La revisión inicial se sella en sitio; nunca se crea una copia redundante.
- Una revisión enviada no vuelve a modificarse, ni siquiera al reabrir.
- Cada reapertura clona la revisión enviada vigente a una nueva revisión draft con `source_revision_id` de la misma evaluación.
- `subject_judge_profile_id` siempre coincide con la asignación; `submitted_by_user_id` siempre es el usuario real.
- Sólo existe una revisión draft vigente y sólo puede reabrirse la revisión actualmente enviada.
- M6 v1/v2 conserva cantidad dinámica de criterios y cálculo BCMath autoritativo.

## Rutas y permisos

- Juez: confirmación GET y envío POST con `submit own evaluations`.
- Admin: listado/detalle, reabrir, guardar borrador reabierto, confirmar y enviar con `view evaluations`, `reopen evaluations` y `manage reopened evaluations`.
- Las mutaciones usan CSRF, throttle, flag M7 y lock; las administrativas también contraseña reciente, contraseña actual y confirmación reforzada.

## Ventanas

- Reapertura inclusiva hasta `2026-08-27 20:00:00 America/Hermosillo` (`2026-08-28 03:00:00 UTC`).
- Guardado/envío inclusivo hasta `2026-08-27 23:59:59 America/Hermosillo` (`2026-08-28 06:59:59 UTC`).
- Cualquier deriva de zona, configuración o `JudgeAssignment.due_at` falla cerrada.

## Pruebas y validación

- Pruebas de límites de comentario, payload hostil, inmutabilidad, reaperturas sucesivas, actor real, privacidad diferenciada, ventanas, concurrencia, 409, roles/IDOR y ausencia de comunicaciones.
- Forward/rollback/forward bajo guard, regresión M1–M6A, suite completa, Pint, Composer, auditorías, build, rutas, scheduler, estado de migraciones, diff, enlaces y scans redactados.
- UAT Firefox local sintético en 1440×900, 1024×768 y 390×844.

## Progreso

- [x] 2026-08-24: baseline, lecturas, PDF jurídico, guard y regresión previa verificados.
- [x] 2026-08-24: migración/modelo/permisos implementados y probados forward/rollback/forward.
- [x] 2026-08-24: Actions, Policies, Requests, eventos y rutas implementados con locks y allowlists.
- [x] 2026-08-25: UX juez/panel verificada y corregida mediante UAT Firefox local.
- [x] 2026-08-25: regresión dirigida, suite completa, gates, rollback con evidencia y UAT completados.
- [x] 2026-08-25: ADR 0011, informe 28 y documentación canónica actualizados.

## Decisiones y hallazgos

- La Mecánica v1.1 confirma visual y textualmente “al menos tres jueces”. Se conserva `OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`; el máximo estado permitido es `GO LOCAL/TEST` y `NO-GO RELEASE/PRODUCTION`.
- M7 reutiliza la bitácora de auditoría, pero no extiende el outbox: las comunicaciones pertenecen a M8.
- El UAT detectó dos derivas visuales M6A: el dashboard afirmaba que el envío seguía deshabilitado y el detalle ofrecía declarar conflicto después del primer envío. Ambas se corrigieron según el flag/estado real y quedaron cubiertas por prueba.
- La primera sonda manual de rollback con evidencia intentó ordenar criterios por un campo inexistente `display_order`; no generó evidencia M7 y el `down()` procedió de forma segura, preservando el borrador M6. La repetición corregida creó evidencia enviada y el rollback se negó con el mensaje esperado, conservando tabla y filas.

## Riesgos y rollback

- Riesgo principal: preservar compatibilidad con borradores M6 y rúbricas v1/v2 al ampliar checks y estados.
- El rollback operativo primario es `FLOWERFLOW_EVALUATION_FINALIZATION_ENABLED=false`.
- El `down()` sólo procede sin evidencia M7 y nunca elimina evidencia para forzarlo.

## Resultados

- `GO LOCAL/TEST` para M7; `NO-GO RELEASE/PRODUCTION — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED` permanece obligatorio.
- Guard exacto repetido antes de migraciones, seeding y UAT; sólo `flowerflow_testing`/loopback/usuario exclusivo y datos `example.test`.
- Migración 23: forward/rollback/forward verde sin evidencia; rollback permitido preservó borrador M6; rollback con una evaluación enviada terminó con exit 1 y conservó la evidencia M7.
- Regresión previa 50/601; dirigida final M5–M7 60/847; M7 final 5/165; suite definitiva 208/2,385, sin fallos.
- Pint, Composer validate/platform/audit, build Vite de 784 módulos, JSON de producto (11), 15 enlaces Markdown locales, 104 rutas, scheduler, estado de 23 migraciones y `git diff --check`: verdes.
- `yarn audit`: exit 2 exclusivamente por un advisory bajo conocido de Quill, sin parche; cero moderados/altos/críticos.
- UAT Firefox con datos sintéticos: 1440×900, 1024×768 y 390×844; mínimo 99/100, total servidor 75.25/76.25, doble clic, sólo lectura, tres reaperturas sucesivas, reenvío juez/admin, actor real, aviso genérico, XSS inerte, 409 en dos pestañas sin overwrite, teclado/skip link/offcanvas, zoom/reflow sin overflow, consola limpia y 403/404.
- No se añadieron comunicaciones de evaluación, no se modificaron PDFs/hashes y no hubo stage, commit, push, despliegue ni acceso a producción.
