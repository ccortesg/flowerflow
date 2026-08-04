# ADR-0004: RBAC, Policies y separación de elegibilidad/evaluación

- **Estado:** Proposed
- **Fecha:** 2026-07-15

## Contexto

El sistema procesa identidad y comprobantes sensibles, mientras los jueces requieren propuestas ciegas. Un rol global o botones ocultos no evitan IDOR, exports excesivos ni descargas cruzadas.

## Decisión

Usar permisos granulares para capacidad general y Policies para ownership, asignación, estado, calendario, conflicto y visibilidad. Separar metadatos/storage de residencia de anexos evaluables. Todas las consultas, serializaciones, descargas y exportaciones aplican la misma frontera.

## Consecuencias

- Cada recurso exige pruebas positivas y negativas por actor.
- Las pantallas de juez usan proyecciones ciegas y nunca cargan PII/comprobantes.
- Los reportes/exportaciones se generan con allowlist por permiso.
- Se propone un paquete RBAC, pero su elección e instalación quedan PENDING; las Policies no dependen del paquete.

## Criterio para aceptar

Aprobar matriz RBAC, evaluación ciega, responsables de elegibilidad y quién puede declarar/publicar ganadores.

## Adenda de análisis — Fase 02B, 2026-08-04

La matriz de `docs/15-phase-02b-judge-evaluation-definition.md` confirma la dirección del ADR, pero no permite cambiar su estado a `Accepted`.

Determinaciones técnicas:

- crear una proyección allowlist de snapshot/anexos evaluables; no conceder al juez la Policy administrativa de `Submission`;
- negar en todas las capas identidad, residencia, aclaraciones, notas y auditoría sensible;
- separar permisos de evaluación, conflicto, reapertura, consolidación, declaración y publicación;
- cerrar acceso de inmediato al declarar conflicto;
- probar juez asignado/no asignado/conflictuado y canarios de PII en payloads, archivos, correo y logs.

Persisten como criterios de aceptación pendientes: protocolo de anonimización de contenido/metadatos, proceso y autoridad de conflicto/recusación, autoridad/quórum de resultados y visibilidad al participante. Por ello el ADR continúa `Proposed`.
