# Modelo de datos preliminar

## Adenda Fase 01 implementada — 2026-07-15

Tablas nuevas: `competitions`, `categories`, `participant_profiles`, `legal_documents`, `legal_acceptances`, `teams`, `team_members`, `submissions`, `submission_files`, `submission_external_links`, `submission_versions`, `submission_events` y las tablas RBAC de Spatie. `users.public_id` y las entidades expuestas usan ULID público.

Invariantes de base: `competition+user+category` único, folio e idempotency key únicos, snapshot `submission+version` único, links `submission+kind` únicos. Los perfiles no se duplican dentro de propuestas; el snapshot copia el estado de envío. Archivos conservan actor, disk/path privado, nombre original/servidor, tipo de formato, MIME, extensión, bytes y SHA-256. Legales conservan código, versión, vigencia, obligatoriedad, hash y path; aceptaciones conservan propósito independiente, valor, versión, UTC, IP, agente y contexto.

El cierre sembrado es `2026-08-16 06:59:59 UTC`, equivalente a `2026-08-15 23:59:59 America/Hermosillo`. `opens_at` queda nullable/configurable porque no existe hora jurídica aprobada.

**Estado:** hipótesis validable; no representa migraciones ejecutadas.  
**Motor:** MySQL 8, InnoDB, utf8mb4.  
**Tiempo:** persistencia UTC; presentación y reglas en America/Hermosillo.

## Criterios de diseño

- PK bigint autoincremental para joins eficientes y public_id ULID único en entidades expuestas.
- Foreign keys e índices explícitos; RESTRICT en evidencia histórica y CASCADE sólo en hijos sin significado independiente.
- PII de participantes separada de evaluación y de documentos de residencia.
- Snapshots JSON sólo para preservar la versión enviada; datos operativos consultables permanecen normalizados.
- Estados con enums respaldados en código y strings restringidos en base cuando sea viable.
- Sin soft delete indiscriminado. La retención y anonimización se decide por entidad.
- Comprobantes y anexos guardan metadatos; el binario nunca vive en MySQL ni bajo public.

## ERD

