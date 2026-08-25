# Handoff actual — Flower Flow

> **M8 `GO LOCAL/TEST` — 2026-08-25:** sobre baseline limpio `a1a2a3babbb7b827b73cb8455b340e697ec93cb1` se implementaron cinco comunicaciones del ciclo de evaluación y digest resumido mediante el outbox existente. Resultado final: 216 pruebas/2,495 aserciones, 23 migraciones, 104 rutas, cuatro schedules y UAT Firefox verde. No hay migración, permiso, ruta o worker nuevo. Flags default-off y rollback sin borrar evidencia. No hubo stage/commit/push/deploy, producción o SMTP real. M9–M10 siguen fuera y se conserva `NO-GO RELEASE/PRODUCTION — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`.

> **M7 `GO LOCAL/TEST` — validación final 2026-08-25:** sobre baseline limpio `8da3ed0cb19e3548380ca0732db11d3d205e4612` se implementaron confirmación, mínimo 100, sellado inmutable, reapertura append-only, actor administrativo real y ventanas exactas. Resultado final: 208 pruebas/2,385 aserciones, 23 migraciones, 104 rutas, gates y UAT Firefox verdes. Migración y rutas juez/panel son aditivas; flags default-off y rollback funcional sin borrar evidencia. M8–M10, comunicaciones de evaluación, commit/push/deploy y producción siguen fuera. `NO-GO RELEASE/PRODUCTION — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`.

> **M6A `GO LOCAL/TEST` — 2026-08-24:** baseline limpio `d3f616c86d72bfd32c1545205df057e19cf765ea`; suite final 203/2,220, 22 migraciones y 95 rutas. El contrato vigente sustituye operacionalmente `4+2`, cobertura fija, sustitutos exclusivos y cinco criterios para nuevas asignaciones: selección admin sin mínimos/límites, v2 legal de cuatro criterios, setup purpose-bound y UX juez. V1 y los informes previos se conservan como historia/evidencia. No hubo stage/commit/push/deploy. `NO-GO RELEASE/PRODUCTION` hasta reconciliar “al menos tres jueces”. Ver `docs/27-phase-02b-m6a-judge-operations-reconciliation-report-2026-08-24.md`.

> **Extensión local de exportaciones — 2026-08-24:** `/panel/propuestas` añade `Exportar Contactos` para todas las propuestas `submitted`, con una fila por proyecto y cinco columnas tomadas sólo del snapshot inmutable. Reutiliza permiso, contraseña, job/cola `exports`, disk privado, ownership, auditoría y expiración existentes; `filters.kind` distingue `full|submitted_contacts` sin migración. No hubo producción, stage, commit ni push. Evidencia final: `.agent/execplans/flowerflow-panel-submitted-contacts-export.md`.

> **Milestone independiente de comunicaciones — 2026-08-23, `GO LOCAL/TEST`:** el árbol local añade ADR-0009, tablas de deliveries/intentos, job central, panel admin `Notificaciones` y recuperación individual de correo existente. Suite final 191/2,255, 21 migraciones, 84 rutas, build y UAT Firefox verdes. No implementa M7/M8, campañas, resultados o producción. Rollback: `FLOWERFLOW_COMMUNICATION_LEDGER_ENABLED=false`; conservar evidencia. Ver `.agent/execplans/flowerflow-communication-delivery-ledger.md` y `docs/26-communication-delivery-ledger-implementation-report-2026-08-23.md`.

> **Adenda local del panel — 2026-08-22, `GO LOCAL/TEST`:** sobre baseline `bffc7d7f4738e0937b276ea9d5d22e3744afe65c` se implementaron recordatorios de borradores, confirmación firmada sin archivo, excepción administrativa sin aceptaciones ajenas y diagnóstico de exports. Suite final 179/2,130, build y UAT Firefox verdes. Los flags nacen apagados. No hubo stage/commit/push, producción, PDFs ni datos reales; resultados finales viven en `.agent/execplans/flowerflow-panel-submission-actions-reminders.md` y `docs/25-panel-submission-actions-reminders-implementation-report-2026-08-22.md`.

> **Adenda vigente M6 — 2026-08-18:** el estado canónico está en `docs/11-operations-handoff.md`. M1–M6 quedaron verdes local/test. M6 añade apertura explícita, guardado optimista y cálculo decimal servidor; M7–M10 permanecen separados/no autorizados.

