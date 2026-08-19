# Handoff operativo vigente — Flower Flow

> **Estado vigente M5 — 2026-08-18:** M1–M5 están `GO LOCAL/TEST`; seis jueces ilimitados, asignación manual y paquete ciego único/allowlist con anexos neutros. No aplicar migraciones/seeders ni corregir producción por inferencia.

**Fecha:** 2026-08-18 (`America/Hermosillo`)

**Alcance:** estado documental y siguiente puerta; no es evidencia productiva independiente ni un runbook de despliegue.

## Estado recibido del propietario

El propietario confirma que los cambios aprobados hasta el momento fueron instalados en producción, que la plataforma continúa publicada en `https://app.flowerflow.com.mx/` y que contiene más de 50 propuestas reales de distintas categorías.

Estado registrado: `OWNER_CONFIRMED_DEPLOYED`.

Esta confirmación no acredita por sí misma SHA, migraciones, flags, workers, scheduler, SMTP, monitoreo, integridad de datos, smoke o UAT productiva. Como Codex no accedió a producción en esta tarea:

`PRODUCTION_RELEASE_SHA=POR_CONFIRMAR`

El baseline local verificado al iniciar M4 es la rama `codex/submission-deadline-extension`, con `HEAD`, remoto y ancestro común en `865059ad302ff4195ac18f671bd6fa13b99e398b`. Los cambios M3/M4 posteriores permanecen locales; no se afirma que producción ejecute ese SHA ni el diff actual.

## Handoff M5 local

M5 queda `GO LOCAL/TEST`: 18 migraciones; M4A `4+2` ilimitado y paquete ciego/descargas privadas probados. `FLOWERFLOW_EVALUATION_ENABLED=false` cierra el shell juez. No ejecutar seeders productivos ni borrar asignaciones, conflictos, paquetes o inventarios.

## Estado funcional transferido

| Área | Estado local documentado | Estado productivo en este handoff |
|---|---|---|
| Fase 01 / 02A, cuarta categoría, plazo, legales v1.1, XLSX y 503/CSP | Implementado y validado localmente según diagnóstico/ExecPlans | Instalación confirmada sólo por el propietario. |
| Jueces, asignaciones, conflictos, rúbrica y evaluación | M1–M5 conformes local/test; paquete ciego sí, evaluación/puntajes M6+ no | Nada de M1–M5 atribuido a producción. |
| Ganadores/resultados | 0 %; fuera de Fase 02B | No implementado; resultados deben permanecer apagados. |
| Operación externa | Runbooks y configuración documentados | Evidencia técnica independiente `POR_CONFIRMAR`. |

## Siguiente puerta

Las decisiones de Fase 02B están `OWNER_APPROVED`; la corrección final resuelve `P2B-BLOCK-001` y `P2B-M4-CORRECTION-001` localmente mediante `4+2` ilimitado. El paquete vigente incluye:

- `.agent/execplans/flowerflow-phase-02b-evaluation-design.md`;
- `.agent/execplans/flowerflow-phase-02b-m1-judge-rbac.md`;
- `.agent/execplans/flowerflow-phase-02b-m2-judge-profile-onboarding.md`;
- `.agent/execplans/flowerflow-phase-02b-m3-versioned-rubric.md`;
- `.agent/execplans/flowerflow-phase-02b-m4-assignments-conflicts.md`;
- `.agent/execplans/flowerflow-phase-02b-m4a-two-substitutes-reconciliation.md`;
- `.agent/execplans/flowerflow-phase-02b-m5-blind-package.md`;
- `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`;
- `docs/19-phase-02b-m2-implementation-report-2026-08-18.md`;
- `docs/20-phase-02b-m3-implementation-report-2026-08-18.md`;
- `docs/21-phase-02b-m4-implementation-report-2026-08-18.md`;
- `docs/22-phase-02b-m4a-unlimited-judges-implementation-report-2026-08-18.md`;
- `docs/23-phase-02b-m5-blind-package-implementation-report-2026-08-18.md`;
- `docs/adr/0008-phase-02b-evaluation-contract.md`.

La siguiente puerta es autorizar y ejecutar exclusivamente M6. El prompt M6 queda sincronizado y condicionado. El estado es:

`M1–M5 CONFORMANT LOCAL/TEST — BLIND PACKAGE ACTIVE — M6 SEPARATE`

M1 evita el acceso por descarte; M2 añade cuenta; M3 rúbrica; M4/M4A asignación/conflicto `4+2`; M5 proyección ciega y anexos. M6–M10 requieren autorización separada.

`P2B-BLOCK-001` está `OWNER RESOLVED / LOCAL VERIFIED`: cuatro principales cubren todas las elegibles y dos sustitutos exclusivos son ilimitados. `admin` selecciona manualmente uno; si el seleccionado no está operativo o ya tiene la propuesta, el flujo falla cerrado.

## Invariantes para cualquier handoff posterior

- Preservar cuentas, más de 50 propuestas reales, folios, snapshots, archivos privados, revisiones y aceptaciones.
- Migraciones 02B futuras exclusivamente aditivas; sin backfill automático de asignaciones ni cambio inferido de estados.
- Juez sólo ve asignaciones propias; puede ver campos sustantivos/anexos evaluables, pero nunca PII estructurada, residencia, notas internas, aclaraciones o historial. La autoidentificación dentro del contenido es riesgo aceptado y no se promete anonimización total.
- Total calculado exclusivamente en servidor; evaluación no equivale a ganador.
- Sin producción/AWS/SMTP real ni datos reales en tareas locales salvo autorización separada y gates aplicables.
- Un milestone de implementación por vez, después de decisiones expresas y ExecPlan aprobado.

## Evidencia que permanece pendiente fuera de esta tarea

- SHA productivo y relación con el baseline local.
- Estado real de migraciones y flags.
- Worker, scheduler, `failed_jobs`, SMTP y entregabilidad.
- Monitoreo, capacidad, logs redactados y alertas.
- Integridad/conteos y smoke/UAT productiva por rol.
- Restore medido, RPO/RTO y evidencia de licencia Pixinvent.

La ausencia de esta evidencia no revierte `OWNER_CONFIRMED_DEPLOYED`; impide convertirla en verificación técnica independiente.

## Referencias

- `docs/16-project-status-by-module-and-role-2026-08-17.md`
- `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`
- `docs/07-deployment-aws-ec2.md`
- `docs/15-risk-reduction-release-runbook.md`
- `docs/11-local-development.md`
