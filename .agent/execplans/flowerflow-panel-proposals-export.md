# Corrección de paginación y exportación privada de propuestas

Este ExecPlan es un documento vivo y se rige por `.agent/PLANS.md`. La autorización proviene de la aprobación expresa del propietario el 2026-08-11. El trabajo es exclusivamente local/test: no autoriza stage, commit, push, acceso a AWS ni despliegue.

## Purpose / Big Picture

Corregir la paginación sobredimensionada de `/panel/propuestas` y `/panel/admisibilidad` alineando el renderer global de Laravel con Bootstrap 5. Añadir al panel una exportación XLSX de todas las propuestas en estado borrador o enviada, con datos de contacto y proyecto, relaciones normalizadas y enlaces autenticados a cada archivo privado.

El resultado observable será que la paginación conserve controles compactos y accesibles al superar 25 registros, y que una persona administradora con permiso explícito y contraseña confirmada pueda solicitar, consultar y descargar durante 24 horas un XLSX privado auditado. Un usuario anónimo, participante, reviewer sin permiso o administrador distinto del solicitante no podrá descargar el export ni los anexos enlazados.

## Status and Scope

- Rama: `codex/panel-proposals-export`.
- Base exacta: `3fd9ddbf3dcfeacbbc9f2c459ece5c4220670a45`.
- Incluye paginación Bootstrap global, permiso `export submissions`, Policy de descarga privada, auditoría de archivos y exports, tabla `submission_exports`, job/servicio XLSX, disco privado, purga horaria, interfaz y pruebas locales.
- El XLSX incluye exclusivamente `draft` y `submitted`; `withdrawn` queda fuera.
- Para enviados usa `submission_versions.snapshot`; para borradores usa el estado actual.
- Incluye nombres, correo, celular, colonia, preferencia WhatsApp, equipo/integrantes, contenido completo en texto plano, metadatos funcionales, enlaces externos y enlaces autenticados a documentos/imágenes.
- Excluye fecha de nacimiento, contraseñas, 2FA, sesiones, IP/user-agent, rutas internas, hashes, claves de idempotencia, notas internas, comprobantes de residencia y archivos de aclaraciones.
- Excluye enlaces anónimos, DataTables/JSZip cliente, envío por correo, reportes de evaluación, producción y cambios en AWS.
- Retención aprobada: 24 horas desde la finalización; la purga elimina únicamente el XLSX temporal, nunca propuestas ni anexos.
- `PENDING`: UAT del propietario, backup/rollback verificados y worker/scheduler productivos antes de desplegar.

## Context and Invariants

- Laravel 12 usa `pagination::tailwind` por defecto; la aplicación carga Bootstrap 5 y no contiene las utilidades Tailwind que dimensionan/ocultan el paginator.
- Sólo `panel.submissions.index` y `panel.admissibility.index` invocan `links()` actualmente.
- Los anexos permanecen en storage privado con `serve=false`; la URL del XLSX nunca sustituye autenticación ni Policy.
- `download private files` es distinto de `view submissions`; el panel debe exigir ambos para personal, conservando descarga por ownership para participantes.
- El export pertenece al usuario solicitante incluso frente a otro administrador.
- Las fechas se persisten en UTC y el XLSX las presenta explícitamente en `America/Hermosillo`.
- Todo dato controlado por usuario se escribe como string literal; nunca como fórmula de Excel.
- El job sólo conserva identificadores y filtros redactados en la cola; el archivo final vive en el disk `exports` privado.

## Model and Contracts

Tabla aditiva `submission_exports`:

- `public_id`, `requested_by_user_id`, `status`, `filters`, `disk`, `path`, `file_name`;
- conteos de propuestas, contactos, integrantes, archivos y enlaces;
- `expires_at`, `completed_at`, `failed_at`, `failure_code`, timestamps;
- índices por solicitante/estado y expiración.

Estados respaldados: `queued`, `processing`, `completed`, `failed`, `expired`.

Rutas nuevas bajo `/panel/propuestas/exportaciones`, con `auth`, `verified`, `view panel` y `export submissions`:

- `GET` de preparación y descarga usan `password.confirm`;
- `POST` revalida en servidor la marca reciente de confirmación, solicita un export y despacha el job después del commit;
- `GET` de descarga sirve sólo el export completado, vigente y propio.

