# Informe de implementación Fase 02B M6 — evaluación en borrador y cálculo servidor

> **Adenda M6A — 2026-08-24:** M6 continúa compatible con v1 histórica de cinco criterios y ahora usa dinámicamente la rúbrica fijada; nuevas asignaciones v2 crean cuatro scores. Las referencias inferiores a “exactamente cinco” describen el corte histórico M6. M7–M10 permanecen no implementados/no autorizados.

**Fecha:** 2026-08-18  
**Estado:** `GO LOCAL/TEST`  
**Producción:** no accedida, no verificada y no autorizada  
**Siguiente alcance:** M7–M10 `NOT IMPLEMENTED / NOT AUTHORIZED`

## 1. Baseline y límites

El trabajo inició en `/home/ccortesg/workspace/flowerflow`, rama `codex/submission-deadline-extension`, con `HEAD`, upstream y merge-base `e4e4cd2ff7144cce5f9385f5f11c122cda80e7b8` y árbol limpio. El baseline heredado era M1–M5 `GO LOCAL/TEST`, 18 migraciones y 150 pruebas/1,703 aserciones.

No se hizo stage, commit, push, despliegue ni acceso a producción/AWS/EC2. No se modificaron PDF jurídicos, documentos públicos v1.1, aceptaciones, plazo de propuestas, propuestas reales, snapshots, folios, archivos privados ni admisibilidad.

Antes de cada operación destructiva o de esquema se demostró, sin contraseña: `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_DATABASE=flowerflow_testing`, `DB_USERNAME=flowerflow_testing_user` y `SELECT DATABASE()=flowerflow_testing`.

## 2. Modelo implementado

- `evaluations`: una por `judge_assignment_id`, ULID, rúbrica y paquete fijados, revisión actual, estado único `draft`, `lock_version`, actor y fecha UTC.
- `evaluation_revisions`: revisión M6 número 1, comentario general nullable, total decimal nullable, actores y unicidad evaluación+número.
- `evaluation_scores`: exactamente cinco filas ligadas a los criterios de la rúbrica fijada; score/componente ambos nulos o ambos presentes.
- FKs `RESTRICT`, checks de estado, revisión positiva, límites de comentarios, rango/paso de score, total y coherencia score/componente.
- Modelos con `$guarded=['*']`; `update`/`delete` directos bloqueados. Las mutaciones de dominio viven en Actions transaccionales.

La migración es aditiva y no hace backfill. Su `down()` se niega si existe una evaluación, revisión, score o audit `evaluation.*`; sin evidencia retira sólo las tres tablas y el permiso M6.

## 3. Rutas, permiso y autorización

| Método | Ruta | Función |
|---|---|---|
| GET | `/juez/asignaciones/{judgeAssignment}` | lectura pura; nunca crea filas |
| POST | `/juez/asignaciones/{judgeAssignment}/evaluacion` | apertura explícita e idempotente |
| PATCH | `/juez/asignaciones/{judgeAssignment}/evaluacion` | guardado de revisión 1 con lock optimista |

POST/PATCH usan CSRF, `throttle:panel-mutations` y `manage own evaluation drafts`. Sólo el rol exacto `judge` recibe ese permiso. La Policy y las Actions exigen además correo verificado, perfil activo, contraseña inicializada, flag encendido, ownership, assignment/package activos, rúbrica fijada válida y plazo autoritativo sin drift. Visitor, participant, reviewer, admin, otro juez, no asignado, pending, suspended, roleless y multirol fallan cerrados.

`conflict_declared`, `voided` y `cancelled` revocan acceso de inmediato. Un replacement activo abre un agregado independiente; no copia scores/comentarios del original y no se infiere otra cadena si el replacement deja de operar.

## 4. Cálculo decimal y payload

El servidor usa BCMath. Los criterios son Pertinencia 20.0000, Claridad 20.0000, Viabilidad 25.0000, Impacto 25.0000 y Coherencia 10.0000, escala 0.0000–10.0000 y paso 0.5000. Por criterio aplica `(score/10)×weight`; componentes y total se conservan a cuatro decimales. El total permanece `NULL` hasta capturar los cinco. La presentación usa `HALF_UP` a dos decimales.

El PATCH admite exclusivamente `lock_version`, `general_comment` y una lista `criteria` con `code`, `score`, `comment`. Rechaza IDs y campos extra, duplicados/desconocidos, total/componentes cliente, notación científica, NaN/infinito, rango/step inválidos y comentarios fuera de límite. Vacío se conserva como `NULL`, no como cero.

