# Informe de implementación — Fase 02B M7

Fecha de implementación: 2026-08-24; validación final: 2026-08-25 (`America/Hermosillo`).

## 1. Estado

- `GO LOCAL/TEST — M7`.
- `NO-GO RELEASE/PRODUCTION — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`.
- No hubo stage, commit, push, despliegue, producción, SMTP real, servicios externos ni datos reales.
- M8–M10 permanecen `NOT IMPLEMENTED / NOT AUTHORIZED`; no se añadió correo, delivery o job de evaluación.

La Mecánica pública v1.1 conserva “al menos tres jueces”. La operación sin mínimos continúa como excepción expresa del propietario y no se presenta como reconciliada jurídicamente.

## 2. Baseline y guard

- Repositorio `/home/ccortesg/workspace/flowerflow`, rama `codex/submission-deadline-extension`.
- HEAD/upstream/merge-base inicial `8da3ed0cb19e3548380ca0732db11d3d205e4612`; árbol inicial limpio.
- Baseline: 22 migraciones, 95 rutas propias, M1–M6A `GO LOCAL/TEST`, suite M6A 203/2,220.
- Guard repetido sin exponer contraseña: `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_DATABASE=flowerflow_testing`, `DB_USERNAME=flowerflow_testing_user`, `SELECT DATABASE()=flowerflow_testing`.
- Regresión dirigida previa a editar: 50 pruebas/601 aserciones.

## 3. Diagnóstico M6 previo

M6 tenía un agregado único por asignación, revisión 1 editable, scores dimensionados por la rúbrica fijada, total BCMath y `lock_version`, pero sólo admitía `draft`. No existían actor/fecha/modo de envío, separación de estados agregado/revisión, confirmación, sellado, historial de reapertura, actor administrativo real ni panel administrativo de evaluaciones. El comentario general podía quedar vacío y el plazo único terminaba a las 23:59:59 Hermosillo.

## 4. Modelo, migración y rollback

La migración 23 es aditiva:

- `evaluations.status`: `draft|reopened|submitted`.
- `evaluation_revisions.status`: enum separado `draft|submitted`.
- Revisión: juez sujeto no nulo, actor/fecha/modo de envío y fuente append-only.
- `evaluation_reopenings`: ULID, evaluación, fuente/destino únicos, juez sujeto, actor real, motivo cifrado, largo y fecha UTC.
- Backfill determinista del juez sujeto desde la asignación; no crea evaluaciones, revisiones ni scores.
- Permisos: `submit own evaluations` sólo `judge`; `view evaluations`, `reopen evaluations` y `manage reopened evaluations` sólo `admin`.

Forward/rollback/forward pasó sin evidencia M7 y preservó borradores M6, rúbricas, assignments y paquetes sintéticos. Una primera sonda auxiliar usó por error el campo inexistente `display_order`; no produjo evidencia M7 y demostró que el rollback permitido conservaba el borrador M6. Tras repetir con `sort_order`, se creó una evaluación enviada y `migrate:rollback --step=1 --force` terminó con exit 1 y `Cannot remove M7 while submitted or reopened evaluation evidence exists.`; tabla y revisión enviada permanecieron.

Rollback operativo: `FLOWERFLOW_EVALUATION_FINALIZATION_ENABLED=false`. Con evidencia M7 no se ejecuta `down()` ni se despliega código M6 incompatible.

## 5. Ciclo de vida, actor e inmutabilidad

- El juez sella en sitio la revisión 1; no se crea una copia redundante.
- El servidor exige criterios completos, comentario general Unicode-trimmed de 100–2,000, total persistido íntegro y recálculo BCMath coincidente.
- Una revisión enviada queda sólo lectura. Reapertura, edición o reenvío no cambian valores ni `updated_at` de la fuente.
- La reapertura clona exactamente a `revision_number + 1`, enlaza la fuente y crea evidencia append-only. Sólo procede sobre la revisión enviada vigente y cuando no hay draft pendiente.
- Juez o admin pueden editar/reenviar una revisión reabierta. Admin no puede enviar el draft inicial.
- `subject_judge_profile_id` permanece ligado a la asignación. Los campos de actor y la auditoría conservan al usuario real.
- Tres reaperturas sucesivas fueron recorridas en UAT; las pruebas automatizadas verifican revisiones 2/3, carrera juez/admin y un único actor final.

## 6. Rutas y payloads

Juez:

- `GET /juez/asignaciones/{judgeAssignment}/evaluacion/confirmar`.
- `POST /juez/asignaciones/{judgeAssignment}/evaluacion/enviar`.
- PATCH M6 admite sólo `intent=save|review` adicional a su payload previo.
- Envío: `lock_version`, `confirm_submission=1`.

Administración:

- `GET /panel/evaluaciones` y `GET /panel/evaluaciones/{evaluation}`.
- `GET|POST /panel/evaluaciones/{evaluation}/reabrir`.
- `PATCH /panel/evaluaciones/{evaluation}/borrador`.
- `GET /panel/evaluaciones/{evaluation}/confirmar-envio`.
- `POST /panel/evaluaciones/{evaluation}/enviar`.
- Reapertura: lock, motivo, contraseña actual y confirmación.
- Envío: lock, contraseña actual, confirmación de envío y confirmación de actuación administrativa.

Los GET son lectura. Mutaciones usan CSRF, throttle, flag, Policy y lock; las administrativas además rol exacto, permisos, `password.confirm`, contraseña actual y confirmación reforzada.

