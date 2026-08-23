# Panel de propuestas — recordatorios, envío administrativo y recuperación de exports

Este ExecPlan es un documento vivo y se rige por `.agent/PLANS.md`. El propietario aprobó expresamente este milestone el 2026-08-22. El trabajo es exclusivamente local/test: no autoriza stage, commit, push, despliegue, producción, AWS ni SMTP real.

## Propósito y resultado observable

Permitir que sólo `admin` pueda enviar recordatorios individuales o masivos a la cuenta propietaria de propuestas en borrador y, mediante una acción separada y auditada, finalizar administrativamente un borrador con título, resumen y descripción aunque carezca de archivos, perfil/elegibilidad o aceptaciones de envío. El correo ofrece una confirmación temporal firmada, sin login, que exige un POST explícito y las tres aceptaciones jurídicas antes de finalizar sin archivo. El listado incorpora una columna final `Acciones` y conserva al reviewer como lector.

La exportación XLSX permanece asíncrona, privada y en la cola `exports`. El milestone añade diagnóstico/advertencia de espera prolongada y evidencia local de que un worker dedicado completa la exportación; no cambia el contrato de ADR-0007 ni opera workers productivos.

## Baseline comprobado

- Repositorio y Git toplevel: `/home/ccortesg/workspace/flowerflow`.
- Rama: `codex/submission-deadline-extension`.
- `HEAD`, upstream y merge-base: `bffc7d7f4738e0937b276ea9d5d22e3744afe65c`.
- Árbol inicial limpio; status, diffs y archivos no rastreados sin salida.
- Guard de pruebas: `APP_ENV=testing`, MySQL `127.0.0.1`, base `flowerflow_testing`, usuario `flowerflow_testing_user` y `SELECT DATABASE()=flowerflow_testing`; no se imprimieron secretos.
- Baseline dirigido: 19 pruebas y 247 aserciones verdes en flujo, wizard, panel y exportación.
- Entorno local de desarrollo observado, no productivo: sin worker activo, `QUEUE_CONNECTION=sync`, job export configurado en `database/exports` y migración de exports pendiente. Producción permanece `POR_CONFIRMAR`.

## Alcance y exclusiones

Incluye migraciones aditivas, permisos exclusivos, flags, modelos guarded/no eliminables, Policies, Form Requests, Actions y jobs transaccionales, correo HTML/texto, rutas firmadas, panel accesible, auditoría redactada, diagnóstico de exports, pruebas, UAT local y documentación.

Excluye recordatorios programados, campañas de marketing, destinatarios integrantes, envío por GET, impersonación, modificación de PDFs/hashes/aceptaciones históricas, cambios de archivos privados, M7–M10, stage/commit/push, producción/AWS/Supervisor/SMTP real y datos reales.

## Contratos e invariantes

- Permisos nuevos: `send submission reminders` y `administratively finalize submissions`, sólo para rol exacto `admin`.
- Flags default-off: `FLOWERFLOW_SUBMISSION_REMINDERS_ENABLED` y `FLOWERFLOW_ADMIN_FINALIZATION_ENABLED`.
- Recordatorio individual y masivo sólo considera `draft`; el destinatario es exclusivamente `submission.user` con correo verificado. Un cooldown de 24 horas omite filas ya queued/processing/sent.
- El lote masivo usa todos los borradores de la convocatoria activa; filtros y paginación de UI no reducen el conjunto.
- GET firmado sólo lee. POST firmado exige firma vigente, CSRF, token no consumido, owner verificado, flag/plazo/convocatoria válidos, contenido mínimo y tres aceptaciones; omite sólo el archivo.
- El envío participante ordinario conserva todos sus requisitos, incluido archivo.
- El envío administrativo exige `draft`, contenido mínimo, convocatoria/flag/plazo válidos, permiso, password reciente, confirmación y razón 20–1,000. No crea aceptaciones en nombre del participante y omite archivo, correo/perfil/elegibilidad/equipo/legales.
- Toda finalización bloquea la propuesta, es idempotente, crea como máximo un folio, una versión y una revisión de admisibilidad y conserva modo/actor/excepciones en snapshot/evento/auditoría.
- La fecha autoritativa no puede exceder `2026-08-23 23:59:59 America/Hermosillo`; config y `competitions.closes_at` deben coincidir o la mutación falla cerrada.
- Ningún log/audit de recordatorio incluye correo, nombre, contenido, URL firmada, archivo o PII.
- La proyección ciega ignora por allowlist los metadatos de finalización y recordatorio.

