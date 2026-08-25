# Milestone — asignación previa al onboarding, búsqueda por referencia y exportación de evaluaciones

Este ExecPlan es un documento vivo. Se mantiene conforme a `.agent/PLANS.md` y registra únicamente trabajo local/test sobre datos sintéticos.

## Propósito

Permitir que un administrador asigne propuestas a un juez coherente en `pending_setup` sin otorgarle acceso anticipado; unificar la búsqueda parcial por folio o ULID público en Propuestas y Admisibilidad; y generar un XLSX privado, asíncrono y auditable con todas las revisiones de evaluación para el administrador solicitante.

## Baseline y límites

- Rama: `codex/submission-deadline-extension`.
- HEAD/upstream/merge-base: `94af3525e54af49130be079ebc1bbab27e4b03cd`.
- Árbol rastreado limpio al inicio; `output/doc/` y `video-tutorial/` son no rastreados preexistentes y quedan fuera.
- 24 migraciones y 112 rutas propias antes de este milestone.
- No se autoriza stage, commit, push, despliegue, producción, SMTP real ni servicios externos.
- No se modifica acceso del juez, paquete ciego, cálculo, inmutabilidad, reaperturas, consolidación, ranking, resultados o PDF jurídicos.
- Continúa `NO-GO RELEASE/PRODUCTION — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`.

## Guard y evidencia inicial

El 2026-08-25 se demostró sin imprimir secretos:

