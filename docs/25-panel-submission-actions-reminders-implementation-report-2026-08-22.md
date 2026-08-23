# Reporte de implementación — acciones y recordatorios del panel

**Fecha:** 2026-08-22 (`America/Hermosillo`)  
**Resultado:** `GO LOCAL/TEST`  
**Producción:** `NOT ACCESSED / NOT VERIFIED / NOT AUTHORIZED`

## Baseline y límites

- Repositorio: `/home/ccortesg/workspace/flowerflow`.
- Rama: `codex/submission-deadline-extension`.
- `HEAD`, upstream y merge-base iniciales: `bffc7d7f4738e0937b276ea9d5d22e3744afe65c`.
- Árbol inicial limpio. Todo el diff permanece local, sin stage, commit o push.
- Guard: `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_DATABASE=flowerflow_testing`, `DB_USERNAME=flowerflow_testing_user`, `SELECT DATABASE()=flowerflow_testing`.
- Sólo se usaron cuentas, propuestas, contenido, correo y archivos sintéticos. No se accedió a producción, AWS, SMTP real ni datos reales. No se modificaron PDFs jurídicos, hashes o aceptaciones históricas.

## Resultado funcional

El listado `panel/propuestas` tiene una séptima columna `Acciones`. Cuando los flags están activos y el actor es un `admin` exacto con el permiso correspondiente, los borradores muestran:

- recordatorio individual con `ri-mail-send-line`;
- registro administrativo con `ri-send-plane-line`;
- estado visual deshabilitado si faltan título, resumen o descripción.

El botón masivo `Enviar recordatorio` considera todos los borradores de la convocatoria activa, no sólo filtros o página visible. La confirmación muestra encontrados, enviables y omitidos. `reviewer` conserva acceso de lectura, sin acciones mutantes.

El recordatorio se entrega únicamente al propietario/representante verificado. El job es cifrado, único, post-commit, usa la cola de correo `database/default`, revalida estado y plazo antes de enviar y aplica cooldown de 24 horas. El correo HTML/texto reutiliza el layout con las marcas Flower Flow y Florece Hermosillo, fecha límite y CTA `Enviar propuesta`.

El CTA abre un GET temporal firmado que nunca escribe. El POST firmado exige CSRF, firma vigente, rate limit, token no consumido y las tres aceptaciones. Permite finalizar sin archivo únicamente si existen título, resumen y descripción. Firma alterada, vencida o cruzada falla cerrada.

La finalización compartida conserva tres modos explícitos:

| Modo | Actor | Archivo | Perfil/equipo | Aceptaciones | Evidencia |
| --- | --- | --- | --- | --- | --- |
| `participant` | propietario autenticado | obligatorio | obligatorio | obligatorias | contrato previo intacto |
| `signed_reminder` | propietario resuelto por token | omitido | obligatorio | obligatorias en POST | reminder consumido transaccionalmente |
| `administrative` | `admin` exacto con permiso | omitido | omitido | no se crean | razón, actor y requisitos omitidos en snapshot |

Los tres modos usan bloqueo transaccional. El administrativo sólo reintenta idempotentemente un snapshot ya administrativo; nunca reetiqueta como excepción un envío previo de otro modo. Se crea como máximo un folio, versión, revisión de admisibilidad, evento y acuse.

## Modelo, permisos, rutas e invariantes

La migración aditiva crea `submission_reminder_batches` y `submission_reminders`, claves foráneas restrictivas, ULID únicos, checks de alcance/estado, unicidad batch/propuesta/destinatario e índices de cooldown. Los modelos están guarded y prohíben delete.

Permisos exclusivos de `admin`:

- `send submission reminders`;
- `administratively finalize submissions`.

Flags default-off:

- `FLOWERFLOW_SUBMISSION_REMINDERS_ENABLED=false`;
- `FLOWERFLOW_ADMIN_FINALIZATION_ENABLED=false`;
- `FLOWERFLOW_REMINDER_LINK_TTL_MINUTES=2880`;
- `FLOWERFLOW_REMINDER_COOLDOWN_HOURS=24`.

Rutas añadidas:

- `GET|POST /panel/propuestas/recordatorios[/nuevo]`;
- `POST /panel/propuestas/{submission}/recordatorios`;
- `GET|POST /panel/propuestas/{submission}/envio-administrativo`;
- `GET /propuestas/{submission}/recordatorio/{reminder}/confirmar`;
- `POST /propuestas/{submission}/recordatorio/{reminder}/enviar`.

Las rutas panel aplican auth, verificación, rol, permiso, flag y throttle; la acción administrativa exige password reciente. Las públicas aplican flag, firma temporal y throttle, y el POST conserva CSRF. Policies, Actions y revalidación transaccional son autoridad; ocultar el botón no concede acceso.

Auditoría incorporada:

- `submission_reminder.batch_requested`;
- `submission_reminder.queued|sent|failed`;
- `submission.submitted_from_reminder`;
- `submission.submitted_administratively`.

La metadata operativa conserva IDs técnicos, conteos y reason codes; no copia destinatarios, contenido, comentarios, archivos, URL firmada o razón administrativa. La razón sólo vive en el snapshot privado inmutable y no entra al paquete ciego M5.

## Exportaciones

No se alteró el generador XLSX ni se convirtió a síncrono. Se añadió:

- `php artisan flowerflow:exports-diagnose --json`, comando de sólo lectura sin payloads, secretos o paths;
- advertencia para una exportación propia que continúe `queued` más que `FLOWERFLOW_EXPORT_STALLED_AFTER_MINUTES`;
- cobertura automatizada de que el diagnóstico no modifica jobs, exports o archivos.

Evidencia local real: sobre `flowerflow_testing` se creó una exportación sintética, se confirmó el job en `database/exports`, se ejecutó un worker `--once`, el estado pasó a `completed`, se creó un XLSX en disco privado y la descarga autorizada quedó disponible. Al finalizar se retiraron registro y archivo; jobs y failed jobs quedaron en cero.

Esto confirma que el writer funciona cuando existe migración y worker. La causa exacta del ambiente que permanece en `En espera` es `POR_CONFIRMAR`; allí deben revisarse migración, configuración cacheada, cola `exports`, failed jobs, disco y el proceso Supervisor exclusivo de Flower Flow. Esta tarea no autorizó esa inspección ni un reinicio productivo.

## Migración y rollback

Con el guard exacto se verificó:

1. forward de la migración;
2. rollback sin evidencia;
3. segundo forward;
4. inserción de una auditoría sintética M6-panel;
5. rollback rechazado con exit code 1;
6. retiro exclusivo de la evidencia sintética y confirmación de migración aplicada.

El rollback operativo primario es apagar ambos flags. No se borran recordatorios, snapshots, eventos, auditoría o envíos administrativos. El `down()` sólo retira tablas y permisos cuando no existe evidencia.

## Pruebas y gates

| Gate | Resultado |
| --- | --- |
| Baseline dirigido | 19 tests / 247 assertions |
| Recordatorios, concurrencia, administración, panel y exports dirigidos | verde |
| Administración final después del cierre por modo | 6 tests / 67 assertions |
| Suite completa final | 179 tests / 2,130 assertions, 601.58 s |
| `vendor/bin/pint --test` | pass |
| `composer validate --strict` | valid |
| `composer check-platform-reqs` | pass, PHP 8.3.33 |
| `composer audit` | 0 advisories |
| `corepack yarn audit` | exit 2: 1 advisory low de Quill 2.0.3, sin parche |
| `corepack yarn icons:check` | 99 iconos verificados |
| `scripts/build_frontend_production.sh` | pass, 784 módulos |
| JSON y enlaces Markdown locales | pass |
| `php artisan route:list --except-vendor` | 80 rutas |
| `php artisan schedule:list` | 1 tarea de purga de exports |
| `php artisan migrate:status --env=testing` | 20 migraciones `Ran` |
| `git diff --check` | pass |

No estaba instalado `gitleaks`; se ejecutó el scan textual disponible sobre el diff y la revisión manual de logs/auditoría, sin hallazgos de secretos o PII. El advisory de Quill es heredado y no tiene fix publicado; la entrada HTML continúa sanitizada en servidor.

## UAT Firefox local

Se usó Firefox con datos sintéticos y flags habilitados sólo en el proceso local:

- 1440×900: columna/botones, lote completo y conteos independientes de filtros;
- 1024×768: correo/confirmación firmada, tres legales v1.1, envío sin archivo, doble pestaña y 410 al reusar;
- 390×844: acciones, estado incompleto, formulario administrativo, foco en errores y reflow sin desbordamiento;
- firma alterada 403, cruce de proposal/reminder 404 y token consumido 410;
- título XSS renderizado como texto, sin nodo hostil ni ejecución;
- teclado, enlace para saltar contenido, zoom, foco y consola normal sin errores;
- excepción administrativa con razón, cero archivos y cero aceptaciones ajenas;
- XLSX procesado por worker local dedicado y descargable con autorización.

Las capturas relevantes permanecen en `output/playwright/` ignorado por Git. El wrapper local del skill Playwright presentaba CRLF, por lo que se usó el CLI oficial de Playwright. `SESSION_DRIVER=file` fue necesario únicamente en el servidor UAT para conservar CSRF entre requests; `.env.testing` no se modificó.

Después del UAT y de la suite, `flowerflow_testing` quedó con cero users, submissions, reminder batches, reminders, exports, jobs y failed jobs.

## Archivos principales

