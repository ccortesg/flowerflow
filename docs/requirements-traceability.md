# Matriz de trazabilidad de requisitos — Flower Flow 2026

## ADR-0017 — banner público y modal de votación

Autorización del propietario: plan de implementación del 2026-09-08, sólo local/test. Evidencia final y pendientes en [informe 34](34-public-voting-integration-2026-09-08.md).

| ID | Requisito | Implementación/prueba | Estado |
|---|---|---|---|
| VOTE-01 | Banner inspirado en el flyer, sin «Próximamente»; CTA visible en móvil | Landing/header/CSS, `PublicLandingTest`, QA 390×844: CTA a 383–436 px | VERIFIED LOCAL/TEST |
| VOTE-02 | Modal único, URLs exactas, carga diferida, enlace externo y foco | Parcial, configuración y `public-voting.js`; Feature + navegador con iframe sintético y Google sin sesión | VERIFIED LOCAL/TEST; sesión Google pendiente |
| VOTE-03 | Google Forms sólo permitido en `frame-src` de landing | `SecurityHeaders`, `SecurityAndFlagsTest` vigente/estricta/rutas ajenas | VERIFIED LOCAL/TEST |
| VOTE-04 | Originales preservados y derivados documentados | `build_voting_assets.php`, dimensiones/SHA-256 reproducidos y QA visual | VERIFIED LOCAL/TEST |
| VOTE-05 | Alcance inicial: mantener información, PDF, categorías, cuentas y flags | Evidencia histórica: informe 34; la adenda de VOTE-07 sustituye la conservación de las secciones del landing | SUPERSEDED parcialmente por VOTE-07; PDF/cuentas/flags conservados |
| VOTE-06 | Conservar acceso Google actual y completar votación en su servicio | Alternativa «Abrir en Google»; UAT con sesión real por propietario | PENDING manual; sin votos automatizados |
| VOTE-07 | H-01: landing centrado en votar, sin categorías/proceso/requisitos/documentos/FAQ renderizados | Landing, introducción y `PublicLandingTest`; adenda aprobada del 2026-09-08 | VERIFIED LOCAL/TEST; gate y límites en informe 35 |
| VOTE-08 | H-01: comunicar dos proyectos ganadores por votos y premio por definir | Sección `#ganadores`, composición tipográfica, pruebas de texto y ausencia de iPad/regla anterior | VERIFIED LOCAL/TEST; gate y límites en informe 35 |
| VOTE-09 | H-01: enlaces públicos coherentes y acceso a documentos/cuentas | Header/footer/login/layout; `PublicLandingTest`, `ParticipantExperienceRedesignTest` y QA de menú/modal | VERIFIED LOCAL/TEST con router QA temporal; limitación de artisan serve en informe 35 |
| VOTE-10 | Reconciliar premio y criterio comunicados con PDF y otras pantallas | Sólo se cambia el landing y sus enlaces en esta adenda; no hay conteo, desempate ni selección automática | PENDING fuera de esta integración |

## ADR-0016 — filtros y exportación de revisiones vigentes

| ID | Requisito | Implementación/prueba | Estado |
|---|---|---|---|
| PANEL-FLT-01 | Asignaciones filtra propuesta/categoría sin alterar enviado+admitido | controller/vista, `SubmissionReferenceFilter`, `PanelAssignmentEvaluationFiltersTest` | VERIFIED LOCAL/TEST |
| PANEL-FLT-02 | Evaluaciones filtra propuesta/estado/categoría y conserva paginación | controller/vista, enum y `PanelAssignmentEvaluationFiltersTest` | VERIFIED LOCAL/TEST |
| EVAL-CUR-01 | Una fila por evaluación propuesta–juez desde `current_revision_id` | `EvaluationExportScope`, job/writer y `EvaluationExportTest` | VERIFIED LOCAL/TEST |
| EVAL-CUR-02 | Estado, total/comentario general y rubros/comentarios persistidos | dos hojas vigentes e inspección OpenSpout/PhpSpreadsheet/XML | VERIFIED LOCAL/TEST |
| EVAL-CUR-03 | Historial compatible, PII excluida y drift fail-closed | default legado, writer histórico intacto y pruebas negativas | VERIFIED LOCAL/TEST |

## ADR-0015 — asignación previa, referencias y exportación de evaluaciones

| ID | Requisito | Implementación/prueba | Estado |
|---|---|---|---|
| JUD-PRE-01 | Asignar individual, bulk y replacement a `pending_setup` coherente | `AdministrativeJudgeEligibility`, Actions/controladores y suites de asignación | VERIFIED LOCAL/TEST |
| JUD-PRE-02 | Mantener acceso bloqueado y omitir correo sin replay | middleware/Policies existentes, dispatchers y matriz antes/después de onboarding | VERIFIED LOCAL/TEST |
| REF-01 | Buscar folio o ULID público parcial, con comodines literales | `SubmissionReferenceFilter`, Propuestas/Admisibilidad y pruebas combinadas | VERIFIED LOCAL/TEST |
| EVAL-EXP-01 | Export privado/asíncrono de todas las evaluaciones y revisiones | `evaluation_exports`, Policy, job, writer, rutas y UI | VERIFIED LOCAL/TEST |
| EVAL-EXP-02 | Fuente inmutable, texto literal y campos sensibles excluidos | validación de snapshot, XML XLSX y pruebas v1/v2/hostiles | VERIFIED LOCAL/TEST |
| EVAL-EXP-03 | Ownership, expiración, purga, diagnóstico y rollback protegido | descarga/purge/diagnose/migración y pruebas negativas | VERIFIED LOCAL/TEST |

## Recuperación de admisibilidad — 2026-08-25

| ID | Requisito | Implementación/prueba | Estado |
|---|---|---|---|
| ADM-REC-01 | Catálogo RBAC reproducible sin depender del seeder productivo | migración correctiva, caché Spatie y `AdmissibilityRecoveryTest` | VERIFIED LOCAL/TEST |
| ADM-REC-02 | Admin/reviewer operan; participant/judge permanecen aislados | permisos exactos, middleware/Policies y matriz negativa | VERIFIED LOCAL/TEST |
| ADM-REC-03 | Acción por propuesta enviada abre expediente sin admitir ni mutar GET | eager-load, botón contextual, prueba de atributos/eventos y UAT Firefox | VERIFIED LOCAL/TEST |
| ADM-REC-04 | Evidenciar enviados sin expediente y recuperar con backfill idempotente | estado “Sin expediente” y comando dry-run/execute existente | VERIFIED LOCAL/TEST; PRODUCCIÓN PENDING |

## Trazabilidad M8A — 2026-08-25

| ID | Requisito | Implementación/prueba | Estado |
|---|---|---|---|
| M8A-UX-01 | Wizard responsive de cuatro pasos y conflicto sólo en la ubicación autorizada | vistas separadas, stepper semántico, CSS acotado y UAT Firefox | VERIFIED LOCAL/TEST |
| M8A-PROJECT-01 | Proyecto del juez sólo desde paquete ciego fijado | `BlindReviewProjectResolver`, hash/schema/ownership y pruebas IDOR/drift | VERIFIED LOCAL/TEST |
| M8A-EXPORT-01 | PDF A4 privado con marcas y sin PII estructurada | DOMPDF endurecido, controller privado, auditoría y render Poppler | VERIFIED LOCAL/TEST |
| M8A-EXPORT-02 | XLSX de tres hojas, marcas reales y texto literal | `JudgeProjectWorkbookWriter`, PhpSpreadsheet, inspección OpenPyXL/render | VERIFIED LOCAL/TEST |
| M8A-SAVE-01 | Autosave cada 30 s mediante Action servidor y sin storage del navegador | `intent=autosave`, JSON autoritativo y prueba/browser UAT | VERIFIED LOCAL/TEST |
| M8A-LOCK-01 | Dos pestañas no sobrescriben; 409 conserva cambios locales | lock optimista M6/M7, JS bloqueado y evidencia DB/browser | VERIFIED LOCAL/TEST |
| M8A-COMPAT-01 | Sin migración ni alteración de M7/M8 | regresión M5–M8, rutas sólo aditivas y ADR-0014 | VERIFIED LOCAL/TEST |
| M8A-LEGAL | Reconciliar “al menos tres jueces” | decisión formal o documento jurídico futuro | OPEN / NO-GO RELEASE |


## Trazabilidad M8 — 2026-08-25

| ID | Requisito | Implementación/prueba | Estado |
|---|---|---|---|
| M8-01 | Eventos ID-only sólo después del commit | eventos/listeners, rollback dirigido | VERIFIED LOCAL |
| M8-02 | Destinatarios exactos sin fallback ni duplicados | `EvaluationCommunicationDispatcher`, pruebas de conflicto/envío | VERIFIED LOCAL |
| M8-03 | Cinco tipos HTML/texto sin contenido sensible | enum, registry, notifications/views, XSS/scans | VERIFIED LOCAL |
| M8-04 | Revalidación, cancelación y recuperación | `CommunicationMessageRegistry`, `DeliverCommunication`, panel existente y UAT | VERIFIED LOCAL |
| M8-05 | Digest único por juez y ventanas exactas | comando/servicio/scheduler, pruebas límite/deriva/conteos | VERIFIED LOCAL |
| M8-06 | Sin recordatorios vencidos, consolidación o resultados | búsqueda de alcance y regresión | VERIFIED LOCAL |
| M8-LEGAL | Reconciliar “al menos tres jueces” | decisión formal o documento jurídico futuro | OPEN / NO-GO RELEASE |

## Trazabilidad M7 — 2026-08-24

| ID | Requisito | Implementación/prueba | Estado |
|---|---|---|---|
| M7-01 | Confirmación, comentario 100–2,000 y cálculo servidor | `SubmitEvaluation`, confirmación juez, `EvaluationSubmissionReopeningTest` | IMPLEMENTED LOCAL |
| M7-02 | Revisión enviada inmutable | enums separados, checks, Actions append-only, prueba de fuente intacta | IMPLEMENTED LOCAL |
| M7-03 | Reaperturas sucesivas append-only | `evaluation_reopenings`, `ReopenEvaluation`, revisiones 2/3 | IMPLEMENTED LOCAL |
| M7-04 | Actor admin real y juez sujeto estable | campos de revisión/reapertura, panel/aviso juez, pruebas | IMPLEMENTED LOCAL |
| M7-05 | Ventanas exactas y lock/409 | `EvaluationWindow`, locks, pruebas límite/concurrencia | IMPLEMENTED LOCAL |
| M7-06 | Sin M8+ | ausencia de `CommunicationType`/delivery de evaluación y prueba | VERIFIED LOCAL |
| M7-LEGAL | Reconciliar “al menos tres jueces” | decisión formal o documento jurídico futuro | OPEN / NO-GO RELEASE |

