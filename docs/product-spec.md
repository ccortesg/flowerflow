# Especificación de producto — Flower Flow 2026

> **Adenda autoritativa Fase 01 — 2026-07-15:** el alcance aprobado es recepción local/test, no el MVP completo histórico. Cierre inclusivo: 15 de agosto de 2026 a las 23:59:59 en `America/Hermosillo`; categorías exactas: Movilidad con Flow, Hermosillo Florece y Mi familia, mi mascota; participación individual/equipo hasta cinco; una propuesta por categoría y tres totales. Registro/recepción/resultados están apagados por defecto. Evaluación, jueces, ganadores y publicación permanecen fuera. Ver `docs/01-functional-scope.md` y `docs/legal-change-log.md`.

**Fecha de corte:** 2026-07-15  
**Estado:** planificación; no autoriza implementación ni despliegue  
**Propósito:** consolidar el producto que debe construirse, sus límites y las decisiones que requieren aprobación.

## Convenciones de decisión

- **DECISION:** dato confirmado por el solicitante, por el alcance recibido o por evidencia directa del repositorio.
- **ASSUMPTION:** supuesto de trabajo recomendado para poder planificar; requiere validación antes de implementar la parte afectada.
- **PENDING:** dato, aprobación o evidencia aún no disponible.

## Integridad del insumo

> **PENDING — especificación de origen incompleta:** el texto recibido comienza en “Comunicaciones” y después salta a “7. Módulo de jueces” y “8. Resultados públicos”. Faltan la introducción y los módulos 1–6. Este documento propone una reconstrucción mínima para planificar, pero no convierte esa reconstrucción en requisito aprobado. Debe solicitarse el fragmento faltante y reconciliarse mediante diff antes de iniciar desarrollo.

## Resumen ejecutivo

Flower Flow será la plataforma web de la convocatoria 2026 para registrar participantes, recibir proyectos, verificar elegibilidad, gestionar anexos privados, asignar proyectos a jueces, capturar evaluaciones con rúbrica y declarar resultados de manera trazable.

El MVP se limita a lo indispensable para recibir, revisar y evaluar proyectos de forma segura antes del 15 de agosto de 2026. Desde la fecha de corte quedan 31 días calendario, de modo que seguridad, flujo de envío, revisión y evaluación tienen precedencia sobre funciones presentacionales. La publicación pública de ganadores se prepara con un interruptor desactivado por defecto; una galería enriquecida, marketing masivo y cualquier API o aplicación móvil quedan fuera del MVP.

La primera fase es documental. No incluye cambios de aplicación, dependencias, esquema de base de datos ni producción. La implementación posterior requiere aprobación expresa del alcance, del modelo de datos, de los textos legales y del ExecPlan.

## Decisiones confirmadas

| ID | Estado | Decisión |
| --- | --- | --- |
| DEC-001 | DECISION | La edición operativa es Flower Flow 2026. |
| DEC-002 | DECISION | La fecha límite de negocio es el 2026-08-15; la hora exacta permanece pendiente. |
| DEC-003 | DECISION | La zona horaria de presentación es `America/Hermosillo`; los timestamps persistidos se planifican en UTC. |
| DEC-004 | DECISION | No existe selección aleatoria. La declaración de ganador es una acción administrativa separada del cálculo de puntuación. |
| DEC-005 | DECISION | Los jueces sólo acceden a proyectos asignados y nunca a comprobantes de residencia. |
| DEC-006 | DECISION | Los resultados públicos permanecen desactivados hasta confirmación administrativa. |
| DEC-007 | DECISION | El código futuro estará en inglés y la interfaz en español. |
| DEC-008 | DECISION | El entorno local/de pruebas usará MySQL en `127.0.0.1:3306`, base `flowerflow` y usuario `flowerflow_user`. |
| DEC-009 | DECISION | La contraseña de MySQL fue proporcionada fuera del repositorio y sólo se configura en el `.env` local no versionado. No se reproduce en documentación, ejemplos, logs ni fixtures. |
| DEC-010 | DECISION | Producción se planifica en una instancia AWS EC2 con Ubuntu, coexistente con el proyecto `administratec`. |
| DEC-011 | DECISION | La coexistencia en EC2 exige aislamiento por virtual host, ruta, usuario de sistema, variables, base de datos, storage, procesos, logs y backups. |
| DEC-012 | DECISION | En esta primera fase no se implementa código ni se despliega. |