~~~mermaid
erDiagram
    USERS ||--o| PARTICIPANT_PROFILES : has
    USERS ||--o| JUDGE_PROFILES : has
    USERS ||--o{ SUBMISSIONS : owns
    USERS ||--o{ SUBMISSION_MEMBERS : joins
    USERS ||--o{ LEGAL_ACCEPTANCES : accepts
    USERS ||--o{ AUDIT_LOGS : acts

    COMPETITIONS ||--o{ CATEGORIES : contains
    COMPETITIONS ||--o{ SUBMISSIONS : receives
    COMPETITIONS ||--o{ LEGAL_DOCUMENTS : governs
    COMPETITIONS ||--o{ RUBRICS : defines

    CATEGORIES ||--o{ SUBMISSIONS : classifies
    CATEGORIES ||--o{ RUBRICS : customizes
    CATEGORIES ||--o{ WINNER_DECISIONS : awards

    SUBMISSIONS ||--o{ SUBMISSION_VERSIONS : snapshots
    SUBMISSIONS ||--o{ SUBMISSION_MEMBERS : includes
    SUBMISSIONS ||--o{ SUBMISSION_FILES : attaches
    SUBMISSIONS ||--o{ RESIDENCY_DOCUMENTS : proves
    SUBMISSIONS ||--o{ ELIGIBILITY_REVIEWS : reviewed
    SUBMISSIONS ||--o{ SUBMISSION_STATUS_HISTORIES : transitions
    SUBMISSIONS ||--o{ JUDGE_ASSIGNMENTS : assigned
    SUBMISSIONS ||--o{ WINNER_DECISIONS : selected

    LEGAL_DOCUMENTS ||--o{ LEGAL_ACCEPTANCES : versioned

    JUDGE_PROFILES ||--o{ JUDGE_ASSIGNMENTS : receives
    JUDGE_ASSIGNMENTS ||--o| CONFLICT_DECLARATIONS : may_have
    JUDGE_ASSIGNMENTS ||--o| EVALUATIONS : produces
    RUBRICS ||--o{ RUBRIC_CRITERIA : contains
    RUBRICS ||--o{ EVALUATIONS : grades_with
    EVALUATIONS ||--o{ EVALUATION_SCORES : contains
    RUBRIC_CRITERIA ||--o{ EVALUATION_SCORES : scores

    USERS ||--o{ PRIVACY_REQUESTS : requests
    USERS ||--o{ INTERNAL_NOTES : writes
    SUBMISSIONS ||--o{ INTERNAL_NOTES : noted
~~~

Las tablas de roles y permisos de un paquete aprobado se conectan con users y no se expanden en el diagrama.

## Diccionario de tablas

### Identidad

| Tabla | Campos clave | Índices/constraints | Borrado |
|---|---|---|---|
| users | id, public_id, name, email, email_verified_at, password, status, suspended_at, last_login_at | unique public_id/email; index status | anonimizar tras cierre de retención; RESTRICT si hay evidencia |
| participant_profiles | user_id, phone_encrypted, birth_date, municipality, privacy_flags | unique user_id; index municipality | ligado al proceso ARCO |
| judge_profiles | user_id, expertise, availability_status, blinded_name | unique user_id; index availability | conservar mínimo para trazabilidad; anonimizar después |
| invitations | public_id, email, role, token_hash, expires_at, accepted_at, inviter_id | unique token_hash; index email/expires_at | purga corta tras expiración |

No guardar edad calculada; derivarla de birth_date en la fecha definida por las reglas.

### Convocatoria

| Tabla | Campos clave | Índices/constraints | Borrado |
|---|---|---|---|
| competitions | public_id, edition, slug, title, timezone, opens_at, closes_at, status, results_enabled | unique slug/edition; index status, opens_at, closes_at | archivar; no borrar mientras haya envíos |
| categories | public_id, competition_id, slug, name, description, sort_order, active | unique competition_id+slug; index active/order | RESTRICT con envíos |
| settings | key, typed_value, type, is_public, updated_by | unique key | historial para cambios críticos |

Se prefiere competitions sobre calls para evitar ambigüedad con llamadas/comunicaciones.

### Proyectos y elegibilidad

| Tabla | Campos clave | Índices/constraints | Borrado |
|---|---|---|---|
| submissions | public_id, competition_id, category_id, owner_id, folio, title, summary, status, current_version_id, submitted_at | unique public_id/folio; índices owner+status, category+status, competition+submitted_at | archivar; anonimizar según retención |
| submission_versions | submission_id, version_no, snapshot, content_hash, created_by, reason, submitted_at | unique submission_id+version_no/content_hash | inmutable; RESTRICT |
| submission_members | submission_id, user_id nullable, email, member_role, invitation_status, accepted_at, eligibility_status | unique submission_id+email; index eligibility | anonimizar/purgar invitados no aceptados |
| submission_files | public_id, submission_id, version_id, uploader_id, kind, visibility, disk, path, original_name, mime, size, sha256, scan_status | unique public_id/path; index submission+kind+visibility | borrar binario por retención y conservar evidencia mínima |
| residency_documents | public_id, submission_id, subject_user_id, uploader_id, storage metadata, review_status, reviewed_by, reviewed_at, expires_at | índices submission/status/subject; path unique | retención corta; nunca visible a jueces |
| eligibility_reviews | submission_id, version_id, reviewer_id, decision, reason_code, notes_redacted, decided_at | index decision/decided_at/reviewer | conservar decisión; redactar notas |
| submission_status_histories | submission_id, from_status, to_status, actor_id, reason, occurred_at, correlation_id | index submission+occurred_at/to_status | inmutable |
| internal_notes | submission_id, author_id, scope, body_encrypted, created_at | index submission/scope | retención administrativa; nunca a juez/participante salvo scope |

El snapshot contiene sólo los campos de la versión; no duplica tokens, contraseñas ni binarios. Su esquema y canonicalización deben versionarse para que content_hash sea reproducible.

### Legal

| Tabla | Campos clave | Índices/constraints | Borrado |
|---|---|---|---|
| legal_documents | public_id, competition_id nullable, type, version, effective_at, content_path/body, sha256, status | unique type+version+competition; index effective/status | inmutable al publicarse |
| legal_acceptances | user_id, legal_document_id, context, accepted_at, ip_hash_or_prefix, user_agent_redacted | unique user+document+context; index accepted_at | conservar evidencia proporcional |

### Jueces y evaluación

| Tabla | Campos clave | Índices/constraints | Borrado |
|---|---|---|---|
| judge_assignments | public_id, submission_id, judge_profile_id, status, assigned_by, assigned_at, due_at, closed_at | unique submission+judge; index judge+status/due | void, no delete, al iniciar evaluación |
| conflict_declarations | assignment_id, type, explanation_redacted, declared_at, resolution, resolved_by, resolved_at | unique assignment; index resolution | inmutable con resolución adicional |
| rubrics | public_id, competition_id, category_id nullable, version, name, status, total_weight | unique scope+version; index status | inmutable al activarse |
| rubric_criteria | rubric_id, code, name, description, weight, min_score, max_score, sort_order | unique rubric+code/order | RESTRICT con scores |
| evaluations | public_id, assignment_id, rubric_id, status, total_score, general_comment, started_at, submitted_at, reopened_by | unique assignment; index status/submitted_at | void, no delete |
| evaluation_scores | evaluation_id, criterion_id, score, comment | unique evaluation+criterion | ligado a evaluación |
| winner_decisions | public_id, category_id, submission_id nullable, decision_type, justification, decided_by, decided_at, published_at | unique category+decision_type activo; index dates | revocar con nueva evidencia, no sobrescribir |

total_score se recalcula en servidor desde scores, límites y pesos de la rúbrica activa. No se acepta un total enviado por el navegador.

### Operación

| Tabla | Campos clave | Índices/constraints | Borrado |
|---|---|---|---|
| audit_logs | occurred_at, actor_id nullable, action, entity_type/id, request_id, ip_hash_or_prefix, before_redacted, after_redacted, metadata_redacted | índices occurred_at, actor+date, entity+id, action | append-only; retención aprobada |
| privacy_requests | public_id, requester_id nullable, channel, request_type, status, received_at, due_at, closed_at, evidence_path | index status/due/type | política legal PENDING |
| contact_messages | public_id, email, subject, body_encrypted, status, assigned_to, received_at | index status/date | sólo si se aprueba formulario |
| communication_deliveries | notification_type, recipient_user_id, event_id, channel, status, attempts, provider_id, sent_at, failed_at, error_code | unique event+recipient+type; index status/date | no guardar cuerpo completo |
| export_jobs | public_id, requested_by, report_type, filters_redacted, status, path, expires_at, completed_at | index user/status/expires | purgar archivo pronto; conservar evento |

Laravel aporta notifications, jobs, job_batches, failed_jobs, sessions, cache y cache_locks. Sus payloads también deben evitar PII innecesaria.

## Máquinas de estado

### Convocatoria

Se elimina eligibility_review como estado global: la revisión de cada proyecto puede iniciar después del cierre sin convertir la convocatoria en un pseudoestado operativo.

~~~mermaid
stateDiagram-v2
    [*] --> draft
    draft --> scheduled: calendario y legales aprobados
    scheduled --> open: abre_at alcanzado o acción autorizada
    open --> closed: cierra_at alcanzado o cierre autorizado
    closed --> judging: elegibilidad suficiente y rúbrica activa
    judging --> results_published: decisión y publicación separadas
    results_published --> archived
    closed --> archived: convocatoria cancelada documentada
~~~

### Proyecto

assigned_to_judges y under_evaluation son hechos derivados de asignaciones; no se guardan como estados del proyecto para evitar desincronización.

~~~mermaid
stateDiagram-v2
    [*] --> draft
    draft --> submitted: envío final válido
    submitted --> under_eligibility_review
    under_eligibility_review --> correction_requested
    correction_requested --> submitted: nueva versión
    under_eligibility_review --> eligible
    under_eligibility_review --> ineligible
    eligible --> evaluated: evaluaciones requeridas completas
    evaluated --> finalist
    evaluated --> not_selected
    finalist --> winner: decisión administrativa
    finalist --> not_selected
    draft --> withdrawn
    submitted --> withdrawn: regla aprobada
    ineligible --> archived
    not_selected --> archived
    winner --> archived
~~~

### Evaluación/asignación

~~~mermaid
stateDiagram-v2
    [*] --> assigned
    assigned --> conflict_declared
    conflict_declared --> voided: conflicto confirmado
    assigned --> in_progress
    in_progress --> submitted
    submitted --> reopened: acción administrativa auditada
    reopened --> in_progress
    assigned --> voided: reasignación justificada
~~~

## Reglas de transición

| Transición | Actor | Precondiciones | Efectos/auditoría | Reversible |
|---|---|---|---|---|
| draft a submitted | participante propietario | email verificado, convocatoria abierta, elegibilidad mínima, legales vigentes, archivos válidos | snapshot, folio, hash, evento y correo | no; corrección crea versión |
| submitted a correction_requested | revisor | Policy, versión revisada, razón | estado, plazo, nota redactada y notificación | sí mediante nueva versión |
| review a eligible/ineligible | revisor | comprobantes autorizados y decisión completa | decisión, historial y notificación sin documento | reabrible por permiso especial |
| eligible a evaluated | sistema | número requerido de evaluaciones submitted y no void | recalcular agregados sin publicar ranking | derivada/recalculable |
| evaluated a finalist | call_admin | regla de selección aprobada | decisión auditada | sí con justificación |
| finalist a winner | permiso winners.declare | categoría, ausencia de conflicto, justificación | winner_decision separada | revocación, no overwrite |
| winner a publicación | permiso winners.publish | consentimiento y doble confirmación | published_at y bitácora | despublicar auditado |
| assigned a conflict_declared | juez | asignación propia abierta | bloquea edición de scores | resolución por admin |
| in_progress a submitted | juez | rúbrica completa, calendario abierto, sin conflicto | total servidor, lock y confirmación | sólo reopen autorizado |

## Invariantes de base y aplicación

1. Un juez sólo puede consultar assignment con su judge_profile_id.
2. Una descarga de juez exige visibility judge y excluye kind residency.
3. Un submission enviado siempre apunta a una versión inmutable.
4. Una aceptación legal referencia exactamente la versión vigente al enviar.
5. Un criterio no admite score fuera de min_score y max_score.
6. El total de evaluación se calcula dentro de la misma transacción de envío.
7. WinnerDecision no nace del cálculo ni de una selección aleatoria.
8. Folio, public_id, claves idempotentes y hashes son únicos.
9. closes_at se evalúa con reloj del servidor y zona de la convocatoria; se persiste UTC.
10. Una excepción de deadline exige permiso, razón, actor y audit log.

## Índices prioritarios

- submissions: competition_id+status+submitted_at, category_id+status, owner_id+updated_at, folio.
- judge_assignments: judge_profile_id+status+due_at, submission_id+status.
- evaluations: status+submitted_at y rubric_id.
- residency_documents: review_status+created_at; subject_user_id.
- audit_logs: occurred_at, actor_id+occurred_at, entity_type+entity_id+occurred_at.
- communication_deliveries y jobs: status/queue+available_at.
- privacy_requests: status+due_at.

Los planes EXPLAIN y volumen sintético se validan antes de fijar índices adicionales para DataTables.

## PII, cifrado y visibilidad

| Dato | Clasificación | Control |
|---|---|---|
| Email/nombre/teléfono | PII | permiso mínimo; teléfono cifrado; máscara en listados |
| Fecha de nacimiento | PII alta | cifrado o separación; no exponer a jueces |
| Comprobante de residencia | documento sensible | storage privado, cifrado de volumen, descarga auditada |
| Propuesta/anexos | confidencial hasta publicación | visibility explícita por archivo |
| Scores/comentarios | evaluación confidencial | juez propio y administración autorizada |
| Password/token | secreto | hash; nunca logs/snapshots |
| IP/user agent | identificador técnico | minimización, hash/prefijo y retención corta |

## Retención propuesta

Los plazos requieren aprobación legal; son defaults operativos, no afirmaciones de cumplimiento.

| Entidad | ASSUMPTION | Fin de periodo |
|---|---|---|
| Invitaciones expiradas | 90 días | purga |
| Comprobantes no ganadores | 90 días tras cierre firme | borrar binario y conservar decisión mínima |
| Anexos no ganadores | 12 meses | borrar o anonimizar según bases |
| Datos de ganadores | mientras exista archivo público autorizado | retirar al revocar consentimiento cuando proceda |
| Evaluaciones/auditoría | 24 meses | anonimizar actor/PII si ya no es necesaria |
| Exportaciones generadas | 24 horas | borrar archivo; conservar evento |
| Jobs/failed_jobs | 30/90 días | purga redactada |
| Sesiones/reset tokens | expiración técnica | purga automática |
| Solicitudes de privacidad | PENDING legal | cierre controlado |
| Backups | 35 días diario + 12 mensuales PENDING | expiración cifrada |

## Validación antes de migraciones

- Aprobar nombres, estados, límites, reglas de equipos, documentos, retención y publicación.
- Confirmar paquete RBAC y sus tablas.
- Probar migraciones en copia vacía y rollback sin datos reales.
- Revisar foreign keys, uniques, charset/collation y timezone.
- Generar dataset sintético para EXPLAIN y pruebas de listados.
- Ejecutar tests de acceso cruzado antes de aceptar storage o Policies.

Ninguna tabla de dominio fue creada en esta fase.

## Adenda implementada — Fase 02A, 2026-07-16

La frase histórica anterior describe la fase documental original y queda sustituida para el código actual por la migración aditiva `2026_07_16_120000_create_admissibility_review_tables.php`.

| Tabla | Propósito e invariantes principales |
|---|---|
| `eligibility_reviews` | una por propuesta y versión enviada; reviewer/resolutor opcionales; estado, motivo público, nota interna cifrada y fechas UTC |
| `eligibility_review_events` | transiciones y actor append-only; registra versión y contexto redactado |
| `clarification_requests` | mensaje, estado, fecha límite opcional, respuesta/cierre |
| `clarification_responses` | texto append-only, máximo 2,000 caracteres en aplicación |
| `clarification_response_files` | adjuntos privados con ULID, nombre interno aleatorio, MIME, tamaño y SHA-256 |
| `residency_document_requests` | sujeto representante o integrante, estado, revisión y fechas/base candidata de retención |
| `residency_documents` | tipo jurídico, explicación de equivalente y metadatos privados; sin URL pública |
| `audit_logs` | actor, acción, entidad, hashes de IP/agente, metadata mínima y fecha UTC; append-only |

Los estados respaldados viven en `app/Enums`. `Submission.status` no cambió. El snapshot de `submission_versions` rechaza actualización y borrado mediante el modelo. Las migraciones no hacen backfill: `flowerflow:admissibility-backfill` lo realiza idempotentemente.

Retención: al verificar se calcula `retention_due_at` 90 días después de la fecha base; si existe aclaración relacionada cerrada, se usa la base aplicable más reciente. El comando de reporte es dry-run y la eliminación permanece bloqueada hasta conocer ganadores.