## Trazabilidad M6A — 2026-08-24

| ID | Requisito | Implementación/prueba | Estado |
|---|---|---|---|
| M6A-SETUP-001 | Enlace inicial configura contraseña y verifica correo una vez | `judge_setup_links`, Actions/validator/rutas, `JudgeSetupLinkTest`, `JudgeSetupLinkConcurrencyTest` | VERIFIED local/test |
| M6A-RUBRIC-001 | v1 histórica y v2 legal activa de cuatro criterios | migración M6A, `EvaluationRubricContract`, seeder, `VersionedRubricTest` | VERIFIED local/test |
| M6A-ASGN-001 | Selección manual, ilimitada, cero mínimos, sin automatismo | Actions/controller/views, `JudgeAssignmentsAndConflictsTest`, concurrencia | VERIFIED local/test |
| M6A-PKG-001 | Paquete ciego sin cobertura mínima | Actions M5 generalizadas y prueba dirigida | VERIFIED local/test |
| M6A-MAIL-001 | Alta/asignación configurables y delivery sin PII | flags, outbox, templates, pruebas onboarding/asignaciones/ledger | VERIFIED local/test |
| M6A-UX-001 | Shell/dashboard/listado/detalle/cuenta accesible y responsive | vistas juez, pruebas RBAC y UAT Firefox 1440×900, 1024×768 y 390×844 | VERIFIED local/test; zoom nativo manual recomendado antes de release |
| M6A-LEGAL-001 | Visibilizar contradicción “al menos tres jueces” | ExecPlan, ADR-0010, riesgos/handoff | RELEASE BLOCKER |

> **Adenda de exportación de contactos — 2026-08-24:** extensión local/test del ADR 0007 sobre baseline `9df0828a41733cd0b35128f71698fee6f3cfd1ab`; no añade migraciones, permisos, dependencias, workers ni acceso productivo.

> **Adenda de trazabilidad de comunicaciones — 2026-08-23:** milestone independiente local/test con ADR-0009, sin producción, SMTP real, M7/M8, campañas o resultados.

> **Adenda de trazabilidad del panel — 2026-08-22:** este milestone local parte de `bffc7d7f4738e0937b276ea9d5d22e3744afe65c`; no altera M1–M6, M7–M10, PDFs jurídicos ni producción.

| ID | Requisito del panel | Implementación/evidencia | Estado |
|---|---|---|---|
| PANEL-ACT-001 | Columna final Acciones y botones sólo por permiso/estado | `panel/submissions/index`, `SubmissionPolicy`, flags y `PanelSubmissionContractTest` | VERIFIED local/test |
| PANEL-REM-001 | Individual y masivo a todos los drafts, sólo propietario, cooldown | tablas/actions/job/controlador/preview y `SubmissionReminderTest` | VERIFIED local/test |
| PANEL-REM-002 | Correo HTML/texto con ambas marcas, CTA temporal y contenido escapado | `SubmissionDraftReminder`, layout dual y pruebas de render/XSS | VERIFIED local/test |
| PANEL-REM-003 | GET firmado puro; POST sin login y sin archivo exige firma/CSRF/legales/plazo | rutas/controlador/eligibilidad/finalización modo `signed_reminder` y pruebas negativas | VERIFIED local/test |
| PANEL-ADM-001 | Envío administrativo con mínimo, password, confirmación/razón y cero aceptaciones ajenas | request/policy/action/snapshot/event/audit/mail y `AdministrativeSubmissionFinalizationTest` | VERIFIED local/test |
| PANEL-EXP-001 | Export sigue asíncrono/privado; diagnóstico read-only y alerta por espera | `DiagnoseSubmissionExports`, índice y `SubmissionExportTest` | VERIFIED local/test; entorno observado POR_CONFIRMAR |
| PANEL-EXP-002 | Exportar una fila por propuesta enviada con contacto y proyecto inmutables | `SubmissionExportKind`, `SubmissionContactsWorkbookWriter`, rutas/UI y `SubmissionExportTest` | VERIFIED local/test dirigido; gate completo en ExecPlan |
| PANEL-OPS-001 | Flags default-off y rollback que preserva evidencia | config/env/migración fail-closed/ExecPlan | VERIFIED local/test |

> **Contrato histórico M6 — 2026-08-18:** M4A conservaba `4+2`; M6A sustituyó la cobertura fija, M7 implementó envío/reapertura y M8 comunicaciones/digest. Sólo M9–M10 permanecen no implementados/no autorizados.

## Trazabilidad de reconciliación jurídica v1.1 — 2026-08-17

| ID | Requisito | Implementación/evidencia | Estado |
|---|---|---|---|
| LEG-V11-001 | Validar seis PDF por existencia, tipo, páginas, tamaño y SHA-256 | inventario y revisión de 28 páginas en `docs/17-legal-v1-1-reconciliation-2026-08-17.md` | VERIFIED local |
| LEG-V11-002 | Comparar v1.0/v1.1 con evidencia de página/sección | matriz jurídica del mismo documento; extracción y revisión visual | VERIFIED documental |
| LEG-V11-003 | Publicar v1.1 sin borrar v1.0 | config, seeder y migración `2026_08_17_220000_publish_legal_documents_v1_1.php`; base aislada con seis versiones y una activa por tipo | VERIFIED local |
| LEG-V11-004 | Conservar aceptaciones históricas y registrar la versión real | `CreateNewUser`, `ProfileController`, `FinalizeSubmission`, FKs existentes y `LegalDocumentsV11Test`; UAT autenticada con v1.1 | VERIFIED local |
| LEG-V11-005 | Enlaces v1.1 coherentes por superficie/rol | landing, documentos, registro, login, perfil, envío, footers y panel; UAT 360/768/1440 | VERIFIED local |
| LEG-V11-006 | No inferir contradicciones de categorías | cantidades 4/4 verificadas; superposición de accesibilidad aceptada por el propietario sin recategorización | VERIFIED / OWNER ACCEPTED |
| LEG-V11-007 | Política de reaceptación | cuentas v1.0 continúan sin bloqueo/backfill; nuevas aceptaciones usan v1.1 | VERIFIED / OWNER DECISION |
| LEG-V11-008 | Preservar integridad v1.0 | propietario designa el archivo físico `3bcf31…`; hash histórico `42bd5e…` y aceptaciones permanecen intactos | VERIFIED / HISTORICAL DISCREPANCY ACCEPTED |
| SEC-503-001 | 503 accesible y compatible con CSP | vista propia sin estilos inline, prueba CSP estricta, pre-render de mantenimiento y revisión responsive | VERIFIED local |
| OPS-TOPOLOGY-001 | Runbook coherente con la topología productiva real | ADR 0002, runbooks 07/15, handoff y prompt registran checkout directo `/var/www/flowerflow`, sin `releases/current/shared` | OWNER CONFIRMED / DOCUMENTED |
| OPS-DEPLOY-001 | Registrar instalación informada sin inventar evidencia | diagnóstico/product spec/handoff separan `OWNER_CONFIRMED_DEPLOYED` de `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR` | OWNER CONFIRMED / TECHNICAL EVIDENCE PENDING |

## Trazabilidad de la auditoría integral — 2026-08-17

| ID | Requisito | Implementación/evidencia | Estado |
|---|---|---|---|
| AUDIT-001 | Diagnóstico por módulo y funcionalidad con porcentajes reproducibles | `docs/16-project-status-by-module-and-role-2026-08-17.md`, rúbrica de cinco dimensiones y pesos del plan maestro | VERIFIED documental |
| AUDIT-002 | Diagnóstico por rol y acceso efectivo | roles/permisos del seeder, rutas, Policies, vistas y pruebas negativas contrastadas con la matriz planificada | VERIFIED documental/local |
| AUDIT-003 | Separar código, runtime local y producción | estado de flags/config, migraciones de testing, `OWNER_CONFIRMED_DEPLOYED` y `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR` separados | VERIFIED documental; producción no verificada independientemente |
| AUDIT-004 | Gate de código vigente | suite completa M1–M4A, Pint, Composer, JSON y build; conteos en informe M4A; Quill bajo documentado | VERIFIED local |
| AUDIT-005 | Prompt M6 exacto y acotado | sección 21 sustituida por el contrato ejecutado; preserva M4A/M5 | M6 GO LOCAL/TEST |

## Trazabilidad del diseño Fase 02B — 2026-08-18

| ID | Requisito de diseño | Evidencia | Estado |
|---|---|---|---|
| F2B-DES-001 | Baseline Git y código real antes de diseñar | ExecPlan 02B e inventario en paquete sección 4 | VERIFIED documental/local read-only |
| F2B-DES-002 | Distinguir despliegue informado de evidencia técnica | diagnóstico, product spec y handoff con ambos estados | `OWNER_CONFIRMED_DEPLOYED` / SHA `POR_CONFIRMAR` |
| F2B-DES-003 | Identidad/alta directa/perfil/gate de juez | M1: enum/Action/middleware/migración/rutas/shell; M2: `judge_profiles`, acciones, Policy/Requests, admin UI y middleware activo | M1/M2 VERIFIED LOCAL |
| F2B-DES-004 | Asignación/reasignación/cobertura/plazo | M4A verifica `4+2`, capacidad nula, sustitutos sin iniciales y selección manual | OWNER FINAL / M4A VERIFIED LOCAL |
| F2B-DES-005 | Matriz ciega campo por campo y anonimización | builder/payload/inventario/Policies M5; paquete sección 8; decisiones 006–008 | M5 VERIFIED LOCAL / SEMANTIC IDENTITY RISK ACCEPTED |
| F2B-DES-006 | Rúbrica versionada y contrato exacto | `rubric_versions`, `rubric_criteria`, contrato/Actions/Policy/UI y pruebas M3; paquete sección 9 | M3 VERIFIED LOCAL |
| F2B-DES-007 | Estados, envío inmutable y reapertura versionada | `SubmitEvaluation`, `ReopenEvaluation`, migración M7, ADR-0011 y pruebas dirigidas | M7 VERIFIED LOCAL |
| F2B-DES-008 | Cálculo sólo servidor, consolidación/faltantes/empate | cálculo draft M6 implementado; consolidación/empate siguen en paquete sección 11 | M6 VERIFIED / CONSOLIDATION NOT IMPLEMENTED |
| F2B-DES-009 | Matriz negativa, amenazas y auditoría | suites M1–M5; M5 añade canarios, IDOR, drift, neutralidad y concurrencia | M1–M5 VERIFIED LOCAL |
| F2B-DES-010 | UX accesible mínima | flujos juez/admin y bitácora/correos M8 en tres viewports | M1–M8 LOCAL; UX M9+ PENDING |
| F2B-DES-011 | Notificaciones idempotentes y operación | M2: configuración de acceso, verificación y estado/recovery con HTML+texto y dispatcher resiliente; paquete sección 14 para eventos futuros | M2 SUBSET VERIFIED / M3+ PENDING |
| F2B-DES-012 | Compatibilidad con más de 50 propuestas | migración M2 aditiva, perfil primary/substitute, sin backfill/asignaciones; upgrade/rollback/forward preservó usuario sintético | M2 VERIFIED LOCAL / CAPACITY DECISION CLOSED |
| F2B-DES-013 | Diez milestones y corrección | paquete sección 18 + ExecPlans M4A/M5/M6/M6A/M7/M8 | M8 LOCAL; M9–M10 NOT AUTHORIZED |
| F2B-DES-014 | Bloque de 21 respuestas y prompt ejecutado | paquete secciones 20–21; contrato M6 corregido | OWNER FINAL / M6 VERIFIED |
| F2B-DES-015 | Resolver incompatibilidad de cobertura/capacidad/reemplazo | ADR-0008; D-034/D-035; R76; ExecPlan/informe M4A | `P2B-BLOCK-001 RESOLVED LOCAL` |
| F2B-DES-016 | Contrato de QA por milestone | suites M1–M8, gates y UAT local | M8 LOCAL / M9+ PENDING |

