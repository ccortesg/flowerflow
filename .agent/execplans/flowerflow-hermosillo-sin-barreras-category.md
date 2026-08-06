# Integración de la categoría Hermosillo sin Barreras

Este ExecPlan es un documento vivo y se rige por `.agent/PLANS.md`. La autorización de este trabajo proviene del plan aprobado por el propietario el 2026-08-06. No autoriza despliegue ni cambios en producción.

## Purpose / Big Picture

Agregar a `hermosillo-florece-2026` una cuarta categoría activa llamada “Hermosillo sin Barreras”, disponible en las superficies públicas, del participante y administrativas que ya consumen categorías. Una cuenta podrá registrar hasta cuatro propuestas, con máximo una por categoría. La categoría tendrá un Apple iPad Pro como premio potencial y elevará a cuatro el máximo de ganadores mostrado por la plataforma.

El resultado observable será una plataforma compatible con los datos existentes: la migración actualizará únicamente la descripción de “Movilidad con Flow” e insertará o actualizará idempotentemente la nueva categoría sin reemplazar su `public_id`. No habrá cambios de esquema, rutas, APIs, documentos jurídicos ni recategorización de propuestas.

## Status and Scope

- Rama: `codex/category-hermosillo-sin-barreras`.
- Base productiva exacta: `26256e32cb7dcc38e94d8d46737a4c3b81e5c8a9`.
- Incluye migración aditiva de datos, seeder, configuración, bloqueo transaccional, landing, iconos, vistas dinámicas, pruebas y documentación.
- Excluye despliegue, acceso a EC2, datos reales, PDF jurídicos, hashes, registros de aceptación, jueces, evaluación, resultados y ganadores.
- La prueba destructiva autorizada usa exclusivamente MySQL `flowerflow_testing` en loopback con el usuario dedicado definido en `.env.testing` ignorado.
- `PENDING`: UAT del propietario, backup productivo verificado, checkout productivo limpio y autorización explícita antes de cualquier despliegue.

## Approved Contracts

- Competencia: exclusivamente `hermosillo-florece-2026`.
- Nueva categoría: nombre `Hermosillo sin Barreras`, slug `hermosillo-sin-barreras`, descripción `Ideas para mejorar la accesibilidad y la inclusión para todas y todos.`, orden `4`, icono `ri-accessibility-line`, activa.
- “Movilidad con Flow” queda descrita como `Ideas para mejorar la movilidad, la vialidad y la seguridad de los desplazamientos en la ciudad.`.
- Límite: cuatro propuestas por cuenta y máximo una por categoría.
- Las propuestas enviadas son inmutables; sólo borradores pueden cambiar de categoría.
- El dashboard administrativo muestra sólo categorías activas de la convocatoria activa; los listados históricos conservan sus relaciones.
- El `down` de la migración de datos es deliberadamente no destructivo.

## Legal Residual Risk

El propietario acepta mantener sin cambios la Mecánica v1.0 aunque enumera tres categorías, incluye accesibilidad en “Movilidad con Flow”, limita a tres propuestas y ordena seleccionar una de tres categorías. La aplicación mostrará cuatro categorías, cuatro propuestas y cuatro premios máximos mientras las aceptaciones seguirán vinculadas a la Mecánica v1.0. Es un riesgo residual alto no resoluble técnicamente sin nueva autorización jurídica; los PDF, hashes y aceptaciones no se modificarán.

## Plan of Work

### Milestone 1 — Datos y reglas

Crear una migración de datos que no actúe si la competencia no existe, preserve el `public_id` existente y sea no destructiva al revertir. Alinear el seeder y la configuración. Revalidar límite y unicidad dentro de la transacción de creación después de bloquear la cuenta participante.

### Milestone 2 — UI dinámica e iconos

