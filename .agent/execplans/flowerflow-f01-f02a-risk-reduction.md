# Reducción de riesgos residuales de Fases 01/02A

Este ExecPlan es un documento vivo. Las secciones `Progress`, `Surprises & Discoveries`, `Decision Log` y `Outcomes & Retrospective` deben mantenerse actualizadas durante la ejecución.

Este documento se rige por `.agent/PLANS.md`. Su alcance es exclusivamente local y de pruebas. No autoriza push, pull request, acceso a EC2 ni despliegue.

## Purpose / Big Picture

Preparar tres releases pequeños, reversibles y sin migraciones sobre la línea productiva exacta `baff7892f886af3fd4e42132c686620f1ae76d91`, sin incorporar la definición documental de Fase 02B. La primera release corrige advisories PHP sin alterar funcionalidad; la segunda protege archivos, transiciones administrativas, permisos y 2FA; la tercera reduce el grafo frontend y endurece CSP/HSTS. Cada release debe poder probarse y revertirse de forma independiente.

El éxito observable local exige conservar los nueve flujos productivos protegidos: landing, registro, acceso participante, dashboard participante, registro/edición/envío de propuesta, acceso administrador, dashboard administrador, listado de propuestas y detalle/descarga. Fase 02A debe seguir apagada por defecto y sólo encenderse explícitamente en pruebas.

## Scope and Guardrails

- Rama de implementación: `codex/f01-f02a-risk-reduction`.
- Base inmutable: `baff7892f886af3fd4e42132c686620f1ae76d91`.
- No producción, push, PR, AWS, Apache real, servicios compartidos, SMTP real ni datos reales.
- No migraciones, seeders productivos, cambios de esquema, estados históricos ni reglas de negocio.
- La base destructible admitida es sólo MySQL `flowerflow_testing`, en loopback y con el usuario `flowerflow_testing_user`.
- La contraseña vive sólo en `.env.testing`, ignorado; nunca se imprime, documenta ni versiona.
- No Fase 02B, jueces, evaluación, resultados, ganadores, antimalware, ARCO completo ni cambios de retención.
- Los artefactos generados de navegador viven en `output/playwright/` y no se versionan.

## Progress

- [x] (2026-08-06) Confirmada la base remota exacta `baff7892…` y creada la rama local sin merge.
- [x] (2026-08-06) Confirmado checkpoint limpio del trabajo anterior en `f0e9f28`; no se reescribió.
- [x] (2026-08-06) Preservado y retirado el worktree `codex/ui-public-landing-v2` mediante patch binario, tar de untracked y bundle verificado.
- [x] (2026-08-06) Portado únicamente el hardening MySQL/test revisado y creado el gate local en `2d18f99`/`723bc71`.
- [x] (2026-08-06) Release 1 consolidada en `84e34d1` con Guzzle 7.15.3 y sólo dos transitivas indispensables.
- [x] (2026-08-06) Release 2 consolidada en `f530e6a` con integridad de archivos, auditor read-only, estados, throttle, contratos y 2FA opcional.
- [x] (2026-08-06) Release 3 consolidada en `7f660b0` con grafo Vite mínimo, iconos deterministas, CSP Report-Only/HSTS y nonces completos.
- [x] (2026-08-06) Gate estático/dependencias/build y QA pública real ejecutados; documentación actualizada.
- [ ] Propietario: cargar directamente el secreto en `.env.testing`, ejecutar suite Feature/MySQL y completar QA autenticada. Este punto permanece bloqueante para liberar, no para conservar la implementación local.

## Surprises & Discoveries

- Observation: el worktree antiguo conservaba su archivo `.git` apuntando al checkout Windows, mientras WSL tenía metadatos copiados.
  Evidence: la validación de `git worktree remove` en WSL rechazó el retiro; el repositorio Windows sí era su administrador efectivo.
  Resolution: se archivaron primero todos los cambios; el retiro se ejecutó desde el repositorio administrador y después se podó sólo la metadata obsoleta de WSL.

- Observation: el árbol que antes se había observado sucio ya estaba consolidado en `f0e9f28` al iniciar esta ejecución.
  Evidence: `git status --porcelain=v2` vacío y `git show --stat f0e9f28` contiene el hardening/auditoría.
  Resolution: se usa como checkpoint recuperable, sin cherry-pick completo porque incluye documentación de Fase 02B y cambios mecánicos ajenos al hardening.

- Observation: el wrapper Playwright tenía finales CRLF y la distribución Chrome del sistema no estaba instalada.
  Evidence: el wrapper falló inicialmente al ejecutar y Playwright buscó `/opt/google/chrome/chrome`.
  Resolution: se ejecutó una copia efímera normalizada por stream y Chrome for Testing desde el caché del usuario; no se añadió dependencia al repositorio ni se alteró Chrome del sistema.

- Observation: la primera propuesta de resolución global de `picomatch` era incompatible con el rango de Vite.
  Evidence: Vite requiere la línea 4.x y `vite-plugin-full-reload` admite 2.3.2.
  Resolution: se fijaron resoluciones anidadas compatibles: 4.0.5 para Vite/tinyglobby y 2.3.2 para full-reload.

