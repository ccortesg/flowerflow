# Registro de dependencias

## Snapshot instalado Fase 01 — 2026-07-15

| Dependencia | Versión lock | Motivo | Licencia | Alternativa considerada | Estado |
|---|---:|---|---|---|---|
| laravel/framework | 12.64.0 | Framework baseline | MIT | Upgrade major rechazado en fase | INSTALLED/AUDITED |
| laravel/fortify | 1.37.2 | Auth, reset, verificación, 2FA | MIT | Auth propia rechazada por riesgo | INSTALLED; passkeys deshabilitadas |
| spatie/laravel-permission | 8.3.0 | Roles/permisos Laravel 12/PHP 8.3 | MIT | RBAC mínimo propio | INSTALLED |
| symfony/html-sanitizer | 7.4.14 | Allowlist HTML servidor | MIT | Purificador manual rechazado | INSTALLED |
| quill | 2.0.3 | Editor rico requerido | BSD-3-Clause | textarea plano no cubre requisito | INSTALLED heredado/adaptado |
| bootstrap | 5.3.6 | Shell responsive starter | MIT | CSS propio completo | INSTALLED heredado |

`composer.lock` contiene 135 paquetes y `composer audit --locked` no reportó advisories. Yarn 1.22.22 instaló el lock y el build fue verde; persisten warnings de resolución Algolia, peers de loaders/ESLint y chunks demo grandes. No se añadió Playwright, S3, ClamAV, Mailpit ni otra dependencia propuesta.

Fecha de corte: 2026-07-15

## 1. Convenciones

Estados:

- DECLARED: figura en composer.json o package.json.
- ABSENT: declarado pero no instalado en la baseline.
- TRANSITIVE-UNKNOWN: no puede inventariarse sin lock/vendor/node_modules.
- PENDING: propuesta no aprobada y no instalada.
- CONDITIONAL: sólo se justifica si una historia aprobada la necesita.

La licencia indicada es una clasificación preliminar basada en el manifiesto local, metadata oficial del paquete o repositorio oficial. No sustituye una revisión legal. Se requiere un SBOM y verificación de licencias transitivas después de instalar desde lockfiles.

## 2. Resumen

| Ecosistema | Directas runtime | Directas dev | Lock | Instaladas |
|---|---:|---:|---|---|
| Composer | 3 entradas incluyendo PHP | 7 | composer.lock ausente | No |
| JavaScript | 79 | 33 | yarn.lock v1 presente | No |

Riesgos transversales:

- El proyecto raíz Materialize declara licencia Commercial en package.json.
- No existe evidencia de la licencia comprada, dominio autorizado o derecho de despliegue.
- FormValidation remite a términos propios; FullCalendar Timeline y Mapbox remiten a archivos/licencias especiales.
- composer.lock ausente impide fijar el árbol PHP.
- yarn.lock existe, pero Yarn no está instalado; npm audit no lo consume.
- overrides y resolutions fuerzan transitivas y deben revisarse después de instalar.
- react y react-dom aparecen sólo en resolutions, no como dependencias directas: configuración heredada a justificar o retirar.

## 3. PHP/Composer actual

| Paquete/requisito | Restricción | Uso esperado | Licencia | Estado y riesgo |
|---|---:|---|---|---|
| php | ^8.2 | Runtime | PHP License | DECLARED; producción exacta aún PENDING |
| laravel/framework | ^12.0 | Framework | MIT | DECLARED/ABSENT; minor/patch desconocido sin lock |
| laravel/tinker | ^2.10.1 | Consola interactiva | MIT | DECLARED/ABSENT; no necesaria en producción operativa |
| fakerphp/faker | ^1.23 | Datos de test | MIT | DEV/ABSENT; prohibir PII real |
| laravel/pail | ^1.2.2 | Tail de logs local | MIT | DEV/ABSENT; no sustituye observabilidad |
| laravel/pint | ^1.13 | Formato PHP | MIT | DEV/ABSENT |
| laravel/sail | ^1.41 | Docker local | MIT | DEV/ABSENT; compose depende de sus archivos |
| mockery/mockery | ^1.6 | Mocks | BSD-3-Clause | DEV/ABSENT |
| nunomaduro/collision | ^8.6 | Salida CLI de errores | MIT | DEV/ABSENT |
| phpunit/phpunit | ^11.5.3 | Tests | BSD-3-Clause | DEV/ABSENT |

