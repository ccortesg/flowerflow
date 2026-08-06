# Handoff técnico integral de Flower Flow

> Última revisión: 2026-08-04 15:03 MST (`America/Hermosillo`).
> Alcance: auditoría de código propio, configuración, locks, rutas, migraciones, pruebas, documentación, historial Git y contexto confirmado por el usuario. No se modificó código funcional, configuración operativa, base de datos ni producción.

> **Adenda local/test — 2026-08-05 20:22 MST:** el propietario creó y autorizó `flowerflow_testing`. Se verificó conexión y grant sin mostrar secretos; `phpunit.xml` fuerza MySQL/loopback/base de pruebas y `Tests\TestCase` aplica un guard antes de `RefreshDatabase`. Pasaron 78 pruebas/702 aserciones, Pint y build Vite (874 entradas de manifest). La huella de la base principal permaneció idéntica antes y después. Composer conserva 5 advisories (1 alto, 4 medios) y Yarn 7 bajos, 37 moderados, 38 altos y 4 críticos; no se tocaron lockfiles.

## 1. Identificación, propósito y alcance

- Proyecto: **Flower Flow — Hermosillo Florece 2026**.
- Repositorio de origen: `/mnt/c/wamp64/www/flowerflow`.
- Destino WSL autorizado: `/home/ccortesg/workspace/flowerflow`.
- Rama auditada: `codex/phase-02-admissibility-review`.
- HEAD auditado: `d293fee63843beb64899498ca169e114e7695d6e` (`Falta definiciones sobre jueces`).
- [EVIDENCIA EN CÓDIGO] Aplicación Laravel para registro de participantes, recepción de propuestas y revisión administrativa. Jueces, evaluación y resultados no están implementados.

La documentación operativa y la interfaz se mantienen en español de México; el código se escribe en inglés. Los instantes se persisten en UTC y las reglas/fechas de negocio se presentan en `America/Hermosillo`.

## 2. Dictamen ejecutivo

- [EVIDENCIA EN CÓDIGO] Fase 01 implementada: sitio público, autenticación, perfil, equipos, propuestas versionadas, archivos privados, aceptaciones legales y panel mínimo.
- [EVIDENCIA EN CÓDIGO] Fase 02A implementada detrás de `FLOWERFLOW_ADMISSIBILITY_REVIEW_ENABLED=false`: expedientes, aclaraciones append-only, residencia privada, resolución, auditoría y correo en cola.
- [EVIDENCIA EN HISTORIAL GIT] Fase 02B sólo está definida documentalmente en HEAD. No existen migraciones, rutas, permisos, modelos ni vistas de jueces/evaluaciones.
- [VERIFICADO EL 2026-08-05] La suite actual registra 78 pruebas y 702 aserciones sobre `flowerflow_testing`; incluye seis pruebas del guard fail-closed. La evidencia histórica de QA por rol y viewport no se repitió.
- [VALIDACIÓN FALLIDA] Las auditorías actuales de Composer y Yarn reportan vulnerabilidades; no se actualizaron dependencias por restricción expresa.
- [PENDIENTE DE VALIDACIÓN] La migración de Fase 02A está pendiente en la base local principal. No debe habilitarse esa fase en dicha base.
- [PENDIENTE DE VALIDACIÓN] El estado actual de EC2, Apache, Supervisor, MySQL, SMTP y del despliegue productivo no se inspeccionó.

## 3. Arquitectura real y stack confirmado

Arquitectura monolítica server-rendered: Laravel 12, Blade, Eloquent, Form Requests, Policies, Actions/Services, Jobs y comandos Artisan. La UI reutiliza Materialize/Pixinvent 3.0.0 y se compila con Vite. No hay SPA, API pública, webhooks, microservicios ni Redis.

| Componente | Versión observada | Evidencia |
|---|---:|---|
| PHP CLI | 8.3.31 | [VERIFICADO EN ESTA EJECUCIÓN] |
| Composer | 2.10.2 | [VERIFICADO EN ESTA EJECUCIÓN] |
| Laravel | 12.64.0 | [VERIFICADO EN ESTA EJECUCIÓN] |
| Node | 22.23.1 | [VERIFICADO EN ESTA EJECUCIÓN] |
| npm | 10.9.8 | [VERIFICADO EN ESTA EJECUCIÓN] |
| Corepack | 0.34.6 | [VERIFICADO EN ESTA EJECUCIÓN] |
| Yarn Classic | 1.22.22 | [VERIFICADO EN ESTA EJECUCIÓN] |
| Vite | 6.3.5 | [VERIFICADO EN ESTA EJECUCIÓN] |
| MySQL client | 8.0.46 | [VERIFICADO EN ESTA EJECUCIÓN] |
| Materialize/Pixinvent | 3.0.0 | [EVIDENCIA EN MANIFIESTOS] |

