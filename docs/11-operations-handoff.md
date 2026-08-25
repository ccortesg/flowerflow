# Handoff operativo vigente — Flower Flow

> **M7 `GO LOCAL/TEST` — validación final 2026-08-25:** añade `FLOWERFLOW_EVALUATION_FINALIZATION_ENABLED=false` y `FLOWERFLOW_EVALUATION_REOPEN_CLOSE_AT="2026-08-27 20:00:00"`; conserva cierre de evaluación `2026-08-27 23:59:59`. La migración 23 amplía estados/revisiones, hace backfill del juez sujeto y crea reaperturas. Suite final 208/2,385, 23 migraciones, 104 rutas y UAT Firefox verdes. Rollback operativo: apagar el flag; con evidencia M7 no ejecutar `down()` ni desplegar código M6 incompatible. No se requieren workers nuevos porque M7 no envía correo. Producción no está autorizada.

> **M6A `GO LOCAL/TEST` — 2026-08-24:** baseline `d3f616c86d72bfd32c1545205df057e19cf765ea`; suite final 203/2,220, 22 migraciones y 95 rutas. Añade onboarding purpose-bound, rúbrica v2 activa, asignación manual ilimitada/sin mínimos, cancelación, cadenas explícitas de reemplazo, notificación opcional y shell responsive de juez. Flags nuevos: `FLOWERFLOW_JUDGE_ACCOUNT_SETUP_NOTIFICATION_ENABLED`, `FLOWERFLOW_JUDGE_SETUP_LINK_TTL_MINUTES` y `FLOWERFLOW_JUDGE_ASSIGNMENT_NOTIFICATION_ENABLED`. No aplicar en producción: `OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED` mantiene `NO-GO RELEASE/PRODUCTION`. M7–M10 siguen fuera. Evidencia: `docs/27-phase-02b-m6a-judge-operations-reconciliation-report-2026-08-24.md`.

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

## Handoff M7 local

M7 queda `GO LOCAL/TEST`: 23 migraciones, suite completa 208/2,385, asignación manual M6A sin mínimos/límites, paquete ciego M5, borrador/cálculo M6 y sellado/reapertura/concurrencia M7 probados. `FLOWERFLOW_EVALUATION_FINALIZATION_ENABLED=false` es el rollback funcional M7 y conserva evidencia. No ejecutar seeders productivos ni borrar asignaciones, conflictos, paquetes, revisiones, reaperturas o auditoría.

## Estado funcional transferido

| Área | Estado local documentado | Estado productivo en este handoff |
|---|---|---|
| Fase 01 / 02A, cuarta categoría, plazo, legales v1.1, XLSX y 503/CSP | Implementado y validado localmente según diagnóstico/ExecPlans | Instalación confirmada sólo por el propietario. |
| Jueces, asignaciones, conflictos, rúbrica y evaluación | M1–M7 conformes local/test; paquete ciego, borrador/cálculo, envío inmutable y reapertura append-only | Nada de M1–M7 atribuido a producción. |
| Ganadores/resultados | 0 %; fuera de Fase 02B | No implementado; resultados deben permanecer apagados. |
| Operación externa | Runbooks y configuración documentados | Evidencia técnica independiente `POR_CONFIRMAR`. |

## Siguiente puerta

Las decisiones de Fase 02B hasta M7 están implementadas local/test. M6A sustituyó el contrato `4+2` por selección manual sin mínimos/límites; la divergencia con “al menos tres jueces” permanece `LEGAL_RECONCILIATION_REQUIRED`. El paquete vigente incluye:

- `.agent/execplans/flowerflow-phase-02b-evaluation-design.md`;
- `.agent/execplans/flowerflow-phase-02b-m1-judge-rbac.md`;
- `.agent/execplans/flowerflow-phase-02b-m2-judge-profile-onboarding.md`;
- `.agent/execplans/flowerflow-phase-02b-m3-versioned-rubric.md`;
- `.agent/execplans/flowerflow-phase-02b-m4-assignments-conflicts.md`;
- `.agent/execplans/flowerflow-phase-02b-m4a-two-substitutes-reconciliation.md`;
- `.agent/execplans/flowerflow-phase-02b-m5-blind-package.md`;
- `.agent/execplans/flowerflow-phase-02b-m6-draft-evaluation-server-scoring.md`;
- `.agent/execplans/flowerflow-phase-02b-m6a-judge-operations-reconciliation.md`;
- `.agent/execplans/flowerflow-phase-02b-m7-immutable-submission-append-only-reopening.md`;
- `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`;
- `docs/19-phase-02b-m2-implementation-report-2026-08-18.md`;
- `docs/20-phase-02b-m3-implementation-report-2026-08-18.md`;
- `docs/21-phase-02b-m4-implementation-report-2026-08-18.md`;
- `docs/22-phase-02b-m4a-unlimited-judges-implementation-report-2026-08-18.md`;
- `docs/23-phase-02b-m5-blind-package-implementation-report-2026-08-18.md`;
- `docs/24-phase-02b-m6-draft-evaluation-implementation-report-2026-08-18.md`;
- `docs/27-phase-02b-m6a-judge-operations-reconciliation-report-2026-08-24.md`;
- `docs/28-phase-02b-m7-evaluation-submission-reopening-report-2026-08-24.md`;
- `docs/adr/0008-phase-02b-evaluation-contract.md`;
- `docs/adr/0010-m6a-judge-operations-reconciliation.md`;
- `docs/adr/0011-m7-immutable-evaluation-submission-reopening.md`.

La siguiente puerta potencial es diseñar y autorizar exclusivamente M8. El estado es:

`M1–M7 CONFORMANT LOCAL/TEST — IMMUTABLE SUBMISSION/REOPENING ACTIVE — M8–M10 NOT AUTHORIZED`

M1 evita el acceso por descarte; M2 añade cuenta; M3 rúbrica; M6A fija asignación manual sin mínimos/límites y rúbrica v2; M5 conserva proyección ciega/anexos; M6 borrador/cálculo; M7 sellado y reapertura append-only. M8–M10 requieren autorización separada.

La asignación vigente es exclusivamente administrativa y explícita, sin mínimo/máximo ni sustitutos exclusivos. `P2B-BLOCK-001` queda superado operacionalmente por M6A, pero la contradicción jurídica de mínimos impide release/producción.

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