## Trazabilidad de implementación Fase 02B M3 — 2026-08-18

| ID | Requisito M3 | Implementación/evidencia | Estado |
|---|---|---|---|
| F2B-M3-001 | Versión global exacta por competencia | `RubricVersion`, `RubricCriterion`, `EvaluationRubricContract` y migración `2026_08_18_140000_create_rubric_versions_and_permissions.php` | VERIFIED local |
| F2B-M3-002 | Cinco códigos/orden/pesos y contrato decimal exactos | validación de servicio/Requests, checks MySQL y `VersionedRubricTest`; descripciones nulas `POR_CONFIRMAR` | VERIFIED local |
| F2B-M3-003 | Ciclo draft→active→superseded e inmutabilidad | Actions create/update/activate, modelos guarded, UI y auditoría | VERIFIED local |
| F2B-M3-004 | Máximo una activa bajo concurrencia | lock de competencia/versiones, `active_slot`, unique/check y dos procesos MySQL en `RubricActivationConcurrencyTest` | VERIFIED local |
| F2B-M3-005 | Permisos admin separados y matriz negativa | permisos `view/manage evaluation rubrics`, Policy/rutas y pruebas participant/reviewer/judge/sin rol/multirol | VERIFIED local |
| F2B-M3-006 | Provisionamiento v1 idempotente sin datos funcionales | `ProvisionCanonicalRubricDraft`, seeder local/testing, divergencia fail-closed | VERIFIED local |
| F2B-M3-007 | Compatibilidad/rollback | forward/rollback/forward; draft reversible, active/superseded protegidas; cero asignaciones/evaluaciones | VERIFIED local |
| F2B-M3-008 | QA y UAT | M3 8/132; M1–M3 24/399; suite 133/1,448; Firefox responsive/teclado/reflow/consola/403/404/inmutabilidad | VERIFIED local |
| F2B-M3-009 | Alcance y siguiente puerta | informe M3 y paquete sección 21; M4 limitado a asignaciones/conflictos, M5 excluido | GO LOCAL/TEST / M4 NOT AUTHORIZED |

## Trazabilidad de implementación Fase 02B M4 — 2026-08-18

| ID | Requisito M4 | Implementación/evidencia | Estado |
|---|---|---|---|
| F2B-M4-001 | Elegibilidad y cobertura manual exacta | `AssignmentEligibility`, `ActivateSubmissionCoverage`, cuatro `initial`, versión/rúbrica/plazo fijados | VERIFIED local |
| F2B-M4-002 | Modelo append-only y restricciones | migración `2026_08_18_150000_create_judge_assignments_and_conflicts.php`, enums, modelos guarded/checks/FKs/índices | VERIFIED local |
| F2B-M4-003 | Composición y capacidad histórica | cuatro primary activos sin límite, un substitute activo, máximo diez replacements activos/conflictuados | VERIFIED HISTORICAL / SUPERSEDED BY M4A |
| F2B-M4-004 | Conflicto propio y resolución admin | catálogo exacto, explicación condicional, original voided, reemplazo ligado, contraseña/razón y auditoría | VERIFIED local |
| F2B-M4-005 | Autorización y minimización | permisos separados, Policies/Requests, proyección juez sin contenido M5 y canarios de ausencia | VERIFIED local |
| F2B-M4-006 | Idempotencia/concurrencia | repetición converge; dos procesos MySQL dejan cuatro asignaciones; cobertura divergente falla sin parcial | VERIFIED local |
| F2B-M4-007 | Reversibilidad/compatibilidad | rollback/forward dirigido; seeder no crea filas M4; propuestas/snapshots/admisibilidad/rúbrica no se reescriben | VERIFIED local |
| F2B-M4-008 | QA/UAT histórica | suites M4; Firefox 3 viewports, vacío, 0→4→3→4 y 10/11 | VERIFIED HISTORICAL 1×10 |
| F2B-M4-009 | Alcance y siguiente puerta | informe M4 histórico; ExecPlan/informe M4A; prompt M5 actualizado | M4A GO / M5 SEPARATE |

## Trazabilidad de corrección Fase 02B M4A — 2026-08-18

| ID | Requisito M4A | Implementación/evidencia | Estado |
|---|---|---|---|
| F2B-M4A-001 | Seis jueces operativos | exactamente cuatro primary activos y dos substitute activos; históricos/suspendidos no cuentan | VERIFIED LOCAL |
| F2B-M4A-002 | Capacidad ilimitada | `primary=NULL` y `substitute=NULL`; migración aditiva desde perfiles existentes `10→NULL`; check MySQL | VERIFIED LOCAL |
| F2B-M4A-003 | Sin carga inicial a sustitutos | cobertura inicial conserva exactamente cuatro primary y cero substitute | VERIFIED LOCAL |
| F2B-M4A-004 | Selección manual | admin elige por ULID uno de dos sustitutos operativos; no hay balanceo automático | VERIFIED LOCAL/UI |
| F2B-M4A-005 | Sin límite y concurrencia | 31 replacements activos para un sustituto sin rechazo; segundo seleccionable; carreras sin duplicados | VERIFIED LOCAL |
| F2B-M4A-006 | Preservar historia | original voided, replacement append-only, misma versión/rúbrica/plazo y auditoría del sustituto seleccionado | VERIFIED LOCAL |
| F2B-M4A-007 | Puerta M5 | prompt sección 21 verifica capacidad `NULL`, `4+2`, selección manual y >30 | DOCUMENTED / M4A GO |
| F2B-M4A-008 | Contingencia de replacement | conflicto/suspensión del sustituto asignado deja cobertura incompleta; no crea cadena automática sin decisión expresa | POR_CONFIRMAR / FAIL-CLOSED |

## Trazabilidad de implementación Fase 02B M5 — 2026-08-18

| ID | Requisito M5 | Implementación/evidencia | Estado |
|---|---|---|---|
| F2B-M5-001 | Paquete único/versionado por snapshot | `blind_review_packages`, `submission_version_id` único, schema v1, payload/hash y estados respaldados | VERIFIED local |
| F2B-M5-002 | Allowlist sin snapshot crudo | `BlindReviewPackageBuilder`: category, campos sustantivos y external links exactos; canarios fuera de modelo/HTML/audit | VERIFIED local |
| F2B-M5-003 | Inventario evaluable neutro | `blind_review_package_files`, comparación exacta contra snapshot/live, etiquetas deterministas y exclusión de no-snapshot | VERIFIED local |
| F2B-M5-004 | Descarga privada e integridad | ruta assignment+file, Policy ownership/status/versión, `nosniff`, nombre neutro, tamaño/SHA/MIME/firma | VERIFIED local |
| F2B-M5-005 | Activación explícita e inmutable | Actions/Requests admin, razón/password confirmation, locks, idempotencia, guarded/checks y auditoría redactada | VERIFIED local |
| F2B-M5-006 | Replacement comparte paquete | misma `submission_version_id`; original voided pierde acceso y sustituto elegido consume la misma fila/binarios | VERIFIED local |
| F2B-M5-007 | Seguridad y riesgo semántico | matriz negativa, XSS/SSRF sin fetch, canarios PII, aviso “anonimización estructural” y riesgo owner accepted | VERIFIED local / RISK ACCEPTED |
| F2B-M5-008 | Concurrencia/rollback/compatibilidad | dos activaciones MySQL→una activa/un audit; forward/rollback/forward preserva usuario sintético y M4 | VERIFIED local |
| F2B-M5-009 | QA y UAT | M5 8/119; M1–M5 dirigido 41/654; suite 150/1,703; Firefox tres viewports/teclado/reflow/consola/IDOR | VERIFIED local |
| F2B-M5-010 | Alcance y puerta histórica | ExecPlan/informe M5; el prompt posterior limitó M6 a borrador/cálculo | M5 GO / M6 EXECUTED LATER |

## Trazabilidad de implementación Fase 02B M6 — 2026-08-18

| ID | Requisito M6 | Implementación/evidencia | Estado |
|---|---|---|---|
| F2B-M6-001 | GET sin efectos y apertura explícita | GET de detalle sólo carga; POST idempotente crea evaluación/revisión 1/cinco scores | VERIFIED local/UAT |
| F2B-M6-002 | Rúbrica/package/assignment fijados | `EnsureEvaluationDraftContext` revalida IDs server-side, contrato exacto y drift fail-closed | VERIFIED local |
| F2B-M6-003 | Cálculo decimal servidor | BCMath, 4 decimales, total NULL incompleto y display HALF_UP; vectores exactos | VERIFIED local |
| F2B-M6-004 | Payload cerrado | Request+Action rechazan extras, IDs, códigos duplicados/extraños, notación hostil y step/rango | VERIFIED local |
| F2B-M6-005 | Concurrencia optimista | `lock_version`, locks transaccionales, incremento único y HTTP 409 sin overwrite | VERIFIED local/UAT |
| F2B-M6-006 | Ownership y matriz negativa | permiso exclusivo judge, Policy por assignment, exact-role/verified/active/flag/package/plazo | VERIFIED local |
| F2B-M6-007 | Conflicto y replacement | conflicto conserva filas y revoca acceso; replacement abre agregado independiente sin copia | VERIFIED local/UAT |
| F2B-M6-008 | Auditoría redactada | open/save/stale/rechazos con metadata técnica allowlist; scan sin contenido/PII | VERIFIED local |
| F2B-M6-009 | Migración y rollback | aditiva, sin backfill; forward/rollback/forward y negativa con evidencia | VERIFIED local |
| F2B-M6-010 | QA, UAT y alcance | M6 13/228; M1–M6 54/888; suite 163/1,937; Firefox tres viewports; M7–M10 ausentes | GO LOCAL/TEST |

