# ExecPlan — Fase 02B M5: paquete ciego estructural

**Estado:** GO LOCAL/TEST — M5 COMPLETE — M6 NOT AUTHORIZED
**Fecha:** 2026-08-18 (`America/Hermosillo`)
**Repositorio:** `/home/ccortesg/workspace/flowerflow`

## Propósito y resultado observable

Materializar, exclusivamente en local/test, una proyección ciega estructural, inmutable y versionada de cada `submission_version` cubierta por M4A. Un administrador podrá generar, revisar y activar explícitamente un paquete allowlist; únicamente el juez exacto con asignación propia `active` podrá consumir el paquete activo y descargar sus anexos mediante nombres neutros y autorización privada.

M5 no muestra rúbricas en el shell juez ni implementa evaluación, criterios, puntajes, comentarios, totales, consolidación, envío, reapertura, notificaciones M8, retención, ganadores o resultados.

## Baseline protegido

- `pwd` y Git toplevel: `/home/ccortesg/workspace/flowerflow`.
- Rama: `codex/submission-deadline-extension`.
- HEAD, upstream y ancestro común: `865059ad302ff4195ac18f671bd6fa13b99e398b`.
- El árbol contiene trabajo M1–M4A y documentación preexistentes sin commit; se preservan sin stage, commit, push, reset, clean ni checkout destructivo.
- Guard ejecutado antes de toda operación de esquema: `APP_ENV=testing`, MySQL, host `127.0.0.1`, base `flowerflow_testing`, usuario `flowerflow_testing_user` y `SELECT DATABASE()=flowerflow_testing`.
- Precondición M4A comprobada en código y prueba dirigida: ambos roles derivan capacidad `NULL`, la composición es `4 primary + 2 substitute`, la selección es manual y 31 reemplazos para un sustituto no fallan por volumen.
- No se accede a producción, URL pública, AWS, servicios externos, bases, logs o datos reales.

## Contrato de exposición

| Origen inmutable | Campo | Tratamiento M5 |
| --- | --- | --- |
| `category` | `slug`, `name` | visible |
| `submission` | `participation_type`, `title`, `summary`, `description_html`, `description_text` | visible; HTML nuevamente sanitizado |
| `external_links[]` | `kind`, `url`, `normalized_host` | visible sólo con HTTPS y host ya capturado |
| `files[]` | binario evaluable capturado | visible mediante inventario neutro y descarga privada |
| cualquier otro campo | incluidos IDs de propuesta, folio, fechas, competition, participant y team | excluido por allowlist |
| otras fuentes | residencia, aclaraciones, admisibilidad, auditoría y archivos no capturados | excluido siempre |

La ceguera es estructural. El contenido sustantivo o los binarios pueden autoidentificar a su autor y ese riesgo fue aceptado expresamente; la plataforma no promete anonimato semántico ni altera contenido por inferencia.

## Modelo e invariantes

- `blind_review_packages` será uno-a-uno con `submission_versions` mediante FK y unicidad; usa ULID público, versión de esquema, estado `draft|active|invalidated`, payload JSON allowlist, hash SHA-256 canónico y actores/razones/fechas técnicas.
- `blind_review_package_files` registra sólo archivo técnico, orden, clase `document|editor_image`, etiqueta neutra, MIME, extensión, tamaño y SHA esperados. Nunca conserva nombre original, path, stored name o PII.
- El builder lee el snapshot v1, valida forma y pertenencia contra `SubmissionFile`, exige igualdad exacta del inventario y produce salida determinista. No consulta residencia, aclaraciones ni el estado vivo para completar datos faltantes.
- Un paquete activado o invalidado y su inventario son terminales e inmutables; un draft puede regenerarse explícitamente antes de activarse.
- Activar exige propuesta enviada y admitida en la misma versión, cobertura M4 vigente, integridad reproducible, administrador exacto, contraseña confirmada y razón de 20–1,000 caracteres.
- La reasignación conserva `submission_version_id` y, por ello, comparte exactamente el mismo paquete. No se copia ni regenera en el acceso juez.
- El consumo juez exige rol exacto `judge`, correo verificado, perfil `active`, flag vigente, asignación propia `active`, paquete `active` y archivo allowlisted de la misma versión.
- La descarga revalida tamaño, SHA, MIME y firma; cualquier drift falla cerrado sin revelar rutas o nombres internos.

## Implementación

1. Añadir enums, migración, modelos y relaciones M5 con checks, FKs, índices y unicidades compatibles con MySQL.
2. Añadir permisos administrativos separados y conservar al juez sin permisos de panel o archivos participantes.
3. Implementar canonicalización, verificación binaria y builder determinista allowlist.
4. Implementar Actions transaccionales, Requests, Policies y controladores para generación/regeneración draft y activación explícita.
5. Crear `/panel/paquetes-ciegos` y ampliar el detalle de asignación del juez sin incorporar M6.
6. Añadir descarga privada M5 con nombre neutro, `nosniff`, autorización por assignment y auditoría redactada.
7. Cubrir matriz positiva/negativa, canarios de PII, XSS, drift, inmutabilidad, idempotencia y concurrencia.
8. Ejecutar migración forward/rollback/forward, suites y gates; realizar UAT Firefox local con datos sintéticos.
9. Sincronizar documentación, trazabilidad, ADR y este registro vivo con evidencia real.

