# Informe — Fase 02B M8A: wizard de evaluación y exportaciones del proyecto

**Fecha:** 2026-08-25 · **Entorno:** local/testing sintético · **Producción:** no autorizada

## Resultado observable

El área del juez separa el flujo en cuatro pasos: inicio, proyecto asignado, evaluación y revisar/enviar. Iniciar conserva el POST explícito de M6 y redirige al proyecto; continuar abre la captura. El juez puede alternar entre proyecto y evaluación, guardar manualmente sin JavaScript y autoguardar cada treinta segundos con el mismo lock optimista y cálculo decimal server-side.

La pantalla del proyecto ofrece PDF y XLSX profesionales generados exclusivamente desde el paquete ciego inmutable. Ninguna exportación consulta el snapshot crudo, identidad, contacto, admisibilidad, residencia, notas o nombres originales.

## Contratos implementados

- Rutas GET nuevas `.../proyecto`, `.../evaluacion`, `.../proyecto.pdf` y `.../proyecto.xlsx`; cuatro pasos en el stepper y confirmación M7 conservada.
- GET de detalle/proyecto/evaluación/exportación sin creación de `Evaluation`; sólo el POST explícito abre el agregado.
- Proyecto y exportaciones disponibles únicamente tras iniciar, para el juez propietario, assignment activo, sin conflicto y con paquete/version/hash/schema vigentes.
- Acceso superior a conflicto sólo antes de iniciar; el formulario autorizado queda al final del paso 1 mientras M7 lo permita.
- `intent=autosave` en el PATCH existente, con JSON de lock, hora, progreso, completitud y total presentado por el servidor.
- Autosave sólo cuando hay cambios; navegación al proyecto guarda primero. 409 bloquea, no sobrescribe y conserva datos locales; 422/offline conservan el formulario.
- PDF A4 con ambos logotipos, secciones, footer y enlaces autenticados; remoto, PHP y JavaScript deshabilitados.
- XLSX con hojas exactas `Proyecto`, `Enlaces` y `Anexos`, ambos logotipos, filtros, panel inmovilizado, impresión A4 y valores no confiables como texto literal.
- Temporales privados con borrado post-respuesta, `Cache-Control: private, no-store`, `nosniff` y seis exportaciones/minuto por juez/asignación/formato.
- Auditoría redactada `blind_review_package.project_exported|project_export_rejected` sin contenido, PII, URLs o nombres de archivo.

## Dependencias

- `barryvdh/laravel-dompdf` 3.1.2 (`^3.1`, MIT) y `dompdf/dompdf` 3.1.6 (LGPL-2.1-only).
- `phpoffice/phpspreadsheet` 5.9.0 (`^5.9`, MIT).
- OpenSpout 4.32 permanece como writer de streaming de las exportaciones masivas; PhpSpreadsheet se limita a este libro pequeño con imágenes.

No se añadió migración, permiso, tabla, job, queue o worker.

## Evidencia automatizada

- Guard exacto: `APP_ENV=testing`, MySQL `127.0.0.1`, base `flowerflow_testing`, usuario `flowerflow_testing_user` y `SELECT DATABASE()` coincidente.
- Baseline: rama `codex/submission-deadline-extension`, HEAD/upstream/merge-base `4aff0b0cd6e65a2101463657165e11e30379be00`, árbol limpio y 24 migraciones preexistentes.
- Baseline dirigido previo: 26 pruebas/495 aserciones.
- Regresión final M8A+M5+M6+M7+asignaciones/RBAC: 37 pruebas/812 aserciones.
- Suite completa final: 233 pruebas aprobadas, 1 omitida y 2,849 aserciones en 1,164.51 segundos. La omisión corresponde exclusivamente al workload opt-in de archivos máximos de la asignación masiva, no a M8A.
- Pint, Composer validate/platform/audit y build Vite: verdes. `yarn audit` conserva únicamente el advisory bajo conocido de Quill 2.0.3 sin parche.
- Inventario final: 112 rutas propias, cuatro tareas programadas y las 24 migraciones preexistentes aplicadas; M8A no añadió migración.
- Once JSON válidos, 15 destinos Markdown locales válidos, `git diff --check` verde y scan de 40 archivos del cambio sin secretos de alta confianza ni correo first-party no sintético. Los correos públicos de autores de paquetes presentes en `composer.lock` se identificaron como metadata de terceros, no datos de Flower Flow.
- Tras el UAT se volvió a demostrar el guard exacto y se ejecutó `migrate:fresh --seed` únicamente en `flowerflow_testing`. Estado de entrega: cero usuarios, perfiles de juez, evaluaciones, sesiones y jobs sintéticos; 24 migraciones aplicadas.

