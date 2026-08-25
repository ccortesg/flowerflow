# Informe de implementación — asignación previa, referencias y exportación de evaluaciones

**Fecha:** 2026-08-25  
**Ámbito:** local/test con datos sintéticos  
**Baseline:** `codex/submission-deadline-extension` / `94af3525e54af49130be079ebc1bbab27e4b03cd`  
**Estado funcional:** alcance implementado y pruebas verdes  
**Gate formal:** `NO-GO LOCAL/TEST` por Pint global fuera de alcance  
**Release/producción:** `NO-GO — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`

## 1. Resultado funcional

Se implementó una autoridad compartida de elegibilidad administrativa. Un perfil coherente `active` o `pending_setup`, con rol exacto `judge` y capacidad ilimitada, puede recibir asignaciones individuales, masivas y de reemplazo. La asignación pendiente conserva el mismo paquete, versión, rúbrica, plazo, actor y evidencia; no activa la cuenta, no omite middleware/Policies y no modifica assignments al completar onboarding.

Cuando se solicita correo para un juez pendiente, la asignación sí se conserva pero no se crea `communication_delivery`. La interfaz presenta una advertencia y la auditoría usa `judge_setup_pending`. Selecciones mixtas notifican sólo a cuentas operativas. No existe replay posterior.

