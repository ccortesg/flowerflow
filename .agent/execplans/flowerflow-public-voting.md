# Banner público de votación y Google Forms

## Propósito y autorización

El propietario aprobó el plan completo el 2026-09-08: adaptar el flyer a web, cambiar banner/CTA del encabezado/CTA final y abrir un único modal de votación. Se omite «Próximamente». Se conserva el requisito de cuenta de Google y una alternativa externa siempre visible. Sólo local/test, sin stage, commit, push ni despliegue.

## Contexto y alcance

- Checkout: `/home/ccortesg/workspace/flowerflow`; rama existente `codex/submission-deadline-extension`, HEAD inicial `3d2b43c`; árbol inicialmente limpio.
- Laravel 12, Blade, Bootstrap 5.3.6, Vite 6.4.3 y Node 22.23.1/Yarn 1.22.22 existentes. Leer con `.agent/PLANS.md`, ADR-0001 y ADR-0017.
- Se conservan categorías, proceso, requisitos, premio inferior, FAQ, PDF y acceso a cuentas. No se cambian reglas de recepción, evaluación, datos, rutas ni roles.
- La ilustración deriva del flyer autorizado; sus textos no se rasterizan en el hero. Originales y hashes se conservan; los WebP se publican reproduciblemente.
- `PENDING`: UAT con sesión real de Google por el propietario; no se automatizan credenciales ni se envían votos. El formulario observado exige sesión y contiene «Option 1»; no se cambia su configuración/contenido.

## Contratos y pasos

1. Baseline y guard MySQL exclusivamente `flowerflow_testing`/`flowerflow_testing_user`; sin `DB_URL` ni datos reales.
2. Banner con «¡La gente elige!», paleta crema/verde/naranja y urna/follaje; CTA antes de la imagen en móvil. Encabezado y cierre comparten el enlace «Votar».
3. Configuración única de URL corta y embed exacto. Enlaces progresivos, módulo Vite por página y modal al final del body; ancho máximo 800 px/90dvh, fullscreen bajo 576 px.
4. Iframe sin `src` inicial; cargar sólo en la primera apertura y conservar instancia. No inferir éxito de carga o voto. Mantener «Abrir en Google», cerrar/restaurar foco y coordinar menú móvil.
5. CSP: permitir `https://docs.google.com` sólo en `frame-src` de la ruta `landing`, en las dos políticas; conservar los demás controles.
6. Pruebas, QA de navegador, trazabilidad, registro de dependencias externas y evidencia.

## Validación

```bash
FLOWERFLOW_TEST_GUARD_ONLY=true scripts/serve_local_testing.sh
php artisan test --filter=DisposableDatabaseGuardTest
php artisan test --filter='PublicLandingTest|SecurityAndFlagsTest'
scripts/quality_gate_local.sh
php scripts/build_voting_assets.php
git diff --check
```

El gate comprende suite completa, Pint, Composer validate/platform/audit, Yarn audit, build desde lock y rutas. Si el gate se detiene por deuda ajena, registrar el fallo y ejecutar sus controles restantes por separado, sin ocultarlo ni declarar el milestone completo. Validar JSON y manifest; nuevas pruebas sólo sobre la base desechable autorizada. No correr dos suites contra esa base simultáneamente.

QA: 320/390/768/1440 px, CTA visible en 390×844, zoom 200 %, teclado/Space/Enter/Escape, foco inicial/restauración, menú abierto, overlay, reapertura sin recarga, movimiento reducido, JS desactivado, iframe bloqueado y enlace externo. Separar la prueba del contenedor con contenido sintético de la conexión real a Google. Guardar evidencia en `output/playwright/`.

## Despliegue y rollback

No hay despliegue en este trabajo. Una release posterior exige aprobación, backup verificado, UAT y rollback probado. El rollback revierte sólo los archivos de esta integración y reconstruye Vite; no cambia datos, PDF ni archivos históricos. Los nuevos originales pueden conservarse fuera del runtime.

## Registro vivo

