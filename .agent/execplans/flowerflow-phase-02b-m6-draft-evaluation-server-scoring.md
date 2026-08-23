# Fase 02B M6 — evaluación en borrador y cálculo en servidor

Este ExecPlan es un documento vivo. Las secciones `Progreso`, `Hallazgos inesperados`, `Decisiones` y `Resultados` deben actualizarse a medida que avance el milestone, conforme a `.agent/PLANS.md`.

## Propósito

Permitir que únicamente el juez propietario de una asignación vigente abra de forma explícita un borrador de evaluación, capture parcialmente los cinco puntajes y comentarios, guarde con concurrencia optimista y reciba un total calculado exclusivamente por el servidor cuando los cinco puntajes estén presentes. El milestone termina con evidencia local/test y UAT Firefox usando sólo datos sintéticos.

## Baseline comprobado

- Repositorio y Git toplevel: `/home/ccortesg/workspace/flowerflow`.
- Rama: `codex/submission-deadline-extension`.
- `HEAD`, upstream y merge-base al inicio: `e4e4cd2ff7144cce5f9385f5f11c122cda80e7b8`.
- Árbol inicial: limpio; `git status --short --untracked-files=all`, `git diff`, `git diff --stat` y `git diff --check` sin salida.
- Estado heredado: M1–M5 `GO LOCAL/TEST`; 18 migraciones; suite M5 documentada en 150 pruebas y 1,703 aserciones.
- Guard de base demostrado antes de cualquier mutación de esquema/datos:
  - `APP_ENV=testing`
  - `DB_CONNECTION=mysql`
  - `DB_HOST=127.0.0.1`
  - `DB_DATABASE=flowerflow_testing`
  - `DB_USERNAME=flowerflow_testing_user`
  - `SELECT DATABASE()=flowerflow_testing`
- No se imprimió la contraseña ni se accedió a producción o servicios externos.

## Alcance autorizado

- Migración aditiva para `evaluations`, `evaluation_revisions`, `evaluation_scores` y permiso `manage own evaluation drafts`.
- Modelos guarded y no eliminables, enums mínimos `draft`, relaciones y Policies scoped por `JudgeAssignment`.
- Actions transaccionales explícitas para apertura idempotente y guardado optimista.
- Cálculo decimal determinista con BCMath, cuatro decimales internos y presentación de dos decimales `HALF_UP`.
- POST y PATCH exactos bajo `/juez/asignaciones/{judgeAssignment}/evaluacion`; GET existente permanece estrictamente de lectura.
- Form Requests de allowlist cerrada; rechazo de IDs/campos/total/componentes/estado/actores/versiones/timestamps del cliente.
- Ampliación accesible del detalle M5 para inicio explícito, captura, progreso y total servidor.
- Auditoría redactada de apertura, guardado, rechazo stale e invariantes relevantes.
- Pruebas unitarias/Feature/concurrencia/migración, suite completa y UAT Firefox local.
- Actualización de documentación, estado, trazabilidad, QA, riesgos y handoff con evidencia real.

## Exclusiones estrictas

No se implementan envío final, confirmación, estado `submitted`, reapertura, nuevas revisiones, edición administrativa en nombre del juez, consolidación, empate, ranking, ganador, resultados, notificaciones, recordatorios, retención, purga, generación automática/lote, scheduler ni operación productiva. No se alteran PDF jurídicos, documentos públicos v1.1, hashes, aceptaciones, plazo de propuestas, propuestas, snapshots, folios, archivos privados, admisibilidad o datos reales. No se hace stage, commit, push ni despliegue.

## Contrato de dominio y modelo

`JudgeAssignment` continúa siendo la raíz autoritativa: fija juez, `submission_version_id`, `rubric_version_id`, `due_at` y vínculo con el paquete activo. La evaluación nunca consulta una rúbrica activa actual para sustituir la fijada.