La página lista los cinco exports recientes de la cuenta y sus estados. El job usa la conexión/cola configurada, procesa propuestas por bloques, genera cinco hojas (`Propuestas`, `Contactos`, `Integrantes`, `Archivos`, `Enlaces externos`) y registra solicitud, finalización, descarga, fallo y expiración sin PII completa.

## Plan of Work

1. Establecer baseline MySQL exacto, suite y gates; registrar advisories previos.
2. Añadir ExecPlan/ADR y resolver de forma mínima el advisory bloqueante de Composer.
3. Configurar `Paginator::useBootstrapFive()` y cubrir ambos listados con más de 25 registros.
4. Endurecer `SubmissionPolicy::downloadFile`, exigir `download private files` al personal y auditar descargas.
5. Añadir migración, enum/model/Policy, permiso, disco privado y configuración de export.
6. Añadir writer XLSX OpenSpout, job idempotente, controlador, rutas, purga e interfaz.
7. Cubrir permisos positivos/negativos, snapshot frente a draft, hojas/celdas, enlaces, fórmula hostil, fechas, expiración, fallos y almacenamiento.
8. Actualizar documentación, trazabilidad y registro de dependencias.
9. Ejecutar suite/gates, validar estructura XLSX y renderizar hojas para inspección visual.

## Validation

Desde `/home/ccortesg/workspace/flowerflow` y sólo contra MySQL `flowerflow_testing`/`flowerflow_testing_user` probado con `SELECT DATABASE()`:

    php artisan test
    vendor/bin/pint --test
    composer validate --strict --no-check-publish
    composer check-platform-reqs --no-dev
    composer audit --locked
    corepack yarn audit --groups dependencies --level moderate
    scripts/build_frontend_production.sh
    php artisan route:list
    git diff --check

Además:

- migración forward/rollback sólo en la base desechable confirmada;
- archivo XLSX abierto por lector independiente y revisión de hojas, valores, links y ausencia de fórmulas;
- render visual con LibreOffice/Poppler si están disponibles;
- QA real del panel en 360/768/1440 px, teclado, foco, zoom 200 %, consola y overflow;
- pruebas de anónimo, participante, reviewer, permiso revocado, otro admin, export vencido y archivo cruzado.

## Deployment and Rollback

No se despliega en este milestone. Antes de producción se requieren backup, UAT, extensión `zip`, worker de la cola de exports, scheduler y smoke autenticado. El rollback funcional deshabilita/retira las rutas y el botón; antes de revertir la migración se ejecuta la purga privada de XLSX. El `down()` elimina sólo `submission_exports`; jamás elimina propuestas o anexos. OpenSpout y la configuración pueden retirarse tras confirmar que no quedan jobs pendientes.

## Changed Files

- `.env.example`
- `.agent/execplans/flowerflow-panel-proposals-export.md`
- `app/Console/Commands/PurgeExpiredSubmissionExports.php`
- `app/Enums/SubmissionExportStatus.php`
- `app/Http/Controllers/Panel/SubmissionController.php`
- `app/Http/Controllers/Panel/SubmissionExportController.php`
- `app/Http/Controllers/SubmissionController.php`
- `app/Jobs/GenerateSubmissionExport.php`
- `app/Models/SubmissionExport.php`
- `app/Models/User.php`
- `app/Policies/SubmissionExportPolicy.php`
- `app/Policies/SubmissionPolicy.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/SubmissionWorkbookWriter.php`
- `composer.json`
- `composer.lock`
- `config/filesystems.php`
- `config/flowerflow.php`
- `database/migrations/2026_08_11_180000_create_submission_exports_table.php`
- `database/migrations/2026_08_11_180100_add_export_submissions_permission.php`
- `database/seeders/FlowerFlowSeeder.php`
- `docs/07-deployment-aws-ec2.md`
- `docs/08-testing-qa.md`
- `docs/adr/0007-private-submission-xlsx-exports.md`
- `docs/dependency-register.md`
- `docs/requirements-traceability.md`
- `docs/template-overrides.md`
- `resources/assets/vendor/fonts/iconify/iconify.css`
- `resources/views/panel/submissions/index.blade.php`
- `resources/views/panel/submissions/exports/create.blade.php`
- `routes/console.php`
- `routes/web.php`
- `tests/Feature/PanelPaginationRenderingTest.php`
- `tests/Feature/PanelSubmissionContractTest.php`
- `tests/Feature/SubmissionExportTest.php`

## Progress