- [x] 2026-09-08 MST — Reglas, planes y arquitectura revisados; guard de conexión exacta confirmado y 8 pruebas/8 aserciones del guard verdes.
- [x] 2026-09-08 MST — Baseline frontend: build verde, 101 iconos; Composer validate/platform/audit verdes; Yarn con un advisory LOW (exit 2, tolerado por el gate).
- [!] 2026-09-08 MST — Pint global falla antes del cambio únicamente en `video-tutorial/scripts/freeze-time.php` (`fully_qualified_strict_types`). Se preserva el archivo ajeno.
- [x] 2026-09-08 MST — Ilustración generada con ImageGen integrado a partir del flyer: urna, papeleta y follaje, sin texto ni logos. Resultado inspeccionado.
- [x] 2026-09-08 MST — Baseline completa: 244 passed, 1 skipped, 4131 assertions, 1093.52 s; `/tmp/ff-voting-baseline-tests.log`. Las vistas/configuración/CSP originales se conservaron durante esa ejecución.
- [x] 2026-09-08 MST — Banner, cuatro accesos públicos, parcial/modal/JS, configuración y CSP conectados. Secciones entre introducción y CTA final comparadas byte por byte con HEAD: iguales. Build verde; 15 JSON versionados válidos; manifest con tres entradas; Pint de los PHP cambiados verde.
- [x] Implementación y documentación de la integración local.
- [ ] UAT manual del propietario con sesión Google; no se automatizan votos.
- [x] 2026-09-08 MST — Pruebas focalizadas: 21 passed, 298 assertions (19.91 s). URLs, modal diferido/único, flags independientes, página deshabilitada y CSP por ruta verificados.
- [x] 2026-09-08 MST — QA en 320/390/768/1440 px; CTA a 383–436 px en 390×844. 25 comprobaciones de modal/foco/persistencia y 11 de fallos externos/mejora progresiva/ampliación. Una única petición del iframe entre accesos/reaperturas. Zoom CSS 200 % aproximado y reflow 720×500; no se atribuye zoom nativo.
- [x] 2026-09-08 MST — Correcciones visuales de QA: encabezado admite otra fila para evitar colisiones con zoom CSS; cierre alineado al extremo derecho. Build y revisión posterior verdes.
- [x] 2026-09-08 MST — Google real anónimo: embed 401 con pantalla de acceso; alternativa abre el ID exacto y diálogo obligatorio de Google. Sin credenciales ni votos. UAT con cuenta real sigue PENDING del propietario.
- [x] 2026-09-08 MST — Composer validate/platform/audit verdes; Yarn conserva un aviso LOW (exit 2 tolerado). Pint acotado y sintaxis JS verdes. Derivados regenerados con hashes idénticos.
- [!] 2026-09-08 MST — Gate global final: 247 passed, 1 skipped, 4296 assertions (1074.97 s), luego exit 1 por Pint en `video-tutorial/scripts/freeze-time.php`, fixer `fully_qualified_strict_types`, igual al baseline. No se modifica ese archivo ajeno ni se declara verde el gate. El benchmark optativo de 200 MiB continúa omitido como en baseline.
- [x] 2026-09-08 MST — Sesión limpia de navegador: cero errores JS y cero warnings/errors de la aplicación; FAQ, Escape del menú y enlace `#categorias` operativos. Los HTTP 401 de Google están separados de esa métrica.
- [x] 2026-09-08 MST — Build desde lock verde (Node 22.23.1/Yarn 1.22.22), 140 rutas y `git diff --check` verde. Dependencias/lockfiles intactos. Informe enumera exactamente 24 archivos; links locales, JSON y manifest verificados.
- [x] 2026-09-08 MST — Smoke final sobre servidor real, sin interceptar HTML: `/` 200, cuatro accesos, Google diferido, CTA móvil visible y fallback visible ante 401. `/login` 200 sin módulo/modal ni permiso Google. Capturas escritorio/móvil actualizadas. Evidencia: `output/playwright/ff-voting-final-smoke.log`.
- [x] 2026-09-08 MST — Sesión Chromium y servidor temporal de QA cerrados. HEAD conserva `3d2b43c`; sin stage, commit, push ni despliegue.

## Resultado

Implementación y QA de los contratos cambiados realizados en local. Gate global pendiente por Pint preexistente y UAT Google manual pendiente; el milestone no se declara cerrado. No se atribuye a HEAD ni a producción ningún cambio local.

