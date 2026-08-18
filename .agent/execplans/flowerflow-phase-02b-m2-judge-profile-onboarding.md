# ExecPlan — Fase 02B M2: perfil y alta directa segura de juez

> **Corrección final posterior — 2026-08-18:** el contrato `substitute=10` documentado y probado aquí es histórico; la decisión intermedia `2×30` también fue sustituida. M4A implementa dos sustitutos y capacidad `NULL` para ambos roles; este ExecPlan no debe citarse como evidencia de capacidad vigente.

Estado: `COMPLETE — GO LOCAL/TEST — NO PRODUCTION AUTHORIZATION`

Fecha de inicio: 2026-08-18 (`America/Hermosillo`)

## Propósito y resultado observable

Implementar exclusivamente M2 sobre la base M1: perfil operativo uno-a-uno de juez, alta directa y controlada por `admin`, establecimiento de credencial propia mediante el broker seguro de restablecimiento ya instalado, activación derivada de contraseña propia más correo verificado, suspensión/reactivación, revocación de sesiones persistidas y recuperación administrativa de 2FA.

Al terminar, una cuenta nueva de juez deberá nacer en `pending_setup`, con contraseña aleatoria desconocida, rol exclusivo `judge`, función operativa explícita y ningún acceso a datos de negocio. Un juez `primary` tendrá `max_active_assignments=NULL`, que representa ausencia de límite fijo, y un juez `substitute` tendrá capacidad fija diez. Sólo podrá abrir `/juez` si su perfil está `active`, el correo está verificado, su contraseña propia fue establecida y `FLOWERFLOW_EVALUATION_ENABLED` está encendido. `participant`, `reviewer`, `admin`, cero roles y multirol conservarán el aislamiento M1.

M3–M10 permanecen fuera de alcance. No se implementan invitaciones, asignaciones, paquetes ciegos, conflictos, rúbricas, evaluaciones, puntajes, consolidación, reapertura de evaluaciones, recordatorios masivos, retención/purga, ganadores o resultados.

## Baseline Git preservado

Comprobado antes de editar:

- `pwd`: `/home/ccortesg/workspace/flowerflow`.
- Git toplevel: `/home/ccortesg/workspace/flowerflow`.
- Rama: `codex/submission-deadline-extension`.
- `HEAD`: `e0fa0455e61afcb38593b62ae0d983f75a92b210`.
- Remoto `origin/codex/submission-deadline-extension`: `e0fa0455e61afcb38593b62ae0d983f75a92b210`.
- Ancestro común: `e0fa0455e61afcb38593b62ae0d983f75a92b210`.
- `git diff --check`: verde.
- Huella SHA-256 del diff tracked preexistente: `84f2cb74d1b6f8fb593e1438c95b20d55b2a3ac92d06859675c9aea3ccd1607a`.
- El diff tracked preexistente contenía 30 archivos, 642 inserciones y 211 eliminaciones; además existían 13 rutas no rastreadas correspondientes al diseño/M1.

El árbol de trabajo ya contenía M1 y documentación previa sin stage. Se preservarán íntegramente todos esos cambios. No se hará stage, commit, push, reset, clean, checkout destructivo ni despliegue.

## Decisiones e invariantes aplicables

- Los roles `participant`, `reviewer`, `judge` y `admin` son estrictamente excluyentes.
- El alta de juez es directa por `admin`; no existe invitación, token ni tabla de invitación de juez.
- Cada perfil fija `assignment_role=primary|substitute`: `primary` usa `max_active_assignments=NULL` y `substitute` usa `max_active_assignments=10`; no contiene especialidad ni categorías.
- El acceso a `/juez` exige correo verificado y perfil activo; 2FA permanece opcional.
- Sólo `admin`, con permiso separado, razón de 20–1,000 caracteres y confirmación reciente de contraseña, puede recuperar 2FA.
- Suspensión y reactivación requieren razón de 20–1,000 caracteres y confirmación reciente de contraseña.
- Una suspensión o recovery rota `remember_token`, elimina todas las sesiones persistidas del juez y nunca borra usuario, rol ni perfil.
- La reactivación produce `active` sólo si existen `password_initialized_at` y `email_verified_at`; de otro modo vuelve a `pending_setup`.
- La contraseña aleatoria inicial nunca se presenta, envía ni registra. El acceso inicial usa exclusivamente un enlace temporal generado por el broker de reset existente. El token opaco sólo puede existir dentro de la URL temporal cifrada en cola; nunca se registra como campo separado, metadata, log o respuesta administrativa.
- Toda activación es derivada, idempotente, transaccional y auditada. Un perfil suspendido nunca se reactiva por eventos automáticos.
- M1 continúa siendo invariante: permiso `access judge workspace` exclusivo, gates de rol exacto, flag fail-closed y ausencia de acceso a participante, panel, PII, archivos, residencia o exportaciones.

