# ExecPlan: Flower Flow MVP 2026

**Estado:** Proposed — listo para revisión, no autorizado para implementar  
**Creado:** 2026-07-15 America/Hermosillo  
**Owner propuesto:** líder técnico Flower Flow  
**Fecha objetivo:** producción controlada 2026-08-07; cierre 2026-08-15, hora PENDING

## Propósito

Construir un MVP Laravel seguro para que participantes creen y envíen proyectos, revisores determinen elegibilidad y jueces asignados evalúen sin acceso a identidad o comprobantes. Administración debe operar el flujo y declarar ganadores mediante una acción separada; la publicación pública permanece apagada.

Al terminar, un recorrido sintético completo funciona en staging y producción AWS EC2 Ubuntu, con autorización negativa, auditoría, backup restaurado, workers/scheduler observables y rollback ensayado.

## Autoridad

Antes de ejecutar, leer:

- AGENTS.md
- .agent/PLANS.md
- docs/product-spec.md
- docs/01-functional-scope.md
- docs/02-architecture.md
- docs/03-data-model.md
- docs/04-security-privacy.md
- docs/06-roadmap-backlog.md
- docs/07-deployment-aws-ec2.md
- docs/08-testing-qa.md
- docs/10-open-questions.md
- docs/adr

Este plan no sustituye decisiones P0. Si una regla aprobada contradice un ASSUMPTION, actualizar primero producto, ADR, modelo, trazabilidad y este ExecPlan.

## Alcance

### Incluido

- Baseline reproducible PHP/JS/MySQL y aislamiento AWS.
- Branding/limpieza mínima de Materialize.
- Auth, verificación, reset, 2FA privilegiado, RBAC y Policies.
- Convocatoria/categorías, contenido público esencial y legales versionados.
- Perfil, residencia, elegibilidad, borrador, integrantes básicos, anexos, preview y envío.
- Backoffice, asignación, conflicto, rúbrica y evaluación.
- Auditoría/notificaciones/reportes mínimos y decisión de ganador sin publicación.
- QA, accesibilidad crítica, performance, backup/restore, UAT, deploy y estabilización.

### Excluido

- Marketing masivo, galería, API móvil, app móvil, integraciones externas, CMS general y analítica invasiva.
- Publicación pública de ganadores hasta aprobación posterior.
- Upgrade mayor Laravel, microservicios, SPA y Redis obligatorio.
- Cambios o despliegues de Administratec.

## Estado de partida verificable

- Laravel 12 y PHP declarado 8.2+, sin composer.lock/vendor.
- Materialize/Pixinvent 3.0.0 Commercial, variante/licencia del dominio PENDING.
- Node Windows 20.20.0; package manager WSL no normalizado; yarn.lock sin Yarn.
- Seis rutas GET demo, un modelo User, tres migraciones base y dos tests de ejemplo.
- No existe autenticación backend ni módulo de dominio.
- MySQL WSL2 8.0.46 activo sólo en loopback; schema flowerflow vacío; usuario local verificado.
- El secreto local fue entregado fuera del repositorio; nunca se escribe aquí.
- No existe Git en el directorio al corte.
- Target decidido: AWS EC2 Ubuntu compartida con Administratec sólo a nivel de host, con aislamiento completo.

## Decisiones y supuestos

### DECISION

- AWS EC2 Ubuntu es el destino; GoDaddy queda descartado.
- Arquitectura propuesta: monolito modular Laravel/Blade/Materialize.
- MySQL, InnoDB y utf8mb4; persistencia UTC y presentación America/Hermosillo.
- Comprobantes privados separados de anexos; jueces nunca ven residencia/PII.
- Puntuación servidor; ganador separado; sin selección aleatoria; resultados off.

### ASSUMPTION hasta aprobación

- PHP 8.3 y Node 20 se estandarizan.
- Sesión/cache/cola database para MVP; worker persistente.
- Storage privado en EBS cifrado o S3 y base en RDS o EC2 son decisiones de preflight; se prefieren S3/RDS si presupuesto y operación los aprueban.
- Un representante puede gestionar equipo básico; máximo y aceptaciones PENDING.
- Retención propuesta en docs/03-data-model.md.

### PENDING P0

- Recuperar módulos 1-6 del input original.
- Licencia/variante Materialize.
- Apertura/cierre exactos, edad, equipos, cupos y límites de archivos.
- Documentos de residencia, retención y textos legales finales.
- Rúbrica, número de jueces, evaluación ciega, empates/categoría desierta.
- Premio/datos publicables.
- SMTP, CAPTCHA, staging/UAT owner.
- Acceso e inventario EC2, DB productiva, backup y RPO/RTO.

## Seguridad de secretos

- .gitignore debe conservar /.env, /.env.* y excepción /.env.example.
- La configuración local usa DB_CONNECTION mysql, host 127.0.0.1, puerto 3306, base flowerflow y usuario flowerflow_user.
- DB_PASSWORD se introduce únicamente en .env local protegido o secret store del ambiente.
- No copiar el secreto local a .env.example, docs, CI, AWS, logs, comandos, tickets ni capturas.
- Antes de cada commit/release, ejecutar secret scan aprobado y revisar archivos untracked.

