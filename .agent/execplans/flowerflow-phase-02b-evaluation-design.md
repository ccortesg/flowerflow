# ExecPlan — Fase 02B: identidad de jueces y evaluación

**Estado:** `DESIGN UPDATED — M1–M5 GO LOCAL/TEST — M6 NEXT SEPARATE MILESTONE`

> **Estado vigente — 2026-08-18:** el propietario reemplazó los contratos históricos `1 substitute × 10` y `2 substitutes × 30` por `4 primary + 2 substitute`, todos sin límite. M4A y el paquete ciego M5 quedaron verdes local/test; M6 conserva autorización y ejecución separadas.

**Fecha de apertura:** 2026-08-18 (`America/Hermosillo`)

**Repositorio:** `/home/ccortesg/workspace/flowerflow`

## Propósito

Preparar un contrato implementable, seguro y trazable para identidad/acceso de jueces, alta directa, perfiles, asignaciones, evaluación ciega, conflictos, rúbrica versionada, borradores, cálculo en servidor, envío inmutable, reapertura administrativa, auditoría, notificaciones y QA. Este plan no autoriza código, migraciones, seeders, pruebas, instalaciones, bases de datos, servicios externos ni producción.

El paquete de decisión autoritativo es `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`. El propietario respondió las 21 decisiones el 2026-08-18. Las opciones descartadas se conservan como historia. M1–M5 quedaron verdes sólo en local/test: M4A reconcilia la composición `4+2`, la selección manual y la capacidad ilimitada; M5 añade la proyección ciega allowlist. M6 exige su propio alcance y no se implementa en este plan.

## Límites e invariantes

- No implementar jueces, ganadores, resultados, ranking público/global, premios, categoría desierta, actas, galería, campañas, ARCO completo ni borrado automático de residencia.
- No modificar propuestas, folios, snapshots, aceptaciones jurídicas ni evaluaciones reales.
- No acceder a producción, URL pública, AWS, EC2, SSH, SSM, APIs, MySQL productivo, logs o servicios externos.
- No ejecutar migraciones, seeders, comandos de base de datos, instalaciones, builds ni pruebas que recreen esquemas.
- No hacer stage, commit, push, deploy, reset, clean ni checkout destructivo.
- Conservar Laravel 12, Blade, Materialize/Pixinvent, Fortify, Spatie Permission, Policies, Form Requests, Actions/Services y MySQL.
- Código futuro en inglés; interfaz y operación en español; UTC persistido y `America/Hermosillo` presentado.
- Juez sin asignación no ve la propuesta; ningún juez accede a PII estructurada de contacto, comprobantes de residencia, notas internas, aclaraciones, historial de admisibilidad o recursos no autorizados. El propietario aceptó el riesgo residual de identidad incluida por participantes dentro de texto, imágenes, enlaces o anexos evaluables.
- El navegador nunca es fuente de verdad del total. Enviar una evaluación no declara ganador.

## Baseline verificado

| Dato | Evidencia 2026-08-18 |
|---|---|
| `pwd` / Git toplevel | `/home/ccortesg/workspace/flowerflow` |
| Rama | `codex/submission-deadline-extension` |
| SHA local | `e0fa0455e61afcb38593b62ae0d983f75a92b210` |
| SHA remoto | `origin/codex/submission-deadline-extension` = `e0fa0455e61afcb38593b62ae0d983f75a92b210` |
| Ancestro común | `e0fa0455e61afcb38593b62ae0d983f75a92b210` |
| Estado inicial | limpio; sin archivos modificados o no rastreados |
| Diff inicial | vacío |
| Producción | `OWNER_CONFIRMED_DEPLOYED`; más de 50 propuestas reales según propietario |
| SHA productivo | `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR` |

La confirmación productiva es testimonial y actualiza el paso operativo documentado; no demuestra por sí misma SHA, migraciones, flags, workers, scheduler, SMTP, monitoreo, integridad, smoke ni UAT productiva por rol. Este plan no intenta obtener esa evidencia.

## Hallazgos del código al abrir el diseño — baseline histórico pre-M1

Los siguientes puntos describen el SHA/árbol leído antes de implementar M1. Se conservan como evidencia histórica; el estado vigente de roles/gates está en el ExecPlan M1 y en el registro vivo de este documento.

