# ADR-0012 — Comunicaciones transaccionales del ciclo de evaluación

- **Estado:** Accepted — implementación local/test M8; release/producción bloqueados.
- **Fecha:** 2026-08-25
- **Complementa:** ADR-0009 (outbox/bitácora), ADR-0010 (operación de jueces) y ADR-0011 (envío/reapertura).

## Contexto

M7 conserva eventos de negocio y evidencia append-only, pero deliberadamente no comunica conflicto, resolución, envío o reapertura. El outbox de ADR-0009 ya aporta delivery, intentos, cifrado, idempotencia, recuperación administrativa y un único worker `database/default`. Crear otra cola, tabla o mecanismo de correo fragmentaría la observabilidad y permitiría fallback no auditable.

La Mecánica v1.1 continúa exigiendo “al menos tres jueces”, mientras la operación autorizada no impone cobertura mínima. M8 no resuelve esa divergencia: conserva `OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED` y `NO-GO RELEASE/PRODUCTION`.

## Decisión

1. M8 extiende el outbox existente con cinco tipos: conflicto declarado, conflicto resuelto, evaluación enviada, evaluación reabierta y digest de cierre. No añade tablas, permisos, rutas, dependencias ni workers.
2. Los eventos de conflicto/resolución/reapertura y el evento existente de envío contienen únicamente IDs y se publican después del commit. Un rollback no produce delivery.
3. Los listeners crean deliveries de forma síncrona post-commit; sólo `DeliverCommunication` toca el transporte. M8 nunca usa el fallback legacy si el ledger está apagado.
4. Los destinatarios son deterministas: administrador que creó la asignación para conflicto; juez saliente para resolución; juez sujeto y administrador responsable para cada revisión enviada; juez sujeto para reapertura. No existe fallback a “todos los administradores”.
5. El juez entrante conserva exclusivamente la notificación opcional M6A; M8 no genera un duplicado.
6. Cada delivery revalida flags, fingerprint, rol/permiso/estado, relaciones, plazo y, cuando corresponde, rúbrica/paquete. Un evento no accionable queda `cancelled` con código redactado.
7. El comando `flowerflow:evaluations-queue-close-digests` es dry-run por defecto. `--execute` crea un solo digest por juez desde `2026-08-28 00:00:00` hasta `2026-08-28 23:59:59` Hermosillo; antes no escribe y desde el siguiente segundo falla cerrado. Timezone, configuración o cualquier `due_at` divergente bloquean el lote completo.
8. El digest persiste conteos cifrados en su contexto y los revalida antes del transporte. Nunca enumera proyectos, evaluaciones, scores, comentarios u otros jueces.
9. `sent` significa únicamente “Aceptado por el servidor de correo”. No se afirma entrega sin webhook de proveedor.
10. Los recordatorios programados de participantes del 20/22 de agosto no se recrean ni se reproducen retroactivamente. M8 tampoco implementa consolidación, ranking, resultados, cierre administrativo o purga.

## Consecuencias

- La bitácora administrativa existente muestra y recupera también los cinco tipos M8 sin ampliar privilegios.
- El worker actual `--queue=high,exports,default,low` es suficiente; el scheduler añade una cuarta tarea, ejecutada cada minuto con `withoutOverlapping`.
- Un fallo de enqueue o transporte nunca revierte conflicto, resolución, envío o reapertura.
- La idempotencia incorpora tipo, destinatario, evento fuente y versión de plantilla; solicitudes/listeners concurrentes convergen.
- El rollback funcional apaga `FLOWERFLOW_EVALUATION_NOTIFICATIONS_ENABLED` y `FLOWERFLOW_EVALUATION_CLOSE_DIGEST_ENABLED`; la evidencia del outbox y auditoría se conserva.

## Alternativas descartadas

- Enviar correo dentro de las Actions: acopla transporte y puede revertir negocio.
- Crear tablas o workers M8: el esquema existente cubre identidad, contexto cifrado, intentos y recuperación.
- Notificar a todos los administradores: amplía datos y elimina responsabilidad operativa determinista.
- Enviar un correo por asignación al cierre: multiplica ruido; el contrato exige un digest por juez.
- Reproducir eventos M7 históricos o recordatorios vencidos: produciría comunicaciones retroactivas no autorizadas.

## Verificación

La evidencia vive en `.agent/execplans/flowerflow-phase-02b-m8-evaluation-communications.md` y `docs/29-phase-02b-m8-evaluation-communications-report-2026-08-25.md`.