## Adenda aprobada — landing centrado en votación (2026-09-08)

El propietario aprobó ocultar categorías, proceso, requisitos, documentos y FAQ del HTML del landing; adaptar la introducción a votar y comunicar «Los 2 proyectos con más votos serán los ganadores» y «Premio aún por definir». Esta decisión sustituye la conservación de esas secciones del alcance inicial. HEAD inicial de esta adenda: `55bd202` (`Votacion`), árbol limpio; el registro anterior conserva su contexto histórico.

Alcance: vistas propias del landing, enlaces de encabezado/pie/login/layout público y CSS. Conservar modal, URLs, CSP, cuentas, ruta `/documentos` y PDF. No agregar flags, dependencias, endpoints, almacenamiento, conteo, selección automática ni reglas de desempate. Cambiar las referencias a anclas ocultas por `#ganadores`; conservar documentos mediante su ruta propia.

`PENDING`: los PDF y algunas pantallas de cuenta todavía describen el premio anterior. Esta adenda cambia sólo la comunicación solicitada del landing; la reconciliación documental y de otras pantallas requiere una tarea separada. El premio y los desempates no se inventan. UAT con Google sigue a cargo del propietario, sin votos automatizados.

### Pasos y validación de la adenda

1. Registrar baseline y verificar guard de base desechable.
2. Simplificar contenido, adaptar introducción/premio y corregir navegación.
3. Actualizar pruebas del landing y la expectativa anterior de FAQ en experiencia participante.
4. Revisar navegador 320/390/768/1440 px, ampliación 200 %, teclado, menú, modal, reapertura y enlaces sin JS; guardar capturas en `output/playwright/`.
5. Ejecutar gate global y controles restantes si Pint preexistente lo interrumpe; validar JSON/manifest y diff. Actualizar ADR, trazabilidad, overrides e informe de evidencia.

```bash
FLOWERFLOW_TEST_GUARD_ONLY=true scripts/serve_local_testing.sh
php artisan test --filter='DisposableDatabaseGuardTest|PublicLandingTest|SecurityAndFlagsTest|ParticipantExperienceRedesignTest'
scripts/build_frontend_production.sh
vendor/bin/pint --test
scripts/quality_gate_local.sh
git diff --check
```

Sólo ejecutar pruebas contra la base desechable autorizada, sin suites concurrentes. La verificación de navegador sobre servidor de pruebas se realiza fuera de las suites que recrean su esquema. No se requiere regenerar ilustraciones sin cambios. Rollback: revertir únicamente esta adenda y reconstruir assets; no revertir datos ni la integración previa de Google Forms. Sin stage, commit, push ni despliegue.

### Registro de la adenda

