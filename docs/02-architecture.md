# Arquitectura propuesta

> **Estado vigente M5 — 2026-08-18:** M4A conserva cuatro `primary` y dos `substitute` ilimitados. M5 está `GO LOCAL/TEST`: `BlindReviewPackageBuilder` proyecta sólo una allowlist desde el snapshot, una fila única por versión fija el hash canónico y un inventario separado sirve anexos privados con Policy ligada a la asignación activa. M6–M10 permanecen separados.

> **Adenda Fase 02B — 2026-08-18:** M1–M5 están implementados localmente. M5 añade `BlindReviewPackage`/`BlindReviewPackageFile`, builder determinista, integridad binaria, panel explícito y consumo juez por asignación. M6–M10 no están implementados/no autorizados; `P2B-BLOCK-001` permanece resuelto.

> **Implementación vigente — 2026-08-18:** monolito modular Laravel 12.64.0 con Fase 01/02A, exportaciones XLSX privadas y cierre ampliado. MySQL es el datastore local; timestamps se persisten UTC y el concurso conserva `America/Hermosillo`. Uploads usan discos privados y sólo salen por controller+Policy. El propietario registra `OWNER_CONFIRMED_DEPLOYED`; el SHA y la operación productiva siguen sin evidencia técnica independiente. Ver `docs/16-project-status-by-module-and-role-2026-08-17.md`.

> **Reconciliación jurídica v1.1 local:** `config/flowerflow.php` declara identidad y metadatos esperados; `legal_documents` conserva v1.0 y v1.1; la migración `2026_08_17_220000_publish_legal_documents_v1_1.php` hace vigente exactamente una v1.1 por tipo sin borrar historia; registro, perfil y envío fallan de forma cerrada si el catálogo activo no es determinístico. `legal_acceptances` conserva el `legal_document_id` realmente aceptado y no se reescribe. El propietario resolvió continuidad v1.0 sin reaceptación forzada ni backfill.

**Estado:** arquitectura parcialmente implementada; decisiones productivas pendientes

**Corte de información:** 2026-08-17
**Destino decidido:** una instancia AWS EC2 con Ubuntu donde ya opera Administratec, con aislamiento lógico y operativo.  
**Restricción:** esta fase no instala, configura ni despliega componentes.

## Decisión ejecutiva

Se recomienda un **monolito modular Laravel 12 renderizado en servidor**, con Blade y el shell Materialize 3.0.0 existente. MySQL es el sistema de registro; las colas, sesiones y cache comienzan en base de datos; un worker persistente y el scheduler se administran con systemd o Supervisor/cron según el inventario real de la EC2.

### Frontera implementada M4–M5

`Assignments` consulta elegibilidad sin modificarla, fija `SubmissionVersion`+`RubricVersion` y deriva cobertura desde filas append-only. `Conflicts` bloquea la asignación y crea un reemplazo ligado. M5 nunca entrega el snapshot crudo: genera una proyección allowlist separada, canónica e inmutable, más un inventario técnico sin nombres/rutas originales. El juez obtiene contenido sólo si su asignación exacta y el paquete siguen `active`; el reemplazo comparte el mismo paquete por `submission_version_id`.

La decisión minimiza piezas nuevas durante el plazo de 31 días. Mantiene las fronteras de dominio en código para poder extraer componentes después, sin pagar ahora el costo de una SPA, microservicios o Redis.

## Estado de partida histórico (2026-07-15)

- El repositorio es un skeleton Laravel 12 sin vendor ni composer.lock.
- Materialize/Pixinvent 3.0.0 aporta layouts front, blank, vertical y horizontal, pero no módulos Flower Flow.
- Sólo hay seis rutas GET de demostración, un modelo User y migraciones base.
- Login y registro son vistas sin backend.
- El MySQL local WSL2 fue verificado en 127.0.0.1:3306 con esquema flowerflow vacío.
- No existe configuración de AWS, CI/CD, worker, scheduler, backup ni observabilidad.

Véase [auditoría del repositorio](00-repository-audit.md).

## Contexto C4

~~~mermaid
flowchart LR
    Participant[Participante]
    Reviewer[Revisor de elegibilidad]
    Judge[Juez]
    Admin[Administrador de convocatoria]
    Privacy[Soporte de privacidad]
    Auditor[Auditor]

    FF[Flower Flow<br/>Laravel 12 + Blade + Materialize]
    DB[(MySQL<br/>esquema y usuario dedicados)]
    Files[(Archivos privados<br/>EBS o S3, decisión PENDING)]
    Mail[Proveedor SMTP<br/>PENDING]
    Ops[Servicios EC2<br/>web, worker, scheduler]

    Participant -->|HTTPS| FF
    Reviewer -->|HTTPS + 2FA| FF
    Judge -->|HTTPS; 2FA opcional| FF
    Admin -->|HTTPS + 2FA| FF
    Privacy -->|HTTPS + 2FA| FF
    Auditor -->|HTTPS + 2FA| FF
    FF --> DB
    FF --> Files
    FF -->|notificaciones sin PII sensible| Mail
    Ops --> FF
