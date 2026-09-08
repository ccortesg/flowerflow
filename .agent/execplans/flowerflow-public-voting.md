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
