# Informe de implementación — Fase 02B M8

Fecha: 2026-08-25 (`America/Hermosillo`).

## 1. Estado

- Estado: `GO LOCAL/TEST — M8`.
- Estado de release: `NO-GO RELEASE/PRODUCTION — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`.
- No hubo stage, commit, push, despliegue, producción, SMTP real, servicios externos ni datos reales.
- M9–M10 continúan `NOT IMPLEMENTED / NOT AUTHORIZED`.

La Mecánica pública v1.1 conserva “al menos tres jueces”; M8 no altera el documento ni resuelve la operación sin mínimos.

## 2. Baseline y guard

- Checkout `/home/ccortesg/workspace/flowerflow`; rama `codex/submission-deadline-extension`.
- HEAD/upstream/merge-base inicial `a1a2a3babbb7b827b73cb8455b340e697ec93cb1`; árbol inicial limpio.
- Baseline: 23 migraciones, 104 rutas propias, tres tareas programadas, M1–M7 `GO LOCAL/TEST`, suite M7 208/2,385.
- Guard demostrado sin contraseña: `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_DATABASE=flowerflow_testing`, `DB_USERNAME=flowerflow_testing_user`, `SELECT DATABASE()=flowerflow_testing`.
- Regresión dirigida previa: 23 pruebas/323 aserciones.

## 3. Implementación

M8 añade cinco `CommunicationType`, cuatro eventos ID-only post-commit, listeners y plantillas HTML/texto institucionales. `EvaluationCommunicationDispatcher` resuelve destinatarios exactos y usa exclusivamente `recordAndDispatch`; `CommunicationMessageRegistry` reconstruye y revalida cada comunicación. No se añadió migración, ruta, permiso, dependencia o proceso worker porque el esquema/bitácora existentes fueron suficientes.

El comando `flowerflow:evaluations-queue-close-digests` es dry-run por defecto; `--execute` usa el outbox. El scheduler lo ejecuta cada minuto con exclusión mutua. Un digest por juez conserva sólo conteos cifrados y los compara con el estado autoritativo antes de enviar.

## 4. Destinatarios e idempotencia

| Evento | Destinatario | Fuente idempotente |
|---|---|---|
| Conflicto declarado | admin exacto que creó la asignación | conflicto + declarado + admin responsable |
| Conflicto resuelto | juez saliente | conflicto + resuelto + juez saliente |
| Evaluación enviada/reenviada | juez sujeto y admin responsable | revisión + enviado + propósito |
| Evaluación reabierta | juez sujeto | revisión destino + reapertura + juez |
| Cierre | un digest por juez con asignaciones | competencia + perfil + cierre UTC |

La versión global de plantilla y el fingerprint HMAC del destinatario forman parte de la clave final. El juez entrante sólo recibe la notificación M6A cuando el flag y la casilla de esa operación lo solicitan.

## 5. Privacidad y revalidación

Las plantillas no incluyen scores, componentes, total, comentarios, motivos, identidad del otro actor, propuesta o archivos. Los eventos y jobs no transportan correo ni contenido. El worker valida flags, destinatario/fingerprint, rol/permisos/estado, relaciones, deadline y, cuando aplica, rúbrica/paquete. Un estado obsoleto pasa a `cancelled` con reason code redactado. La recuperación desde `Notificaciones` repite la misma revalidación.

`sent` continúa significando “Aceptado por el servidor de correo”; no existe confirmación de entrega al buzón.

## 6. Ventana de digest

- Cierre inclusivo de evaluación: `2026-08-27 23:59:59 America/Hermosillo` / `2026-08-28 06:59:59 UTC`.
- Apertura de digest: `2026-08-28 00:00:00 America/Hermosillo`.
- Último segundo admitido: `2026-08-28 23:59:59`.
- Desde `2026-08-29 00:00:00` falla cerrado.
- Timezone/configuración/`due_at` divergentes producen cero deliveries para todo el lote.

## 7. Pruebas y gates

