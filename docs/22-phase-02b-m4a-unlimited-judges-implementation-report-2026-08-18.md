# Informe de implementación Fase 02B M4A — jueces ilimitados

**Fecha:** 2026-08-18 (`America/Hermosillo`)

**Estado al cierre M4A:** `GO LOCAL/TEST — NOT DEPLOYED — M5 NOT IMPLEMENTED`
**Adenda posterior:** M5 quedó `GO LOCAL/TEST` en su ExecPlan e informe propios; M6 es la siguiente puerta separada.

## Resultado ejecutivo

M4A reconcilia el contrato final del propietario: exactamente cuatro jueces `primary` y dos `substitute` activos, los seis sin límite de proyectos. Los principales cubren todas las propuestas elegibles; los sustitutos no reciben asignaciones iniciales y `admin` selecciona manualmente a uno cuando resuelve el conflicto de una asignación principal.

El cambio se ejecutó exclusivamente contra MySQL local `flowerflow_testing`, con datos sintéticos. No se accedió a producción ni se implementó M5.

## Baseline y guard

- repositorio: `/home/ccortesg/workspace/flowerflow`;
- rama: `codex/submission-deadline-extension`;
- HEAD/upstream/ancestro común al iniciar: `865059ad302ff4195ac18f671bd6fa13b99e398b`;
- `APP_ENV=testing`, `DB_CONNECTION=mysql`, host loopback, base `flowerflow_testing`, usuario `flowerflow_testing_user` y `SELECT DATABASE()=flowerflow_testing`;
- el árbol ya contenía M3/M4 y documentación sin commit; se preservó sin stage, commit, push, reset, clean o checkout destructivo.

## Contrato implementado

| Invariante | Implementación local/test |
|---|---|
| Composición operativa | exactamente cuatro `primary` + dos `substitute` activos |
| Capacidad | `max_active_assignments=NULL` para ambas funciones |
| Carga inicial | sólo los cuatro principales |
| Reemplazo | selección manual obligatoria de uno de los dos sustitutos por ULID público |
| Duplicidad | un juez no puede conservar dos asignaciones vigentes de la misma propuesta |
| Volumen | no se cuenta ni rechaza por cantidad; se probaron 31 reemplazos activos |
| Cadena por conflicto de replacement | no aprobada; continúa fail-closed |

## Cambios técnicos

- `JudgeAssignmentRole::maxActiveAssignments()` devuelve siempre `NULL`.
- `ActivateSubmissionCoverage` bloquea y valida la composición exacta `4+2`, pero crea sólo cuatro asignaciones iniciales.
- `ResolveJudgeConflict` exige selección manual, revalida bajo locks los seis perfiles y elimina el contador de capacidad.
- El request, controller y UI administrativa reciben un ULID de sustituto; selección omitida, alterada, no operativa o duplicada falla sin mutación parcial.
- La UI de jueces y conflictos muestra `Sin límite`.
- La migración `2026_08_18_160000_make_all_judge_assignment_roles_unlimited.php` convierte sustitutos históricos `10→NULL` y reemplaza el check por `max_active_assignments IS NULL`.

## Migración y rollback

Se probó `forward→rollback→forward` y un upgrade sintético con un principal `NULL` y dos sustitutos `10`. El forward dejó los tres en `NULL` y MySQL rechazó capacidad fija para ambas funciones.

El rollback restaura el contrato histórico `primary=NULL|substitute=10` sólo si ningún sustituto tiene más de diez reemplazos activos. Si ya se excedió ese antecedente, aborta antes de cambiar esquema o datos para evitar una restauración inconsistente.

## Evidencia automatizada

| Gate | Resultado |
|---|---|
| M1–M4 dirigidas | 33 pruebas / 535 aserciones, verde |
| Suite completa | 142 pruebas / 1,584 aserciones, verde |
| Caso ilimitado | 31 reemplazos activos en un sustituto y selección independiente del segundo, verde |
| Pint | verde |
| Composer validate/platform/audit | verde; cero advisories Composer |
| Yarn audit | advisory bajo conocido de Quill; cero moderate+ |
| Build frontend | verde; Vite compiló 784 módulos |
| Migraciones | 17 migraciones aplicadas en testing |
| Menús JSON | ambos archivos parseados con `JSON_THROW_ON_ERROR`; `jq` no está instalado |
| Rutas/schedule | 66 rutas propias; único schedule: purga horaria de XLSX |
| Markdown/diff | enlaces locales y `git diff --check`, verdes |
| Secretos/PII | diff sin secretos de alta confianza; nuevos datos de prueba exclusivamente sintéticos |

## UAT real local

Firefox mostró ambos sustitutos como `Sin límite` y exigió selección manual. La reasignación sintética anuló la original, creó el replacement elegido y restauró cobertura `3/4→4/4`.

Se revisaron 768×1024 y 390×844 además de escritorio; el documento mantuvo `scrollWidth=clientWidth` en móvil, el skip link recibió foco por teclado y la consola terminó con cero errores y cero warnings. Los datos UAT fueron retirados después con `migrate:fresh --seed`; quedaron cero usuarios, perfiles, asignaciones y conflictos.

## Riesgos residuales

- La cadena de reemplazo cuando un `substitute` ya asignado declara conflicto continúa sin decisión y falla cerrada.
- Capacidad ilimitada elimina el bloqueo numérico, pero aumenta la carga operativa posible de cada juez; es una decisión expresa del propietario, no un balanceo automático.
- No existe evidencia productiva de M1–M4A. `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR` y este informe no autoriza despliegue.
- M5–M10 permanecen no implementados.

## Rollback operativo

El flag `FLOWERFLOW_EVALUATION_ENABLED=false` cierra el shell de juez sin borrar evidencia. Un rollback de migración debe respetar la precondición descrita; no se borran cuentas, perfiles, propuestas, asignaciones, conflictos ni auditoría para forzarlo.