## Pruebas y gates previstos

- Migración M5 forward/rollback/forward en `flowerflow_testing` sin generación automática ni cambios en M1–M4A.
- Pruebas dirigidas M5/M4A/M4/M3/M2/M1 y suite completa.
- Matriz negativa completa de rol, estado, ownership y estado de asignación/paquete.
- Builder/hash deterministas; rechazo de schema, forma e inventario divergente; canarios ausentes fuera de contenido sustantivo aprobado.
- Activación idempotente/concurrente, inmutabilidad y acceso compartido por replacement.
- Descarga propia, IDOR, neutralidad de headers/nombres y drift fail-closed.
- Pint, Composer, Yarn, build, JSON, rutas, schedule, estado de migraciones, enlaces Markdown, diff y scan de secretos/PII.
- UAT Firefox 1440×900, 1024×768 y 390×844 con teclado, foco, reflow, zoom, consola y 403/404.

## Riesgos y mitigaciones

- **Identidad dentro de contenido:** riesgo aceptado; aviso explícito de anonimización estructural y ninguna promesa semántica.
- **Snapshot o binario divergente:** construcción/activación/descarga fallan cerradas y sólo auditan IDs técnicos.
- **Fuga mediante HTML o nombres:** resanitización, allowlist, URLs internas M5 y etiquetas neutras.
- **Carrera de generación/activación:** locks de versión, paquete, archivos y cobertura, unicidad por versión e idempotencia por hash.
- **Regresión de acceso:** Policy ligada a la asignación exacta y matriz negativa M1–M4A.

## Rollback

- Apagar `FLOWERFLOW_EVALUATION_ENABLED` cierra el shell juez sin borrar evidencia.
- Antes de activar paquetes, la migración M5 puede revertirse sólo en test después de retirar datos sintéticos M5; no toca snapshots ni archivos originales.
- Si ya existiera evidencia M5 que deba preservarse, no se ejecuta `down`: se corrige mediante una migración aditiva.
- El rollback de M5 nunca modifica propuestas, versiones, folios, admisibilidad, rúbricas, asignaciones, conflictos, perfiles o aceptaciones.

## Registro vivo

- [x] 2026-08-18 MST — Baseline Git registrado y cambios M1–M4A preexistentes preservados.
- [x] 2026-08-18 MST — Guard MySQL exacto verde sin exponer contraseña.
- [x] 2026-08-18 MST — Precondición M4A verificada: capacidad `NULL` para ambos roles, composición `4+2`, selección manual y prueba de 31 reemplazos verde (1 prueba, 5 aserciones).
- [x] 2026-08-18 MST — Migración/modelo/permisos M5 implementados; forward/rollback/forward preservó evidencia M1–M4A y cerró en 18/18 migraciones.
- [x] 2026-08-18 MST — Builder determinista, canonicalización SHA-256, integridad binaria, Actions, Policies, rutas, panel admin, shell juez y descarga neutra implementados.
- [x] 2026-08-18 MST — Pruebas M5 8/119; regresión dirigida M1–M5 41/654; suite definitiva 150/1,703 en 268.33 s.
- [x] 2026-08-18 MST — Pint, Composer validate/platform/audit, JSON, 71 rutas, schedule, 18 migraciones y build Vite verdes. Yarn mantiene sólo el advisory bajo conocido de Quill sin parche.
- [x] 2026-08-18 MST — UAT Firefox verde en escritorio/tableta/móvil/reflow: generación/preview/activación, allowlist, descarga neutra, 404/IDOR, conflicto, pérdida inmediata y reemplazo manual hacia ambos sustitutos; consola limpia.
- [x] 2026-08-18 MST — Hallazgos no bloqueantes: una sonda Tinker inicial falló por alias/quoting y se repitió completa con facade/guard; el wrapper Playwright tenía CRLF y se usó el CLI oficial vía `npx` con Firefox. No hubo mutación fuera de testing.
- [x] 2026-08-18 MST — Datos y dos binarios sintéticos UAT retirados; `migrate:fresh --seed` dejó cero usuarios y cero paquetes en `flowerflow_testing`.
- [x] 2026-08-18 MST — Documentación, informe `docs/23-phase-02b-m5-blind-package-implementation-report-2026-08-18.md` y prompt canónico M6 sincronizados.

## Resultado

`GO LOCAL/TEST`. M5 queda completo bajo el contrato de ceguera estructural, sin evidencia productiva y sin autorización para M6. El riesgo semántico aceptado y el advisory bajo de Quill permanecen visibles. La siguiente puerta documental es M6 —borrador y cálculo servidor— mediante autorización separada.
