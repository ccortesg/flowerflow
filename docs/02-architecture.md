# Arquitectura propuesta

**Estado:** propuesta para aprobación  
**Corte de información:** 2026-07-15  
**Destino decidido:** una instancia AWS EC2 con Ubuntu donde ya opera Administratec, con aislamiento lógico y operativo.  
**Restricción:** esta fase no instala, configura ni despliega componentes.

## Decisión ejecutiva

Se recomienda un **monolito modular Laravel 12 renderizado en servidor**, con Blade y el shell Materialize 3.0.0 existente. MySQL es el sistema de registro; las colas, sesiones y cache comienzan en base de datos; un worker persistente y el scheduler se administran con systemd o Supervisor/cron según el inventario real de la EC2.

La decisión minimiza piezas nuevas durante el plazo de 31 días. Mantiene las fronteras de dominio en código para poder extraer componentes después, sin pagar ahora el costo de una SPA, microservicios o Redis.

## Estado de partida

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
    Judge -->|HTTPS + 2FA| FF
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
| Evaluación | asignaciones, conflicto, rúbrica, borrador y envío | PII y comprobantes |
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
| Auth/RBAC | mecanismos Laravel + paquete RBAC PENDING | Debe aprobarse Spatie Permission/Fortify o alternativa antes de instalar |
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
3. Paquetes de autenticación y RBAC.
4. SMTP y política de entregabilidad.
5. Storage privado EBS frente a S3 para producción.
6. Fecha/hora de apertura y cierre, equipo, límites y reglas legales.
7. RPO/RTO y propietario de operación.

No se autoriza implementación hasta aprobar esas decisiones o aceptar los supuestos explícitos del ExecPlan.