## Trazabilidad paginación y exportación privada — 2026-08-11

| ID | Requisito aprobado | Implementación | Evidencia prevista/actual | Estado |
|---|---|---|---|---|
| EXP-001 | Corregir iconos Anterior/Siguiente sobredimensionados y revisar otras pantallas | `Paginator::useBootstrapFive()` global; cubre propuestas y admisibilidad | `PanelPaginationRenderingTest`, build y UAT browser con 31 filas/2 páginas | VERIFIED local |
| EXP-002 | Exportar todas las propuestas borrador y enviadas | consulta server-side por bloques; `withdrawn` excluida | `SubmissionExportTest` con ambos estados y negativo retirado | VERIFIED local |
| EXP-003 | Incluir contacto y toda la información funcional del proyecto | hojas Propuestas, Contactos, Integrantes, Archivos y Enlaces externos | lectura independiente de cinco hojas, conteos y valores | VERIFIED local |
| EXP-004 | Preservar la versión enviada | snapshot inmutable para `submitted`; estado actual para `draft` | título vivo distinto del título exportado de snapshot | VERIFIED local |
| EXP-005 | Enlaces de imágenes/documentos descargables | fórmula `HYPERLINK` generada con ruta estable autenticada | enlace a cada `SubmissionFile`; archivo cruzado y permiso revocado rechazados | VERIFIED local |
| EXP-006 | No permitir descarga anónima | auth, permiso separado, Policy, ownership y confirmación reciente de contraseña | anónimo, viewer, reviewer y otro admin negativos | VERIFIED local |
| EXP-007 | Minimizar PII y bloquear fórmulas hostiles | excluye fecha de nacimiento, residencia y datos técnicos; strings literales | celda `=2+2` serializada como `inlineStr`; ausencia de fecha de nacimiento | VERIFIED local |
| EXP-008 | Archivo temporal privado y auditable | disk `serve=false`, job cifrado post-commit, 24 h, purga horaria y eventos redactados | generación/descarga/expiración/auditoría y dry-run | VERIFIED local |

## Trazabilidad Hermosillo sin Barreras — 2026-08-06

| ID | Requisito aprobado | Implementación | Evidencia prevista/actual | Estado |
|---|---|---|---|---|
| HSB-001 | Cuarta categoría exacta, activa, orden 4 y `public_id` estable | migración de datos + `FlowerFlowSeeder` | `HermosilloSinBarrerasCategoryTest` idempotencia, descripciones y orden | VERIFIED local |
| HSB-002 | Máximo cuatro y una por categoría, sin quinta concurrente | config, unique existente y bloqueo de cuenta en `SubmissionController::store` | `SubmissionFlowTest` + `SubmissionCreationConcurrencyTest` con dos procesos MySQL | VERIFIED local |
| HSB-003 | Landing sólo activa, fallback de cuatro, iconos por slug y 4/2/1 | `LandingController`, Blade, CSS, catálogo/generador Iconify | `PublicLandingTest`, `icons:check`, build y QA 360/768/1440 sin overflow ni consola | VERIFIED local |
| HSB-004 | Superficies participante y snapshot/correo | vistas dinámicas, config iconos y mail de acuse | dashboard, crear/editar/listar/ver/enviar e inmutabilidad en `HermosilloSinBarrerasCategoryTest` | VERIFIED local automatizado |
| HSB-005 | Dashboard/filtro/listado/detalle/descarga admin | scope de dashboard y contratos existentes | cero/uno, filtro slug, detalle y descarga en `HermosilloSinBarrerasCategoryTest` | VERIFIED local automatizado |
| HSB-006 | Conservar evidencia histórica y reconciliar nueva Mecánica | v1.0/v1.1 no se sobrescriben; `legal-change-log.md`, risk register y matriz jurídica | v1.1 confirma cuatro categorías; propietario designó binario v1.0 y conserva discrepancia de hash | VERIFIED / HISTORICAL DISCREPANCY ACCEPTED |
| HSB-007 | Despliegue reversible según existencia de datos | migración aditiva con `down` no destructivo y ExecPlan | UAT, backup y smoke son puertas externas | READY FOR OWNER REVIEW |

## Trazabilidad del plan de reducción de riesgos — 2026-08-06

| ID | Requisito aprobado | Implementación | Evidencia actual | Estado |
|---|---|---|---|---|
| RR-001 | Partir del SHA productivo sin Fase 02B | rama `codex/f01-f02a-risk-reduction` desde `baff789…` | historial Git y checkpoint/bundle externo | VERIFIED local |
| RR-002 | Aislar MySQL destructivo por base y cuenta | `phpunit.xml`, `EnsuresDisposableDatabase`, `.env.testing` ignorado | 8 pruebas de guard y suite completa verdes en la base/cuenta exclusivas | VERIFIED local |
| RR-003 | Remediar advisories PHP aisladamente | `composer.lock` con Guzzle 7.15.3 | audit Composer sin advisories; diff de tres paquetes | VERIFIED local |
| RR-004 | Atomicidad de archivos | `SubmissionFileStore`, transacciones y `DB::afterCommit` | rollback múltiple, persistencia fallida y orphan post-commit verdes | VERIFIED local |
| RR-005 | Auditor de storage no destructivo | `flowerflow:storage-audit --disk --json` | missing/orphan determinista sin mutación, verde | VERIFIED local |
| RR-006 | Transiciones y throttle administrativo | workflow y `panel-mutations` | estados inválidos, idempotencia y 10/min por actor/ruta verdes | VERIFIED local |
| RR-007 | Contratos admin/reviewer/IDOR | Policies y `PanelSubmissionContractTest` | descarga positiva y rechazos cruzados/directos verdes | VERIFIED local |
| RR-008 | 2FA opcional completo | `/panel/cuenta/2fa/*`, UI y Fortify trait | flujo TOTP/recovery/desactivación, throttle y desafío browser verdes | VERIFIED local; enforcement pendiente |
| RR-009 | Reducir grafo frontend | dos entrypoints, poda y generador de iconos | build/manifest/audit verdes | VERIFIED local |
| RR-010 | CSP con nonce y HSTS gradual | `SecurityHeaders`, flags de config | tests de promoción/HTTPS y navegador público con nonces/consola limpia | VERIFIED local |
| RR-011 | Preservar nueve flujos productivos | QA pública y suites de regresión | 90 pruebas/800 aserciones y público comparado en 3 viewports | VERIFIED automatizado/público; browser autenticado pendiente |
| RR-012 | Releases post-cierre, inmutables y reversibles | `docs/15-risk-reduction-release-runbook.md` | revisión documental local | READY FOR OWNER REVIEW |

**Fecha de corte:** 2026-07-16
**Estado histórico:** baseline de planificación. La tabla Fase 01 siguiente registra implementación actual.
**Convenciones:** `DECISION` confirmado; `ASSUMPTION` supuesto de trabajo; `PENDING` requiere información/aprobación.

## Trazabilidad Fase 01 aprobada

| ID | Requisito | Implementación/evidencia | Prueba/gate | Estado |
|---|---|---|---|---|
| F1-001 | Reconciliar docs/ExecPlan sin borrar historia | `AGENTS.md`, ExecPlan, docs 00–14, ADR 0005/0006 | Revisión documental | IMPLEMENTED |
| F1-002 | Base reproducible Laravel/MySQL/Yarn | `composer.lock`, `yarn.lock`, `.env.example`, docs 11 | Composer validate/audit, Yarn frozen/build y migración/seed MySQL | VERIFIED local |
| F1-003 | Activos autorizados/hashes | `formatos/`, `imagen/`, script y `public/documentos/2026` | SHA-256 exacto y revisión 14 páginas | VERIFIED |
| F1-004 | Landing V2 con contenido crítico, CTA por estado y responsive encapsulado | `public/landing.blade.php`, parciales `public/partials/landing-*`, `resources/css/pages/public-landing.css`, flags y derivados locales | `PublicLandingTest`, build y browser desktop/móvil registrados en `docs/design-qa.md`; regresión posterior incluida en la suite vigente | VERIFIED local; QA visual histórica aceptada |
| F1-005 | Auth, correo verificado, reset y 2FA | Fortify 1.37.2, vistas propias y página `/correo-verificado` | rutas, login/logout browser, signed verify y mail fake | VERIFIED local; UAT correo pendiente |
| F1-006 | RBAC/panel sólo admin | Permission 8.3.0, middleware y Policy | `PanelAuthorizationTest`, IDOR y browser admin | VERIFIED local |
| F1-007 | Registro/perfil 18+/residencia/E.164/WhatsApp | `CreateNewUser`, profile model/request/controller/view y teléfono México `+52` | `RegistrationProfileFlowTest`, `ProfileEligibilityTest` | VERIFIED local |
| F1-008 | 4 categorías exactas/cierre Hermosillo | migración de datos, `FlowerFlowSeeder`, config/middleware | seed/idempotencia, frontera y regresión UTC/Hermosillo | VERIFIED local |
| F1-009 | Equipo ≤5, una/categoría, máximo 4 | request, unique, bloqueo transaccional y controller | Feature positivo/negativo/concurrente | VERIFIED local |
| F1-010 | Rich text seguro | Quill + Delta/HTML/texto + Symfony sanitizer | Unit XSS + Feature stored XSS + browser | VERIFIED local |
| F1-011 | Upload privado/10 MiB/formatos/hash | inspector/store/Policy, disk `serve=false` | MIME/signature/quota/IDOR + PDF browser | VERIFIED local; antivirus pendiente |
| F1-012 | Links allowlist sin SSRF | Form Request host exacto, no cliente HTTP | hosts internos/prohibidos | VERIFIED local |
| F1-013 | Legales versionados/consentimientos separados | tablas, config/seeder/migración v1.1, registro/perfil/envío y `legal-change-log.md` | hashes, una activa por tipo, rollback y acceptance rows en `LegalDocumentsV11Test` | IMPLEMENTED local; continuidad v1.0 resuelta por el propietario sin backfill |
| F1-014 | Envío transaccional/idempotente | `FinalizeSubmission`, lock, snapshot, folio, event | doble POST, una versión/mail + envío browser | VERIFIED local |
| F1-015 | Panel mínimo sin evaluación | counts/distribución/lista/detalle/cuenta | admin/participant/browser desktop/móvil | VERIFIED local |
| F1-016 | Correo post-commit, sin adjuntos | queued Mailable y config central | Mail fake | VERIFIED local; SMTP pendiente |
| F1-017 | AWS sólo documentación | docs 07, ADR 0002; cero acceso EC2 | revisión de diff/operación | VERIFIED |
| F1-018 | Flags productivos seguros | config/env: registro/recepción/resultados false | flags test + config review | VERIFIED |
| F1-019 | Acceso, perfil y propuestas con sistema visual participante responsive y datos reales | `layouts/flowerflow`, vistas `auth/login`, `participant/profile`, `submissions/index`, navegación compartida y CSS/JS encapsulados | `ParticipantExperienceRedesignTest`, suite/build y UAT manual de teclado/móvil registrada en `design-qa.md` | VERIFIED local; QA visual aceptado por usuario |
| F1-020 | Nueva propuesta como asistente real de cuatro pasos, persistencia por sección y revisión final | `SubmissionController`, `SubmissionDraftRequest`, vistas `submissions/form`/`show`, stepper, CSS/JS progresivo y config central | `SubmissionWizardTest`, `SubmissionFlowTest`, suite/build, permisos negativos y UAT manual registrada en `design-qa.md` | VERIFIED local; QA visual aceptado por usuario |
| F1-021 | Inicio participante dinámico y menú global reducido sin perder contenido público | `DashboardController`, `participant/dashboard`, `participant-navigation`, layout y CSS participante | `ParticipantExperienceRedesignTest`: conteos/aislamiento, perfil, convocatoria/zona, CTA, preservación pública/admin y UAT manual | VERIFIED local; QA visual aceptado por usuario |

