# ADR 0009 — Outbox transaccional y bitácora de comunicaciones

Estado: aceptado · 2026-08-23

## Contexto

Flower Flow enviaba sus correos transaccionales mediante clases cifradas en cola, pero el resultado final del worker no quedaba correlacionado en una bitácora de negocio común. `jobs` y `failed_jobs` son estructuras técnicas, pueden contener payloads o excepciones y no son una superficie segura para el panel. Sólo los recordatorios de borradores tenían estados propios.

SMTP tampoco acredita entrega al buzón: una ejecución sin excepción sólo permite afirmar que el transporte aceptó el mensaje. Además, reenviar tras un timeout puede duplicar el correo si el servidor lo aceptó antes de perderse la confirmación.

## Decisión

- Persistir un delivery y sus intentos en `communication_deliveries` y `communication_delivery_attempts`.
- Centralizar la ejecución en `DeliverCommunication`, job cifrado y post-commit cuyo único dato de negocio es el ID interno del delivery.
- Reconstruir cada mensaje mediante un registro allowlist que integra exclusivamente las nueve familias ya existentes.
- Cifrar dirección, contexto y razón administrativa; conservar máscara y fingerprint HMAC para observabilidad e idempotencia sin mostrar el destinatario completo.
- Usar estados `queued|processing|sent|failed|unknown|cancelled`. La interfaz presenta `sent` como `Aceptado por el servidor de correo`.
- Revalidar destinatario, token, vigencia y estado del evento dentro del worker. Un evento inválido se cancela sin regenerar credenciales.
- Permitir recuperación individual sólo al rol exacto `admin`, con permiso, contraseña reciente, CSRF, throttle, versión optimista, confirmación y razón cifrada. `unknown` exige reconocer el riesgo de duplicado.
- Despachar recuperaciones a `high`; el request nunca ejecuta SMTP ni edita `jobs`.
- Purgar dirección y contexto al quedar `sent|cancelled`; conservarlos hasta 90 días en `failed|unknown` y no borrar automáticamente la metadata mínima.
- Mantener `FLOWERFLOW_COMMUNICATION_LEDGER_ENABLED=false` como rollback funcional. Con el flag apagado, el dispatcher conserva temporalmente la ruta legacy.
- Autorizar backfill únicamente desde `submission_reminders`, dry-run por defecto. No inferir historial desde logs o colas técnicas.

## Consecuencias

- El worker existente debe escuchar `high,exports,default,low`; no se añade otro proceso.
- El módulo mejora observabilidad y recuperación, pero no ofrece confirmación de entrega, apertura o rebote. Eso requeriría webhooks y otro ADR.
- El resultado `unknown` necesita decisión humana porque un reintento puede duplicar el correo.
- Las razones administrativas existen para evidencia, pero no se muestran en el panel ni se registran en logs/auditoría.
- La migración es aditiva y `down()` falla si existe evidencia. El rollback normal apaga el flag y conserva la bitácora.

## Alternativas rechazadas

- Exponer `failed_jobs`: mezcla detalle técnico, payloads y excepciones sin correlación segura.
- Enviar dentro del request: aumenta latencia, acopla SMTP con transacciones y dificulta recuperación.
- Reintento masivo: incrementa el riesgo de duplicados y evita revisar vigencia por evento.
- Marcar como `Entregado`: no existe evidencia suficiente sin proveedor/webhook.
- Reconstruir historial completo desde logs: la evidencia no es uniforme ni confiable y puede incorporar PII.

## Validación

Migración forward/rollback/forward bajo guard de `flowerflow_testing`; pruebas de las nueve familias, cifrado, idempotencia, redacción, GET sin mutación, permisos, concurrencia optimista, cancelación, fallo, desconocido, backfill y sincronización de recordatorios; suite completa, formato, build, auditorías y UAT Firefox local con datos sintéticos.
