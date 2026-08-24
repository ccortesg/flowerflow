# Exportación privada de contactos de propuestas enviadas

Este ExecPlan es un documento vivo y se rige por `.agent/PLANS.md`. La autorización proviene de la aprobación expresa del propietario el 2026-08-24. El trabajo se limita a código, pruebas, documentación y UAT local con datos sintéticos; no autoriza stage, commit, push, despliegue ni acceso a producción.

## Purpose / Big Picture

Añadir en `/panel/propuestas`, inmediatamente después de `Exportar a Excel`, una exportación XLSX específica de contactos. El libro contendrá una fila por cada propuesta `submitted` y las columnas nombre completo, correo electrónico, teléfono, nombre y descripción del proyecto.

El resultado observable será que una cuenta autorizada pueda solicitar, consultar y descargar un XLSX privado de contactos enviados usando el mismo permiso, confirmación de contraseña, cola `exports`, ownership, auditoría, almacenamiento privado y expiración de la exportación completa actual.

## Status and Scope

- Rama: `codex/submission-deadline-extension`.
- Baseline exacto: HEAD/upstream/merge-base `9df0828a41733cd0b35128f71698fee6f3cfd1ab`; árbol limpio.
- Incluye un tipo de exportación explícito, writer de una hoja, rutas de confirmación/solicitud, botón, identificación del tipo en exportaciones recientes, pruebas y documentación.
- Incluye todas las propuestas `submitted` de la base, sin depender de convocatoria, filtros o paginación del listado.
- Para cada enviada usa exclusivamente su snapshot inmutable; no consulta el perfil o contenido vivo como fallback.
- Excluye borradores, retiradas, anexos, integrantes, cambios de permisos, migraciones, dependencias, workers nuevos y producción.

## Context and Invariants

- ADR 0007 exige backend asíncrono, OpenSpout, disk privado, permiso `export submissions`, contraseña reciente, ownership, auditoría redactada y expiración de 24 horas.
- `submission_exports.filters` ya es JSON y admite discriminar `full` y `submitted_contacts` sin modificar el esquema.
- Un registro histórico sin `filters.kind` conserva el significado `full`; un valor desconocido falla cerrado durante la generación.
- El snapshot válido debe contener los arreglos `submission` y `participant`, además de título, descripción y correo en texto. `participant.profile` puede ser `null` por una finalización administrativa válida; nombre y teléfono quedan vacíos en ese caso.
- Todo valor controlado por usuario se escribe como `StringCell`; nunca se interpreta como fórmula.

## Model and Contracts

Enum respaldado `SubmissionExportKind`: `full` y `submitted_contacts`. No se crea tabla ni columna nueva.

Rutas protegidas por el grupo actual del panel, `view submissions` y `export submissions`:

- `GET /panel/propuestas/exportaciones/contactos/nueva` (`panel.submissions.exports.contacts.create`) con `password.confirm`.
- `POST /panel/propuestas/exportaciones/contactos` (`panel.submissions.exports.contacts.store`) con CSRF, revalidación de contraseña reciente y `throttle:panel-mutations`.

El writer `SubmissionContactsWorkbookWriter` crea una sola hoja `Contactos`, con encabezados exactos `Nombre completo`, `Correo electrónico`, `Teléfono de contacto`, `Nombre del proyecto` y `Descripción del proyecto`. El job existente selecciona el writer por allowlist, guarda `flower-flow-contactos-enviados-YYYYMMDD-HHMMSS.xlsx` y usa los conteos existentes.

## Plan of Work

1. Demostrar guard exacto de `flowerflow_testing` y baseline dirigido.
2. Añadir enum/modelo, writer y selección allowlistada en el job.
3. Añadir controlador/rutas, confirmación, botón y tipo visible en historial.
4. Cubrir permisos, alcance global, snapshot, perfil ausente, texto hostil, fallo cerrado y regresión completa.
5. Actualizar ADR, seguridad, QA, trazabilidad y handoff.
6. Ejecutar gates, inspección estructural/visual del XLSX y UAT local posible.

## Validation

Sólo después de comprobar `APP_ENV=testing`, MySQL local, `flowerflow_testing`, `flowerflow_testing_user` y `SELECT DATABASE()=flowerflow_testing`:

    APP_ENV=testing php artisan test --compact tests/Feature/SubmissionExportTest.php
    APP_ENV=testing php artisan test --compact
    vendor/bin/pint --test
    composer validate --strict --no-check-publish
    composer check-platform-reqs --no-dev
    composer audit --locked
    corepack yarn audit --groups dependencies --level moderate
    scripts/build_frontend_production.sh
    php artisan route:list --except-vendor
    php artisan schedule:list
    php artisan migrate:status --env=testing
    git diff --check

El XLSX sintético se abrirá con OpenSpout y OpenPyXL; si LibreOffice/Poppler están disponibles se renderizará para revisar ancho, wrapping, encabezado, filtro y fila congelada. La UAT local cubrirá 1440×900, 1024×768 y 390×844 con teclado, foco, reflow y descarga privada.

## Deployment and Rollback

No hay despliegue. El worker actual que escucha `exports` procesa ambos tipos; no se añade otro proceso. El rollback de código retira botón/rutas/writer/enum y devuelve el job al writer completo. No hay migración que revertir; los XLSX ya generados expiran mediante la purga existente y los registros históricos sin `kind` siguen resolviendo como `full` mientras el cambio esté activo.

## Progress

- [x] 2026-08-24 MST — Reglas, plan previo y ADR 0007 leídos completamente; baseline Git exacto y limpio.
- [x] 2026-08-24 MST — Guard exacto demostrado sin secretos: `testing`, MySQL `127.0.0.1`, `flowerflow_testing`, usuario dedicado y `SELECT DATABASE()` coincidente.
- [x] 2026-08-24 MST — Baseline dirigido verde: `SubmissionExportTest`, 7 pruebas y 80 aserciones.
- [x] 2026-08-24 MST — Tipo `full|submitted_contacts`, writer de contactos, selección allowlistada, rutas, confirmación, botón, historial tipado, auditoría y documentación implementados.
- [x] 2026-08-24 MST — Suite dirigida final verde: 11 pruebas y 141 aserciones; incluye snapshot, perfil ausente, fórmula hostil, permisos y fallos cerrados.
- [!] 2026-08-24 MST — La suite completa fue interrumpida a petición expresa del propietario para terminar de inmediato; no había reportado fallos antes de detenerse, pero no cuenta como gate ejecutado. Composer/Yarn/build, inspección OpenPyXL/render y UAT Firefox quedan pendientes.

## Decision Log

- Decision: reutilizar `submission_exports` y el worker `exports`, discriminando mediante `filters.kind`.
  Rationale: mantiene el contrato de seguridad y operación probado sin migración ni infraestructura adicional.
  Date/Author: 2026-08-24 / propietario y Codex.

- Decision: usar sólo snapshot en propuestas enviadas y fallar si la estructura autoritativa es inválida.
  Rationale: evita mezclar evidencia inmutable con datos vivos o producir un archivo silenciosamente inconsistente.
  Date/Author: 2026-08-24 / propietario y Codex.

## Outcomes & Retrospective

La funcionalidad quedó implementada y su suite dirigida está verde. No se declara `GO LOCAL/TEST` completo porque el propietario solicitó terminar antes de concluir la suite global y los demás gates. No hubo migraciones, stage, commit, push, despliegue ni acceso a producción.