No están declarados paquetes de autenticación, permisos, almacenamiento S3, antivirus, generación de documentos ni auditoría de dominio.

## 4. JavaScript runtime actual

Todos los siguientes están DECLARED/ABSENT con versión exacta en package.json.

| Grupo | Paquetes y versiones | Licencia/riesgo principal |
|---|---|---|
| Búsqueda/autocomplete | @algolia/autocomplete-js 1.19.2; @algolia/autocomplete-theme-classic 1.19.2; bloodhound-js 1.2.3; typeahead.js 0.11.1 | Mayormente MIT; Algolia sólo debe buscar navegación estática, nunca PII dinámica en JSON público |
| Validación cliente | @form-validation/bundle 2.4.0; @form-validation/core 2.4.0; @form-validation/plugin-alias 2.4.0; @form-validation/plugin-auto-focus 2.4.0; @form-validation/plugin-bootstrap5 2.4.0; @form-validation/plugin-excluded 2.4.0; @form-validation/plugin-field-status 2.4.0; @form-validation/plugin-framework 2.4.0; @form-validation/plugin-message 2.4.0 | Licencia remite a formvalidation.io/license; confirmar cobertura con la licencia Pixinvent. Nunca sustituye Form Requests |
| Calendario | @fullcalendar/core 6.1.17; @fullcalendar/daygrid 6.1.17; @fullcalendar/interaction 6.1.17; @fullcalendar/list 6.1.17; @fullcalendar/timegrid 6.1.17; @fullcalendar/timeline 6.1.17 | Core/plugins estándar suelen ser MIT; timeline declara SEE LICENSE IN LICENSE.md y requiere revisión específica |
| Iconos | @iconify/json 2.2.348; @iconify/tools 4.1.2; @iconify/types 2.0.0; @iconify/utils 2.3.0; flag-icons 7.5.0 | Paquetes MIT; cada set de iconos puede conservar atribuciones/licencias propias |
| Core UI | @popperjs/core 2.11.8; bootstrap 5.3.6; jquery 3.7.1; jquery-idletimer 1.0.0; hammerjs 2.0.8; node-waves 0.7.6; perfect-scrollbar 1.5.6; @simonwep/pickr 1.9.1 | Principalmente MIT; forman parte del shell y no deben podarse sin smoke visual |
| Formularios | @yaireo/tagify 4.32.2; bootstrap-daterangepicker 3.1.0; bootstrap-select 1.14.0-beta3; bs-stepper 1.7.0; cleave-zen 0.0.17; dropzone 5.9.3; flatpickr 4.6.13; jquery.repeater 1.2.1; nouislider 15.8.1; select2 4.0.13; timepicker 1.14.1 | Mayormente MIT; varias soluciones se solapan y deben cargarse por página |
| Tablas/export cliente | clipboard 2.0.11; datatables.net-bs5 2.1.8; datatables.net-buttons 3.2.3; datatables.net-buttons-bs5 3.2.3; datatables.net-fixedcolumns-bs5 5.0.4; datatables.net-fixedheader-bs5 4.0.2; datatables.net-responsive 3.0.4; datatables.net-responsive-bs5 3.0.4; datatables.net-rowgroup-bs5 1.5.1; datatables.net-select-bs5 2.1.0; jszip 3.10.1; pdfmake 0.2.20; numeral 2.0.6 | Principalmente MIT; exportación con PII debe ser backend, autorizada y auditada |
| Gráficas | apexcharts 4.2.0; chart.js 4.4.9 | Ambas MIT en las versiones declaradas; elegir una |
| Contenido/media | highlight.js 11.10.0; katex 0.16.22; plyr 3.7.8; quill 2.0.3; raty-js 4.3.0; swiper 11.1.15 | Quill BSD-3-Clause; Leaflet no está aquí; Quill sólo si contenido enriquecido y sanitizado es aprobado |
| Apps y layout avanzado | jkanban 1.3.1; jstree 3.3.17; leaflet 1.9.4; mapbox-gl 3.8.0; masonry-layout 4.2.2; shepherd.js 14.3.0; sortablejs 1.15.6 | Leaflet BSD-2-Clause; Mapbox declara SEE LICENSE IN LICENSE.txt; casi todo es demo/CONDITIONAL |
| Feedback/efectos | animate.css 4.1.1; aos 2.3.4; notiflix 3.2.8; notyf 3.10.0; spinkit 2.0.1; sweetalert2 11.14.5 | Principalmente MIT; evitar feedback duplicado |
| Bridge Laravel | laravel-vite-plugin 1.3.0 | MIT; acoplado a Vite |
| Utilidad temporal | moment 2.30.1 | MIT; legacy/maintenance mode en upstream, evitar uso nuevo si APIs existentes bastan |

