# Fase 02B M8 — comunicaciones transaccionales del ciclo de evaluación

Este ExecPlan es un documento vivo y se mantiene conforme a `.agent/PLANS.md`.

## Propósito

Implementar exclusivamente comunicaciones transaccionales para conflicto declarado/resuelto, evaluación enviada/reabierta y un digest único por juez al cerrar la ventana de evaluación. La bitácora ADR-0009, su job central y su recuperación administrativa continúan como única autoridad de entrega y observabilidad.

## Baseline y guard

- Repositorio: `/home/ccortesg/workspace/flowerflow`.
- Rama: `codex/submission-deadline-extension`.
- HEAD/upstream/merge-base verificados: `a1a2a3babbb7b827b73cb8455b340e697ec93cb1`.
- Árbol inicial limpio; 23 migraciones, 104 rutas propias y tres tareas programadas.
- M1–M7 documentados `GO LOCAL/TEST`; suite M7: 208 pruebas/2,385 aserciones.
- Antes de cualquier prueba que escriba base se demostrará, sin secretos: `APP_ENV=testing`, MySQL en `127.0.0.1`, base `flowerflow_testing`, usuario `flowerflow_testing_user` y `SELECT DATABASE()=flowerflow_testing`.

## Alcance

- Tipos `judge.conflict_declared`, `judge.conflict_resolved`, `evaluation.submitted`, `evaluation.reopened` y `evaluation.close_digest`.
- Eventos de dominio ID-only y post-commit; listeners síncronos que sólo crean deliveries idempotentes.
- Plantillas HTML/texto institucionales, enlaces autenticados y contenido mínimo sin evaluación ni PII de terceros.
- Revalidación de flags, destinatario, rol/permiso, ownership, estado, relaciones, ventana, plazo, rúbrica y paquete dentro del worker.
- Comando dry-run por defecto `flowerflow:evaluations-queue-close-digests`, opción `--execute` y scheduler cada minuto sin solapamiento.
- Un digest por juez con conteos de enviadas, pendientes, conflictos/reemplazos y canceladas.
- Etiquetas/filtros automáticos en la bitácora existente y auditoría redactada de requested/skipped.

## Exclusiones

- Sin migraciones, dependencias, workers, permisos o rutas nuevas.
- Sin fallback legacy si el ledger está apagado.
- Sin replay/backfill M7 ni recordatorios retroactivos de participantes del 20/22 de agosto.
- Sin consolidación, promedio, cobertura mínima, empate, ranking, ganador, resultado, cierre administrativo, retención o purga.
- Sin cambios a propuestas, snapshots, folios, PDFs, hashes, aceptaciones, archivos privados o datos reales.
- Sin stage, commit, push, despliegue, producción, SMTP real o servicios externos.

## Arquitectura e idempotencia

- El outbox existente conserva `communication_deliveries`, attempts, cifrado, cola `database/default`, `DeliverCommunication` y recuperación en `high`.
- Los listeners comprobarán simultáneamente ledger y flag M8 antes de invocar directamente `recordAndDispatch`; por tanto nunca caerán a la ruta legacy.
- Las claves fuente serán estables por evento, propósito del destinatario y versión de plantilla; la unicidad final seguirá gobernada por el idempotency key del outbox.
- Conflicto declarado: admin responsable exacto de la asignación, sin fallback.
- Conflicto resuelto: juez saliente exacto; el entrante conserva únicamente la notificación M6A opcional.
- Envío/reenvío: juez sujeto y admin responsable exacto; para revisión inicial el asignador y para reabierta quien la reabrió.
- Reapertura: juez sujeto exacto; si ya se reenvió antes del worker, se cancela.
- Digest: perfil de juez y competencia exactos; una fila por juez con al menos una asignación.

## Ventana del digest

- Zona única `America/Hermosillo`.
- Cierre inclusivo: `2026-08-27 23:59:59` local / `2026-08-28 06:59:59 UTC`.
- Enqueue permitido desde `2026-08-28 00:00:00` hasta `2026-08-28 23:59:59` local.
- Desde `2026-08-29 00:00:00` local se rechaza el catch-up.
- Timezone/configuración o cualquier `JudgeAssignment.due_at` divergente bloquean el lote completo antes de crear deliveries.

## Pruebas y validación

- Post-commit/rollback, flags, destinatarios exactos, idempotencia, carreras, cancelaciones, privacidad, plantillas duales, bitácora y recuperación.
- Digest en segundos frontera, conteos, un correo por juez, concurrencia, drift y ausencia de funciones excluidas.
- Regresión M1–M7, suite completa, Pint, Composer, Yarn, build, JSON, rutas, scheduler, migraciones, diff, enlaces y scans.
- UAT Firefox local con `array`, cola database y datos `example.test` en 1440×900, 1024×768 y 390×844.