`evaluations` tiene una fila única por asignación, ULID público, referencias restrictivas a asignación/rúbrica/paquete, `current_revision_id` nullable sólo durante la creación transaccional, estado único M6 `draft`, `lock_version` inicial cero, actor y fecha de inicio UTC.

`evaluation_revisions` conserva la revisión draft número 1 de M6, comentario general nullable, total nullable, actores reales y unicidad por evaluación/número. `source_revision_id` permanece nullable y sin uso.

`evaluation_scores` contiene exactamente una fila por cada criterio de la rúbrica fijada. Un puntaje ausente es `NULL`; cero es un valor capturado. Componente y puntaje permanecen ambos nulos o ambos presentes. Las tablas usan FKs restrict, índices, unique y checks MySQL compatibles, complementados por validación de aplicación.

Todos los modelos usan `$guarded = ['*']`, bloquean `delete`, y no exponen mutaciones de negocio fuera de las Actions.

## Rutas e interfaz

- `GET /juez/asignaciones/{judgeAssignment}`: sólo lectura; nunca crea evaluation/revision/score.
- `POST /juez/asignaciones/{judgeAssignment}/evaluacion`: inicio explícito.
- `PATCH /juez/asignaciones/{judgeAssignment}/evaluacion`: reemplazo autorizado del estado editable de la revisión draft 1, con `lock_version` obligatorio.

POST/PATCH conservan `auth`, rol exacto `judge`, correo verificado, perfil active, flag de evaluación y además exigen CSRF, throttle de mutaciones y permiso exacto `manage own evaluation drafts`. Sólo `judge` recibe ese permiso. El detalle muestra “Iniciar evaluación” si no existe agregado; tras abrir, muestra formulario semántico sin dependencia de JavaScript. Después del plazo, el borrador puede mostrarse sólo lectura si todas las demás invariantes siguen vigentes.

## Fórmula decimal

Criterios exactos: `pertinence=20.0000`, `clarity=20.0000`, `feasibility=25.0000`, `impact=25.0000`, `coherence=10.0000`. Escala `0.0000..10.0000`, paso exacto `0.5000`.

Por criterio: `component = (score / 10) * weight`. El servidor normaliza y persiste componente/total con cuatro decimales usando BCMath; el total es `NULL` hasta que existan los cinco puntajes. La presentación usa helper decimal `HALF_UP` a dos decimales. Ningún valor derivado del navegador se acepta.

Vectores obligatorios: todos 0; todos 10; sólo Viabilidad 0.5; combinación 7.5/8/6.5/9/5.5; incompleto nulo; y helper sintético `12.3449→12.34`, `12.3450→12.35`, `99.9950→100.00`.

## Concurrencia e idempotencia

La apertura bloquea/revalida asignación, rúbrica y paquete; crea evaluación, revisión 1 y cinco scores dentro de una transacción. La unicidad de `judge_assignment_id` es la última defensa. Dos aperturas simultáneas convergen en el mismo agregado completo.

Cada PATCH exige `lock_version`. Dentro de una transacción se bloquean evaluación y revisión actual, se revalidan todas las invariantes y se compara la versión. Si coincide, sólo se guardan comentario general y campos por código permitido, se recalculan componentes/total y se incrementa exactamente una vez. Si difiere, no hay escritura y se responde HTTP 409 con mensaje accesible. No existe last-write-wins.

## Autorización y vencimiento

Sólo un usuario con rol exacto `judge`, correo verificado, perfil `active`, flag encendido, permiso exacto y ownership de una asignación `active` puede iniciar/leer/guardar. Deben coincidir la rúbrica fijada, el paquete activo y la versión de propuesta. `conflict_declared`, `voided` y `cancelled` revocan inmediatamente lectura y mutación del borrador.

El `due_at` autoritativo debe coincidir con `2026-08-28 06:59:59 UTC` (`2026-08-27 23:59:59 America/Hermosillo`). El segundo exacto es inclusivo; desde el siguiente se bloquea inicio/guardado. Cualquier drift de assignment/rúbrica/package/plazo falla cerrado.