## 5. JavaScript de desarrollo actual

| Grupo | Paquetes y versiones | Riesgo |
|---|---|---|
| Babel | @babel/core 7.26.10; @babel/plugin-transform-destructuring 7.23.3; @babel/plugin-transform-object-rest-spread 7.23.4; @babel/plugin-transform-template-literals 7.23.3; @babel/preset-env 7.26.9; babel-loader 9.1.3 | Herencia del template; comprobar si Vite moderno realmente los necesita |
| Build/CSS | @rollup/plugin-html 1.1.0; autoprefixer 10.4.21; postcss 8.5.5; resolve-url-loader 5.0.0; sass 1.76.0; sass-loader 14.0.0; source-map-resolve 0.6.0; vite 6.3.5 | source-map-resolve es antiguo; build aún no probado |
| Lint/formato | eslint 9.16.0; eslint-config-airbnb-base 15.0.0; eslint-config-prettier 9.1.0; eslint-plugin-import 2.31.0; eslint-plugin-prettier 5.4.1; prettier 3.5.3 | Revisar peer compatibility al instalar |
| Stylelint | @stylistic/stylelint-config 1.0.1; @stylistic/stylelint-plugin 2.1.3; stylelint 16.20.0; stylelint-config-idiomatic-order 10.0.0; stylelint-config-standard-scss 13.1.0; stylelint-use-logical-spec 5.0.1 | Sólo útil si hay scripts de lint definidos; package.json hoy sólo define dev/build |
| Runtime de desarrollo | ajv 8.17.1; axios 1.9.0; browser-sync 3.0.4; concurrently 9.1.2; cross-env 7.0.3; glob 10.4.5; lodash 4.17.21 | axios se usa en resources/js/bootstrap.js pese a estar en devDependencies; producción debe incluirlo en build |

La clasificación dev de axios no impide que Vite lo empaquete, pero una instalación con omit=dev no podría compilar assets. La estrategia correcta en EC2 es desplegar public/build ya generado en CI o instalar devDependencies sólo durante una etapa de build separada.

## 6. Duplicaciones y decisiones de poda

| Necesidad | Alternativas actuales | Recomendación | Estado |
|---|---|---|---|
| Gráficas | ApexCharts / Chart.js | Elegir una tras diseñar dashboards | PENDING |
| Mapas | Leaflet / Mapbox GL | No hay mapa en MVP conocido; retirar ambos si no aparece historia aprobada | PENDING |
| Select avanzado | bootstrap-select / Select2 | Preferir Select2 sólo para catálogos extensos; input nativo para listas pequeñas | PENDING |
| Fechas | Flatpickr / daterangepicker / timepicker / Moment | Preferir Flatpickr y APIs de fecha de Laravel; conservar extras sólo por caso real | PENDING |
| Feedback | Notyf / Notiflix / SweetAlert2 / Spinkit | Notyf para toast, SweetAlert2 para confirmación, Notiflix sólo para bloqueo/carga justificada | PENDING |
| Export cliente | DataTables Buttons / JSZip / PDFMake | Preferir export backend autorizado; evitar PII en navegador | PENDING |
| Búsqueda | Algolia autocomplete / Typeahead-Bloodhound | Búsqueda estática de navegación solamente; datos por endpoints propios | PENDING |
| Rich text | Quill | No incluir sin contenido administrable aprobado y sanitización | PENDING |
| Calendario | FullCalendar seis plugins | No cargar si el calendario de convocatoria puede resolverse con vistas simples | PENDING |
| Demos | Kanban, tree, tours, academy/ecommerce/logistics scripts | Excluir del build cuando se confirme que no los usa una página | PENDING |

