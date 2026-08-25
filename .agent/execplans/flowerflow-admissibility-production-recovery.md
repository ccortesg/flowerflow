# ExecPlan: recuperación operativa del módulo de admisibilidad

**Estado:** Complete — `GO LOCAL/TEST`; producción no autorizada  
**Creado:** 2026-08-25 (`America/Hermosillo`)  
**Rama:** `codex/submission-deadline-extension`  
**Baseline:** `2e17ca2af9ef67ba7ad1bddcb0793fa7012aa45c`

## Propósito y resultado observable

Corregir de forma reproducible la deriva RBAC que dejó las tablas de admisibilidad instaladas sin sus permisos operativos. Con el flag habilitado, `admin` y `reviewer` podrán abrir el módulo existente y acceder desde una acción contextual de cada propuesta enviada. La acción no admitirá por sí sola ni cambiará `submissions.status`; abrirá el expediente y conservará el flujo de revisión, motivo, confirmación, residencia, auditoría y notificación.

## Estado, alcance y exclusiones

Incluido:

- migración aditiva e idempotente de siete permisos de admisibilidad;
- permisos de operación para `reviewer`, administración completa para `admin` y revocación a otros roles;
- acceso contextual desde la columna `Acciones` sin endpoint de escritura nuevo;
- indicador visible para propuestas enviadas sin expediente;
- pruebas de RBAC, flag, estados, GET sin mutación y compatibilidad del backfill.

Excluido:

- producción, `.env` productivo, AWS, SMTP real, datos reales y ejecución del backfill productivo;
- admisión rápida, cambios directos a `submissions.status` y bypass de aclaraciones/residencia;
- cambios a PDFs, snapshots, folios, archivos privados, evaluaciones o resultados.

`PENDING`: el valor efectivo productivo de `FLOWERFLOW_ADMISSIBILITY_REVIEW_ENABLED`, el SHA desplegado y el conteo autoritativo del dry-run sólo pueden verificarse con autorización operativa separada.

## Contexto, modelo y contratos

- `eligibility_reviews.status` es la autoridad de admisibilidad; `submissions.status` conserva `draft|submitted|withdrawn`.
- El menú y las rutas exigen simultáneamente flag y permiso; ocultar el botón no sustituye middleware/Policy.
- Permisos reviewer: `view admissibility reviews`, `review admissibility`, `request clarification`, `decide admissibility`, `view residency documents`, `download residency documents`.
- Permiso adicional admin: `manage admissibility reviews`.
- `participant`, `judge` y cualquier rol distinto de `reviewer|admin` no reciben esos permisos.
- El backfill existente crea un expediente `pending` por propuesta enviada con versión y es idempotente.

## Plan por pasos

1. Corroborar baseline, guard de `flowerflow_testing` y pruebas dirigidas previas.
2. Añadir migración RBAC correctiva con caché Spatie y `down()` limitado exclusivamente a permisos.
3. Eager-load del expediente y renderizar acción contextual por estado.
4. Añadir pruebas de upgrade sin permisos, roles negativos, flag, estados y GET puro.
5. Ejecutar pruebas dirigidas, suite completa, Pint, Composer, build, rutas, migraciones y diff.
6. Actualizar trazabilidad y handoff sin atribuir cambios a producción.

## Validación

```text
APP_ENV=testing php artisan test tests/Feature/AdmissibilityRecoveryTest.php
APP_ENV=testing php artisan test tests/Feature/AdmissibilityAuthorizationTest.php tests/Feature/AdmissibilityWorkflowTest.php tests/Feature/PanelSubmissionContractTest.php tests/Feature/JudgeRbacIsolationTest.php
APP_ENV=testing php artisan test
vendor/bin/pint --test
composer validate --strict
composer check-platform-reqs
composer audit
yarn audit
scripts/build_frontend_production.sh
php artisan route:list --except-vendor
APP_ENV=testing php artisan migrate:status
git diff --check
```

La UAT visual requiere Firefox local en escritorio, tableta y móvil; si el navegador no está disponible se registra como pendiente y no se presenta como ejecutada.

## Despliegue y rollback

No se despliega en este milestone. En una operación posterior: backup nuevo verificado, SHA/DocumentRoot/flag comprobados, migración, cache RBAC, backfill dry-run/execute/dry-run, activación del flag, regeneración de cachés y smoke por roles. El rollback operativo es apagar el flag y conservar toda evidencia. La migración `down()` retira únicamente los permisos añadidos y nunca elimina expedientes, eventos, aclaraciones, residencia o auditoría.

