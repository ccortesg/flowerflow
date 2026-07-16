# Auditoría de referencia Pixinvent Full

Fecha: 2026-07-15
Fuente local: `_referencia/` (sólo lectura, ignorada por Git)
Fuente visual: `https://demos.pixinvent.com/materialize-html-laravel-admin-template/demo-1/`

## Inventario y límites

Se observaron 1,301 archivos (aprox. 40 MiB), `package.json` Materialize 3.0.0, licencia declarada `Commercial`, Laravel 12, layouts front/vertical/horizontal/blank, menús JSON, helpers, vistas demo y assets por página. No se ejecutó ningún comando desde `_referencia/`, no se modificó un archivo y no se incorporó la carpeta a autoload, Vite, rutas, CI o producción.

La licencia comercial efectiva y su alcance por proyecto/dominio siguen pendientes de evidencia. Por ello sólo se adaptaron patrones puntuales ya presentes también en el starter activo; no se copiaron datos demo, avatares, copy comercial ni bloques completos.

## Catálogo técnico evaluado

| Capacidad | Vista local y demo | JS/SCSS y dependencia | Función demostrada | Aplicación FlowerFlow | Decisión | Riesgo y prueba de aceptación |
|---|---|---|---|---|---|---|
| Landing | `content/front-pages/landing-page.blade.php`; `/front-pages/landing-page` | `front-page-landing.js`, `front-page-landing.scss`, Swiper, noUiSlider | Hero, secciones, scroll y cards | Orden visual público | ADAPT | Copy/assets demo y peso; probar HTML semántico, móvil, contraste y que no exista dependencia externa. |
| Basic Inputs | `content/form-elements/forms-basic-inputs.blade.php`; `/forms/basic-inputs` | `form-basic-inputs.js`, Bootstrap | Labels, help text, tipos HTML5 | Perfil y propuesta | ADAPT | Floating labels pueden ocultar contexto; probar label asociado, error y teclado. |
| Editors | `content/form-elements/forms-editors.blade.php`; `/forms/editors` | `forms-editors.js`, Quill 2.0.3, editor SCSS, KaTeX/highlight | Snow/Bubble editor | Descripción rica | ADAPT | XSS, Base64 y toolbar excesiva; se usa toolbar mínima y sanitización doble. |
| File Upload | `content/form-elements/forms-file-upload.blade.php`; `/forms/file-upload` | `forms-file-upload.js`, Dropzone 5.9.3 | Drag/drop y fallback | Adjuntos múltiples | REJECT | Demo no sube archivos y aumenta superficie; se usa input nativo con backend privado. |
| Validation | `content/form-validation/form-validation.blade.php`; `/form/validation` | FormValidation, Select2, Flatpickr, Tagify | Estados de validación | Mensajes/form controls | ADAPT | Licencia comercial y duplicación; HTML nativo ayuda, Form Requests son autoridad. |
| Wizard | `content/form-wizard/form-wizard-numbered.blade.php`; `/form/wizard-numbered` | bs-stepper, FormValidation, Select2 | Flujo numerado | Propuesta por etapas | FUTURE | JS demo no persiste ni recupera foco; aceptar sólo con browser/a11y y guardado por paso. |
| Auth | `content/authentications/auth-login-basic.blade.php`; `/auth/login-basic` | `pages-auth.js`, FormValidation, `page-auth.scss` | Card de login/password | Login Fortify | ADAPT | Social links y GET demo rechazados; probar rate limit, reset, verificación, 2FA y enumeración. |
| Modals/offcanvas | `content/modal/modal-examples.blade.php`; `/modal-examples` | varios `modal-*`, Bootstrap modal | Diálogos de edición/OTP | Confirmaciones puntuales | REJECT | Copiar demos arrastra formularios falsos; confirmación crítica usa flujo simple y progresivo. |
| Alerts/SweetAlert | `content/extended-ui/extended-ui-sweetalert2.blade.php`; `/extended-ui/sweetalert2` | SweetAlert2, Animate.css | Feedback y confirmación | Avisos de éxito/error | FUTURE | Diálogo JS no sustituye respuesta accesible; mensajes server-rendered son actuales. |
| Cards/dashboard | dashboards CRM y `cards-*`; `/dashboard/crm` | ApexCharts/Chart.js y cards JS | KPIs, estadísticas | Panel mínimo | ADAPT | Gráficas innecesarias; se adoptan cards estáticas y tablas HTML. |
| DataTables | `content/tables/tables-datatables-basic.blade.php`; `/tables/datatables-basic` | DataTables Responsive/Buttons, Moment, Flatpickr | Búsqueda, filtros, export | Lista de propuestas | REJECT | Bundle grande y export PII en cliente; paginación/filtros se resuelven en servidor. |
| Account | `content/pages/pages-account-settings-account.blade.php` | Select2, Tagify, SweetAlert, FormValidation | Perfil y avatar | Perfil participante/admin | ADAPT | Campos demo exceden minimización; sólo datos aprobados, sin avatar. |
| Security | `content/pages/pages-account-settings-security.blade.php` | `pages-account-settings-security.js`, `modal-enable-otp.js` | Password/OTP | Fortify 2FA | ADAPT | UI demo no persiste; probar confirmación de password, secretos y recovery codes. |
| Roles | `content/apps/app-access-roles.blade.php`; `/app/access-roles` | DataTables, modal role | Cards de roles | RBAC backend | REJECT | UI de gestión amplía alcance; se adopta sólo patrón conceptual con Spatie y seeder. |
| Permissions | `content/apps/app-access-permission.blade.php`; `/app/access-permission` | DataTables, modales permission | Tabla de permisos | Policy/permisos | REJECT | Gestión dinámica no autorizada; permisos fijados por código y pruebas. |
| Error/maintenance | `content/pages/pages-misc-error.blade.php` y páginas misc | `page-misc.scss` | Estados visuales | 404/419/429/500 | FUTURE | Inline style/assets demo; crear páginas propias con status HTTP real. |

## Accesibilidad, responsive y rendimiento

La referencia usa Bootstrap responsive y labels con `for`, pero abundan enlaces `javascript:`, formularios `onsubmit=false`, iconos sin nombre claro, modales, contenido en inglés y dependencias globales. Ningún ejemplo se considera WCAG por sí solo. FlowerFlow conserva skip link, headings, labels explícitos, tablas semánticas y mensajes server-rendered; la aceptación final requiere teclado, foco, zoom 200/400 %, lector de pantalla y contraste.

El build heredado aún incluye globs amplios del starter. El build verde genera chunks grandes de DataTables, Mapbox y Highlight aunque las pantallas nuevas no los usan; su racionalización es deuda de rendimiento separada para no podar core sin smoke visual.

## Trazabilidad de adaptaciones efectivas

- Estructura de card/hero/rows: `resources/views/public/landing.blade.php` y `resources/css/app.css`.
- Inputs, feedback y cards: vistas `auth/`, `participant/`, `submissions/` y `panel/`.
- Quill: `resources/js/app.js`, formulario y `SubmissionContentSanitizer`.
- Cuenta/seguridad: `resources/views/panel/account.blade.php`, backend Fortify.
- Roles/permisos: Spatie, `SubmissionPolicy`, middleware de panel y seeder.

No se editó core en `_referencia/`. Las pruebas requeridas son build, Feature/RBAC/XSS/IDOR, QA browser responsive y revisión de licencia.
