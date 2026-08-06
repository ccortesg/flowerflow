# Hardening del entorno MySQL de pruebas

## Propósito y resultado observable

Hacer que la suite automatizada de Flower Flow use exclusivamente la base MySQL local desechable `flowerflow_testing` y falle antes de ejecutar `RefreshDatabase` si el ambiente, driver, host o nombre de base no coinciden con el contrato aprobado. Al terminar, la suite podrá ejecutarse con datos sintéticos sin alterar la base local principal `flowerflow`.

## Estado y alcance

- Milestone local/test autorizado por el propietario el 2026-08-05.
- Incluye configuración PHPUnit, guard fail-closed, pruebas positivas/negativas, ejecución de la suite y documentación de evidencia.
- Incluye formato mecánico de PHP activo sólo si Pint continúa bloqueando el gate.
- Excluye cambios a dependencias o lockfiles, Fase 02B, activos jurídicos/gráficos, Apache, AWS y producción.
- La contraseña permanece únicamente en `.env` ignorado y no se copia a configuración, pruebas, comandos, documentación o salida.
- `flowerflow_testing` es desechable y sólo contendrá fixtures sintéticos.

## Contexto y contratos

- Checkout canónico: `/home/ccortesg/workspace/flowerflow`.
- Rama/HEAD iniciales: `codex/phase-02-admissibility-review` en `d293fee63843beb64899498ca169e114e7695d6e`.
- MySQL 8.0.46 escucha en loopback.
- La conexión real a `flowerflow_testing` fue confirmada; el esquema inició con cero tablas.
- Trece archivos Feature usan `RefreshDatabase`; el guard debe ejecutarse después de bootstrap/configuración y antes de que Laravel inicialice traits de prueba.
- Invariantes: `APP_ENV=testing`, driver `mysql`, host local y base exacta `flowerflow_testing`.

## Plan

1. Fijar en `phpunit.xml` únicamente los valores no secretos del entorno MySQL de pruebas.
2. Incorporar un guard reutilizable en `tests/Support` y llamarlo desde `Tests\TestCase::createApplication()` antes de `setUpTraits()`.
3. Añadir pruebas unitarias positivas y negativas del guard.
4. Probar el rechazo con configuración peligrosa sin conectar ni modificar una base.
5. Ejecutar la suite completa sobre `flowerflow_testing` y comprobar que la base efectiva es la autorizada.
6. Ejecutar Pint acotado, Composer, auditorías, build y `git diff --check` según alcance seguro.
7. Actualizar handoff, QA, desarrollo local, riesgo y estado con evidencia actual diferenciada.

## Validación

```bash
vendor/bin/phpunit --do-not-cache-result tests/Unit/DisposableDatabaseGuardTest.php
php artisan test
vendor/bin/pint --test app bootstrap config database routes tests
composer validate --strict --no-check-publish
composer check-platform-reqs --no-dev
composer audit --locked --no-interaction --format=plain
corepack yarn audit --groups dependencies --level moderate --json
scripts/build_frontend_production.sh
git diff --check
```

La suite sólo se ejecutará después de demostrar el guard. Auditorías o build con red/escritura se ejecutarán sin actualizar dependencias y sus fallos se reportarán; no autorizan lockfile changes.

## Despliegue y rollback

- No hay despliegue ni cambios de producción.
- Rollback: retirar las variables PHPUnit y el guard/pruebas/documentación añadidos. No se conserva ningún dato de prueba que deba migrarse.
- No se elimina automáticamente `flowerflow_testing`; es un recurso local aprobado por el propietario.

## Registro vivo

- [x] 2026-08-05 20:16 MST — Grant y conexión a `flowerflow_testing` verificados sin mostrar secretos; esquema vacío y permiso explícito confirmado.
- [x] 2026-08-05 20:16 MST — Baseline: rama/HEAD correctos; cambios documentales preexistentes preservados; suite completa aún bloqueada por falta de aislamiento PHPUnit.
- [x] 2026-08-05 20:18 MST — `phpunit.xml` fuerza valores no secretos de MySQL local/`flowerflow_testing`; guard previo a traits y seis pruebas unitarias negativas/positivas agregados.
- [x] 2026-08-05 20:19 MST — Feature de control pasó 6 pruebas/61 aserciones; huella principal idéntica antes/después.
- [x] 2026-08-05 20:20 MST — Suite completa verde: 78 pruebas/702 aserciones; huella principal volvió a coincidir exactamente.
- [x] 2026-08-05 20:22 MST — Pint quedó verde mediante formato activo y exclusión explícita de `_referencia/`/cache; Composer validate/platform y build Vite pasaron.
- [!] 2026-08-05 20:22 MST — Auditorías reproducen 5 advisories Composer y 86 Yarn (7 bajos, 37 moderados, 38 altos, 4 críticos). El hardening de test está terminado, pero el gate integral de seguridad no puede declararse verde sin milestone de dependencias o aceptación formal.
