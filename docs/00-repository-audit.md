# Auditoría técnica del repositorio

> **Actualización Fase 01 (2026-07-15):** este documento conserva la fotografía de la baseline. Desde el commit base `403656dce350709d066aaab0576175036a9f339c` ya existen Git, locks, dependencias instaladas, dominio FlowerFlow, rutas mutables, pruebas y build verde. El estado vigente está en `docs/12-project-status-2026-07-15.md`; las afirmaciones “no existe” de este informe no describen el checkout actual.

Fecha de corte: 2026-07-15  
Ruta auditada: /mnt/c/wamp64/www/flowerflow  
Modalidad: sólo lectura sobre la baseline inicial

## 1. Dictamen ejecutivo

El checkout auditado no contiene todavía una plataforma Flower Flow funcional. Contiene dos cimientos reutilizables:

1. Un esqueleto de Laravel que declara Laravel 12 y PHP 8.2 o superior.
2. El shell visual de Materialize de Pixinvent, con layouts Blade, menús y una biblioteca amplia de assets.

La cobertura funcional del dominio es cero módulos implementados. No existen flujos reales de autenticación, RBAC, convocatorias, categorías, participantes, elegibilidad, proyectos, archivos, jueces, rúbricas, evaluaciones, resultados, comunicaciones, reportes, auditoría ni privacidad. Las pantallas de login y registro son demostraciones que envían un GET a la portada; no autentican ni crean usuarios.

El repositorio tampoco estaba instalable en su estado inicial: faltaban .env, vendor, node_modules, public/build y composer.lock. Artisan, tests y build no podían ejecutarse. La sintaxis PHP, los manifiestos JSON y composer.json sí eran válidos.

La primera fase implementable debe ser Milestone 0: proteger secretos, fijar la baseline en control de versiones, normalizar runtimes y package manager, instalar y bloquear dependencias, configurar MySQL local, ejecutar migraciones y obtener tests/build/smoke verdes. No corresponde iniciar todavía un módulo de negocio.

## 2. Definición de baseline

Esta auditoría distingue la baseline inicial del trabajo documental realizado en paralelo:

- Al comenzar la inspección no existían .git, AGENTS.md, .agent ni docs.
- Durante la ejecución aparecieron AGENTS.md, .agent y docs por trabajo paralelo de planificación.
- Los conteos de aplicación, rutas, modelos, migraciones, vistas, tests y assets corresponden al checkout inicial.
- Este documento no atribuye los archivos de planificación posteriores a la baseline.
- No se modificó código de aplicación para producir esta auditoría.

## 3. Alcance y limitaciones

Se inspeccionaron manifiestos, configuración, rutas, controladores, modelos, middleware, migraciones, factories, seeders, tests, vistas, layouts, menús, assets, Vite, Docker/Sail, README y archivos de ambiente.

Limitaciones:

- No había repositorio Git. No fue posible obtener historial, autoría, tags, diff respecto del ZIP original ni demostrar que los archivos Pixinvent estén intactos.
- No había composer.lock ni vendor. La versión exacta minor/patch de Laravel y las dependencias transitivas PHP no pudieron determinarse.
- No había node_modules. No se pudo compilar ni obtener un inventario instalado real de dependencias transitivas JS.
- yarn.lock existe, pero Yarn no estaba instalado. npm audit no acepta yarn.lock como lockfile npm.
- Docker Desktop no estaba integrado con esta distro WSL; no se levantó Sail.
- La inspección inicial del repositorio no abrió una conexión. Durante la misma fase documental, una verificación separada y no destructiva confirmó conexión TCP por CLI y PDO; el secreto se omite deliberadamente de documentación versionada.
- No se ejecutaron migraciones ni escrituras sobre ninguna base.
- La variante/licencia comprada de Materialize no puede confirmarse sólo con package.json; requiere factura, licencia o acceso al repositorio privado de Pixinvent.

## 4. Matriz de versiones

