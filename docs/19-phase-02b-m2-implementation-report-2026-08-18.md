# Informe de implementación — Fase 02B M2

> **Corrección final vigente — 2026-08-18:** este informe acredita históricamente `substitute=10`. El owner aprobó después dos sustitutos y finalmente eliminó el límite para ambos roles. M4A ya migró a `max_active_assignments=NULL`; los resultados M2 siguientes no se reescriben.

**Fecha:** 2026-08-18 (`America/Hermosillo`)

**Estado:** `COMPLETE — GO LOCAL/TEST — NO PRODUCTION AUTHORIZATION`
**ExecPlan:** `.agent/execplans/flowerflow-phase-02b-m2-judge-profile-onboarding.md`

## Resultado ejecutivo

M2 quedó implementado y validado exclusivamente en `flowerflow_testing`. Existe perfil operativo uno-a-uno de juez, alta directa por `admin`, establecimiento de contraseña propia mediante el broker de reset, activación derivada, suspensión/reactivación, revocación de sesiones database y recuperación administrativa de 2FA. M1 conserva su aislamiento: ningún juez entra a participante/panel ni ve propuestas, archivos, residencia o exportaciones.

La decisión histórica es **`GO LOCAL/TEST` para M2** bajo `substitute=10`. No acredita despliegue ni el contrato vigente `4+2` ilimitado; M4A lo reconcilió y M5 quedó verde en planes/informes posteriores.

## Baseline y guard

- Checkout: `/home/ccortesg/workspace/flowerflow`.
- Rama: `codex/submission-deadline-extension`.
- `HEAD`, remoto y ancestro común: `e0fa0455e61afcb38593b62ae0d983f75a92b210`.
- Se preservó el árbol M1/documental preexistente sin stage, commit, push, reset o clean.
- Guard acreditado antes de mutar esquema: `APP_ENV=testing`, MySQL, host `127.0.0.1`, base `flowerflow_testing`, usuario `flowerflow_testing_user` y `SELECT DATABASE()=flowerflow_testing`.

## Modelo e invariantes

`judge_profiles` usa ULID público, `user_id` único, estados `pending_setup`, `active`, `suspended` y función `primary|substitute`. El contrato se protege en servidor y mediante `CHECK`: `primary` exige `max_active_assignments=NULL` para expresar que no tiene límite fijo, mientras `substitute` exige capacidad diez. No hay especialidad, categorías, invitaciones, asignaciones ni datos de evaluación.

| Estado | Entrada | Acceso `/juez` | Salida |
|---|---|---|---|
| `pending_setup` | Alta directa; falta correo, contraseña propia o ambos | Bloqueado; sólo `/juez/estado` seguro | Automática e idempotente al completar ambos prerrequisitos |
| `active` | Correo verificado + `password_initialized_at` | Sólo con rol exacto, permiso exclusivo y flag encendido | Suspensión administrativa |
| `suspended` | Razón 20–1,000 + contraseña admin | Bloqueado inmediatamente | Reactivación administrativa; vuelve a `active` o `pending_setup` según prerrequisitos |

Los permisos `view judges`, `manage judges` y `recover judge two factor` pertenecen sólo a `admin`. `access judge workspace` permanece exclusivo de `judge`. Cero roles, multirol y reemplazo implícito fallan cerrados.

## Credencial, correo y activación

- El alta `/panel/jueces` acepta nombre, correo y función `primary|substitute`, normaliza el email y crea usuario+rol+perfil dentro de una transacción. La capacidad se deriva en servidor; no se acepta del navegador.
- La contraseña inicial aleatoria sólo se conserva como hash y nunca se muestra, registra o envía.
- Después de persistir la cuenta se genera un token del broker `users`; el token opaco sólo viaja dentro de la URL temporal de la notificación cifrada y nunca como metadata/log/respuesta.
- El primer reset propio fija una sola vez `password_initialized_at` y solicita verificación firmada si falta.
- El evento `Verified` y el reset sincronizan activación en cualquier orden. 2FA sigue siendo opcional.
- Un fallo de token/dispatch no revierte la cuenta: genera warning seguro, log redactado y auditoría reintentable.