- Configuración/migración: `.env.example`, `config/flowerflow.php`, `database/migrations/2026_08_22_190000_create_submission_reminders_and_permissions.php`.
- Dominio: `FinalizeSubmission`, `QueueSubmissionReminders`, `RefreshSubmissionReminderBatch`, enums/modelos/policy y `SubmissionFinalizationEligibility`.
- HTTP: controladores, middleware, Form Request, rutas y vistas panel/públicas.
- Correo: `SendSubmissionDraftReminder`, `SubmissionDraftReminder`, `SubmissionAdministrativelyFinalized` y sus vistas HTML/texto.
- Operación: `DiagnoseSubmissionExports`, advertencia en el listado y pruebas de exportación.
- QA: `SubmissionReminderTest`, `SubmissionReminderConcurrencyTest`, `AdministrativeSubmissionFinalizationTest`, contratos del panel y exportación.
- Documentación: ExecPlan, alcance, modelo, seguridad, UX, QA, riesgos, decisiones, handoff, product spec y trazabilidad.

### Inventario exacto del diff local

~~~text
.env.example
.agent/execplans/flowerflow-panel-submission-actions-reminders.md
app/Actions/FinalizeSubmission.php
app/Actions/QueueSubmissionReminders.php
app/Actions/RefreshSubmissionReminderBatch.php
app/Console/Commands/DiagnoseSubmissionExports.php
app/Enums/SubmissionFinalizationMode.php
app/Enums/SubmissionReminderBatchScope.php
app/Enums/SubmissionReminderBatchStatus.php
app/Enums/SubmissionReminderStatus.php
app/Http/Controllers/Panel/AdministrativeSubmissionFinalizationController.php
app/Http/Controllers/Panel/SubmissionController.php
app/Http/Controllers/Panel/SubmissionReminderController.php
app/Http/Controllers/SubmissionReminderConfirmationController.php
app/Http/Middleware/EnsureAdministrativeFinalizationEnabled.php
app/Http/Middleware/EnsureSubmissionRemindersEnabled.php
app/Http/Requests/AdministrativeFinalizeSubmissionRequest.php
app/Jobs/SendSubmissionDraftReminder.php
app/Mail/SubmissionAdministrativelyFinalized.php
app/Mail/SubmissionDraftReminder.php
app/Models/Submission.php
app/Models/SubmissionEvent.php
app/Models/SubmissionReminder.php
app/Models/SubmissionReminderBatch.php
app/Models/User.php
app/Policies/SubmissionPolicy.php
app/Providers/AppServiceProvider.php
app/Services/SubmissionFinalizationEligibility.php
app/Services/SubmissionReminderPreview.php
bootstrap/app.php
config/flowerflow.php
database/migrations/2026_08_22_190000_create_submission_reminders_and_permissions.php
database/seeders/FlowerFlowSeeder.php
docs/01-functional-scope.md
docs/03-data-model.md
docs/04-security-privacy.md
docs/05-ux-ui.md
docs/08-testing-qa.md
docs/09-risk-register.md
docs/10-open-questions.md
docs/11-operations-handoff.md
docs/25-panel-submission-actions-reminders-implementation-report-2026-08-22.md
docs/CODEX_PROJECT_HANDOFF.md
docs/product-spec.md
docs/requirements-traceability.md
resources/assets/vendor/fonts/iconify/iconify.css
resources/views/mail/submission-administratively-finalized-text.blade.php
resources/views/mail/submission-administratively-finalized.blade.php
resources/views/mail/submission-draft-reminder-text.blade.php
resources/views/mail/submission-draft-reminder.blade.php
resources/views/panel/submissions/administrative-finalization.blade.php
resources/views/panel/submissions/index.blade.php
resources/views/panel/submissions/reminders/create.blade.php
resources/views/submissions/reminder-confirmation.blade.php
resources/views/submissions/reminder-submitted.blade.php
routes/web.php
tests/Feature/AdministrativeSubmissionFinalizationTest.php
tests/Feature/PanelSubmissionContractTest.php
tests/Feature/SubmissionExportTest.php
tests/Feature/SubmissionReminderConcurrencyTest.php
tests/Feature/SubmissionReminderTest.php
~~~

## Riesgos residuales y siguiente puerta

- Worker, migración, cache, SMTP y causa del estado `En espera` en producción siguen `POR_CONFIRMAR`.
- Entregabilidad real, rebotes y reputación SMTP no se probaron localmente.
- El advisory bajo de Quill 2.0.3 sigue abierto sin parche; mantener sanitización y no usar HTML exportado por Quill como autoridad.
- La activación debe ser gradual con ambos flags apagados por defecto, backup/restore, migración revisada, worker Flower Flow, smoke/UAT autorizado y rollback por flags.
- No ejecutar `down()` con evidencia y no borrar jobs/exportaciones para desbloquear una cola.

No se autoriza despliegue por este reporte.