| Componente | Declarado en repositorio | Observado en estación | Estado |
|---|---:|---:|---|
| PHP | ^8.2, composer.json líneas 11-14 | 8.3.31 CLI WSL | Compatible con la restricción |
| Laravel Framework | ^12.0, composer.json línea 13 | No instalable sin vendor/composer.lock | Minor/patch desconocido |
| Laravel Tinker | ^2.10.1 | No instalado | Declarado |
| PHPUnit | ^11.5.3, desarrollo | No instalado | Tests bloqueados |
| Laravel Sail | ^1.41, desarrollo | No instalado | Docker compose depende de vendor |
| Materialize | 3.0.0, package.json líneas 2-6 | Fuentes presentes | La numeración local no prueba release/licencia Pixinvent |
| Bootstrap | 5.3.6 | No instalado | Declarado |
| Vite | 6.3.5 | No instalado | Build bloqueado |
| Node | No se fija engine | node.exe 20.20.0; no alias node WSL | Debe normalizarse |
| npm | No se fija engine | 10.8.2 | Disponible vía Windows |
| Yarn | yarn.lock v1 | No instalado | Package manager inconsistente |
| Composer | No se fija versión | 2.2.6 | Antiguo; audit no disponible |
| Cliente MySQL | N/A | 8.0.46 | Disponible |
| Extensión MySQL PHP | Requerida por el ambiente objetivo | pdo_mysql y mysqli presentes | Disponible |
| Extensión SQLite PHP | .env.example la presupone | pdo_sqlite ausente | Baseline local incompatible |
| Docker | Sail compose | Integración WSL no disponible | No validado |
| Ubuntu de EC2 | No documentado | No inspeccionado | PENDING |

Laravel 12 admite PHP 8.2 a 8.5 según su política oficial. A la fecha de corte, sus correcciones generales terminan el 2026-08-13 y las de seguridad el 2027-02-24. La decisión de permanecer en Laravel 12 o planear Laravel 13 debe ser explícita; no se debe actualizar de major durante Milestone 0.

## 5. Inventario de aplicación

### 5.1 Conteos

| Artefacto | Cantidad | Observación |
|---|---:|---|
| Declaraciones Route en routes/web.php | 6 | Todas GET |
| Health route | 1 | /up, definido en bootstrap/app.php |
| Controllers PHP | 7 | Incluye Controller base |
| Modelos | 1 | User |
| Vistas Blade totales | 26 | Principalmente layout/template |
| Vistas de contenido | 5 | Home, Page 2, error, login, registro |
| Migraciones | 3 | Sólo tablas base Laravel |
| Migraciones de dominio | 0 | Sin entidades Flower Flow |
| Feature tests | 1 | Ejemplo GET / = 200 |
| Unit tests | 1 | Ejemplo true = true |
| Assets fuente | 340, aproximadamente 3.2 MB | Amplia biblioteca demo |
| Assets públicos | 38, aproximadamente 233 KB | Varias referencias faltantes |
| Dependencias JS directas | 79 runtime + 33 dev | Ninguna instalada |

### 5.2 Rutas y controladores

routes/web.php líneas 11-21 declara:

- GET /: HomePage@index.
- GET /page-2: Page2@index.
- GET /lang/{locale}: cambio de idioma.
- GET /pages/misc-error: vista demo de error.
- GET /auth/login-basic: maqueta de login.
- GET /auth/register-basic: maqueta de registro.

bootstrap/app.php líneas 8-16 agrega /up y LocaleMiddleware al grupo web.

No existen POST, PUT, PATCH o DELETE; tampoco grupos auth, verified, throttle o can. No existen Form Requests, Policies, Jobs, Mailables, Notifications, Events, Listeners ni Actions de negocio.

Los controladores sólo retornan vistas. MiscError retorna una vista normal y por ello /pages/misc-error responde 200, aunque el contenido muestre 404.

### 5.3 Autenticación

Las vistas son demostrativas:

- resources/views/content/authentications/auth-login-basic.blade.php líneas 45-76 envía el formulario con GET a /.
- resources/views/content/authentications/auth-register-basic.blade.php líneas 44-77 hace lo mismo.
- app/Models/User.php línea 5 mantiene MustVerifyEmail comentado.
- composer.json no declara Fortify, Breeze, Jetstream ni otro backend de autenticación.
- resources/views/layouts/sections/navbar/navbar-partial.blade.php líneas 110-166 referencia Laravel Jetstream cuando existe un usuario autenticado, pero Jetstream no está instalado. Es un fallo latente al habilitar autenticación.
- El navbar muestra John Doe y Admin como texto demo en líneas 89-97.

### 5.4 Vistas, layouts y menús

Sí existen bases reutilizables:

- layoutMaster selecciona vertical, horizontal, blank o front.
- commonMaster concentra HTML, metadatos, estilos y scripts.
- contentNavbarLayout compone menú, navbar, contenido y footer.
- layoutFront existe, aunque ninguna página de contenido actual lo utiliza.
- verticalMenu.json y horizontalMenu.json contienen sólo Home, Page 2, Error, Login y Register.

La presentación sigue siendo Pixinvent:

- config/variables.php líneas 4-24 contiene nombre, enlaces, metadatos y repositorio Materialize.
- commonMaster.blade.php líneas 47-68 emite título, OG y canonical del producto Pixinvent.
- commonMaster.blade.php línea 63 impone noindex,nofollow a todos los layouts.
- public/robots.txt permite rastreo al dejar Disallow vacío.
- config/custom.php líneas 16-17 mantiene el customizer habilitado.
- footer-front.blade.php conserva newsletter, demos, apps móviles y texto de una plantilla React.

### 5.5 Assets y build

vite.config.js líneas 17-69 agrega mediante glob todos los JS, librerías y SCSS del template como entradas. Esto compilaría una superficie demo mucho mayor que las cinco vistas reales y debe racionalizarse página por página, no borrarse indiscriminadamente.

Referencias de imagen faltantes comprobadas:

- auth-basic-login-mask-light.png.
- auth-basic-login-mask-dark.png.
- auth-basic-register-mask-light.png.
- auth-basic-register-mask-dark.png.
- front-pages/backgrounds/footer-bg.png.
- front-pages/landing-page/apple-icon.png.
- front-pages/landing-page/google-play-icon.png.

La página de error sí tiene sus ilustraciones. El login, registro y footer front quedarían visualmente incompletos incluso después del build.

### 5.6 Datos

Las únicas migraciones crean:

- users, password_reset_tokens y sessions.
- cache y cache_locks.
- jobs, job_batches y failed_jobs.

No existen competitions/calls, categories, participant_profiles, submissions, files, legal_documents, legal_acceptances, reviews, assignments, rubrics, evaluations, winners, communications, privacy_requests ni audit_logs.

database/seeders/DatabaseSeeder.php líneas 14-21 crea diez usuarios aleatorios y test@example.com. UserFactory.php líneas 24-31 utiliza la contraseña conocida password. Estos seeders son útiles sólo para pruebas y no deben formar parte de un seed productivo.

### 5.7 Configuración, README y despliegue

- README.md es el README estándar de Laravel; no contiene instalación Flower Flow.
- .env.example líneas 1-28 usa APP_NAME=Laravel, locale en, debug true y SQLite.
- composer.json líneas 51-54 crea database/database.sqlite durante create-project.
- database/database.sqlite no existe y el PHP WSL no tiene pdo_sqlite.
- La conexión mysql de config/database.php líneas 45-63 sí usa utf8mb4, modo estricto y pdo_mysql.
- Sesiones, cache y colas se declaran sobre database en .env.example.
- Correo usa log y hello@example.com.
- docker-compose.yml selecciona docker/8.2 y MySQL 8.0, pero monta un script desde vendor/laravel/sail, hoy inexistente.
- Las carpetas docker/7.4, docker/8.0 y docker/8.1 son incompatibles con PHP ^8.2; compose sólo selecciona 8.2.
- No hay configuración Nginx/Apache de producción, PHP-FPM, TLS, systemd/Supervisor para workers, cron, CI/CD, backup o rollback para EC2.

## 6. Cobertura contra el producto solicitado

| Capacidad | Evidencia reutilizable | Implementación de dominio |
|---|---|---:|
| Laravel y shell Materialize | Sí, parcial | No aplica |
| Branding y UX Flower Flow | Ninguna | 0 |
| Sitio público y legales | Sólo layoutFront demo | 0 |
| Registro, login, reset y verificación | Vistas demo | 0 |
| Roles, permisos, Policies y 2FA | Ninguna | 0 |
| Convocatorias y categorías | Ninguna | 0 |
| Perfil, residencia y elegibilidad | Ninguna | 0 |
| Wizard, equipos, archivos y versiones | Assets demo de stepper/dropzone | 0 |
| Panel administrativo y revisión | Shell vertical | 0 |
| Jueces, conflictos, rúbrica y evaluación | Ninguna | 0 |
| Ganadores y resultados públicos | Ninguna | 0 |
| Correo y comunicaciones | Config Laravel genérica | 0 |
| Reportes, exportaciones y auditoría | Librerías JS demo | 0 |
| Solicitudes de privacidad | Enlace demo sin destino | 0 |
| Pruebas trazables | Dos tests de ejemplo | 0 |
| Despliegue AWS EC2 | Config genérica AWS/Sail | 0 |

