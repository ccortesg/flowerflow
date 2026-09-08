# Landing centrado en votación — 2026-09-08

## Alcance y estado

Plan aprobado por el propietario después de la integración inicial de Google Forms. Checkout `/home/ccortesg/workspace/flowerflow`, HEAD inicial y final `55bd202` (`Votacion`), árbol limpio antes de la adenda. Implementación y QA realizados en local/test; gate global pendiente por Pint preexistente, milestone sin cierre integral. Sin stage, commit, push ni despliegue.

Se retiran del HTML las secciones «Cuatro formas de transformar la ciudad», «Un proceso sencillo», «Antes de comenzar», «Consulta antes de participar» y «Resolvemos tus dudas». La introducción pasa a invitar a votar. El reconocimiento comunica exactamente «Los 2 proyectos con más votos serán los ganadores» y «Premio aún por definir», con un 2 tipográfico en lugar del iPad y sin referencias a cuatro ganadores o premios por categoría.

El banner enlaza a `#ganadores`; encabezado, pie, login y navegación pública dejan de apuntar a secciones ocultas. Documentos sigue accesible por `/documentos`, junto con los enlaces a términos y privacidad. Se conservan el modal y las URLs exactas de Google Forms, carga diferida y reutilización del iframe, alternativa externa, CSP y acceso a cuentas.

## Archivos de esta adenda

1. `.agent/execplans/flowerflow-public-voting.md`
2. `resources/views/public/landing.blade.php`
3. `resources/css/pages/public-landing.css`
4. `resources/views/public/partials/landing-header.blade.php`
5. `resources/views/public/partials/landing-footer.blade.php`
6. `resources/views/auth/login.blade.php`
7. `resources/views/layouts/flowerflow.blade.php`
8. `tests/Feature/PublicLandingTest.php`
9. `tests/Feature/ParticipantExperienceRedesignTest.php`
10. `docs/adr/0017-public-voting-google-forms.md`
11. `docs/requirements-traceability.md`
12. `docs/template-overrides.md`
13. `docs/35-voting-focused-landing-2026-09-08.md`

No hay cambios en controladores, modelo, rutas, permisos, configuración, JavaScript, middleware, originales, PDF ni dependencias. La preparación de categorías existente en el controlador permanece; el landing ya no las renderiza.

## Baseline

| Control | Resultado observado antes de editar |
|---|---|
| `FLOWERFLOW_TEST_GUARD_ONLY=true scripts/serve_local_testing.sh` | Testing, MySQL 127.0.0.1, base `flowerflow_testing`, usuario `flowerflow_testing_user` |
| `php artisan test --filter='DisposableDatabaseGuardTest\|PublicLandingTest\|SecurityAndFlagsTest\|ParticipantExperienceRedesignTest'` | 38 passed, 476 assertions, 22.44 s |
| `scripts/build_frontend_production.sh` | Verde; Node 22.23.1, Yarn 1.22.22, lock conservado |
| `vendor/bin/pint --test` | Exit 1; sólo `video-tutorial/scripts/freeze-time.php`, fixer `fully_qualified_strict_types` |

Logs: `/tmp/ff-voting-focus-baseline-tests.log`, `/tmp/ff-voting-focus-baseline-build.log`, `/tmp/ff-voting-focus-baseline-pint.log`.

## Validación final

