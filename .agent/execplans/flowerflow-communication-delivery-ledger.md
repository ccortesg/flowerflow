# Milestone independiente: bitácora y recuperación de comunicaciones

Este ExecPlan es un documento vivo. Deben mantenerse actualizadas las secciones `Progress`, `Surprises & Discoveries`, `Decision Log` y `Outcomes & Retrospective` durante la implementación.

## Purpose / Big Picture

Flower Flow necesita una bitácora administrativa que permita conocer qué correos transaccionales fueron generados, su estado operativo y sus intentos, sin exponer contenido sensible. El resultado visible será un acceso `Notificaciones` en el panel, exclusivo del rol exacto `admin`, situado entre `Paquetes ciegos` y `Cuenta y seguridad`. Desde ahí podrán consultarse deliveries e intentos y solicitarse de manera segura el procesamiento de un registro en cola, el reintento de uno fallido o el reenvío explícito de uno con resultado desconocido.

El milestone centraliza las nueve familias actuales de correo en un outbox transaccional. No crea nuevas campañas ni comunicaciones de evaluación, resultados, ganadores o marketing. Toda validación y ejecución será local/testing con correo falso; producción, SMTP real, despliegue, stage, commit y push quedan fuera.

## Baseline

- Repositorio: `/home/ccortesg/workspace/flowerflow`.
- Rama esperada: `codex/submission-deadline-extension`.
- HEAD, upstream y merge-base esperados: `e8101ad565e96c0d6113639874dde74704c7ab18`.
- Árbol esperado: limpio al comenzar.
- Worker documentado: colas `high,exports,default,low`; este milestone no añade otro proceso.
- La comprobación de base de datos debe demostrar, sin revelar secretos, `APP_ENV=testing`, conexión MySQL local y `SELECT DATABASE() = flowerflow_testing` antes de migraciones o pruebas con esquema.

## Scope

Se implementarán:

- Tablas aditivas `communication_deliveries` y `communication_delivery_attempts` y vínculo opcional desde `submission_reminders`.
- Estados `queued`, `processing`, `sent`, `failed`, `unknown` y `cancelled`; en interfaz `sent` significará exclusivamente `Aceptado por el servidor de correo`.
- Destinatario y contexto cifrados, destinatario enmascarado y fingerprint HMAC; nunca cuerpo, tokens o URLs en la bitácora visible.
- Idempotencia por tipo, destinatario, evento origen y versión de plantilla.
- Job central cifrado y post-commit que reciba sólo el ID del delivery y reconstruya una clase de correo mediante registro allowlist.
- Integración de verificación, restablecimiento, alta/verificación/estado de juez, acuses normal/administrativo, admisibilidad y recordatorio de borrador.
- Menú, lista, detalle y confirmación de procesamiento/reintento para administradores exactos.
- Comando dry-run por defecto para backfill exclusivo de `submission_reminders`.
- Diagnóstico de sólo lectura, reconciliación de `processing` estancado hacia `unknown` y purga de contexto conforme al contrato.
- Pruebas automatizadas, UAT local y documentación afectada.

## Non-goals

- No se implementan campañas masivas, nuevas notificaciones, webhooks del proveedor, apertura/click tracking ni certeza de entrega al buzón.
- No se reconstruye historial desde logs, `jobs` o `failed_jobs`.
- No se edita ni reencola directamente la tabla `jobs`.
- No hay acción masiva desde el panel.
- No se generan nuevos tokens o credenciales al forzar un correo vencido o inválido.
- No se altera producción, infraestructura, PDFs jurídicos, hashes, aceptaciones históricas ni datos reales.

## Model and invariants

`communication_deliveries` contendrá ULID público, tipo, variante, versión de plantilla, evento origen opaco, clave de idempotencia, destinatario interno opcional, dirección cifrada, máscara, fingerprint HMAC, contexto cifrado, referencia técnica, estado, cola, intentos, versión optimista, códigos de fallo redactados y timestamps UTC. `communication_delivery_attempts` registrará número, origen `automatic|admin_forced`, actor administrativo, razón cifrada, estado, UUID del job cuando exista, etapa/código de fallo y reconocimiento de posible duplicado.

