# Asignación simultánea de múltiples propuestas a un juez

Este ExecPlan es un documento vivo y se mantiene conforme a `.agent/PLANS.md`.

## Propósito y resultado observable

Un administrador exacto podrá abrir un mismo asistente desde Propuestas o Asignaciones, seleccionar un juez y entre una y veinte propuestas, revisar un preflight sin mutaciones y ejecutar sincrónicamente admisión, paquete ciego y asignación. Cada propuesta constituye una unidad atómica independiente: una falla revierte sólo esa propuesta y las demás continúan. El resultado identifica por propuesta las tres fases sin exponer PII o contenido en auditoría.

## Baseline y guard

- Repositorio y Git toplevel: `/home/ccortesg/workspace/flowerflow`.
- Rama: `codex/submission-deadline-extension`.
- HEAD, upstream y merge-base: `d19c8b90d211a0bc57b636aead79ca01d60ec56a`.
- Árbol inicial limpio.
- Guard demostrado sin secretos: `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_DATABASE=flowerflow_testing`, `DB_USERNAME=flowerflow_testing_user` y `SELECT DATABASE()=flowerflow_testing`.
- Baseline dirigido: 33 pruebas y 341 aserciones verdes en admisibilidad, paquetes, asignaciones y bitácora.

## Alcance y exclusiones

Incluido:

- flag y límite configurable, con máximo técnico y de producto de 20;
- rutas de selección, preflight, ejecución y resultado;
- intención cifrada/autenticada de quince minutos ligada a actor, juez, propuestas, datos compartidos y estado observado;
- admisión de expedientes `pending|in_review`, preservación exacta de expedientes ya admitidos y rechazo individual de estados/bloqueos incompatibles;
- generación/activación o validación/reutilización del paquete ciego;
- asignación manual de un juez sin mínimos o máximos de carga;
- un solo delivery consolidado opcional mediante el outbox existente;
- auditoría redactada, pruebas, UAT local y documentación.

Excluido:

- crear expedientes faltantes; el backfill es precondición operativa explícita;
- asignación automática, balanceo, selección total entre páginas o más de un juez por operación;
- modificar evaluaciones, resultados, PDFs, hashes, snapshots, folios o archivos fuente;
- tabla de batches, proceso asíncrono de negocio, dependencia o worker nuevo;
- stage, commit, push, despliegue, producción, SMTP real, servicios externos o datos reales.

## Contratos e invariantes

- Configuración: `FLOWERFLOW_BULK_JUDGE_ASSIGNMENT_ENABLED=false` y `FLOWERFLOW_BULK_JUDGE_ASSIGNMENT_LIMIT=20`.
- Acceso: rol exacto `admin` y permisos `decide admissibility`, `manage blind review packages` y `manage evaluation assignments`; además autenticación, verificación, panel, `password.confirm`, CSRF, contraseña actual y throttle de mutaciones.
- GET y preflight son estrictamente de lectura.
- Se selecciona un único juez activo, verificado, con rol exacto y configuración completa, y una lista `distinct` de 1..20 ULID de propuestas.
- La intención usa `Crypt`, expira en quince minutos e incluye un `operation_id` ULID. El estado global del juez/rúbrica y el estado por propuesta se vuelven a comparar al ejecutar.
- Un lock de aplicación por `operation_id` evita carreras; una auditoría terminal del mismo actor/operación impide reejecución posterior.
- Cada propuesta se procesa en su propia transacción y vuelve a bloquear propuesta, versión, expediente, bloqueos de admisibilidad, paquete, juez, rúbrica y asignaciones.
- La resolución de admisibilidad conserva el workflow, actor real, evento, auditoría y correo participante post-commit. Un expediente ya admitido no se modifica.
- El paquete se construye sólo desde la versión inmutable y archivos actuales; un paquete activo debe reproducir exactamente el builder. Un paquete invalidado o divergente falla cerrado.
- La asignación conserva el Action canónico. El modo de notificación distingue `individual|bulk|none` para no registrar omisiones engañosas ni generar correos individuales durante el lote.
- La notificación consolidada requiere ledger y flag de asignaciones; contiene sólo cantidad, categorías, plazo y CTA autenticado. El worker revalida cada asignación y cancela si ninguna continúa vigente.
- Auditoría permitida: operation ID, IDs técnicos, juez, conteos, booleanos y reason codes. Motivos compartidos, PII y contenido no entran en metadata/logs/correo al juez.

## Rutas y UX

- `GET /panel/asignaciones/masiva`
- `POST /panel/asignaciones/masiva/revisar`
- `POST /panel/asignaciones/masiva`
- `GET /panel/asignaciones/masiva/resultado`