- Observation: Yarn desaconseja `lodash-es` 4.18.0 y publica 4.18.1 como corrección posterior.
  Resolution: se fijó 4.18.1, sin cambiar majors de runtime.

- Observation: cuatro scripts/estilos inline heredados y un `onclick` no habrían cumplido la CSP estricta.
  Evidence: inventario de tags Blade antes de promover la política.
  Resolution: se añadieron nonces a todos los bloques inline y el logout se convirtió en formulario POST sin handler inline. Playwright confirmó cero bloques inline sin nonce y cero mensajes de consola en login.

- Observation: `/documentos` devuelve 404 bajo `php artisan serve` porque existe el directorio físico `public/documentos/`.
  Resolution: se documentó como limitación del servidor incorporado; la descarga debe validarse en Apache mediante smoke y no se infiere un defecto productivo.

## Decision Log

- Decision: implementar desde el commit productivo, no desde el HEAD de Fase 02A.
  Rationale: evita introducir Fase 02B documental y hace que cada diff de release sea auditable contra producción.
  Date/Author: 2026-08-06 / propietario y Codex.

- Decision: mantener tres commits de release independientes, precedidos por un commit de preparación local si resulta necesario.
  Rationale: Composer, backend y frontend tienen perfiles de riesgo y rollback distintos.
  Date/Author: 2026-08-06 / propietario y Codex.

- Decision: no aceptar credenciales desde `.env` ni desde `DB_URL` durante pruebas destructivas.
  Rationale: el guard debe fallar antes de `RefreshDatabase` si no se usa la cuenta exclusiva.
  Date/Author: 2026-08-06 / propietario y Codex.

- Decision: el auditor de storage será estrictamente de sólo lectura.
  Rationale: detectar huérfanos no autoriza borrarlos y la comparación debe ser revisable.
  Date/Author: 2026-08-06 / propietario y Codex.

- Decision: CSP estricta se publica primero sólo como Report-Only y HSTS inicia en 86400 segundos únicamente bajo HTTPS productivo.
  Rationale: reduce el riesgo de romper assets/dinámica antes de enforcement.
  Date/Author: 2026-08-06 / propietario y Codex.

## Context and Orientation

`routes/web.php` define los flujos públicos, del participante y del panel. Las operaciones de propuestas viven principalmente en `app/Http/Controllers/Participant/SubmissionController.php` y `app/Support/SubmissionFileStore.php`. Admisibilidad usa `app/Actions/EligibilityReviewWorkflow.php`, controladores bajo `app/Http/Controllers/Panel` y Policies. Fortify está configurado en `config/fortify.php`; `/panel/cuenta` es la superficie de 2FA. Los headers se aplican en el middleware de seguridad registrado desde `bootstrap/app.php`. Vite parte de `vite.config.js`, `vite.icons.plugin.js`, `resources/css/app.css` y `resources/js/app.js`.

## Plan of Work

### Milestone 0 — Preparación local y guard MySQL

Portar desde `f0e9f28` sólo `pint.json`, el contrato PHPUnit y el guard. Fijar `DB_USERNAME=flowerflow_testing_user`, exigir username exacto y añadir casos negativos para cuenta principal y usuario vacío. Crear `scripts/quality_gate_local.sh` con `set -euo pipefail`, sin volcar ambiente, que ejecute el gate aprobado en orden y falle al primer error. Crear `.env.testing` ignorado sin versionar secretos; si la contraseña no puede transferirse sin exponerla, dejar explícito el único paso local pendiente antes de pruebas destructivas.

### Milestone 1 — Release 1: Composer

Ejecutar `composer update guzzlehttp/guzzle --with-dependencies --minimal-changes`, fijar 7.15.3 y revisar el lock paquete por paquete. El commit no puede modificar rutas, vistas, migraciones, configuración de aplicación, frontend ni otro paquete no indispensable. Validar Composer, plataforma, audit y suite.

### Milestone 2 — Release 2: integridad y contratos

Modificar `SubmissionFileStore` para devolver `SubmissionFile`, normalizar `/` y `\\`, y compensar el binario si falla el registro. En uploads múltiples, recopilar las rutas persistidas y borrarlas si la transacción completa revierte. En borrado, confirmar evento y fila SQL antes de borrar físicamente mediante callback posterior al commit; si falla, registrar el orphan sin falsear el éxito SQL.

Crear el comando `flowerflow:storage-audit` con `--disk` y `--json`, sin mutaciones. Comparará referencias de archivos privados de propuestas/residencia contra el disco y reportará `missing` y `orphaned` de forma determinista.

Restringir `start` a `pending -> in_review`, mantener idempotencia en `in_review` y rechazar aclaración/finales. Definir `panel-mutations` a 10/minuto por usuario y nombre de ruta y aplicarlo a toda mutación de admisibilidad. Añadir contratos positivos/negativos para listado, detalle y descargas.

Completar la UI opcional de Fortify 2FA en `/panel/cuenta`: estado pendiente, QR, confirmación TOTP, códigos, regeneración y desactivación con confirmación de contraseña. Probar ambos estados del feature flag sin cambiar su default.

