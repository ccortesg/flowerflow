# Informe de implementación Fase 02B M3 — rúbrica versionada

> **Corrección final vigente — 2026-08-18:** la rúbrica M3 no cambia. La dependencia de asignación pasó históricamente de `1×10` a `2×30` y terminó en `4+2` ilimitado. M4A ya está verde; las referencias de capacidad de este informe son históricas.

**Fecha:** 2026-08-18 (`America/Hermosillo`)

**Decisión:** `GO LOCAL/TEST — NOT AUTHORIZED FOR PRODUCTION`

**Repositorio:** `/home/ccortesg/workspace/flowerflow`

## 1. Resumen ejecutivo

M3 implementa y valida exclusivamente el contrato persistente de la rúbrica global. Existen versiones con ciclo `draft → active → superseded`, cinco criterios exactos y ordenados, activación administrativa con contraseña/razón, sustitución atómica, inmutabilidad, auditoría redactada y gestión accesible en `/panel/rubricas` sólo para `admin`.

M1 y M2 permanecen verdes. M3 no crea jueces, asignaciones, conflictos, paquetes ciegos, evaluaciones, puntajes, consolidación, ganadores ni resultados. `/juez` continúa vacío. Producción y la URL pública no fueron consultadas; `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR`.

Fase 02B pasa de 20 % a **30 % funcional** porque tres de sus diez milestones están implementados localmente. La preparación técnica/documental para M4 se estima en **95 %**. `P2B-BLOCK-001=RESOLVED BY OWNER 2026-08-18`; persiste el límite operativo de diez sustituciones activas para el quinto juez, que M4 deberá aplicar de forma fail-closed.

## 2. Baseline y guard

| Evidencia | Resultado |
|---|---|
| `pwd` / Git toplevel | `/home/ccortesg/workspace/flowerflow` |
| Rama | `codex/submission-deadline-extension` |
| HEAD/remoto/merge-base inicial | `865059ad302ff4195ac18f671bd6fa13b99e398b` |
| Estado inicial | limpio; sin cambios ajenos que preservar |
| Entorno | `APP_ENV=testing` |
| Driver/host | MySQL / `127.0.0.1` |
| Base/usuario | `flowerflow_testing` / `flowerflow_testing_user` |
| `SELECT DATABASE()` | `flowerflow_testing` |

La contraseña no se imprimió ni se copió. Todas las mutaciones y cuentas fueron sintéticas y quedaron limitadas a la base aislada.

## 3. Modelo e invariantes

### `rubric_versions`

- una competencia y una versión positiva única;
- ULID público;
- estados respaldados `draft`, `active`, `superseded`;
- título interno y contrato exacto de escala, paso, total, precisión, redondeo y comentarios;
- actores/fechas/razón de activación y sustitución;
- `active_slot=1` exclusivamente para la activa y `NULL` para los demás estados;
- `CHECK` de coherencia y `UNIQUE(competition_id, active_slot)` como defensa de base, además del lock transaccional.

### `rubric_criteria`

| Orden | Código | Etiqueta | Peso | Rango | Paso | Descripción |
|---:|---|---|---:|---:|---:|---|
| 1 | `pertinence` | Pertinencia | 20 % | 0–10 | 0.5 | `NULL` / `POR_CONFIRMAR` |
| 2 | `clarity` | Claridad | 20 % | 0–10 | 0.5 | `NULL` / `POR_CONFIRMAR` |
| 3 | `feasibility` | Viabilidad | 25 % | 0–10 | 0.5 | `NULL` / `POR_CONFIRMAR` |
| 4 | `impact` | Impacto | 25 % | 0–10 | 0.5 | `NULL` / `POR_CONFIRMAR` |
| 5 | `coherence` | Coherencia | 10 % | 0–10 | 0.5 | `NULL` / `POR_CONFIRMAR` |

El contrato exige peso `100.0000`, total futuro 0–100, cuatro decimales internos, dos visibles, `HALF_UP`, comentario general futuro 100–2,000 y comentario por criterio opcional hasta 1,000. M3 no calcula ni captura una evaluación.

### Ciclo

1. El provisionador local/testing crea una única v1 `draft` exacta y nunca la activa.
2. Sólo un borrador puede editarse y únicamente dentro del contrato aprobado.
3. Activar toma locks de competencia y versiones, valida nuevamente, sustituye la activa anterior y activa el destino dentro de una sola transacción.
4. Una activa o sustituida no se edita, reactiva ni elimina.
5. Una divergencia de v1 hace fallar el provisionador sin sobrescribir evidencia.

## 4. Acceso efectivo

| Actor | Listar/ver | Crear/editar draft | Activar | `/juez` consume rúbrica |
|---|---:|---:|---:|---:|
| Visitante | No | No | No | No |
| Participant | 403 | 403 | 403 | No |
| Reviewer | 403 | 403 | 403 | No |
| Admin exacto | Sí | Sí | Sí, contraseña y razón | No como juez |
| Judge | 403 | 403 | 403 | No; shell M3 sigue vacío |
| Sin rol | 403 | 403 | 403 | No |
| Multirol inválido | 403 | 403 | 403 | No |

Los permisos nuevos son `view evaluation rubrics` y `manage evaluation rubrics`, sólo para `admin`. `admin` no recibe `access judge workspace` y `judge` no recibe permisos de panel/rúbrica.

## 5. Migración, compatibilidad y rollback

La migración M3 es aditiva. No modifica propuestas, folios, snapshots, aceptaciones jurídicas, `judge_profiles`, sesiones ni auditoría previa. Forward/rollback/forward preservó usuarios/perfiles sintéticos M1/M2 y no creó asignaciones o evaluaciones.