## Evidencia actual del repositorio

| Elemento | Estado | Evidencia al 2026-07-15 |
| --- | --- | --- |
| Backend | DECISION | `composer.json` declara PHP `^8.2` y Laravel `^12.0`. |
| Plantilla | DECISION | `package.json` declara Materialize `3.0.0` con licencia comercial. |
| Frontend | DECISION | Bootstrap 5.3.6, Vite 6.3.5 y varios plugins de la plantilla están declarados; su presencia no autoriza usarlos todos. |
| Layouts | DECISION | Existen `layoutFront.blade.php` y layouts administrativos vertical/horizontal. |
| Navegación | DECISION | `resources/menu/verticalMenu.json` conserva entradas demo. |
| Aplicación | DECISION | `routes/web.php` sólo expone páginas base/demo, selector de idioma y vistas básicas de login/registro; los módulos de negocio aún no están implementados. |
| Variante/licencia exacta | PENDING | Debe confirmarse si el paquete adquirido es starter kit o full version, y el alcance de su licencia para dominio/proyecto. |

## Objetivo del producto

Permitir que una convocatoria opere de punta a punta con mínimo privilegio, trazabilidad y separación de datos sensibles:

1. Informar reglas, calendario y documentos vigentes.
2. Registrar y verificar participantes.
3. Capturar perfil, residencia y elegibilidad mínima.
4. Crear y conservar borradores de proyecto.
5. Recibir un envío final versionado, con folio y anexos controlados.
6. Revisar elegibilidad sin exponer comprobantes a jueces.
7. Asignar proyectos a jueces y gestionar conflictos.
8. Evaluar mediante rúbrica versionada y cálculo de servidor.
9. Declarar ganadores mediante una decisión administrativa auditable.
10. Comunicar eventos transaccionales y generar reportes autorizados.

## Resultados esperados del MVP

| Resultado | Indicador de aceptación |
| --- | --- |
| Envíos íntegros | Cada proyecto enviado tiene propietario, convocatoria, categoría, folio, versión auditable, aceptación legal vigente y sello de tiempo. |
| Elegibilidad separada | Sólo personal autorizado revisa residencia; el juez no puede consultar esos documentos. |
| Evaluación controlada | Cada evaluación está ligada a una asignación vigente, rúbrica versionada y ausencia de conflicto. |
| Cálculo reproducible | Los totales se calculan en servidor y pueden reconstruirse desde puntuaciones por criterio. |
| Trazabilidad | Transiciones, decisiones críticas, descargas sensibles y exportaciones identifican actor, fecha y contexto técnico proporcional. |
| Accesibilidad | Los recorridos críticos cumplen la lista de aceptación WCAG 2.2 AA definida en `docs/05-ux-ui.md`. |
| Operabilidad | Existen pruebas, backups, restauración, monitoreo y rollback documentados antes del despliegue. |

## Actores

| Actor | Responsabilidad | Límite principal |
| --- | --- | --- |
| Visitante | Consultar convocatoria, reglas, categorías, calendario y documentos públicos. | No accede a información privada ni a resultados no publicados. |
| Participante | Gestionar su perfil, equipo, proyectos, anexos y seguimiento. | Sólo sus datos y recursos autorizados. |
| Integrante de equipo | Aceptar invitación y, si se aprueba, documentos y participación. | Sin acceso implícito a otros proyectos del representante. |
| Revisor de elegibilidad | Revisar residencia y requisitos; solicitar correcciones. | Sin modificar evaluaciones ni publicar resultados. |
| Juez | Consultar asignaciones, declarar conflicto y evaluar. | Sin PII innecesaria, comprobantes, proyectos ajenos ni ranking global. |
| Administrador de convocatoria | Operar convocatoria, categorías, revisión, asignaciones y comunicaciones. | Acciones críticas sujetas a permisos y auditoría. |
| Soporte de privacidad | Gestionar solicitudes de acceso, rectificación, cancelación u oposición. | Acceso limitado a lo necesario para el caso. |
| Auditor | Consultar reportes y bitácoras. | Sólo lectura; sin cambios operativos. |
| Superadministrador | Administrar roles y configuración excepcional. | 2FA, confirmación de contraseña y auditoría reforzada. |

## Requisitos funcionales consolidados

### Convocatoria y contenido público