Docker no está disponible en esta distribución WSL; los archivos Sail/Docker existentes no se ejecutaron.

## 4. Mapa operativo del repositorio

| Ruta | Responsabilidad |
|---|---|
| `app/Actions` | Casos de uso y transiciones de dominio. |
| `app/Http` | Controladores, middleware y Form Requests. |
| `app/Models`, `app/Enums`, `app/Policies` | Modelo, estados y autorización. |
| `app/Jobs`, `app/Mail`, `app/Notifications` | Trabajo asíncrono y correo transaccional. |
| `config/flowerflow.php` | Reglas técnicas, horario, límites y feature flags. |
| `database/migrations` | Siete migraciones, incluida Fase 02A. |
| `database/seeders` | Roles, permisos, categorías y documentos legales. |
| `resources/views`, `resources/assets` | Blade y fuentes del frontend. |
| `routes/web.php` | Rutas web; 59 rutas observadas. |
| `storage/app/private` | Archivos privados; nunca publicar con `storage:link`. |
| `formatos/`, `imagen/` | Originales jurídicos y gráficos autorizados; no sobrescribir. |
| `_referencia/` | Material local ignorado, sólo lectura y fuera del release. |
| `.agent/execplans` | Planes vivos e históricos. |
| `docs` | Especificación, arquitectura, operación, riesgos y evidencia. |

Inventario: 715 archivos rastreados, 41 Markdown rastreados, 7 migraciones, 15 archivos de prueba y 72 métodos de prueba. `vendor`, `node_modules`, `public/build`, `.env`, `_referencia`, `output` y `tmp` existen localmente y están total o parcialmente ignorados.

## 5. Funcionalidad y flujos implementados

### Acceso y participante

- Fortify: login, registro, recuperación, verificación de correo, confirmación de contraseña y 2FA disponible.
- Registro con perfil inicial, teléfono mexicano `+52`, mayoría de edad y consentimientos.
- Contraseña mínima de ocho caracteres con complejidad y ayuda visual; el backend conserva la autoridad.
- Landing pública y descarga controlada de documentos jurídicos publicados por hash.

### Propuestas

- Borrador, edición, equipo de hasta cinco integrantes, adjuntos privados y enlaces.
- Máximo tres propuestas por participante y una por categoría.
- Envío idempotente con snapshot inmutable y folio; estados `draft`, `submitted`, `withdrawn`.
- Panel privilegiado mínimo para consulta.

### Admisibilidad (Fase 02A)

- Expediente asociado a propuesta y versión enviada, creado idempotentemente.
- Aclaraciones y respuestas append-only.
- Solicitudes/documentos de residencia por persona en almacenamiento privado.
- Estados, transición motivada, notas internas separadas, eventos inmutables y auditoría.
- Correos después del commit con reintentos y sin revertir decisiones por una falla SMTP.
- Feature flag apagada por defecto.

### Evaluación (Fase 02B)

- [DOCUMENTACIÓN VIGENTE] Sólo matrices, amenazas, modelo propuesto, pruebas y ExecPlan.
- [PENDIENTE DE VALIDACIÓN] Escala, pesos, fórmula individual, umbral, asignación y desempate impiden implementar.

## 6. Modelo de datos y migraciones

Las migraciones base crean usuarios, caché y jobs; después se agregan 2FA, permisos, dominio Flower Flow y tablas de admisibilidad. La migración `2026_07_16_120000_create_admissibility_review_tables.php` está pendiente en la base local inspeccionada. No se ejecutaron migraciones en esta auditoría.

El dominio incluye perfiles, categorías, propuestas, integrantes, archivos, versiones enviadas, aceptaciones legales, expedientes, eventos, aclaraciones, residencia y auditoría. La especificación detallada está en `docs/03-data-model.md`.

## 7. Autenticación, roles y permisos

