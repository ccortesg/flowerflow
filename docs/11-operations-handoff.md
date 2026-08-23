# Handoff operativo vigente — Flower Flow

> **Bitácora de comunicaciones — 2026-08-23, `GO LOCAL/TEST`:** se añadió un outbox común para las nueve familias existentes, panel `Notificaciones`, recuperación individual, backfill de recordatorios y comandos de diagnóstico/reconciliación/purga. Suite final 191/2,255, 21 migraciones, 84 rutas, build y UAT Firefox verdes. El flag default-off `FLOWERFLOW_COMMUNICATION_LEDGER_ENABLED=false` es el rollback funcional. El worker existente `--queue=high,exports,default,low` cubre recuperación y correo normal; no se requiere otro proceso. La activación productiva, migración, scheduler, cache, reinicio y smoke SMTP requieren autorización separada. `sent` acredita sólo aceptación del transporte. Ver `docs/26-communication-delivery-ledger-implementation-report-2026-08-23.md`.

> **Milestone de acciones del panel — 2026-08-22, `GO LOCAL/TEST`:** partió de `bffc7d7f4738e0937b276ea9d5d22e3744afe65c` en `codex/submission-deadline-extension`. Añade recordatorios y finalización administrativa bajo flags default-off, más diagnóstico de exportaciones. Suite final 179/2,130, build y UAT Firefox verdes; 20 migraciones y 80 rutas propias. No hubo stage, commit, push, despliegue, SMTP real ni acceso productivo. El rollback primario es `FLOWERFLOW_SUBMISSION_REMINDERS_ENABLED=false` y `FLOWERFLOW_ADMIN_FINALIZATION_ENABLED=false`; una migración `down` no debe forzarse si existe evidencia. Ver `docs/25-panel-submission-actions-reminders-implementation-report-2026-08-22.md`.

Para exports, `flowerflow:exports-diagnose --json` es de sólo lectura y no muestra secretos ni payloads. Una espera prolongada exige comprobar migración, configuración efectiva `database/exports`, antigüedad de `jobs`, `failed_jobs`, disco privado y el worker exclusivo Flower Flow con `--queue=high,exports,default,low`. No borrar jobs ni crear exportaciones duplicadas. Reinicios/cache/worker productivos requieren autorización separada.

> **Estado vigente M6 — 2026-08-18:** M1–M6 están `GO LOCAL/TEST`; M6 añade borrador propio, cálculo BCMath y lock optimista sin envío final. No aplicar migraciones/seeders ni corregir producción por inferencia.

**Fecha:** 2026-08-18 (`America/Hermosillo`)

**Alcance:** estado documental y siguiente puerta; no es evidencia productiva independiente ni un runbook de despliegue.

## Estado recibido del propietario

El propietario confirma que los cambios aprobados hasta el momento fueron instalados en producción, que la plataforma continúa publicada en `https://app.flowerflow.com.mx/` y que contiene más de 50 propuestas reales de distintas categorías.

Estado registrado: `OWNER_CONFIRMED_DEPLOYED`.

Esta confirmación no acredita por sí misma SHA, migraciones, flags, workers, scheduler, SMTP, monitoreo, integridad de datos, smoke o UAT productiva. Como Codex no accedió a producción en esta tarea:

`PRODUCTION_RELEASE_SHA=POR_CONFIRMAR`

El baseline local verificado al iniciar M6 es la rama `codex/submission-deadline-extension`, con `HEAD`, remoto y ancestro común en `e4e4cd2ff7144cce5f9385f5f11c122cda80e7b8`. El diff M6 permanece local; no se afirma que producción ejecute ese SHA ni el diff actual. `865059a…` queda únicamente como baseline histórico de milestones previos.

## Handoff M6 local

M6 queda `GO LOCAL/TEST`: 19 migraciones, suite completa 163/1,937, M4A `4+2` ilimitado, paquete ciego M5 y borrador/concurrencia/cálculo servidor probados. `FLOWERFLOW_EVALUATION_ENABLED=false` es el rollback operativo y conserva evidencia. No ejecutar seeders productivos ni borrar asignaciones, conflictos, paquetes, inventarios o borradores.

## Estado funcional transferido

| Área | Estado local documentado | Estado productivo en este handoff |
|---|---|---|
| Fase 01 / 02A, cuarta categoría, plazo, legales v1.1, XLSX y 503/CSP | Implementado y validado localmente según diagnóstico/ExecPlans | Instalación confirmada sólo por el propietario. |
| Jueces, asignaciones, conflictos, rúbrica y evaluación | M1–M6 conformes local/test; paquete ciego y borrador/cálculo sí; envío M7 no | Nada de M1–M6 atribuido a producción. |
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
- `.agent/execplans/flowerflow-phase-02b-m6-draft-evaluation-server-scoring.md`;
- `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`;
- `docs/19-phase-02b-m2-implementation-report-2026-08-18.md`;
- `docs/20-phase-02b-m3-implementation-report-2026-08-18.md`;
- `docs/21-phase-02b-m4-implementation-report-2026-08-18.md`;
- `docs/22-phase-02b-m4a-unlimited-judges-implementation-report-2026-08-18.md`;
- `docs/23-phase-02b-m5-blind-package-implementation-report-2026-08-18.md`;
- `docs/24-phase-02b-m6-draft-evaluation-implementation-report-2026-08-18.md`;
- `docs/adr/0008-phase-02b-evaluation-contract.md`.

La siguiente puerta potencial es diseñar y autorizar exclusivamente M7. El estado es:

`M1–M6 CONFORMANT LOCAL/TEST — DRAFT/SERVER SCORE ACTIVE — M7 NOT AUTHORIZED`

M1 evita el acceso por descarte; M2 añade cuenta; M3 rúbrica; M4/M4A asignación/conflicto `4+2`; M5 proyección ciega/anexos; M6 borrador y cálculo. M7–M10 requieren autorización separada.

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
