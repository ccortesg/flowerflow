# ExecPlan — Fase 02B M3: rúbrica global versionada

> **Corrección final vigente — 2026-08-18:** M3 no cambia. Las referencias siguientes a capacidad son históricas; M4A implementa cuatro primary + dos substitute, todos ilimitados.

**Estado:** COMPLETE — GO LOCAL/TEST — NOT AUTHORIZED FOR PRODUCTION
**Fecha:** 2026-08-18 (`America/Hermosillo`)
**Repositorio:** `/home/ccortesg/workspace/flowerflow`

## Objetivo observable

Implementar exclusivamente M3: persistencia y validación exacta de una rúbrica global por competencia, versiones `draft|active|superseded`, gestión administrativa de borradores, activación atómica, sustitución e inmutabilidad. M1/M2 son invariantes. M4–M10, asignaciones, conflictos, evaluaciones, puntajes, consolidación, ganadores y producción permanecen fuera.

## Baseline verificado antes de editar

- `pwd` y Git toplevel: `/home/ccortesg/workspace/flowerflow`.
- Rama: `codex/submission-deadline-extension`.
- `HEAD`: `865059ad302ff4195ac18f671bd6fa13b99e398b`.
- `origin/codex/submission-deadline-extension`: `865059ad302ff4195ac18f671bd6fa13b99e398b`.
- Ancestro común: `865059ad302ff4195ac18f671bd6fa13b99e398b`.
- `git status --short`, `git diff` y `git diff --check`: sin cambios preexistentes.
- Producción no se consultó. `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR`.

## Contrato aprobado y decisiones técnicas M3

- Una rúbrica global para las cuatro categorías, vinculada a `competitions`.
- Códigos/orden/pesos exactos: `pertinence` 20, `clarity` 20, `feasibility` 25, `impact` 25 y `coherence` 10.
- Escala 0–10, paso 0.5, total futuro 0–100, precisión 4/2 y `HALF_UP`.
- Comentario general futuro 100–2,000; comentario por criterio opcional hasta 1,000.
- Descripciones de criterios nulas y mostradas como `POR_CONFIRMAR`; no se inventa contenido.
- `draft` puede corregirse sólo dentro del contrato exacto. `active` y `superseded` no se editan ni eliminan.
- Activar una versión bloquea la competencia y sus versiones, sustituye la activa anterior y conserva ambas.
- La base usa `active_slot`: `1` sólo para `active`, `NULL` en otros estados; `CHECK` de coherencia y `UNIQUE(competition_id, active_slot)` impiden dos activas. La transacción y `lockForUpdate()` siguen siendo la autoridad de concurrencia.
- Permisos separados `view evaluation rubrics` y `manage evaluation rubrics`, sólo para `admin`; nunca se concede `access judge workspace` al admin.
- La versión 1 canónica se provisiona idempotentemente sólo en local/testing, queda `draft`, no crea usuarios/asignaciones/evaluaciones y falla cerrado si encuentra divergencia.
- `P2B-BLOCK-001=RESOLVED BY OWNER 2026-08-18`: cuatro principales sin límite fijo y quinto sustituto exclusivo con capacidad diez. M3 no modifica perfiles ni crea jueces/asignaciones.

## Modelo previsto

### `rubric_versions`

ULID público, competencia, versión positiva única, título interno, estado enum, contrato numérico/de comentarios, ranura activa, actores/fechas/razón de activación y sustitución, timestamps. FKs restrict/null según evidencia y checks MySQL para estados, precisión, escala y coherencia temporal.

### `rubric_criteria`

Versión, código estable, etiqueta española, descripción nullable, peso, mínimo, máximo, paso y orden; unicidades por código/orden y checks de rango. La validación de aplicación exige el contrato exacto completo y peso total `100.0000`.

## Plan de implementación

