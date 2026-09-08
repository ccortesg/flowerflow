# Banner público de votación — implementación local del 2026-09-08

## Estado y alcance

Implementación local y QA de interacción realizados; el control global se registra abajo. No se declara cerrado el milestone mientras exista una validación requerida pendiente. Ver [ExecPlan de votación](../.agent/execplans/flowerflow-public-voting.md) y [ADR-0017](adr/0017-public-voting-google-forms.md). La autorización incluye banner, botones de encabezado/cierre y modal Google Forms. No incluye configuración del formulario, datos, credenciales, votos, commit, push ni despliegue.

## Comportamiento acordado

- Texto HTML: «Votación ciudadana · Hermosillo 2026», «¡La gente elige!», «Vota por tu proyecto favorito. Tu opinión cuenta. Hagamos florecer a Hermosillo.».
- Enlaces «Votar» mejorados a controles de modal con JavaScript, conservando su destino HTTPS sin JavaScript y en clics modificados.
- Iframe sin conexión inicial a Google, cargado en la primera apertura y conservado entre aperturas. URL exacta entregada por el propietario; ningún dato FlowerFlow se añade al enlace.
- Modal único al final del body, cierre/foco/overlay/scroll, menú móvil coordinado y fondo no interactivo mientras está abierto.
- «Abrir en Google» siempre visible. La carga del iframe no se interpreta como voto o acceso confirmado. Se acepta completar el acceso/voto en una pestaña externa.
- CSP autoriza `docs.google.com` sólo en `frame-src` de `landing`, para políticas vigente y estricta. Se conserva el resto de controles.
- Las secciones históricas de información de convocatoria permanecen sin cambios; no se reinterpretan como reglas de la votación.

## Recursos gráficos y reproducción

El flyer autorizado se conserva en `imagen/ff-flyer-votacion-original.jpg`; nunca se publica su texto «Próximamente» en el banner. ImageGen integrado produjo la nueva ilustración sin textos ni logos, inspeccionada visualmente antes de guardarla como `imagen/voting-illustration-source.png` (1254×1254).

Prompt usado con el flyer como referencia:

> Create a production website illustration derived from the attached Flower Flow voting flyer. Input image is the visual reference. Use case: stylized-concept. Output only a single square 1024x1024 illustration asset, NOT a website, mockup or flyer. Isolate and reinterpret the reference's lower-center ballot box and a cream ballot with an orange circle and white check mark, surrounded by graceful olive/sage botanical branches and restrained pale peach flowers. Preserve the warm watercolor and printed-paper aesthetic, cream/off-white background, orange lid, dark green/sage leaves, delicate hand-painted texture. Show the entire ballot box including its bottom, not cropped. Balanced airy composition suited to the right half of a website hero, with 8 percent breathing room around all edges. No words, letters, typography, numbers, logos, brands, seals, dates, website addresses, watermarks, UI buttons or 'próximamente'. Remove the original logo from the face of the ballot box, leave that face simply cream paper. Background should be very light warm cream #fff6e5, fading cleanly to this same flat cream at outer edges for integration into a web hero. Keep focus on the single ballot and ballot box. Polished subtle texture, not photorealistic, not 3D plastic.

Se pidió 1024×1024; el servicio entregó 1254×1254. La generación no es determinista: se conserva el PNG generado e inspeccionado. Los exportes sí se reproducen con `php scripts/build_voting_assets.php` sobre el PNG guardado, con PHP 8.3.33/GD 2.3.3, remuestreo proporcional y WebP calidad 86. El runtime de la aplicación no ejecuta ese script. No se introduce dependencia.

| Archivo | Dimensiones | Bytes | SHA-256 |
|---|---:|---:|---|
| `imagen/ff-flyer-votacion-original.jpg` | 1122×1402 | 299824 | `8978e36902af98d7d50766b6085e4fa0e28f4893b7b279bb16fb26c22559210a` |
| `imagen/voting-illustration-source.png` | 1254×1254 | 1714339 | `dfe26690712b5fdf8fed0b0b2f7a674b0e349b7af64ea7f8627085f5f28ed9b8` |
| `public/assets/flowerflow/landing/voting-illustration-640.webp` | 640×640 | 27502 | `d3df2c03e41b2a586ddaf2231edcc455f1457341cf1f3187c05e457cc069a783` |
| `public/assets/flowerflow/landing/voting-illustration-1024.webp` | 1024×1024 | 53824 | `2ba2bfb9fa4c781c9196620ca1cffa2d6c49a9376a7bdbd825e99a6d1a6dd088` |

## Validación y evidencia