Conclusión cuantitativa: cero módulos de negocio completos. No es responsable asignar un porcentaje global de avance mayor a cero por la mera presencia de librerías visuales; la fundación técnica sí reduce trabajo de layout y configuración inicial.

## 7. Hallazgos priorizados

| ID | Severidad | Hallazgo | Evidencia | Acción |
|---|---|---|---|---|
| AUD-001 | Crítica en baseline; mitigada | .env no estaba ignorado | .gitignore inicial contenía # .env | Corregido durante la planificación: /.env y /.env.* ignorados, con excepción de /.env.example |
| AUD-002 | Crítica | Checkout no reproducible ni ejecutable | Sin .git, composer.lock, vendor, node_modules, public/build ni .env | Estabilizar baseline e instalar con lockfiles |
| AUD-003 | Alta | Configuración local presupone SQLite no disponible | .env.example línea 23; pdo_sqlite ausente | Cambiar el ambiente local a MySQL flowerflow |
| AUD-004 | Alta | Password MySQL no debe versionarse | Credencial entregada fuera del repo | Guardarla sólo en .env local o secret manager |
| AUD-005 | Alta | Login/registro no son funcionales | Formularios GET a / | Implementar backend autorizado tras aprobar Fortify |
| AUD-006 | Alta | Referencias Jetstream sin paquete | navbar-partial líneas 110-166 | Retirar acoplamiento demo o instalar solución aprobada |
| AUD-007 | Alta | Branding, canonical y OG apuntan a Pixinvent | config/variables y commonMaster | Sustituir con metadatos Flower Flow aprobados |
| AUD-008 | Alta | Assets referenciados faltantes | Siete imágenes verificadas como ausentes | Reemplazar con assets autorizados o retirar referencias |
| AUD-009 | Alta | Error 404 demo devuelve 200 | Route + controller retornan view normal | Implementar vistas errors/ con status real |
| AUD-010 | Alta | Sin RBAC ni separación de PII | Sin Policies/roles/permisos/tablas | Diseñar e implementar antes de datos sensibles |
| AUD-011 | Alta | Sin modelo de dominio ni auditoría | Cero migraciones de dominio | Aprobar ERD y transiciones antes de migrar |
| AUD-012 | Alta | No existe runbook EC2 | Sólo Sail local | Diseñar despliegue Ubuntu compartido con Administratec |
| AUD-013 | Media | Customizer activo | config/custom.php | Deshabilitar en producción |
| AUD-014 | Media | UI/locale en inglés; español no permitido | .env.example y LocaleMiddleware | Añadir es y traducir interfaz |
| AUD-015 | Media | robots y meta robots son contradictorios | robots.txt permite; commonMaster bloquea | Separar indexación pública/privada/staging |
| AUD-016 | Media | Build incluye superficie demo excesiva | globs de vite.config.js | Reducir entradas después de mapear usos |
| AUD-017 | Media | Seed productivo inseguro si se reutiliza | test@example.com y password conocida | Separar seeders base, dev y test |
| AUD-018 | Media | Auditorías de dependencias bloqueadas | Composer antiguo; npm sin lock compatible | Actualizar toolchain y ejecutar audits con locks |
| AUD-019 | Media | La licencia exacta de Materialize no está probada | package marca Commercial; no hay comprobante | Confirmar compra, dominio y derecho de despliegue |
| AUD-020 | Baja | Imports Request no utilizados | Controllers simples | Limpiar durante implementación, no como cambio aislado |

## 8. Base MySQL local y separación de ambientes

El ambiente de pruebas confirmado por el usuario es una instancia MySQL en WSL2:

- Distribución observada: Ubuntu 22.04.5 LTS sobre WSL2; Flower Flow es el nombre del ambiente/esquema, no el nombre registrado de la distribución.
- Servidor observado: MySQL Community 8.0.46, activo y limitado a loopback.
- Base: flowerflow.
- Usuario: flowerflow_user.
- Host verificado desde el mismo WSL: 127.0.0.1.
- Puerto verificado: 3306.
- Password: secreto local proporcionado por el operador; omitido de este documento.

