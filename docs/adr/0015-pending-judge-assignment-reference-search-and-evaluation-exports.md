# ADR-0015 — Asignación previa al onboarding, búsqueda por referencia y exportación de evaluaciones

- **Estado:** aceptado para local/test
- **Fecha:** 2026-08-25
- **Ámbito:** administración de jueces, filtros del panel y exportación privada
- **Bloqueo de release:** `OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`

## Contexto

La operación necesita preparar asignaciones antes de que un juez consuma su enlace inicial, localizar propuestas indistintamente por folio o ULID público y extraer evidencia completa de evaluaciones para revisión administrativa. El contrato anterior confundía la capacidad de recibir una asignación con la capacidad de acceder al espacio del juez. Además, Admisibilidad y Propuestas aplicaban búsquedas distintas y las exportaciones de propuestas no tenían la semántica ni los permisos adecuados para unir identidad con evaluaciones ciegas.

## Decisión

### Elegibilidad administrativa

Un servicio único clasifica como asignable al perfil con rol exacto `judge`, capacidad ilimitada y estado coherente `active` o `pending_setup`. El perfil activo debe tener correo verificado y contraseña inicializada; el pendiente puede carecer de esos requisitos. Suspendidos, roleless, multirol y combinaciones incoherentes se rechazan.

La regla se aplica a asignación individual, masiva y reemplazo. Una asignación a `pending_setup` queda activa y conserva plazo, versión, paquete, rúbrica y actor normales, pero no concede acceso: los middleware, Policies y Actions del juez siguen exigiendo correo verificado y perfil activo. Al completar el onboarding no se ejecuta backfill; las asignaciones existentes se vuelven visibles por las reglas normales.

No se crea una comunicación de asignación para un perfil pendiente, aunque el administrador marque la opción. Se informa la omisión, se registra `judge_setup_pending` y no existe envío diferido o replay automático.

### Búsqueda por referencia

El parámetro GET histórico `folio` se conserva y una regla compartida busca parcialmente, en un grupo lógico, sobre `submissions.folio` y `submissions.public_id`. El valor se recorta, limita a 64 caracteres y escapa `%`, `_` y `\` antes de construir `LIKE`. “ID de propuesta” significa exclusivamente ULID público, nunca el ID interno.

### Exportación de evaluaciones

Se crea `evaluation_exports` como agregado separado de `submission_exports`, con permiso exclusivo `export evaluations`, flag default-off, Policy de rol exacto/permiso/ownership y contraseña reciente. La solicitud se procesa mediante un job cifrado, único y post-commit en `database/exports`; el XLSX vive en almacenamiento privado, expira en 24 horas y sólo lo descarga quien lo solicitó.

El libro contiene `Evaluaciones`, `Criterios` y `Reaperturas`, incluyendo todos los estados y revisiones append-only. Proyecto, folio, ID público, participante y categoría proceden únicamente del snapshot inmutable de la versión enviada. La ausencia legítima de perfil queda declarada; un snapshot inválido aborta toda la exportación y elimina el archivo parcial. El juez y los actores son nombres actuales y se presentan como datos al momento de exportar porque no existe snapshot histórico de ellos. Scores, componentes, comentarios y totales son los valores persistidos y no se recalculan.

Todas las celdas de contenido se escriben como texto literal. Se excluyen motivo cifrado de reapertura, correos, teléfonos, domicilios, archivos, rutas, tokens, hashes y metadata técnica. La unión privilegiada de identidad y evaluación existe sólo dentro del XLSX autorizado y nunca se copia al paquete ciego ni a la interfaz del juez.

## Consecuencias

- Preparar el trabajo administrativo deja de depender de que el juez haya terminado su onboarding, sin ampliar su autorización.
- Propuestas y Admisibilidad comparten significado, normalización y protección de comodines.
- La exportación requiere el worker Flower Flow existente con `--queue=high,exports,default,low`; no añade proceso ni dependencia.
- El diagnóstico y la purga existentes incorporan el nuevo agregado, pero la purga elimina únicamente el archivo y conserva la fila como `expired`.
- `down()` se niega ante filas o auditoría de exportación. El rollback operativo es `FLOWERFLOW_EVALUATION_EXPORT_ENABLED=false`.
- La identidad del juez no es histórica; el archivo lo declara expresamente.
- Esta decisión sustituye sólo la exigencia de cuenta activa para **asignación administrativa**. No sustituye requisitos de acceso, conflicto o evaluación ni resuelve la divergencia jurídica sobre cobertura mínima.

## Alternativas descartadas

- Activar artificialmente al juez o verificarlo al asignar: ampliaría acceso sin consumir el enlace purpose-bound.
- Enviar el correo y confiar en que falle en el worker: generaría evidencia no accionable y reintentos innecesarios.
- Reutilizar `submission_exports`: mezclaría permisos, retención, conteos y una frontera de privacidad distinta.
- Consultar perfiles o propuestas vivas como fallback: rompería la inmutabilidad y permitiría drift silencioso.
- Exportación síncrona: aumentaría el tiempo del request y duplicaría el patrón operativo existente.

## Verificación

La evidencia real se registra en `docs/32-pending-judge-reference-filters-evaluation-exports-report-2026-08-25.md` y en el ExecPlan vivo. La autorización permanece limitada a local/test; no hubo stage, commit, push, despliegue o acceso a producción.