## Trazabilidad Fase 02A autorizada

| ID | Fuente/regla | Implementación | Prueba/evidencia | Estado |
|---|---|---|---|---|
| F2A-001 | Expediente separado y snapshot inmutable | migración, enums, `EligibilityReview`, `EnsureEligibilityReview` | creación/doble POST/snapshot inmutable | IMPLEMENTED local |
| F2A-002 | Backfill sin migración de datos | `flowerflow:admissibility-backfill` | dry-run y dos ejecuciones idempotentes | VERIFIED local |
| F2A-003 | Estados/transiciones motivadas | `EligibilityReviewWorkflow`, eventos y transacciones | válidas, inválidas y doble resolución | VERIFIED local |
| F2A-004 | Aclaración formal sin editar proyecto | requests/responses/files y sección participante | 2,000 caracteres, append-only, bloqueo abierto, fecha opcional | VERIFIED local |
| F2A-005 | Residencia por persona/equipo | requests separados por user/team_member | representante e integrante aislados | VERIFIED local |
| F2A-006 | Archivo privado seguro | inspector/store, discos `residency`/`clarifications` | firma/MIME/nombre/PDF cifrado/activo/tamaño/cuota | VERIFIED local |
| F2A-007 | Equivalente manual; sin antigüedad inventada | enum/tipo y precondición de resolución | equivalente exige justificación; archivo antiguo no se rechaza solo | VERIFIED local |
| F2A-008 | Admisión con aclaraciones/residencia resueltas | workflow transaccional | bloqueos y resolución humana posterior a rechazo | VERIFIED local |
| F2A-009 | RBAC/Policies y juez excluido | permisos, middleware, cinco Policies | owner/otro/reviewer/admin/sin rol/judge/descarga limitada | VERIFIED local |
| F2A-010 | Auditoría sensible | eventos inmutables + `audit_logs` con hashes | carga/vista/descarga/validación/rechazo/decisión | IMPLEMENTED local |
| F2A-011 | Correo resiliente en español | `AdmissibilityUpdate`, dispatcher y plantillas dual-brand | cinco variantes + falla sintética sin 500/rollback | VERIFIED local |
| F2A-012 | UTC/Hermosillo | casts, conversiones y UI | vencimiento Hermosillo→UTC y render de resolución | VERIFIED local |
| F2A-013 | Listado server-side | panel/admisibilidad, filtros/eager loading/paginación | filtros, 25 por página y lazy loading bloqueado | VERIFIED local |
| F2A-014 | Feature flag seguro | env/config/middleware/menús | apagado 404/oculto y encendido visible | VERIFIED local |
| F2A-015 | Retención sin borrado prematuro | campos y `flowerflow:residency-retention-report` | fecha +90 días, dry-run y archivo preservado | VERIFIED local; integración ganadores PENDING |
| F2A-016 | UI responsive/accesible nueva | Blade + `admissibility-review.css` | Feature/views y QA real en `docs/design-qa-phase-02-admissibility.md` | VERIFIED local en escritorio, tableta, móvil, teclado y zoom 200% |

## Cobertura y limitación

**PENDING:** el input comienza truncado y faltan la introducción y los módulos 1–6. Los requisitos de esos módulos fueron reconstruidos para poder planificar y se identifican como `ASSUMPTION` cuando el detalle no está confirmado. La matriz debe actualizarse al recibir la fuente completa; ninguna fila reconstruida debe considerarse evidencia de aprobación.

## Leyenda

### Fases

- **MVP:** imprescindible para recibir, revisar y evaluar con seguridad.
- **MVP-R:** MVP recortable si no bloquea la operación central.
- **F2:** fase 2.
- **OUT:** fuera de alcance.
- **PLAN:** requisito de planificación/operación previo a implementar o desplegar.

### Niveles de prueba planeados

- **U:** unit.
- **F:** feature/integración Laravel.
- **B:** navegador/E2E.
- **A11Y:** accesibilidad manual/automatizada.
- **SEC:** revisión de seguridad/autorización.
- **OPS:** runbook, infraestructura o prueba operativa.
- **UAT:** aceptación por usuario/producto.

## Trazabilidad funcional