La conexión por cliente MySQL y PDO devolvió el usuario efectivo flowerflow_user@localhost. El schema existe, está vacío, usa utf8mb4/utf8mb4_0900_ai_ci y el motor por defecto es InnoDB. No se ejecutaron migraciones. Los grants del sandbox incluyen WITH GRANT OPTION, permiso excesivo que no debe copiarse a staging o producción.

El .env local deberá usar DB_CONNECTION=mysql y los valores anteriores. .env.example debe documentar sólo placeholders. .gitignore ya fue corregido para excluir .env y sus variantes.

No se debe reutilizar esta credencial en EC2. Producción requiere una credencial distinta, rotada y entregada mediante un mecanismo de secretos. Si la aplicación accede a S3 desde EC2, debe usar un IAM instance role, no access keys persistentes.

El objetivo de producción cambió de GoDaddy a una instancia AWS EC2 Ubuntu donde ya se desplegó Administratec. Antes de proponer vhost, puertos, usuario Unix o servicios compartidos se debe inspeccionar esa instancia y aislar:

- directorio y usuario/grupo de despliegue;
- server_name y document root apuntando a public;
- socket/pool PHP-FPM;
- APP_KEY, APP_URL, cookies, cache prefix y colas;
- base y usuario MySQL;
- logs, rotación, backups y restore;
- worker y scheduler;
- permisos IAM y almacenamiento;
- staging y producción.

## 9. Estado de instalación comprobado

| Comando | Resultado resumido |
|---|---|
| git status --short --branch | Fatal: no es repositorio Git |
| composer validate --no-check-publish | composer.json válido |
| php -l sobre app, bootstrap, config, database, routes y tests | Sintaxis válida |
| Parseo JSON de package.json y menús | JSON válido |
| php artisan --version | Falla: vendor/autoload.php ausente |
| php artisan route:list | Falla: vendor/autoload.php ausente |
| php artisan test | Falla: vendor/autoload.php ausente |
| npm run build | Falla: vite no está instalado |
| npm ls --depth=0 | Todas las dependencias directas ausentes |
| composer audit | Comando no disponible en Composer 2.2.6 |
| npm audit | ENOLOCK; requiere package-lock/shrinkwrap |
| docker compose version | Docker no integrado con WSL |
| php -m | pdo_mysql presente; pdo_sqlite ausente |
| Búsqueda de entidades Flower Flow | Sin código de dominio |

Ningún comando de auditoría realizó migraciones, instalaciones, escrituras de base ni despliegues.

## 10. Primera fase implementable

### Milestone 0: baseline reproducible y entorno

Orden recomendado:

1. Inicializar o recuperar el repositorio Git y registrar la procedencia/licencia del template.
2. Verificar que la corrección ya aplicada a .gitignore excluye .env y variantes.
3. Estandarizar PHP 8.3, Composer actual compatible, Node 20 LTS y un solo package manager.
4. Resolver la estrategia de locks: producir composer.lock y conservar/regenerar el lock JS con el package manager elegido.
5. Crear .env local no versionado para MySQL flowerflow y generar APP_KEY.
6. Instalar dependencias sin cambios de major no aprobados.
7. Ejecutar migraciones base sobre la base de pruebas vacía.
8. Ejecutar about, route:list, migrate:status, tests, build y smoke HTTP.
9. Confirmar licencia Pixinvent y los términos especiales de FormValidation, FullCalendar Timeline y Mapbox.
10. Inspeccionar la EC2 de Administratec sin desplegar y documentar la arquitectura aislada.

### Criterios de salida

- git check-ignore .env confirma que el secreto está protegido.
- composer.lock y lock JS son coherentes y revisados.
- La versión exacta de Laravel queda registrada.
- php artisan about y php artisan route:list funcionan.
- php artisan migrate:status funciona contra MySQL local.
- php artisan test pasa.
- El build de Vite pasa.
- GET / y GET /up responden correctamente en un smoke local.
- No hay referencias visuales rotas en las cinco pantallas baseline.
- composer audit y la auditoría del package manager elegido producen resultados documentados.
- No se ha desplegado ni alterado la EC2.

Después de este cierre, el primer milestone funcional debe ser branding/limpieza controlada del starter y luego autenticación/RBAC, antes de capturar PII o documentos.