## Modelo y rutas

`submission_reminder_batches`: ULID, alcance `single|all_drafts`, solicitante restrictivo, estado, conteos y timestamps UTC.

`submission_reminders`: ULID, batch/propuesta/destinatario restrictivos, estado de entrega, código de fallo redactado, vencimiento/consumo/envío/fallo UTC y unicidad batch+submission+recipient. Los modelos son guarded y prohíben delete; el rollback no borra evidencia.

Rutas panel estáticas antes de `/{submission}`:

- `GET /panel/propuestas/recordatorios/nuevo` y `POST /panel/propuestas/recordatorios`.
- `POST /panel/propuestas/{submission}/recordatorios`.
- `GET|POST /panel/propuestas/{submission}/envio-administrativo`.

Rutas públicas firmadas:

- `GET /propuestas/{submission}/recordatorio/{reminder}/confirmar`.
- `POST /propuestas/{submission}/recordatorio/{reminder}/enviar`.

## Plan de implementación

1. Crear migración de tablas/permisos, enums/modelos/relaciones y configuración default-off con rollback fail-closed.
2. Implementar elegibilidad temporal/contenido compartida y refactor de finalización por modos sin debilitar el modo participante.
3. Implementar lotes/actions/jobs de recordatorio, Mailable HTML/texto y confirmación firmada de sólo lectura + POST.
4. Implementar acción/controlador/request/pantalla de finalización administrativa con password confirmation, razón y auditoría.
5. Ampliar índice con columna/botones/confirmación masiva y advertencia de export en espera prolongada.
6. Añadir diagnóstico read-only de exports y prueba de integración del worker `database/exports`.
7. Añadir pruebas de permisos, estados, concurrencia, plazo, legales, XSS, logs, mail y regresiones M1–M6.
8. Ejecutar forward/rollback/forward, suite/gates, UAT Firefox local y actualizar documentación/evidencia.

## Validación

Tras demostrar nuevamente el guard exacto:

    php artisan test tests/Feature/SubmissionReminderTest.php tests/Feature/AdministrativeSubmissionFinalizationTest.php tests/Feature/PanelSubmissionContractTest.php tests/Feature/SubmissionFlowTest.php tests/Feature/SubmissionWizardTest.php tests/Feature/SubmissionExportTest.php
    php artisan test
    vendor/bin/pint --test
    composer validate --strict
    composer check-platform-reqs
    composer audit
    corepack yarn audit
    scripts/build_frontend_production.sh
    php artisan route:list --except-vendor
    php artisan schedule:list
    php artisan migrate:status
    git diff --check

También se validan JSON/Markdown, secretos/PII/logs y Firefox 1440×900, 1024×768 y 390×844 con datos exclusivamente sintéticos.

## Despliegue y rollback

No se despliega. En un release posterior se requieren backup/restore, migraciones, flags explícitos, cache, worker Flower Flow escuchando `high,exports,default,low`, scheduler y smoke/UAT autorizado. No usar `restart all` ni tocar otros servicios.

Rollback operativo: apagar ambos flags. La migración `down()` sólo retira tablas/permisos si no existe ningún reminder, evento o audit administrativo; con evidencia aborta. Nunca se borran propuestas, snapshots, aceptaciones, recordatorios ni XLSX para revertir.

## Progreso

