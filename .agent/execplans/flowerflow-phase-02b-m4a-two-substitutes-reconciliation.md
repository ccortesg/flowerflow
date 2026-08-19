# ExecPlan — Fase 02B M4A: reconciliación de dos jueces sustitutos

**Estado:** GO LOCAL/TEST — OWNER FINAL UNLIMITED — NOT M5
**Fecha:** 2026-08-18 (`America/Hermosillo`)
**Repositorio:** `/home/ccortesg/workspace/flowerflow`

## Propósito y resultado observable

Implementar la corrección final `OWNER_APPROVED` de `P2B-BLOCK-001`: la operación tendrá exactamente cuatro jueces principales activos, que evaluarán todas las propuestas elegibles, y dos jueces sustitutos activos, sin carga inicial. Ninguno de los seis perfiles operativos tendrá límite de proyectos o reasignaciones.

Esta ejecución implementa exclusivamente M4A: esquema/capacidad ilimitada, composición `4+2`, selección manual del sustituto, UI, pruebas y documentación. No implementa paquetes ciegos M5, evaluación M6 ni producción. M5 sólo podrá iniciar después de este plan verde.

## Baseline protegido

- `pwd` y Git toplevel: `/home/ccortesg/workspace/flowerflow`.
- Rama: `codex/submission-deadline-extension`.
- HEAD, upstream y ancestro común: `865059ad302ff4195ac18f671bd6fa13b99e398b`.
- El árbol contiene M3/M4 y documentación preexistentes sin commit; se preservan sin stage, commit, push, reset, clean o checkout destructivo.
- Baseline M1–M4 previo al delta: 33 pruebas/521 aserciones verdes en `flowerflow_testing` después del guard exacto.
- No se accede a producción, URL pública, AWS, bases, logs o datos reales.

## Decisión vigente y reglas derivadas

- Cuatro perfiles `primary` activos, sin límite fijo, cubren todas las propuestas elegibles de las cuatro categorías.
- Dos perfiles `substitute` activos no reciben asignaciones iniciales.
- Primary y substitute usan `max_active_assignments=NULL`: no existe límite individual ni combinado de proyectos.
- La reasignación permanece manual: `admin` selecciona expresamente uno de los dos sustitutos operativos. No existe balanceo automático, round-robin o selección implícita.
- El límite de seis se refiere a perfiles activos operativos: cuatro primary y dos substitute. Cuentas suspendidas o históricas pueden conservarse para no destruir auditoría, pero no cuentan para la composición activa.
- Un sustituto no puede recibir dos asignaciones vigentes de la misma propuesta.
- La decisión no autoriza una cadena de reemplazo cuando un sustituto ya asignado declara conflicto. Ese caso conserva el fallo cerrado actual hasta una decisión expresa distinta.

## Divergencia de implementación comprobada

El baseline local M2/M4 materializa el contrato sustituido:

- `JudgeAssignmentRole::Substitute` deriva capacidad `10`;
- el `CHECK judge_profiles_capacity_check` exige `substitute=10`;
- alta/UI/pruebas M2 esperan capacidad diez;
- cobertura M4 exige exactamente cuatro primary y un substitute;
- resolución M4 exige exactamente un substitute, lo selecciona implícitamente y rechaza a partir de diez.

Por ello, M1–M3 permanecen `GO LOCAL/TEST`, pero M4 queda `GO HISTÓRICO BAJO CONTRATO 1×10 / CURRENT CONTRACT NOT IMPLEMENTED` hasta terminar esta ejecución. No debe presentarse M5 como ejecutable antes de cerrar M4A.

## Implementación futura M4A requerida

1. Crear migración aditiva posterior a M4 que sustituya de forma reversible y compatible el `CHECK` de capacidad, actualizando perfiles `substitute` existentes de 10 a `NULL`; no editar la migración M2 ya histórica.
2. Cambiar la capacidad derivada server-side y todas las validaciones/UI/pruebas M2 a `primary=NULL|substitute=NULL`.
3. Exigir composición operativa exacta `4 primary + 2 substitute` sin crear cuentas por migración/seeder ni borrar cuentas históricas.
4. Añadir selección manual obligatoria del sustituto en la resolución administrativa, por ULID autorizado; revalidar rol, estado, prerrequisitos y no duplicidad dentro de la transacción.
5. Tomar locks determinísticos sobre la composición y el sustituto seleccionado para evitar carreras; no contar ni rechazar por volumen.
6. Demostrar que cada sustituto admite más de treinta reemplazos activos sin límite de aplicación o base, conservando no duplicidad por propuesta/juez.
7. Preservar original `voided`, conflicto/resolución append-only, versión, rúbrica, plazo, auditoría y toda la matriz negativa M1–M4.
8. Mantener sin autorización la cadena de reemplazo de un sustituto conflictuado.

## Validación futura mínima