El `down()` elimina borradores no referenciados y los permisos M3 cuando es seguro. Si existe una versión `active` o `superseded`, falla de forma explícita para no destruir evidencia. El rollback fue probado en ambos caminos; no es una instrucción para producción.

## 6. Evidencia automatizada y UAT

| Gate | Resultado real |
|---|---|
| Pruebas M3 dirigidas | 8 pruebas / 132 aserciones, incluidas dos activaciones MySQL concurrentes |
| Regresión M1+M2+M3 | 24 / 399 |
| Suite completa | 133 / 1,448 |
| Pint | verde |
| Composer validate/platform | verdes; Composer del sistema 2.2.6 mostró avisos deprecados, sin fallo |
| Composer audit | verde con Composer 2.10.2; cero advisories PHP |
| Yarn audit | un advisory bajo conocido de Quill 2.0.3; cero moderados/altos/críticos |
| Build | Vite 6.3.5, 784 módulos, 98 iconos y tres assets |
| JSON | dos menús válidos |
| Rutas/schedule/migraciones | 59 rutas propias; purga XLSX horaria; 15/15 migraciones |
| Firefox | desktop 1440×900, tablet 1024×768, móvil 390×844 y reflow equivalente a 200 % |
| Accesibilidad/seguridad browser | skip-link/foco, teclado, consola limpia, 403 sin datos y 404 sin stack/SQL |
| Git whitespace | `git diff --check` verde |

La UAT activó v1, creó y activó v2, comprobó v2 activa/v1 sustituida, ausencia de edición en estados inmutables, una sola activa y auditoría de creación/activación/sustitución. Después se cerraron Firefox/servidor y `migrate:fresh --seed` retiró todas las cuentas/versiones sintéticas, dejando v1 draft canónica y cero usuarios.

## 7. Auditoría y riesgos residuales

Los eventos `rubric.draft_provisioned`, `rubric.draft_created`, `rubric.draft_edited`, `rubric.activated` y `rubric.superseded` conservan actor, sujeto técnico, versión, transición y razón cuando aplica. No incluyen contraseña, token, TOTP, PII o contenido de propuestas.

Riesgos residuales:

- Quill 2.0.3 conserva un advisory XSS bajo sin versión corregida; la sanitización servidor sigue siendo defensa obligatoria.
- Las descripciones extensas de los criterios permanecen `POR_CONFIRMAR`; no bloquean la estructura/pesos aprobados y no fueron inventadas.
- Riesgo histórico del corte M3: entonces se planificaba un sustituto y rechazo 11. La corrección intermedia `2×30` también fue sustituida; el contrato vigente es `4+2` ilimitado, M4A/M5 verdes y M6 separado.
- La operación productiva, SHA, migraciones, flags, workers, scheduler, SMTP, smoke e integridad permanecen sin evidencia técnica independiente.

## 8. Archivos funcionales principales

Inventario exacto del diff M3 al cierre: 46 archivos, 22 modificados y 24 nuevos.

- Dominio/modelo: `app/Enums/RubricVersionStatus.php`, `app/Models/RubricVersion.php`, `app/Models/RubricCriterion.php`, `app/Models/Competition.php`, `app/Services/EvaluationRubricContract.php`.
- Casos de uso: `app/Actions/Rubrics/ActivateRubricVersion.php`, `CreateRubricDraft.php`, `EnsureRubricAdministrator.php`, `ProvisionCanonicalRubricDraft.php`, `UpdateRubricDraft.php`.
- HTTP/autorización: `app/Http/Controllers/Panel/RubricVersionController.php`, Requests `ActivateRubricVersionRequest.php`, `StoreRubricVersionRequest.php`, `UpdateRubricVersionRequest.php`, `app/Policies/RubricVersionPolicy.php`, `routes/web.php`.
- Persistencia/datos: `database/migrations/2026_08_18_140000_create_rubric_versions_and_permissions.php`, `database/seeders/FlowerFlowSeeder.php`.
- UI: `resources/views/layouts/flowerflow.blade.php` y `resources/views/panel/rubrics/{index,create,edit,show}.blade.php`, `resources/views/panel/rubrics/partials/form.blade.php`.
- Pruebas: `tests/Feature/VersionedRubricTest.php`, `tests/Feature/RubricActivationConcurrencyTest.php`.
- Planes: `.agent/execplans/flowerflow-phase-02b-evaluation-design.md`, `.agent/execplans/flowerflow-phase-02b-m3-versioned-rubric.md`.
- Documentación: `docs/01-functional-scope.md`, `02-architecture.md`, `03-data-model.md`, `04-security-privacy.md`, `05-ux-ui.md`, `06-roadmap-backlog.md`, `08-testing-qa.md`, `09-risk-register.md`, `10-open-questions.md`, `11-local-development.md`, `11-operations-handoff.md`, `16-project-status-by-module-and-role-2026-08-17.md`, `18-phase-02b-evaluation-decision-package-2026-08-18.md`, `20-phase-02b-m3-implementation-report-2026-08-18.md`, `CODEX_PROJECT_HANDOFF.md`, `adr/0008-phase-02b-evaluation-contract.md`, `product-spec.md` y `requirements-traceability.md`.

## 9. Siguiente puerta

El siguiente prompt canónico está en la sección 21 de `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md` y se limita a **M4 —asignaciones y conflictos**. Generarlo y documentarlo no autoriza ejecutarlo. M5–M10, producción, ganadores y resultados permanecen fuera.