- [x] 2026-08-11 10:52 MST — Preflight Git limpio; creada `codex/panel-proposals-export` desde `3fd9ddb…`, sin stage/commit/push.
- [x] 2026-08-11 10:56 MST — Entorno probado: `testing`, driver MySQL, configuración/`SELECT DATABASE()` en `flowerflow_testing` y usuario exclusivo `flowerflow_testing_user`.
- [x] 2026-08-11 10:58 MST — Baseline funcional: 95 pruebas/898 aserciones y Pint verdes.
- [!] 2026-08-11 10:59 MST — El baseline de Composer detectó seis advisories nuevos en `league/commonmark` 2.8.3, cuatro altos; el gate se detuvo antes de Yarn/build. Laravel admite 2.9+ y la actualización mínima se validará separadamente.
- [x] 2026-08-11 11:02 MST — Actualización mínima de seguridad: sólo `league/commonmark` 2.8.3 → 2.10.0; `composer audit --locked` quedó sin advisories.
- [x] 2026-08-11 11:04 MST — OpenSpout 4.32.0 incorporado en lock; plataforma PHP 8.3 y extensiones requeridas verificadas.
- [x] 2026-08-11 11:13 MST — Paginación Bootstrap global, permiso/Policies, auditoría, tabla/estados, writer/job, disco privado, purga, rutas e interfaz implementados.
- [x] 2026-08-11 11:17 MST — Migraciones `submission_exports` y permiso admin probadas en rollback/forward sobre `flowerflow_testing`; desarrollo permaneció intacto.
- [!] 2026-08-11 11:22 MST — OpenPyXL detectó colores de fuente de diez dígitos producidos por pasar ARGB a una API de OpenSpout que antepone alfa. Se corrigieron fuentes a RGB de seis dígitos, se conservó fill ARGB válido y se añadió regresión sobre `styles.xml`.
- [x] 2026-08-11 11:27 MST — Gate final verde: 102 pruebas/979 aserciones, Pint, Composer validate/platform/audit, Yarn audit bajo aceptado, 97 iconos, Vite, 66 rutas y `git diff --check`.
- [x] 2026-08-11 11:27 MST — XLSX sintético abierto por OpenPyXL: cinco hojas, filtros, `A2` congelado, estilos válidos, link autenticado, fecha de nacimiento ausente y cero fórmulas inesperadas.
- [!] 2026-08-11 11:27 MST — QA browser automatizado no concluyó: Browser no pudo inicializar su runtime desde WSL/Windows y Playwright no tiene Chrome/Firefox instalado. Feature tests y build son verdes; recorrido visual autenticado queda para UAT.

## Decision Log

- Decision: configurar la paginación Bootstrap globalmente, no parchear SVG ni cada vista.
  Rationale: resuelve las dos pantallas actuales y evita repetir el defecto en futuros `links()`.
  Date/Author: 2026-08-11 / propietario y Codex.

- Decision: los enlaces de anexos requieren login y permiso vigente; no se crean bearer URLs anónimas.
  Rationale: el XLSX puede compartirse o conservarse y no debe otorgar acceso por posesión.
  Date/Author: 2026-08-11 / propietario y Codex.

- Decision: usar OpenSpout 4.32.0, fijado en lock, y generación asíncrona privada.
  Rationale: es compatible con PHP 8.3 y escribe XLSX por streaming sin exponer PII al navegador.
  Date/Author: 2026-08-11 / propietario y Codex.

- Decision: mantener las relaciones multivaluadas en hojas separadas.
  Rationale: evita concatenaciones ambiguas y conserva un contrato tabular auditable.
  Date/Author: 2026-08-11 / propietario y Codex.

## Outcomes & Retrospective

El milestone quedó implementado en local/test sin stage, commit, push ni despliegue. La causa de los iconos fue corregida en la fuente global: Laravel ahora usa su vista Bootstrap 5 y desaparecen los SVG Tailwind sin utilidades de tamaño tanto en propuestas como en admisibilidad.

La exportación genera por streaming un XLSX privado de borradores y enviados, usa snapshot para enviados, separa relaciones en cinco hojas, protege contra fórmulas hostiles, enlaza anexos mediante rutas autenticadas y expira en 24 horas. Crear, completar, descargar, fallar y expirar queda auditado sin mensajes sensibles. Sólo `admin` recibe el permiso nuevo; otro admin tampoco descarga un export ajeno.

La validación automatizada y estructural está completa. Persisten como puertas externas: UAT visual autenticado, instalación/validación del worker `exports` y scheduler, prueba de carga, backup y rollback antes de cualquier despliegue.
