# ExecPlan — Fase 02B M4: asignaciones manuales y conflictos

> **Corrección final vigente — 2026-08-18:** el resultado de este ExecPlan acredita el contrato histórico `4 primary + 1 substitute × 10`. El propietario lo sustituyó finalmente por `4 primary + 2 substitute`, todos ilimitados. M4A ya ajustó esquema/código/selección manual y repitió gates; no se reescribe la evidencia histórica siguiente.

**Estado:** COMPLETE — GO LOCAL/TEST — NOT AUTHORIZED FOR PRODUCTION
**Fecha:** 2026-08-18 (`America/Hermosillo`)
**Repositorio:** `/home/ccortesg/workspace/flowerflow`

## Propósito y resultado observable

Implementar exclusivamente M4 sobre M1–M3: cobertura manual e idempotente de cada propuesta elegible con cuatro asignaciones iniciales a jueces `primary`, declaración propia de conflicto y resolución administrativa append-only mediante reemplazo por el único `substitute`, con límite transaccional de diez reemplazos activos. El juez sólo podrá ver alias opaco, categoría, plazo y estado; M5 y todo contenido de propuesta/anexo permanecen fuera.

## Baseline Git protegido

- `pwd` y toplevel: `/home/ccortesg/workspace/flowerflow`.
- Rama: `codex/submission-deadline-extension`.
- HEAD, upstream y ancestro común: `865059ad302ff4195ac18f671bd6fa13b99e398b`.
- El árbol ya contenía M3 completo sin commit: 22 archivos tracked modificados y 24 no rastreados.
- SHA-256 del diff tracked preexistente M3: `51ef90ae6838c9f9c304997ab6d7b2cb110051b256db5db4341dd31c44ebb6a0`.
- `git diff --check` inicial: verde.
- Ese trabajo preexistente se preserva; no se hará stage, commit, push, reset, clean, checkout destructivo ni despliegue.
- Producción no será consultada; `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR`.

## Alcance e invariantes

- Sólo asignación manual por propuesta, conflictos y reemplazo; sin lote automático, scheduler o seeder de asignaciones.
- Elegible significa `submissions.status=submitted`, versión final existente y `eligibility_reviews.status=admitted` para esa misma versión.
- La cobertura exige exactamente cuatro perfiles `primary` activos, un perfil `substitute` activo y una sola rúbrica `active` completa para la competencia.
- Cada asignación fija `submission_version_id`, `judge_profile_id`, `rubric_version_id` y plazo UTC equivalente a `2026-08-27 23:59:59 America/Hermosillo`.
- El sustituto nunca recibe una asignación `initial`; admite como máximo diez `replacement` en `active|conflict_declared`.
- Conflicto del titular bloquea de inmediato. Sólo `admin` resuelve: original `voided`, conflicto `resolved_reassigned` y reemplazo independiente `active` ligado por `replaces_assignment_id`.
- No se cambia `submissions.status`, snapshots, folios, admisibilidad, aceptaciones, perfiles/capacidad ni rúbrica.
- `/juez/asignaciones` nunca mostrará título, folio, resumen, texto, integrantes, enlaces, anexos, nombres de archivo, PII, residencia, notas, aclaraciones, historial, otros jueces, rúbrica, scores o ranking.
- `P2B-BLOCK-001=RESOLVED BY OWNER 2026-08-18`. Si no hay sustituto autorizado disponible, el sistema falla cerrado y registra el riesgo operativo.

## Modelo y contratos previstos

- `judge_assignments`: ULID, competencia, versión enviada, perfil, rúbrica, `initial|replacement`, `active|conflict_declared|voided|cancelled`, plazo, asignación reemplazada, actores/fechas/razones y unicidades defensivas.
- `judge_conflicts`: ULID, asignación, juez declarante, catálogo exacto, explicación condicional, declaración y resolución/reasignación con actor/razón/asignación sustituta.
- Permisos: admin `view/manage evaluation assignments` y `resolve evaluation conflicts`; judge `declare own evaluation conflicts`. Ningún rol recibe permisos por agregación global incompatible.
- Cobertura se deriva de asignaciones vigentes; no se persiste como estado de propuesta.
- Las Actions revalidan dentro de transacción y toman locks sobre competencia, versión/propuesta, rúbrica, perfiles, asignaciones y contador del sustituto.

## Plan por pasos

1. Demostrar guard MySQL y baseline funcional M1–M3.
2. Añadir enums, modelos guarded, relaciones y migración aditiva/reversible con checks, índices, FKs y permisos.
3. Implementar servicios de actor/elegibilidad/cobertura, creación manual, conflicto y resolución con auditoría redactada.
4. Añadir Policies, Form Requests, controllers y rutas administrativas/juez fail-closed.
5. Crear UI accesible `/panel/asignaciones` y `/juez/asignaciones` sin contenido M5.
6. Añadir pruebas de autorización, elegibilidad, cobertura, versionado, idempotencia, concurrencia, conflicto, reemplazo y capacidad.
7. Ejecutar forward/rollback/forward, regresión completa, gates y QA Firefox local.
8. Sincronizar documentación, trazabilidad, riesgos, ADR y preparar M5 sólo si M4 queda completamente verde.

## Validación obligatoria

