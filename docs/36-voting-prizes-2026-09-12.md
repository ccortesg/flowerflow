# Premios de «¡La gente elige!» — 2026-09-12

## Cambio autorizado

El propietario confirmó los premios y proporcionó las fotografías: primer lugar **Meta Quest 3S**; segundo lugar **audífonos inalámbricos Beats Solo 4**. Se sustituye el aviso «Premio aún por definir» por dos tarjetas ordenadas, conservando el título «Los 2 proyectos con más votos serán los ganadores». Fotografías completas, sin recortes, con versiones responsive y carga diferida. No se agregan precios, capacidades, condiciones de entrega ni desempates.

Checkout local: `/home/ccortesg/workspace/flowerflow`; HEAD inicial `cc2bafc` (`Votacion 2`), árbol limpio. Sin cambios de rutas, configuración, autenticación, modal, JavaScript, CSP, documentos jurídicos o dependencias. Sólo local/test, sin stage, commit, push ni despliegue. La reconciliación con PDF y otras pantallas sigue PENDING; esta tarea no la sustituye.

## Archivos

1. `.agent/execplans/flowerflow-public-voting.md`
2. `resources/views/public/landing.blade.php`
3. `resources/css/pages/public-landing.css`
4. `tests/Feature/PublicLandingTest.php`
5. `tests/Feature/ParticipantExperienceRedesignTest.php`
6. `scripts/build_voting_prize_assets.php`
7. `imagen/prizes/metaquest3s.png`
8. `imagen/prizes/beats-solo4.png`
9. `public/assets/flowerflow/landing/prize-metaquest3s-480.webp`
10. `public/assets/flowerflow/landing/prize-metaquest3s-960.webp`
11. `public/assets/flowerflow/landing/prize-beats-solo4-320.webp`
12. `public/assets/flowerflow/landing/prize-beats-solo4-640.webp`
13. `docs/adr/0017-public-voting-google-forms.md`
14. `docs/requirements-traceability.md`
15. `docs/template-overrides.md`
16. `docs/36-voting-prizes-2026-09-12.md`

## Imágenes y reproducción

Los PNG se copiaron desde `/mnt/c/Users/carlo/Downloads/` y se comprobó igualdad SHA-256 con las copias en `imagen/prizes/`. No se sobrescribió ningún original previo. Derivados con GD/WebP existentes, calidad 86, escalado proporcional y canal alfa conservado; sin recortes ni retoque del producto. Reproducción: `php scripts/build_voting_prize_assets.php`.

| Archivo | Dimensiones | Bytes | SHA-256 |
|---|---|---|---|
| `imagen/prizes/metaquest3s.png` | 1810×976 | 489885 | `67f546c7011ed8603f15f8f5483ee92704a920b11f9a4c4d0b24145c9ac8bf3c` |
| `imagen/prizes/beats-solo4.png` | 892×1284 | 331766 | `e4e5dbb1188b87185f15ee6185d3b44c843b9de3e349936187c7b0e91deb07cc` |
| `prize-metaquest3s-480.webp` | 480×259 | 8926 | `8438b2cf62903927c83acf856a1a5124bf3629a050937cc2bdb3ce40aed69f5f` |
| `prize-metaquest3s-960.webp` | 960×518 | 23796 | `338f8203068c1e15e820cf85967d47c084c0a02b4080eba20ada1e3ad3def5fd` |
| `prize-beats-solo4-320.webp` | 320×461 | 4772 | `5cfd46908c42c5c279f7454dfe972a890e027f3237b1d5e47ffb92770ca0c583` |
| `prize-beats-solo4-640.webp` | 640×921 | 14122 | `1ae5d56391cf0e915bfba726ca4a6471f23b3d131ad91272c2a0aa15f3c13d96` |

Las dos versiones web mayores suman 37918 bytes. Los originales no se cargan desde el landing.

## Validación

Actualización acotada a contenido, imágenes y CSS. Se ejecutan las pruebas relacionadas y se conserva como histórico el gate global del informe 35; no se atribuye aquella suite a este cambio.

| Comando/control | Resultado |
|---|---|
| `FLOWERFLOW_TEST_GUARD_ONLY=true scripts/serve_local_testing.sh` | Base desechable `flowerflow_testing`, usuario `flowerflow_testing_user`, MySQL local |
| `php artisan test --filter='DisposableDatabaseGuardTest\|PublicLandingTest\|ParticipantExperienceRedesignTest\|SecurityAndFlagsTest'` antes de editar | 38 passed, 528 assertions, 29.82 s |
| Mismo filtro después del cambio | 38 passed, 562 assertions, 28.00 s |
| `scripts/build_frontend_production.sh` baseline y final | Ambos verdes; Node 22.23.1, Yarn 1.22.22, Vite 6.4.3. CSS final `app-CIkbKb42.css`; módulo de votación `public-voting-7EE-Cs6c.js` conservado |
| `vendor/bin/pint --test scripts/build_voting_prize_assets.php tests/Feature/PublicLandingTest.php tests/Feature/ParticipantExperienceRedesignTest.php` | Passed |
| `vendor/bin/pint --test` baseline y final | Exit 1 en ambos: sólo el archivo preexistente `video-tutorial/scripts/freeze-time.php`, `fully_qualified_strict_types` |
| Segunda ejecución `php scripts/build_voting_prize_assets.php` y `cmp /tmp/ff-prizes-assets.log /tmp/ff-prizes-assets-reproduced.log` | Exportación reproducible: mismas dimensiones, bytes y hashes de los cuatro WebP |
| `sha256sum imagen/prizes/*.png /mnt/c/Users/carlo/Downloads/beats-solo4.png /mnt/c/Users/carlo/Downloads/metaquest3s.png` | Originales y copias idénticos |
| JSON con `json.loads` sobre `git ls-files '*.json'`; manifest con comprobación de archivos/importaciones | 15 JSON válidos; entradas `resources/css/app.css`, `resources/js/app.js`, `resources/js/pages/public-voting.js`, sin referencias faltantes |
| `git diff --check` | Passed |