- **CAL-001 — DECISION:** modelar una convocatoria versionable con slug, estado, fechas y zona horaria.
- **CAL-002 — ASSUMPTION:** el MVP tendrá una sola edición activa, pero el modelo no bloqueará ediciones posteriores.
- **CAL-003 — DECISION:** mostrar categorías, calendario, preguntas frecuentes y documentos legales vigentes.
- **CAL-004 — DECISION/PENDING:** cierre inclusivo aprobado `2026-08-15 23:59:59 America/Hermosillo`; fecha/hora de apertura pendiente y configurable.
- **CAL-005 — DECISION:** cerrar automáticamente nuevos envíos al vencer el plazo; una excepción requiere actor autorizado, justificación y auditoría.

### Identidad y acceso

- **IAM-001 — DECISION:** registro, login, restablecimiento y verificación de correo.
- **IAM-002 — DECISION:** RBAC de mínimo privilegio complementado con Policies por recurso.
- **IAM-003 — DECISION:** 2FA para roles privilegiados y confirmación de contraseña para acciones críticas.
- **IAM-004 — DECISION:** rate limiting por IP y cuenta, respuestas que no revelen si un correo existe y revocación de sesiones al suspender usuarios.
- **IAM-005 — ASSUMPTION:** las invitaciones de jueces e integrantes usarán tokens firmados, expirables y de un solo uso.

### Perfil, residencia y elegibilidad

- **ELG-001 — DECISION:** recolectar sólo los datos mínimos del participante.
- **ELG-002 — DECISION:** almacenar comprobantes de residencia separados de anexos evaluables.
- **ELG-003 — DECISION:** registrar decisión, razones, revisor, fecha y versión revisada.
- **ELG-004 — DECISION:** permitir solicitar una corrección con plazo y notificación.
- **ELG-005 — PENDING:** tipos aceptados de comprobante, vigencia, criterios, cifrado y retención.

### Proyectos y equipos

- **SUB-001 — DECISION:** crear borradores y autosave recuperable.
- **SUB-002 — ASSUMPTION:** el formulario será un wizard con pasos de datos, equipo, contenido, anexos, revisión y envío.
- **SUB-003 — DECISION:** el envío final exige correo verificado, elegibilidad mínima y aceptación de documentos vigentes.
- **SUB-004 — DECISION:** el envío genera folio, versión inmutable y acuse transaccional.
- **SUB-005 — DECISION:** una corrección posterior crea una nueva versión; no sobrescribe la enviada.
- **SUB-006 — DECISION:** archivos privados se almacenan fuera del web root, con nombres aleatorios, allowlist, límites, validación MIME/firma y descarga autorizada.
- **SUB-007 — PENDING:** participación individual/equipo, máximo de integrantes, límites de proyectos, texto, cantidad/tipo/tamaño de anexos.

### Elegibilidad administrativa

- **REV-001 — DECISION:** listados administrativos paginados, autorizados e indexados.
- **REV-002 — DECISION:** el revisor puede marcar elegible, no elegible o solicitar corrección conforme a transición válida.
- **REV-003 — DECISION:** notas internas no son visibles a participantes ni jueces.
- **REV-004 — DECISION:** cambios de estado, descarga de comprobantes y exportaciones se auditan.

### Jueces y evaluación

- **JUD-001 — DECISION:** dashboard de asignaciones pendientes, borrador y finalizadas.
- **JUD-002 — DECISION:** vista anónima de proyecto y sólo anexos autorizados.
- **JUD-003 — DECISION:** declaración de conflicto por proyecto; un conflicto bloquea evaluación.
- **JUD-004 — DECISION:** evaluación por criterio con comentarios y guardado de borrador.
- **JUD-005 — DECISION:** confirmación explícita antes de enviar; el juez conserva historial personal.
- **JUD-006 — DECISION:** cierre automático conforme al calendario; excepciones auditadas.
- **JUD-007 — PENDING:** número de jueces por proyecto, modalidad ciega, rúbrica final, puntaje mínimo, empate y recusación.

### Ganadores y resultados

- **WIN-001 — DECISION:** la puntuación calculada no declara automáticamente un ganador.
- **WIN-002 — DECISION:** declarar ganador requiere permiso específico, actor, justificación y fecha.
- **WIN-003 — PENDING:** reglas para empate, categoría desierta y número/naturaleza del premio.
- **PUB-001 — DECISION:** módulo público desactivado por defecto.
- **PUB-002 — DECISION:** publicar sólo tras confirmación administrativa y únicamente campos autorizados.
- **PUB-003 — DECISION:** nunca publicar comprobantes, correos, teléfonos, domicilios ni anexos no autorizados.
- **PUB-004 — PENDING:** datos consentidos de ganador, texto legal definitivo y política del archivo 2026.