### Milestone 3 — Release 3: frontend y headers

Reducir Vite a `resources/css/app.css` y `resources/js/app.js`; retirar globs/demo/HTML plugin. Eliminar dependencias no alcanzables y Axios/bootstrap.js sólo tras confirmar ausencia de llamadas. Mantener Bootstrap, Popper y Quill en runtime y Vite/Laravel Vite/Iconify JSON/Utils en build. Generar CSS determinista sólo para iconos `ri-*` usados mediante `--check`, sin reescribir durante el gate.

Resolver `lodash-es` y `picomatch` sin majors prohibidos y exigir cero moderadas/altas/críticas. Añadir nonce por respuesta, CSP estricta Report-Only junto a enforcement vigente y HSTS gradual sólo para `production`+HTTPS. Probar headers, nonce y ausencia fuera de sus condiciones.

## Concrete Steps

Todos los comandos se ejecutan desde `/home/ccortesg/workspace/flowerflow`.

    git status --short --branch
    php artisan test
    vendor/bin/pint --test
    composer validate --strict --no-check-publish
    composer check-platform-reqs --no-dev
    composer audit --locked
    corepack yarn audit --groups dependencies --level moderate
    scripts/build_frontend_production.sh
    php artisan route:list
    git diff --check

Para QA de navegador se seguirá la skill Playwright, con servidor local, sesión nombrada y artefactos en `output/playwright/`. Se validarán 360, 768 y 1440 px, teclado, foco, zoom 200 %, consola, descargas y comparación de las tres páginas públicas autorizadas.

## Validation and Acceptance

Cada release debe aprobar el gate de forma independiente. Las pruebas de archivos deben cubrir: fallo de persistencia tras upload, rollback después de varios uploads, borrado SQL exitoso con fallo de storage y reporte missing/orphan. Las pruebas de autorización deben cubrir admin/reviewer y rechazar participante ajeno, usuario sin permiso, archivo de otra propuesta y URL directa. Las de estado deben cubrir pendiente, idempotencia, aclaración/finales y throttle. Las de 2FA deben cubrir configuración incompleta, TOTP válido/inválido, códigos, regeneración y desactivación. Las fechas se verifican antes, exactamente en y después del cierre en `America/Hermosillo`.

El manifest final debe contener sólo las dos entradas y ningún chunk de demo. Ningún asset crítico crecerá más de 5 % respecto a la base sin una justificación registrada.

## Idempotence and Recovery

Los comandos de auditoría y `--check` son repetibles y de sólo lectura. `.env.testing` nunca se versiona. Si una release falla, se corrige dentro de su milestone antes del commit; no se oculta ni se mezcla con la siguiente. Los commits previos permanecen intactos y permiten comparar o revertir código sin datos. No se usa force-push, reset destructivo ni limpieza global.

## Artifacts and Notes

Preservación externa del worktree:

    /home/ccortesg/workspace/flowerflow-worktree-archive/2026-08-06-ui-public-landing-v2/

Incluye `tracked-changes.patch`, `untracked-files.tar.gz`, `ui-public-landing-v2.bundle` y `README.md` con hashes SHA-256.

## Outcomes & Retrospective

La implementación local quedó separada en tres releases sin migraciones: `84e34d1`, `f530e6a` y `7f660b0`. Composer quedó sin advisories. Yarn audita diez dependencias de producción con cero vulnerabilidades moderadas, altas o críticas y un advisory bajo de Quill 2.0.3 sin fix. El manifest pasó de 874 entradas y aproximadamente 19.1 MB a dos entradas y 678,955 bytes; el único JS consolidado crece frente al entrypoint anterior porque ahora contiene Bootstrap/Popper/Quill, pero el grafo total y los assets demo se reducen de forma material. El CSS de 96 iconos conservó el mismo SHA-256 antes y después del build.

Pasaron 8 pruebas unitarias del guard (y el sanitizador dentro de la suite completa), Pint, Composer validate/platform/audit, Yarn audit bajo el umbral aprobado, build Vite, rutas y `git diff --check`. La ejecución canónica terminó con 9 pruebas unitarias verdes y 81 Feature fallidas antes de probar lógica, todas por la misma denegación de MySQL sin password. La QA Playwright comparó landing, registro y login local/productivo en 360, 768 y 1440 px; validó reflow, foco, teclado, skip link, zoom 200 %, nonces y consola limpia. Sólo se realizaron GET públicos contra producción.

La suite Feature no se declara verde: MySQL rechazó de forma segura al usuario dedicado porque `.env.testing` mantiene el password vacío. No hubo migraciones ni acceso a la base principal. Hasta que el propietario cargue el secreto local y ejecute `scripts/quality_gate_local.sh`, permanecen pendientes la regresión autenticada, los contratos de archivos/transacciones/IDOR/2FA y ambos estados de Fase 02A.

No hubo push, PR, acceso a EC2, despliegue o modificación productiva. El runbook posterior al cierre está en `docs/15-risk-reduction-release-runbook.md`; backup/restore, preflight y smoke de las siete aplicaciones siguen siendo responsabilidad y evidencia externa del propietario.
