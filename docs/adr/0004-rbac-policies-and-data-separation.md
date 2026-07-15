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
