# Seguridad y privacidad desde el diseño

## Controles Fase 01 ejecutados

- Fortify: hashing Laravel, rate limit de login/2FA, reset, verificación, password confirmation y TOTP.
- RBAC Spatie más Policy propietario/admin; `/panel` sólo admin y descargas con Policy.
- CSP/headers: `nosniff`, `DENY`, referrer/permissions policy; `style-src 'unsafe-inline'` es excepción temporal por Bootstrap/Quill y debe revisarse.
- HTML: toolbar mínima, Delta+HTML+texto, sanitizer servidor al guardar y al renderizar; se eliminan scripts, handlers, Base64 y esquemas activos.
- Archivos: allowlist, límite individual/acumulado, firma PDF/imagen/Office, detección de macros OOXML, path traversal y ZIP bomb; nombre ULID; hash; disk local con `serve=false`.
- Enlaces: HTTPS y hosts exactos; sin DNS, HTTP, preview o descarga server-side, reduciendo SSRF.
- Consentimientos: WhatsApp y avisos opcionales son reversibles y separados; jurídicos versionados por propósito.
- Logs/correo: no adjuntar proyecto/PII; secreto DB sólo `.env`; mail post-commit en cola.

Pendiente operativo: antivirus real, revisión de formatos Office binarios, CSP sin excepción si es viable, retención/borrado, SMTP, revisión legal v1.1, secret scan y prueba de carga/concurrencia MySQL. El owner aceptó temporalmente el 2026-07-15 abrir la recepción sin motor antimalware; esto no elimina el riesgo ni autoriza retirar allowlist, firma, cuota, almacenamiento privado, monitoreo o capacidad de cierre inmediato.

**Estado:** controles propuestos; requieren aprobación de producto/legal e implementación posterior.  
**Objetivo:** WCAG y seguridad son criterios de aceptación, no declaraciones de cumplimiento.

## Activos y fronteras de confianza

### Activos críticos

1. Credenciales, sesiones, tokens de verificación, recuperación e invitación.
2. Identidad, contacto, edad/elegibilidad y comprobantes de residencia.
3. Propuestas y anexos no publicados.
4. Asignaciones, conflictos, rúbricas, puntuaciones y comentarios.
5. Decisiones de ganadores y momento de publicación.
6. Aceptaciones legales versionadas, historial y bitácora.
7. Secretos de aplicación, correo, AWS, base y backups.

### Fronteras

- Navegador no confiable frente a Laravel.
- Rol autenticado frente a Policy y consulta de cada recurso.
- Aplicación frente a MySQL, storage, cola y SMTP.
- Jueces frente a identidad/comprobantes.
- Flower Flow frente a Administratec en la misma EC2.
- Operador/CI frente a secretos y producción.
- Archivo subido frente al almacenamiento y consumidor.

## Modelo de amenazas

| ID | Amenaza | Escenario | Impacto | Controles preventivos | Detección/respuesta |
|---|---|---|---|---|---|
| T01 | Toma de cuenta | credential stuffing o reset abusivo | crítico | rate limit por IP/cuenta, mensajes neutros, hash robusto, 2FA privilegiado | alertas de intentos, revocar sesiones |
| T02 | IDOR/BOLA | cambiar ULID para ver proyecto/archivo ajeno | crítico | Policies, route model binding acotado, query scopes, storage privado | test negativo y audit log |
| T03 | Fuga juez/PII | endpoint o export expone identidad/residencia | crítico | DTO/vistas ciegas, permisos separados, allowlist de columnas | canary tests y auditoría de descarga |
| T04 | Archivo hostil | ejecutable, polyglot, ZIP bomb, MIME falso | alto | allowlist, límites, magic bytes, nombre aleatorio, no ejecución, escaneo PENDING | cuarentena, log, eliminación |
| T05 | Doble envío | reintento crea folios/versiones duplicados | alto | idempotency key, unique constraint, transacción y lock | métrica de conflictos/reintentos |
| T06 | Manipular score | total enviado desde browser o criterio alterado | alto | cálculo servidor, rúbrica versionada e inmutable | recalcular y comparar, audit |
| T07 | Conflicto ignorado | juez evalúa tras declarar conflicto | alto | estado bloqueante y Policy | test de transición y alerta |
| T08 | Deadline evadido | reloj cliente o endpoint permite envío tardío | alto | hora servidor, zona explícita, excepción con permiso/razón | reporte de excepciones |
| T09 | Publicación prematura | resultado visible antes de aprobación | crítico | feature flag off, permiso separado, doble confirmación | monitor público y bitácora |
| T10 | Escalada de privilegios | admin operativo se asigna roles | crítico | roles.manage sólo super_admin, confirmación de contraseña, 2FA | alerta de cambios RBAC |
| T11 | Injection/XSS/CSRF | entrada o contenido rico malicioso | alto | Form Requests, bindings, escape Blade, sanitización, CSRF, CSP | logs redactados y tests |
| T12 | Exposición de secretos | clave en repo, log, HTML o backup | crítico | .gitignore, secret store/.env, no debug, redacción | secret scanning y rotación |
| T13 | Colisión EC2 | worker/vhost/cache de Administratec toca Flower Flow | alto | aislamiento de rutas, usuarios, cookies, prefijos y servicios | health/logs por app |
| T14 | Pérdida/ransomware | volumen o MySQL corrupto | crítico | backup cifrado externo, snapshots, least privilege | restore drill y alertas |
| T15 | Insider/export | usuario autorizado extrae más datos de lo necesario | alto | columnas por permiso, export async y expiración | quién/cuándo/filtros, revisión |
| T16 | Supply chain | paquete vulnerable o demo abandonado | alto | locks, auditorías, mínimo de paquetes, revisión de licencias | CVE cadence y rollback |
| T17 | DoS cierre | carga o uploads agotan CPU/disco | alto | rate limits, cuotas, tamaños, capacidad y monitor | alarmas, modo degradado |
| T18 | Repudio | actor niega cambio crítico | alto | actor, fecha, entidad, before/after redactado, request ID | export de auditoría inmutable |