~~~

## Contenedores de ejecución

~~~mermaid
flowchart TB
    Internet[Internet]
    SG[AWS Security Group<br/>80/443; SSH restringido]
    Web[Nginx o Apache<br/>inventario PENDING]
    FPM[PHP-FPM 8.3 recomendado]
    App[Release Flower Flow<br/>Laravel 12]
    Worker[Worker de cola<br/>servicio dedicado]
    Scheduler[Scheduler<br/>timer o cron dedicado]
    DB[(MySQL 8<br/>DB y usuario Flower Flow)]
    Private[Storage privado<br/>fuera de public]
    Backup[Backup externo cifrado<br/>dump + snapshot]
    Logs[Logs y alertas<br/>destino PENDING]
    AdminApp[Administratec<br/>vhost y runtime propios]

    Internet --> SG --> Web
    Web --> FPM --> App
    App --> DB
    App --> Private
    Worker --> App
    Scheduler --> App
    DB --> Backup
    Private --> Backup
    App --> Logs
    AdminApp -.sin compartir secretos, sesiones o workers.-> Web
~~~

### Aislamiento obligatorio respecto de Administratec

| Recurso | Flower Flow | Regla |
|---|---|---|
| DNS/vhost | dominio o subdominio propio | Document root exclusivo a public |
| Ruta | release y current propios | No desplegar dentro del árbol de Administratec |
| Usuario Unix | usuario/grupo dedicado si es viable | Sólo storage y bootstrap/cache escribibles |
| PHP-FPM | pool dedicado recomendado | Límites y logs separados |
| Base | esquema y usuario exclusivos | Sin privilegios sobre Administratec |
| Secretos | archivo de entorno o secret store propio | Nunca copiar el archivo de Administratec |
| Sesión/cache | prefijo y cookie propios | Evitar colisiones de dominio |
| Worker/scheduler | unidades y colas propias | Reinicio y rollback independientes |
| Archivos/logs/backups | rutas y políticas propias | Acceso y restauración por aplicación |

El despliegue se bloquea hasta completar el preflight de [AWS EC2](07-deployment-aws-ec2.md).

## Componentes del monolito

~~~mermaid
flowchart LR
    WebUI[HTTP + Blade + JS por página]
    Identity[Identity & Access]
    Calls[Convocatorias & Categorías]
    Submissions[Proyectos & Versiones]
    Eligibility[Residencia & Elegibilidad]
    Judging[Asignaciones & Evaluación]
    Decisions[Ganadores & Publicación]
    Legal[Legal & Consentimientos]
    Comms[Notificaciones]
    Audit[Auditoría & Reportes]
    Privacy[Privacidad]
    Infra[Storage, Queue, Mail]

    WebUI --> Identity
    WebUI --> Calls
    WebUI --> Submissions
    Identity --> Eligibility
    Calls --> Submissions
    Submissions --> Eligibility
    Eligibility --> Judging
    Judging --> Decisions
    Legal --> Submissions
    Identity --> Audit
    Calls --> Audit
    Submissions --> Audit
    Eligibility --> Audit
    Judging --> Audit
    Decisions --> Audit
    Privacy --> Audit
    Comms --> Infra
    Submissions --> Infra
~~~

### Responsabilidades

| Módulo | Responsabilidad | No debe conocer |
|---|---|---|
| Identity & Access | login, verificación, 2FA, roles, suspensión, sesiones | contenido de comprobantes |
| Convocatorias | calendario, categorías, reglas activas, estado | resultados individuales |
| Proyectos | borradores, integrantes, folio, snapshots, envío | puntuaciones de otros proyectos |
| Elegibilidad | residencia, revisión, correcciones | rúbrica y ranking |
| Evaluación | asignaciones, conflicto, rúbrica, borrador, envío y revisiones append-only | PII estructurada, comprobantes, notas, aclaraciones e historial de admisibilidad |
| Decisiones | finalistas, ganador y publicación explícita | selección aleatoria |
| Legal | documentos versionados y aceptaciones | edición retroactiva de aceptaciones |
| Comunicaciones | eventos y resultados de entrega | documentos o PII de alto riesgo |
| Auditoría/reportes | eventos redactados y exportaciones autorizadas | contraseñas, tokens y contenido completo |
| Privacidad | seguimiento administrativo ARCO | afirmar resolución legal automática |

