# Informe de implementación Fase 02B M4 — asignaciones y conflictos

> **Corrección final vigente — 2026-08-18:** este informe acredita el contrato histórico `4 primary + 1 substitute × 10`. El owner lo sustituyó primero por `4+2 × 30` y finalmente por seis jueces ilimitados. M4A está `GO LOCAL/TEST`; la evidencia histórica siguiente se conserva y el estado vigente está en `docs/22-phase-02b-m4a-unlimited-judges-implementation-report-2026-08-18.md`.

**Fecha:** 2026-08-18 (`America/Hermosillo`)

**Decisión:** `GO LOCAL/TEST — NOT AUTHORIZED FOR PRODUCTION`

**Repositorio:** `/home/ccortesg/workspace/flowerflow`

## 1. Resumen ejecutivo

M4 implementa exclusivamente la asignación manual de propuestas elegibles a cuatro jueces principales, el conflicto declarado por el juez propietario y su resolución administrativa append-only mediante el quinto juez sustituto. La cobertura, versión enviada, rúbrica y fecha límite quedan fijadas; la undécima sustitución activa falla cerrada.

M1–M3 permanecen verdes. M4 no crea jueces, asignaciones o conflictos por migración/seeder; no expone contenido de propuesta, rúbrica, PII o archivos al juez; no implementa paquete ciego M5, evaluación M6, envío/reapertura, notificaciones, retención, ganadores o resultados. Producción y la URL pública no fueron consultadas; `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR`.

En ese corte histórico, M4 materializó principales sin límite y un sustituto con capacidad diez. La corrección intermedia M4A `2×30` también fue sustituida por `4+2` ilimitado y después M5 quedó verde; esto no cambia los resultados de prueba históricos.

## 2. Baseline y guard MySQL

| Evidencia | Resultado |
|---|---|
| `pwd` / Git toplevel | `/home/ccortesg/workspace/flowerflow` |
| Rama | `codex/submission-deadline-extension` |
| HEAD/remoto/merge-base inicial | `865059ad302ff4195ac18f671bd6fa13b99e398b` |
| Estado inicial | M3 preexistente: 22 tracked modificados y 24 no rastreados; preservados |
| SHA-256 diff tracked inicial | `51ef90ae6838c9f9c304997ab6d7b2cb110051b256db5db4341dd31c44ebb6a0` |
| Entorno | `APP_ENV=testing` |
| Driver/host | MySQL / `127.0.0.1` |
| Base/usuario | `flowerflow_testing` / `flowerflow_testing_user` |
| `SELECT DATABASE()` | `flowerflow_testing` |

No se imprimió ni copió la contraseña. Todas las mutaciones, archivos, cuentas y propuestas fueron sintéticos y se limitaron a la base aislada.

## 3. Modelo, estados e invariantes

### `judge_assignments`

- ULID público y FKs restrictivas a competencia, versión enviada, perfil de juez, versión de rúbrica, actor y asignación reemplazada;
- tipo `initial|replacement` y estado `active|conflict_declared|voided|cancelled` respaldados por enums y `CHECK`;
- `current_slot=1` sólo para una asignación vigente y `NULL` para estados terminales;
- unicidad defensiva por versión/juez/slot y una sola sustitución por asignación original;
- fecha límite fijada en UTC equivalente a `2026-08-27 23:59:59 America/Hermosillo`;
- modelos guarded e inmutables; las transiciones autorizadas ocurren dentro de Actions transaccionales auditadas.

### `judge_conflicts`

- una declaración por asignación;
- catálogo exacto `personal_or_family_relationship`, `professional_or_economic_relationship`, `participation_in_submission` y `other`;
- `other` exige explicación de 20–1,000 caracteres y los demás tipos no guardan explicación libre;
- ciclo `declared → resolved_reassigned` con actor, razón, timestamps y asignación sustituta;
- la resolución no sobrescribe ni elimina la asignación original: la deja `voided` y crea una `replacement` independiente.

### Elegibilidad y cobertura

Una propuesta sólo es elegible si está `submitted`, tiene `submitted_at`, conserva versión final vigente y esa misma versión tiene revisión `admitted`. Activar cobertura exige, bajo locks:

1. una rúbrica activa válida y determinística;
2. exactamente cuatro perfiles activos `primary` y uno `substitute`;
3. roles `judge` exactos, correo verificado y contraseña propia establecida;
4. cero cobertura divergente preexistente.

La operación es manual, por propuesta e idempotente. Crea exactamente cuatro asignaciones iniciales sólo a principales. La cobertura `0..4` se deriva de las filas vigentes y nunca cambia `submissions.status`.

## 4. Matriz efectiva de acceso

| Actor/estado | `/panel/asignaciones` | Activar cobertura/resolver | `/juez/asignaciones` | Declarar conflicto | Contenido M5 |
|---|---:|---:|---:|---:|---:|
| Visitante | Login | No | Login | No | No |
| `participant` | 403 | 403 | 403 | 403 | No |
| `reviewer` | 403 | 403 | 403 | 403 | No |
| `admin` exacto | Sí | Sí, contraseña y razón | 403 como juez | No | No |
| `judge active` asignado | 403 | 403 | Sólo propias | Sí, sólo vigente/propia | No |
| `judge active` no asignado | 403 | 403 | Estado vacío | No | No |
| `judge pending_setup/suspended` | 403 | 403 | Bloqueado por M2 | No | No |
| Sin rol / multirol | 403 | 403 | 403 | 403 | No |

Con `FLOWERFLOW_EVALUATION_ENABLED=false`, el shell juez continúa cerrado. Encenderlo no concede permisos ni relaja perfil, correo, rol exacto, ownership o estado.

## 5. Conflicto, reemplazo y capacidad

- declarar conflicto bloquea inmediatamente la asignación y es idempotente sólo para el mismo payload;
- sólo `admin` con `resolve evaluation conflicts`, contraseña actual y razón de 20–1,000 puede resolver;
- el reemplazo conserva versión, rúbrica y plazo de la asignación original;
- el sustituto nunca recibe asignaciones `initial`;
- cuentan para su capacidad diez los reemplazos `active|conflict_declared`; `voided|cancelled` no cuentan;
- la undécima sustitución se rechaza sin mutación parcial;
- si el sustituto está ausente, suspendido, ya asignado a la propuesta o declara conflicto, el sistema no inventa un sexto juez ni una segunda cadena de reemplazo.

## 6. Migración, compatibilidad y rollback

La migración M4 es aditiva y no modifica usuarios, perfiles, propuestas, folios, snapshots, admisibilidad, aceptaciones, rúbrica o archivos existentes. Forward/rollback/forward preservó datos sintéticos M1–M3 y no creó filas M4 automáticamente.

El `down()` aborta antes de destruir tablas si existe una asignación o conflicto. También impide retirar permisos M4 con asignaciones directas incompatibles. El rollback dirigido por `--path` emitió avisos de migraciones de otro batch no encontradas al limitar el path, pero retiró sólo M4; las verificaciones confirmaron tablas M4 ausentes, datos M1–M3 preservados y forward posterior verde.

Al cierre, tras repetir el guard, `migrate:fresh --seed` dejó 16/16 migraciones, cero usuarios, cero perfiles, una rúbrica v1 draft, cero asignaciones y cero conflictos. Esta evidencia es local/testing y no constituye una instrucción productiva.

## 7. Evidencia automatizada y UAT

| Gate | Resultado real |
|---|---|
| Pruebas M4 dirigidas | 9 pruebas / 116 aserciones, incluida concurrencia MySQL de dos procesos |
| Regresión dirigida M1–M4 | 33 / 521 |
| Suite completa | 142 / 1,570 en 125.21 s |
| Pint | Verde |
| Composer validate/platform/audit | Verdes; PHP 8.3.33 y cero advisories PHP |
| Yarn audit | Un advisory bajo conocido de Quill 2.0.3; cero moderados/altos/críticos |
| Build | Node 22.23.1, Yarn 1.22.22, Vite 6.3.5, 98 iconos, 784 módulos y tres assets |
| JSON | `verticalMenu.json` y `horizontalMenu.json` válidos |
| Rutas/schedule/migraciones | 66 rutas propias; purga XLSX horaria; 16/16 migraciones |
| Enlaces/whitespace/secretos | vínculos locales sin roturas; `git diff --check` verde; cero secretos de alta confianza |