- M8 dirigida: 7 pruebas/99 aserciones.
- Concurrencia M8: 1 prueba/11 aserciones; dos procesos convergen en un delivery, un attempt y un job.
- Regresión final M7/M8 después de convertir `EvaluationSubmitted` en estrictamente ID-only: 13 pruebas/275 aserciones.
- Regresión conjunta ledger/conflictos/M7/concurrencia/M8: 30 pruebas/418 aserciones antes de la última ampliación determinista del digest; M8 volvió a pasar después con 7/99.
- Suite completa: 216 pruebas/2,495 aserciones, todas verdes en MySQL `flowerflow_testing`.
- Guard demostrado: `APP_ENV=testing`, `mysql`, loopback, base/usuario exclusivos y `SELECT DATABASE()=flowerflow_testing`.
- Las 23 migraciones existentes se aplicaron y sembraron desde cero; M8 no necesitó migración ni rollback estructural.
- 104 rutas propias y cuatro tareas programadas; la cuarta es el digest cada minuto con `withoutOverlapping`.
- Pint, Composer validate/platform/audit, JSON, enlaces Markdown, scans redactados, build Vite de 784 módulos y `git diff --check`: verdes.
- `yarn audit` conserva únicamente el advisory bajo conocido de Quill 2.0.3 (`GHSA-v3m3-f69x-jf25`), sin versión corregida; cero moderados, altos o críticos.

## 8. UAT local

Firefox real automatizado con Playwright, datos/correos `example.test`, `MAIL_MAILER=array`, sesiones database y worker local `database --queue=high,default`:

- 1440×900, 1024×768 y 390×844 sin desbordamiento del documento, errores de consola o pérdida de foco por teclado;
- reflow equivalente a zoom 200 % en desktop/tablet y reflow móvil a 390 CSS px;
- orden de menú `Paquetes ciegos → Notificaciones → Cuenta y seguridad`, etiquetas de los cuatro eventos presentes y filtro por tipo;
- “Procesar ahora” exigió contraseña reciente, razón y confirmación; produjo un segundo attempt sin envío SMTP dentro del request;
- seis jobs procesados: cuatro deliveries aceptados por el transporte `array`, un conflicto declarado obsoleto cancelado por revalidación, cero jobs y cero `failed_jobs` al finalizar;
- filtro/detalle de cancelados mostró sólo `judge_conflict_declared_invalid`; no expuso explicación, comentarios ni contenido de evaluación;
- juez denegado con 403 y visitante redirigido al login; XSS permaneció inerte;
- el comando de digest ejecutado antes del cierre devolvió `evaluation_close_digest_window_not_open` y cero deliveries. Los segundos frontera y el catch-up se comprobaron automatizadamente con reloj controlado.

Los primeros intentos del arnés revelaron sesiones `array` no persistentes y una simulación de zoom inadecuada; se corrigió únicamente el entorno/guion temporal. No fue necesario cambiar producto. Los archivos, sesiones y datos sintéticos del UAT se eliminaron; `flowerflow_testing` quedó nuevamente en `migrate:fresh --seed` con 23/23 migraciones.

## 9. Compatibilidad, exclusiones y rollback

No se crearon recordatorios programados 20/22 de agosto ni replay histórico. Tampoco consolidación, promedio, cobertura, empate, ranking, ganador, resultado, cierre administrativo, retención o purga.

Rollback funcional:

```dotenv
FLOWERFLOW_EVALUATION_NOTIFICATIONS_ENABLED=false
FLOWERFLOW_EVALUATION_CLOSE_DIGEST_ENABLED=false
```

No borrar deliveries, attempts, auditoría o eventos. El ledger puede permanecer activo para comunicaciones anteriores.

M8 no añade worker: el proceso Flower Flow existente que atiende `high,exports,default,low` cubre recuperación, exportación y entrega normal. Tampoco añade permiso, ruta, tabla o dependencia. Los deliveries históricos de M7 no se reconstruyen.

## 10. Archivos y trazabilidad

- Configuración/agenda: `.env.example`, `config/flowerflow.php`, `routes/console.php`.
- Dominio: acciones de conflicto/resolución/reapertura; cinco tipos, cuatro eventos y cuatro listeners.
- Comunicación: `EvaluationCommunicationDispatcher`, `EvaluationCloseDigest`, `CommunicationMessageRegistry`, cinco Notifications y diez vistas HTML/texto.
- Operación: comando `QueueEvaluationCloseDigests` y excepción redactada.
- Pruebas: `EvaluationCommunicationTest` y `EvaluationCommunicationConcurrencyTest`.
- Decisión/evidencia: ADR 0012, ExecPlan M8, este informe y documentos canónicos de alcance, arquitectura, datos, seguridad, UX, QA, riesgos, operación, estado, product spec, trazabilidad y handoff.

No se modificaron PDF jurídicos, hashes, aceptaciones, propuestas, snapshots, folios ni archivos privados.