| Control | Resultado de esta adenda |
|---|---|
| Mismo filtro focalizado del baseline | 38 passed, 528 assertions, 21.59 s; `/tmp/ff-voting-focus-tests.log` |
| `scripts/build_frontend_production.sh` | Verde desde lock antes de QA y después de la suite; `/tmp/ff-voting-focus-build.log`, `/tmp/ff-voting-focus-final-build.log` |
| `vendor/bin/pint --test tests/Feature/PublicLandingTest.php tests/Feature/ParticipantExperienceRedesignTest.php` | Passed |
| JSON y manifest mediante Python (`json.loads`, rutas de archivos del manifest) | 15 JSON versionados válidos; tres entradas Vite, todos sus archivos existen |
| `git diff --check` | Verde |
| `composer validate --strict --no-check-publish`, `composer check-platform-reqs --no-dev`, `composer audit --locked` | Verdes; sin advisories Composer |
| `corepack yarn audit --groups dependencies --level moderate` | Exit 2, un aviso LOW; código tolerado por el gate. Sin nuevas dependencias ni cambios de lock |
| `scripts/quality_gate_local.sh` | Suite: 247 passed, 1 skipped, 4348 assertions, 1044.39 s; después exit 1 en Pint. `/tmp/ff-voting-focus-quality-gate.log` |
| `vendor/bin/pint --test` posterior a corrección del router QA | Sólo falla `video-tutorial/scripts/freeze-time.php`, `fully_qualified_strict_types`, como en baseline; `/tmp/ff-voting-focus-final-pint.log` |
| `APP_ENV=testing php artisan route:list --json` | 140 rutas; `/`, `/documentos` y login conservan nombres/destinos; `/tmp/ff-voting-focus-routes.json` |

El primer lanzamiento del gate final terminó con SIGTERM (143), sin resumen final ni fallos de aserciones reportados. Se conserva `/tmp/ff-voting-focus-quality-gate-interrupted.log` y no se contabiliza como una suite aprobada. El reintento ejecutó el mismo comando completo en un proceso independiente y registró su código final en `/tmp/ff-voting-focus-quality-gate.exit`; no hubo suites concurrentes.

La suite del reintento aprobó todos los casos ejecutados. El único omitido es el benchmark optativo de 200 MiB de `BulkJudgeAssignmentTest`, condicionado por `FLOWERFLOW_RUN_BULK_PERFORMANCE_TEST`, igual que en la validación histórica. Pint detectó el archivo preexistente y una línea vacía faltante en el router temporal propio: se corrigió esta última y se repitió Pint global, que ahora sólo informa el fallo preexistente. No se repitió la suite completa por ese cambio de formato del helper de QA. El gate permanece sin aprobar y el milestone no se declara cerrado.

Las pruebas cubren contenido y ausencia del contenido anterior, enlaces con destinos existentes, acceso a documentos y cuentas, portada con/sin convocatoria activa, flags independientes, 404 al desactivar portada, modal único y CSP por ruta. Se actualizó la expectativa histórica de FAQ en experiencia participante. La cobertura de categorías en pantallas participantes se conserva en sus pruebas existentes.

### Navegador real

Chromium local mediante la habilidad Playwright y CLI, sesión `ff-voting-focus`. Guiones y logs reproducibles, ignorados por Git, en `output/playwright/ff-voting-focus-{responsive,checks,edge-cases,navigation}.js` y `.log`. Las capturas identificadas como `synthetic` usan contenido local sin envío para probar foco/estado; las identificadas como `google` corresponden al servicio real sin sesión.

| Escenario | Resultado |
|---|---|
| 320×780, 390×844, 768×1024, 1440×1000 | Sin overflow horizontal ni anclas rotas; cuatro secciones; sin los cinco bloques retirados ni imagen del iPad |
| CTA principal en 390×844 | Top 383.42 px, bottom 436.19 px, altura 52.77 px; visible sin desplazar |
| Modal y accesos | 25 controles verdes: carga diferida, cuatro accesos/una instancia, una petición de iframe, foco visible, Tab/Shift+Tab/Escape/Space/Enter, overlay, retorno de foco y estado conservado |
| Móvil y movimiento reducido | Menú cerrado al votar, foco vuelve al acceso visible, modal fullscreen y controles accesibles; movimiento reducido respetado |
| Fallos externos y mejora progresiva | 11 controles verdes: alternativa visible ante fallo, URL exacta, enlace funcional sin JS, clic modificado hacia Google; sin éxito inventado |
| Ampliación | Zoom CSS 200 % sin overflow; viewport equivalente 720×500, modal 688×450. Es una aproximación explícita, no evidencia de zoom nativo |
| Navegación HTTP | 13 controles verdes con router temporal: destinos desde banner/footer/login/layout, menú y Escape, título fuera del header fijo, tres PDF 200; cero excepciones JS |
| Google real anónimo | Embed exacto HTTP 401, «Abrir en Google» siempre visible; URL corta resuelve al formulario correcto. Sin credenciales ni votos |
| Consola final de navegación | 0 errores, 0 warnings; los HTTP 401 externos se reportan por separado |