Limitar la landing a categorías activas ordenadas, añadir fallback de cuatro categorías, resolver iconos por slug, usar cuadrícula 4/2/1 y destacar `hermosillo-florece` por clase semántica. Alinear textos de propuestas y ganadores. Usar el catálogo de iconos también en el listado participante y hacer que el generador lea `config/flowerflow.php`. Limitar la distribución administrativa a categorías activas de la convocatoria activa.

### Milestone 3 — Pruebas y documentación

Cubrir idempotencia de datos, estabilidad de `public_id`, máximo cuatro, quinta propuesta, categoría duplicada, bloqueo `FOR UPDATE`, landing/fallback, pantallas participante, dashboard/filtro/detalle administrativo y snapshot/correo. Actualizar alcance, producto, UX, QA, trazabilidad, riesgos y cambio legal. Registrar por separado la evidencia pública productiva del commit `26256e32…` sin presentarla como UAT autenticada.

## Validation

Ejecutar desde `/home/ccortesg/workspace/flowerflow`:

    php artisan test
    vendor/bin/pint --test
    composer validate --strict --no-check-publish
    composer check-platform-reqs --no-dev
    composer audit --locked
    corepack yarn audit --groups dependencies --level moderate
    corepack yarn icons:check
    scripts/build_frontend_production.sh
    php artisan route:list
    git diff --check

QA de navegador local: landing y recorridos autorizados en 360, 768 y 1440 px; teclado, foco, zoom 200 %, consola y overflow. No usar datos ni credenciales productivas.

## Deployment and Rollback

No desplegar durante este ExecPlan. Un despliegue posterior requiere UAT, backup verificado, checkout limpio y autorización expresa. Preparará Composer/build fuera del webroot, fijará `FLOWERFLOW_MAX_SUBMISSIONS_PER_USER=4`, aplicará la migración, regenerará cachés y reiniciará sólo el worker Flower Flow antes de smoke de Flower Flow y de las aplicaciones compartidas.

Antes de existir propuestas de la nueva categoría, una compensación puede desactivarla y restaurar el límite tres. Después de existir cualquier borrador o envío asociado, la categoría y los datos deben preservarse y el límite debe seguir en cuatro; sólo podrá revertirse presentación/código compatible.

## Progress

- [x] 2026-08-06 06:42 MST — Leídos `AGENTS.md`, `.agent/PLANS.md`, el ExecPlan previo y ADR 0001, 0003, 0004 y 0006.
- [x] 2026-08-06 06:42 MST — Verificada rama previa limpia; actualizado `origin/main` y creada esta rama desde `26256e32…`.
- [x] 2026-08-06 06:43 MST — Baseline completo verde: 90 pruebas/800 aserciones, Pint, Composer, Yarn, iconos, build Vite, 63 rutas y `git diff --check`.
- [x] 2026-08-06 07:02 MST — Implementados migración/seeder, límite cuatro con bloqueo transaccional, superficies dinámicas, landing, catálogo de iconos y correo.
- [x] 2026-08-06 07:10 MST — Completadas pruebas MySQL de datos, concurrencia, participante y administrador; actualizados alcance, producto, UX, trazabilidad, riesgos, legal y runbook.
- [x] 2026-08-06 07:17 MST — QA real local concluido en 360/768/1440 px, teclado, reflow 200 %, participante y administrador; cero errores o advertencias de consola finales.
- [x] 2026-08-06 07:21 MST — Gate final repetido y verde: 95 pruebas/896 aserciones, Pint, Composer, auditorías, 96 iconos, Vite, 63 rutas y `git diff --check`.
- [x] 2026-08-06 07:38 MST — Ajuste visual solicitado: “Hermosillo sin Barreras” reutiliza `.is-featured` por slug para alternar las cuatro tarjetas; 7 pruebas/77 aserciones, Pint, build y QA Playwright 1440/768/360 px verdes, sin cambios de CSS, datos o superficies autenticadas.

## Surprises & Discoveries