## Flujo técnico de un envío final

~~~mermaid
sequenceDiagram
    actor P as Participante
    participant C as SubmissionController
    participant A as SubmitSubmission Action
    participant DB as MySQL
    participant Q as Cola
    participant N as Notificación

    P->>C: POST envío + idempotency key
    C->>C: Form Request + Policy
    C->>A: usuario, proyecto, clave
    A->>DB: BEGIN y bloqueo del borrador
    A->>DB: validar convocatoria, email, elegibilidad y legales
    A->>DB: crear snapshot y transición auditable
    A->>DB: COMMIT
    A->>Q: evento SubmissionSubmitted
    Q->>N: correo con folio, sin anexos
    C-->>P: confirmación inmutable
~~~

La operación debe ser idempotente: una misma clave y proyecto devuelve el resultado anterior; no crea dos versiones ni dos folios.

## Stack recomendado

| Capa | Elección MVP | Razón |
|---|---|---|
| Backend | Laravel 12, PHP 8.3 | Compatible con el manifiesto y runtime local; soporte de seguridad hasta 2027-02-24 |
| UI | Blade + Bootstrap 5 + Materialize 3.0.0 | Reutiliza el activo comprado y reduce complejidad |
| JS | módulos por página con Vite | Evita una SPA y limita bundle |
| Datos | MySQL 8, InnoDB, utf8mb4 | Requisito del producto; transacciones e índices |
| Auth/RBAC | Fortify 1.37.2 + Spatie Permission 8.3.0 + Policies | M1 implementa `participant`, `reviewer`, `judge` y `admin` excluyentes, gates exactos y fallo cerrado; perfil/alta/suspensión/recovery de juez quedan para M2 y 2FA continúa opcional |
| Sesión/cache/cola | database en MVP | Sin dependencia Redis; operación simple y auditable |
| Archivos | EBS privado cifrado o S3 privado | Decisión de preflight; descarga sólo por Policy/controlador |
| Correo | Notifications/Mailables + SMTP PENDING | Plantillas multi-canal y pruebas con fakes |
| Procesos | systemd o Supervisor + scheduler | Worker persistente en EC2, separado de Administratec |
| Web/TLS | servidor real de EC2 + certificado | No cambiar Nginx/Apache hasta inventario |
| Observabilidad | logs rotados + health + alertas PENDING | Mínimo para operación del cierre |

### Evolución recomendada

- S3 privado con SSE-KMS es preferible si se aprueban costo, IAM y estrategia de borrado; EBS privado es el fallback del MVP.
- RDS MySQL es la recomendación productiva para separar el failure domain; MySQL en EC2 queda como fallback sujeto a capacidad, backup/restore y aceptación de riesgo.
- Redis sólo si las métricas reales justifican cache/colas y existe operación administrada.

## Frontend y contrato de la plantilla

- Layout front para sitio público; vertical autenticado para participante, jueces y backoffice.
- Branding en SCSS/JS propios de Flower Flow.
- Menús dinámicos autorizados en servidor; JSON estático sólo para navegación no sensible.
- DataTables server-side para listados administrativos; exportaciones sensibles en backend.
- bs-stepper y FormValidation sólo como UX; Form Requests siguen siendo autoridad.
- Elegir ApexCharts o Chart.js antes de implementar reportes.
- Elegir Notyf para avisos breves; Notiflix sólo si se aprueba un caso de bloqueo.
- Deshabilitar customizer, demos, Buy Now y metadatos Pixinvent en producción dentro del milestone 1.

## NFR y SLO propuestos

Estos objetivos son de aplicación; disponibilidad y recuperación requieren confirmar capacidad AWS.

| Objetivo | SLO MVP | Medición |
|---|---|---|
| Disponibilidad en ventana crítica | 99.5 por ciento mensual; 99.9 por ciento en últimas 48 h | monitor HTTPS cada minuto |
| Latencia pública | p95 menor a 800 ms, sin incluir uploads | métricas web |
| Listados admin | p95 menor a 2 s para filtros comunes | log de consultas y APM PENDING |
| Envío final | 99.9 por ciento sin duplicados | clave idempotente + auditoría |
| Correo transaccional | 95 por ciento en 5 min, excluyendo rechazo proveedor | jobs y failed_jobs |
| Recuperación | RPO 24 h inicial, meta 1 h en ventana crítica; RTO 4 h | simulacro de restore |
| Accesibilidad | WCAG 2.2 AA en flujos críticos | revisión automática y manual |
| Seguridad | cero acceso cruzado conocido y cero secretos en artefactos | tests negativos + escaneo |

## Alternativas rechazadas para el MVP