## Registro vivo

- [x] 2026-08-25 04:24 MST — Baseline limpio y sincronizado: rama, HEAD, upstream y merge-base `2e17ca2af9ef67ba7ad1bddcb0793fa7012aa45c`.
- [x] 2026-08-25 04:25 MST — Guard demostrado sin secretos: `APP_ENV=testing`, MySQL `127.0.0.1`, base/usuario de prueba exactos y `SELECT DATABASE()=flowerflow_testing`.
- [x] 2026-08-25 04:26 MST — Baseline dirigido verde: 22 pruebas, 234 aserciones.
- [x] 2026-08-25 05:05 MST — Migración correctiva implementada: alta idempotente de siete permisos, asignación exacta a `reviewer|admin`, revocación a los demás roles y limpieza de caché Spatie antes/después. `down()` retira exclusivamente RBAC y conserva toda evidencia de admisibilidad.
- [x] 2026-08-25 05:10 MST — Listado de propuestas actualizado con eager-load y acción contextual por estado; el caso enviado sin expediente queda visible y deshabilitado. No se añadió ruta de mutación ni admisión rápida.
- [x] 2026-08-25 05:18 MST — Pruebas nuevas: 5/59; regresión dirigida: 37/402; ocho archivos de regresión reejecutados tras corregir el contrato inicial de `down()`: 22/302.
- [x] 2026-08-25 05:25 MST — Migración forward/rollback/forward verde bajo el guard exacto; la reversión de RBAC preservó expedientes/eventos sintéticos.
- [x] 2026-08-25 05:46 MST — Pint, Composer validate/platform/audit, build Vite y `git diff --check` verdes. `yarn audit` finalizó con código 2 únicamente por el advisory bajo conocido de Quill, sin parche disponible.
- [x] 2026-08-25 05:47 MST — Suite completa verde: 221 pruebas, 2,556 aserciones, 1,185.66 s.
- [x] 2026-08-25 05:57 MST — UAT Firefox sintética verde en 1440×900, 1024×768 y 390×844: menú, etiquetas, estado “Sin expediente”, reflow sin overflow global, scroll interno de tabla, foco/Enter, consola sin warnings y apertura sin mutar 5 expedientes/5 eventos.
- [x] 2026-08-25 06:00 MST — Base `flowerflow_testing` reconstruida con `migrate:fresh --seed` después de UAT; usuarios y propuestas UAT retirados.
- [x] 2026-08-25 06:03 MST — Gates finales: 104 rutas propias, cuatro tareas programadas, 24 migraciones aplicadas, 11 JSON válidos, cero enlaces Markdown relativos rotos, cero patrones de secreto y cero correos nuevos en el diff.

## Decisiones

- [x] 2026-08-25 MST — No ejecutar el seeder completo en producción: sincroniza catálogos y roles fuera de esta corrección.
- [x] 2026-08-25 MST — La acción por fila abre el expediente; nunca resuelve con un clic.
- [x] 2026-08-25 MST — Una propuesta admitida conserva `submissions.status=submitted`.
- [x] 2026-08-25 MST — El rollback de esta migración revierte sólo su catálogo/asignación RBAC; nunca elimina evidencia de Fase 02A.

## Resultado final local

`GO LOCAL/TEST`. La corrección es aditiva, no crea expedientes y deja el backfill como operación explícita. Se verificaron 24 migraciones aplicadas, 104 rutas propias y cuatro tareas programadas. Producción permanece `PENDING / NOT AUTHORIZED`: no se verificaron flag efectivo, SHA, DocumentRoot, cache, permisos ni dry-run en vivo.

Riesgos residuales: la navegación administrativa móvil existente se apila antes del contenido y el skip link desplaza al fragmento, pero Firefox no conserva el foco sobre `<main>`. No impide operar por teclado la nueva acción —es un enlace nativo con `tabIndex=0` y Enter verificado—, no fue introducido por esta corrección y requiere un ajuste transversal del shell en otro alcance. El dump productivo no fue copiado, versionado ni usado como fixture; su custodia/eliminación y posible invalidación de sesiones siguen siendo responsabilidad operativa autorizada por separado.