Vectores ejecutados:

| Entrada | Total interno | Presentación |
|---|---:|---:|
| todos 0 | 0.0000 | 0.00 |
| todos 10 | 100.0000 | 100.00 |
| sólo Viabilidad 0.5; restantes 0 | 1.2500 | 1.25 |
| 7.5 / 8 / 6.5 / 9 / 5.5 | 75.2500 | 75.25 |
| un criterio ausente | NULL | no disponible |

El helper directo produjo `12.3449→12.34`, `12.3450→12.35` y `99.9950→100.00`.

## 5. Concurrencia, auditoría y aislamiento

La apertura bloquea asignación/rúbrica/paquete y dos POST concurrentes convergen en una evaluación, una revisión y cinco scores. Cada PATCH bloquea agregado/revisión/scores, compara `lock_version`, guarda allowlist, recalcula y aumenta la versión una sola vez. Una versión stale devuelve HTTP 409 accesible y no escribe.

Los eventos `evaluation.draft_opened`, `evaluation.draft_saved`, `evaluation.draft_save_rejected_stale` y rechazos de invariantes sólo registran IDs técnicos, número de revisión, locks anterior/nuevo, cantidad capturada, completitud y `reason_code`. La inspección UAT confirmó ausencia de total, componentes, scores, comentarios, contenido, archivos, correo, nombres y PII. M5 no recibe datos de evaluación.

## 6. Migración, pruebas y gates

- forward/rollback/forward: verde; preservó evidencia sintética M1–M5 y no generó evaluaciones.
- rollback con audit sintético M6: abortó fail-closed; después se retiró únicamente el probe.
- M6 dirigido: 13 pruebas/228 aserciones.
- M1–M6 dirigido: 54/888.
- suite completa: 163/1,937 en 434.98 s en la segunda pasada final.
- Pint, `composer validate --strict`, platform requirements y Composer audit: verdes.
- Yarn: Vite `6.3.5→6.4.3` y transitivas seguras; sólo Quill 2.0.3 conserva 1 advisory bajo sin parche, 0 moderados/altos/críticos.
- build: 98 iconos, 784 módulos, tres assets; JSON, 73 rutas, scheduler, enlaces de 22 Markdown modificados, scans y estado de 19 migraciones validados.

## 7. UAT Firefox sintético

Se recorrió en 1440×900, 1024×768 y 390×844, con teclado, foco, reflow y zoom. GET repetido mantuvo cero evaluaciones/revisiones/scores. El botón POST creó `1/1/5`; un guardado parcial conservó total `NULL`; el completo mostró 75.25 y persistió 75.2500. Refresh preservó datos.

Dos pestañas partieron del mismo lock: la primera guardó y la segunda recibió 409 sin overwrite. Payload XSS quedó como texto escapado y no creó scripts/imágenes. Después de conflicto el original perdió acceso, pero el agregado permaneció; el replacement vio el paquete común, no vio comentarios previos y abrió su agregado independiente (`2/2/10`). Acceso del replacement al assignment original devolvió 403; ULID inexistente, 404. Con reloj controlado posterior al plazo, el borrador apareció sólo lectura y la consola quedó limpia.

Los datos, usuarios, propuesta y archivos UAT fueron exclusivamente sintéticos. Al terminar se cerraron Firefox/servidores y se ejecutó `migrate:fresh --seed` bajo el guard exacto; la base quedó con 19 migraciones y sin evidencia UAT.

## 8. Riesgos y rollback

- Rollback operativo primario: `FLOWERFLOW_EVALUATION_ENABLED=false`; conserva borradores.
- El rollback estructural no es válido cuando ya existe evidencia M6.
- Quill mantiene un advisory bajo sin fix; sanitizer servidor y tratamiento como texto plano continúan.
- La ceguera es estructural, no semántica; el riesgo aceptado M5 no cambia.
- No existe todavía envío final, inmutabilidad submitted, reapertura, consolidación, empate, ranking, ganadores, notificaciones ni retención. Son M7+ y requieren autorización independiente.
- Ninguna evidencia local acredita producción.

## 9. Archivos fuente principales

La implementación vive en `database/migrations/2026_08_18_180000_create_evaluation_drafts.php`, `app/Actions/Evaluations/`, `app/Services/EvaluationDraftCalculator.php`, `app/Support/Decimal.php`, `app/Models/Evaluation*.php`, Requests/controller/Policy/rutas, el detalle juez y las suites `EvaluationDraft*Test.php`.
