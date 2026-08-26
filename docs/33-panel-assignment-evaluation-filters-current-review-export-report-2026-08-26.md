# Informe de implementación — filtros y exportación de revisiones vigentes

**Fecha:** 2026-08-26 (`America/Hermosillo`)  
**Ámbito:** local/test; sin stage, commit, push, despliegue, producción o servicios externos.  
**Estado:** validación final en curso.

## Resultado funcional

- `/panel/asignaciones` filtra por folio/ID público parcial y categoría sin cambiar el conjunto base enviado+admitido. Muestra folio e ID público y conserva filtros al paginar.
- `/panel/evaluaciones` filtra por folio/ID público, estado enum y categoría. Muestra la propuesta junto a cada evaluación y conserva filtros al paginar.
- El administrador autorizado elige `Revisiones vigentes` o `Historial completo`. El alcance legado conserva `all_revisions_v1`; la vista nueva usa `current_revisions_v1`.
- `current_revisions_v1` produce `Evaluaciones vigentes` y `Rubros vigentes`: una fila principal por evaluación/juez, revisión señalada por `current_revision_id`, estado, total 4/2, comentario general y detalle persistido por rubro.
- El writer falla cerrado si el puntero vigente no pertenece al agregado o no coincide con la última revisión append-only. No calcula consolidado, promedio, ranking o ganador.

## Seguridad y privacidad

Se mantienen flag default-off, rol exacto admin, permisos acumulativos, contraseña reciente, ownership, job cifrado/post-commit, cola `exports`, disk privado, expiración y auditoría redactada. El libro vigente no incluye identidad/contacto del participante, nombre del proyecto, archivos, reaperturas ni motivos. Todas las celdas son texto literal; `NULL` queda vacío y cero se conserva.

## Compatibilidad y datos

No hay migración, rutas, permisos, dependencias o configuración nuevos. Las solicitudes antiguas sin `scope_version` siguen generando el historial completo. No se modifica ninguna evaluación, revisión, score, asignación, propuesta, snapshot o exportación previa.

## Evidencia ejecutada

- Preflight WSL: repositorio/rama esperados, árbol limpio, `core.autocrlf` sin configurar y toolchain Linux disponible, incluido `rg 13.0.0`.
- Guard de base: 8 pruebas/8 aserciones verdes.
- Baseline dirigido previo: 16 pruebas/834 aserciones verdes.
- Pruebas nuevas/dirigidas iniciales: 6 pruebas/1,224 aserciones verdes, incluida lectura independiente con PhpSpreadsheet y XML ZIP sin fórmulas.
- Suite completa, gates y UAT: `PENDING` hasta cerrar el ExecPlan.

## Rollback y riesgos residuales

Rollback operativo: `FLOWERFLOW_EVALUATION_EXPORT_ENABLED=false`. El rollback de código puede retirar filtros y `current_revisions_v1` sin borrar evidencia ni tocar `all_revisions_v1`. Persisten el bloqueo jurídico global de cobertura mínima, la identidad del juez como dato actual al exportar y la necesidad de validar worker/disk/capacidad/UAT en un release autorizado.