- [x] Leer instrucciones, ExecPlans, documentación y ADR obligatorios.
- [x] Verificar baseline Git y ausencia de cambios preexistentes.
- [x] Demostrar guard MySQL exacto antes de cualquier mutación.
- [x] Añadir enums, contrato canónico y modelos guarded.
- [x] Añadir migración M3 aditiva/reversible con restricciones y permisos.
- [x] Añadir provisionador/seeder local-testing idempotente y fail-closed.
- [x] Añadir Actions transaccionales de crear/editar/activar y auditoría redactada.
- [x] Añadir Requests, Policy, controller, rutas y UI `/panel/rubricas`.
- [x] Añadir pruebas de contrato, autorización, inmutabilidad, activación, sustitución, concurrencia, migración y regresión M1/M2.
- [x] Ejecutar forward/rollback/forward sólo en `flowerflow_testing`.
- [x] Ejecutar gates automatizados, build, inventarios y scans.
- [x] Ejecutar UAT Firefox local desktop/tablet/mobile con datos sintéticos.
- [x] Actualizar documentación, trazabilidad, ADR-0008, riesgos y este registro vivo.

## Pruebas y gates

- Guard: `APP_ENV=testing`, MySQL, host loopback, base `flowerflow_testing`, usuario `flowerflow_testing_user`, `SELECT DATABASE()` exacto.
- Migración: upgrade con usuarios/perfiles sintéticos, down fail-closed con rúbricas, limpieza sólo sintética, rollback y forward; no modifica M1/M2.
- Feature/integration: matriz por rol, contrato inválido, mass assignment, ULID/IDOR, password/razón, exactamente una activa, sustitución, inmutabilidad y dos activaciones concurrentes.
- Regresión: suites M1/M2 y suite completa.
- Calidad: Pint, Composer validate/platform/audit, Yarn audit, build, JSON, rutas, schedule, migrate status, `git diff --check`, enlaces Markdown y scan de secretos/PII.
- Browser: Firefox local, admin/roles denegados, vacío/crear/detalle/editar/activar/inmutabilidad, 1440/tablet/mobile, teclado/foco/reflow/consola.

## Riesgos y mitigaciones

- **Dos activas por carrera:** lock de competencia + lock de versiones + `active_slot`/CHECK/unique + prueba concurrente.
- **Divergencia silenciosa del provisionador:** comparar cada campo y criterio; excepción sin overwrite.
- **Mutación de evidencia:** modelos guarded, acciones explícitas y bloqueo de update/delete para estados no draft; sin rutas delete/reactivate.
- **Descripción inventada:** persistir `NULL` y UI `POR_CONFIRMAR`.
- **Escalada de permisos:** role exacto admin, permisos separados, Policy/Request/ruta y regresión negativa.
- **Afectación a datos existentes:** migración aditiva, sin backfill de usuarios/propuestas/jueces y sin tablas de evaluación.

## Rollback

El `down()` puede retirar borradores M3 no referenciados para conservar reversibilidad, pero debe fallar si existe una versión activa o sustituida. En local/testing se comprueba que usuarios, roles previos, perfiles de juez y datos existentes permanecen. Los permisos M3 sólo se eliminan si no hay asignaciones directas. No se usa rollback en producción ni sobre datos reales.

## Registro vivo

