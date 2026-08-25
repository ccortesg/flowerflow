# ADR-0013 — Orquestación administrativa de asignación simultánea

- **Estado:** Accepted para local/test; release/producción bloqueados.
- **Fecha:** 2026-08-25
- **Complementa:** ADR-0009 (outbox) y ADR-0010 (asignación manual sin mínimos/límites).

## Contexto

El flujo M6A permite seleccionar varios jueces para una sola propuesta, pero preparar un conjunto de propuestas para un mismo juez exige recorrer por separado admisibilidad, paquete ciego y asignación. Esas tres fronteras ya tienen reglas transaccionales, auditoría e invariantes propias. Una “asignación rápida” que cambiara sólo `judge_assignments` dejaría propuestas no admitidas o paquetes inexistentes y debilitaría la ceguera estructural.

## Decisión

1. Añadir un asistente administrativo síncrono, apagado por defecto, que selecciona un juez y entre una y veinte propuestas de una página explícita.
2. Exigir rol exacto `admin`, los tres permisos de mutación existentes, contraseña reciente, contraseña actual, CSRF, throttle y tres confirmaciones.
3. Separar preflight y ejecución mediante una intención cifrada/autenticada de quince minutos, ligada a actor, juez, propuestas, textos compartidos, opción de correo y estado técnico observado.
4. Procesar cada propuesta en una transacción independiente: admitir `pending|in_review`, conservar `admitted`, generar/validar/activar paquete y crear u omitir idempotentemente la asignación.
5. Rechazar individualmente expediente/versión faltante, resolución `not_admitted`, aclaración abierta, residencia no verificada/cancelada, paquete invalidado/divergente o deriva posterior al preflight.
6. Reutilizar las Actions canónicas. `AssignJudgesToSubmission` distingue notificación `individual|bulk|none`; el modo bulk difiere el correo sin registrar una omisión engañosa.
7. Cuando se solicita, crear un solo delivery `judge.assignment_bulk_created` por operación y juez. El contexto cifrado contiene únicamente IDs de asignación; el mensaje muestra cantidad, categorías, plazo y CTA. El worker omite asignaciones que dejaron de ser válidas y cancela si ninguna permanece.
8. No añadir tabla batch. Un `operation_id`, lock de cache y auditoría terminal evitan doble ejecución; la evidencia duradera permanece en expedientes, eventos, paquetes, asignaciones, deliveries y auditoría.

## Consecuencias

- Una falla de una propuesta no revierte éxitos previos o posteriores; dentro de esa propuesta no puede persistir una fase parcial.
- Los expedientes ya admitidos no reciben un motivo nuevo ni cambian actor/fechas/notas.
- El correo de admisibilidad al participante sigue siendo individual y post-commit. El juez recibe como máximo un resumen consolidado y nunca contenido o PII.
- No se crean expedientes faltantes mediante GET, preflight o ejecución; `flowerflow:admissibility-backfill` sigue siendo una precondición operativa explícita.
- El worker existente `high,exports,default,low` es suficiente y no se añade migración, dependencia o proceso.
- Rollback funcional: `FLOWERFLOW_BULK_JUDGE_ASSIGNMENT_ENABLED=false`, conservando toda evidencia.

## Riesgo jurídico

El asistente no impone cobertura mínima. Permanece `NO-GO RELEASE/PRODUCTION — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED` por la contradicción entre “al menos tres jueces” de la Mecánica v1.1 y ADR-0010.

## Verificación

La evidencia ejecutada y los pendientes viven en `.agent/execplans/flowerflow-bulk-judge-assignment.md` y `docs/30-bulk-judge-assignment-report-2026-08-25.md`.