Una asignación replacement activa puede abrir su propio agregado independiente, compartiendo la rúbrica y paquete fijados sin copiar datos del juez sustituido. Si el replacement entra en conflicto o deja de operar, Q-032B conserva evidencia y falla cerrado; no se infieren cadenas.

## Validación y seguridad de payload

PATCH admite sólo `lock_version`, `general_comment` y `criteria`; cada criterio sólo `code`, `score`, `comment`. Se rechazan claves extra, códigos duplicados/desconocidos/de otra rúbrica, IDs, totales/componentes hostiles, notación científica, NaN/infinito, negativos, sobre-rango y valores fuera del paso exacto. Comentario general nullable/máximo 2,000; comentario de criterio nullable/máximo 1,000. El contenido es texto plano UTF-8 escapado al renderizar.

## Auditoría redactada

Eventos: `evaluation.draft_opened`, `evaluation.draft_saved`, `evaluation.draft_save_rejected_stale` y rechazos relevantes de invariantes en Actions. La metadata sólo puede incluir IDs técnicos, `revision_number`, versiones de lock anterior/nueva, cantidad de criterios capturados, `is_complete` y `reason_code`. No incluye total, componentes, scores, comentarios, contenido, URL, nombre/path de archivo, correo, nombre personal ni PII.

## Pruebas previstas

1. GET repetido sin mutación.
2. POST repetido y carrera real convergen en un agregado completo.
3. Matrices positiva y negativa por rol/estado/ownership/asignación/package/plazo.
4. Guardado parcial/completo, `NULL` frente a cero y fórmula exacta.
5. Payload/códigos/decimales/comentarios/XSS hostiles.
6. Dos pestañas con `lock_version` igual: primer guardado OK, segundo HTTP 409 sin overwrite.
7. Segundo anterior/exacto/posterior del plazo.
8. Conflicto posterior conserva evidencia y revoca acceso.
9. Replacement independiente.
10. Auditoría/logs sin contenido prohibido y paquete M5 inmutable.
11. Migración forward/rollback/forward bajo guard y preservación sintética M1–M5.
12. Regresión dirigida M5, M4A, M4, M3, M2, M1 y suite completa.

## Validaciones finales previstas

`php artisan test` dirigido/completo, `vendor/bin/pint --test`, `composer validate --strict`, `composer check-platform-reqs`, `composer audit`, `yarn audit`, build de producción, JSON, route/schedule/migrate status, `git diff --check`, enlaces Markdown, scan de secretos/PII/contenido de evaluación y UAT Firefox 1440×900, 1024×768 y 390×844.

## Rollback

El rollback operativo primario es `FLOWERFLOW_EVALUATION_ENABLED=false`, que corta el shell sin borrar evidencia. La migración es aditiva y su `down()` se niega si existe cualquier evaluación/evidencia M6. Sólo sin evidencia puede retirar tablas y permiso, preservando M1–M5. No se eliminan borradores para revertir.

## Progreso

- [x] 2026-08-18: baseline Git exacto comprobado; árbol limpio.
- [x] 2026-08-18: documentación, ExecPlans M1–M5 y ADR obligatorios leídos completamente.
- [x] 2026-08-18: guard exacto de `flowerflow_testing` demostrado sin secretos.
- [x] 2026-08-18: esquema aditivo, modelos guarded, permiso exclusivo y dominio decimal BCMath implementados.
- [x] 2026-08-18: Actions transaccionales, Policy, Requests, rutas, controller y auditoría redactada implementados.
- [x] 2026-08-18: interfaz progresiva sin dependencia de JavaScript y pruebas M6 implementadas.
- [x] 2026-08-18: forward/rollback/forward, rechazo de rollback con evidencia, regresión M1–M6 y gates técnicos ejecutados.
- [x] 2026-08-18: UAT Firefox local completado en tres viewports, incluida concurrencia, XSS, vencimiento, conflicto, replacement e IDOR.
- [x] 2026-08-18: datos UAT sintéticos retirados mediante `migrate:fresh --seed` bajo el guard exacto; 19/19 migraciones finales.
- [x] 2026-08-18: segunda pasada documental, 22 archivos Markdown con enlaces locales válidos, scans, rutas, scheduler, estado de migraciones y diff definitivo verdes.

