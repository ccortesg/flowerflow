# ADR-0016 — Filtros administrativos y exportación de revisiones vigentes

- **Estado:** aceptado para local/test
- **Fecha:** 2026-08-26
- **Complementa:** ADR-0010, ADR-0011 y ADR-0015
- **Bloqueo de release:** `OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`

## Contexto

Los listados de Asignaciones y Evaluaciones no permitían localizar una propuesta con el patrón ya disponible en Propuestas y Admisibilidad. La exportación administrativa de ADR-0015 entregaba toda la historia append-only, útil como evidencia, pero no una vista operativa directa de la revisión que hoy gobierna cada evaluación propuesta–juez.

El propietario confirmó que “última revisión” significa la revisión vigente referenciada por `evaluations.current_revision_id` y que debe producirse una fila por juez. Esa definición no autoriza consolidar, promediar, ordenar, seleccionar ganadores ni sustituir la exportación histórica.

## Decisión

1. Asignaciones y Evaluaciones reutilizan `SubmissionReferenceFilter` con el parámetro GET `folio`, buscando folio o ULID público parcial con `%`, `_` y `\` literales. Ambos combinan categoría; Evaluaciones añade el enum de estado. Los GET son sólo lectura y la paginación conserva parámetros.
2. Las tablas muestran una columna Propuesta con folio e ID público para que el resultado encontrado sea reconocible. No exponen participante, contacto, residencia o contenido.
3. `evaluation_exports.scope_version` admite el nuevo `current_revisions_v1` además de `all_revisions_v1`; no hay migración. Un POST legado sin alcance conserva `all_revisions_v1`. La interfaz envía siempre una selección explícita y recomienda revisiones vigentes.
4. `current_revisions_v1` genera `Evaluaciones vigentes` y `Rubros vigentes`. La primera conserva las columnas de datos del listado y añade estado de revisión, total persistido 4/2 y comentario general. La segunda desglosa cada score persistido con rubro, peso, puntaje, componente y comentario.
5. Cada evaluación equivale a una relación propuesta–juez y produce exactamente una fila principal. `current_revision_id` es la autoridad; el writer valida que pertenezca a la evaluación y coincida con el mayor número append-only. Una inconsistencia aborta todo el archivo en vez de inferir o mezclar revisiones.
6. No se recalculan puntajes, componentes o totales. `NULL` queda vacío, cero se conserva y toda celda de entrada es texto literal. La propuesta/categoría proceden del snapshot enviado y el juez se etiqueta como valor actual al exportar.
7. El alcance vigente excluye identidad/contacto del participante, proyecto, reaperturas y motivos. El histórico de ADR-0015 conserva exactamente sus tres hojas y contenido; no se reescribe evidencia previa.
8. RBAC, flag, contraseña reciente, job cifrado/post-commit, ownership, disk privado, vigencia, purga y auditoría existentes se aplican a ambos alcances.

## Consecuencias

- La vista operativa puede consumirse sin deduplicar revisiones históricas y soporta rúbricas v1/v2 dinámicamente.
- Los conteos de `evaluation_exports` mantienen significado: en alcance vigente `revision_count === evaluation_count` y `reopening_count === 0`.
- El filtro visible no limita el XLSX; la confirmación declara que ambos alcances son globales.
- No se añade tabla, columna, ruta, permiso, dependencia, flag, worker o proceso.
- El rollback de operación sigue siendo apagar `FLOWERFLOW_EVALUATION_EXPORT_ENABLED`; no se eliminan filas ni archivos vigentes.

## Alternativas descartadas

- Reemplazar silenciosamente `all_revisions_v1`: rompería el contrato probatorio y clientes existentes.
- Resolver “última” con `MAX(revision_number)` ignorando `current_revision_id`: ocultaría drift del agregado.
- Una fila por propuesta consolidando jueces: inventaría una regla de agregación fuera de alcance.
- Exportar sólo lo filtrado/paginado: acoplaría una tarea asíncrona global a estado efímero del navegador y exigiría persistir otro contrato de alcance.
- Recalcular totales: podría reinterpretar evidencia histórica con lógica actual.

## Verificación

La matriz y resultados ejecutados se registran en `.agent/execplans/flowerflow-panel-assignment-evaluation-filters-current-review-export.md`, `docs/requirements-traceability.md` y `docs/33-panel-assignment-evaluation-filters-current-review-export-report-2026-08-26.md`. La autorización se limita a local/test; producción no fue modificada.