### Comunicaciones, privacidad y reportes

- **COM-001 — DECISION:** plantillas transaccionales para verificación, invitaciones, correcciones, envío, elegibilidad, asignación, evaluación y resultados.
- **COM-002 — DECISION:** colas, idempotencia, reintentos y registro de resultado sin contenido sensible innecesario.
- **COM-003 — DECISION:** usar `convocatoria@flowerflow.com.mx` como remitente o reply-to funcional y `privacidad@flowerflow.com.mx` para privacidad; credenciales SMTP permanecen pendientes y no deben inventarse.
- **COM-004 — DECISION:** no construir marketing masivo sin consentimiento y alcance explícitos.
- **PRV-001 — ASSUMPTION:** bandeja administrativa para solicitudes recibidas por correo o formulario, sin afirmar que sustituye revisión legal.
- **PRV-002 — DECISION:** conservar evidencia de atención y cierre, con exportación, rectificación, retención y eliminación controladas.
- **RPT-001 — DECISION:** reportes por categoría, estado, elegibilidad y evaluación, sujetos a permiso.
- **RPT-002 — DECISION:** exportaciones backend con columnas mínimas, marca de actor/fecha y redacción de PII.
- **RPT-003 — DECISION:** reporte de conflictos/recusaciones y bitácora de declaración de ganadores.

## Máquinas de estado

Las listas del texto recibido son una hipótesis que debe simplificarse, no un contrato de persistencia. La recomendación canónica de planificación está en `docs/03-data-model.md`: evita guardar estados que pueden derivarse de revisiones o asignaciones y reduce el riesgo de desincronización.

### Convocatoria

Hipótesis recibida: `draft → scheduled → open → closed → eligibility_review → judging → results_published → archived`.

Recomendación: `draft → scheduled → open → closed → judging → results_published → archived`. La revisión de elegibilidad pertenece a cada proyecto; no se persiste como pseudoestado global de la convocatoria.

- **ASSUMPTION:** `scheduled` sólo se usa si existe apertura automática; si no, se elimina para simplificar.
- **DECISION:** publicar resultados requiere confirmación administrativa y no ocurre como efecto automático de `judging`.

### Proyecto

Hipótesis recibida: `draft → submitted → under_eligibility_review → correction_requested → eligible / ineligible → assigned_to_judges → under_evaluation → evaluated → finalist → winner / not_selected → archived`.

Recomendación: persistir `draft`, `submitted`, `under_eligibility_review`, `correction_requested`, `eligible`, `ineligible`, `evaluated`, `finalist` si existe la etapa, `winner`, `not_selected`, `withdrawn` y `archived`. `assigned_to_judges` y `under_evaluation` se derivan de asignaciones/evaluaciones y no se duplican en el proyecto.

Transiciones laterales controladas: `correction_requested → submitted` mediante nueva versión; cualquier estado permitido puede terminar en `withdrawn` conforme a reglas pendientes.

- **ASSUMPTION:** `finalist` se conservará sólo si existe una etapa real de finalistas.
- **PENDING:** definir desde qué estados y hasta qué fecha se permite retirar un proyecto.

### Evaluación

`assigned → in_progress → submitted`

Transiciones excepcionales: `assigned → conflict_declared`; `submitted → reopened → in_progress`; cualquier evaluación inválida puede pasar a `voided` por actor autorizado y con auditoría.

Cada transición deberá especificar actor, precondiciones, efectos, notificaciones, evidencia de auditoría y reversibilidad.

## Invariantes de negocio

1. No se envía un proyecto sin correo verificado, elegibilidad mínima y aceptación legal vigente.
2. No se envía después del cierre salvo excepción explícita, autorizada y auditada.
3. Un juez no puede ver ni evaluar un proyecto no asignado.
4. Un juez con conflicto no puede evaluar ese proyecto.
5. Los jueces nunca acceden a comprobantes de residencia.
6. La puntuación se calcula en servidor.
7. Declarar ganador es distinto de calcular puntuación.
8. No existe selección aleatoria.
9. Todo envío conserva una versión auditable.
10. Archivos y endpoints se autorizan en servidor; ocultar un botón no concede seguridad.
11. Datos reales no se usan en desarrollo o pruebas.
12. Ninguna credencial se almacena en repositorio, documentación rastreada, frontend o logs.