## Plan de ejecución

Sólo M0 puede comenzar después de la aprobación inicial. Cada milestone requiere su propia confirmación de alcance y un registro de evidencia.

### Paso 0 — Congelar baseline y decisiones

1. Recuperar la fuente completa y reconciliar requisitos.
2. Obtener aprobación escrita de P0 o recorte.
3. Confirmar licencia y origen del template.
4. Crear/importar repositorio Git sin incluir secretos ni artefactos.
5. Capturar hashes/lista de la baseline y preservar cambios de usuario.

Resultado: baseline revisable y contrato de producto aprobado.

### Paso 1 — Hacer instalable el starter

1. Estandarizar PHP 8.3, Composer compatible, Node 20 y un package manager.
2. Instalar dependencias sin upgrades mayores; revisar resolución antes de aceptar.
3. Fijar composer.lock y lock JS elegido.
4. Crear .env local desde ejemplo, generar APP_KEY y configurar MySQL sandbox.
5. Resolver assets/referencias rotos mínimos para obtener build.
6. Ejecutar migraciones Laravel base sólo en schema local vacío.
7. Levantar web local y capturar baseline /, /up y rutas.

Resultado: fresh install reproducible con test/build verdes.

### Paso 2 — Preparar infraestructura aislada

1. Ejecutar preflight de EC2 por SSH/SSM sin cambios.
2. Elegir Apache existente o estrategia aprobada; no reemplazar stack de Administratec.
3. Definir /var/www/flowerflow, usuario/grupo, vhost, PHP pool si aplica, logs y DNS.
4. Decidir MySQL local/RDS, storage EBS/S3, secretos, worker, scheduler y monitoreo.
5. Provisionar staging separado y probar deploy/rollback/backup/restore.

Resultado: staging aislado, sin impacto a Administratec.

### Paso 3 — M1 shell Flower Flow

1. Crear tokens/overrides propios.
2. Adaptar layouts front/vertical, menús y español.
3. Quitar rutas/demo/customizer/metadatos/enlaces rotos tras verificar imports.
4. Crear estados de error y noindex por ambiente.
5. Validar responsive, teclado, contraste y build.

Resultado: shell estable sin PII ni branding del proveedor.

### Paso 4 — M2 identidad y autorización

1. Crear ADR final del paquete auth/RBAC.
2. Implementar registro/login/logout/reset/verificación e invitaciones.
3. Implementar 2FA privilegiado, suspensión y revocación.
4. Sembrar permisos/roles sin usuarios reales.
5. Implementar Policies/scopes y tests negativos.

Resultado: actores autenticados y aislados.

### Paso 5 — M3 convocatoria y legales

1. Migrar competitions, categories, legal_documents/acceptances.
2. Crear estado/calendario con zona explícita.
3. Publicar contenido aprobado y contacto/privacidad mínimo.
4. Capturar aceptación de versión/hash.

Resultado: convocatoria exacta y condiciones trazables.

### Paso 6 — M4 elegibilidad privada

1. Implementar profile, residency_documents y eligibility_reviews.
2. Implementar pipeline storage privado, metadata, cuotas y descarga Policy.
3. Implementar corrección/decisión/auditoría.
4. Probar que juez/tercero nunca obtiene documento.

Resultado: elegibilidad operable sin exposición.

### Paso 7 — M5 proyecto y envío

1. Migrar submissions, members, versions, files e histories.
2. Implementar wizard/autosave con locking optimista.
3. Implementar anexos, preview y resumen accesible.
4. Implementar SubmitSubmission con transacción, idempotencia, snapshot/hash/folio y evento.
5. Probar bordes de cierre y concurrencia.

Resultado: envío único, versionado y recuperable.

### Paso 8 — M6 backoffice

1. Crear queries server-side autorizadas y DataTables.
2. Crear revisión, corrección, notas, excepciones y auditoría.
3. Crear export CSV mínimo con allowlist/expiración.
4. Validar índices, EXPLAIN, query count y masking.

Resultado: operación administrativa eficiente y limitada.

### Paso 9 — M7 evaluación ciega

1. Migrar profiles/assignments/conflicts/rubrics/criteria/evaluations/scores.
2. Implementar proyección ciega y Policy de assignment.
3. Implementar conflicto, borrador, cálculo servidor, submit y reopen.
4. Probar ausencia de PII, estados y límites.

Resultado: juez evalúa sólo lo asignado.

### Paso 10 — M8 cierre operativo

1. Implementar winner_decisions con declaration/publish separados y flag off.
2. Implementar notificaciones críticas, delivery log, idempotencia y retries.
3. Implementar reportes/audit view mínimos.
4. Ensayar failed jobs y comunicación manual de contingencia.

Resultado: cierre trazable sin publicación accidental.

### Paso 11 — M9 UAT y producción