- [x] 2026-08-22 18:41 MST — Baseline Git exacto y árbol limpio comprobados.
- [x] 2026-08-22 18:41 MST — AGENTS, PLANS, ExecPlans M6/export y ADR 0003/0004/0005/0007/0008 revisados.
- [x] 2026-08-22 18:41 MST — Guard exacto de `flowerflow_testing` demostrado sin secretos.
- [x] 2026-08-22 18:41 MST — Baseline dirigido 19/247 verde.
- [x] 2026-08-22 — Modelo, permisos, rutas, Actions, jobs, correos HTML/texto, confirmaciones y panel implementados.
- [x] 2026-08-22 — Diagnóstico read-only de exports y advertencia de espera prolongada implementados; worker local `database/exports` verificado de extremo a extremo.
- [x] 2026-08-22 — Migración forward/rollback/forward y rechazo de rollback con evidencia sintética verificados bajo el guard exacto.
- [x] 2026-08-22 — Suite final 179/2,130, Pint, Composer, Vite, inventarios, JSON, Markdown y diff validados.
- [x] 2026-08-22 — UAT Firefox sintético completado en 1440×900, 1024×768 y 390×844; datos y archivos temporales retirados.
- [x] 2026-08-22 — Documentación y reporte `docs/25-panel-submission-actions-reminders-implementation-report-2026-08-22.md` actualizados.

## Decisiones

- El propietario eligió confirmación temporal firmada con segundo clic POST y sin login; nunca existe mutación GET.
- Sólo el representante/propietario recibe recordatorios; integrantes opcionales quedan excluidos.
- El modo administrativo exige sólo contenido mínimo y deja evidencia explícita de que no existieron aceptaciones del participante.
- Plazo, convocatoria activa y flags siguen siendo gates sistémicos; la autorización no permite envíos tardíos.

## Hallazgos y resultados

- La primera migración falló localmente porque un índice autogenerado excedía el límite de 64 caracteres de MySQL. Se le asignó el nombre explícito `reminder_batches_requester_created_index`; forward/rollback/forward quedó verde.
- El build detectó correctamente que `ri-mail-send-line` no estaba en el CSS derivado. `corepack yarn icons:write` regeneró el artefacto fuente de 99 iconos y el build posterior pasó; `public/build` no se editó manualmente.
- El wrapper local del skill Playwright tenía finales CRLF incompatibles con `/usr/bin/env bash`; el UAT continuó con el CLI oficial `npx --package @playwright/cli playwright-cli` y Firefox.
- `SESSION_DRIVER=array` produjo 419 en el navegador real porque no conserva CSRF entre requests. El servidor UAT aislado usó `SESSION_DRIVER=file`; no se alteró `.env.testing`.
- El UAT encontró dos mensajes `required` heredados en inglés en el formulario administrativo. Se añadieron traducciones específicas y pruebas de regresión.
- La revisión final cerró un falso éxito posible: un reintento administrativo sólo es idempotente si el snapshot existente ya declara modo `administrative`; un envío previo por otro modo se rechaza sin modificar evidencia.
- Exportación local real: un job sintético en `database/exports` pasó con `queue:work ... --once` de `queued` a `completed`, produjo XLSX privado descargable y dejó cero jobs fallidos. La causa del ambiente reportado sigue `POR_CONFIRMAR` hasta revisar allí migración, cache, cola y Supervisor.
- Suite completa final: 179 pruebas y 2,130 aserciones, 601.58 s. Las pruebas dirigidas de recordatorio, concurrencia, administración, panel y exports también quedaron verdes.
- `composer validate --strict`, platform requirements, Composer audit, Pint, build, JSON, enlaces Markdown, inventario de 80 rutas, scheduler y 20 migraciones: verdes. `yarn audit` conserva un advisory bajo conocido de Quill 2.0.3, sin parche disponible; la sanitización servidor permanece activa.
- UAT Firefox: recordatorio individual/masivo, propietario único, correo dual, GET sin mutación, POST firmado, doble pestaña/410, firma alterada/403, cruce/404, envío sin archivo, excepción administrativa, XSS escapado, teclado, foco, reflow, zoom y consola verificados. Las páginas normales quedaron sin errores de consola.
- Estado final de `flowerflow_testing`: cero users, submissions, reminder batches/reminders, exports, jobs y failed jobs. No hubo producción, SMTP real, PDFs, stage, commit ni push.

## Resultado

`GO LOCAL/TEST` para este milestone. Activación productiva, migración, cache, worker y smoke/UAT externo siguen `NOT AUTHORIZED / POR_CONFIRMAR` y requieren una ventana separada con backup y rollback.