### Particularidad del servidor de QA

La primera revisión HTTP detectó que `artisan serve` devuelve 404 en `/documentos`: el router del framework evalúa `file_exists` y delega el directorio público homónimo al servidor integrado de PHP, que no lo resuelve como ruta Laravel. Las pruebas Feature de `/documentos` pasan porque llegan a Laravel.

Para completar QA se cerró ese servidor y se usó `php -S 127.0.0.1:8017 -t public output/playwright/ff-voting-focus-router.php` después del guard, con entorno testing, base/usuario desechables, mail array y cola sync. Este router temporal usa `is_file` para servir estáticos y envía los demás paths al `public/index.php` real. No intercepta el HTML ni cambia los PDF. Los tres documentos respondieron HTTP 200. No se modificaron archivos del proveedor, rutas ni configuración de producción; el comportamiento del comando `artisan serve` original se conserva y queda documentado.

Los dos intentos iniciales del guion de navegación se conservan en `ff-voting-focus-navigation-first-attempt.log` (constructor `URL` no expuesto por el runner CLI, corregido en el guion) y `ff-voting-focus-navigation-artisan-directory.log` (404 descrito arriba). La ejecución final pasó los 13 controles. Navegador y servidores temporales se cerraron antes del gate para evitar concurrencia con las pruebas que recrean el esquema.

### Capturas

- [Página completa, escritorio](../output/playwright/ff-voting-focus-full-1440.png).
- [Página completa, móvil](../output/playwright/ff-voting-focus-full-390.png).
- [Reconocimiento, 320 px](../output/playwright/ff-voting-focus-winners-320.png) y [390 px](../output/playwright/ff-voting-focus-winners-390.png).
- [Tablet](../output/playwright/ff-voting-focus-full-768.png).
- [Destino Ganadores en móvil](../output/playwright/ff-voting-focus-anchor-mobile.png).
- [Modal Google sin sesión](../output/playwright/ff-voting-focus-google-mobile.png) y [alternativa externa](../output/playwright/ff-voting-focus-google-external-anonymous.png).
- [Sin JavaScript](../output/playwright/ff-voting-focus-no-js.png) y [ampliación CSS 200 %](../output/playwright/ff-voting-focus-zoom-css-200.png).

## Pendientes y límites

- `PENDING`: PDF y pantallas de cuenta que todavía anuncian el premio anterior requieren reconciliación separada. El cambio aprobado comunica dos proyectos ganadores en total sólo en el landing.
- Premio concreto, fechas de votación y desempates no definidos aquí. No hay conteo, selección automática ni publicación de resultados implementados por esta adenda.
- Google mantiene autenticación, respuestas y confirmación. UAT con cuenta Google corresponde al propietario; no se introducen credenciales ni votos de prueba.
- El Pint global ya falla en el baseline por un archivo ajeno. El milestone no se declarará cerrado si el gate requerido continúa fallando.
- Verificar `/documentos` en el servidor destino antes de una release: el router temporal de QA permite comprobar Laravel, pero no corrige el comportamiento de `artisan serve` ni valida la configuración de producción.

## Rollback

Revertir únicamente los cambios enumerados de esta adenda, preservando la integración de votación presente en `55bd202`, y ejecutar `scripts/build_frontend_production.sh`. Recuperar las secciones desde Git; no hay migraciones ni datos que revertir. Los PDF y el asset histórico del iPad se conservan. El rollback no autoriza acciones en producción.