- `APP_ENV=testing`
- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_DATABASE=flowerflow_testing`
- `DB_USERNAME=flowerflow_testing_user`
- `SELECT DATABASE()=flowerflow_testing`

Regresión previa: 38 pruebas pasaron, 535 aserciones y una prueba de carga fue omitida por diseño en las suites de asignación, bulk, admisibilidad, propuestas, M7 y exportaciones.

## Contrato de elegibilidad administrativa

Una autoridad central determina si un perfil puede recibir una asignación. Debe tener rol exacto `judge`, capacidad ilimitada (`max_active_assignments=NULL`) y estar coherentemente `active` u `pending_setup`. Un juez activo requiere correo verificado y contraseña inicializada; un pendiente conserva onboarding incompleto. Suspendidos, roleless, multirol o estados incoherentes fallan cerrados.

La asignación individual, bulk y de replacement usan la misma autoridad. La asignación puede quedar activa antes del onboarding, pero middleware, Policies y Actions existentes siguen bloqueando toda lectura o evaluación del juez hasta completar configuración. El plazo no cambia.

Las notificaciones de asignación se omiten antes de crear un delivery si el perfil está pendiente. Se registra únicamente `reason_code=judge_setup_pending`; no existe replay al activar la cuenta.

## Contrato de búsqueda

Un servicio compartido aplica el parámetro GET histórico `folio` sobre `submissions.folio OR submissions.public_id`. Recorta espacios, limita a 64 caracteres y escapa `%`, `_` y `\` para que siempre sean literales. La búsqueda parcial se combina con los filtros actuales y conserva query string. Los GET no mutan.

## Contrato de exportación

- Flag: `FLOWERFLOW_EVALUATION_EXPORT_ENABLED=false`.
- Permiso exclusivo: `export evaluations`, además de `view evaluations` y `view submissions`.
- Rutas estáticas antes de `/{evaluation}` para confirmar, solicitar y descargar.
- Agregado dedicado `evaluation_exports`; no se reutiliza `submission_exports`.
- Job cifrado, único y post-commit en `database/exports`; archivo privado, ownership, contraseña reciente y expiración de 24 horas.
- Libro OpenSpout con `Evaluaciones`, `Criterios` y `Reaperturas`.
- Identidad/proyecto/categoría proceden exclusivamente de `submission_versions.snapshot`; snapshot inválido aborta y elimina el parcial. Juez procede del perfil sujeto actual y se etiqueta como valor al momento de exportar.
- Scores, componentes, comentarios y totales se exportan como evidencia persistida, sin recalcular. `NULL` queda vacío y cero se conserva con precisión fija.
- Todo contenido no confiable es texto literal; se excluyen motivo de reapertura, correo, teléfono, domicilio, archivos, paths, secretos, tokens, hashes y metadata técnica.

## Migración y rollback

La migración es aditiva: crea `evaluation_exports`, registra el permiso y lo concede únicamente a `admin`. `down()` se niega ante filas o auditoría de exportación. El rollback operativo es apagar `FLOWERFLOW_EVALUATION_EXPORT_ENABLED`; nunca borrar evidencia.

## Rutas, permisos y UI

- `GET /panel/evaluaciones/exportaciones/nueva`
- `POST /panel/evaluaciones/exportaciones`
- `GET /panel/evaluaciones/exportaciones/{evaluationExport}/descargar`

El listado muestra el botón sólo con flag y permisos, confirmación de datos confidenciales y las cinco exportaciones recientes con estados, expiración y alerta de cola estancada. La descarga revalida flag, rol exacto, permisos, ownership, estado, vigencia, disk y archivo.

## Pruebas y validación previstas

- Elegibilidad pendiente en individual, bulk y replacement; aislamiento antes/después del setup; notificación mixta y omisión redactada.
- Folio/ULID completo/parcial, caracteres comodín literales, combinación de filtros, paginación y GET sin mutación.
- Matriz RBAC/flag/password/ownership; tres hojas, revisiones completas, v1/v2, `NULL`/cero, snapshot inmutable/ausente/inválido, texto hostil y ausencia de campos excluidos.
- Job/cola/disk/expiración/purga/fallo/auditoría/diagnóstico.
- Migración forward/rollback/forward y rechazo con evidencia.
- Pruebas dirigidas, una suite completa, Pint, Composer, Yarn, Vite, rutas, scheduler, migraciones, JSON, Markdown, scans y `git diff --check`.
- Inspección independiente del XLSX y UAT Firefox en 1440×900, 1024×768 y 390×844 cuando el navegador local esté disponible.

## Progreso

- [x] Baseline, lecturas obligatorias, guard y regresión dirigida inicial.
- [x] Autoridad de elegibilidad administrativa y notificaciones pendientes.
- [x] Filtro compartido Folio/ID propuesta.
- [x] Agregado, migración, Policy, job, writer y UI de exportación.
- [x] Pruebas dirigidas y migración reversible.
- [x] Documentación, gates finales y UAT Firefox local.

## Hallazgos y decisiones

- La base de datos ya admite assignments vinculados a perfiles pendientes; no se alterará `judge_assignments`.
- SMTP aceptado no equivale a entrega; este milestone sólo omite deliveries no accionables para cuentas pendientes.
- La unión identidad/evaluación existe exclusivamente en el XLSX privilegiado y no modifica la proyección ciega.
- El umbral histórico del listado de propuestas consultaba `stale_after_minutes`, mientras la configuración canónica define `stalled_after_minutes`; se corrigieron controlador, vista y prueba para evitar advertencias inmediatas falsas.
- LibreOffice no está instalado. La apertura independiente se ejecutó con PhpSpreadsheet además del lector OpenSpout y la inspección ZIP/XML; las tres hojas fueron legibles y ninguna celda fue fórmula.
- `vendor/bin/pint --test` global también inspecciona `video-tutorial/scripts/freeze-time.php`, archivo preexistente y expresamente fuera de alcance. Durante la tarea un commit externo `2b3d4f9 Videos` avanzó HEAD/upstream desde `94af352` y convirtió los 27 archivos previos de video/manual en rastreados, sin solaparse con este milestone. Pint pasó sobre `app`, `config`, `database`, `routes`, `tests` y `bootstrap/app.php`; el archivo ajeno no se modificó.

## Resultados

- Migración: forward/rollback/forward verde; `down()` rechazó con código de salida 1 una fila sintética y mensaje `Cannot remove evaluation exports while export evidence exists`. El fixture se retiró y la migración quedó aplicada.
- Pruebas focalizadas previas: 42 pasaron, 538 aserciones, una carga opt-in omitida.
- Suite completa: 241 pasaron, 2,982 aserciones, una carga opt-in omitida, 1,319.21 segundos.
- Apertura independiente posterior del XLSX y regresión de exportaciones: 14 pasaron, 865 aserciones.
- Pint del alcance, Composer validate/platform/audit, build Vite, `git diff --check`, once JSON, quince enlaces Markdown, 115 rutas, cuatro schedules y 25 migraciones: verdes.
- Yarn audit: un advisory **low** conocido de Quill, sin parche disponible; no fue introducido por este milestone.
- UAT Firefox local: 1440×900, 1024×768 y 390×844 verdes para filtros, configuración pendiente, confirmación confidencial, reflow y consola; en 1440×900 también se generó el XLSX y quedó `Disponible` con descarga. Datos sintéticos retirados y `flowerflow_testing` restaurada con seed canónico.
- Se creó ADR-0015, la trazabilidad y el informe 32. No hubo stage, commit, push, despliegue, producción, SMTP o servicios externos.
- Estado funcional del alcance: verde. Estado formal: `NO-GO LOCAL/TEST` porque el gate global exacto de Pint falla en el único archivo externo `video-tutorial/scripts/freeze-time.php`; requiere una autorización separada para formatearlo o una exclusión aprobada. Se conserva además `NO-GO RELEASE/PRODUCTION — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`.