| Alternativa | Motivo |
|---|---|
| GoDaddy/cPanel | La decisión del usuario establece AWS EC2 Ubuntu |
| Microservicios | Demasiado costo operativo y transaccional para plazo/equipo |
| SPA React/Vue | No existe base SPA; duplica autorización y validación |
| Actualizar a Laravel 13 ahora | Cambio mayor sin lock/baseline y sin autorización |
| Redis obligatorio | Capacidad no confirmada y base de datos basta para escala inicial |
| Guardar archivos sensibles en public | Viola aislamiento y control de descarga |
| Ranking calculado en navegador | Manipulable; la puntuación es autoridad del servidor |
| Exportación sensible sólo en DataTables JS | Expone datos al cliente y limita auditoría |

## Decisiones pendientes

1. Inventario de EC2: Ubuntu, servidor web, PHP-FPM, MySQL, CPU/RAM/disco, acceso y procesos.
2. Variante/licencia de Materialize para el dominio.
3. Suspensión/revocación y gestión excepcional de permisos. Los roles exclusivos y 2FA opcional para juez ya están aprobados; Fortify/Spatie ya están instalados.
4. SMTP y política de entregabilidad.
5. Storage privado EBS frente a S3 para producción.
6. Fecha/hora de apertura, correcciones/retiro/equipos colaborativos y reglas legales; el cierre inclusivo ya es `2026-08-23 23:59:59 America/Hermosillo`.
7. RPO/RTO y propietario de operación.
8. M4A `GO LOCAL/TEST`: cuatro principales y dos sustitutos exclusivos sin límite, con selección manual. M5 también está `GO LOCAL/TEST` y conserva ese contrato al proyectar el paquete; M6 debe verificar ambos antes de añadir captura.

No se autoriza implementar los módulos o comportamientos aún pendientes ni desplegar hasta aprobar esas decisiones o aceptar los supuestos explícitos de un ExecPlan nuevo.

## Adenda arquitectónica Fase 02B aprobada — 2026-08-18

- Identidad agrega el rol exclusivo `judge`; una cuenta sin rol o multirol falla cerrada mediante `EnsureExclusiveBusinessRole`. M2 implementó alta directa por `admin`, sin invitaciones de juez.
- El shell juez ya está separado de participante y `/panel`; `FLOWERFLOW_EVALUATION_ENABLED=false` lo cierra por defecto y `/juez` sólo muestra un estado vacío sin propuestas ni PII.
- El dominio vigente usa `judge_profiles` y rúbrica/versiones M3; paquetes ciegos, asignaciones, conflictos, evaluaciones y revisiones append-only siguen futuros. Nunca reutilizará el snapshot crudo como DTO de juez.
- M3 normaliza `rubric_versions`/`rubric_criteria`: versión positiva por competencia, estados `draft|active|superseded`, criterios exactos, descripciones nulas, checks/FKs/índices y una sola activa mediante transacción, locks y `active_slot`. Una activa/sustituida no se actualiza o elimina.
- La proyección permite campos sustantivos y anexos evaluables, pero elimina PII estructurada, residencia, notas internas, aclaraciones, historial, nombres expuestos y metadatos técnicos. La autoidentificación dentro del contenido es un riesgo aceptado, no una promesa de anonimización total.
- El servidor calcula y persiste puntajes; la consolidación sólo existe con cuatro evaluaciones válidas. Ganadores/resultados no forman parte del componente Evaluación.
- La edición administrativa conserva actor real, juez sujeto, razón y revisión previa; no suplanta ni sobrescribe la autoría histórica.
- Todas las migraciones futuras serán aditivas, sin backfill de asignaciones ni cambios inferidos sobre las más de 50 propuestas existentes.

## Adenda arquitectónica — Fase 02A, 2026-07-16

- Se conserva el monolito Laravel 12 con Blade, servicios/acciones y transacciones; no se agrega API, SPA, Redis ni dependencia productiva.
- `EligibilityReview` es un agregado separado de `Submission`, ligado uno a uno a `SubmissionVersion`.
- Los eventos de revisión, respuestas de aclaración y logs de auditoría son inmutables en aplicación.
- `residency` y `clarifications` son discos privados dedicados con `serve=false`; ningún binario se expone por `public` o `storage:link`.
- Policies y permisos granulares autorizan recursos y descargas; el filtrado no depende de botones ocultos.
- `AdmissibilityUpdate` reutiliza el dispatcher, cola cifrada, `ShouldQueueAfterCommit`, timeout, reintentos y backoff existentes.
- `FLOWERFLOW_ADMISSIBILITY_REVIEW_ENABLED=false` oculta rutas y menús sin eliminar datos.
- No se modifica el core de Pixinvent/Materialize ni `public/build` manualmente.
