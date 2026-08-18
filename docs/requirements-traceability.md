# Matriz de trazabilidad de requisitos — Flower Flow 2026

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
| AUDIT-004 | Gate de código vigente | 125 pruebas/1,316 aserciones, Pint, Composer, JSON y build verdes; Quill bajo documentado | VERIFIED local |
| AUDIT-005 | Próximo prompt exacto y acotado | prompt M3 sincronizado en diagnóstico/paquete; conserva M1/M2 y registra `P2B-BLOCK-001=RESOLVED` | READY FOR EXPLICIT OWNER AUTHORIZATION |

## Trazabilidad del diseño Fase 02B — 2026-08-18

| ID | Requisito de diseño | Evidencia | Estado |
|---|---|---|---|
| F2B-DES-001 | Baseline Git y código real antes de diseñar | ExecPlan 02B e inventario en paquete sección 4 | VERIFIED documental/local read-only |
| F2B-DES-002 | Distinguir despliegue informado de evidencia técnica | diagnóstico, product spec y handoff con ambos estados | `OWNER_CONFIRMED_DEPLOYED` / SHA `POR_CONFIRMAR` |
| F2B-DES-003 | Identidad/alta directa/perfil/gate de juez | M1: enum/Action/middleware/migración/rutas/shell; M2: `judge_profiles`, acciones, Policy/Requests, admin UI y middleware activo | M1/M2 VERIFIED LOCAL |
| F2B-DES-004 | Asignación/reasignación/cobertura/plazo | paquete sección 7; decisiones 003–005/015/016 y corrección D-031 | OWNER_APPROVED / BLOCK RESOLVED / M4 NOT AUTHORIZED |
| F2B-DES-005 | Matriz ciega campo por campo y anonimización | paquete sección 8; decisiones 006–008 | OWNER_APPROVED / SEMANTIC IDENTITY RISK ACCEPTED |
| F2B-DES-006 | Rúbrica versionada y contrato exacto | paquete sección 9; decisiones 009–011 | OWNER_APPROVED / NOT IMPLEMENTED |
| F2B-DES-007 | Estados, envío inmutable y reapertura versionada | paquete sección 10; decisión 017 | OWNER_APPROVED / NOT IMPLEMENTED |
| F2B-DES-008 | Cálculo sólo servidor, consolidación/faltantes/empate | paquete sección 11; decisiones 010/012/013/021 | OWNER_APPROVED / NOT IMPLEMENTED |
| F2B-DES-009 | Matriz negativa, amenazas y auditoría | `JudgeRbacIsolationTest` + `JudgeProfileOnboardingTest` (16 pruebas/267 aserciones), suite completa y paquete sección 12 | M1/M2 VERIFIED; M3+ PENDING |
| F2B-DES-010 | UX accesible mínima | `/juez`, `/juez/estado`, `/panel/jueces` y `/cuenta/acceso`; UAT Firefox desktop/tablet/mobile, teclado, foco, reflow, consola y 403/404 | M1/M2 VERIFIED; UX M3+ PENDING |
| F2B-DES-011 | Notificaciones idempotentes y operación | M2: configuración de acceso, verificación y estado/recovery con HTML+texto y dispatcher resiliente; paquete sección 14 para eventos futuros | M2 SUBSET VERIFIED / M3+ PENDING |
| F2B-DES-012 | Compatibilidad con más de 50 propuestas | migración M2 aditiva, perfil primary/substitute, sin backfill/asignaciones; upgrade/rollback/forward preservó usuario sintético | M2 VERIFIED LOCAL / CAPACITY DECISION CLOSED |
| F2B-DES-013 | Diez milestones futuros | paquete sección 18 | M1/M2 COMPLETE; M3 READY; M4 READY AFTER M3; M5–M10 NOT AUTHORIZED |
| F2B-DES-014 | Bloque de 21 respuestas y siguiente prompt | paquete secciones 20–21; diagnóstico prompt vigente | 21/21 OWNER_APPROVED / M3 READY |
| F2B-DES-015 | Resolver incompatibilidad de cobertura/capacidad/reemplazo | paquete secciones 5/7/19/20; ADR-0008; D-031; riesgo R76 | `P2B-BLOCK-001 RESOLVED BY OWNER` |
| F2B-DES-016 | Contrato de QA por milestone | `JudgeRbacIsolationTest`, `JudgeProfileOnboardingTest`, `docs/08-testing-qa.md`; gates futuros de fórmula, reapertura, anonimización y retención | M1/M2 TESTS VERIFIED / M3+ PENDING |

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
| F2B-M2-001 | VERIFIED LOCAL | Perfil juez uno-a-uno, ULID, estados, función primary/substitute y capacidad derivada. | `JudgeProfile`, enums y migración M2 | Principal sin límite fijo (`NULL`), sustituto diez; checks/FKs/índices; sin especialidad, invitación, backfill, juez o asignación automática. | `JudgeProfileOnboardingTest` + forward/rollback/forward | F2B-M2 |
| F2B-M2-002 | VERIFIED LOCAL | Sólo admin crea juez directamente con nombre/correo/función y rol exclusivo; capacidad derivada en servidor. | `CreateJudgeAccount`, Requests/Policy, `/panel/jueces` | Correo/rol/perfil duplicados o carreras fallan sin sustitución ni fila parcial; contraseña aleatoria nunca se expone; `primary=NULL`/`substitute=10`. | Feature positivo/negativo/concurrencia/DB checks | F2B-M2 |
| F2B-M2-003 | VERIFIED LOCAL | El juez establece su credencial mediante broker seguro y se activa sólo con contraseña propia y correo verificado. | `InitializeJudgePassword`, `SynchronizeJudgeProfileActivation`, listener de verificación | `password_initialized_at` se fija una vez; ambos órdenes activan idempotentemente; 2FA permanece opcional. | Feature reset/verificación/activación | F2B-M2 |
| F2B-M2-004 | VERIFIED LOCAL | Pending/suspended fallan cerrados y sólo active puede abrir el shell con flag. | middleware `judge.active`, `/juez/estado`, gate M1 | Ningún estado recibe rutas participant/panel/archivos; flag apagado sigue fail-closed. | M1+M2 matrix + Firefox | F2B-M2 |
| F2B-M2-005 | VERIFIED LOCAL | Suspensión/reactivación requieren admin, permiso, razón y password confirmation; sesiones se revocan. | `SuspendJudge`, `ReactivateJudge`, `RevokeUserSessions` | Rol/perfil se conservan; reactivación vuelve a pending si faltan prerrequisitos. | Feature + UAT de sesión real | F2B-M2 |
| F2B-M2-006 | VERIFIED LOCAL | Recovery 2FA sólo admin y sin mostrar secreto/códigos. | `RecoverJudgeTwoFactor`, permiso separado, aviso de estado | Razón/password confirmation, auditoría, limpieza TOTP, remember token y sesiones revocadas; correo verificado requerido. | Feature + UAT de invalidación | F2B-M2 |
| F2B-M2-007 | VERIFIED LOCAL | Alta, setup, activación, estado y recovery quedan auditados/notificados sin secretos. | acciones M2, `ResilientMailDispatcher`, notificaciones duales | Actor/sujeto/transición/timestamps UTC; fallo de despacho observable y no revierte la cuenta. | Mail fake/array, fallo resiliente y scan | F2B-M2 |
| F2B-M2-008 | VERIFIED LOCAL | Gates de release local y UX M2 están verdes. | ExecPlan/reporte M2 y QA | M1+M2 16/267 tras función/capacidad; suite completa y gates finales registrados en el reporte; Firefox desktop/tablet/mobile. | automatizado + UAT local | F2B-M2 |
| JUD-001 | M1/M2 VERIFIED LOCAL | Shell exclusivo; roles excluyentes, alta directa, correo verificado, perfil activo y 2FA opcional. | `/juez`, `judge.active`, `/panel/jueces`, escritor exclusivo y flag | M1 aísla; M2 crea/activa/suspende/recupera sin conceder superficies ajenas. | suites M1/M2 + Firefox local | F2B |
| JUD-002 | OWNER_APPROVED / NOT IMPLEMENTED | Ceguera simple estructural; todos los campos sustantivos/anexos evaluables; nunca PII estructurada/residencia/notas/aclaraciones/historial. | `/juez/asignaciones/{id}` futura | Allowlist elimina estructura/metadatos; riesgo de identidad en contenido aceptado y visible. | F payload + B + SEC | F2B |
| JUD-003 | OWNER_APPROVED / NOT IMPLEMENTED | Catálogo cerrado de conflicto y resolución/reasignación manual por admin al quinto sustituto. | Conflicto/evaluación futura | Estado bloqueante, reemplazo, locks y máximo diez sustituciones activas. | U estados + F + B | F2B |
| JUD-004 | OWNER_APPROVED / NOT IMPLEMENTED | Cuatro principales por propuesta sin límite fijo, asignación manual; quinto sólo sustituciones con capacidad diez; sin especialidad. | Admin asignaciones futura | Impedir carga inicial al sustituto y rechazar la undécima activa; no autoasignar. | U reglas/capacidad + F | F2B |
| JUD-005 | OWNER_APPROVED / NOT IMPLEMENTED | Rúbrica 20/20/25/25/10, escala 0–10/paso 0.5, comentario general obligatorio y por criterio opcional. | Rúbrica/evaluación futura | Versión activada inmutable; evaluación conserva versión exacta. | U cálculo/versionado + F | F2B |
| JUD-006 | OWNER_APPROVED / NOT IMPLEMENTED | Borrador, envío inmutable y reapertura append-only con ventanas, razón, password confirmation y actor real. | Evaluación futura | Admin no sobrescribe ni suplanta; cierre 27-ago 23:59:59. | F + B + A11Y + SEC | F2B |
| JUD-007 | OWNER_APPROVED / NOT IMPLEMENTED | Total servidor 0–100, precisión 4/2 HALF_UP; media de cuatro; faltante bloquea; igualdad a dos decimales señala empate. | Evaluación/historial futura | Payload cliente no altera total; señal no declara ganador. | U cálculo + F payload + SEC | F2B |
| JUD-008 | OWNER_APPROVED / NOT IMPLEMENTED | Cierre global y reapertura sólo hasta 20:00; envío/reenvío hasta 23:59:59 Hermosillo. | Middleware/Policy futura | Tests de segundo anterior/exacto/posterior en servidor. | U fecha + F + SEC | F2B |
| WIN-001 | DECISION | Declarar ganador es separado del cálculo. | `/admin/ganadores` | Resultado calculado no cambia proyecto a ganador automáticamente. | U + F | MVP |
| WIN-002 | DECISION | Declaración registra categoría, proyecto, actor, justificación y fecha. | Ganadores/auditoría | Decisión incompleta o sin permiso se rechaza. | F + SEC + UAT | MVP |
| WIN-003 | PARTIAL | Empate técnico se detecta por igualdad del consolidado redondeado a dos decimales; resolución, categoría desierta y premio siguen pendientes. | Ganadores/reglas | 02B sólo emite señal; nunca elige ganador ni usa azar. | U reglas + F + UAT | MVP/FUTURE |
| COM-001 | DECISION | Notificaciones transaccionales de eventos críticos en español, HTML/texto y marca dual. | `VerifyEmailNotification`, `ResetPasswordNotification`, `SubmissionReceived`, `resources/views/mail` | Verificación, reset y acuse generan plantilla profesional sin adjuntos/PII adicional. | `AuthMailHardeningTest` + revisión render | MVP |
| COM-002 | DECISION | Cola cifrada post-commit, reintento y recuperación de correo. | `ResilientMailDispatcher`, `database/default`, `failed_jobs`, reenvíos | Cuatro intentos con 60/300/900; falla de enqueue avisa sin 500 y permite reintentar; fallo SMTP queda observable. | Feature con dispatcher/Mail fake + OPS worker | MVP |
| COM-003 | DECISION | Usar `convocatoria@flowerflow.com.mx` para convocatoria y `privacidad@flowerflow.com.mx` para privacidad. | Plantillas/configuración | Remitente/reply-to y canal corresponden al propósito sin mezclar casos. | F con mail fake + revisión de configuración | MVP |
| COM-004 | PENDING | SMTP y entregabilidad SPF/DKIM/DMARC. | Configuración/runbook AWS | Dominio autentica envío y se monitorean rebotes. | OPS DNS + smoke correo | MVP |
| COM-005 | DECISION | Marketing masivo no está aprobado. | Comunicaciones | No existe envío promocional/masivo en MVP. | Revisión de rutas/permisos | OUT |
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