## RBAC de mínimo privilegio

Leyenda: O propio; A asignado; T todos con permiso; — denegado.

| Permiso | super_admin | call_admin | eligibility_reviewer | judge | privacy_support | auditor | participant |
|---|---:|---:|---:|---:|---:|---:|---:|
| dashboard.view | T | T | T | A | T | T | O |
| calls.view | T | T | T | A | — | T | publicadas |
| calls.manage | T | T | — | — | — | — | — |
| categories.manage | T | T | — | — | — | — | — |
| participants.view | T | limitado | elegibilidad | — | solicitud | redactado | O |
| residency.review | T | PENDING | T | — | — | — | O upload |
| submissions.view | T | T | asignados revisión | A ciego | solicitud | redactado | O |
| submissions.review | T | T | T | — | — | — | — |
| submissions.reopen | T | T con razón | — | — | — | — | — |
| submissions.export | T | T limitado | limitado | — | — | lectura redactada | O export privacidad |
| judges.manage | T | T | — | — | — | — | — |
| assignments.manage | T | T | — | — | — | lectura | — |
| evaluations.create | — | — | — | A | — | — | — |
| evaluations.submit | — | — | — | A | — | — | — |
| evaluations.view_all | T | T | — | — | — | redactado | — |
| evaluations.reopen | T | T con razón | — | — | — | — | — |
| winners.declare | T | T con doble confirmación | — | — | — | lectura | — |
| winners.publish | T | rol aprobado PENDING | — | — | — | lectura | — |
| content.manage | T | T | — | — | — | lectura | — |
| legal.manage | T | T aprobado | — | — | — | lectura | — |
| communications.send | T | T limitado | plantilla de corrección | — | privacidad | lectura metadata | — |
| privacy.manage | T | — | — | — | T | lectura redactada | O solicitud |
| reports.view/export | T | T | propio alcance | propio | propio alcance | T redactado | O |
| audit.view | T | T limitado | — | — | propio alcance | T | — |
| users.manage | T | limitado convocatoria | — | — | — | — | O perfil |
| roles.manage | T | — | — | — | — | — | — |
| settings.manage | T | limitado | — | — | — | — | — |

La matriz no sustituye Policies. Super admin puede usar un bypass controlado sólo si se registra y se prueban las Policies sin depender de ese bypass.

## Capas de autorización obligatorias

1. **Ruta:** auth, verified, 2FA/confirmación de contraseña y permiso grueso.
2. **Form Request/controller:** authorize sobre la acción y el recurso.
3. **Policy:** ownership, asignación, estado, fecha, conflicto y visibilidad.
4. **Consulta:** filtrar antes de paginar; nunca cargar todos y ocultar después.
5. **Serialización/vista:** allowlist de campos por actor.
6. **Archivo/export:** Policy nueva en cada descarga; no confiar en una URL previa.
7. **Transición:** servicio de dominio revalida precondiciones dentro de transacción.

### Pruebas mínimas por recurso

- Anónimo obtiene 401/redirect, no contenido.
- Rol sin permiso obtiene 403.
- Participante A no ve ni modifica recurso de B.
- Juez A no ve proyecto no asignado ni evaluación de B.
- Juez con conflicto no lee anexos evaluables ni escribe scores.
- Revisor puede ver residencia pero no ranking global.
- URL de archivo no funciona sin autorización actual.
- Revocar/suspender usuario invalida sesiones.

