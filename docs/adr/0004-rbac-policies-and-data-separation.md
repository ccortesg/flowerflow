# ADR-0004: RBAC, Policies y separación de elegibilidad/evaluación

- **Estado:** Proposed
- **Fecha:** 2026-07-15

> **Adenda de implementación M1 — 2026-08-18:** ADR-0008 fija roles estrictamente excluyentes y ceguera simple estructural para Fase 02B. M1 implementó en local/test el rol `judge`, un permiso exclusivo de shell, escritor de rol único, gates exactos y cierre seguro de cuentas sin rol/multirol. No implementó perfiles, asignaciones ni proyección ciega. El juez asignado futuro podrá ver campos sustantivos y anexos evaluables, pero nunca PII estructurada de contacto, residencia, notas internas, aclaraciones o historial de admisibilidad. El propietario acepta el riesgo de identidad incrustada en contenido evaluable; la UI no debe prometer anonimización total.

## Contexto

El sistema procesa identidad y comprobantes sensibles, mientras los jueces requieren propuestas ciegas. Un rol global o botones ocultos no evitan IDOR, exports excesivos ni descargas cruzadas.

## Decisión

Usar permisos granulares para capacidad general y Policies para ownership, asignación, estado, calendario, conflicto y visibilidad. Separar metadatos/storage de residencia de anexos evaluables. Todas las consultas, serializaciones, descargas y exportaciones aplican la misma frontera.

## Consecuencias

- Cada recurso exige pruebas positivas y negativas por actor.
- Las pantallas de juez usan una proyección ciega estructural y nunca cargan PII estructurada/comprobantes. El contenido evaluable puede contener autoidentificación, riesgo aceptado y documentado en ADR-0008.
- Los reportes/exportaciones se generan con allowlist por permiso.
- Spatie Permission ya respalda RBAC. M1 añadió `EnsureExclusiveBusinessRole`, `AssignExclusiveBusinessRole` y el permiso `access judge workspace`; ningún rol obtiene el permiso exclusivo mediante `Permission::all()`.
- Las rutas participantes exigen el rol exacto `participant`, `/panel` exige `reviewer|admin`, `/juez` exige `judge` y el flag; las descargas compartidas conservan además sus Policies actuales.

## Criterio para aceptar

La matriz RBAC y el contrato de evaluación ciega de 02B quedaron aprobados en ADR-0008. M1 demostró su aislamiento base con 6 pruebas/92 aserciones dirigidas, suite completa y QA Firefox. La declaración/publicación de ganadores continúa fuera de alcance y pendiente de una decisión futura; por ello este ADR maestro conserva estado `Proposed`.