Los modelos serán guarded y no podrán borrarse. Toda mutación pasará por Actions o el job central dentro de transacciones con `lockForUpdate`. Dos ejecuciones concurrentes deben converger sin dos intentos activos ni dos envíos autorizados por la misma transición. Un GET nunca muta.

El contexto cifrado se purgará al quedar `sent|cancelled`; en `failed|unknown` se conservará como máximo 90 días para recuperación. La metadata mínima no se eliminará automáticamente hasta que exista política legal aprobada.

## Routes and authorization

- `GET /panel/notificaciones`
- `GET /panel/notificaciones/{communicationDelivery}`
- `GET /panel/notificaciones/{communicationDelivery}/procesar`
- `POST /panel/notificaciones/{communicationDelivery}/procesar`

Permisos exactos:

- `view communication deliveries`
- `manage communication deliveries`

Sólo el rol exacto `admin` recibirá ambos. La acción POST requerirá CSRF, throttle de mutaciones, contraseña reciente, `lock_version`, confirmación y razón de 20 a 1,000 caracteres. `unknown` requerirá además reconocer el riesgo de duplicado. La solicitud sólo despachará a `high`; nunca realizará SMTP dentro del request.

## Communication lifecycle

La creación del delivery y de su primer intento ocurrirá en la misma unidad transaccional del evento de negocio o mediante dispatch post-commit. El job revalidará destinatario, vigencia y estado del evento antes de renderizar. Un evento inválido termina en `cancelled`; un resultado no determinable termina en `unknown`; los fallos agotados terminan en `failed`. Los reintentos automáticos seguirán una política acotada y cada intento quedará registrado.

El panel permitirá:

- `queued`: Procesar ahora.
- `failed`: Reintentar.
- `unknown`: Reenviar con riesgo de duplicado.
- `sent|cancelled`: sólo lectura.

## Testing and validation

Antes de ejecutar migraciones o tests de base de datos se comprobará el guard exacto de `flowerflow_testing`. Se cubrirán permisos positivos y negativos, GET sin mutación, cifrado/redacción, idempotencia, enqueue fallido, reintentos, agotamiento, cancelación, resultado desconocido, concurrencia, backfill dry-run/aplicado, filtros, paginación y ausencia de secretos/PII.

Los gates finales incluirán migración forward/rollback/forward bajo el guard, suites dirigidas y completa, Pint, Composer, auditorías, build Vite, rutas, scheduler, estado de migraciones, JSON, Markdown, secretos/PII y `git diff --check`. El UAT local utilizará Firefox en 1440x900, 1024x768 y 390x844 con correo fake y datos sintéticos.

## Rollout and rollback

El flag `FLOWERFLOW_COMMUNICATION_LEDGER_ENABLED=false` será el rollback operativo primario. La migración no ejecutará backfill automático. `down()` se negará si existe cualquier delivery, intento o evidencia propia del milestone. Si no existe evidencia, retirará vínculo, tablas y permisos sin afectar las funcionalidades previas.

Un rollout productivo no está autorizado en este milestone y requerirá una tarea separada con backup, migración, caché, scheduler, reinicio exclusivo de workers Flower Flow, smoke/UAT y rollback verificados.

## Progress