Fecha de corte canónico: 2026-08-25.

## Estado canónico

- Checkout: `/home/ccortesg/workspace/flowerflow`.
- Rama auditada: `codex/submission-deadline-extension`.
- HEAD/remoto/ancestro común observado al iniciar M8: `a1a2a3babbb7b827b73cb8455b340e697ec93cb1`; el árbol contiene el diff local M8 no publicado por esta tarea.
- El diagnóstico vigente está en `docs/16-project-status-by-module-and-role-2026-08-17.md`; informes M7/M8: `docs/28-phase-02b-m7-evaluation-submission-reopening-report-2026-08-24.md` y `docs/29-phase-02b-m8-evaluation-communications-report-2026-08-25.md`.
- Fase 01, Fase 02A, cuarta categoría, exportación privada, ampliación de plazo y catálogo/vínculos/aceptaciones v1.1 están implementados, probados y recorridos localmente sin tocar v1.0 ni aceptaciones históricas.
- La Mecánica v1.1 definitiva confirma cuatro categorías y máximo cuatro propuestas. El propietario aceptó la superposición de accesibilidad sin cambios y resolvió la continuidad de aceptaciones v1.0 sin reaceptación forzada ni backfill.
- El propietario designó como v1.0 el archivo físico actual `3bcf31…`; la diferencia con `42bd5e…` permanece como incidencia histórica visible, pero deja de ser bloqueo operativo. Ver `docs/17-legal-v1-1-reconciliation-2026-08-17.md`.
- P2 503/CSP quedó resuelto localmente con vista accesible/de marca, assets Vite normales, cero estilos inline y soporte de pre-render de mantenimiento.
- Topología productiva confirmada por el propietario: checkout Git directo en `/var/www/flowerflow`, sin `releases/current/shared`; el VirtualHost informado `app.sguniformes.com.mx` apunta a esa ruta. No inferir por ello un cambio del host canónico público ni alterar Apache.
- Fase 02B M1–M8 está implementada sólo en local/test. M6A sustituyó `4+2`, M7 agregó sellado/reapertura y M8 comunicaciones/digest por outbox. M9–M10, consolidación, ganadores, resultados, ARCO y despliegue permanecen fuera.
- Existe evidencia pública histórica de una release anterior (`26256e3`), pero no prueba que `e2f4345` esté desplegado.

## Evidencia vigente

- Última evidencia ejecutada M8: suite MySQL aislada 216 pruebas/2,495 aserciones; M8 7/99 y concurrencia 1/11.
- Pint, Composer validate/platform/audit, JSON y build Vite: verdes.
- Yarn conserva un advisory bajo conocido de Quill 2.0.3 sin fix; sanitización servidor vigente.
- 104 rutas propias sin vendor y 23 migraciones aplicadas en `flowerflow_testing`.
- UAT Firefox M8: bitácora, filtro, detalle, procesamiento prioritario, worker database, aceptación/cancelación redactada, juez 403, visitante redirigido y tres viewports; teclado/foco/zoom-reflow/consola limpios.
- `scripts/serve_local_testing.sh` valida base/cuenta/catálogos/flags antes de servir; usa sesiones database, correo array, cola sync, limpia cache de permisos y mantiene resultados apagados. La base terminó sembrada con cero usuarios/perfiles/sesiones sintéticos.
- La base local primaria `flowerflow` conserva cuatro migraciones funcionales pendientes.
- El `.env` local conserva `FLOWERFLOW_MAX_SUBMISSIONS_PER_USER=3`; el contrato, código, ejemplo y pruebas usan cuatro.
- Flags observados: público/panel activos; registro/recepción/resultados/admisibilidad inactivos.

## Siguiente puerta

La siguiente puerta potencial es M9 y requiere autorización separada. No puede inferirse consolidación, cobertura, ranking, resultados, retención, producción ni acceso al checkout `/var/www/flowerflow` desde M8.

## Reglas de continuidad

- Leer `AGENTS.md`, `.agent/PLANS.md`, el ExecPlan activo y ADR antes de editar.
- No registrar secretos, PII, documentos reales ni contenido sensible de `.env`.
- Separar código implementado, flag activado, migración aplicada y despliegue verificado.
- No ejecutar `migrate:fresh` fuera de la base/cuenta de pruebas exactas y del guard previo.
- No hacer stage, commit, push, AWS ni despliegue sin autorización expresa de esa acción.