Los accesos de Propuestas y Asignaciones apuntan a la misma selección. La lista ofrece filtros por categoría, admisibilidad, paquete y asignación para el juez seleccionado, paginación visible y causas concretas en elementos no seleccionables. No existe selección implícita fuera de la página. El preflight presenta los estados observados y exige tres confirmaciones expresas antes de ejecutar.

## Plan de implementación

1. Añadir configuración, enum de modo de notificación y contratos de intención/resultado.
2. Extraer servicios de lectura para elegibilidad/preflight y crear el Action orquestador por propuesta.
3. Adaptar `AssignJudgesToSubmission` de forma compatible para el modo consolidado.
4. Añadir tipo, notificación, plantillas y revalidación allowlist al outbox.
5. Añadir Requests, controlador, rutas, botones y vistas accesibles/responsive.
6. Cubrir autorización, pureza GET/preflight, límites, intención, fases, rollback parcial, concurrencia, idempotencia y privacidad.
7. Actualizar documentación y ejecutar gates/UAT local con datos sintéticos.

## Validación

- Pruebas dirigidas nuevas más regresión de admisibilidad, paquete, asignaciones y ledger.
- Suite completa `php artisan test`.
- `vendor/bin/pint --test`.
- `composer validate --strict`, `composer check-platform-reqs`, `composer audit`, `yarn audit`.
- `scripts/build_frontend_production.sh`, JSON, rutas, scheduler, migraciones, enlaces Markdown, scans de secretos/PII y `git diff --check`.
- UAT Firefox local a 1440x900, 1024x768 y 390x844 cuando el runtime disponible lo permita.
- Medición sintética de veinte propuestas y archivos máximos; si excede timeout operativo se declara `NO-GO` sin ampliar límite o inferir asincronía.

## Rollback y riesgo jurídico

Rollback funcional: `FLOWERFLOW_BULK_JUDGE_ASSIGNMENT_ENABLED=false`. No se borra ninguna evidencia. El worker existente continúa atendiendo `high,exports,default,low`. Release/producción permanecen `NO-GO — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED` por la contradicción vigente entre la Mecánica y la ausencia de mínimo de jueces.

## Registro vivo

- [x] 2026-08-25 06:37 MST — Baseline Git limpio y sincronizado comprobado.
- [x] 2026-08-25 06:38 MST — Lecturas obligatorias de AGENTS, PLANS, ExecPlans y ADR aplicables completadas.
- [x] 2026-08-25 06:39 MST — Guard exacto de `flowerflow_testing` demostrado sin secretos.
- [x] 2026-08-25 06:40 MST — Baseline dirigido verde: 33 pruebas, 341 aserciones.
- [x] 2026-08-25 07:05 MST — Backend, intención cifrada, orquestación, outbox, rutas e interfaz terminados.
- [x] 2026-08-25 07:14 MST — Suite completa verde: 228 pruebas y 2,653 aserciones.
- [x] 2026-08-25 07:18 MST — Prueba dirigida final: 9 verdes/123 aserciones y benchmark opt-in separado verde/5 aserciones.
- [x] 2026-08-25 07:18 MST — Benchmark: ejecución de 20 propuestas con un PDF de 10 MiB cada una en 2.589 s; 200 MiB acumulados, sin fallas.
- [x] 2026-08-25 07:25 MST — UAT Firefox verde en 1440x900, 1024x768 y 390x844; preflight, éxito parcial 2/1, correo consolidado, reflow y consola sin errores.
- [x] 2026-08-25 07:28 MST — Pint, Composer validate/platform/audit, build, JSON, rutas, scheduler, migraciones, backfill dry-run cero y diff check verdes. Yarn conserva sólo el advisory bajo conocido de Quill.

## Decisiones y hallazgos

- No se añade tabla batch: la intención firmada, el lock por operación, la auditoría terminal y las evidencias de cada agregado cubren idempotencia y observabilidad del alcance sin inventar persistencia duplicada.
- La notificación individual existente no puede invocarse con `notify=false` porque registraría una omisión incorrecta; el Action se generalizará con un modo explícito compatible.
- Los correos de admisibilidad al participante permanecen en el workflow canónico y un fallo de correo no revierte la propuesta.
- El límite de archivo vigente es 10 MiB acumulados por propuesta; el benchmark máximo utilizó un PDF sintético de 10 MiB en cada una de las veinte propuestas.
- Por petición del propietario se evitó repetir una segunda suite completa de dieciocho minutos después del último ajuste acotado. Ese ajuste sólo retiró el nombre del saludo del correo consolidado y amplió pruebas; la prueba dirigida final, Pint y build quedaron verdes.

## Resultados

`GO LOCAL/TEST` para el milestone. El flujo individual permanece verde, la ejecución masiva es atómica por propuesta, el estado obsoleto queda visible y la carga máxima se completó sin agotar el request local. `NO-GO RELEASE/PRODUCTION — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`; producción no fue inspeccionada ni modificada.
