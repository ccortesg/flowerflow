# ExecPlan — Fase 02B M1: RBAC y aislamiento base del juez

Estado: `COMPLETE — GO LOCAL/TEST — NO PRODUCTION AUTHORIZATION`

Fecha de inicio: 2026-08-18 (`America/Hermosillo`)

## Propósito y resultado observable

Implementar exclusivamente la base de identidad y autorización de M1: rol `judge`, permiso mínimo y exclusivo para su shell futuro, roles de negocio estrictamente excluyentes, rutas fail-closed, redirección segura y una superficie `/juez` vacía que sólo exista cuando `FLOWERFLOW_EVALUATION_ENABLED` esté habilitado. Al terminar, ninguna cuenta juez, sin rol o multirol podrá caer por descarte en las superficies de participante o panel.

M2–M10 permanecen fuera de alcance: no se crean jueces, perfiles, invitaciones, asignaciones, conflictos, paquetes ciegos, rúbricas, evaluaciones, puntajes, reaperturas, notificaciones, retención, ganadores ni resultados.

## Baseline Git preservado

Comprobado antes de editar:

- `pwd`: `/home/ccortesg/workspace/flowerflow`.
- Git toplevel: `/home/ccortesg/workspace/flowerflow`.
- Rama: `codex/submission-deadline-extension`.
- `HEAD`: `e0fa0455e61afcb38593b62ae0d983f75a92b210`.
- Remoto `origin/codex/submission-deadline-extension`: `e0fa0455e61afcb38593b62ae0d983f75a92b210`.
- Ancestro común: `e0fa0455e61afcb38593b62ae0d983f75a92b210`.
- No había divergencia local/remota en el SHA; sí existía un árbol de trabajo documental sucio procedente de la tarea de diseño anterior.

Archivos preexistentes modificados o no rastreados, que deben preservarse:

- `.agent/execplans/flowerflow-legal-v1-1-local-release-candidate.md`;
- `.agent/execplans/flowerflow-project-status-audit-2026-08-17.md`;
- `.agent/execplans/flowerflow-phase-02b-evaluation-design.md` (no rastreado);
- `docs/01-functional-scope.md`;
- `docs/02-architecture.md`;
- `docs/03-data-model.md`;
- `docs/04-security-privacy.md`;
- `docs/05-ux-ui.md`;
- `docs/06-roadmap-backlog.md`;
- `docs/08-testing-qa.md`;
- `docs/09-risk-register.md`;
- `docs/10-open-questions.md`;
- `docs/11-operations-handoff.md` (no rastreado);
- `docs/16-project-status-by-module-and-role-2026-08-17.md`;
- `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md` (no rastreado);
- `docs/adr/0004-rbac-policies-and-data-separation.md`;
- `docs/adr/0008-phase-02b-evaluation-contract.md` (no rastreado);
- `docs/product-spec.md`;
- `docs/requirements-traceability.md`.

No se hará stage, commit, push, reset, clean, checkout destructivo ni despliegue.

## Decisiones aplicables

- `OWNER_APPROVED`: `participant`, `reviewer`, `judge` y `admin` son roles estrictamente excluyentes.
- `OWNER_APPROVED`: el alta directa de juez por admin pertenece a M2, no a este milestone.
- `OWNER_APPROVED`: 2FA de juez es opcional; M1 no implementa recuperación ni suspensión.
- El permiso exclusivo propuesto para M1 es `access judge workspace`; no autoriza datos ni operaciones de evaluación.
- `FLOWERFLOW_EVALUATION_ENABLED` tendrá valor predeterminado `false` y cerrará `/juez` con 404 cuando esté apagado.
- Descargas actualmente compartidas entre propietarios participantes y personal autorizado conservarán sus Policies, pero incorporarán un gate de rol exacto que excluye `judge`, cuentas sin rol y cuentas multirol.
- La asignación de un rol mediante escritores propios será idempotente para el mismo rol y rechazará cualquier combinación o sustitución implícita.

## Riesgo bloqueante preservado

`P2B-BLOCK-001`: con más de 50 propuestas, cuatro jueces por ocho proyectos aportan 32 cupos, suficientes para ocho propuestas con cuatro evaluaciones; para 51 propuestas se requieren al menos 204 cupos y no existe un quinto juez de reemplazo ante conflicto. Bloquea M4/asignaciones, no M1. M1 no cambia cantidades, capacidad ni reemplazos.