- guard MySQL exacto y migración forward/rollback/forward sólo en `flowerflow_testing`;
- upgrade con perfiles substitute sintéticos `10→NULL` y primary conservado en `NULL`;
- cobertura acepta exactamente `4+2` y rechaza `4+0`, `4+1`, `4+3` o composición no operativa;
- admin selecciona manualmente cualquiera de dos sustitutos; selección omitida, ajena, inactiva o duplicada falla sin mutación parcial;
- al menos treinta y un reemplazos activos para un sustituto sin rechazo por volumen y selección independiente del segundo;
- dos resoluciones concurrentes no duplican propuesta/juez ni dejan mutaciones parciales;
- suites M1–M4, suite completa, Pint, Composer/Yarn, build, JSON, rutas, schedule, migraciones, diff, enlaces y secretos/PII;
- UAT Firefox admin con ambos sustitutos visibles como `Sin límite`, selección manual, 403/404 y estados fail-closed.

## Rollback futuro

- apagar `FLOWERFLOW_EVALUATION_ENABLED` cierra el shell juez sin borrar evidencia;
- el rollback de esquema sólo puede volver al contrato histórico de capacidad diez si ningún sustituto supera diez reemplazos activos; de lo contrario debe abortar;
- nunca borrar cuentas, perfiles, asignaciones, conflictos, auditoría o propuestas para restaurar el contrato anterior.

## Registro vivo

- [x] 2026-08-18 MST — El propietario sustituye `1 substitute × 10` por `2 substitutes × 30`, mantiene cuatro primary sin límite y fija seis jueces operativos.
- [x] 2026-08-18 MST — Se comprueba que el código M2/M4 sigue en `1×10`; la actualización documental no se presenta como implementación.
- [x] 2026-08-18 MST — Se conserva asignación/reasignación manual: admin deberá seleccionar explícitamente uno de los dos sustitutos; no se infiere un algoritmo automático.
- [x] 2026-08-18 MST — Se sincronizan ADR, producto, alcance, arquitectura/datos, seguridad/UX, roadmap/QA, riesgos/preguntas, handoffs, diagnóstico, trazabilidad e informes históricos. Las referencias `1×10` conservadas quedan etiquetadas como evidencia sustituida.
- [x] 2026-08-18 MST — La sección 21 del paquete queda como prompt M5 canónico condicionado: verifica enum/check/composición `4+2`/selección manual/límite 30–31 y se detiene si M4A no está verde.
- [x] 2026-08-18 MST — Validación documental: `git diff --check` y enlaces Markdown locales verdes; scan del diff documental sin material de credenciales de alta confianza y sin patrones de email/teléfono. No se ejecutan base, migraciones, suites o build porque esta reconciliación no modifica código.
- [x] 2026-08-18 MST — El propietario elimina también el límite de los dos sustitutos. Contrato final: cuatro primary + dos substitute, todos ilimitados; sustitutos sin iniciales y selección manual.
- [x] 2026-08-18 MST — Guard MySQL exacto verde y baseline M1–M4 verde: 33 pruebas/521 aserciones.
- [x] 2026-08-18 MST — Migración aditiva/rollback/forward verificada: `substitute 10→NULL`; check vigente sólo acepta `NULL` para ambas funciones; rollback aborta si un sustituto supera diez reemplazos activos.
- [x] 2026-08-18 MST — Actions, Request, controller y UI exigen composición `4+2`, sustitutos sin iniciales y selección manual por ULID; se eliminó todo conteo/rechazo por volumen.
- [x] 2026-08-18 MST — Pruebas dirigidas M1–M4: 33/535; suite completa: 142/1,584; escenario de 31 reemplazos para un mismo sustituto y selección independiente del segundo, verde.
- [x] 2026-08-18 MST — Pint, Composer validate/platform/audit, build, migraciones y gates técnicos verdes; Yarn conserva un advisory bajo conocido de Quill, sin vulnerabilidades moderate+.
- [x] 2026-08-18 MST — Menús JSON parseados con PHP porque `jq` no está instalado; 66 rutas, schedule de purga XLSX, 17 migraciones, enlaces Markdown, `git diff --check` y scan del diff verdes.
- [x] 2026-08-18 MST — UAT Firefox local en escritorio/tableta/móvil: ambos sustitutos `Sin límite`, selección manual, cobertura `3/4→4/4`, reflow sin overflow y consola sin warnings/errors.
- [x] 2026-08-18 MST — Datos sintéticos retirados mediante `migrate:fresh --seed` protegido; quedaron cero usuarios, perfiles, asignaciones y conflictos.
- [x] Implementación M4A local/test y gates completos bajo la autorización vigente.
- [x] M5 puede proponerse como milestone separado después de M4A `GO LOCAL/TEST`; este ExecPlan no lo implementa.
- [x] 2026-08-18 MST — Entrada histórica posterior: M5 se ejecutó en su propio ExecPlan y quedó `GO LOCAL/TEST`; el paquete inmutable fue compartido por reemplazos manuales hacia ambos sustitutos. M6 permanece separado y no autorizado por este plan.

## Resultado

`GO LOCAL/TEST`. El contrato ilimitado final está implementado y validado sin tocar producción. El rollback conserva una protección deliberada: no restaura el antecedente `substitute=10` si los datos actuales ya lo exceden.