## Hallazgos inesperados

- La documentación contenía el baseline histórico `865059a`, referencias vigentes a M1–M5 “no publicados” y contratos intermedios 10/30. Se corrigieron las superficies vigentes; los informes/ExecPlans que realmente iniciaron en ese SHA conservan el dato como historia explícita. El contrato actual es `4+2` ilimitado, selección manual y paquete por acción administrativa explícita.
- El contrato de M3 menciona comentario general obligatorio desde 100 caracteres; M6 lo deja vacío/máximo 2,000 y reserva el mínimo para M7, tal como autoriza el prompt actual.
- El lock inicial contenía advisories transitivos nuevos en Vite/Rollup/PostCSS/Nanoid. La actualización acotada de Vite `6.3.5→6.4.3` corrigió los avisos altos sin cambiar major; permanece únicamente el advisory bajo conocido de Quill 2.0.3 sin parche.
- `APP_ENV=testing` usa `SESSION_DRIVER=array`; para UAT multi-request se usó el servidor seguro del repositorio con sesiones persistentes. El reloj posterior al vencimiento requirió además un router temporal ignorado y un `SESSION_LIFETIME` sintético ampliado; no se incorporó ese arnés al producto.

## Decisiones

- La instrucción actual del propietario es el contrato canónico M6 y sustituye las ambigüedades históricas sobre efectos laterales de GET, contenido de auditoría y alcance de comentario mínimo.
- La implementación conservará cinco filas de score desde la apertura; una fila con `score=NULL` representa criterio no capturado y es distinta de cero.
- La respuesta a concurrencia stale será HTTP 409 sin persistencia y con una vista/mensaje accesible que ordene recargar antes de volver a guardar.

## Resultados

- **Modelo y migración:** 19/19 migraciones aplicadas. Forward/rollback/forward retiró exclusivamente M6 sin evidencia y conservó conteos M1–M5; un audit sintético `evaluation.*` hizo que `down()` abortara con el mensaje fail-closed esperado.
- **Pruebas:** M6 dirigido 13 pruebas/228 aserciones; M1–M6 dirigido 54/888; segunda suite completa 163/1,937 en 434.98 s, todo verde. Los vectores de total fueron `0.0000`, `100.0000`, `1.2500`, `75.2500` y `NULL`; el helper `HALF_UP` produjo `12.34`, `12.35` y `100.00`.
- **Calidad:** Pint, Composer validate/platform/audit, JSON y build Vite verdes. `yarn audit` quedó únicamente en 1 advisory bajo de Quill; 0 moderados/altos/críticos. Build: Node 22.23.1, Yarn 1.22.22, Vite 6.4.3, 98 iconos, 784 módulos y tres assets.
- **UAT Firefox:** 1440×900, 1024×768 y 390×844 sin overflow; zoom/teclado/foco/consola limpios. GET repetido mantuvo `0/0/0`; POST abrió `1/1/5`; parcial conservó total `NULL`; completo mostró/persistió `75.25/75.2500`; la segunda pestaña recibió 409 y no sobrescribió. XSS quedó escapado. El conflicto retiró acceso conservando filas; el replacement creó un agregado independiente. IDOR devolvió 403 y ULID inexistente 404. Después del segundo posterior al plazo el borrador se mostró sólo lectura.
- **Limpieza:** se cerraron los servidores/navegadores locales y se reconstruyó `flowerflow_testing` bajo guard. No quedaron usuarios, propuestas, paquetes ni evaluaciones UAT.
- **Estado:** `GO LOCAL/TEST`. M7–M10, stage/commit/push, despliegue y producción continúan fuera.