1. El seeder sólo crea `participant`, `reviewer` y `admin`; `judge` aparece únicamente como rol sintético sin permisos en una prueba negativa.
2. Las rutas `/inicio`, `/perfil`, `/propuestas` y admisibilidad participante usan `auth` + `verified`, sin gate `participant`.
3. `DashboardController` redirige sólo `admin`/`reviewer`; `layouts/flowerflow.blade.php` clasifica como participante a cualquier autenticado que no tenga esos dos roles.
4. `FinalizeSubmission` excluye de elegibilidad sólo `admin`/`reviewer`. Crear `judge` sin corregir estas fronteras abriría el shell y acciones participantes por accidente.
5. Fortify, verificación de correo, TOTP/recuperación y confirmación de contraseña existen; 2FA es opcional y no hay suspensión ni revocación administrativa de sesiones.
6. Spatie Permission, Policies, rate limiters, transacciones con locks, eventos inmutables y `AuditLogger` son patrones reutilizables.
7. La propuesta enviada tiene snapshot inmutable; el snapshot contiene identidad, equipo, nombres originales y enlaces, por lo que no es una proyección ciega utilizable directamente.
8. Anexos de propuesta, aclaraciones y residencia están separados físicamente y por Policies. La residencia y las notas internas no pueden reutilizarse en la superficie de juez.
9. Admisibilidad `admitted` puede ser precondición de elegibilidad futura, pero no cambia el estado `submissions` ni crea asignaciones.
10. No existen tablas, modelos, enums, Requests, Policies, rutas, vistas, factories o pruebas de jueces/evaluación.

## Resultado de preparación

- Estado funcional: M1–M5 conformes local/test bajo el contrato final `4+2` ilimitado.
- Preparación: migraciones/código/UI/pruebas M4A y M5 están verdes; no se atribuyen a producción.
- Puerta actual: M6 como ejecución separada limitada al borrador y cálculo servidor.
- `P2B-BLOCK-001` y `P2B-M4-CORRECTION-001` están resueltos local/test.
- Puerta posterior: M6 sólo después de M5 verde y sin mezclar confirmación/envío/reapertura M7.

## Plan de este milestone documental

- [x] Leer completamente reglas, ExecPlans, diagnóstico, especificaciones, runbooks y ADR solicitados.
- [x] Registrar baseline Git y preservar el árbol limpio.
- [x] Auditar rutas, roles, permisos, Policies, modelos, migraciones, snapshots, storage, auditoría, correo, colas, 2FA, layouts y pruebas reales.
- [x] Separar hechos implementados, decisiones confirmadas, recomendaciones y `PROPOSAL_NEEDED`.
- [x] Diseñar contratos de identidad, asignación, proyección ciega, rúbrica, estados, cálculo, seguridad, UX, notificaciones y compatibilidad de datos.
- [x] Preparar matriz de 21 decisiones y respuesta numerada del propietario.
- [x] Reconciliar las 21 respuestas del propietario, sus consecuencias y el bloqueo cruzado de capacidad/cobertura.
- [x] Preparar diez milestones futuros, sin autorización de implementación.
- [x] Ejecutar gates documentales finales y registrar resultados reales.

## Milestones de implementación

Cada milestone requiere un ExecPlan acotado, base local/test protegida, datos sintéticos, revisión de migraciones y aprobación expresa. M1–M5 ya fueron ejecutados y cerrados exclusivamente en local/test.

1. **Completado local/test:** roles, permisos, gate de rutas y seguridad base.
2. **Completo local/test:** perfil de juez y alta directa administrativa.
3. **Completo local/test:** rúbrica versionada.
4. **Completo local/test:** asignaciones y conflictos, incluida reconciliación M4A ilimitada.
5. **Completo local/test:** proyección ciega y anexos autorizados.
6. Evaluación en borrador y cálculo servidor.
7. Confirmación, envío inmutable y eventual reapertura.
8. Notificaciones y auditoría.
9. QA automatizada y UAT por rol.
10. Preparación de release exclusivamente local.

Los criterios, dependencias, migraciones probables, pruebas, riesgos y rollback de cada milestone están detallados en el paquete de decisión.

## Estrategia de modelo aprobada para planificación — todavía no implementada