Baseline: rama `codex/submission-deadline-extension`, HEAD `3d2b43c`, árbol inicialmente limpio. `php artisan test` terminó antes de conectar las nuevas vistas/configuración/CSP: **244 passed, 1 skipped, 4131 assertions**, 1093.52 s. Log: `/tmp/ff-voting-baseline-tests.log`. La omisión existente corresponde al benchmark optativo de 200 MiB de `BulkJudgeAssignmentTest`; no se habilitó esa carga extraordinaria.

| Comando/comprobación | Resultado |
|---|---|
| `FLOWERFLOW_TEST_GUARD_ONLY=true scripts/serve_local_testing.sh` | Conexión exclusiva a `flowerflow_testing` con `flowerflow_testing_user`; sin DB_URL |
| `php artisan test --filter=DisposableDatabaseGuardTest` | 8 passed / 8 assertions |
| `php artisan test --filter='PublicLandingTest\|SecurityAndFlagsTest'` | 21 passed / 298 assertions, 19.91 s; `/tmp/ff-voting-focused.log` |
| `corepack yarn build` | Verde; 101 iconos, tres entradas Vite; módulo nuevo 1.60 KB / 0.80 KB gzip y Bootstrap compartido |
| Pint sobre los PHP modificados, `node --check resources/js/pages/public-voting.js` | Verdes |
| `composer validate --strict --no-check-publish`, `composer check-platform-reqs --no-dev`, `composer audit --locked` | Verdes; sin advisories |
| `corepack yarn audit --groups dependencies --level moderate` | Un aviso LOW de Quill ya existente; exit 2 permitido por el gate, sin moderados/altos/críticos |
| `php scripts/build_voting_assets.php` | Regeneración con los mismos hashes y dimensiones de la tabla de recursos |
| JSON versionados y manifest Vite | 15 JSON válidos; entradas CSS/app JS/voting JS |
| Comparación de secciones informativas con HEAD | Introducción hasta antes del CTA final: igualdad byte por byte |
| `scripts/quality_gate_local.sh` | Suite: **247 passed, 1 skipped, 4296 assertions**, 1074.97 s. Exit 1 en Pint global por `video-tutorial/scripts/freeze-time.php` (`fully_qualified_strict_types`), igual al baseline; `/tmp/ff-voting-quality-gate.log` |
| `scripts/build_frontend_production.sh` | Verde desde lockfile; Node 22.23.1 / Yarn 1.22.22; `/tmp/ff-voting-final-build.log`; dependencias y lockfiles sin cambios |
| `php artisan route:list`, `git diff --check` | 140 rutas; diff sin errores de whitespace |
| Smoke directo posterior al gate/build | `/` HTTP 200, cuatro accesos, iframe inicialmente sin src; móvil sin overflow y CTA visible. Google 401 con fallback visible. `/login` HTTP 200 sin modal/JS de votación ni permiso Google en CSP; `output/playwright/ff-voting-final-smoke.log` |

La revisión en Chromium comprobó 320/390/768/1440 px sin desbordamiento horizontal. En 390×844 el CTA principal pasó de empezar a **1155 px** a empezar a **383 px**, terminando a **436 px**, antes de la ilustración. El modal mide 800×896 px en escritorio 1440×1000 y ocupa la pantalla en 390×844. Se comprobó reapertura con una única petición del iframe, persistencia de contenido sintético, overlay, botones de cierre, Escape en documento padre, Tab/Shift+Tab, foco/restauración, fondo no interactivo, desbloqueo de scroll, menú móvil y movimiento reducido.

Sin JavaScript los accesos conservan semántica de enlace y abren la URL corta. Con iframe bloqueado, la alternativa permanece visible y no aparece ningún éxito ficticio. Un clic modificado siguió la redirección de Google al ID exacto del formulario. El zoom CSS al 200 % se usó como aproximación automatizable, junto con un viewport equivalente 720×500; no se presenta como comprobación del zoom nativo del navegador. Durante esta revisión se corrigió el solapamiento del encabezado permitiendo otra fila y se alineó el cierre a la derecha.

**Google real sin sesión:** embed HTTP **401** con pantalla «Accede a tu cuenta de Google»; «Abrir en Google» abre `People Choice Award` y su diálogo de acceso obligatorio. Esta respuesta no se interpreta como fallo del modal ni como voto. La prueba con sesión Google queda manual para el propietario.

| Captura local (bajo `output/playwright/`) | Contenido |
|---|---|
| `ff-voting-1440.png`, `ff-voting-390.png`, `ff-voting-320.png`, `ff-voting-768.png` | Nuevo banner en los cuatro tamaños |
| `ff-voting-desktop-full.png` | Landing completo y secciones conservadas |
| `ff-voting-modal-google-desktop.png`, `ff-voting-modal-google-mobile.png` | Modal con respuesta real de Google sin sesión |
| `ff-voting-google-external-anonymous.png` | Diálogo real de acceso de Google en pestaña externa |
| `ff-voting-modal-synthetic-*.png` | Documento sintético de QA para foco, scroll y persistencia; no es Google Forms |
| `ff-voting-no-js.png`, `ff-voting-zoom-css-200.png` | Mejora progresiva y aproximación de ampliación |