1. Ejecutar suite completa, browser, WCAG, seguridad y carga.
2. Corregir P0/P1; recortar extras antes de aceptar deuda crítica.
3. Ejecutar migración/backup/restore/rollback en staging.
4. Obtener UAT, backup y aprobación expresa.
5. Desplegar release, migrar, optimizar, reiniciar worker, smoke y monitorear.

Resultado: release productiva verificable y reversible.

### Paso 12 — M10 estabilización

1. Freeze funcional y triage diario.
2. Monitorear health, 5xx, queue lag, mail, MySQL, CPU/RAM/disco y backups.
3. Ensayar deadline y soporte.
4. Documentar cierre e incidentes.

Resultado: convocatoria cerrada sin pérdida ni acceso indebido.

## Validación

Los comandos reales se fijan al completar M0. Base:

~~~text
composer validate --no-check-publish
composer install
php artisan about
php artisan route:list
php artisan migrate:status
php artisan test
./vendor/bin/pint --test
composer audit
npm ci
npm run build
npm audit --omit=dev
~~~

Validación adicional:

- tests de matrices RBAC/IDOR, estados, timezone, idempotencia, archivos y mail;
- navegador por seis actores y tres viewports;
- teclado/contraste/reflow;
- EXPLAIN/query count y carga staging;
- TLS/headers/health externos;
- worker/scheduler/failed jobs;
- restore desde backup y rollback de release.

La suite autoritativa es docs/08-testing-qa.md. Una herramienta ausente se instala sólo con ADR/aprobación, no se marca como aprobada por omisión.

## Despliegue

Seguir docs/07-deployment-aws-ec2.md. Reglas no negociables:

- document root a public;
- APP_DEBUG false;
- permisos mínimos, nunca 777;
- secrets fuera del release;
- backup antes de migrar;
- releases versionados y rollback;
- optimize y reinicio controlado de workers;
- smoke /up, público, auth y actor administrativo;
- resultados off y staging noindex;
- no modificar vhost/procesos de Administratec salvo cambio separado aprobado.

## Rollback

- Código: apuntar current al release anterior y recargar servicios.
- Config: restaurar secret/config anterior cifrado y auditado.
- DB: preferir migraciones compatibles hacia adelante; restore sólo con decisión de incidente y pérdida cuantificada.
- Worker: stop, rollback release, restart y verificar jobs idempotentes.
- Archivos: no borrar uploads durante rollback; preservar referencia y reconciliar.
- DNS/TLS: no cambiar en rollback ordinario.

Cada milestone especifica su rollback local en docs/06-roadmap-backlog.md.

## Progreso vivo

- [x] 2026-07-15 — Se inspeccionó el starter, runtimes, MySQL WSL2 y precedente Administratec.
- [x] 2026-07-15 — Se verificó conexión read-only/metadata a MySQL; schema vacío; sin migraciones.
- [x] 2026-07-15 — Se produjo la documentación de fase 0 y ADR propuestos.
- [x] 2026-07-15 — Se corrigió .gitignore para excluir .env y variantes.
- [ ] PENDING aprobación — Recuperar fuente truncada y cerrar decisiones P0.
- [ ] PENDING aprobación — Ejecutar Paso 0/M0.

## Hallazgos inesperados

- El archivo de requisitos comienza en una subsección de comunicaciones y salta a módulo 7.
- No existe repositorio Git ni locks/backend instalado.
- Node existe como runtime Windows, pero no como comando WSL normalizado.
- La distro WSL se llama Ubuntu; flowerflow es el ambiente/schema, no el nombre de distro observado.
- El usuario MySQL sandbox tiene WITH GRANT OPTION; no reutilizar en producción.
- Vistas demo referencian Jetstream y assets ausentes.
- Parte de la documentación de Administratec no refleja su checkout actual; la EC2 debe inspeccionarse directamente.

## Registro de decisiones

| Fecha | Decisión | Razón | Estado |
|---|---|---|---|
| 2026-07-15 | AWS EC2 Ubuntu reemplaza GoDaddy | precisión explícita del usuario | Accepted |
| 2026-07-15 | secreto MySQL no se repite en repo | política de cero secretos | Accepted |
| 2026-07-15 | modular monolith | plazo y starter existente | Proposed |
| 2026-07-15 | database queue/cache/session MVP | menor operación, sin asumir Redis | Proposed |
| 2026-07-15 | resultado público apagado | privacidad y aprobación pendiente | Accepted por alcance |

## Criterio de finalización del ExecPlan

- MVP observable y matrices de seguridad pasan.
- Traceability enlaza cada requisito crítico a prueba/evidencia.
- Build/tests/auditorías en verde y cero P0/P1 abierto.
- UAT, licencia, legal, backup/restore y producción aprobados.
- Release EC2 aislada, monitoreada y reversible.
- Documentación y registro vivo reflejan el sistema real.

Este ExecPlan está listo para revisión; ejecutarlo requiere aprobación explícita y cierre de P0.
