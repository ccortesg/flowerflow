# Handoff actual — Flower Flow

> **ADR-0015 — 2026-08-25, local/test:** sobre baseline inicial limpio `94af3525e54af49130be079ebc1bbab27e4b03cd` se centralizó elegibilidad administrativa para aceptar `pending_setup` sin acceso ni correo, se unificó folio/ULID y se añadió exportación privada/asíncrona de evaluaciones con permiso, ownership, snapshot inmutable y tres hojas. Durante la tarea un commit externo `2b3d4f9 Videos` avanzó HEAD/upstream sin solapar estos cambios. La evidencia funcional es verde, pero el gate formal queda `NO-GO LOCAL/TEST` porque Pint global falla únicamente en `video-tutorial/scripts/freeze-time.php`, fuera del alcance preservado; Pint del producto sí pasa. Ver `docs/32-pending-judge-reference-filters-evaluation-exports-report-2026-08-25.md`. No hubo stage, commit, push, deploy o producción por esta tarea; se conserva `NO-GO RELEASE/PRODUCTION — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`.

> **M8A `GO LOCAL/TEST` — 2026-08-25:** sobre baseline limpio `4aff0b0cd6e65a2101463657165e11e30379be00` se implementó el wizard responsive de cuatro pasos, autosave cada 30 segundos con lock optimista y exportaciones privadas PDF/XLSX construidas sólo desde el paquete ciego fijado. Resultado final: 233 pruebas/2,849 aserciones, una prueba opt-in omitida, 112 rutas, cuatro schedules, 24 migraciones, gates y UAT Firefox en tres viewports verdes. No añade migración, permiso, job, cola o worker; sí registra DOMPDF y PhpSpreadsheet. No hubo stage/commit/push/deploy ni producción. Se conserva `NO-GO RELEASE/PRODUCTION — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`. Ver `docs/31-phase-02b-m8a-judge-evaluation-wizard-exports-report-2026-08-25.md`.

> **Asignación simultánea — `GO LOCAL/TEST`, 2026-08-25:** se añadió un asistente default-off para un juez y hasta veinte propuestas, con preflight cifrado, transacción por propuesta, éxito parcial y un único correo consolidado. Suite 228/2,653, dirigida final 9/123, benchmark de 200 MiB en 2.589 s y UAT Firefox en tres viewports. No hay migración, tabla batch, worker nuevo, stage/commit/push/deploy o producción. Ver `.agent/execplans/flowerflow-bulk-judge-assignment.md` y `docs/30-bulk-judge-assignment-report-2026-08-25.md`.

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
- HEAD/remoto/ancestro común observado al iniciar M8A: `4aff0b0cd6e65a2101463657165e11e30379be00`; el árbol contiene el diff local M8A no publicado por esta tarea.
- El diagnóstico vigente está en `docs/16-project-status-by-module-and-role-2026-08-17.md`; informes M7/M8/M8A: `docs/28-phase-02b-m7-evaluation-submission-reopening-report-2026-08-24.md`, `docs/29-phase-02b-m8-evaluation-communications-report-2026-08-25.md` y `docs/31-phase-02b-m8a-judge-evaluation-wizard-exports-report-2026-08-25.md`.
- Fase 01, Fase 02A, cuarta categoría, exportación privada, ampliación de plazo y catálogo/vínculos/aceptaciones v1.1 están implementados, probados y recorridos localmente sin tocar v1.0 ni aceptaciones históricas.
- La Mecánica v1.1 definitiva confirma cuatro categorías y máximo cuatro propuestas. El propietario aceptó la superposición de accesibilidad sin cambios y resolvió la continuidad de aceptaciones v1.0 sin reaceptación forzada ni backfill.
- El propietario designó como v1.0 el archivo físico actual `3bcf31…`; la diferencia con `42bd5e…` permanece como incidencia histórica visible, pero deja de ser bloqueo operativo. Ver `docs/17-legal-v1-1-reconciliation-2026-08-17.md`.
- P2 503/CSP quedó resuelto localmente con vista accesible/de marca, assets Vite normales, cero estilos inline y soporte de pre-render de mantenimiento.
- Topología productiva confirmada por el propietario: checkout Git directo en `/var/www/flowerflow`, sin `releases/current/shared`; el VirtualHost informado `app.sguniformes.com.mx` apunta a esa ruta. No inferir por ello un cambio del host canónico público ni alterar Apache.
- Fase 02B M1–M8 y M8A están implementadas sólo en local/test. M6A sustituyó `4+2`, M7 agregó sellado/reapertura, M8 comunicaciones/digest por outbox y M8A reorganizó la experiencia del juez sin cambiar persistencia. M9–M10, consolidación, ganadores, resultados, ARCO y despliegue permanecen fuera.
- Existe evidencia pública histórica de una release anterior (`26256e3`), pero no prueba que `e2f4345` esté desplegado.

## Evidencia vigente

- Última evidencia ejecutada M8A: suite MySQL aislada 233 pruebas/2,849 aserciones, con una prueba de carga máxima opt-in omitida; regresión dirigida 37/812.
- Pint, Composer validate/platform/audit, JSON y build Vite: verdes.
- Yarn conserva un advisory bajo conocido de Quill 2.0.3 sin fix; sanitización servidor vigente.
- 112 rutas propias sin vendor, cuatro schedules y 24 migraciones aplicadas en `flowerflow_testing`; M8A no añade migración.
- UAT Firefox M8A: cuatro pasos, autosave real, navegación con guardado, 422, offline, dos pestañas/409, envío y sólo lectura, PDF/XLSX y tres viewports; teclado/foco/reflow/consola limpios. El zoom nativo 200–400 % y un smoke en Excel/LibreOffice real se mantienen como verificación manual previa a release.
- `scripts/serve_local_testing.sh` valida base/cuenta/catálogos/flags antes de servir; usa sesiones database, correo array, cola sync, limpia cache de permisos y mantiene resultados apagados. La base terminó sembrada con cero usuarios/perfiles/sesiones sintéticos.
- La base local primaria `flowerflow` conserva cuatro migraciones funcionales pendientes.
- El `.env` local conserva `FLOWERFLOW_MAX_SUBMISSIONS_PER_USER=3`; el contrato, código, ejemplo y pruebas usan cuatro.
- Flags observados: público/panel activos; registro/recepción/resultados/admisibilidad inactivos.

## Siguiente puerta

La siguiente puerta potencial es M9 y requiere autorización separada. No puede inferirse consolidación, cobertura, ranking, resultados, retención, producción ni acceso al checkout `/var/www/flowerflow` desde M8A.

## Reglas de continuidad

- Leer `AGENTS.md`, `.agent/PLANS.md`, el ExecPlan activo y ADR antes de editar.
- No registrar secretos, PII, documentos reales ni contenido sensible de `.env`.
- Separar código implementado, flag activado, migración aplicada y despliegue verificado.
- No ejecutar `migrate:fresh` fuera de la base/cuenta de pruebas exactas y del guard previo.
- No hacer stage, commit, push, AWS ni despliegue sin autorización expresa de esa acción.