La poda debe ocurrir después de establecer un smoke visual y un mapa de imports. No se deben eliminar librerías comunes del template por nombre únicamente.

## 7. Licencias con verificación obligatoria

| Componente | Evidencia | Acción |
|---|---|---|
| Materialize/Pixinvent | package.json declara Commercial; config/variables.php declara templateFree=false | Obtener comprobante, tipo de licencia y dominio/proyecto autorizado |
| FormValidation 2.4.0 | npm metadata remite a https://formvalidation.io/license | Confirmar que el bundle comercial adquirido permite su uso |
| FullCalendar Timeline 6.1.17 | npm metadata: SEE LICENSE IN LICENSE.md | Verificar si requiere licencia Scheduler/Premium |
| Mapbox GL 3.8.0 | npm metadata: SEE LICENSE IN LICENSE.txt | Verificar términos de SDK, token, telemetría y facturación |
| Iconify/flag-icons | Código MIT, iconos con licencias por colección | Conservar atribuciones aplicables |
| Quill 2.0.3 | BSD-3-Clause | Conservar notice |
| Leaflet 1.9.4 | BSD-2-Clause | Conservar notice |
| Playwright propuesto | Apache-2.0 | Sólo desarrollo/CI |
| ClamAV propuesto | GPL-2.0 | Ejecutable/servicio separado; revisar operación y distribución |

## 8. Dependencias propuestas, no instaladas

Ninguna fila de esta sección autoriza instalación.

| Propuesta | Versión | Propósito | Licencia | Condición de aprobación | Estado |
|---|---|---|---|---|---|
| spatie/laravel-permission | PENDING | Roles/permisos integrados con Gate y Policies | MIT | Fijar PHP de EC2: v7/v8 requiere PHP 8.3+; v6 soporta Laravel 12 con PHP 8.2 | PENDING |
| laravel/fortify | PENDING, rama estable compatible | Backend headless para login, registro, reset, verificación, password confirmation y 2FA | MIT | Aprobar arquitectura de auth y adaptación a Blade Materialize | PENDING |
| league/flysystem-aws-s3-v3 | ^3.0, por validar | Disco S3 y URLs temporales | MIT | Aprobar S3, IAM role, bucket privado, retención y costo | PENDING |
| Amazon S3 | Servicio, no paquete | Almacenamiento privado escalable | Servicio AWS | Block Public Access, cifrado, lifecycle, logging y IAM mínimo | PENDING |
| ClamAV/clamd | Versión de Ubuntu aprobada | Escaneo de uploads | GPL-2.0 | Validar RAM/CPU, actualización freshclam, timeouts y fallback | PENDING |
| Playwright | Versión dev fijada en lock | E2E real Chromium/Firefox/WebKit | Apache-2.0 | Aprobar costo CI, navegadores y datos controlados | PENDING |
| Mailpit | Versión de entorno fijada | Captura de correo local | MIT | Sólo desarrollo; nunca producción | PENDING |

Alternativas que no deben añadirse por defecto:

- Un paquete genérico Repository para Eloquent.
- Un segundo sistema de roles paralelo a Spatie.
- Un segundo framework E2E además de Playwright sin necesidad.
- Un paquete de auditoría antes de definir eventos, redacción de PII y retención.
- Un generador Excel/PDF hasta que los formatos de exportación estén aprobados.

## 9. Auditoría y actualización

La baseline no permitió ejecutar auditoría real:

- composer audit no existe en Composer 2.2.6.
- npm audit devuelve ENOLOCK.
- No hay vendor ni node_modules.

Antes de aprobar dependencias:

1. Actualizar Composer a una versión soportada y segura.
2. Generar/revisar composer.lock sin cambiar majors no aprobados.
3. Elegir npm o Yarn y mantener un solo lock autoritativo.
4. Instalar desde lock en un entorno aislado.
5. Ejecutar composer audit y la auditoría del package manager.
6. Generar SBOM con versiones transitivas y licencias.
7. Revisar CVEs, paquetes abandonados y peer warnings.
8. Documentar toda aceptación de riesgo con dueño y fecha de revisión.
