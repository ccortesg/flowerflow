# Handoff actual — Flower Flow

> **Extensión local de exportaciones — 2026-08-24:** `/panel/propuestas` añade `Exportar Contactos` para todas las propuestas `submitted`, con una fila por proyecto y cinco columnas tomadas sólo del snapshot inmutable. Reutiliza permiso, contraseña, job/cola `exports`, disk privado, ownership, auditoría y expiración existentes; `filters.kind` distingue `full|submitted_contacts` sin migración. No hubo producción, stage, commit ni push. Evidencia final: `.agent/execplans/flowerflow-panel-submitted-contacts-export.md`.

> **Milestone independiente de comunicaciones — 2026-08-23, `GO LOCAL/TEST`:** el árbol local añade ADR-0009, tablas de deliveries/intentos, job central, panel admin `Notificaciones` y recuperación individual de correo existente. Suite final 191/2,255, 21 migraciones, 84 rutas, build y UAT Firefox verdes. No implementa M7/M8, campañas, resultados o producción. Rollback: `FLOWERFLOW_COMMUNICATION_LEDGER_ENABLED=false`; conservar evidencia. Ver `.agent/execplans/flowerflow-communication-delivery-ledger.md` y `docs/26-communication-delivery-ledger-implementation-report-2026-08-23.md`.

> **Adenda local del panel — 2026-08-22, `GO LOCAL/TEST`:** sobre baseline `bffc7d7f4738e0937b276ea9d5d22e3744afe65c` se implementaron recordatorios de borradores, confirmación firmada sin archivo, excepción administrativa sin aceptaciones ajenas y diagnóstico de exports. Suite final 179/2,130, build y UAT Firefox verdes. Los flags nacen apagados. No hubo stage/commit/push, producción, PDFs ni datos reales; resultados finales viven en `.agent/execplans/flowerflow-panel-submission-actions-reminders.md` y `docs/25-panel-submission-actions-reminders-implementation-report-2026-08-22.md`.

> **Adenda vigente M6 — 2026-08-18:** el estado canónico está en `docs/11-operations-handoff.md`. M1–M6 quedaron verdes local/test. M6 añade apertura explícita, guardado optimista y cálculo decimal servidor; M7–M10 permanecen separados/no autorizados.

Fecha de corte: 2026-08-18.

## Estado canónico

- Checkout: `/home/ccortesg/workspace/flowerflow`.
- Rama auditada: `codex/submission-deadline-extension`.
- HEAD/remoto/ancestro común observado al iniciar M6: `e4e4cd2ff7144cce5f9385f5f11c122cda80e7b8`; el árbol contiene únicamente el diff M6 no publicado por esta tarea.
- El diagnóstico vigente es `docs/16-project-status-by-module-and-role-2026-08-17.md`: producto maestro 70 %, alcance local aprobado hasta M6 100 %, runtime aislado del RC 100 %, runtime local primario 42 % y preparación productiva 34 %.
- Fase 01, Fase 02A, cuarta categoría, exportación privada, ampliación de plazo y catálogo/vínculos/aceptaciones v1.1 están implementados, probados y recorridos localmente sin tocar v1.0 ni aceptaciones históricas.
- La Mecánica v1.1 definitiva confirma cuatro categorías y máximo cuatro propuestas. El propietario aceptó la superposición de accesibilidad sin cambios y resolvió la continuidad de aceptaciones v1.0 sin reaceptación forzada ni backfill.
- El propietario designó como v1.0 el archivo físico actual `3bcf31…`; la diferencia con `42bd5e…` permanece como incidencia histórica visible, pero deja de ser bloqueo operativo. Ver `docs/17-legal-v1-1-reconciliation-2026-08-17.md`.
- P2 503/CSP quedó resuelto localmente con vista accesible/de marca, assets Vite normales, cero estilos inline y soporte de pre-render de mantenimiento.
- Topología productiva confirmada por el propietario: checkout Git directo en `/var/www/flowerflow`, sin `releases/current/shared`; el VirtualHost informado `app.sguniformes.com.mx` apunta a esa ruta. No inferir por ello un cambio del host canónico público ni alterar Apache.
- Fase 02B M1–M6 está implementada sólo en local/test. M4A lleva esquema, derivación y selección manual a `4+2` ilimitado; M5 materializa paquete ciego; M6 agrega borrador/revisión/scores, lock 409 y cálculo BCMath. Envío M7, ganadores, resultados, ARCO y despliegue permanecen fuera.
- Existe evidencia pública histórica de una release anterior (`26256e3`), pero no prueba que `e2f4345` esté desplegado.

## Evidencia vigente

- Última evidencia ejecutada M6: suite MySQL aislada 163 pruebas/1,937 aserciones; M6 13/228 y M1–M6 dirigidas 54/888.
- Pint, Composer validate/platform/audit, JSON y build Vite: verdes.
- Yarn conserva un advisory bajo conocido de Quill 2.0.3 sin fix; sanitización servidor vigente.
- 73 rutas propias sin vendor y 19 migraciones aplicadas en `flowerflow_testing`.
- UAT Firefox M6: inicio explícito, parcial/completo 75.25, refresh, 409 en dos pestañas, XSS, vencimiento sólo lectura, conflicto, replacement independiente, 403/404 y tres viewports; teclado/foco/zoom/reflow/consola limpios.
- `scripts/serve_local_testing.sh` valida base/cuenta/catálogos/flags antes de servir; usa sesiones database, correo array, cola sync, limpia cache de permisos y mantiene resultados apagados. La base terminó sembrada con cero usuarios/perfiles/sesiones sintéticos.
- La base local primaria `flowerflow` conserva cuatro migraciones funcionales pendientes.
- El `.env` local conserva `FLOWERFLOW_MAX_SUBMISSIONS_PER_USER=3`; el contrato, código, ejemplo y pruebas usan cuatro.
- Flags observados: público/panel activos; registro/recepción/resultados/admisibilidad inactivos.

## Siguiente puerta

M7 es sólo una puerta potencial: no está implementado ni autorizado. Requerirá un prompt separado para confirmación/envío inmutable y reapertura append-only, preservando M4A–M6. No puede mezclar M8+, consolidación, resultados o producción. Ninguna puerta autoriza el checkout `/var/www/flowerflow`.

## Reglas de continuidad

- Leer `AGENTS.md`, `.agent/PLANS.md`, el ExecPlan activo y ADR antes de editar.
- No registrar secretos, PII, documentos reales ni contenido sensible de `.env`.
- Separar código implementado, flag activado, migración aplicada y despliegue verificado.
- No ejecutar `migrate:fresh` fuera de la base/cuenta de pruebas exactas y del guard previo.
- No hacer stage, commit, push, AWS ni despliegue sin autorización expresa de esa acción.