Los tests verifican orden/asociación premio–lugar, assets existentes, descripciones, ausencia del aviso pendiente, contenido con/sin convocatoria activa y flags de inscripción/recepción, modal único, URLs y CSP, preservación de documentos y otras pantallas. Logs `/tmp/ff-prizes-{baseline-tests,tests,baseline-build,final-build,assets,baseline-pint}.log`.

## QA de navegador

Chromium local con Playwright CLI, servidor PHP en `127.0.0.1:8017`, `APP_ENV=testing` y base desechable. Se usa el router temporal de pruebas documentado en el informe 35 para que `/documentos` llegue a Laravel; no modifica el servidor original ni valida producción. Las suites que recrean el esquema terminaron antes de iniciar el servidor.

Comandos de QA, con guiones locales ignorados y logs del mismo nombre:

```bash
npx --yes --package @playwright/cli playwright-cli -s=ff-prizes run-code "$(cat output/playwright/ff-prizes-responsive.js)"
npx --yes --package @playwright/cli playwright-cli -s=ff-prizes run-code "$(cat output/playwright/ff-prizes-interactions.js)"
npx --yes --package @playwright/cli playwright-cli -s=ff-prizes run-code "$(cat output/playwright/ff-prizes-captures.js)"
```

Resultados:

- 1440×1000 y 768×1024: dos tarjetas alineadas; 390×844 y 320×780: primer lugar sobre segundo lugar. Sin desbordamiento horizontal, fotos cargadas y completas, `object-fit: contain`, texto legible y orden de premios correcto. Cero excepciones JavaScript de la aplicación.
- El botón Votar del banner permanece visible a 383.42–436.19 px en 390×844. El título de ganadores queda debajo del encabezado al seguir el ancla en las cuatro dimensiones.
- Doce controles de interacción verdes: iframe único/diferido, menú móvil, enlace de ganadores por teclado, Escape/retorno del foco, reapertura conservando estado, alternativa Google, premios y cuatro enlaces directos sin JavaScript, documentos/acceso HTTP 200 y ajuste de tarjetas con zoom CSS al 200 % en viewport 720×500. Este último es una comprobación de layout ampliado, no zoom nativo del navegador.
- El iframe se sustituyó por contenido sintético exclusivamente en la interceptación de QA para comprobar continuidad del modal: una petición al abrir y ninguna adicional al reabrir. No se introdujeron credenciales ni votos; no se afirma haber validado acceso o envío en Google en esta actualización.
- Inspección visual de capturas de escritorio, tablet y móvil completada. Para las capturas aisladas de la sección se desactiva temporalmente la posición sticky del encabezado mediante la opción `style` de screenshot, evitando que tape tarjetas más altas que el viewport; las mediciones de layout/anclas y capturas completas conservan el encabezado real. Ningún cambio de producto para las capturas.

Evidencias: [premios en escritorio](../output/playwright/ff-prizes-1440.png), [tablet](../output/playwright/ff-prizes-768.png), [móvil 390](../output/playwright/ff-prizes-390.png), [móvil 320](../output/playwright/ff-prizes-320.png), [landing completo en escritorio](../output/playwright/ff-prizes-full-1440.png), [landing completo en móvil](../output/playwright/ff-prizes-full-390.png), [banner móvil](../output/playwright/ff-prizes-hero-390.png), [zoom CSS 200 %](../output/playwright/ff-prizes-css-zoom-200.png). Disponibles en el workspace local; `output/` no forma parte del release.

Entrega local: inventario de 16 archivos comprobado contra Git, HEAD conservado en `cc2bafc`, sin cambios en staging. Sesión Chromium `ff-prizes` y servidor temporal cerrados al terminar; ningún commit, push ni despliegue.

## Rollback y pendientes

Revertir sólo los archivos de esta actualización y reconstruir assets. Sin migraciones ni datos que revertir; conservar la integración previa de votación. Gate global histórico pendiente por Pint preexistente y UAT de Google pendiente del propietario. La confirmación de premios resuelve el texto pendiente del landing, no la revisión jurídica de otros documentos/pantallas.
