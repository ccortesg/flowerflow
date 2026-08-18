# Informe de implementación Fase 02B M5 — paquete ciego

**Fecha:** 2026-08-18 (`America/Hermosillo`)
**Estado:** `GO LOCAL/TEST — M5 COMPLETE — M6 NOT AUTHORIZED`
**Alcance:** paquete ciego estructural, inventario de anexos y descarga privada; sin evaluación, puntajes, envío, producción o datos reales.

## 1. Decisión ejecutiva

M5 queda `GO LOCAL/TEST`. El sistema genera por acción administrativa explícita una proyección allowlist inmutable de la `submission_version`, calcula un SHA-256 canónico del payload, inventaría exactamente los anexos capturados y sólo los sirve con nombres neutros al juez dueño de una asignación activa. El snapshot crudo, la PII estructurada, residencia, aclaraciones, notas, admisibilidad y nombres/rutas originales no llegan al shell juez.

M1–M4A permanecen verdes. La composición vigente es `4 primary + 2 substitute`, todos ilimitados; una reasignación seleccionada manualmente usa el mismo paquete. M6–M10 continúan no implementados y no autorizados.

## 2. Baseline y guard

| Evidencia | Resultado |
| --- | --- |
| Repositorio | `/home/ccortesg/workspace/flowerflow` |
| Rama | `codex/submission-deadline-extension` |
| HEAD/upstream/merge-base inicial | `865059ad302ff4195ac18f671bd6fa13b99e398b` |
| Árbol inicial | Trabajo M1–M4A preexistente preservado; no hubo stage, commit, push, reset, clean o checkout destructivo. |
| Runtime protegido | `APP_ENV=testing`; MySQL; host `127.0.0.1`; base `flowerflow_testing`; usuario `flowerflow_testing_user`. |
| Base seleccionada | `SELECT DATABASE()=flowerflow_testing`. |
| Producción | No consultada ni modificada. |

## 3. Matriz campo por campo

| Fuente | Campo/contenido | Tratamiento real |
| --- | --- | --- |
| `snapshot.category` | `slug`, `name` | Visible. |
| `snapshot.submission` | `participation_type`, `title`, `summary`, `description_html`, `description_text` | Visible; HTML resanitizado. |
| `snapshot.external_links[]` | `kind`, `url`, `normalized_host` | Visible sólo bajo contrato HTTPS/host capturado; `rel="noopener noreferrer"`; sin fetch servidor. |
| `snapshot.files[]` | binarios `document|editor_image` capturados | Visible una vez mediante etiqueta neutra y descarga M5 privada. |
| propuesta/versión | IDs públicos de propuesta, folio, fechas de captura/envío y competition | Excluidos. |
| participant/team | usuario, representante, nombres, emails, teléfono, nacimiento, colonia | Excluidos. |
| operación | otros jueces, auditoría, notas, razón/historial de admisibilidad | Excluidos. |
| evidencia separada | residencia y archivos de aclaración | Excluidos siempre. |
| metadata de archivo | nombre original, stored name, path, disk y actor | Excluida del paquete/UI/headers. |

La ceguera es estructural, no semántica. Título, resumen, texto, enlaces, imágenes o binarios pueden autoidentificar al participante; el propietario aceptó ese riesgo. La interfaz lo comunica expresamente y no altera contenido por inferencia.

## 4. Modelo, versionado e invariantes

- `blind_review_packages`: ULID público, relación única con `submission_version_id`, `schema_version=1`, estado `draft|active|invalidated`, payload JSON allowlist, SHA-256 canónico y actores/razones/fechas UTC.
- `blind_review_package_files`: relación al paquete y `submission_file_id`, orden, clase, etiqueta neutra determinística, MIME, extensión, bytes y SHA esperado. No persiste nombres o rutas fuente.
- El builder valida esquema y forma del snapshot, pertenencia, inventario exacto y metadata contra `SubmissionFile`; el verificador inspecciona también tamaño, hash, MIME/firma del binario privado.
- Generación/activación requieren `admin` exacto, permisos separados, confirmación de contraseña, razón de 20–1,000 caracteres, propuesta enviada/admitida, cobertura vigente y locks.
- `active`/`invalidated` y sus archivos son inmutables/no eliminables. Activación idéntica converge; divergencia falla sin overwrite.
- El consumo exige judge exacto, verified, perfil active, flag, assignment propia `active` y package `active` de la misma versión.
- Conflicto/anulación corta el acceso inmediatamente. Un replacement activo referencia el mismo package y rúbrica, sin copiar o regenerar contenido.

## 5. Matriz efectiva de acceso

| Actor/estado | Panel M5 | Contenido/anexos M5 |
| --- | --- | --- |
| `admin` exacto con permisos | listar, generar, preview y activar | preview estructural administrativa; no entra como judge |
| `reviewer` / `participant` / visitante | denegado | denegado |
| judge activo, verified, assignment propia active, package active y flag on | denegado | permitido |
| judge no asignado u otro judge | denegado | 403/404 |
| judge pending/suspended, roleless o multirol | denegado | fail-closed |
| assignment `conflict_declared`, `voided` o `cancelled` | no aplica | denegado inmediatamente |
| package ausente/draft/invalidated o flag off | no aplica | sin contenido y sin generación implícita |