## 7. Ventanas exactas

`EvaluationWindow` centraliza `America/Hermosillo` y falla cerrado ante deriva:

- Reapertura: hasta `2026-08-27 20:00:00` local / `2026-08-28 03:00:00` UTC, inclusivo.
- Guardado/envío/reenvío: hasta `2026-08-27 23:59:59` local / `2026-08-28 06:59:59` UTC, inclusivo.
- Vectores verdes: 19:59:59/20:00:00 permitidos y 20:00:01 rechazado; 23:59:58/23:59:59 permitidos y 00:00:00 rechazado.
- Timezone, configuración, `due_at`, rúbrica o paquete divergentes fallan cerrados.

## 8. Autorización y privacidad

| Actor | Leer propia | Enviar inicial | Editar/reenviar reabierta | Reabrir | Panel de evaluaciones |
|---|---:|---:|---:|---:|---:|
| Juez propietario exacto | Sí | Sí | Sí | No | No |
| Otro juez/no asignado | No | No | No | No | No |
| Admin con permisos | Panel | No | Sí | Sí | Sí |
| Admin sin permiso | No | No | No | No | No |
| Reviewer/participant/visitor/roleless/multirol | No | No | No | No | No |

Conflicto, cancelación o void revocan acceso. El conflicto ordinario se rechaza tras cualquier revisión enviada. Un replacement usa otro agregado y no hereda evaluaciones. El juez ve sólo un aviso genérico de reapertura; el admin ve actor y motivo. XSS se renderiza como texto y no se ejecuta.

Auditoría M7 conserva sólo IDs técnicos, revisiones, modo, estados, locks, conteos, completitud y reason codes. Las pruebas verifican ausencia de motivo, score, componente, total, comentarios, propuesta, archivos, URL y PII. El motivo vive cifrado únicamente en `evaluation_reopenings`.

## 9. Cálculo y concurrencia

BCMath sigue siendo autoridad para v1 de cinco criterios y v2 de cuatro. Permanecen verdes los vectores 0, 100, 1.25, 75.25/77.50, incompleto `NULL` y `HALF_UP` 12.3449→12.34, 12.3450→12.35, 99.9950→100.00.

Las carreras MySQL reales verifican un solo envío, una sola reapertura y un solo actor en carrera juez/admin. En dos pestañas Firefox, la primera guardó y la segunda recibió HTTP 409 con “Ningún dato fue sobrescrito”; el servidor conservó el primer valor.

## 10. Pruebas y gates

- Regresión previa: 50/601.
- Regresión dirigida final M5–M7: 60/847.
- M7 final: 5/165.
- Suite definitiva: 208 pruebas/2,385 aserciones en 982.42 s.
- `vendor/bin/pint --test`: verde.
- `composer validate --strict`, `composer check-platform-reqs`, `composer audit`: verdes; cero advisories Composer.
- `yarn audit`: exit 2 sólo por el advisory bajo conocido de Quill sin parche; cero moderados, altos o críticos.
- Build Vite: 784 módulos, tres assets, verde.
- JSON de producto: 11 archivos válidos; `.vscode/settings.json` es JSONC de tooling y se excluyó explícitamente.
- 15 enlaces Markdown locales válidos; 104 rutas; tres schedules existentes; 23 migraciones aplicadas bajo guard; `git diff --check` verde.

## 11. UAT Firefox local

Datos `example.test`, correo `array`, cola local y tres viewports: 1440×900, 1024×768 y 390×844.

- Inicio, captura v1, guardado/revisión, total servidor 75.25 y mínimo 99/100.
- Doble clic produjo un único envío; enviado quedó sólo lectura y se retiró la acción imposible de conflicto.
- Reaperturas, historial, reenvío administrativo y del juez; total recalculado 76.25.
- Admin mostró juez sujeto, actor real, modo, fuente/destino y motivo; juez mostró sólo aviso genérico.
- XSS quedó inerte (`xssExecuted=false`, cero scripts con marcador).
- Dos pestañas produjeron 409 sin overwrite.
- Skip link, teclado, offcanvas, foco, zoom, reflow sin overflow (`scrollWidth=clientWidth=390`), consola sin errores/warnings, 403 de juez al panel y 404 para ULID inexistente.
- El recorrido detectó y corrigió dos derivas M6A: texto del dashboard dependiente del flag y acción de conflicto posterior al envío.

## 12. Archivos y compatibilidad

Se añadieron migración, enums, modelo de reapertura, evento, servicio de ventanas, Actions/guards, Requests, middleware, Policy, controladores y vistas juez/admin; se ajustaron modelos M6, conflictos, navegación, seeder, rutas y pruebas. Se crearon el ExecPlan M7, ADR 0011 y este informe; se actualizaron alcance, arquitectura, modelo, seguridad, UX, QA, riesgos, preguntas, operación, estado, product spec, trazabilidad y handoff.

No se modificaron PDFs jurídicos, hashes, aceptaciones, propuestas, snapshots, folios ni archivos privados. La compatibilidad v1/v2 y M1–M6A quedó verde; los flags nacen apagados.

## 13. Riesgos residuales

- Bloqueo jurídico de mínimos: impide release/producción.
- Quill conserva un advisory bajo sin parche; no fue introducido por M7.
- UAT fue Firefox/local con datos sintéticos; staging, multi-browser, producción, SMTP, capacidad y restore siguen no autorizados/no verificados.
- M7 no notifica envío o reapertura; esa conducta pertenece exclusivamente a M8.