Los scripts/logs de navegador `ff-voting-responsive`, `ff-voting-checks`, `ff-voting-edge-cases` y `ff-voting-real-google` quedan junto a las capturas, ignorados y fuera del release. El QA de interfaz conservó temporalmente el HTML público y headers obtenidos del servidor local antes de ejecutar la suite que recrea esa misma base; los assets corresponden al build vigente. El smoke final vuelve a consultar la ruta real sin esa captura. Detalles en [design-qa.md](design-qa.md). Las capturas `ff-voting-audit-*` son exclusivamente anteriores al cambio.

El chequeo independiente `ff-voting-console-regressions.log` terminó con **0 errores JavaScript y 0 mensajes warning/error de la aplicación**, FAQ expandida correctamente, menú cerrado con Escape y enlace secundario dirigido a `#categorias`. Los fallos HTTP 401 de Google se registran por separado; no se ocultan bajo esa métrica.

## Archivos de la integración

| Archivo | Cambio |
|---|---|
| `resources/views/public/landing.blade.php` | Nuevo hero, SEO, CTA final y registro de modal/script por página |
| `resources/views/public/partials/landing-header.blade.php` | Enlaces Votar en escritorio y móvil; login conservado |
| `resources/views/public/partials/voting-modal.blade.php` | Modal compartido con iframe diferido y alternativa externa |
| `resources/views/layouts/flowerflow.blade.php` | Stack de modales al final del body |
| `resources/css/pages/public-landing.css` | Estilos de banner/modal y adaptación del encabezado al espacio disponible |
| `resources/js/pages/public-voting.js` | Bootstrap, carga única, foco, menú móvil e interactividad de fondo |
| `vite.config.js` | Entrada JS exclusiva del landing |
| `config/flowerflow.php` | Dos URL exactas centralizadas, sin flags nuevos |
| `app/Http/Middleware/SecurityHeaders.php` | Permiso de frame Google limitado a la ruta landing |
| `tests/Feature/PublicLandingTest.php` | Contenido, flags, enlaces, modal único, ausencia de src inicial y rutas ajenas |
| `tests/Feature/SecurityAndFlagsTest.php` | CSP vigente/estricta y exclusión de Google en otras rutas |
| `scripts/build_voting_assets.php` | Exportes WebP reproducibles mediante GD |
| `imagen/ff-flyer-votacion-original.jpg` | Copia intacta del flyer autorizado |
| `imagen/voting-illustration-source.png` | Original de ilustración generada |
| `public/assets/flowerflow/landing/voting-illustration-640.webp` | Derivado optimizado 640 px |
| `public/assets/flowerflow/landing/voting-illustration-1024.webp` | Derivado optimizado 1024 px |
| `.agent/execplans/flowerflow-public-voting.md` | Ejecución, evidencia, pendientes y rollback |
| `docs/adr/0017-public-voting-google-forms.md` | Decisión y dependencia externa |
| `docs/05-ux-ui.md` | Comportamiento público vigente |
| `docs/dependency-register.md` | Google Forms; sin paquetes nuevos |
| `docs/requirements-traceability.md` | Requisitos VOTE-01 a VOTE-06 |
| `docs/template-overrides.md` | Adaptaciones fuera del core del proveedor |
| `docs/design-qa.md` | Medidas, revisión visual, pruebas y límites |
| `docs/34-public-voting-integration-2026-09-08.md` | Este informe, prompt gráfico, hashes y entrega |

## Límites y pendientes

- UAT con cuenta Google: sólo manual por el propietario. No se introdujeron credenciales ni se emitieron votos.
- Google controla cookies, acceso, tamaño interno y estados. No se promete que todo visitante complete el proceso dentro del iframe.
- `Option 1` fue observado en el formulario y debe revisarlo su propietario. No se modifica desde esta integración.
- Pint global tenía deuda preexistente en `video-tutorial/scripts/freeze-time.php`; no se debe declarar el gate global verde si persiste.

El gate global **no está verde** por esa deuda de formato ajena a la integración. Se conserva el archivo sin cambios; los controles restantes del script se ejecutaron por separado y pasaron bajo su umbral aprobado. El milestone no se declara cerrado. Para UAT con sesión, el propietario puede abrir el formulario con su cuenta y verificar las opciones sin introducir votos de prueba; autenticación, respuestas y confirmación pertenecen a Google.

## Rollback

Revertir únicamente los archivos de esta integración y reconstruir Vite. No hay migraciones, flags nuevos ni datos que revertir. Conservar los originales gráficos y documentos legales históricos. Producción queda fuera de alcance.
