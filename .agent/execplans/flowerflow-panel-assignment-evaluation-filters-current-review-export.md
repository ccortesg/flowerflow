# Milestone — filtros de asignaciones/evaluaciones y exportación de revisiones vigentes

Este ExecPlan es un documento vivo. Se mantiene conforme a `.agent/PLANS.md` y limita la ejecución a local/test con datos sintéticos.

## Propósito y resultado observable

El panel administrativo permitirá localizar asignaciones y evaluaciones por folio o ID público de propuesta y combinarlas con categoría; Evaluaciones añadirá además estado. Un administrador autorizado podrá solicitar un XLSX de la revisión vigente de cada relación propuesta–juez, con una fila por evaluación/juez y el detalle persistido de cada rubro. La exportación histórica de todas las revisiones seguirá disponible sin cambiar su contrato.

## Estado, alcance y decisiones aprobadas

- Autorización expresa del propietario: 2026-08-26.
- Decisión expresa: “última revisión” significa la revisión vigente señalada por `evaluations.current_revision_id` para cada evaluación propuesta–juez; el resultado produce una fila por juez.
- Incluye filtros GET, columnas de referencia visibles, selección de alcance de exportación, writer XLSX, pruebas y documentación.
- Excluye cambios de esquema, rutas, permisos, flags, dependencias, cálculo, consolidación, ranking, resultados, PDF jurídico, producción y servicios externos.
- La nueva exportación es global y no hereda los filtros visibles del listado. La UI debe declararlo.
- El alcance histórico `all_revisions_v1` permanece compatible. El nuevo alcance es `current_revisions_v1`.
- Continúa `NO-GO RELEASE/PRODUCTION — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`.

## Contexto e invariantes

- Repositorio canónico: `/home/ccortesg/workspace/flowerflow` dentro de WSL.
- Rama al iniciar: `codex/submission-deadline-extension`; árbol limpio.
- ADR aplicables: 0010, 0011, 0014 y 0015; este milestone añadirá ADR-0016.
- La búsqueda reutiliza `SubmissionReferenceFilter`, que recorta y escapa `%`, `_` y `\` antes de consultar `submissions.folio OR submissions.public_id`.
- La elegibilidad de Asignaciones permanece limitada a propuesta enviada cuya última versión está admitida.
- `current_revision_id` es la autoridad de vigencia. El export falla cerrado si no pertenece a la evaluación o no coincide con el mayor `revision_number`; no infiere vigencia mediante `MAX()`.
- Totales, componentes, puntajes y comentarios salen de la evidencia persistida; no se recalculan.
- Todo contenido no confiable se escribe como texto literal. No se incorporan correo, teléfono, domicilio, residencia, archivos, rutas, tokens, hashes ni motivo cifrado de reapertura.
- La identidad del juez es el valor actual al exportar; la interfaz y la documentación lo declaran.

## Modelo y contratos

- `EvaluationExportScope`: `current_revisions_v1` y `all_revisions_v1`, con etiquetas en español.
- `scope_version` existente guarda el alcance; no se requiere migración.
- Un POST sin `scope_version` conserva compatibilidad y genera `all_revisions_v1`; la UI nueva envía siempre el valor explícito.
- `current_revisions_v1` genera:
  - `Evaluaciones vigentes`: primero las columnas observables del módulo (`Evaluación`, `Propuesta`, `Juez sujeto`, `Categoría`, `Estado`, `Revisión`, `Actualización`), seguidas por estado de revisión, total interno/presentado y comentario general.
  - `Rubros vigentes`: una fila por score de la revisión vigente con código, etiqueta, peso, puntaje, componente y comentario.
- `all_revisions_v1` conserva hojas, columnas, nombres de archivo y conteos actuales.
- Sólo rol exacto `admin` con `view evaluations`, `view submissions`, `export evaluations`, flag y contraseña reciente puede solicitar/descargar; ownership se revalida.

## Plan por pasos

1. Crear el enum de alcance y request validado; adaptar controller/model/job manteniendo compatibilidad histórica.
2. Extender el writer con un camino independiente para revisiones vigentes y validaciones fail-closed.
3. Añadir filtros GET y columnas de propuesta en Asignaciones y Evaluaciones.
4. Actualizar la pantalla de confirmación y el historial reciente con el tipo de exportación.
5. Añadir pruebas de filtros, RBAC/validación de alcance y contenido XLSX vigente, incluyendo reapertura, una fila por juez, v1/v2, cero, `NULL` y fórmula hostil literal.
6. Actualizar ADR, trazabilidad, especificación, operación, handoff e informe del milestone.
7. Ejecutar pruebas dirigidas, regresión, suite completa y gates del proyecto; inspeccionar el XLSX con OpenSpout, PhpSpreadsheet y XML ZIP.

## Validación y evidencia esperada

- Guard: `php artisan test --filter=DisposableDatabaseGuardTest`.
- Dirigidas: filtros nuevos y `EvaluationExportTest`.
- Regresión: asignaciones/conflictos, reapertura, contrato de panel y exportación de propuestas.
- Suite completa: `php artisan test`.
- Calidad: Pint del alcance y global, Composer validate/platform/audit, build Vite, rutas, scheduler, migraciones, JSON, enlaces Markdown y búsquedas de secretos/PII.
- Git: `git status --short`, `git diff --check`, `git diff --stat`, `git diff`.
- UAT de navegador ejecutado con Chromium administrado por Playwright dentro de WSL, sobre `flowerflow_testing` y datos sintéticos eliminados al finalizar.

## Despliegue y rollback

No se autoriza despliegue. El rollback operativo de la exportación continúa siendo `FLOWERFLOW_EVALUATION_EXPORT_ENABLED=false`. El rollback de código es selectivo: retirar el alcance `current_revisions_v1` y los filtros preservando el contrato histórico y toda evidencia en `evaluation_exports`; no hay migración ni datos que revertir.

## Registro vivo

- [x] 2026-08-26 11:29 MST — Preflight WSL, lecturas obligatorias y baseline dirigido completados; evidencia: guard 8/8 y 16 pruebas/834 aserciones verdes.
- [x] 2026-08-26 12:45 MST — Contrato, filtros, exportación, pruebas y documentación implementados; el alcance PHP pasó Pint y las suites dirigidas, de regresión y completa quedaron verdes.
- [x] 2026-08-26 12:45 MST — UAT local completado en 390, 1024 y 1440 px: filtros GET, limpieza, reconfirmación de contraseña, selector de alcance, teclado y consola sin errores. Se corrigió el desbordamiento móvil de la tarjeta de exportaciones recientes y se restauró `flowerflow_testing` al seed canónico.
- [x] 2026-08-26 12:45 MST — Gates registrados: Composer/build/rutas/scheduler/migraciones/JSON/enlaces/secret scan verdes; el gate global de Pint continúa rojo únicamente por `video-tutorial/scripts/freeze-time.php`, archivo preexistente y fuera de alcance, y `yarn audit` mantiene el aviso LOW conocido de Quill sin parche.