## Suspensión, reactivación y recovery 2FA

Las tres acciones usan Policy/Form Request, permiso granular, razón y confirmación de contraseña. Suspensión y recovery exigen sesiones `database`; si el driver no permite revocación dirigida, fallan antes de cambiar el estado. Ambas rotan `remember_token` y eliminan todas las sesiones persistidas del sujeto.

Recovery requiere correo verificado, elimina secreto/códigos/confirmación TOTP, no muestra material 2FA y no altera rol ni perfil. Las notificaciones M2 son HTML+texto, cifradas, después de commit y sin datos de propuestas. Auditoría conserva actor real, sujeto técnico, transición/razón y UTC sin credenciales.

## Matriz efectiva

| Actor/estado | `/panel/jueces` | Alta/estado | Recovery 2FA | `/juez` | Participant/panel/privados |
|---|---:|---:|---:|---:|---:|
| Visitante | Login | No | No | Login | No |
| `participant` | 403 | 403 | 403 | 403 | Sólo recursos propios existentes |
| `reviewer` | 403 | 403 | 403 | 403 | Panel actual sin gestión de jueces |
| `admin` | Sí | Sí | Sí, permiso separado | 403 como juez | Panel actual |
| `judge pending_setup` | 403 | Estado seguro | No | Bloqueado | 403/404 |
| `judge active` | 403 | Estado activo | No | Sí con flag | 403/404 |
| `judge suspended` | 403 | Estado seguro | No | Bloqueado | 403/404 |
| Sin rol / multirol | 403 | 403 | 403 | 403 | Fail-closed |

Con `FLOWERFLOW_EVALUATION_ENABLED=false`, `/juez` y `/juez/estado` devuelven 404. El flag no concede permisos ni cambia estados.

## Evidencia ejecutada

| Gate | Resultado |
|---|---|
| Migración M2 | Forward/rollback/forward verde; usuario sintético preexistente conservado; cero backfill/perfiles automáticos |
| Pruebas M2 | 10 pruebas / 175 aserciones |
| Pruebas M1+M2 | 16 pruebas / 267 aserciones |
| Suite completa | 125 pruebas / 1,316 aserciones |
| Pint | Verde |
| Composer validate/platform/audit | Verde; cero advisories |
| Yarn audit | Un advisory bajo conocido de Quill 2.0.3; cero moderados/altos/críticos |
| Iconos/build | 98 iconos regenerados reproduciblemente; 784 módulos y tres assets Vite |
| Browser local | Firefox, escritorio/tableta/móvil; admin, pending/active/suspended, teclado/foco, reflow, simulación CSS de zoom 200 %, consola, 403/404 y revocación de sesión verdes |

La UAT usó correo `array`, cola sync, sesiones database y sólo cuentas/correos sintéticos. Recovery eliminó una sesión persistida real del juez; la siguiente navegación volvió a login. Suspensión mostró el estado seguro y reactivación restauró `/juez`. El reflow se acreditó mediante viewports 390/768/1440; una simulación CSS de zoom 200 % duplicó la altura sin overflow horizontal (`scrollWidth=clientWidth=1440`) y terminó con consola limpia. No se presenta esta simulación como zoom nativo del navegador.

## Archivos y responsabilidades

- Dominio/datos: `app/Enums/JudgeProfileStatus.php`, `app/Enums/JudgeAssignmentRole.php`, `app/Models/JudgeProfile.php` y `database/migrations/2026_08_18_130000_create_judge_profiles_and_permissions.php`.
- Acciones: `app/Actions/Judges/` cubre alta, actor administrativo exacto, activación, credencial, correo, suspensión/reactivación, sesiones y recovery.
- Autorización/HTTP: `JudgeProfilePolicy`, cuatro Form Requests, `EnsureActiveJudge`, controllers de juez/admin, aliases y rutas.
- Auth/correo: integración acotada en `ResetUserPassword`, `User`, `AppServiceProvider`; tres notificaciones y seis plantillas HTML/texto.
- UX: `resources/views/panel/judges/`, `resources/views/judge/`, layout y estilos/iconos Flower Flow.
- Datos base/QA: `FlowerFlowSeeder`, `scripts/serve_local_testing.sh`, `JudgeProfileOnboardingTest` y adaptación M1 para perfiles activos sintéticos.
- Documentación: ExecPlans M1/M2/diseño, ADR-0008, diagnóstico, producto/alcance/arquitectura/datos/seguridad/UX, roadmap, QA, riesgos, decisiones, handoff y trazabilidad.