## Plan de implementación

1. Crear un contrato único para roles de negocio y una Action de asignación exclusiva.
2. Incorporar migración aditiva/idempotente para rol `judge` y permiso exclusivo, preservando roles, permisos, usuarios y datos existentes.
3. Corregir el seeder para que `admin` no herede automáticamente el permiso exclusivo y para mantener el conjunto determinístico por rol.
4. Añadir middleware de rol exclusivo y flag de evaluación; registrar aliases.
5. Separar rutas de participante, descargas compartidas, panel y juez con gates explícitos.
6. Corregir redirección y layout para cuentas participant, reviewer/admin, judge y estados inválidos sin clasificación por descarte.
7. Crear vistas accesibles, de marca y sin datos para `/juez` y acceso restringido.
8. Añadir pruebas de matriz positiva/negativa, idempotencia, exclusividad y preservación.
9. Ejecutar guard MySQL exacto antes de cualquier migración, seeder o prueba que toque esquema.
10. Ejecutar gates técnicos, QA real de navegador y reconciliar documentación/trazabilidad.

## Guard MySQL obligatorio

Ejecutado antes de migraciones, seeders y pruebas de esquema. El primer intento de la consulta abortó antes de `SELECT DATABASE()` por usar un alias de fachada no importado; no mutó datos. El segundo intento usó la conexión configurada y probó, sin imprimir contraseña:

| Control | Resultado |
|---|---|
| `APP_ENV` | `testing` |
| Driver | `mysql` |
| Host | `127.0.0.1` |
| Base configurada | `flowerflow_testing` |
| Usuario configurado | `flowerflow_testing_user` |
| `SELECT DATABASE()` | `flowerflow_testing` |
| Decisión | `TEST_DATABASE_GUARD=PASS` |

## Validaciones y resultados

| Gate/comando | Resultado real |
|---|---|
| Guard Artisan/Tinker | `PASS`; seis valores exactos y `SELECT DATABASE()` coincidente, sin secretos. |
| `php artisan migrate --env=testing` | Migración M1 aplicada; 13/13 al cierre. |
| `php artisan migrate:rollback --env=testing --step=1` | Revirtió únicamente M1. |
| Segundo `php artisan migrate --env=testing` | Forward posterior verde. |
| `php artisan test --filter=JudgeRbacIsolationTest` | 6 pruebas, 92 aserciones, sin fallos. |
| Regresión dirigida RBAC/propuestas/panel | 40 pruebas, 393 aserciones, sin fallos. |
| `php artisan test` | 115 pruebas, 1,141 aserciones, sin fallos. |
| `vendor/bin/pint --test` | Verde después de formatear únicamente los archivos M1 afectados. |
| Composer validate/platform/audit | `validate --strict`, `check-platform-reqs --no-dev` y `audit --locked` verdes; cero advisories Composer. |
| `corepack yarn audit --groups dependencies --level moderate` | Cero moderados/altos/críticos; permanece un advisory **bajo** conocido de Quill HTML export/XSS, sin parche disponible. |
| `scripts/build_frontend_production.sh` | Verde con Node 22.23.1/Yarn 1.22.22; 97 iconos, 784 módulos y tres assets Vite. |
| JSON de menús | Ambos archivos válidos mediante `json_decode(..., JSON_THROW_ON_ERROR)`; `jq` no está instalado. |
| Rutas/schedule/migraciones | 43 rutas propias; schedule conserva sólo purge horario de exportaciones; 13/13 migraciones ejecutadas. |
| QA Firefox local | Matriz de ocho estados de cuenta, flag on/off, 1440/768/360, reflow 320 px, zoom CSS 200 %, teclado/foco y consola sin errores. |

La matriz de navegador fue ejecutada exclusivamente contra `http://127.0.0.1:8137`, con Mail `array`, cola `sync`, sesiones locales y siete cuentas sintéticas. No se consultó la URL pública ni ningún servicio externo. Un primer arranque con `SESSION_DRIVER=array` devolvió 419 al iniciar sesión porque no persiste sesión entre requests; se corrigió el arnés a `file` y no se clasificó como defecto de la aplicación.