El diseño favorece migraciones aditivas y referencias a la versión enviada, sin tocar `submissions.status`, folios, snapshots ni aceptaciones:

- `judge_profiles`; el alta es directa por `admin` y no se crea `judge_invitations`;
- `rubrics`, `rubric_versions` o una entidad equivalente inmutable y `rubric_criteria`;
- `blind_review_packages` y referencias a anexos explícitamente autorizados;
- `judge_assignments` ligados a `submission_version_id`, juez y versión de rúbrica;
- `conflict_declarations` con resolución administrativa;
- `evaluations`, revisiones/versiones de evaluación y `evaluation_scores`;
- eventos/auditoría append-only e idempotencia de notificaciones.

Los nombres físicos finales y campos exactos deberán cerrarse en el ExecPlan de cada milestone. No se crearán fuera de un prompt posterior expresamente autorizado. La estrategia aditiva nunca debe asignar jueces ni modificar propuestas existentes por inferencia.

## Validación documental prevista

- Búsqueda de contradicciones sobre producción, siguiente puerta, autorización 02B, roles, ceguera, campos visibles, número de jueces, rúbrica, empates, ganadores y resultados.
- Confirmar que las 21 decisiones vigentes estén marcadas `OWNER_APPROVED`, que las alternativas descartadas estén identificadas como históricas y que `P2B-BLOCK-001` se distinga de su implementación M4 local/test.
- Revisión de enlaces Markdown locales.
- Revisión de secretos, credenciales y PII en el diff.
- `git diff --check`, `git diff --name-only` y `git status --short`.
- Confirmar que sólo cambien Markdown/ExecPlans.

No se ejecutan PHPUnit, Pint, Composer, Yarn, Artisan, migraciones, seeders, instalaciones ni build porque no cambia código.

## Rollback de este milestone

Los cambios son exclusivamente documentales. El rollback futuro consiste en revertir de forma selectiva los archivos Markdown de este milestone, preservando entradas históricas append-only. No hay rollback de base, storage, dependencias o runtime porque no se modifican.

## Registro de decisiones

- `DECISION HISTÓRICA`: este ExecPlan autorizó sólo diseño; M1 recibió después un prompt y ExecPlan de implementación separados.
- `DECISION`: identidad/residencia/notas internas quedan fuera del acceso de juez.
- `DECISION`: sólo propuestas asignadas pueden evaluarse.
- `DECISION`: total exclusivamente servidor; evaluación no declara ganador; no existe selección aleatoria.
- `DECISION`: datos existentes se protegen con cambios aditivos y sin backfills inferidos.
- `OWNER_APPROVED` 2026-08-18: las 21 decisiones del paquete quedaron respondidas; el contrato completo se conserva en la sección 17 del paquete y en ADR-0008.
- `DECISION`: roles estrictamente excluyentes, alta directa, asignación manual, ceguera simple estructural, rúbrica/fórmula aprobadas, evaluación append-only, 2FA opcional, notificaciones mínimas, retención de 24 meses y empate por igualdad redondeada.
- `OWNER FINAL — P2B-BLOCK-001`: cuatro principales y dos sustitutos exclusivos, todos ilimitados. Los antecedentes `1×10` y `2×30` se conservan como historia; M4A implementa capacidad nula y selección manual.

## Registro vivo