- Guard: `APP_ENV=testing`, MySQL, host loopback, `flowerflow_testing`, `flowerflow_testing_user` y `SELECT DATABASE()` exacto, sin secreto.
- Migración M4 forward/rollback/forward preservando datos M1–M3 sintéticos; sin creación automática de asignaciones/conflictos.
- Suites dirigidas M4/M3/M2/M1 y `php artisan test` completa.
- Pint, Composer validate/platform/audit, Yarn audit, build, JSON, rutas, schedule, migrate status, enlaces Markdown, secretos/PII y `git diff --check`.
- UAT Firefox con admin/judge, tres viewports, teclado, foco, reflow, consola, 403/404, cobertura, conflicto, reemplazo y límite diez/undécima.

## Rollback

- Apagar `FLOWERFLOW_EVALUATION_ENABLED` cierra las superficies juez sin borrar evidencia.
- El `down()` M4 sólo podrá retirar tablas si no existen asignaciones/conflictos; con evidencia abortará antes de destruirla.
- Los permisos M4 sólo se eliminan si no tienen asignación directa incompatible.
- El rollback de código se limita a archivos M4; nunca afecta M1–M3, propuestas, snapshots, rúbrica, perfiles, archivos o aceptaciones.
- No se ejecutará rollback o migración en producción.

## Riesgos

- Carreras de cobertura o reemplazo: locks, unicidades/checks y pruebas con procesos MySQL.
- Elegibilidad/versiones divergentes: revalidación dentro de la transacción y fallo sin filas parciales.
- Sustituto sin capacidad/disponibilidad/conflictuado: cobertura incompleta visible sólo a operación; sin segundo reemplazo inferido.
- Fuga de contenido M5: consultas/vistas con allowlist mínima y pruebas canary de ausencia.
- El estado futuro `in_progress|submitted|reopened` ampliará el conteo en M6/M7; M4 no lo anticipa ni reinterpreta historia.

## Registro vivo

- [x] 2026-08-18 10:46 MST — Lectura obligatoria, prompt canónico y ADR completados; baseline Git verificado y diff M3 protegido.
- [x] 2026-08-18 10:49 MST — Guard exacto verde: testing/MySQL/127.0.0.1/`flowerflow_testing`/`flowerflow_testing_user` y `SELECT DATABASE()` coincidente; regresión inicial M1–M3 24/399.
- [x] 2026-08-18 MST — Modelo, dominio, autorización y UI M4 completos: cobertura manual, conflicto propio, reemplazo append-only y proyección juez mínima.
- [x] 2026-08-18 MST — Forward/rollback/forward M4, 9/116 dirigidas M4, 33/521 M1–M4, suite 142/1,570, Pint/Composer/Yarn/build/JSON/rutas/schedule/whitespace y UAT Firefox verdes; Quill conserva un advisory bajo conocido.
- [x] 2026-08-18 MST — Documentación, ADR, trazabilidad, informe M4 y siguiente puerta M5 sincronizados; `P2B-BLOCK-001` permanece resuelto e implementado localmente.
- [x] 2026-08-18 MST — El propietario sustituye el contrato por dos sustitutos, treinta activas cada uno y seis jueces operativos. El código M4 sigue en `1×10`; se crea el ExecPlan M4A y M5 queda condicionado a su cierre verde.

## Resultado final y evidencia

- Decisión: `GO LOCAL/TEST`; no acredita ni autoriza producción.
- Migración: 16/16 al cierre. El seeder deja una v1 draft, cero usuarios/perfiles/asignaciones/conflictos y no realiza backfill.
- Cobertura: exactamente cuatro iniciales a principales, idempotencia y carrera MySQL de dos procesos convergen en cuatro filas.
- Conflicto: catálogo/ownership/plazo, bloqueo inmediato, resolución administrativa con password confirmation/razón, original `voided` y reemplazo independiente.
- Capacidad: diez reemplazos `active|conflict_declared`; cancelación libera cupo; undécimo rechazado sin fila parcial.
- Aislamiento: juez sólo ve alias/categoría/plazo/estado; canarios de título, folio, resumen, rúbrica y contenido ausentes; 403/404 fail-closed.
- UAT: admin/juez/vacío, cobertura 0→4, conflicto 4→3, reemplazo 3→4, capacidad diez, escritorio/tableta/móvil, teclado/foco/reflow y consola limpia.

## Hallazgos inesperados

- Una prueba de concurrencia basada en `DatabaseMigrations` puede dejar desmontado el esquema después de terminar. Se repitió el guard exacto y se reconstruyó sólo `flowerflow_testing`; el estado final quedó 16/16 sin datos M4.
- El rollback M4 restringido por `--path` emitió avisos de otras migraciones del mismo batch no encontradas; la verificación confirmó que retiró únicamente M4, preservó M1–M3 y el forward posterior fue verde.
- El wrapper Playwright local contenía CRLF y falló en el shebang. Se ejecutó la misma CLI mediante `npx --yes --package @playwright/cli playwright-cli`; no se modificó la herramienta.

## Puerta siguiente

Esta conclusión queda como historia del corte M4. La adenda final vigente está en el encabezado y en el ExecPlan M4A: dos sustitutos ilimitados, selección manual y `GO LOCAL/TEST`. M5 —paquete ciego y anexos autorizados— permanece documentado en la sección 21 como milestone separado y no puede incluir captura, puntajes o cálculo M6.