- `participant`: opera únicamente sus recursos.
- `reviewer`: recibe permisos granulares de panel, propuestas, admisibilidad y residencia.
- `admin`: recibe todos los permisos sembrados.
- [EVIDENCIA EN PRUEBAS] Existen pruebas negativas de acceso cruzado y de descarga.
- [PENDIENTE TÉCNICO] 2FA existe, pero no se exige a roles privilegiados.
- [EVIDENCIA EN CÓDIGO] No existe un rol juez operativo. Un juez futuro no debe acceder a identidad, residencia, notas internas ni auditoría sensible.

## 8. APIs, integraciones y procesos asíncronos

No hay API pública ni webhooks. Las integraciones efectivas son MySQL, transporte de correo y cola Laravel. Se aceptan enlaces de YouTube/Drive/Dropbox/OneDrive como datos, sin consumir sus APIs. S3 está configurado como posibilidad estándar, pero no es dependencia operativa confirmada.

La configuración local observada usa `queue=sync` y `mail=log`; producción documenta cola `database` con worker Supervisor. Los mailables implementan cola después del commit, payload cifrado, intentos, timeout y backoff. No hay tareas programadas en `routes/console.php`; el runbook conserva el scheduler como preparación futura.

## 9. Configuración sin secretos

Variables centrales: `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_TIMEZONE`, `DB_*`, `DB_TIMEZONE`, `QUEUE_CONNECTION`, `MAIL_*`, `SESSION_SECURE_COOKIE`, `FLOWERFLOW_TIMEZONE`, `FLOWERFLOW_CANONICAL_URL`, `FLOWERFLOW_REGISTRATION_ENABLED`, `FLOWERFLOW_SUBMISSIONS_ENABLED`, `FLOWERFLOW_RESULTS_ENABLED`, `FLOWERFLOW_PANEL_ENABLED`, `FLOWERFLOW_ADMISSIBILITY_REVIEW_ENABLED` y parámetros de correo Flower Flow.

Contrato horario: `APP_TIMEZONE=UTC`, `DB_TIMEZONE=+00:00`, `FLOWERFLOW_TIMEZONE=America/Hermosillo`. Nunca documentar valores de `.env`; el archivo local está ignorado y se replica físicamente sólo por autorización de migración.

## 10. Instalación, ejecución y compilación

Desde la ruta WSL canónica:

```bash
cd /home/ccortesg/workspace/flowerflow
composer install
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"
nvm use 22.23.1
corepack yarn install --frozen-lockfile
cp .env.example .env
php artisan key:generate
```

Configura la base en `.env` sin exponer credenciales. Antes de `migrate`, confirma explícitamente una base desechable si se usarán pruebas destructivas. Para servir y compilar:

```bash
php artisan serve --host=127.0.0.1 --port=8000
scripts/build_frontend_production.sh
```

`scripts/publish_authorized_assets.sh` publica copias verificadas de los originales; no edites `public/build` ni los originales manualmente.

## 11. Pruebas y validaciones

Comandos oficiales:

```bash
php artisan test
vendor/bin/pint --test
composer validate --strict
composer check-platform-reqs --no-dev
composer audit --locked
corepack yarn audit --groups dependencies --level moderate
scripts/build_frontend_production.sh
php artisan route:list
git diff --check
```

No ejecutes la suite completa hasta separar la base de pruebas: `phpunit.xml` no fija una conexión/base desechable y `RefreshDatabase` podría operar sobre la base indicada por `.env`.