## Progreso

- [x] 2026-08-25: baseline Git, lecturas obligatorias y Mecánica v1.1 completa verificados.
- [x] 2026-08-25: guard MySQL exacto y baseline dirigido 23/323.
- [x] 2026-08-25: configuración, cinco tipos, eventos/listeners, plantillas y registry implementados.
- [x] 2026-08-25: comando/servicio de digest determinista y cuarto schedule implementados.
- [x] 2026-08-25: M8 dirigida 7/99; regresión conjunta previa 30/418, sin fallos.
- [x] 2026-08-25: ADR 0012, informe 29 y documentación canónica actualizados con evidencia final.
- [x] 2026-08-25: suite completa 216/2,495 y gates técnicos cerrados; único advisory bajo conocido de Quill, sin parche disponible.
- [x] 2026-08-25: UAT Firefox local en 1440×900, 1024×768 y 390×844 con `array`, cola database y worker `high,default` completado.

## Decisiones y hallazgos

- El esquema ADR-0009 admite los cinco tipos, contexto cifrado, entidad relacionada e idempotencia sin cambio estructural; por contrato no se añadirá migración.
- `sent` continuará mostrado como “Aceptado por el servidor de correo”; M8 no acredita entrega, apertura o rebote.
- La Mecánica v1.1 confirma visualmente “al menos tres jueces”. Se conserva `OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`: aun con M8 verde, release/producción permanecen `NO-GO`.
- El digest conserva los conteos en contexto cifrado y los revalida en el worker; si cambian, cancela en lugar de enviar un resumen obsoleto.
- La primera corrida de M8 reportó seis defectos acotados a código/pruebas nuevos (tipo Carbon y expectativas/uso de enums); se corrigieron antes de regresión y no hubo falla M1–M7.
- Los primeros intentos del UAT detectaron dos defectos del arnés local —sesión `array` no persistente y una simulación de zoom que no activaba breakpoints—. El servidor se reinició con sesiones database y el zoom se comprobó mediante viewport CSS equivalente; no se requirió corregir código de producto.

## Riesgos y rollback

- Riesgo principal: notificar una transición que dejó de ser accionable; se mitiga reconstruyendo y revalidando desde IDs dentro del worker.
- Riesgo de duplicado tras resultado `unknown`: permanece la recuperación individual reforzada de ADR-0009.
- Rollback funcional: `FLOWERFLOW_EVALUATION_NOTIFICATIONS_ENABLED=false` y `FLOWERFLOW_EVALUATION_CLOSE_DIGEST_ENABLED=false`; conservar deliveries, attempts, auditoría y eventos.

## Resultados

- Baseline dirigido previo: 23 pruebas/323 aserciones.
- M8 dirigida actual: 7 pruebas/99 aserciones.
- Concurrencia M8: 1 prueba/11 aserciones, dos procesos y un único delivery/attempt/job.
- Regresión final M7/M8 posterior a retirar `mode` del evento: 13 pruebas/275 aserciones.
- Regresión conjunta ledger/conflictos/M7/concurrencia/M8: 30 pruebas/418 aserciones antes del último hardening; M8 volvió a pasar 7/99 después.
- Suite completa: 216 pruebas/2,495 aserciones, verde en MySQL aislado.
- `vendor/bin/pint --test`, `composer validate --strict`, `composer check-platform-reqs`, `composer audit`, build Vite (784 módulos), JSON, enlaces Markdown, rutas, scheduler, migraciones, scans y `git diff --check`: verdes.
- `yarn audit`: únicamente `GHSA-v3m3-f69x-jf25`, severidad baja, Quill 2.0.3 sin parche; cero moderados/altos/críticos.
- Rutas propias: 104. Scheduler: cuatro tareas, incluido el digest cada minuto. Migraciones: 23/23 bajo `flowerflow_testing`; M8 no añade migración.
- UAT: cinco deliveries y seis attempts sintéticos; el worker procesó seis jobs, dejó cuatro `sent`, un conflicto obsoleto `cancelled`, cero jobs/failed jobs. Filtros, detalle, procesamiento prioritario, redacción, marcas, teclado, foco, reflow/zoom equivalente, consola, juez 403 y visitante redirigido quedaron verdes.
- El comando de digest antes del cierre devolvió `evaluation_close_digest_window_not_open` y cero deliveries; sus segundos límite/catch-up se verificaron en pruebas con reloj controlado.
- Resultado: `GO LOCAL/TEST — M8`. Release/producción conserva `NO-GO — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`.