- [x] 2026-09-08 MST — Guard confirmado: `testing`, MySQL local, base `flowerflow_testing`, usuario `flowerflow_testing_user`.
- [x] 2026-09-08 MST — Baseline focalizada: 38 pruebas, 476 aserciones, 22.44 s; build desde lock verde. Logs: `/tmp/ff-voting-focus-baseline-tests.log` y `/tmp/ff-voting-focus-baseline-build.log`.
- [!] 2026-09-08 MST — Pint baseline exit 1 sólo por `video-tutorial/scripts/freeze-time.php`, `fully_qualified_strict_types`; `/tmp/ff-voting-focus-baseline-pint.log`.
- [x] 2026-09-08 MST — Implementación: HTML reducido a cuatro secciones; introducción, dos ganadores y premio pendiente; anclas corregidas también en login y layout público. Sin cambios en modal/JS/CSP/controladores/configuración/PDF.
- [x] 2026-09-08 MST — Focalizadas finales: 38 passed, 528 assertions, 21.59 s; build desde lock verde, Pint de los dos tests cambiados verde, 15 JSON válidos y manifest de tres entradas con archivos existentes.
- [x] 2026-09-08 MST — QA responsive en 320/390/768/1440 sin overflow ni anclas rotas; sólo cuatro secciones, ningún bloque oculto en DOM ni imagen del iPad. CTA móvil: 383.42–436.19 px. Capturas `output/playwright/ff-voting-focus-*`.
- [x] 2026-09-08 MST — QA modal: 25 comprobaciones, una sola petición de iframe entre todos los accesos, foco/teclado/overlay/reapertura/menú y movimiento reducido. Otros 11 controles de fallos, enlace sin JS y ampliación; zoom CSS 200 % y viewport equivalente 720×500, no zoom nativo.
- [!] 2026-09-08 MST — `artisan serve` responde 404 en `/documentos`: su router usa `file_exists` y entrega al servidor PHP el directorio público homónimo. Ruta Laravel verde en Feature. QA HTTP continuado con router temporal ignorado `output/playwright/ff-voting-focus-router.php` que sirve archivos con `is_file`; sin editar proveedor/rutas/configuración de producción. Primer intento del guion de navegación también requirió reemplazar el constructor `URL` no expuesto por el runner CLI; sólo cambió el guion de QA.
- [x] 2026-09-08 MST — Navegación final con router temporal: 13 controles verdes; enlaces desde banner/footer/login/layout, menú/Escape, títulos fuera del header fijo y tres PDF con HTTP 200. Cero excepciones JS; consola final vacía.
- [x] 2026-09-08 MST — Google real anónimo: embed exacto 401, alternativa visible; enlace externo llega al ID exacto. Sin credenciales ni votos. Chromium y servidores temporales cerrados antes de suite global.
- [x] Gate global ejecutado; resultado y validaciones complementarias registrados abajo.
- [!] 2026-09-08 MST — Primer lanzamiento del gate final terminó con SIGTERM/143 sin resumen de suite ni fallo de aserción informado; log conservado en `/tmp/ff-voting-focus-quality-gate-interrupted.log`. No se cuenta como validación verde. Reintento completo iniciado en proceso independiente, con log y archivo de exit para verificar su resultado.
- [x] 2026-09-08 MST — Suite completa del reintento: 247 passed, 1 skipped, 4348 assertions, 1044.39 s. Omitida únicamente la prueba optativa de 200 MiB (`FLOWERFLOW_RUN_BULK_PERFORMANCE_TEST`), como en el baseline histórico.
- [!] 2026-09-08 MST — Gate exit 1 en Pint: archivo preexistente `video-tutorial/scripts/freeze-time.php` y línea vacía faltante en el router temporal de QA. Se corrigió únicamente el router propio y se repitió Pint global: sólo persiste el fallo preexistente (`fully_qualified_strict_types`). Log final: `/tmp/ff-voting-focus-final-pint.log`; sin repetir la suite por un cambio de espacio en un helper ajeno a las pruebas.
- [x] 2026-09-08 MST — Controles complementarios: Composer validate/platform/audit verdes; Yarn un LOW (exit 2 tolerado); build final desde lock verde, 140 rutas, 15 JSON y manifest válidos, diff sin errores. Sin cambios de dependencias ni contratos externos.
- [x] 2026-09-08 MST — Informe 35, ADR-0017, trazabilidad y overrides actualizados. Trece archivos versionables de esta adenda; evidencias QA ignoradas por Git. Sin stage, commit, push ni despliegue; HEAD sigue en `55bd202`. Servidores de QA, navegador y suite terminados.
- [ ] Cierre integral del milestone: gate global pendiente por Pint preexistente. UAT Google y coherencia con PDF/otras pantallas continúan PENDING según el alcance aprobado.

### Resultado de la adenda

Landing implementado y validado en local/test, con evidencia reproducible y rollback sin datos. Suite completa y build verdes; no se declara verde el gate ni cerrado el milestone mientras persista Pint global. La ruta `/documentos` fue validada mediante Laravel y router temporal de QA; no se atribuye esa comprobación a `artisan serve` original ni a producción.

## Premios confirmados — 2026-09-12

Autorización directa del propietario: primer lugar Meta Quest 3S; segundo lugar audífonos inalámbricos Beats Solo 4, usando las dos fotografías proporcionadas. Sustituye «Premio aún por definir» del landing. HEAD inicial `cc2bafc` (`Votacion 2`), árbol limpio.