| ID | Estado | Requisito | Módulo / páginas o artefactos | Historia y aceptación resumida | Verificación planeada | Fase |
| --- | --- | --- | --- | --- | --- | --- |
| SRC-001 | RESOLVED | Prompt Fase 01 v2 recibido y reconciliado. | Todos los docs; `docs/10-open-questions.md` | El diff marca reglas anteriores sustituidas sin borrar historia. | Revisión documental | F1 |
| CAL-001 | DECISION | Convocatoria con edición, slug, fechas, zona y estado. | Público/admin; inicio, convocatoria, calendario | Administrador configura edición; público ve estado y fechas correctas. | U + F + B + UAT | MVP |
| CAL-002 | ASSUMPTION | Una sola convocatoria activa en MVP, modelo extensible. | Convocatoria/configuración | No se mezclan proyectos entre ediciones; restricción documentada. | U + F | MVP |
| CAL-003 | ASSUMPTION | Estados persistidos `draft/scheduled/open/closed/judging/results_published/archived`; elegibilidad se deriva de proyectos. | Servicio de estados; admin | Sólo transiciones válidas por actor/precondición y no hay pseudoestado global desincronizado. | U + F | MVP |
| CAL-004 | DECISION/PENDING | Cierre inclusivo `2026-08-23 23:59:59 America/Hermosillo`; apertura pendiente. | Inicio, config, base y middleware | Persiste como `2026-08-24 06:59:59 UTC`, mantiene paridad config/base y bloquea desde el segundo siguiente. | Feature de frontera + migración + browser | F1 |
| CAL-005 | DECISION | No aceptar envío después del cierre salvo excepción auditada. | Wizard/envío; admin | Envío ordinario falla después del deadline; excepción exige permiso/razón. | U + F concurrencia + SEC | MVP |
| PUB-001 | ASSUMPTION | Sitio público con bases, categorías, proceso, FAQ y documentos. | `/`, `/convocatoria`, `/categorias`, `/como-participar`, `/preguntas-frecuentes`, `/documentos` | Visitante comprende requisitos y siguiente acción sin autenticarse. | B + A11Y + UAT | MVP |
| PUB-002 | DECISION | Resultados públicos desactivados por defecto. | `/resultados`; admin ganadores | Sin activación autorizada no se expone resultado. | F + B + SEC | MVP-R |
| PUB-003 | DECISION | Publicar sólo campos autorizados tras confirmación. | Resultados/archivo 2026 | Preview y salida pública omiten PII/documentos no consentidos. | F + B + SEC + UAT | MVP-R |
| PUB-004 | DECISION | Galería pública no pertenece al MVP. | `/proyectos` | No consume ruta crítica; requiere consentimiento/moderación posterior. | Revisión de alcance | F2 |
| IAM-001 | DECISION | Registro completo, login, logout y restablecimiento; contraseña mínima de 8 con mayúscula, minúscula, número, símbolo y confirmación. | Fortify, `CreateNewUser`, vistas auth, `phone-number-field` y componente `password-fields` | Backend aplica regla única; UI muestra requisitos/confirmación; registro crea perfil completo y recuperación no enumera correo. | `AuthMailHardeningTest`, `RegistrationProfileFlowTest` + browser | MVP |
| IAM-002 | DECISION | Verificación de correo antes de enviar. | Verificación; wizard | Usuario no verificado puede guardar borrador pero no enviar. | F + B | MVP |
| IAM-003 | DECISION | Roles/permisos de mínimo privilegio y Policies por recurso. | Todas las rutas autenticadas | Cada rol sólo accede a recursos autorizados incluso por URL directa. | F matriz RBAC + SEC IDOR | MVP |
| IAM-004 | DECISION DIFERENCIADA | 2FA opcional para juez; confirmación de contraseña en reapertura/recovery y demás acciones privilegiadas según contrato. | Cuenta/admin/juez futuro | No convertir 2FA opcional de juez en requisito; acciones críticas administrativas sí aplican step-up. | F + B + SEC | MVP/F2B |
| IAM-005 | DECISION | Rate limit, sesión revocable y respuestas no enumerables. | Auth/contacto/uploads | Abuso se limita y suspensión revoca sesiones. | F + SEC | MVP |
| IAM-006 | OWNER APPROVED JUEZ / PENDING EQUIPO | Alta de juez directa por admin, sin invitación; invitaciones firmadas sólo si se aprueban para integrantes. | Equipo/juez | M2 crea juez directamente; ningún token de juez. | U + F + SEC | F2B / MVP si equipos |
| ELG-001 | DECISION | Perfil mínimo y declaración de elegibilidad. | `/registro` y `/participante/perfil` | Participante captura los mínimos desde el alta, conoce finalidad de cada dato y puede revisar preferencias después. | F + B + A11Y + UAT | MVP |
| ELG-002 | DECISION | Comprobante de residencia separado y privado. | Perfil/residencia; admin elegibilidad | Sólo revisor autorizado accede; juez nunca lo ve. | F descarga + SEC + B por roles | MVP |
| ELG-003 | PENDING | Allowlist, vigencia y retención de comprobantes. | Upload/política de datos | Sólo formatos aprobados; retención ejecutable y documentada. | U + F archivos + OPS eliminación | MVP |
| ELG-004 | DECISION | Revisión registra decisión, razones, actor, fecha y versión. | `/admin/elegibilidad/{id}` | Revisor decide sobre snapshot fijo y deja historial. | F + UAT | MVP |
| ELG-005 | DECISION | Solicitud de corrección y reenvío controlado. | Seguimiento participante/admin | Participante ve motivo/plazo y genera nueva versión cuando aplica. | U estados + F + B | MVP |
| SUB-001 | DECISION Fase 01 | Borrador recuperable mediante guardado explícito; autoguardado queda pendiente de endpoint y control de concurrencia. | Mis propuestas/wizard | Edición persiste sin envío, advierte cambios locales y sólo confirma guardado tras respuesta real. | F por paso + B abandono/error | MVP |
| SUB-002 | DECISION Fase 01 | Wizard de cuatro pasos con revisión final sobre rutas existentes. | `/propuestas/nueva/crear`, `/propuestas/{id}/editar?step=1|2|3`, detalle borrador | Usuario completa pasos, vuelve atrás, preserva otras secciones y corrige desde revisión. | `SubmissionWizardTest` + B móvil/escritorio + A11Y | MVP |
| SUB-003 | DECISION Fase 01 / PENDING invitaciones | Participación individual o equipo de máximo cinco, representante incluida; invitaciones quedan fuera. | Equipo/wizard | Campos condicionales, declaración y límite se validan en servidor; sólo propietario edita. | F positivo/negativo + SEC | MVP recortable |
| SUB-004 | DECISION 2026-08-06 | Máximo cuatro propuestas, una por categoría, límites de texto y cuota compartida de anexos centralizados. | Wizard/configuración/transacción | Servidor y UI usan configuración; la cuenta se bloquea al revalidar el límite; archivos existentes y nuevos cuentan en la misma cuota. | `SubmissionWizardTest`, `SubmissionFlowTest`, `SubmissionCreationConcurrencyTest` + B | MVP |
| SUB-005 | DECISION | Envío exige correo verificado, elegibilidad mínima y legal vigente. | Acción de envío | Cada precondición bloquea con mensaje accionable; todas juntas permiten. | U + F matriz + B | MVP |
| SUB-006 | DECISION | Envío idempotente genera folio y versión inmutable. | Envío/acuse | Doble clic/reintento produce un envío y un folio; snapshot no cambia. | U + F concurrencia/idempotencia + B | MVP |
| SUB-007 | DECISION | Corrección crea nueva versión, no sobrescribe enviada. | Versiones/seguimiento | Auditor puede reconstruir cada envío y versión revisada. | U + F + UAT | MVP |
| SUB-008 | DECISION | Archivos privados fuera del web root y descarga autorizada. | Upload/download | URL directa no sirve archivo; controller/URL temporal aplica Policy. | F válidos/inválidos + SEC | MVP |
| SUB-009 | DECISION | Validar tamaño, extensión, MIME, firma, cuota y nombres internos. | Servicio de archivos | Ejecutable, HTML activo, spoof y exceso de cuota se rechazan. | U + F seguridad archivos | MVP |
| SUB-010 | PENDING | Retiro de proyecto y ventana permitida. | Detalle/estado | Retiro sólo ocurre en estados/fechas aprobados y queda auditado. | U estados + F | MVP-R |
| REV-001 | DECISION | Listados server-side autorizados, paginados, indexados y sin N+1. | `/admin/participantes`, `/admin/proyectos` | Operador filtra sin cargar dataset completo ni ver columnas no permitidas. | F consultas + perfil SQL + SEC | MVP |
| REV-002 | DECISION | Revisor decide elegible/no elegible/corrección según máquina de estados. | Detalle/revisión | Transición inválida se rechaza; válida notifica y audita. | U + F + B | MVP |
| REV-003 | DECISION | Notas internas no se exponen a participante/juez. | Detalle admin | Respuestas, exports y vistas externas omiten notas. | F serialización + SEC | MVP |
| REV-004 | DECISION | Reapertura excepcional requiere permiso, razón y auditoría. | Admin proyecto/evaluación | Sin permiso o razón no procede; usuario afectado recibe estado correcto. | F + SEC + UAT | MVP |
| F2B-M1-001 | OWNER_APPROVED / VERIFIED LOCAL | Los cuatro roles de negocio son excluyentes. | `BusinessRole`, `AssignExclusiveBusinessRole`, creación participant/admin | Mismo rol es idempotente; cero/multirol y sustitución implícita fallan cerrados. | `JudgeRbacIsolationTest` | F2B-M1 |
| F2B-M1-002 | VERIFIED LOCAL | `judge` sólo recibe permiso mínimo exclusivo. | migración M1 y `FlowerFlowSeeder` | Admin/reviewer/participant no heredan `access judge workspace`; no se crean jueces. | M1 dirigido + seeder doble | F2B-M1 |
| F2B-M1-003 | VERIFIED LOCAL | Gates exactos separan participant, panel y judge. | `EnsureExclusiveBusinessRole`, rutas y Policies | Acceso positivo por rol y 403/404 en cruces/IDOR. | matriz 6/92 + regresión 40/393 | F2B-M1 |
| F2B-M1-004 | VERIFIED LOCAL | Evaluación permanece apagada por defecto. | `FLOWERFLOW_EVALUATION_ENABLED=false`, `EnsureEvaluationEnabled` | Judge no cae a participante; flag off da 404 y estado seguro. | Feature + UAT flag on/off | F2B-M1 |
| F2B-M1-005 | VERIFIED LOCAL | Shell juez mínimo no contiene datos ni controles futuros. | `/juez`, layout/vista/estado restringido | Sólo correo verificado+rol+permiso+flag; sin propuestas, PII o archivos. | Firefox 1440/768/360/320, teclado/zoom/consola | F2B-M1 |
| F2B-M1-006 | VERIFIED LOCAL | Migración es aditiva, reversible y preserva roles/datos. | `2026_08_18_120000_add_judge_role_and_workspace_permission.php` | Forward/rollback/forward; down falla seguro si el rol/permiso tiene asignaciones. | `migrate`, rollback, 13/13 | F2B-M1 |
| F2B-M1-007 | VERIFIED LOCAL | La suite y los gates de release local permanecen verdes. | ExecPlan M1 y QA | 115 pruebas/1,141; Pint, Composer, build, JSON, rutas y schedule verdes; Quill bajo visible. | gates automatizados/locales | F2B-M1 |
| F2B-M2-001 | VERIFIED HISTORICAL + M4A CLOSED | Perfil juez uno-a-uno, ULID, estados, función primary/substitute y capacidad derivada. | `JudgeProfile`, enums y migraciones M2/M4A | M2 prueba antecedente `primary=NULL`/`substitute=10`; M4A migra ambos a `NULL`. | M2 histórica + M4A | F2B-M2/M4A |
| F2B-M2-002 | VERIFIED LOCAL + M4A CLOSED | Sólo admin crea juez directamente con nombre/correo/función y rol exclusivo; capacidad derivada. | `CreateJudgeAccount`, Requests/Policy, `/panel/jueces` | Seguridad de alta permanece; M4A muestra `Sin límite`, sin auto-crear seis cuentas. | Feature M2 + regresión M4A | F2B-M2/M4A |
| F2B-M2-003 | VERIFIED LOCAL | El juez establece su credencial mediante broker seguro y se activa sólo con contraseña propia y correo verificado. | `InitializeJudgePassword`, `SynchronizeJudgeProfileActivation`, listener de verificación | `password_initialized_at` se fija una vez; ambos órdenes activan idempotentemente; 2FA permanece opcional. | Feature reset/verificación/activación | F2B-M2 |
| F2B-M2-004 | VERIFIED LOCAL | Pending/suspended fallan cerrados y sólo active puede abrir el shell con flag. | middleware `judge.active`, `/juez/estado`, gate M1 | Ningún estado recibe rutas participant/panel/archivos; flag apagado sigue fail-closed. | M1+M2 matrix + Firefox | F2B-M2 |
| F2B-M2-005 | VERIFIED LOCAL | Suspensión/reactivación requieren admin, permiso, razón y password confirmation; sesiones se revocan. | `SuspendJudge`, `ReactivateJudge`, `RevokeUserSessions` | Rol/perfil se conservan; reactivación vuelve a pending si faltan prerrequisitos. | Feature + UAT de sesión real | F2B-M2 |
| F2B-M2-006 | VERIFIED LOCAL | Recovery 2FA sólo admin y sin mostrar secreto/códigos. | `RecoverJudgeTwoFactor`, permiso separado, aviso de estado | Razón/password confirmation, auditoría, limpieza TOTP, remember token y sesiones revocadas; correo verificado requerido. | Feature + UAT de invalidación | F2B-M2 |
| F2B-M2-007 | VERIFIED LOCAL | Alta, setup, activación, estado y recovery quedan auditados/notificados sin secretos. | acciones M2, `ResilientMailDispatcher`, notificaciones duales | Actor/sujeto/transición/timestamps UTC; fallo de despacho observable y no revierte la cuenta. | Mail fake/array, fallo resiliente y scan | F2B-M2 |
| F2B-M2-008 | VERIFIED LOCAL | Gates de release local y UX M2 están verdes. | ExecPlan/reporte M2 y QA | M1+M2 16/267 tras función/capacidad; suite completa y gates finales registrados en el reporte; Firefox desktop/tablet/mobile. | automatizado + UAT local | F2B-M2 |
| JUD-001 | M1/M2 VERIFIED LOCAL | Shell exclusivo; roles excluyentes, alta directa, correo verificado, perfil activo y 2FA opcional. | `/juez`, `judge.active`, `/panel/jueces`, escritor exclusivo y flag | M1 aísla; M2 crea/activa/suspende/recupera sin conceder superficies ajenas. | suites M1/M2 + Firefox local | F2B |
| JUD-002 | OWNER_APPROVED / M5 VERIFIED LOCAL | Ceguera simple estructural; M5 sirve sólo payload allowlist y anexos neutros a la asignación propia activa. | `/juez/asignaciones/{id}` y descarga M5 | Mantener canarios; la autoidentificación semántica permanece como riesgo aceptado. | F payload + B + SEC | F2B |
| JUD-003 | OWNER FINAL / M4–M8 VERIFIED LOCAL | Catálogo cerrado; reemplazo seleccionado manualmente entre jueces activos elegibles; avisos redactados a responsables exactos. | conflictos/Actions M6A, listeners M8 | Estado bloqueante, cadena explícita, sin fallback de destinatario. | suites M6A/M8 + UAT | F2B |
| JUD-004 | OWNER FINAL / M6A VERIFIED LOCAL | Asignación manual sin mínimo/máximo; `primary|substitute` informativo. | `JudgeAssignment`, UI/actions M6A | Cualquier juez activo elegible; sin cobertura ni automatismo. | suites M6A + concurrencia | F2B |
| JUD-005 | OWNER FINAL / M6A–M7 VERIFIED LOCAL | Rúbrica v1 histórica de cinco y v2 activa de cuatro criterios, escala 0–10/paso .5, comentario general y por criterio. | catálogo de contratos, evaluación | Versión fijada inmutable; dimensión dinámica. | cálculo/versionado + feature | F2B |
| JUD-009 | VERIFIED LOCAL | Un admin exacto asigna hasta veinte propuestas a un juez mediante admisión, paquete y asignación atómicos por propuesta. | `/panel/asignaciones/masiva`, intención cifrada, Actions existentes | Sin selección oculta; éxito parcial; correo consolidado; rollback por flag. | `BulkJudgeAssignmentTest` 9/123; suite 228/2,653; benchmark 20×10 MiB 2.589 s; UAT Firefox | F2B |
| JUD-006 | OWNER APPROVED / M6–M8 VERIFIED LOCAL | Borrador, envío inmutable, reapertura append-only y comunicaciones con actor real. | evaluación M6/M7, outbox M8 | Admin no sobrescribe/suplanta; evento fallido no revierte negocio. | F + B + A11Y + SEC | F2B |
| JUD-007 | OWNER APPROVED / PARTIAL | Total servidor 0–100 y precisión 4/2 HALF_UP están verificados; consolidación/media/empate no se implementan. | cálculo M6/M7; M9+ pendiente | Payload cliente no altera total; no existe ranking/ganador. | U cálculo + ausencia de alcance | F2B |
| JUD-008 | OWNER APPROVED / M7–M8 VERIFIED LOCAL | Reapertura hasta 20:00; envío hasta 23:59:59; digest durante las 24 h siguientes. | `EvaluationWindow`, digest/scheduler M8 | Segundos exactos, timezone/config/`due_at` fail-closed. | límites + drift | F2B |
| WIN-001 | DECISION | Declarar ganador es separado del cálculo. | `/admin/ganadores` | Resultado calculado no cambia proyecto a ganador automáticamente. | U + F | MVP |
| WIN-002 | DECISION | Declaración registra categoría, proyecto, actor, justificación y fecha. | Ganadores/auditoría | Decisión incompleta o sin permiso se rechaza. | F + SEC + UAT | MVP |
| WIN-003 | PARTIAL | Empate técnico se detecta por igualdad del consolidado redondeado a dos decimales; resolución, categoría desierta y premio siguen pendientes. | Ganadores/reglas | 02B sólo emite señal; nunca elige ganador ni usa azar. | U reglas + F + UAT | MVP/FUTURE |
| COM-001 | VERIFIED LOCAL | Notificaciones transaccionales actuales en español, HTML/texto y marca dual. | renderizadores auth/propuesta/admisibilidad/juez/evaluación | Plantillas profesionales sin adjuntos/PII adicional. | suites mail/ledger/M8 + render | MVP |
| COM-002 | VERIFIED LOCAL | Cola cifrada post-commit, reintento y recuperación de correo. | `ResilientMailDispatcher`, `DeliverCommunication`, `database/default`, bitácora común | Cuatro intentos con 60/300/900; cada transición e intento queda correlacionado; una falla no revierte el evento de negocio. | `CommunicationDeliveryLedgerTest`, suites auth/admisibilidad/recordatorios | MVP |
| COM-003 | DECISION | Usar `convocatoria@flowerflow.com.mx` para convocatoria y `privacidad@flowerflow.com.mx` para privacidad. | Plantillas/configuración | Remitente/reply-to y canal corresponden al propósito sin mezclar casos. | F con mail fake + revisión de configuración | MVP |
| COM-004 | PENDING | SMTP y entregabilidad SPF/DKIM/DMARC. | Configuración/runbook AWS | Dominio autentica envío y se monitorean rebotes. | OPS DNS + smoke correo | MVP |
| COM-005 | DECISION | Marketing masivo no está aprobado. | Comunicaciones | No existe envío promocional/masivo en MVP. | Revisión de rutas/permisos | OUT |
| COM-006 | VERIFIED LOCAL | Bitácora administrativa de los quince tipos actuales, sin cuerpo ni PII completa. | `/panel/notificaciones`, delivery/attempt models, ADR-0009/0012 | Sólo admin exacto; GET no muta; máscara, timeline y `sent=aceptado por transporte`. | Feature permisos/HTML/cifrado + UAT local | M8 |
| COM-007 | VERIFIED LOCAL | Recuperación individual segura de queued/failed/unknown. | `ForceCommunicationDelivery`, cola high, lock_version | Password reciente, CSRF, throttle, razón cifrada; unknown exige ack de duplicado; sent/cancelled no actúan. | Feature lock 409/riesgo/concurrencia | Milestone independiente |
| COM-008 | VERIFIED LOCAL | Backfill sólo desde recordatorios confiables y reconciliación de processing vencido. | comandos `communications-*`, scheduler | Dry-run por defecto e idempotente; no reconstruye logs/jobs; reconciliación pasa a unknown sin enviar. | Feature comandos + schedule:list | Milestone independiente |
| COM-009 | M8 VERIFIED LOCAL | Conflicto, resolución, envío/reenvío, reapertura y digest usan destinatarios exactos e idempotencia. | eventos/listeners/registry/digest M8 | Sin fallback/replay; estados obsoletos cancelan; un digest por juez. | `EvaluationCommunicationTest` + UAT | M8 |
| PRV-001 | ASSUMPTION | Bandeja mínima de solicitudes de privacidad. | `/admin/privacidad` | Soporte registra solicitud, evidencia, responsable y cierre. | F + B + SEC + UAT | MVP-R |
| PRV-002 | DECISION | Exportar, rectificar y eliminar de forma controlada. | Privacidad/políticas de datos | Acción aplica permisos, retención y auditoría; no promete revisión legal. | F + SEC + OPS | MVP-R |
| RPT-001 | DECISION | Reportes por categoría, estado, elegibilidad y evaluación. | `/admin/reportes` | Usuario autorizado filtra métricas definidas y consistentes. | U agregados + F + UAT | MVP |
| RPT-002 | DECISION | Exportaciones backend limitadas por permiso y con auditoría. | Reportes/exports | Export omite columnas no autorizadas y registra actor/fecha. | F archivo/contenido + SEC | MVP |
| AUD-001 | DECISION | Auditar actor, acción, entidad, fecha, contexto y antes/después redactado. | Auditoría transversal | Acciones críticas producen registro sin secretos/PII completa. | F eventos + revisión de redacción | MVP |
| AUD-002 | DECISION | Auditar descargas sensibles, exports, conflictos y ganador. | `/admin/auditoria` | Cada evento es consultable por auditor y no editable por operador. | F + SEC + UAT | MVP |