## Decisión posterior de capacidad y sustitución

`P2B-BLOCK-001` quedó `RESOLVED BY OWNER` el 2026-08-18 antes del primer commit de M2: cuatro jueces principales evaluarán todas las propuestas elegibles sin límite fijo y un quinto juez será exclusivamente sustituto con máximo diez reasignaciones activas. M2 persiste la función y capacidad derivada, pero no crea las cinco cuentas, no fija un máximo global de cuentas, no asigna propuestas ni implementa la restricción operativa de asignación inicial/reasignación; esa aplicación pertenece a M4.

## Modelo propuesto

`judge_profiles` será una tabla aditiva uno-a-uno con `users`, identificada externamente por ULID. Campos: `public_id`, `user_id` único, `status`, `assignment_role`, `max_active_assignments`, `created_by_user_id`, `password_initialized_at`, `activated_at`, `suspended_at`, `suspended_by_user_id`, `suspension_reason`, `reactivated_at`, `reactivated_by_user_id` y timestamps.

Estados respaldados por enum:

- `pending_setup`: falta correo verificado, contraseña propia o ambos;
- `active`: ambos prerrequisitos existen y no hay suspensión;
- `suspended`: estado administrativo bloqueante, independientemente de prerrequisitos.

La base aplicará FKs/índices, unicidad uno-a-uno y restricciones MySQL para estado, función y la pareja válida `primary+NULL` o `substitute+10`. El modelo será guarded y sólo las Actions podrán modificar estado, función, capacidad, actores o timestamps.

La historia append-only de alta, credencial inicializada, activación, suspensión, reactivación, correo de configuración y recovery se conservará en `audit_logs`. Los campos del perfil representan el estado/última transición aplicable y nunca sustituyen la bitácora.

## Diseño de servicios y seguridad

1. `CreateJudgeAccount` validará nuevamente dentro de una transacción que el correo no exista, generará una contraseña aleatoria criptográfica que se descartará después del hash, reutilizará `AssignExclusiveBusinessRole` y creará el perfil `pending_setup`.
2. Después del commit, `SendJudgeSetupNotification` generará un token mediante el broker `users`, auditará intento/resultado y usará `ResilientMailDispatcher` con notificación cifrada, en cola, HTML y texto. El fallo de correo no revertirá la cuenta y se mostrará como advertencia segura; habrá reenvío administrativo limitado.
3. `ResetUserPassword` conservará el contrato participante. Para un juez válido marcará `password_initialized_at` sólo una vez, auditará sin secretos y solicitará el correo firmado de verificación si aún falta.
4. Un listener explícito del evento `Verified` y la finalización de contraseña invocarán `SynchronizeJudgeProfileActivation`. El Action bloqueará filas y sólo cambiará `pending_setup -> active` cuando ambos prerrequisitos existan.
5. `EnsureActiveJudge` consultará estado actual directamente en base y negará acceso si falta perfil, prerrequisito o estado activo. Redirigirá a una superficie segura `/juez/estado`, sin datos de negocio.
6. `SuspendJudge`, `ReactivateJudge` y `RecoverJudgeTwoFactor` usarán Policy/Form Request, lock, transacción, auditoría redactada y notificación post-commit.
7. `RevokeUserSessions` exigirá `SESSION_DRIVER=database`, rotará `remember_token` y borrará por `user_id` en la tabla configurada. Si el runtime no permite revocación dirigida, la acción fallará antes de mutar estado.
8. `/panel/jueces` estará detrás de rol exacto `admin`, permisos granulares y Policies. Listará sólo datos de cuenta/perfil necesarios, usará ULID, paginación y formularios server-side; no incluirá asignaciones ni evaluación.

## Plan de implementación

