# Ampliación del plazo de recepción al 23 de agosto de 2026

Este ExecPlan es un documento vivo y se rige por `.agent/PLANS.md`. La autorización proviene de la solicitud expresa del propietario del 2026-08-17. El trabajo es local/test: no autoriza stage, commit, push, acceso a AWS ni despliegue productivo.

> **Adenda 2026-08-18:** la exclusión jurídica que rigió este milestone quedó superada por una autorización posterior e independiente. Los PDF v1.1, su catálogo, migración, vínculos y validaciones se documentan en `.agent/execplans/flowerflow-legal-v1-1-local-release-candidate.md`; v1.0 permanece únicamente como evidencia histórica. Esta adenda no reescribe las decisiones ni el alcance que aplicaron al ejecutar originalmente este plan.

## Purpose / Big Picture

Ampliar el cierre inclusivo de `hermosillo-florece-2026` desde el 15 de agosto de 2026 hasta el 23 de agosto de 2026 a las 23:59:59 en `America/Hermosillo`, equivalente a `2026-08-24 06:59:59 UTC`. Backend, base de datos, portada, dashboard, configuración, pruebas y documentación operativa deben anunciar y aplicar el mismo instante.

## Status and Scope

- Rama: `codex/submission-deadline-extension`.
- Base: `3c0f278` de `codex/panel-proposals-export`.
- Incluye configuración local/ejemplo, valor predeterminado, seeder, migración de datos, portada, pruebas deterministas, trazabilidad y documentación operativa.
- Excluye modificar o reemplazar los PDF jurídicos de `formatos/` y `public/documentos/2026/`, así como sus registros de aceptación.
- La discrepancia entre los PDF vigentes y el nuevo cierre queda documentada como riesgo residual y no se presenta como resuelta.
- Producción, EC2, correo masivo, stage, commit y push permanecen fuera de alcance.

## Context and Invariants

- Laravel/MySQL persisten instantes en UTC y presentan reglas de negocio en `America/Hermosillo`.
- El cierre es inclusivo: se acepta hasta `2026-08-23 23:59:59` y se rechaza desde `2026-08-24 00:00:00` en Hermosillo.
- `FLOWERFLOW_SUBMISSIONS_ENABLED` sigue siendo el interruptor independiente; esta ampliación no lo activa por sí sola.
- La configuración gobierna middleware y envío final; `competitions.closes_at` alimenta el dashboard y el snapshot. Ambos valores deben coincidir.
- La migración sólo modifica la convocatoria conocida y no debe sobrescribir una fecha distinta establecida posteriormente.

## Plan of Work

1. Verificar Git y la base desechable; ejecutar baseline de pruebas, Pint y build.
2. Actualizar configuración, `.env` local, `.env.example`, seeder y portada.
3. Añadir una migración idempotente que cambie el cierre UTC sólo cuando encuentre el valor anterior esperado.
4. Hacer deterministas las pruebas que requieren una ventana abierta y actualizar la frontera del nuevo cierre.
5. Actualizar la documentación vigente y registrar como pendiente la regularización de los PDF excluidos.
6. Probar migración forward/rollback/forward en `flowerflow_testing` y ejecutar los gates completos.

## Validation

Sólo contra MySQL `flowerflow_testing`/`flowerflow_testing_user`, previa comprobación de `SELECT DATABASE()`:

    php artisan test
    vendor/bin/pint --test
    composer validate --strict --no-check-publish
    composer check-platform-reqs --no-dev
    composer audit --locked
    corepack yarn audit --groups dependencies --level moderate
    scripts/build_frontend_production.sh
    php artisan route:list
    git diff --check

Además, probar el instante anterior, exacto y posterior al cierre, paridad config/base, idempotencia del seeder y migración forward/rollback/forward. Verificar visualmente que la landing y el dashboard muestran 23 de agosto, 23:59, tiempo de Hermosillo.

## Deployment and Rollback

No se despliega en este milestone. Una release posterior debe verificar aprobación, backup/restore, UAT, `.env` productivo, migración, regeneración de caché, reinicio exclusivo de workers Flower Flow y smoke autenticado. El rollback de datos restaura el 15 de agosto únicamente si la fila conserva exactamente el nuevo valor; no elimina propuestas ni aceptaciones.

## Progress

- [x] 2026-08-17 MST — Repositorio canónico y árbol limpio verificados; rama local creada desde `3c0f278`.
- [x] 2026-08-17 MST — Base de pruebas confirmada: `testing`, MySQL, `flowerflow_testing`, usuario dedicado y sesión `+00:00`.
- [!] 2026-08-17 MST — Baseline: Pint y build verdes; suite con 84 pruebas verdes y 18 fallos porque el reloj real ya estaba después del cierre anterior. La causa común es el middleware de recepción, no el cambio nuevo.
- [x] 2026-08-17 13:01 MST — Configuración, `.env` local/ejemplo, seeder, migración, portada, pruebas y documentación operativa alineadas; ningún PDF fue modificado.
- [x] 2026-08-17 13:04 MST — Ciclo real sobre `flowerflow_testing`: fresh+seed `2026-08-24 06:59:59 UTC`, rollback `2026-08-16 06:59:59 UTC` y forward nuevamente al valor ampliado.
- [!] 2026-08-17 13:05 MST — La base local `flowerflow` conserva cuatro migraciones históricas pendientes. Para no mezclar módulos se ejecutó sólo la migración de plazo por ruta; config y fila persistida quedaron en paridad, `2026-08-23 23:59:59 America/Hermosillo`.
- [x] 2026-08-17 13:08 MST — Gate final: 104 pruebas/986 aserciones, Pint, Composer validate/platform/audit, build Vite, 66 rutas y `git diff --check` verdes. Yarn conserva un advisory bajo conocido de Quill 2.0.3 sin fix disponible.
- [!] 2026-08-17 13:11 MST — Firefox real confirmó portada y recepción abierta en escritorio y 390×844, sin consola; el login sintético devolvió 419 en el servidor de prueba. Dashboard permanece cubierto por Feature test y no se modificó autenticación fuera de alcance.

## Decision Log

- Decision: conservar segundos inclusivos (`23:59:59`) aunque la interfaz muestre `23:59 horas`.
  Rationale: mantiene el contrato temporal existente y bloquea desde el segundo siguiente.
  Date/Author: 2026-08-17 / propietario y Codex.

- Decision: no modificar los PDF jurídicos.
  Rationale: exclusión expresa del propietario; la divergencia queda registrada como riesgo pendiente.
  Date/Author: 2026-08-17 / propietario.

## Outcomes & Retrospective

El cierre técnico quedó ampliado y sincronizado en configuración, base, seeder, portada, dashboard derivado y documentación. La migración es idempotente, reversible y falla cerrada si encuentra una fecha inesperada. Las pruebas que requieren recepción abierta usan ahora un reloj fijo y no volverán a depender de la fecha calendario en que se ejecute la suite.

No se modificaron los PDF jurídicos ni sus aceptaciones. La contradicción entre esos documentos y el cierre técnico queda como riesgo alto explícito. No hubo stage, commit, push, acceso AWS ni despliegue productivo.