Alcance: sección `#ganadores`, CSS propio, originales en `imagen/prizes/`, derivados WebP reproducibles, expectativas de pruebas y documentación. Mostrar dos tarjetas ordenadas, con fotografías completas, primer lugar antes del segundo; conservar título de los dos proyectos con más votos y el flujo de votación. No se agregan capacidades, precios, especificaciones, reglas de desempate, dependencias ni datos. Continúa PENDING la reconciliación con PDF y otras pantallas; no se edita esa documentación jurídica.

Pasos: baseline → originales/derivados → tarjetas responsive → pruebas/QA → evidencia. Validación ajustada a contenido y assets: pruebas focalizadas existentes de landing, experiencia participante, seguridad/flags y guard; Pint, build, JSON/manifest, hashes reproducibles y navegador en 320/390/768/1440. No se atribuye la suite global histórica a esta actualización. El cierre global anterior sigue condicionado por Pint preexistente y UAT Google.

```bash
FLOWERFLOW_TEST_GUARD_ONLY=true scripts/serve_local_testing.sh
php artisan test --filter='DisposableDatabaseGuardTest|PublicLandingTest|ParticipantExperienceRedesignTest|SecurityAndFlagsTest'
php scripts/build_voting_prize_assets.php
vendor/bin/pint --test scripts/build_voting_prize_assets.php tests/Feature/PublicLandingTest.php tests/Feature/ParticipantExperienceRedesignTest.php
scripts/build_frontend_production.sh
vendor/bin/pint --test
git diff --check
```

Rollback: revertir sólo esta actualización de premios y reconstruir Vite; sin datos ni migraciones. Local/test, sin stage, commit, push ni despliegue.

- [x] 2026-09-12 MST — Fotografías localizadas en Downloads de Windows vía WSL e inspeccionadas. Meta: PNG 1810×976; Beats: PNG 892×1284.
- [x] 2026-09-12 MST — Baseline: guard de base desechable verde, 38 passed/528 assertions/29.82 s y build desde lock verde. Pint global sigue fallando únicamente en `video-tutorial/scripts/freeze-time.php`, igual al antecedente.
- [x] 2026-09-12 MST — Tarjetas ordenadas implementadas, fotografías originales preservadas y cuatro WebP generados. Segunda exportación idéntica byte por byte; copias PNG con SHA-256 igual al de Downloads. Dos versiones web mayores: 37918 bytes.
- [x] 2026-09-12 MST — Focalizadas finales: 38 passed, 562 assertions, 28.00 s. Build desde lock verde (Node 22.23.1, Yarn 1.22.22), Pint de los tres PHP propios verde; 15 JSON y manifest de tres entradas con archivos/importaciones existentes. Diff sin errores.
- [x] 2026-09-12 MST — Navegador Chromium en 320/390/768/1440: fotos completas, orden correcto, columnas responsive y sin overflow ni excepciones JS. Votar visible a 383.42–436.19 px en 390×844. Ancla de ganadores deja el título debajo del encabezado.
- [x] 2026-09-12 MST — Doce controles de continuidad: iframe diferido/único, menú, teclado, Escape y retorno de foco, reapertura sin recargar, alternativa Google, HTML/enlaces sin JS, documentos/login HTTP 200 y tarjetas con zoom CSS al 200 %. Iframe simulado sólo en QA; ningún voto ni credencial. No es UAT de Google ni zoom nativo.
- [x] 2026-09-12 MST — Informe 36, ADR-0017, trazabilidad y overrides actualizados; dieciséis archivos versionables. Capturas y guiones bajo `output/playwright/ff-prizes-*`, ignorados. Validación local de esta actualización concluida.
- [x] 2026-09-12 MST — Servidor y navegador de QA cerrados. Inventario contrastado con Git, HEAD `cc2bafc` conservado y sin stage, commit, push ni despliegue.
- [!] 2026-09-12 MST — Pint global final vuelve a fallar sólo por `video-tutorial/scripts/freeze-time.php`, `fully_qualified_strict_types`; log `/tmp/ff-prizes-final-pint.log`. No se repite la suite/gate global histórico para este ajuste de contenido y assets; no se declara cerrado el milestone integral. UAT Google y reconciliación con PDF/otras pantallas siguen PENDING.