## Trazabilidad UX, seguridad y calidad

| ID | Estado | Requisito | Módulo / páginas o artefactos | Historia y aceptación resumida | Verificación planeada | Fase |
| --- | --- | --- | --- | --- | --- | --- |
| UX-001 | DECISION | WCAG 2.2 AA como objetivo. | Todos los recorridos; `docs/05-ux-ui.md` | Usuario completa tareas con teclado, foco visible, labels y errores asociados. | A11Y manual + axe equivalente + B | MVP |
| UX-002 | DECISION | Wizard usable en móvil/escritorio y lector de pantalla. | Wizard | Progreso, pasos, validación y resumen no dependen sólo de visuales. | B viewports + lector + teclado | MVP |
| UX-003 | DECISION | Estados vacío/carga/error/éxito/sin permiso/cerrada. | Todas las páginas de datos | Cada estado explica situación y siguiente acción sin filtrar datos. | Component review + B | MVP |
| UX-004 | DECISION | Tablas responsive con alternativa móvil. | Backoffice | Datos y acciones permanecen comprensibles a 320 CSS px/zoom. | B responsive + A11Y | MVP |
| UX-005 | ASSUMPTION | Branding naranja/crema/carbón inspirado en Hermosillo. | Layout público/admin | Tokens aprobados alcanzan contraste y no dependen de assets sin licencia. | Contraste + revisión marca | MVP |
| UX-006 | PENDING | Logo, tipografías, fotos y manual licenciados. | Assets/identidad | Sólo assets aprobados llegan a build productivo. | Inventario/licencia + UAT | PLAN |
| SEO-001 | DECISION | Metadata pública, canonical, sitemap/robots y `noindex` privado/staging. | Layout front/rutas | Buscadores indexan sólo contenido público autorizado. | Inspección HTML + smoke robots | MVP |
| SEC-001 | DECISION | CSRF, escape, validación servidor y bindings. | Aplicación web | Payload malicioso no cambia estado ni ejecuta contenido/SQL. | F negativos + SEC | MVP |
| SEC-002 | DECISION | Protección IDOR/BOLA en recurso y archivo. | Policies/queries/downloads | Cambiar identificador no concede acceso. | F matriz usuarios + SEC | MVP |
| SEC-003 | DECISION | Cookies seguras, headers y `APP_DEBUG=false` en producción. | Middleware/config AWS | Smoke productivo confirma atributos y ausencia de debug. | OPS + SEC headers | MVP |
| SEC-004 | DECISION | Secretos fuera de JS, HTML, repo, docs y logs. | Configuración/CI/runbook | Escaneo no encuentra valores reales; ejemplos usan placeholders. | Secret scan + revisión diff | MVP |
| SEC-005 | DECISION | Minimización, masking y separación de PII/evaluación. | Datos, vistas, exports | Cada rol recibe sólo campos necesarios. | F serialización/export + SEC | MVP |
| SEC-006 | IMPLEMENTED/PARTIAL | CSP estricta con nonce disponible por flag y desplegada inicialmente en Report-Only. | `SecurityHeaders`, Vite nonce y configuración Flower Flow | Tests cubren promoción/HTTPS; enforcement productivo requiere consola, soak y aprobación. | `SecurityAndFlagsTest` + browser/OPS | MVP-R |
| LEG-001 | DECISION | Documentos y aceptaciones versionadas. | Legal/registro/perfil/envío | Se conserva documento/hash/version aceptada en el contexto correcto; términos, privacidad, WhatsApp y futuras actividades son propósitos independientes. | U + F + auditoría | MVP |
| LEG-002 | PENDING | Textos legales finales y política de retención. | Público/legal/privacidad | Sólo versiones aprobadas se publican/aceptan. | Revisión legal + UAT | MVP |
| DATA-001 | DECISION | MySQL, InnoDB, `utf8mb4`, FKs e índices intencionales. | Modelo/migraciones futuras | Esquema soporta integridad y filtros; no usa JSON central injustificado. | Revisión migraciones + EXPLAIN | MVP |
| DATA-002 | DECISION | UTC persistido y `America/Hermosillo` presentado. | Fechas/estados/reportes | Tests cubren frontera de apertura/cierre y conversiones. | U + F | MVP |
| DATA-003 | DECISION | Retención/borrado por entidad; no soft deletes indiscriminados. | Modelo/jobs/runbook | Borrado respeta política y evidencia sin conservar PII indebida. | U + F + OPS | MVP |
| QA-001 | DECISION | Matriz de pruebas trazada y datos sintéticos. | Tests/docs | Cada requisito MVP tiene prueba o revisión identificada; fixtures sin PII real. | Revisión matriz + test suite | MVP |
| QA-002 | DECISION | Detener avance si fallan test/build/lint/aceptación. | ExecPlan/CI | Milestone no cierra con validación roja. | Gate CI + evidencia en plan | PLAN |
| QA-003 | PENDING | Herramienta E2E y análisis estático definitivos. | Tooling QA | Se agregan sólo con compatibilidad, ADR y aprobación. | Spike + ADR | PLAN |