1. Crear enum/modelo/migración de `judge_profiles` y permisos administrativos M2, con rollback fail-closed ante datos.
2. Actualizar relaciones de `User` y seeder idempotente sin crear cuentas/perfiles.
3. Crear Policies, Form Requests y middleware de perfil activo.
4. Implementar Actions de alta, correo de setup/reenvío, sincronización, suspensión, reactivación, revocación de sesiones y recovery 2FA.
5. Integrar reset y verificación de correo sin cambiar el comportamiento participante.
6. Añadir controller/rutas/vistas admin y estados seguros de juez; actualizar navegación sólo para `admin` autorizado.
7. Añadir plantillas dual-brand HTML/texto para setup, verificación de juez y eventos de cuenta.
8. Añadir pruebas de esquema, idempotencia, concurrencia/duplicados, secretos, mail resiliente, estados, permisos, IDOR, mass assignment, sesiones y regresión M1.
9. Ejecutar guard MySQL exacto antes de migrar/sembrar/probar esquema.
10. Probar forward/rollback/forward, suites, gates técnicos y QA real local en tres viewports con correo array y sesiones database.
11. Actualizar ADR-0008, documentación viva, riesgos, trazabilidad, diagnóstico y siguiente prompt limitado a M3 sólo si todo queda verde.

## Guard MySQL obligatorio

Antes de cualquier migración, seeder o prueba que pueda recrear esquema se demostrará, sin imprimir secretos:

| Control | Valor requerido |
|---|---|
| `APP_ENV` | `testing` |
| Driver | `mysql` |
| Host | `127.0.0.1` o `localhost` |
| Base configurada | `flowerflow_testing` |
| Usuario configurado | `flowerflow_testing_user` |
| `SELECT DATABASE()` | `flowerflow_testing` |

Si un valor difiere, el trabajo se detendrá antes de mutar la base.

## Pruebas y gates previstos

- Migración sobre esquema con usuarios sintéticos previos; cero backfill y preservación de datos.
- Rollback M2 sólo con `judge_profiles` vacío y forward posterior.
- Seeder repetido conserva usuarios/roles/permisos y no crea jueces, perfiles, invitaciones o asignaciones.
- Suite dirigida `JudgeProfileOnboardingTest` y regresión completa de `JudgeRbacIsolationTest`.
- Suite completa, Pint, Composer validate/platform/audit, Yarn audit, build, JSON, rutas, schedule, migrate status, enlaces Markdown, diff check y revisión de secretos/PII.
- QA browser local de admin y estados `pending_setup`, `active`, `suspended`; escritorio/tableta/móvil, teclado, foco, zoom/reflow, consola, 403/404 y sesión invalidada.

## Rollback previsto

- Apagar `FLOWERFLOW_EVALUATION_ENABLED` mantiene cerrado todo shell juez sin cambiar datos.
- La migración M2 sólo eliminará `judge_profiles` y permisos M2 si no existen perfiles ni asignaciones directas incompatibles; con datos, abortará para evitar pérdida.
- El rollback de código se limita a los archivos M2. No elimina usuarios, roles, aceptaciones, propuestas, archivos, sesiones ajenas ni auditoría existente.
- No se ejecutará rollback en producción ni sobre datos reales en esta tarea.

## Registro de progreso