Resultado: **`GO LOCAL/TEST` para M1**. No equivale a autorización de despliegue ni a inicio de M2.

## Rollback previsto

- Apagar `FLOWERFLOW_EVALUATION_ENABLED` cierra inmediatamente `/juez` sin tocar datos.
- La migración podrá revertir rol y permiso sólo cuando no existan asignaciones a usuarios ni referencias que obliguen a borrar relaciones; deberá fallar de forma segura si hacerlo pudiera destruir datos.
- El rollback de código consiste en revertir únicamente los archivos de M1; nunca se usarán comandos destructivos sobre el árbol de trabajo compartido.

El rollback/forward de la migración quedó demostrado en `flowerflow_testing`. La migración se niega a borrar el rol o permiso si ya están asignados directamente a una cuenta, por lo que preserva datos y exige una remediación explícita antes de un rollback futuro.

## Registro de progreso

- 2026-08-18: baseline Git confirmado; lectura completa de reglas, ExecPlan de diseño, documentación obligatoria y ADR 0001/0003/0004/0005/0006/0007/0008 terminada.
- 2026-08-18: auditoría inicial confirmó clasificación insegura por descarte en `DashboardController` y layout, rutas participante sin rol explícito y `admin` sincronizado con `Permission::all()`.
- 2026-08-18: se implementaron enum/action de rol exclusivo, middleware, migración y seeder idempotentes, gates de participante/panel/juez, flag fail-closed, redirección segura y dos estados vacíos accesibles.
- 2026-08-18: guard MySQL exacto verde; migración forward/rollback/forward verde; suite dirigida, regresión y suite completa verdes.
- 2026-08-18: QA Firefox local confirmó aislamiento por rol, verificación de correo, flag, navegación, teclado, reflow y ausencia de errores de consola.
- 2026-08-18: las siete cuentas sintéticas de UAT se eliminaron por lista exacta después de confirmar cero propuestas; `flowerflow_testing` quedó con cero usuarios.
- 2026-08-18: gates de dependencias/build/JSON/rutas/schedule/migraciones verdes; se mantiene visible el advisory bajo conocido de Quill.
- 2026-08-18: M1 se cierra `GO LOCAL/TEST`; M2 queda como siguiente prompt separado y M4 continúa bloqueado por `P2B-BLOCK-001`.
- 2026-08-18: entrada histórica posterior — M2 fue ejecutado bajo su propio ExecPlan y quedó `GO LOCAL/TEST`; esta confirmación no reescribe evidencia ni alcance de M1.

## Hallazgos inesperados

- Las rutas de descarga de anexos y evidencia son compartidas por participante propietario y personal con permisos; encerrarlas sólo bajo `participant` habría roto reviewer/admin. M1 aplicó rol exacto permitido más Policy/capacidad existente.
- El comando `flowerflow:admin` usaba `syncRoles(['admin'])`, lo que habría sustituido silenciosamente un rol previo. M1 lo llevó al escritor exclusivo y prueba la ausencia de mutación ante incompatibilidad.
- `SESSION_DRIVER=array` no permite UAT autenticada request-a-request; el servidor local de QA usó `file` sin tocar configuración persistente.
- El advisory bajo de Quill continúa sin parche y no incumple el umbral solicitado `moderate`; permanece como riesgo de contenido rico.

## Archivos implementados por M1

- Contrato/autorización: `app/Enums/BusinessRole.php`, `app/Actions/AssignExclusiveBusinessRole.php`, middleware de rol/flag, aliases en `bootstrap/app.php` y flag en `config/flowerflow.php`/`.env.example`.
- Persistencia determinística: migración M1 y `database/seeders/FlowerFlowSeeder.php`; no crean usuarios juez ni datos 02B.
- Fronteras HTTP: `routes/web.php`, redirección de inicio, creación de cuenta/admin y layout Flower Flow.
- UX mínima: dashboard vacío de juez, estado de acceso restringido y ajustes CSS/errores sin estilos inline.
- Evidencia automatizada: `tests/Feature/JudgeRbacIsolationTest.php` y regresión completa.
- Documentación: ExecPlans, ADR-0004/0008, especificación, alcance, arquitectura, datos, seguridad, UX, roadmap, QA, riesgos, preguntas, handoff, diagnóstico y trazabilidad.