- [x] (2026-08-23) Se verificó baseline de rama, HEAD/upstream/merge-base y árbol limpio.
- [x] (2026-08-23) Se leyeron `AGENTS.md`, `.agent/PLANS.md`, los ExecPlans de correo/recordatorios y ADR 0004, 0005 y 0007.
- [x] (2026-08-23) Se demostró dos veces el guard exacto: `testing`, `mysql`, `127.0.0.1`, `flowerflow_testing`, `flowerflow_testing_user` y `SELECT DATABASE() = flowerflow_testing`, sin imprimir contraseña.
- [x] (2026-08-23) La base dirigida previa al cambio quedó verde: 19 pruebas y 217 aserciones.
- [x] (2026-08-23) Se implementaron modelo, migración aditiva, estados, checks, cifrado, idempotencia y permisos exclusivos de `admin`.
- [x] (2026-08-23) Se implementaron dispatcher compatible con flag, registro allowlist y job central cifrado/post-commit que recibe sólo el ID interno.
- [x] (2026-08-23) Se integraron las nueve familias actuales y se sincronizó el estado legado de recordatorios.
- [x] (2026-08-23) Se implementaron menú, lista, filtros, detalle, línea de tiempo y recuperación individual con bloqueo optimista.
- [x] (2026-08-23) Se implementaron backfill dry-run, diagnóstico read-only, reconciliación y purga programada.
- [x] (2026-08-23) La migración forward/rollback/forward quedó verde bajo el guard; `down()` fue probado tanto sin evidencia como con negativa ante evidencia.
- [x] (2026-08-23) La suite integral final quedó verde con 191 pruebas y 2,255 aserciones en 586.22 s.
- [x] (2026-08-23) UAT Firefox local completado en 1440x900, 1024x768 y 390x844 con datos sintéticos y mailer fake: menú, filtros, detalle, recuperación, 409, riesgo de duplicado, reflow, teclado, foco, zoom y consola limpia.
- [x] (2026-08-23) Gates finales completados: Pint, Composer, build, rutas, scheduler, 21 migraciones, JSON, 12 enlaces Markdown, diff y scans verdes. `yarn audit` conserva el único advisory bajo conocido de Quill sin parche.
- [x] (2026-08-23) Evidencia consolidada en `docs/26-communication-delivery-ledger-implementation-report-2026-08-23.md`.

## Surprises & Discoveries

- `failed_jobs` contiene detalle técnico y no es una bitácora de negocio segura para exponer en el panel.
- Los recordatorios de borradores son la única familia con evidencia histórica estructurada y, por ello, la única autorizada para backfill.
- SMTP permite afirmar aceptación por el transporte, no entrega al buzón; la interfaz evitará el término `Entregado`.
- El primer forward falló porque el nombre implícito de una FK excedía el límite de 64 caracteres de MySQL; las tablas parciales estaban vacías, se retiraron únicamente en `flowerflow_testing` y la FK quedó con nombre explícito corto antes de repetir con éxito.
- El UAT reveló un resumen de errores duplicado y pérdida horizontal de la columna de acciones en anchos intermedios; se conservó un único resumen global con foco y la lista se convirtió en tarjetas semánticas por debajo de 1200 px.
- El wrapper local de Playwright tenía finales CRLF incompatibles con `/usr/bin/env`; la UAT se ejecutó con el CLI oficial de Playwright y Firefox, sin modificar dependencias del proyecto.

## Decision Log

- Decision: usar un outbox común y un job central que recibe sólo el ID del delivery.
  Rationale: uniforma estado, reintentos, idempotencia y redacción sin serializar tokens/correos en el payload visible del job.
  Date/Author: 2026-08-23 / Codex, aprobado por el usuario.
- Decision: acciones individuales, sin reintento masivo.
  Rationale: reduce duplicados accidentales y obliga a revisar el contexto y riesgo de cada comunicación.
  Date/Author: 2026-08-23 / Codex, aprobado por el usuario.
- Decision: `sent` se presenta como `Aceptado por el servidor de correo`.
  Rationale: sin webhook de proveedor no existe evidencia de entrega final.
  Date/Author: 2026-08-23 / Codex, aprobado por el usuario.

## Outcomes & Retrospective

**GO LOCAL/TEST.** La funcionalidad solicitada quedó implementada y validada localmente: las nueve familias producen evidencia uniforme, el panel es exclusivo de administradores exactos y la recuperación nunca envía SMTP dentro del request. El diseño mantiene explícita la diferencia entre aceptación por transporte y entrega, y preserva la ruta legacy mediante el flag de rollback. Suite final 191/2,255, migración forward/rollback/forward, build y UAT Firefox verdes. `yarn audit` conserva un advisory bajo conocido de Quill sin parche. No existe ni se afirma evidencia productiva.