## Autenticación y sesiones

- Verificación de correo antes del envío final.
- Contraseñas nuevas: mínimo 8 caracteres, mayúscula, minúscula, número, símbolo y confirmación; checklist cliente como ayuda, regla Laravel como autoridad.
- Rate limit separado para login, registro, reset, verificación, contacto y uploads.
- Respuestas de reset/verificación no confirman si el email existe.
- 2FA obligatorio para super_admin, call_admin, reviewer, judge, privacy_support y auditor antes de producción.
- Confirmación de contraseña para roles, winner declaration/publication, reapertura, exportación masiva y cambios de email/2FA.
- Cookies Secure, HttpOnly, SameSite=Lax o Strict según flujo; nombre y dominio propios.
- Rotar ID de sesión al autenticar/elevar; revocar sesiones al suspender o cambiar rol crítico.
- Invitations y reset tokens se almacenan hasheados, con expiración y uso único.
- No crear un superadmin con contraseña hardcodeada; usar comando interactivo seguro en milestone aprobado.

## Aplicación y cabeceras

- CSRF en toda mutación web; GET nunca cambia estado.
- Blade escapado por defecto; contenido enriquecido deshabilitado en MVP salvo aprobación y sanitización.
- Eloquent/Query Builder con bindings; validación server-side y límites de longitud.
- CSP inicialmente Report-Only tras inventariar scripts inline de Materialize; luego enforcement con nonces/hashes.
- HSTS sólo después de confirmar HTTPS estable en todos los subdominios.
- X-Content-Type-Options nosniff, Referrer-Policy strict-origin-when-cross-origin, Permissions-Policy mínima y frame-ancestors.
- APP_DEBUG=false en staging/producción; páginas 404/419/429/500 sin stack ni secretos.
- ServerSignature/ServerTokens minimizados en Apache.

## Pipeline seguro de archivos

~~~mermaid
flowchart LR
    Upload[Upload autenticado] --> Validate[Cuota, tamaño, extensión, MIME y magic bytes]
    Validate -->|rechazo| Reject[422 genérico + audit]
    Validate --> Rename[ULID aleatorio; nombre original sanitizado como metadata]
    Rename --> Quarantine[Storage privado/quarantine]
    Quarantine --> Scan[Escaneo antivirus PENDING]
    Scan -->|limpio| Private[Storage privado clasificado]
    Scan -->|malicioso| Delete[Eliminar + alerta]
    Private --> Download[Controller + Policy + audit + headers]
~~~

### Política propuesta

- Tipos exactos y tamaños quedan PENDING; default conservador: PDF, imágenes raster y formatos de oficina indispensables.
- Prohibir ejecutables, scripts, HTML activo, SVG de usuario y archivos cifrados/ZIP salvo caso aprobado.
- Cuota por archivo, proyecto y usuario; contabilizar antes de escribir.
- Separar discos/prefijos de residency y judge-visible.
- No usar storage:link para comprobantes ni anexos privados.
- Content-Disposition attachment con filename saneado; Content-Type detectado del servidor.
- Un token temporal nunca reemplaza la Policy; si se usa URL firmada, expiración corta y log.
- ClamAV es control adicional, no sustituto de allowlist/aislamiento.

## Auditoría y redacción

Eventos mínimos:

- login privilegiado, fallo/rate limit, 2FA y revocación;
- creación/cambio de rol y suspensión;
- envío, corrección, elegibilidad y excepción de deadline;
- upload, scan, descarga y eliminación sensible;
- asignación, conflicto, evaluación, reapertura;
- declaración/publicación/revocación de ganador;
- exportación y solicitud de privacidad;
- cambios de calendario, legales, rúbrica y settings.

Campos: occurred_at UTC, actor, action, entity type/id, request/correlation ID, resultado e IP/user agent minimizados. before/after usa allowlist redactada. Nunca registrar password, tokens, cookies, texto completo de comprobantes, binarios, payload de jobs ni secretos.

## Privacidad

### Principios

- Recolectar sólo campos necesarios y justificar cada uno.
- Informar propósito y retención antes del upload/aceptación.
- Versionar bases, aviso y consentimiento; no editar una versión aceptada.
- Evitar datos reales en desarrollo, staging, capturas y soporte.
- Separar soporte ARCO del análisis automático: el sistema organiza evidencia, no decide obligaciones legales.
- No exponer domicilio, teléfono, email, comprobantes ni anexos no autorizados en resultados.

### Flujo administrativo ARCO