## Datos y privacidad

### Clasificación

| Clase | Ejemplos | Tratamiento |
| --- | --- | --- |
| Pública | convocatoria, categorías, calendario, documentos publicados | Cacheable; revisión editorial y legal. |
| Interna | notas, asignaciones, rúbrica no publicada | Acceso por rol y Policy. |
| PII | nombre, correo, teléfono, integrantes | Minimización, masking en listados/exports y retención definida. |
| Alto riesgo | comprobantes de residencia, solicitudes de privacidad | Almacenamiento privado, permisos separados, descargas auditadas y cifrado viable. |
| Evaluación | puntuaciones, comentarios, conflictos | Separada de PII; acceso por asignación/rol. |
| Auditoría | actor, acción, entidad, antes/después redactado, contexto técnico | Inmutable en operación normal; retención pendiente. |

### Reglas de persistencia

- MySQL, InnoDB y `utf8mb4`.
- Claves foráneas y reglas de borrado intencionales.
- Índices por categoría, estado, fechas, propietario, folio, asignación y filtros frecuentes.
- Identificador interno eficiente y ULID/UUID público cuando reduzca enumeración.
- UTC en persistencia y `America/Hermosillo` en presentación.
- JSON sólo para configuración o snapshots justificados, no para datos centrales consultables.
- Soft delete únicamente cuando sea compatible con privacidad y retención.

## Ambientes

### Local y pruebas