| Estado | Validación y resultado |
|---|---|
| [VERIFICADO EN ESTA EJECUCIÓN] | Sintaxis de 141 PHP: 0 fallos. JSON de cuatro manifiestos: válido. |
| [VERIFICADO EN ESTA EJECUCIÓN] | `composer validate --strict --no-check-publish`: válido; `check-platform-reqs --no-dev`: correcto. |
| [VERIFICADO EN ESTA EJECUCIÓN] | `php artisan route:list`: 59 rutas; `migrate:status`: consulta correcta y migración Fase 02A pendiente. |
| [VERIFICADO EN ESTA EJECUCIÓN] | Unit test seguro `SubmissionContentSanitizerTest`: 1 prueba, 5 aserciones. |
| [VERIFICADO EN ESTA EJECUCIÓN] | Build Vite aislado en `/tmp`: 2,218 módulos, exitoso, con advertencia de chunks grandes. |
| [VALIDACIÓN FALLIDA] | Pint sobre PHP rastreado: 10 archivos con deuda de formato; no se modificaron. |
| [VALIDACIÓN FALLIDA] | Composer audit: 5 advisories en `guzzlehttp/guzzle` 7.14.2 (1 alto, 4 medios). |
| [VALIDACIÓN FALLIDA] | Yarn audit producción: 7 bajos, 37 moderados, 38 altos y 4 críticos; incluye `tar`, `swiper` y rutas de `form-data`. Requiere triage y actualización autorizada. |
| [PENDIENTE DE VALIDACIÓN] | Suite completa de 72 métodos: bloqueada por ausencia de aislamiento seguro de DB en esta ejecución. |
| [VALIDADO PREVIAMENTE] | Documentación Fase 02A: 72 pruebas/696 aserciones y QA real con usuarios sintéticos. |
| [VALIDADO POR EL USUARIO] | QA visual y responsive del área participante fue aceptado; no se repitió. |
| [PENDIENTE DE VALIDACIÓN] | Navegador, SMTP real, worker, Apache y producción no se verificaron. |

## 12. Despliegue documentado

El objetivo productivo es AWS EC2 Ubuntu, no GoDaddy. Apache debe apuntar a `/var/www/flowerflow/public`; Apache y worker operan como `www-data`. Node 22.23.1 se aísla mediante NVM del usuario de despliegue para no afectar Administratec. Seguir `docs/07-deployment-aws-ec2.md`; ninguna evidencia local sustituye backup, UAT, preflight y rollback productivos.

## 13. Decisiones vigentes y sustituidas

Vigentes: Laravel 12, Blade, Materialize/Pixinvent, MySQL, UTC/Hermosillo, archivos privados, Policies, flags apagados para funciones sensibles, cola transaccional y AWS EC2 como destino.

- [OBSOLETO] GoDaddy como destino; sustituido por AWS EC2 Ubuntu.
- [OBSOLETO] Contraseña mínima de 12; sustituida por mínimo de 8 con complejidad.
- [OBSOLETO] Estado inicial “sin Git, locks, vendor ni dominio”; sólo describe la auditoría histórica de 2026-07-15.
- [OBSOLETO] Fase 02A “sin commit”: el HEAD actual ya contiene su publicación y la definición documental posterior de Fase 02B.

## 14. Avance, pendientes y próximo paso

Completado: Fase 01 funcional, rediseños participantes aceptados, Fase 02A implementada/validada previamente y Fase 02B definida documentalmente.

Pendientes funcionales: resolver decisiones PENDING de jueces/evaluación; después autorizar un baseline y una rama específica. No implementar resultados, ganadores, comunicaciones masivas ni borrado de residencia antes de sus reglas.

Pendientes técnicos prioritarios:

1. Crear una configuración de pruebas que falle cerrado y apunte a MySQL desechable.
2. Triagear y actualizar de forma controlada las dependencias con advisories; regenerar locks sólo con autorización.
3. Aplicar y validar la migración Fase 02A únicamente en un entorno desechable antes de habilitar el flag.
4. Resolver deuda Pint sin mezclarla con una funcionalidad.
5. Reducir el grafo demo y los chunks de Vite después de un smoke visual.
6. Confirmar licencia Pixinvent/FormValidation/FullCalendar/Mapbox.
7. Diseñar antimalware para uploads y endurecer nombres originales/PDF de propuestas.
8. Definir 2FA privilegiado, CSP y rate limiting de decisiones.

Próximo paso recomendado: un milestone de hardening local, aislado de Fase 02B, que asegure la base de pruebas y cierre advisories. Después, resolver por escrito los PENDING jurídicos/de negocio y emitir un prompt de implementación de jueces.

## 15. Deuda, incidencias y riesgos conocidos