Propuestas y Admisibilidad comparten el parámetro `folio` y buscan parcialmente sobre folio o ULID público. La entrada se recorta, se limita a 64 caracteres y trata `%`, `_` y `\` como literales.

Evaluaciones incorpora solicitud, historial reciente y descarga de un XLSX confidencial. El agregado `evaluation_exports` está separado de exportaciones de propuestas; el permiso `export evaluations` pertenece sólo a `admin` y se acumula con lectura de evaluaciones/propuestas, contraseña reciente, flag, ownership, vigencia y archivo privado.

## 2. Contrato del libro

El job cifrado, único y post-commit usa la cola existente `database/exports` y escribe por streaming con OpenSpout:

- `Evaluaciones`: una fila por revisión, con referencia de propuesta, folio, proyecto, participante, categoría, juez, rúbrica, estados, actores, comentarios, total y fechas Hermosillo.
- `Criterios`: una fila por score/criterio/revisión, incluidos peso, rango, paso, puntaje, componente y comentario.
- `Reaperturas`: enlaces fuente/destino, juez sujeto, actor real y fecha; excluye el motivo cifrado.

Proyecto, folio, ID público, participante y categoría proceden sólo del snapshot inmutable. Un perfil legítimamente ausente queda vacío y rotulado; un snapshot inválido aborta todo el archivo. Los valores de evaluación son persistidos, no recalculados. `NULL` queda vacío y cero se conserva con cuatro decimales. Todas las celdas son texto literal; no hay fórmulas.

El nombre del juez y de los actores representa el valor actual al momento de exportar porque el dominio no conserva snapshot histórico de esos nombres. El encabezado lo declara. No se incluyen correo, teléfono, domicilio, archivos, paths, tokens, hashes, motivo de reapertura o metadata operativa.

## 3. Seguridad y autorización

- Rol exacto `admin`; reviewer, judge, participant, visitor, roleless, multirol y admin incompleto fallan cerrados.
- Otro administrador no descarga el archivo del solicitante.
- Rutas estáticas preceden a `/{evaluation}` y usan CSRF/throttle/`password.confirm` según su mutabilidad.
- Descarga con `private, no-store` y `nosniff`.
- El job lleva únicamente el ID técnico; auditoría contiene sólo scope, estados, conteos, expiración y failure codes redactados.
- La purga elimina únicamente el XLSX vencido y conserva la fila `expired` y la auditoría.
- El diagnóstico read-only informa colas y estados tanto de propuestas como de evaluaciones.

## 4. Migración y rollback

El guard exacto comprobó:

```text
APP_ENV=testing
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=flowerflow_testing
DB_USERNAME=flowerflow_testing_user
SELECT DATABASE()=flowerflow_testing
```

La migración `2026_08_25_180000_create_evaluation_exports` completó forward/rollback/forward. Con una fila sintética, `migrate:rollback --step=1 --force` terminó con código 1 y se negó a retirar evidencia. El fixture puntual se eliminó y la migración quedó aplicada. El rollback operativo es:

```dotenv
FLOWERFLOW_EVALUATION_EXPORT_ENABLED=false
```

No se deben borrar exports o auditoría para forzar `down()`.

## 5. Evidencia ejecutada

| Gate | Resultado |
|---|---|
| Pruebas focalizadas | 42 passed / 538 assertions / 1 opt-in skipped |
| Suite completa | 241 passed / 2,982 assertions / 1 opt-in skipped / 1,319.21 s |
| XLSX independiente + regresión exports | 14 passed / 865 assertions |
| Pint del alcance | passed |
| Pint global exacto | failed: sólo `video-tutorial/scripts/freeze-time.php` externo |
| Composer validate/platform/audit | passed; cero advisories PHP |
| Yarn audit | 1 advisory low de Quill, sin parche disponible, preexistente |
| Build Vite | 784 módulos; passed |
| Rutas propias | 115 |
| Schedules | 4 |
| Migraciones | 25 aplicadas |
| JSON | 11 archivos válidos |
| Enlaces Markdown locales | 15 verificados |
| `git diff --check` | passed |

`vendor/bin/pint --test` sin paths inspeccionó `video-tutorial/scripts/freeze-time.php`, archivo preexistente fuera del milestone, y terminó con código 1 por `fully_qualified_strict_types`. Durante la ejecución un commit externo `2b3d4f9 Videos` avanzó simultáneamente HEAD y upstream desde el baseline inicial `94af352`; sólo agregó los 27 archivos previos de video/manual y no solapó este milestone. El archivo se preservó sin cambios. El gate de las áreas PHP del producto (`app config database routes tests bootstrap/app.php`) pasó, pero conforme a la regla de detener y reparar el resultado formal permanece `NO-GO LOCAL/TEST` hasta autorizar el formato de ese archivo o una exclusión explícita.

El XLSX fue leído con OpenSpout y, de forma independiente, con PhpSpreadsheet; se inspeccionó además el XML de las tres hojas, incluyendo autofiltros y ausencia de nodos `<f>`. LibreOffice no está instalado, por lo que no hubo render visual externo.

## 6. UAT Firefox

Con servidor y base `testing`, correo/servicios externos deshabilitados y datos `example.test`, se verificó en 1440×900, 1024×768 y 390×844:

- búsqueda por folio en Propuestas y Admisibilidad;
- presentación responsive del campo `Folio o ID de propuesta`;
- juez `Configuración pendiente` visible y advertencia correcta;
- acceso y confirmación confidencial de exportación;
- ausencia de overflow horizontal y errores de consola.

En 1440×900 se generó también un XLSX real con cola síncrona local; el panel mostró `Disponible` y el enlace `Descargar`. Al terminar, el servidor se detuvo, se retiraron usuarios/datos sintéticos y `flowerflow_testing` quedó con cero evaluaciones y cero exports, sobre el seed canónico.

La inspección visual de capturas representativas confirmó jerarquía y contraste del CTA, advertencia visible del juez pendiente y filtros legibles. En 1024 px la tabla amplia permanece contenida por su scroll responsive, sin desbordar el documento; en 390 px los controles mantienen orden, etiquetas y ancho útil sin solapamientos.

## 7. Archivos y componentes

Nuevos componentes principales:

- `AdministrativeJudgeEligibility`, `SubmissionReferenceFilter` y `EvaluationWorkbookWriter`.
- `EvaluationExport`, `EvaluationExportStatus`, Policy, middleware, controlador y job.
- migración `2026_08_25_180000_create_evaluation_exports`.
- confirmación/listado de exportaciones y tres rutas estáticas.
- `EvaluationExportTest`, ADR-0015, ExecPlan e informe 32.

Se adaptaron las Actions/controladores/vistas de asignación individual, masiva y reemplazo; filtros de Propuestas/Admisibilidad; diagnóstico/purga de exports; RBAC, configuración, rutas, modelos y pruebas existentes. Los documentos canónicos recibieron adendas; la evidencia histórica `4+2` se preservó como historia superada.

## 8. Riesgos residuales

- El XLSX es altamente confidencial porque une identidad y evaluación; su manejo fuera de la plataforma depende del administrador autorizado.
- Los nombres de juez/actores no son históricos.
- Capacidad, worker, disk y tiempos productivos no se probaron.
- Quill conserva un advisory bajo sin parche.
- La contradicción jurídica sobre el mínimo de jueces continúa bloqueando release y producción.

No hubo stage, commit, push, despliegue, producción, SMTP real ni servicios externos.