## Evidencia de artefactos

- PDF: una página A4, PDF 1.7, ambos logotipos visibles, fuente UTF-8, estructura y footer correctos; `pdfinfo` confirmó `JavaScript: no` y Poppler no mostró clipping.
- XLSX: tres hojas exactas, dos dibujos por hoja, freeze `A6`, filtros `A5:B5`, `A5:C5`, `A5:E5`, impresión landscape/A4/fit-to-width y cero celdas fórmula. PhpSpreadsheet/OpenPyXL y previsualización local confirmaron estructura y reflow.
- LibreOffice/Excel no están instalados en el runtime local; un smoke en visor real sigue recomendado antes de release.

## UAT Firefox

Con usuario, propuesta, paquete, assignment y correo `example.test` sintéticos:

- Inicio → proyecto → evaluación → confirmación → envío de sólo lectura.
- 1440×900, 1024×768 y 390×844, sin scroll horizontal ni errores de consola.
- Autosave real a treinta segundos, total `77.50` devuelto por servidor y habilitación del paso 4 tras completar cuatro criterios/comentario válido.
- Guardado inmediato antes de volver al proyecto, verificado en base con incremento de lock.
- Error 422 por paso decimal inválido con resumen/foco/campo; offline conserva el formulario y guarda al recuperar conexión.
- Dos pestañas: la primera guardó; la segunda recibió 409, conservó su texto local y no sobrescribió. Base confirmó lock vigente y campo stale nulo.
- El UAT descubrió una colisión móvil con `.ff-step-number` de la landing. Se corrigió de forma acotada y la segunda captura confirmó el stepper sin superposición.

El zoom nativo no fue observable como cambio de viewport en Firefox headless; el reflow equivalente se verificó a 390 CSS px. Repetir zoom manual 200–400 % en un Firefox interactivo antes de release.

## Archivos principales

Inventario exacto del cambio M8A:

- Dominio/HTTP: `app/Actions/Evaluations/SaveEvaluationDraft.php`, `app/Http/Requests/SaveEvaluationDraftRequest.php`, `app/Http/Controllers/Judge/AssignmentController.php`, `EvaluationController.php`, `EvaluationDraftController.php`, `EvaluationSubmissionController.php`, `ProjectController.php`, `ProjectExportController.php`, `app/Services/BlindReviewProjectResolver.php`, `JudgeEvaluationWorkspace.php`, `JudgeProjectPdfExporter.php`, `JudgeProjectWorkbookWriter.php`, `app/Providers/AppServiceProvider.php` y `routes/web.php`.
- Interfaz: `resources/views/judge/assignments/index.blade.php`, `show.blade.php`, `_wizard-stepper.blade.php`, `project.blade.php`, `evaluation.blade.php`, `resources/views/judge/evaluations/confirm.blade.php`, `resources/views/judge/exports/project-pdf.blade.php`, `resources/css/app.css`, `resources/css/pages/judge-evaluation-wizard.css` y `resources/js/app.js`.
- Dependencias/configuración: `composer.json`, `composer.lock` y `config/dompdf.php`.
- Pruebas: `tests/Feature/JudgeEvaluationWizardTest.php`, `BlindReviewPackageTest.php`, `EvaluationDraftTest.php` y `EvaluationSubmissionReopeningTest.php`.
- Evidencia/documentación: `.agent/execplans/flowerflow-phase-02b-m8a-judge-evaluation-wizard-exports.md`, `docs/adr/0014-m8a-judge-evaluation-wizard-and-project-exports.md`, este informe, `docs/02-architecture.md`, `04-security-privacy.md`, `05-ux-ui.md`, `08-testing-qa.md`, `11-operations-handoff.md`, `CODEX_PROJECT_HANDOFF.md`, `dependency-register.md` y `requirements-traceability.md`.

Los archivos no rastreados `database/flowerflow.sql` y `docs/confirmacion_email.png` aparecieron después del baseline limpio, no pertenecen a M8A y se preservaron sin modificación.

## Rollback y riesgos

Retirar las rutas/vistas/servicios/dependencias M8A restaura la interfaz anterior. No borrar evaluaciones, scores, revisiones, paquetes o auditoría: todos siguen bajo contratos M6/M7. Los textos, enlaces y anexos pueden revelar identidad semántica aunque la estructura esté ciega; la UI y documentos lo advierten.

Resultado final: `GO LOCAL/TEST`. Se conserva:

`NO-GO RELEASE/PRODUCTION — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`

No hubo stage, commit, push, despliegue, producción, SMTP real o datos reales.