## Trazabilidad de ambientes y operación

| ID | Estado | Requisito | Artefacto / ambiente | Aceptación resumida | Verificación planeada | Fase |
| --- | --- | --- | --- | --- | --- | --- |
| ENV-001 | DECISION | MySQL local en `127.0.0.1:3306`. | `.env` local no versionado; docs | Conectividad usa host/puerto definidos sin publicar secretos. | Diagnóstico de conexión redactado | PLAN |
| ENV-002 | DECISION | Base `flowerflow` y usuario `flowerflow_user`. | Ambiente local/pruebas | Aplicación de prueba usa esquema/usuario indicados. | Consulta `SELECT DATABASE(), CURRENT_USER()` con salida segura | PLAN |
| ENV-003 | DECISION | Contraseña provista fuera del repo sólo en `.env` local. | Gestión de secretos | Valor literal ausente de docs, ejemplos, git, logs y fixtures. | Secret scan + revisión manual | PLAN |
| ENV-004 | DECISION local | La base local vacía se autorizó como ambiente de pruebas. | MySQL local | Migraciones/seeders y suite se ejecutan sólo en `flowerflow`; datos QA sintéticos se retiran al cerrar. | Confirmación del propietario + inventario vacío + gate verde | F1 |
| DEP-001 | DECISION | Producción en AWS EC2 Ubuntu coexistente con `administratec`. | Runbook/ADR AWS | Arquitectura y riesgos reflejan el destino real. | Revisión documental | PLAN |
| DEP-002 | DECISION | Aislar vhost, ruta, usuario, env, DB, storage, cache/sesión, procesos y logs. | EC2 | Flower Flow no comparte secretos ni namespace operativo; fallos no colisionan por configuración. | OPS preflight + smoke cruzado | MVP |
| DEP-003 | PENDING | Inventariar Ubuntu, CPU/RAM/disco, web server, PHP-FPM y extensiones. | EC2 | Laravel 12/PHP 8.2+ y carga prevista son compatibles. | Comandos read-only + matriz de versiones | PLAN |
| DEP-004 | PENDING | Definir DB productiva y backups. | EC2/RDS por decidir | Esquema/usuario exclusivos, cifrado, RPO/RTO y restauración probada. | Backup/restore drill | MVP |
| DEP-005 | PENDING | Definir dominio, DNS y TLS. | Vhost/certificado | HTTPS canónico, headers y renovación monitoreada. | OPS DNS/TLS + browser smoke | MVP |
| DEP-006 | PENDING | Scheduler y workers propios. | `systemd`/Supervisor/cron | Jobs, reintentos y cierre funcionan sin interferir con `administratec`. | OPS queue/scheduler smoke | MVP |
| DEP-007 | DECISION | Build Vite con Node 22.23.1 aislado por NVM y Yarn Classic 1.22.22; no editar `public/build` manualmente. | CI/release | Artefacto reproducible corresponde al commit/release sin alterar el Node global. | `scripts/build_frontend_production.sh` + manifest/revisión | MVP |
| DEP-008 | DECISION | No desplegar sin backup, UAT, checklist y aprobación. | Gate de producción | Las cuatro evidencias existen y responsables firman. | Revisión de release | PLAN |
| OPS-001 | DECISION | Health check y monitoreo de 5xx, jobs, correo, disco y recursos. | EC2/alertas | Alertas tienen umbral, canal y dueño. | Simulación controlada + OPS | MVP |
| OPS-002 | DECISION | Logs redactados y con rotación. | Laravel/web server/systemd | No contienen passwords, tokens, documentos o PII completa; disco no crece sin límite. | Revisión muestras + logrotate test | MVP |
| OPS-003 | PENDING | SLO, RPO, RTO, volumen y concurrencia. | Arquitectura/runbook | Objetivos medibles se basan en capacidad y demanda aprobadas. | Prueba de carga + restore drill | PLAN |
| OPS-004 | DECISION | Rollback de código y base documentado. | Runbook AWS | Ensayo demuestra retorno a versión segura sin afectar `administratec`. | Dry run en staging | MVP |

## Requisitos explícitamente fuera de alcance

| ID | Estado | Requisito excluido | Razón | Evidencia de control |
| --- | --- | --- | --- | --- |
| OUT-001 | DECISION | Marketing masivo. | Sin consentimiento ni alcance explícito. | No hay rutas/jobs/permisos de campaña en MVP. |
| OUT-002 | DECISION | API pública, aplicación móvil o integración externa. | No necesaria para operación central. | No se añade contrato/API pública al backlog MVP. |
| OUT-003 | DECISION | Selección aleatoria. | Contradice reglas invariantes. | No existe dependencia o servicio de sorteo. |
| OUT-004 | DECISION | Ranking global para jueces. | Mínimo privilegio e independencia. | Payload/vista de juez no incluye agregados globales. |
| OUT-005 | DECISION | Publicar comprobantes o PII no autorizada. | Privacidad y seguridad. | Pruebas negativas de serialización/publicación. |
| OUT-006 | DECISION | Afirmar cumplimiento o sustituir revisión legal. | El sistema sólo implementa controles y soporte administrativo. | Textos revisados por producto/legal. |
| OUT-007 | DECISION | Assets Apple/iPad sin licencia. | Propiedad intelectual/marca. | Inventario de assets previo al build productivo. |

## Matriz de invariantes

| INV | Regla | Requisitos relacionados | Prueba mínima |
| --- | --- | --- | --- |
| INV-01 | No enviar sin correo verificado, elegibilidad mínima y legal vigente. | IAM-002, ELG-001, LEG-001, SUB-005 | Feature data set por cada precondición y combinación válida. |
| INV-02 | No enviar después del cierre salvo excepción auditada. | CAL-004, CAL-005, SUB-006, AUD-001 | Tests de fecha/zona, permiso, razón e idempotencia. |
| INV-03 | Juez no ve/evalúa proyecto no asignado. | IAM-003, JUD-001, JUD-002 | Feature con juez A/B y URL/ID alterado. |
| INV-04 | Conflicto impide evaluar. | JUD-003 | Estado + endpoint + UI tras conflicto. |
| INV-05 | Juez nunca accede a residencia. | ELG-002, JUD-002, SEC-005 | Respuestas, downloads, exports y búsqueda por rol. |
| INV-06 | Puntuación se calcula en servidor. | JUD-005, JUD-007 | Manipular total cliente; servidor recalcula. |
| INV-07 | Ganador es decisión separada. | WIN-001, WIN-002 | Cerrar evaluaciones no cambia estado a winner. |
| INV-08 | No existe selección aleatoria. | WIN-003, OUT-003 | Revisión de flujo/dependencias y pruebas de reglas aprobadas. |
| INV-09 | Envío conserva versión auditable. | SUB-006, SUB-007, AUD-001 | Cambiar borrador/corrección no muta snapshot previo. |
| INV-10 | Resultados permanecen apagados hasta autorización. | PUB-002, PUB-003, WIN-002 | Feature default + permiso + preview/publicación. |
| INV-11 | No usar datos reales en pruebas. | QA-001, SEC-005 | Revisión de factories/fixtures y escaneo de PII. |
| INV-12 | No almacenar secretos en repo/docs/logs. | SEC-004, ENV-003, OPS-002 | Secret scan y revisión de artefactos/logs. |

## Historias críticas para el ExecPlan

| Historia | Requisitos | Criterio de salida |
| --- | --- | --- |
| H-01 Consultar convocatoria vigente | CAL-001–005, PUB-001, UX-001, SEO-001 | Fecha/estado exactos y páginas públicas accesibles. |
| H-02 Crear y verificar cuenta | IAM-001–005, COM-001 | Cuenta segura, correo verificado y recuperación funcional. |
| H-03 Completar elegibilidad | ELG-001–005, SUB-008–009 | PII mínima, residencia privada y revisión trazable. |
| H-04 Preparar proyecto | SUB-001–004, UX-002–003 | Borrador recuperable, límites claros y archivos válidos. |
| H-05 Enviar proyecto | SUB-005–007, INV-01–02/09 | Envío idempotente, folio y versión inmutable. |
| H-06 Revisar elegibilidad | REV-001–004, AUD-001–002 | Transiciones autorizadas y correcciones auditadas. |
| H-07 Asignar y evaluar | JUD-001–008, INV-03–06 | Sólo asignación propia, conflicto bloquea y total de servidor. |
| H-08 Declarar ganador | WIN-001–003, INV-07–08 | Decisión justificada, separada y no aleatoria. |
| H-09 Publicar resultado | PUB-002–003, INV-10 | Preview, permiso separado y salida sin PII. |
| H-10 Operar y recuperar | DEP-001–008, OPS-001–004 | EC2 aislada, observable, respaldada y con rollback probado. |

## Comandos de validación planeados

Los comandos exactos se ajustarán al entorno y se ejecutarán sólo en milestones aprobados:

```bash
php artisan route:list
php artisan test
./vendor/bin/pint --test
scripts/build_frontend_production.sh
composer audit --locked
```

**PENDING:** confirmar herramientas adicionales y compatibilidad antes de añadir PHPStan/Larastan, Playwright, Dusk o escáneres.

Para MySQL local, cualquier diagnóstico debe evitar imprimir la contraseña:

```bash
mysql --host=127.0.0.1 --port=3306 --user=flowerflow_user --password --execute="SELECT DATABASE(), CURRENT_USER(), VERSION();"
```

El secreto se introduce interactivamente o desde el `.env` local; no se incrusta en historial, documentación o scripts.

## Criterio de actualización

La matriz se actualiza cuando:

1. llega el fragmento faltante;
2. una pregunta `PENDING` se convierte en `DECISION`;
3. cambia alcance o fase;
4. se crea una historia/ruta/tabla/prueba real;
5. un test demuestra una limitación;
6. cambia la topología AWS o la coexistencia con `administratec`.

Cada cambio debe mantener el vínculo requisito → historia → módulo/página → criterio → prueba y registrar la decisión en `docs/10-open-questions.md` o un ADR.