- 2026-08-18: baseline limpio y sincronizado; lectura obligatoria completa.
- 2026-08-18: se detectó una contradicción vigente: documentación secundaria usa `relevance` y descripciones inventadas; el contrato autorizado usa `pertinence` y descripción nula. Se corregirá sin reescribir historia identificada como tal.
- 2026-08-18: implementación aún no iniciada; guard MySQL pendiente.
- 2026-08-18: guard exacto verde. El primer `down()` bloqueaba también la v1 draft sembrada y era incompatible con pruebas heredadas `DatabaseMigrations`; se corrigió para permitir sólo borradores no referenciados y fallar ante `active|superseded`.
- 2026-08-18: migración/rollback/forward verde con datos M1/M2 sintéticos preservados; el rollback falla deliberadamente ante evidencia `active|superseded`. El estado final fue recreado y sembrado exclusivamente en `flowerflow_testing`.
- 2026-08-18: pruebas M3 dirigidas 8/132; M1+M2+M3 24/399; suite completa 133/1,448. La prueba concurrente usa dos procesos MySQL y termina con una sola activa.
- 2026-08-18: Pint, Composer validate/platform/audit con Composer 2.10.2, build, JSON, rutas, schedule, migraciones y `git diff --check` verdes. Yarn conserva un advisory bajo conocido de Quill 2.0.3 y cero moderados/altos/críticos.
- 2026-08-18: UAT Firefox local verde en 1440×900, 1024×768, 390×844 y reflow equivalente a 200 %; teclado/foco, consola, 403, 404, activación, sustitución e inmutabilidad sin hallazgos. Los datos sintéticos fueron retirados mediante `migrate:fresh --seed` después del guard.
- 2026-08-18: M3 queda `GO LOCAL/TEST`. M4 —asignaciones y conflictos— es la siguiente puerta propuesta, pero permanece `NOT IMPLEMENTED / NOT AUTHORIZED`. Producción no fue consultada.

## Resultado y evidencia final

- Modelo: `rubric_versions` y `rubric_criteria`, ULID público, contrato decimal exacto, descripciones nulas y ciclo `draft|active|superseded`.
- Autorización: sólo `admin` exacto recibe `view evaluation rubrics` y `manage evaluation rubrics`; reviewer, participant, judge, cero rol y multirol fallan cerrados.
- Concurrencia: lock de competencia/versiones más `CHECK` y `UNIQUE(competition_id, active_slot)`; dos activaciones reales no produjeron doble activa.
- Reversibilidad: borradores no referenciados permiten `down`; una activa o sustituida bloquea el rollback para proteger evidencia.
- Alcance: no se crearon jueces, asignaciones, conflictos, paquetes ciegos, evaluaciones, puntajes, consolidación, ganadores ni resultados.
- Evidencia ampliada: `docs/20-phase-02b-m3-implementation-report-2026-08-18.md`.

## Comandos ejecutados y resultado

| Comando | Resultado |
|---|---|
| baseline `pwd`, `git rev-parse`, `git status`, `git diff` | repo/rama/SHA exactos; árbol inicial limpio |
| guard mediante Artisan + `SELECT DATABASE()` | testing/MySQL/loopback/base/usuario exactos, sin secreto |
| `php artisan migrate`, rollback M3 y forward | verde con draft; active/superseded bloquearon `down()` como protección |
| `APP_ENV=testing php artisan migrate:fresh --seed --env=testing --force` | 15/15; cierre limpio con v1 draft y cero usuarios |
| pruebas M3/M1/M2 y `APP_ENV=testing php artisan test --compact` | 8/132; 24/399; suite 133/1,448 |
| `vendor/bin/pint --test` | verde |
| `composer validate --strict --no-check-publish` | verde; Composer del sistema 2.2.6 emitió deprecations externas |
| `composer check-platform-reqs --no-dev` | verde |
| `composer audit --locked` | Composer 2.2.6 no ofrece el comando; repetido con `/home/ccortesg/.local/bin/composer` 2.10.2: cero advisories |
| `corepack yarn audit --groups dependencies --level moderate` y JSON | sólo Quill 2.0.3 bajo; exit 2 de Yarn 1 por ese advisory; cero moderados+ |
| `scripts/build_frontend_production.sh` | verde: 98 iconos, 784 módulos, tres assets |
| JSON, `route:list --except-vendor`, `schedule:list`, `migrate:status` | dos JSON válidos, 59 rutas, purga XLSX horaria, 15/15 migraciones |
| Playwright CLI/Firefox contra `scripts/serve_local_testing.sh` | UAT verde; el wrapper de la skill tenía CRLF en su shebang y se usó el comando `npx --package @playwright/cli` equivalente, sin añadir dependencia |
| links Markdown, scan de secretos/PII, `git diff --check` | 12 destinos, cero rotos; cero hallazgos de alta confianza; whitespace verde |