- El selector genérico `.ff-category-grid` era compartido por landing y formulario: la nueva regla de dos columnas del participante sobrescribía las cuatro columnas de escritorio de la landing. Se acotó a `.ff-participant-submission-wizard-page`; Playwright confirmó 4/2/1 y 2/1 respectivamente.
- La primera ejecución autenticada del servidor de QA devolvió 419 porque `.env.testing` usa deliberadamente `SESSION_DRIVER=array`. Se reinició sólo el servidor efímero con `SESSION_DRIVER=file` y cookie no segura; no se modificó configuración rastreada ni se trató como defecto productivo.
- El primer gate final detectó tres espacios finales en la nueva adenda de QA. Se corrigieron y el gate completo se repitió desde el inicio hasta quedar verde.
- Yarn conserva un aviso de severidad baja. No hay avisos moderados, altos o críticos y se cumple el umbral aprobado.

## Decision Log

- Decision: la migración `down` no elimina ni desactiva la nueva categoría.
  Rationale: puede existir una propuesta relacionada y el rollback no puede destruir ni dejar datos inválidos.
  Date/Author: 2026-08-06 / propietario y Codex.

- Decision: mantener activos los documentos jurídicos v1.0 sin modificar su contenido o hash.
  Rationale: decisión expresa del propietario; la contradicción se registra como riesgo alto aceptado.
  Date/Author: 2026-08-06 / propietario.

- Decision: bloquear la fila del usuario dentro de la transacción antes de recontar propuestas.
  Rationale: serializa creaciones simultáneas de la misma cuenta y evita exceder cuatro sin alterar esquema ni la restricción única por categoría.
  Date/Author: 2026-08-06 / Codex.

- Decision: conservar separadas la evidencia pública productiva previa y la validación autenticada local.
  Rationale: la verificación pública del commit `26256e32…` no equivale a UAT de participante/administrador ni autoriza desplegar esta rama.
  Date/Author: 2026-08-06 / Codex.

- Decision: reutilizar `.is-featured` para los slugs `hermosillo-florece` y `hermosillo-sin-barreras`.
  Rationale: reproduce exactamente el formato solicitado y mantiene la alternancia sin introducir selectores posicionales ni cambiar el CSS compartido.
  Date/Author: 2026-08-06 / propietario y Codex.

## Outcomes & Retrospective

La cuarta categoría quedó implementada de forma aditiva y compatible con los datos existentes. Las superficies públicas, del participante y administrativas consumen la misma relación de categorías; no se añadieron rutas, tablas o APIs. La creación revalida el máximo cuatro dentro de una transacción con bloqueo de la cuenta, y una prueba concurrente real sobre MySQL confirmó que dos solicitudes desde el límite no producen una quinta propuesta.

El gate final terminó con 95 pruebas y 896 aserciones, Pint aprobado, `composer.json` válido, requisitos de plataforma satisfechos, Composer sin advisories, Yarn sin vulnerabilidades moderadas/altas/críticas, 96 iconos verificados, build Vite de tres assets, 63 rutas y diff sin errores. El QA real local cubrió landing, participante y administrador en los viewports aprobados, con foco visible, reflow, ausencia de overflow y consola limpia.

Permanece un riesgo residual alto expresamente aceptado: la Mecánica v1.0 sigue describiendo tres categorías, tres propuestas y una asignación diferente de accesibilidad. También siguen pendientes UAT del propietario, backup productivo verificado, checkout productivo limpio y autorización explícita. No se hizo commit, push ni despliegue durante este ExecPlan.

Una solicitud visual posterior reutilizó el formato destacado de “Hermosillo Florece” en “Hermosillo sin Barreras”. El cambio quedó limitado a la asignación semántica de clase en la landing y su prueba/documentación; no alteró CSS, datos, reglas o módulos autenticados. La validación incremental terminó con 7 pruebas/77 aserciones, Pint y build verdes, y alternancia visual sin overflow ni consola en 1440/768/360 px.