Firefox local acreditó con datos sintéticos:

- cobertura administrativa `0/4 → 4/4` y cuatro asignaciones primarias con rúbrica v1 y plazo Hermosillo;
- declaración propia `4/4 → 3/4` y resolución `3/4 → 4/4` mediante sustituto;
- estado vacío para juez activo sin asignaciones;
- diez reemplazos activos y undécimo rechazado con mensaje accesible, sin crear fila;
- título, folio, resumen, rúbrica y demás canarios M5 ausentes de la respuesta juez;
- 403 de juez a panel y 404 para ULID alterado sin stack/SQL;
- 1440×900, 1024×768 y 390×844 sin overflow horizontal; teclado, skip-link/foco y consola sin errores/advertencias.

La utilidad Playwright incluida tenía finales CRLF y no era ejecutable directamente en este entorno. La misma CLI exigida se invocó mediante `npx --yes --package @playwright/cli playwright-cli`; Firefox y el servidor local se cerraron al terminar.

## 8. Auditoría y seguridad

Los eventos de cobertura creada/rechazada, conflicto declarado, asignación anulada, reemplazo creado, conflicto resuelto y reemplazo rechazado conservan actor real, IDs técnicos, transición, código de rechazo y UTC. No copian contraseñas, tokens, TOTP, PII o contenido de propuestas.

La consulta juez carga únicamente IDs técnicos y categoría; la UI muestra alias opaco, categoría, plazo y estado. Título, folio, resumen, contenido, integrantes, archivos, nombres de archivo, enlaces, residencia, notas, aclaraciones, historial, rúbrica, puntajes y otros jueces permanecen fuera.

## 9. Archivos M4 principales

- Dominio: enums `JudgeAssignment*`/`JudgeConflict*`, modelos `JudgeAssignment`/`JudgeConflict`, `AssignmentEligibility` y `JudgeAssignmentCoverage`.
- Casos de uso: `app/Actions/Assignments/` para actor admin/juez, cobertura, declaración y resolución.
- HTTP/autorización: dos controllers, tres Form Requests, dos Policies y rutas protegidas admin/juez.
- Persistencia: `database/migrations/2026_08_18_150000_create_judge_assignments_and_conflicts.php` y permisos idempotentes en `FlowerFlowSeeder`.
- UI: `/panel/asignaciones`, `/juez/asignaciones`, layout/sidebar y dashboard juez.
- Configuración: `FLOWERFLOW_EVALUATION_CLOSE_AT` fail-closed y fecha zonificada en `config/flowerflow.php`/`.env.example`.
- Pruebas: `JudgeAssignmentsAndConflictsTest`, `JudgeAssignmentConcurrencyTest` y ajustes aditivos de regresión M1–M3.
- Documentación: ExecPlan M4, ADR-0008, diagnóstico, producto, alcance, arquitectura, datos, seguridad, UX, roadmap, QA, riesgos, preguntas, handoff, trazabilidad y este informe.

El árbol contiene además el M3 preexistente preservado. No se hizo stage, commit, push, reset, clean o despliegue.

## 10. Riesgos residuales y siguiente puerta

- El único sustituto y su capacidad diez siguen siendo un límite operativo aceptado. Sin reemplazo disponible, la cobertura queda incompleta y requiere decisión del propietario; no se sobreasigna.
- M4 no entrega notificaciones de asignación/conflicto; pertenecen a M8.
- La ceguera estructural M5 todavía no existe. M4 evita fugas no cargando contenido, pero el riesgo semántico aceptado empezará cuando M5 lo proyecte.
- Las descripciones extensas de criterios siguen `POR_CONFIRMAR`; no fueron inventadas.
- Quill 2.0.3 conserva su advisory bajo conocido.
- SHA, migraciones, flags, workers, scheduler, SMTP, monitoreo, integridad y UAT productiva permanecen `POR_CONFIRMAR`.

La siguiente puerta ejecutable es **M4A —dos sustitutos × treinta y selección manual**. La sección 21 conserva el prompt M5 condicionado: debe detenerse hasta M4A verde y no permite mezclar captura/puntajes M6, producción, ganadores o resultados.
