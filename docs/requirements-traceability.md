# Matriz de trazabilidad de requisitos — Flower Flow 2026

**Fecha de corte:** 2026-07-15  
**Estado:** baseline de planificación; ninguna fila implica implementación terminada  
**Convenciones:** `DECISION` confirmado; `ASSUMPTION` supuesto de trabajo; `PENDING` requiere información/aprobación.

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
| SRC-001 | PENDING | Recuperar introducción y módulos 1–6 omitidos. | Todos los docs; `docs/10-open-questions.md` | Como producto, quiero reconciliar la fuente completa; el diff no deja contradicciones silenciosas. | Revisión documental + aprobación | PLAN |
| CAL-001 | DECISION | Convocatoria con edición, slug, fechas, zona y estado. | Público/admin; inicio, convocatoria, calendario | Administrador configura edición; público ve estado y fechas correctas. | U + F + B + UAT | MVP |
| CAL-002 | ASSUMPTION | Una sola convocatoria activa en MVP, modelo extensible. | Convocatoria/configuración | No se mezclan proyectos entre ediciones; restricción documentada. | U + F | MVP |
| CAL-003 | ASSUMPTION | Estados persistidos `draft/scheduled/open/closed/judging/results_published/archived`; elegibilidad se deriva de proyectos. | Servicio de estados; admin | Sólo transiciones válidas por actor/precondición y no hay pseudoestado global desincronizado. | U + F | MVP |
| CAL-004 | PENDING | Fecha de apertura y hora exacta de cierre. | Inicio, calendario, scheduler | Cierre usa valor aprobado en `America/Hermosillo`. | U fecha/zona + B deadline | MVP |
| CAL-005 | DECISION | No aceptar envío después del cierre salvo excepción auditada. | Wizard/envío; admin | Envío ordinario falla después del deadline; excepción exige permiso/razón. | U + F concurrencia + SEC | MVP |
| PUB-001 | ASSUMPTION | Sitio público con bases, categorías, proceso, FAQ y documentos. | `/`, `/convocatoria`, `/categorias`, `/como-participar`, `/preguntas-frecuentes`, `/documentos` | Visitante comprende requisitos y siguiente acción sin autenticarse. | B + A11Y + UAT | MVP |
| PUB-002 | DECISION | Resultados públicos desactivados por defecto. | `/resultados`; admin ganadores | Sin activación autorizada no se expone resultado. | F + B + SEC | MVP-R |
| PUB-003 | DECISION | Publicar sólo campos autorizados tras confirmación. | Resultados/archivo 2026 | Preview y salida pública omiten PII/documentos no consentidos. | F + B + SEC + UAT | MVP-R |
| PUB-004 | DECISION | Galería pública no pertenece al MVP. | `/proyectos` | No consume ruta crítica; requiere consentimiento/moderación posterior. | Revisión de alcance | F2 |
| IAM-001 | ASSUMPTION | Registro, login, logout y restablecimiento. | `/registro`, `/login`, `/contrasena/*` | Usuario crea y recupera cuenta sin enumeración de correo. | F + B + SEC | MVP |
| IAM-002 | DECISION | Verificación de correo antes de enviar. | Verificación; wizard | Usuario no verificado puede guardar borrador pero no enviar. | F + B | MVP |
| IAM-003 | DECISION | Roles/permisos de mínimo privilegio y Policies por recurso. | Todas las rutas autenticadas | Cada rol sólo accede a recursos autorizados incluso por URL directa. | F matriz RBAC + SEC IDOR | MVP |
| IAM-004 | DECISION | 2FA y confirmación de contraseña en acciones privilegiadas. | Cuenta/admin | Rol privilegiado no accede/ejecuta acción crítica sin controles. | F + B + SEC | MVP |
| IAM-005 | DECISION | Rate limit, sesión revocable y respuestas no enumerables. | Auth/contacto/uploads | Abuso se limita y suspensión revoca sesiones. | F + SEC | MVP |
| IAM-006 | ASSUMPTION | Invitaciones firmadas, expirables y de un uso. | Equipo/jueces | Token alterado, vencido o reutilizado se rechaza. | U + F + SEC | MVP si equipos |
| ELG-001 | DECISION | Perfil mínimo y declaración de elegibilidad. | `/participante/perfil` | Participante conoce finalidad de cada dato y completa mínimos. | F + B + A11Y + UAT | MVP |
| ELG-002 | DECISION | Comprobante de residencia separado y privado. | Perfil/residencia; admin elegibilidad | Sólo revisor autorizado accede; juez nunca lo ve. | F descarga + SEC + B por roles | MVP |
| ELG-003 | PENDING | Allowlist, vigencia y retención de comprobantes. | Upload/política de datos | Sólo formatos aprobados; retención ejecutable y documentada. | U + F archivos + OPS eliminación | MVP |
| ELG-004 | DECISION | Revisión registra decisión, razones, actor, fecha y versión. | `/admin/elegibilidad/{id}` | Revisor decide sobre snapshot fijo y deja historial. | F + UAT | MVP |
| ELG-005 | DECISION | Solicitud de corrección y reenvío controlado. | Seguimiento participante/admin | Participante ve motivo/plazo y genera nueva versión cuando aplica. | U estados + F + B | MVP |
| SUB-001 | DECISION | Borrador y autosave recuperable. | Mis proyectos/wizard | Edición persiste sin envío y comunica guardado/error. | U + F + B reconexión | MVP |
| SUB-002 | ASSUMPTION | Wizard por pasos con resumen final. | `/participante/proyectos/{id}/editar/*` | Usuario completa pasos, vuelve atrás y corrige desde resumen. | B móvil/escritorio + A11Y | MVP |
| SUB-003 | PENDING | Reglas de equipo, máximo e invitaciones/aceptaciones. | Equipo/wizard | Permisos y envío reflejan regla aprobada. | U + F + B + SEC | MVP recortable |
| SUB-004 | PENDING | Límites de proyectos, texto y anexos. | Wizard/configuración | Límites centralizados se validan en servidor y UI. | U límites + F boundary + B | MVP |
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
| JUD-001 | DECISION | Dashboard de asignaciones propias por estado. | `/juez` | Juez ve sólo pendientes, borrador y finalizadas propias. | F scope + B + SEC | MVP |
| JUD-002 | DECISION | Vista anónima y anexos autorizados. | `/juez/asignaciones/{id}` | Respuesta no contiene identidad ni residencia cuando evaluación es ciega. | F payload + B + SEC | MVP |
| JUD-003 | DECISION | Conflicto bloquea evaluación. | Conflicto/evaluación | Tras declarar conflicto no se puede puntuar/enviar; admin puede reasignar. | U estados + F + B | MVP |
| JUD-004 | PENDING | Número de jueces, modalidad ciega y reglas de asignación. | Admin asignaciones | Asignaciones cumplen cantidad/capacidad aprobada y no duplican. | U algoritmo/reglas + F | MVP |
| JUD-005 | PENDING | Rúbrica final versionada con pesos, rangos y mínimo. | Instrucciones/rúbrica/evaluación | Evaluación usa versión fija; cambios no alteran envíos previos. | U cálculo/versionado + F | MVP |
| JUD-006 | DECISION | Borrador de evaluación y confirmación antes de enviar. | `/juez/asignaciones/{id}/evaluacion` | Juez recupera borrador y revisa resumen antes de cierre. | F + B + A11Y | MVP |
| JUD-007 | DECISION | Total calculado en servidor; sin ranking global para juez. | Evaluación/historial | Manipulación cliente no altera total; juez no recibe ranking. | U cálculo + F payload + SEC | MVP |
| JUD-008 | DECISION | Acceso de juez cierra por calendario con excepción auditada. | Middleware/Policy/evaluación | Después del cierre no edita salvo reapertura autorizada. | U fecha + F + SEC | MVP |
| WIN-001 | DECISION | Declarar ganador es separado del cálculo. | `/admin/ganadores` | Resultado calculado no cambia proyecto a ganador automáticamente. | U + F | MVP |
| WIN-002 | DECISION | Declaración registra categoría, proyecto, actor, justificación y fecha. | Ganadores/auditoría | Decisión incompleta o sin permiso se rechaza. | F + SEC + UAT | MVP |
| WIN-003 | PENDING | Empates, recusaciones, categoría desierta y premio. | Ganadores/reglas | Flujo implementa sólo regla aprobada y nunca azar. | U reglas + F + UAT | MVP |
| COM-001 | DECISION | Notificaciones transaccionales de eventos críticos. | Auth, proyectos, elegibilidad, jueces, resultados | Evento genera destinatario/plantilla correctos sin PII sensible. | F con fakes + revisión plantilla | MVP |
| COM-002 | DECISION | Cola, reintento e idempotencia de correo. | Jobs/failed jobs | Reintento no duplica efectos; fallo queda observable. | U + F queue fake + OPS | MVP |
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
| SEC-006 | PENDING | CSP con nonces/hashes compatible con scripts de plantilla. | Layouts/headers | Política report-only se valida antes de enforcement sin romper flujos. | Browser console + SEC | MVP-R |
| LEG-001 | DECISION | Documentos y aceptaciones versionadas. | Legal/cuenta/envío | Se conserva documento/hash/version aceptada en el contexto correcto. | U + F + auditoría | MVP |
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
| ENV-004 | PENDING | Confirmar que la base local es exclusiva/desechable. | MySQL local | No se ejecutan migraciones/seeders hasta confirmar backup/uso. | Aprobación del propietario + inventario read-only | PLAN |
| DEP-001 | DECISION | Producción en AWS EC2 Ubuntu coexistente con `administratec`. | Runbook/ADR AWS | Arquitectura y riesgos reflejan el destino real. | Revisión documental | PLAN |
| DEP-002 | DECISION | Aislar vhost, ruta, usuario, env, DB, storage, cache/sesión, procesos y logs. | EC2 | Flower Flow no comparte secretos ni namespace operativo; fallos no colisionan por configuración. | OPS preflight + smoke cruzado | MVP |
| DEP-003 | PENDING | Inventariar Ubuntu, CPU/RAM/disco, web server, PHP-FPM y extensiones. | EC2 | Laravel 12/PHP 8.2+ y carga prevista son compatibles. | Comandos read-only + matriz de versiones | PLAN |
| DEP-004 | PENDING | Definir DB productiva y backups. | EC2/RDS por decidir | Esquema/usuario exclusivos, cifrado, RPO/RTO y restauración probada. | Backup/restore drill | MVP |
| DEP-005 | PENDING | Definir dominio, DNS y TLS. | Vhost/certificado | HTTPS canónico, headers y renovación monitoreada. | OPS DNS/TLS + browser smoke | MVP |
| DEP-006 | PENDING | Scheduler y workers propios. | `systemd`/Supervisor/cron | Jobs, reintentos y cierre funcionan sin interferir con `administratec`. | OPS queue/scheduler smoke | MVP |
| DEP-007 | DECISION | Build Vite controlado; no editar `public/build` manualmente. | CI/release | Artefacto reproducible corresponde al commit/release. | `npm run build` + checksum/revisión | MVP |
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
npm run build
composer audit
npm audit --omit=dev
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