**DECISION**

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=flowerflow
DB_USERNAME=flowerflow_user
DB_PASSWORD=<provista-fuera-del-repositorio>
```

- El valor real de `DB_PASSWORD` vive sólo en el `.env` local ignorado por control de versiones.
- `.env.example`, documentación y CI deben usar placeholders o secretos administrados.
- No ejecutar migraciones o seeders contra una base que no esté confirmada como desechable.
- Fixtures y pruebas deben usar datos sintéticos.

### Producción

**DECISION:** AWS EC2 Ubuntu coexistente con `administratec`.

**PENDING:** inventario de Ubuntu, servidor web, PHP-FPM, extensiones, CPU/RAM/disco, base de datos, TLS/DNS, correo, cron, workers, monitoreo y backups.

La arquitectura de despliegue deberá aislar:

- dominio y virtual host;
- ruta de release y document root;
- usuario/grupo del sistema y permisos;
- `.env` y secretos;
- esquema/usuario de base de datos;
- storage público y privado;
- sesiones, cache y prefijos;
- unidades `systemd`/Supervisor, workers y scheduler;
- logs, alertas, backups y rollback;
- presupuesto de CPU, memoria y disco frente a `administratec`.

No se desplegará hasta contar con backup probado, staging/UAT, smoke tests y aprobación expresa.

## Requisitos no funcionales

| Área | Requisito de planificación |
| --- | --- |
| Seguridad | OWASP, mínimo privilegio, CSRF, XSS/SQLi/IDOR, headers, cookies seguras, 2FA privilegiado y secretos fuera del repositorio. |
| Accesibilidad | WCAG 2.2 AA en recorridos críticos, teclado, foco, labels, errores, contraste, modales y stepper. |
| Rendimiento | Listados server-side, índices, eager loading, paginación, assets por página y presupuesto móvil pendiente. |
| Disponibilidad | SLO específico para apertura/cierre; capacidad y monitoreo de EC2 por verificar. |
| Integridad | Transacciones e idempotencia en envío, revisión, evaluación, ganador, correo y exportaciones. |
| Recuperación | Backup cifrado, restauración probada y rollback de código/base. |
| Observabilidad | Logs redactados con rotación, jobs fallidos, correo, errores 5xx, espacio y health check. |
| Compatibilidad | Navegadores modernos y Safari/iOS/Android; matriz exacta pendiente. |
| Mantenibilidad | Controladores delgados, Form Requests, Policies, Services/Actions, enums, eventos/jobs justificados y ADRs. |

## Alcance por fase

### MVP estricto

- Convocatoria pública, categorías, calendario y documentos.
- Registro, verificación, login, recuperación y RBAC/Policies.
- Perfil, residencia/elegibilidad y consentimiento versionado.
- Wizard con borrador, equipo sujeto a decisión, archivos privados y envío versionado.
- Backoffice de revisión y correcciones.
- Jueces, asignaciones, conflictos, rúbrica, borrador y envío de evaluación.
- Cálculo de servidor y declaración administrativa de ganador.
- Notificaciones transaccionales críticas.
- Reportes operativos mínimos y auditoría.
- Seguridad, accesibilidad, pruebas, backups, observabilidad y runbook AWS requeridos para operar.

### Fase 2

- Galería pública de proyectos.
- Gestión editorial enriquecida si el contenido por código resulta insuficiente.
- Analítica con consentimiento.
- Automatizaciones/reportes no críticos.
- Mejoras avanzadas de autosave, colaboración de equipos y archivo histórico, según datos reales.

### Fuera de alcance del MVP

- Marketing masivo o newsletters.
- Aplicación móvil o API pública/terceros.
- Selección aleatoria.
- Ranking global visible a jueces.
- Sustituir la revisión legal o afirmar cumplimiento legal.
- Publicar PII, comprobantes o anexos no autorizados.
- Uso de logos, fotografías o assets de Apple sin autorización.

## Restricciones de implementación posterior

- Leer `AGENTS.md`, ExecPlan y ADRs antes de editar.
- Un milestone por vez o worktrees sin solapamiento.
- No agregar dependencias ni cambiar versiones mayores sin aprobación.
- No modificar manualmente `public/build` ni el core de la plantilla.
- Mantener overrides de Flower Flow separados.
- Detener el avance ante fallos de tests, build, lint o aceptación.
- No desplegar sin UAT, backup, checklist y aprobación.

## Criterios de aceptación de esta especificación

- [ ] Se recibió y reconcilió el fragmento faltante de módulos 1–6, o producto aceptó expresamente los supuestos.
- [ ] Se aprobaron actores, MVP, fase 2 y fuera de alcance.
- [ ] Se aprobaron estados, transiciones e invariantes.
- [ ] Se resolvieron hora de cierre, reglas de participación, límites, rúbrica, empates, premio y datos publicables.
- [ ] Se aprobó la separación de PII, residencia y evaluación.
- [ ] Se confirmó variante/licencia de Materialize.
- [ ] Se inventarió la instancia AWS EC2 y el aislamiento con `administratec`.
- [ ] Se confirmó que la base local `flowerflow` es exclusiva/desechable para pruebas antes de ejecutar migraciones.
- [ ] Existe trazabilidad de requisitos a historias, páginas y pruebas.
- [ ] La implementación permanece bloqueada hasta aprobación del ExecPlan.

## Referencias internas

- `docs/01-functional-scope.md`
- `docs/05-ux-ui.md`
- `docs/10-open-questions.md`
- `docs/requirements-traceability.md`

## Adenda aprobada — Fase 02A, 2026-07-16

Esta adenda conserva el historial anterior y registra el alcance autorizado para revisión administrativa:

- la propuesta conserva `draft/submitted/withdrawn`; la admisibilidad usa expediente propio con `pending/in_review/clarification_requested/admitted/not_admitted`;
- cada expediente referencia la propuesta y la versión inmutable enviada;
- las aclaraciones permiten respuestas append-only de hasta 2,000 caracteres y nunca habilitan edición del proyecto enviado;
- residencia se solicita por representante o integrante y usa almacenamiento privado separado de anexos evaluables;
- PDF, JPEG, PNG y WebP; máximo tres archivos y 10 MiB por persona/solicitud son controles técnicos autorizados, no reglas jurídicas;
- “reciente” no tiene antigüedad automática porque los PDF no fijan meses;
- un documento equivalente exige justificación humana y una residencia rechazada no produce por sí sola no admisión;
- admitir requiere resolver/cerrar aclaraciones abiertas y verificar las solicitudes activas de residencia;
- `admitted` sólo habilita una futura fase de evaluación y no equivale a ganador;
- roles `reviewer` y `admin` operan por permisos granulares; jueces y usuarios sin permiso no acceden a identidad, residencia, notas ni auditoría;
- las notificaciones de aclaración, residencia, respuesta y resolución se encolan después del commit y una falla temporal no revierte datos;
- el cálculo de retención registra una fecha candidata a 90 días, pero no borra mientras falte determinar ganadores.

Jueces, evaluación, rúbricas, ganadores, comunicaciones masivas, ARCO completo, reportes avanzados y producción siguen fuera de alcance.