- [x] 2026-08-18 MST — Baseline Git limpio y sincronizado en `e0fa0455e61afcb38593b62ae0d983f75a92b210`.
- [x] 2026-08-18 MST — `docs/11-operations-handoff.md` no existía en el baseline; se crea como handoff vigente sin reemplazar `docs/11-local-development.md`.
- [x] 2026-08-18 MST — Se registra `OWNER_CONFIRMED_DEPLOYED` y `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR`; no hubo acceso externo.
- [x] 2026-08-18 MST — Auditoría de código confirma 0 % de implementación de juez/evaluación y riesgo de caída al shell participante.
- [x] 2026-08-18 MST — Paquete de decisiones y plan de diez milestones preparados; implementación continúa bloqueada por aprobación.
- [x] 2026-08-18 MST — Gates documentales: Pandoc GFM procesó 15 archivos; dos enlaces Markdown locales válidos; matriz 21/21 y diez milestones; scan de secretos/PII sin hallazgos; sólo Markdown/ExecPlans; `git diff --check` y whitespace de archivos nuevos limpios. No se ejecutaron pruebas/build/migraciones por alcance.
- [x] 2026-08-18 MST — El propietario respondió `P2B-DEC-001`–`021`; se registra el contrato como `OWNER_APPROVED`, preparación documental/técnica de 78 % y Fase 02B funcional en 0 %.
- [x] 2026-08-18 MST — Se identifica `P2B-BLOCK-001`: las decisiones aprobadas de cobertura, capacidad y sustitución no son ejecutables simultáneamente con el volumen informado. M4 queda bloqueado sin impedir M1.
- [x] 2026-08-18 MST — Se prepara el prompt exacto del Milestone 1 y se sincroniza en el paquete y el diagnóstico; no se autoriza M2–M10 ni se modifica código.
- [x] 2026-08-18 MST — Reconciliación final validada: 21/21 IDs únicos `OWNER_APPROVED`; prompt M1 idéntico en dos fuentes; Pandoc GFM sobre 19 archivos, dos enlaces locales, `git diff --check`, scan de secretos/PII y alcance sólo Markdown verdes. No se ejecutaron pruebas/build/migraciones por alcance.
- [x] 2026-08-18 MST — M1 fue autorizado en un prompt posterior y se ejecutó mediante `.agent/execplans/flowerflow-phase-02b-m1-judge-rbac.md`: 13/13 migraciones, forward/rollback/forward, 115 pruebas/1,141 aserciones, 6/92 dirigidas, build y QA Firefox verdes.
- [x] 2026-08-18 MST — M1 queda `GO LOCAL/TEST`; se sincroniza el siguiente gate a M2. M3–M10 siguen sin autorización y `P2B-BLOCK-001` continúa bloqueando M4.
- [x] 2026-08-18 MST — M2 queda `GO LOCAL/TEST`: 14/14 migraciones, 125 pruebas/1,306 aserciones, alta/activación/suspensión/recovery y UAT local verdes. M3 es la siguiente puerta; M4 continúa bloqueado.
- [x] 2026-08-18 MST — El propietario resuelve `P2B-BLOCK-001`: cuatro principales sin límite fijo y quinto sustituto exclusivo con capacidad diez. Antes del primer commit M2 se alinea a `primary=NULL`/`substitute=10`; M4 queda no implementado/no autorizado, no bloqueado por capacidad.
- [x] 2026-08-18 MST — M3 queda `GO LOCAL/TEST`: rúbrica global versionada, activación/sustitución e inmutabilidad verdes; M4 queda listo para autorización separada.
- [x] 2026-08-18 MST — M4 queda `GO LOCAL/TEST`: cobertura manual de cuatro principales, conflicto propio, sustitución append-only y capacidad diez fail-closed; M5 es la siguiente puerta y permanece no autorizado.
- [x] 2026-08-18 MST — Decisión posterior fija seis jueces operativos: cuatro primary sin límite y dos substitute con treinta activas cada uno. Se registra M4A como puerta correctiva previa a M5; no se presenta el código `1×10` como vigente.
- [x] 2026-08-18 MST — M3 queda `GO LOCAL/TEST`: 15 migraciones, rúbrica global v1 draft idempotente, ciclo `draft|active|superseded`, permisos admin, concurrencia/inmutabilidad y UAT Firefox verdes; suite 133/1,448. M4 pasa a siguiente puerta separada.
- [x] 2026-08-18 MST — La corrección queda validada con M2 10/175, M1+M2 16/267, suite completa 125/1,316, migración compatible y QA Firefox escritorio/móvil; `flowerflow_testing` vuelve a cero cuentas/perfiles/sesiones.
- [x] 2026-08-18 MST — M4A cierra el contrato final `4+2` ilimitado y M5 queda `GO LOCAL/TEST`: 18 migraciones, paquete allowlist/hash, anexos neutros, permisos, drift fail-closed, UAT Firefox con ambos sustitutos y suite 150/1,703. M6 es la siguiente puerta separada.

## Cierre de este ExecPlan

Este ExecPlan cerró el diseño original y conserva su historia. Las implementaciones M1–M5 se mantienen en ExecPlans separados. La decisión ilimitada está reconciliada local/test por M4A y la ceguera estructural por M5; M6 sólo puede ejecutarse mediante su prompt separado. No acredita producción.