~~~mermaid
stateDiagram-v2
    [*] --> received
    received --> identity_verification
    identity_verification --> in_review
    identity_verification --> rejected: identidad no acreditada
    in_review --> awaiting_information
    awaiting_information --> in_review
    in_review --> approved
    in_review --> rejected: razón legal aprobada
    approved --> executed
    rejected --> closed
    executed --> closed
~~~

Cada paso conserva fecha, actor, razón y evidencia mínima. Plazos, identidad aceptable y excepciones son PENDING legal.

## Comunicaciones

- Remitente/reply-to funcional propuesto: convocatoria@flowerflow.com.mx; privacidad: privacidad@flowerflow.com.mx.
- SMTP, credenciales y reputación son PENDING; no inventarlos.
- Email transaccional en HTML responsive y texto plano, sin documentos ni PII sensible.
- Verificación, reset y acuse usan plantillas en español de México con marcas Flower Flow/Florece Hermosillo, enlaces absolutos y alternativa de texto.
- El registro crea usuario, perfil mínimo, rol y evidencias legales en una sola transacción; si falta un documento activo o falla una validación no queda cuenta parcial.
- El consentimiento para futuras actividades se captura desde registro, aparece marcado por defecto por decisión de producto, se guarda como propósito independiente y puede revertirse en perfil; no habilita envíos masivos sin flujo aprobado.
- Jobs de correo cifrados y post-commit en `database/default`; timeout SMTP 10 segundos, timeout de job 30 segundos y backoff 60/300/900 con cuatro intentos totales.
- Si falla la creación del job, registro/propuesta permanecen confirmados y la interfaz ofrece aviso/reenvío sin error 500. Si falla SMTP en el worker, se reintenta y termina observable en `failed_jobs`.
- Delivery log registra tipo, destinatario interno, estado, intentos y error clasificado, no cuerpo completo.
- Event ID único por usuario/tipo evita duplicados; backoff y failed_jobs para reintentos.
- Marketing masivo y listas sin consentimiento quedan fuera.
- Antes de go-live: SPF, DKIM, DMARC, bounce handling y pruebas de entregabilidad.

## Infraestructura y secretos

- Security Group: 443 público; 80 sólo redirección; SSH/administración restringidos o SSM.
- UFW/Apache como segunda capa sin abrir MySQL públicamente.
- IAM role de mínimo privilegio; no guardar access keys permanentes en EC2.
- Secretos en mecanismo aprobado; el archivo de entorno tiene permisos 600 y queda fuera de releases/backups públicos.
- Flower Flow usa DB, usuario, cookie, cache prefix, worker, logs y backup distintos de Administratec.
- EBS y backup cifrados; restore probado. Snapshot no reemplaza dump consistente de MySQL.
- Parches de Ubuntu/PHP/MySQL con ventana, backup y rollback.

## Verificaciones antes de producción

- Matriz RBAC aprobada y tests negativos verdes.
- 2FA y revocación de sesiones verificadas.
- Escaneo de secretos y dependencia ejecutable con locks.
- Headers/CSP/TLS revisados desde Internet.
- Upload/download probados con MIME falso, tamaño, archivo hostil y acceso cruzado.
- Backup restaurado en entorno aislado.
- Logs sin PII/secreto y rotación/alertas activas.
- Resultados públicos apagados.
- Revisión legal de textos, retención, premio y consentimientos.

## Adenda de seguridad y privacidad — Fase 02A, 2026-07-16

- Los comprobantes usan el disco `residency`, separado de anexos y de adjuntos de aclaración; todos están fuera del web root.
- Se validan extensión, MIME detectado, firma PDF/JPEG/PNG/WebP, nombre hostil, tamaño, cuota, PDF cifrado y elementos PDF activos.
- Máximo tres archivos y 10 MiB acumulados por persona/solicitud. No se automatiza rechazo por antigüedad.
- Nombres internos ULID, SHA-256 y descarga exclusivamente por controlador/Policy.
- La carga, consulta privilegiada, descarga y resolución de residencia generan auditoría sin cuerpo, documento, IP ni agente en claro.
- Reviewer necesita permisos de vista y descarga; participante sólo accede a su propuesta; usuario sin rol y futuro juez quedan denegados.
- Notas internas se cifran con el cast de Laravel y nunca se incluyen en vista/correo participante.
- Los correos sólo contienen folio, título y llamada a entrar al sistema; no adjuntan ni describen documentos.
- Una falla de enqueue/SMTP no revierte aclaraciones ni decisiones y produce aviso seguro en interfaz.
- No existe eliminación automática: el reporte de retención preserva archivos hasta integrar resultados/ganadores.

Riesgo residual: no hay antimalware aprobado. Los controles de firma/contenido reducen superficie, pero producción debe mantener uploads apagados hasta resolver capacidad, monitoreo y escaneo conforme al riesgo R24.
