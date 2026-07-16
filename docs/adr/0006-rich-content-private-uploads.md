# ADR 0006 — Contenido enriquecido y uploads privados

Estado: aceptado para Fase 01 · 2026-07-15

## Decisión

Persistir Delta JSON, HTML sanitizado y texto plano. Sanitizar al guardar y al renderizar con Symfony HTML Sanitizer 7.4.14. Guardar documentos/imágenes en el disk local privado, con nombre ULID, metadatos, SHA-256, actor y cuota acumulada; entregar sólo por controller+Policy. Los enlaces externos usan allowlist HTTPS y nunca se solicitan desde el servidor.

## Controles

Toolbar Quill mínima; sin Base64/hotlinks; allowlist de extensiones; MIME/firma; rechazo de macro OOXML, rutas internas y expansión ZIP peligrosa; máximo 10 MiB transaccional. `serve=false` para el disk privado y ninguna dependencia de `storage:link`.

## Consecuencias

ClamAV y almacenamiento S3/EBS siguen pendientes de capacidad/arquitectura. El owner aceptó temporalmente el 2026-07-15 abrir la recepción sin motor antimalware, conservando los controles actuales y la posibilidad operativa de cerrarla; la defensa heurística adicional para formatos Office continúa como remediación prioritaria. Tests cubren XSS, cuota, hosts, privacidad e idempotencia.