- 2026-08-18: baseline Git confirmado; la huella del diff M1/documental preexistente quedó registrada y el árbol se preserva sin stage.
- 2026-08-18: lectura completa de `AGENTS.md`, `.agent/PLANS.md`, ExecPlans obligatorios, documentación solicitada y ADR 0001/0003/0004/0005/0006/0007/0008 terminada.
- 2026-08-18: auditoría de código confirmó Fortify 1.37.2, Spatie Permission 8.3.0, `password_reset_tokens`, sesiones database por default, `AuditLogger`, dispatcher resiliente, notificaciones cifradas/post-commit y Policies auto-descubiertas como patrones reutilizables.
- 2026-08-18: se decidió revocación dirigida fail-closed sólo con sesiones database; el arnés browser M1 usa `file` y deberá ejecutarse con `database` para acreditar M2.
- 2026-08-18: guard MySQL exacto verde y baseline M1 verde (6 pruebas/92 aserciones) antes de implementar.
- 2026-08-18: migración M2 forward/rollback/forward verde con usuario sintético preexistente preservado, cero perfiles automáticos y checks de estado/capacidad.
- 2026-08-18: suites dirigidas M1+M2 verdes (16 pruebas/257 aserciones) y suite completa verde (125/1,306).
- 2026-08-18: Pint y Composer verdes; Yarn conserva únicamente el advisory bajo conocido de Quill 2.0.3. El catálogo se regeneró a 98 iconos y el build terminó con 784 módulos/tres assets.
- 2026-08-18: UAT Firefox local acreditó admin, alta, pending/active/suspended, recovery 2FA, sesión invalidada, reactivación, 403/404, teclado/foco, reflow y consola limpia en 1440/768/390.
- 2026-08-18: el arnés local reutilizó inicialmente un caché Spatie de archivo anterior a M2; se añadió `permission:cache-reset` después del guard y la repetición quedó verde.
- 2026-08-18: seguimiento de accesibilidad aplicó simulación CSS zoom 200 % al shell activo: altura duplicada, `scrollWidth=clientWidth=1440`, inspección visual y consola limpia. Se documenta explícitamente que no fue zoom nativo.
- 2026-08-18: después de repetir el guard exacto, `migrate:fresh --seed` retiró las cuatro cuentas y sesiones sintéticas de UAT; cierre con 14/14 migraciones y cero usuarios/perfiles/sesiones.
- 2026-08-18: gates estáticos finales verdes: dos JSON de menú, 77 rutas/52 propias, schedule, 14 migraciones, 15 enlaces Markdown locales, diff check y scans de secretos/PII. No hubo stage, commit, push, despliegue o acceso externo.
- 2026-08-18: el propietario sustituyó el máximo ocho por cuatro jueces `primary` sin límite fijo y un quinto `substitute` con máximo diez reasignaciones activas; `P2B-BLOCK-001` quedó resuelto.
- 2026-08-18: antes del primer commit se alinearon enum, esquema, alta, UI y pruebas M2 a `primary=NULL`/`substitute=10`; M2 no crea cuentas o asignaciones automáticamente y M4 conserva la aplicación operativa futura.
- 2026-08-18: suites dirigidas M1+M2 repetidas tras la corrección quedaron verdes con 16 pruebas y 267 aserciones; los gates finales completos se repiten antes del commit/push autorizado.
- 2026-08-18: cierre posterior a la corrección verde: M2 10/175, M1+M2 16/267 y suite completa 125/1,316; Pint, Composer, build, rutas, schedule, migraciones, enlaces y diff check verdes. Yarn conserva sólo el advisory bajo conocido de Quill.
- 2026-08-18: QA Firefox repitió el alta `primary` en escritorio y `substitute` en móvil 390 px; mostró `Sin límite fijo`/`10`, sin overflow horizontal ni mensajes de consola. El ambiente se limpió a cero usuarios/perfiles/sesiones con 14 migraciones.
- 2026-08-18: el propietario autorizó expresamente agrupar los cambios acumulados, realizar commit y push de la rama para que él ejecute el despliegue; esta autorización no permite a Codex desplegar ni acceder a producción.
- 2026-08-18: decisión posterior del propietario sustituye un `substitute=10` por dos sustitutos operativos con `max_active_assignments=30` cada uno. Se preservan resultados históricos M2 y se registra `P2B-M4-CORRECTION-001=IMPLEMENTATION_REQUIRED`; no se modifica código en la reconciliación documental.

## Hallazgos iniciales

- El flujo Fortify de reset emite `PasswordReset` y autentica a la cuenta, pero no marca por sí mismo el prerrequisito M2 ni envía automáticamente verificación; se requiere integración explícita sólo para jueces.
- La verificación firmada emite `Verified`, punto estable para sincronizar activación sin inferirla.
- `SESSION_DRIVER=array` de PHPUnit no representa revocación persistida; las pruebas de esta acción deberán cambiar temporalmente a `database` y crear filas sintéticas de sesión.
- El runtime browser M1 usa sesiones `file`, que no permiten eliminar de forma dirigida todas las sesiones de otra cuenta. M2 cambiará únicamente el arnés local autorizado a sesiones `database`; `.env.example` ya tiene ese default.
- Un enlace de reset necesariamente contiene un token opaco. La regla de no exposición se interpreta de forma técnicamente consistente: nunca se muestra como valor independiente ni se registra; sólo viaja dentro de la URL HTTPS de uso único en la notificación cifrada.

## Resultados

M2 queda **`GO LOCAL/TEST`**. El informe detallado es `docs/19-phase-02b-m2-implementation-report-2026-08-18.md`.

- Existe alta directa segura, perfil uno-a-uno, activación derivada, suspensión/reactivación, revocación de sesiones database y recovery administrativo 2FA.
- M1 mantiene su matriz de aislamiento y el flag continúa fail-closed.
- No se crearon invitaciones, asignaciones, paquetes ciegos, conflictos, rúbricas, evaluaciones, puntajes, ganadores o resultados.
- M3 es la única siguiente puerta proponible. `P2B-BLOCK-001` está resuelto; M4 permanece no implementado/no autorizado y sólo podrá proponerse después de M3 verde.