## 6. Evidencia técnica

| Gate | Resultado real |
| --- | --- |
| Precondición M4A | capacidad `NULL` para ambos roles; composición `4+2`; selección manual; 31 reemplazos individuales sin límite de volumen. |
| Migración | forward/rollback/forward M5 preservó usuario sintético preexistente y tablas M1–M4A; 18/18 migraciones finales. |
| Pruebas M5 | 8 pruebas, 119 aserciones. |
| Regresión dirigida M1–M5 | 41 pruebas, 654 aserciones. |
| Suite completa definitiva | 150 pruebas, 1,703 aserciones; 268.33 s. |
| Formato | `vendor/bin/pint --test`: verde. |
| Composer | validate estricto, platform requirements y audit: verdes; cero advisories Composer. |
| Yarn | un advisory **low** conocido de Quill por exportación HTML; sin parche disponible; cero moderados/altos/críticos. La resanitización M5 y CSP reducen exposición, sin declarar eliminado el riesgo. |
| Build | Node 22.23.1, Yarn 1.22.22; 98 iconos, 784 módulos y tres assets Vite. |
| JSON/rutas/schedule | menús válidos; 71 rutas propias; rutas M5 presentes; purge de exports permanece programado. |
| Base final | `flowerflow_testing`, 18 migraciones, cero usuarios y cero paquetes UAT. |

Incidentes no bloqueantes registrados: un primer comando auxiliar de Tinker usó el alias `DB` fuera de su namespace y terminó sin mutación con `Class "DB" not found`; se repitió con la facade plenamente calificada y guard verde. La primera sonda de preservación durante rollback tuvo un error de quoting, por lo que se repitió completa y correctamente. El wrapper local de Playwright presentó terminadores CRLF incompatibles con Bash; la UAT se ejecutó con el mismo CLI oficial mediante `npx --package @playwright/cli` y Firefox. Ninguno de estos incidentes cambió producción o datos fuera de `flowerflow_testing`.

Las pruebas colocaron canarios de PII en participant/team/folio/fechas/residencia/notas/aclaraciones/nombres. Su ausencia se verificó en payload serializado, HTML, URL, headers, auditoría y logs. La autoidentificación dentro del contenido sustantivo autorizado permaneció visible como exige el contrato.

La concurrencia confirmó una sola activación/paquete sin filas parciales. Los casos de schema desconocido, inventario missing/duplicate/extra/crossed y drift de binario fallaron cerrados. La descarga autorizada devolvió nombre neutro y `X-Content-Type-Options: nosniff`; ULID/assignment/file alterado no filtró existencia.

## 7. UAT Firefox local

Con cuentas, propuesta y archivos exclusivamente sintéticos se verificó:

- admin: ausencia inicial, generación, preview allowlist y activación explícita;
- judge principal: contenido autorizado, aviso de anonimización estructural, link externo seguro y dos anexos neutros; cero controles de rúbrica/scores/comentarios/total;
- descarga privada correcta como `Documento 01.pdf`;
- 404 de ULID alterado e IDOR cubierto también por matriz automatizada;
- declaración de conflicto con desaparición inmediata de contenido/anexos;
- reasignación manual independiente hacia cada uno de los dos sustitutos; ambos abrieron exactamente el mismo paquete activo;
- 1440×900, 1024×768, 390×844 y viewport 720×450 equivalente de reflow/zoom: sin overflow horizontal;
- teclado/foco inicia en “Saltar al contenido”; consola Firefox con cero errores y cero warnings.

## 8. Compatibilidad y rollback

La migración es aditiva y no modifica propuestas, snapshots, binarios, folios, admisibilidad, aceptaciones, rúbricas, asignaciones o perfiles. No genera paquetes por migración, seeder, scheduler o primer acceso.

Rollback seguro local/test:

1. apagar `FLOWERFLOW_EVALUATION_ENABLED` para cerrar inmediatamente el shell juez;
2. si no existe evidencia M5 a preservar, revertir únicamente la migración M5 en `flowerflow_testing`;
3. si existen paquetes activados que deban conservarse, no ejecutar `down`; aplicar una migración aditiva correctiva;
4. nunca borrar o reescribir snapshots/archivos fuente para revertir la proyección.

## 9. Riesgos residuales y siguiente puerta

- Identidad semántica dentro de contenido/anexos: aceptada por el propietario y advertida en UI.
- Quill: advisory bajo sin parche; mitigado parcialmente por sanitización y CSP, no cerrado.
- Drift futuro del binario: descarga falla cerrada y requiere intervención operativa; no sustituye evidencia.
- Producción: SHA, migraciones, flags y UAT productiva siguen `POR_CONFIRMAR`; este informe no acredita despliegue.
- M6 debe preservar package/rubric/assignment fijados y calcular exclusivamente en servidor. El prompt canónico está en la sección 21 de `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`; generarlo no autoriza ejecutarlo.