- La Action de finalización recibe aceptaciones, pero la persistencia depende del contrato previo del Form Request; revisar encapsulación.
- Las aceptaciones legales conservan IP y user-agent; definir necesidad y retención.
- El cálculo de edad usa el reloj de aplicación UTC; probar el borde de fecha contra Hermosillo.
- Los archivos principales tienen controles de firma/MIME, pero no antimalware; la política de PDF privado es más estricta.
- El nombre original de adjuntos evaluables merece normalización explícita de separadores Windows; el nombre interno ya es aleatorio.
- CSP permite `style-src 'unsafe-inline'`; no hay 2FA obligatoria ni throttling específico para decisiones privilegiadas.
- El build incluye una cantidad amplia de assets demo y reporta chunks grandes.
- Menús JSON y algunos controladores heredados de demostración siguen presentes, aunque el layout Flower Flow usa navegación propia.
- No existe CI/CD versionado.
- El repositorio primario tiene metadata de un worktree enlazado en `/mnt/c/temp/flowerflow_ui_public_landing_v2`; esa carpeta física externa no forma parte del repositorio copiado. No eliminar metadata ni el worktree sin una tarea Git autorizada.
- `git fsck --full` no reportó corrupción, pero sí objetos colgantes recuperables, algo compatible con historial local reescrito/operaciones previas.

## 16. Dependencias y estado externo

No viajan por copiar el repositorio: servidor MySQL/usuarios/datos, configuración global de Apache/PHP, servicio Supervisor, SMTP, DNS/TLS, IAM, backups y estado de EC2/Administratec. Aunque `.env`, `vendor`, `node_modules`, storage y build locales sí se replican, ello no vuelve reproducible ni válido el ambiente externo.

## 17. Estado Git del handoff

- [VERIFICADO EN ESTA EJECUCIÓN] Rama y tracking: `codex/phase-02-admissibility-review` sobre `origin/codex/phase-02-admissibility-review` según refs locales.
- [VERIFICADO EN ESTA EJECUCIÓN] HEAD: `d293fee63843beb64899498ca169e114e7695d6e`.
- Remoto: `origin` en GitHub; no se ejecutó `fetch`, por lo que no se afirma estado vivo de GitHub.
- Línea base: árbol e índice limpios; sin untracked.
- Estado posterior autorizado: cambios únicamente documentales, sin stage ni commit.
- Stashes y tags: ninguno.
- Submódulos y Git LFS: no configurados; Git LFS no está instalado.
- Ramas locales: Fase 01, Fase 02A actual, landing v2; esta última está asociada a un worktree externo.

## 18. Migración Windows/WampServer a WSL

Origen: `/mnt/c/wamp64/www/flowerflow`.
Destino: `/home/ccortesg/workspace/flowerflow`.
Distribución: `Ubuntu`.
Ruta Codex/Windows: `\\wsl.localhost\Ubuntu\home\ccortesg\workspace\flowerflow`.

La ruta Linux evita la penalización de I/O y las limitaciones POSIX de `/mnt/c`. El checkout usa `core.filemode=false` y `.gitattributes` fuerza LF. No se detectaron duplicados rastreados que colisionen sólo por mayúsculas ni symlinks físicos. No normalizar modos o EOL masivamente.

Referencias Windows detectadas: evidencia histórica en `design-qa.md`, instrucciones/prompts conservados y la guía local antigua. Las rutas `/var/www/flowerflow` son productivas e intencionales. No se encontró dependencia funcional de PowerShell/CMD en scripts de aplicación.

Para operar en WSL deberán existir PHP/extensiones, Composer, Node/NVM/Corepack/Yarn, cliente/servidor MySQL y permisos de escritura para `storage`/`bootstrap/cache`. Apache/Supervisor sólo son necesarios para un entorno persistente o productivo, no para inspección.

## 19. Continuidad para el siguiente agente

1. Leer `AGENTS.md`, este handoff, `.agent/PLANS.md` y el ExecPlan aplicable.
2. Confirmar ruta, rama, HEAD y estado antes de tocar archivos; el repositorio contiene cambios documentales no stageados derivados de esta auditoría.
3. No ejecutar la suite completa hasta confirmar una base desechable distinta de la local principal.
4. No habilitar Fase 02A donde su migración esté pendiente.
5. No implementar Fase 02B hasta cerrar todos los PENDING y recibir autorización expresa.
6. No limpiar ignorados ni el worktree enlazado: forman parte del contexto local.
7. No desplegar ni modificar servicios externos por inferencia.

## 20. Alcance y fecha de auditoría

Se revisaron código propio, configuración, rutas, migraciones, seeders, pruebas, manifests/locks, scripts, Docker, documentación, refs e historial local de Git y hechos relevantes reportados por el usuario. `vendor` y `node_modules` se inventariaron y auditaron mediante locks/herramientas, sin lectura semántica exhaustiva. No se inspeccionó GitHub en vivo, producción, servicios externos ni datos de usuarios. Los secretos permanecieron redactados.