## Comandos principales y resultado

- Guard: `FLOWERFLOW_TEST_GUARD_ONLY=true scripts/serve_local_testing.sh` — valores exactos y `SELECT DATABASE()` verdes.
- Esquema: `php artisan migrate`, `migrate:rollback --step=1`, nuevo `migrate` y finalmente `migrate:fresh --seed`, siempre con variables testing exactas — 14/14 y cero cuentas/perfiles/sesiones al cierre.
- Pruebas: suites dirigidas M1/M2 y `php artisan test` — 10/175, 16/267 y 125/1,316.
- Calidad: `vendor/bin/pint --test`, Composer validate/platform/audit y Yarn audit — verdes salvo el advisory bajo conocido de Quill, sin moderados+.
- Frontend: `corepack yarn icons:write` y `scripts/build_frontend_production.sh` — 98 iconos, 784 módulos, tres assets.
- Inventario: JSON de menús, rutas, schedule, migraciones, enlaces Markdown, `git diff --check` y scans de secretos/PII — verdes.
- Browser: Playwright CLI con Firefox contra `127.0.0.1:8018` — flujos/estados M2, sesión invalidada, tres viewports y CSS zoom 200 % verdes.

## Hallazgo reparado

El primer arranque browser devolvió 403 a un admin válido porque el arnés local cambió a `CACHE_STORE=file` y reutilizó un caché Spatie anterior a M2. `scripts/serve_local_testing.sh` ahora ejecuta `permission:cache-reset` después del guard y antes del readiness. La repetición mostró el permiso y todas las rutas correctas.

El primer build también detectó que el catálogo de iconos no incluía los iconos nuevos de M2. Se ejecutó el generador autorizado `corepack yarn icons:write`; `icons:check` y el build posterior quedaron verdes.

## Compatibilidad y rollback

- Migración exclusivamente aditiva; no modifica usuarios, propuestas, folios, snapshots, aceptaciones o archivos existentes.
- Seeder idempotente crea permisos/roles, nunca cuentas/perfiles/invitaciones/asignaciones.
- `down` primero verifica que no existan perfiles ni permisos M2 directos; aborta antes de borrar si perdería datos.
- Contención inmediata: apagar el flag cierra todo el shell sin tocar datos.
- Rollback de código debe limitarse a archivos M2. No se autoriza rollback ni despliegue productivo en este corte.

## Riesgos y siguiente puerta

- `P2B-BLOCK-001` está `RESOLVED BY OWNER`: cuatro principales cubren todas las propuestas elegibles sin límite fijo y el quinto juez es sustituto exclusivo con capacidad diez. M4 debe impedir asignaciones iniciales al sustituto y rechazar la undécima sustitución activa; esto no autoriza todavía su implementación.
- Más de diez sustituciones simultáneas constituyen un riesgo operativo residual: el sistema futuro deberá fallar cerrado y exigir una decisión nueva, sin sobreasignación silenciosa.
- La entrega real de correo, worker, `failed_jobs`, SHA/runtime productivo y monitoreo siguen `POR_CONFIRMAR`.
- 2FA opcional es una decisión aceptada, no una brecha de implementación M2; aumenta la importancia de suspensión, recovery auditado y monitoreo.
- M3 puede autorizarse por separado para rúbrica versionada. No debe crear asignaciones ni evaluaciones.

## Estado final del ambiente sintético

Después de repetir el guard exacto se ejecutó `migrate:fresh --seed` exclusivamente en `flowerflow_testing`. El cierre registró 14/14 migraciones, cuatro categorías activas, tres tipos jurídicos activos y cero usuarios, `judge_profiles` o sesiones. No se accedió a otra base ni se usaron datos reales.
