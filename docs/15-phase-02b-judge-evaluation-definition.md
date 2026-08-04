# Definición funcional y técnica — Fase 02B: jueces y evaluación

**Estado:** definición documental terminada; implementación bloqueada por decisiones `PENDING`

**Fecha de corte:** 2026-08-04 (`America/Hermosillo`)

**Rama analizada:** `codex/phase-02-admissibility-review`

**Commit base:** `5007b11a6c157898228ec027a387c86c270a33da`

## 1. Alcance y autoridad

Este documento define el siguiente milestone sin implementar código, migraciones, dependencias ni cambios operativos. La jerarquía utilizada es:

1. PDF jurídicos canónicos v1.0 de `formatos/`.
2. Instrucciones vigentes de `AGENTS.md`.
3. ADR y decisiones documentales aprobadas.
4. Propuestas técnicas condicionadas a resolver los puntos `PENDING`.

Las etiquetas significan:

- `CONFIRMADO_PDF`: obligación o regla expresamente respaldada por los PDF.
- `DECISIÓN_TÉCNICA_VIGENTE`: control ya aprobado en la arquitectura/documentación, sin convertirlo en regla jurídica.
- `PROPUESTA_CONDICIONADA`: diseño recomendado que no debe implementarse hasta aprobarlo.
- `PENDING`: falta una decisión autorizada; no existe valor por defecto.

Fuentes canónicas verificadas:

| Fuente | SHA-256 | Secciones relevantes |
|---|---|---|
| `01_Mecanica_Convocatoria_Hermosillo_Florece_2026.pdf` | `42bd5ea13e491dc64a6520f0e26d9663e8e8f973b35a3febf226999118685aa2` | páginas 3–5, especialmente secciones 7–13 |
| `02_Terminos_y_Condiciones_Plataforma_Flower_Flow_2026.pdf` | `ca5fdb36f7a35f8268458144348e66485e8870f55a2bdd9da59137143ef4f28c` | páginas 2–4, especialmente secciones 5–7 y 10–13 |
| `03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026.pdf` | `056355c0405984a239e97b5074fc6b78eef61570022f8f94c062919620cc6898` | páginas 2–5, especialmente secciones 5, 7–9 y 11 |

## 2. Matriz de decisiones

| ID | Tema | Determinación | Estado y fuente | Consecuencia para implementación |
|---|---|---|---|---|
| F2B-DEC-001 | Integración del panel | Los proyectos admitidos serán evaluados por un grupo de **al menos tres** jueces ciudadanos expertos. | `CONFIRMADO_PDF`, Mecánica §8, p. 4 | El panel de la convocatoria no puede tener menos de tres integrantes activos. |
| F2B-DEC-002 | Elegibilidad general | Los jueces se seleccionan por experiencia en participación ciudadana, evaluación de proyectos y materias relacionadas con las categorías. | `CONFIRMADO_PDF`, Mecánica §8, p. 4 | El perfil debe registrar evidencia administrativa de selección; los requisitos medibles siguen pendientes. |
| F2B-DEC-003 | Deberes | Los jueces actúan con independencia, confidencialidad e imparcialidad. | `CONFIRMADO_PDF`, Mecánica §8, p. 4; Aviso §8, p. 3 | Se requieren aceptación versionada, mínimo privilegio y auditoría. |
| F2B-DEC-004 | Entrada a evaluación | Sólo una propuesta admitida puede entregarse a jueces. | `CONFIRMADO_PDF`, Mecánica §§7–8, pp. 3–4 | La asignación debe apuntar al `submission_version` inmutable de una revisión con estado `admitted`. |
| F2B-DEC-005 | Criterios | Son cuatro: relevancia/diagnóstico; calidad, claridad y originalidad; participación ciudadana/coordinación municipal; impacto, sostenibilidad y medición. | `CONFIRMADO_PDF`, Mecánica §8, p. 4 | La rúbrica debe incluir exactamente estos conceptos; desgloses o textos auxiliares requieren aprobación. |
| F2B-DEC-006 | Consolidación numérica | Cada juez asigna su calificación y el resultado se obtiene con el promedio de las calificaciones. | `CONFIRMADO_PDF`, Mecánica §8, p. 4 | El servidor calcula el promedio de calificaciones finales; la fórmula que produce la calificación individual sigue pendiente. |
| F2B-DEC-007 | Resultado por categoría | Puede existir como máximo un proyecto ganador por categoría y una categoría puede quedar sin ganador si ninguno alcanza la calidad mínima. | `CONFIRMADO_PDF`, Mecánica §§8–9, p. 4; Términos §7, p. 3 | El promedio nunca declara ganador automáticamente y debe ser posible declarar la categoría desierta. |
| F2B-DEC-008 | Premio y suplencia | El premio es un Apple iPad Pro por categoría; en equipos se entrega al representante. Tras la notificación existen cinco días hábiles para aceptar y acreditar requisitos; ante incumplimiento puede designarse al proyecto siguiente. | `CONFIRMADO_PDF`, Mecánica §§9–10, p. 4 | Pertenece al futuro módulo de resultados; no se implementa dentro del núcleo de evaluación de esta fase. |
| F2B-DEC-009 | Separación de cálculo y decisión | La plataforma no selecciona automáticamente a las personas ganadoras. | `CONFIRMADO_PDF`, Términos §7, p. 3 | Cálculo, declaración y publicación son capacidades y eventos distintos. |
| F2B-DEC-010 | Datos del juez | El juez recibe únicamente la información necesaria para evaluar. | `CONFIRMADO_PDF`, Aviso §8, p. 3 | Se requiere una proyección específica; no se reutiliza la vista administrativa de propuestas. |
| F2B-DEC-011 | Identidad y residencia | Los jueces no acceden a identidad, comprobantes de residencia, notas de admisibilidad ni auditoría sensible. | `DECISIÓN_TÉCNICA_VIGENTE`, `AGENTS.md`, ADR-0004 y Fase 02A | La denegación aplica en consultas, relaciones, controladores, descargas, exportaciones, logs y correo. |
| F2B-DEC-012 | Conflicto directo | La imparcialidad del juez es obligatoria. La Mecánica también excluye como concursantes a jueces y a personas con conflicto directo con un proyecto, pero no define recusación de un juez asignado. | `CONFIRMADO_PDF` parcial, Mecánica §§3.1 y 8, pp. 2 y 4 | Bloquear una asignación al declarar conflicto es una decisión técnica de integridad; catálogo, procedimiento, autoridad y reasignación siguen `PENDING`. |
| F2B-DEC-013 | Publicación mínima | Puede publicarse nombre de persona o equipo ganador, categoría, título y síntesis no confidencial. Imagen, voz, testimonio o proyecto completo requieren autorización separada. | `CONFIRMADO_PDF`, Aviso §9, p. 4 | Es alcance futuro de resultados; no autoriza publicar calificaciones, comentarios ni identidades de jueces. |
| F2B-DEC-014 | Retención | Registros/proyectos: hasta 24 meses después de publicar resultados; residencia de no ganadores: eliminación dentro de 90 días de validación o cierre de aclaración; datos privados de ganadores: hasta 24 meses después de entregar premio o durante reclamación. | `CONFIRMADO_PDF`, Aviso §11, p. 4 | La evaluación sólo emite hechos; la eliminación sigue bloqueada hasta existir determinación de ganadores autorizada. |
| F2B-DEC-015 | Notificaciones | El correo registrado es canal para comunicaciones de evaluación y resultados. | `CONFIRMADO_PDF`, Términos §10, p. 4 | Eventos exactos, destinatarios, contenido y acuses siguen `PENDING`; nunca incluir PII o archivos sensibles. |
| F2B-DEC-016 | Escala, pesos y calificación individual | Los PDF no establecen rango, tipo de escala, pesos ni cómo los cuatro criterios producen la calificación de un juez. | `PENDING` | Bloquea tablas definitivas de puntuación, validadores, cálculo y pruebas numéricas. |
| F2B-DEC-017 | Calidad mínima | La Mecánica menciona una calidad mínima, pero no define el umbral. | `PENDING` | Bloquea declarar ganador o categoría desierta por regla automatizada. |
| F2B-DEC-018 | Empate | Los Términos remiten a reglas de desempate de la Mecánica, pero la Mecánica v1.0 no contiene esas reglas. | `PENDING`, contradicción jurídica irreducible | Bloquea consolidación final y selección. Requiere adenda jurídica versionada; no se permite desempate aleatorio. |
| F2B-DEC-019 | Asignación | No se define si todos los jueces evalúan todos los proyectos, si se asignan subconjuntos, ni cómo se equilibran categoría/carga. | `PENDING` | Bloquea algoritmo, capacidad y criterio de “evaluación completa”. |
| F2B-DEC-020 | Mínimo por propuesta | “Al menos tres jueces” describe el panel, no demuestra que cada propuesta deba recibir tres evaluaciones. | `PENDING` | No codificar `3` como mínimo por propuesta. |
| F2B-DEC-021 | Evaluación ciega | La minimización y la exclusión de identidad/residencia están fijadas; no se define qué contenido del proyecto debe anonimizarse ni cómo tratar autoría incrustada en archivos. | `PENDING` parcial | Bloquea la proyección final y el procedimiento de saneamiento/análisis de anexos. |
| F2B-DEC-022 | Conflictos y recusación | No existe catálogo, declaración previa, autoridad resolutora, plazo, reasignación ni tratamiento de evaluación iniciada. | `PENDING` | Bloquea el flujo definitivo de conflicto. |
| F2B-DEC-023 | Reapertura/corrección | No se define si procede, quién autoriza, por cuánto tiempo ni si sustituye o versiona la evaluación. | `PENDING` | Las evaluaciones enviadas deben permanecer inmutables; no implementar reapertura hasta decidir. |
| F2B-DEC-024 | Autoridad de resultados | “FLORECE HERMOSILLO” resuelve lo no previsto, pero no se identifica rol/persona, quórum ni doble control para consolidar/declarar/publicar. | `PENDING` | Bloquea permisos de ganadores y publicación. |
| F2B-DEC-025 | Visibilidad al participante | No se define si verá asignación, avance, calificaciones, comentarios, promedio o identidad de jueces. | `PENDING` | Por minimización, la implementación inicial no expondrá detalle alguno hasta aprobación expresa. |
| F2B-DEC-026 | Calendario de evaluación | No se definen apertura/cierre, zona horaria del deadline ni excepciones. | `PENDING` | Bloquea middleware temporal y avisos de vencimiento. |
| F2B-DEC-027 | Comentarios | No se define si son obligatorios, internos, visibles al participante o publicables. | `PENDING` | Bloquea contrato y plantilla de evaluación. |

## 3. Matriz de roles, permisos y separación de datos

La matriz es un control técnico de mínimo privilegio. `A` significa acceso a su asignación; `T`, acceso total expresamente autorizado; `R`, dato redactado; `—`, denegado.

| Recurso/capacidad | Participante | Juez asignado sin conflicto | Juez no asignado/conflictuado | Reviewer de admisibilidad | Admin autorizado | Futuro auditor |
|---|---:|---:|---:|---:|---:|---:|
| Snapshot admitido, proyección evaluable | propio sin contenido interno | A | — | T | T | R/T por permiso |
| Identidad/perfil/equipo | propio | — | — | T por admisibilidad | T | R/T por permiso |
| Residencia y aclaraciones | propio según Fase 02A | — | — | T por permisos Fase 02A | T | R/T por permiso específico |
| Notas internas de admisibilidad | — | — | — | T | T | R/T por permiso |
| Anexos evaluables autorizados | propio | A | — | T según Policy existente | T | R/T por permiso |
| Asignación propia | — | A | — | — | T | T lectura |
| Declarar conflicto propio | — | A | — | — | T sólo resolución | lectura |
| Borrador de evaluación propio | — | A | — | — | T por permiso excepcional | lectura auditada |
| Evaluación enviada propia | `PENDING` | A sólo lectura | — | — | T | T lectura |
| Evaluaciones de otros jueces | — | — | — | — | T por permiso | T lectura |
| Promedios/ranking global | `PENDING` | — | — | — | T por permiso | T lectura |
| Auditoría sensible | — | — | — | sólo Fase 02A necesaria | T por permiso | T |
| Declarar/publicar ganador | — | — | — | — | `PENDING` por autoridad | T sólo observación |

Permisos propuestos, aún no creados:

- Juez: `view judge dashboard`, `view assigned submissions`, `declare evaluation conflict`, `create evaluations`, `submit evaluations`, `view own evaluations`.
- Gestión: `manage judges`, `manage judge assignments`, `resolve evaluation conflicts`, `manage rubrics`, `view all evaluations`, `reopen evaluations`, `void evaluations`.
- Resultados futuros y separados: `consolidate results`, `declare winners`, `publish winners`.
- Auditoría: `view evaluation audit`.

Los permisos de resultados no se asignan hasta identificar la autoridad. El rol `admin` no sustituye una decisión de negocio; podrá recibir permisos totales sólo cuando la matriz sea aprobada. La Policy de `Submission` vigente no debe ampliarse a `judge`: se requiere un recurso/proyección separado y fail-closed.

## 4. Modelo de dominio propuesto, sin migraciones

### 4.1 Entidades candidatas

| Entidad | Responsabilidad | Restricciones propuestas |
|---|---|---|
| `judge_profiles` | habilitación, especialidad y evidencia de aceptación/confidencialidad | relación única con usuario; sin PII innecesaria en evaluación |
| `judge_assignments` | vínculo juez–snapshot admitido | unique juez+versión; asignación inmutable y auditable |
| `conflict_declarations` | declaración y resolución de conflicto | append-only; catálogo y autoridad `PENDING` |
| `rubrics` | versión de la rúbrica | nunca mutar una versión usada; activación explícita |
| `rubric_criteria` | cuatro criterios canónicos, orden y configuración | pesos/escala `PENDING` |
| `evaluations` | cabecera/borrador/envío del juez | una evaluación por asignación; envío transaccional e idempotente |
| `evaluation_scores` | valor y comentario por criterio | rango/peso/comentario `PENDING` |
| `evaluation_events` | historial inmutable de transiciones | actor, estado origen/destino, motivo, UTC |
| `result_snapshots` | consolidación reproducible | futura; no declara ganador |
| `winner_decisions` | decisión administrativa separada | fuera del núcleo hasta resolver autoridad/empate/umbral |

Las asignaciones deben referenciar `submission_version_id`, no el borrador mutable. Ninguna entidad de evaluación almacena ni replica residencia, fecha de nacimiento, correo, teléfono, notas internas o documentos de admisibilidad.

### 4.2 Estados propuestos

Estos estados son una propuesta técnica condicionada; no autorizan migraciones.

**Asignación**

```text
assigned -> in_progress -> submitted
assigned | in_progress -> conflict_declared -> voided
assigned | in_progress -> voided
submitted -> reopened -> in_progress   [PENDING: autoridad y reglas]
```

- `assigned`: acceso habilitado a la proyección evaluable.
- `in_progress`: existe un borrador.
- `conflict_declared`: acceso evaluable y escritura bloqueados de inmediato.
- `submitted`: evaluación enviada e inmutable.
- `reopened`: excepción auditada, no utilizable hasta aprobar la política.
- `voided`: asignación invalidada sin borrar evidencia.

**Evaluación**

```text
draft -> submitted
submitted -> reopened -> submitted     [PENDING]
draft | reopened | submitted -> voided [sólo autoridad aprobada]
```

Para evitar desincronización, una sola Action transaccional actualizaría evaluación, asignación, evento y agregados. `submitted` no modifica `submissions.status`; el estado de avance se deriva de asignaciones/evaluaciones. La evaluación enviada conserva la rúbrica versionada y sus valores originales.

**Conflicto**

```text
declared -> confirmed | dismissed
```

La nomenclatura es candidata. Mientras esté `declared`, el acceso se cierra. Quién resuelve, qué prueba y qué sucede con trabajo previo permanecen `PENDING`.

**Rúbrica**

```text
draft -> active -> retired
```

Sólo una versión activa puede asignarse a una evaluación nueva. Una rúbrica usada no se edita; se crea otra versión. Activación, escala, pesos y autoridad están `PENDING`.

### 4.3 Invariantes

1. Sólo un snapshot admitido puede asignarse.
2. El snapshot y una evaluación enviada son inmutables.
3. Un juez sólo consulta su asignación activa mediante Policy y scope de consulta.
4. Declarar conflicto cierra inmediatamente lectura evaluable y escritura.
5. Ninguna ruta, relación, exportación o correo de juez incluye PII, residencia, aclaraciones o notas internas.
6. Puntajes, promedios y elegibilidad no declaran ganador.
7. Los cálculos se ejecutan en servidor con decimales y redondeo aún `PENDING`.
8. Envío, reapertura, anulación, conflicto, asignación y acceso a archivo generan auditoría inmutable.
9. Todas las fechas se guardan en UTC y se presentan en `America/Hermosillo`.
10. Doble clic o reintento no duplica evaluación, evento, promedio ni notificación.

## 5. Flujos propuestos

### 5.1 Juez

1. Accede con cuenta verificada y permiso explícito.
2. Acepta documentos versionados de confidencialidad/imparcialidad que sean aprobados (`PENDING` el texto y momento).
3. Ve exclusivamente asignaciones propias y su estado.
4. Antes de abrir contenido evaluable, declara ausencia de conflicto o reporta conflicto conforme al proceso pendiente.
5. Abre una proyección sin identidad/residencia ni notas administrativas.
6. Consulta únicamente anexos autorizados y auditados.
7. Guarda borrador; el servidor valida valores contra la versión de rúbrica.
8. Revisa un resumen y confirma el envío.
9. El servidor bloquea la evaluación, registra evento y despacha notificación resiliente sólo si el evento fue aprobado.
10. Consulta su historial propio sin ranking ni evaluaciones ajenas.

### 5.2 Administración

1. Habilita perfiles de juez después de verificar criterios autorizados.
2. Activa una rúbrica versionada sólo después de resolver escala, pesos, umbral y cálculo.
3. Asigna snapshots admitidos conforme a la política de asignación aprobada.
4. Atiende conflictos sin revelar información adicional y reasigna cuando proceda.
5. Monitorea cobertura y evaluaciones faltantes sin modificar respuestas del juez.
6. Reabre o anula únicamente si existe autoridad/regla aprobada, con motivo y evento inmutable.
7. Genera una consolidación reproducible; no declara ganador automáticamente.
8. La declaración/publicación ocurre en el módulo futuro y sólo tras resolver empate, umbral y autoridad.

### 5.3 Participante

1. Conserva su propuesta admitida sin poder modificar el snapshot.
2. Puede recibir una comunicación de avance sólo si se aprueba evento/texto.
3. No ve identidad de jueces, asignaciones, borradores, comentarios, calificaciones ni promedios mientras esa visibilidad no se autorice.
4. En resultados, sólo recibe/publica los datos aprobados y conforme a la base jurídica aplicable.

## 6. Amenazas y controles

| Amenaza | Escenario | Control requerido | Evidencia futura |
|---|---|---|---|
| IDOR de asignación | juez altera ULID o URL | scope por juez + Policy + route binding scoped + 404/403 consistente | pruebas juez A/B y URL directa |
| Filtración de PII | eager load, vista, export o excepción incluye identidad/residencia | DTO/proyección allowlist; consultas separadas; tests de payload; redacción de logs | canarios sintéticos ausentes en HTML, JSON, correo y logs |
| Autoría incrustada | PDF/imagen contiene nombre o metadatos | procedimiento de anonimización `PENDING`; advertencia y revisión previa | pruebas con metadata/autoría sintética |
| Conflicto ignorado | juez sigue leyendo o puntuando | transición bloqueante, revocación de acceso y evento atómico | pruebas antes/después de declarar conflicto |
| Manipulación cliente | total/peso enviado por navegador | aceptar sólo valores; servidor aplica rúbrica versionada | pruebas tampering y límites |
| Doble envío | doble clic/reintento crea dos evaluaciones | unique constraints, lock/transacción e idempotency key | prueba concurrente |
| Cambio retroactivo | editar rúbrica altera calificaciones históricas | versionado e inmutabilidad después de uso | prueba versión antigua/nueva |
| Colusión/ranking | juez conoce promedios o respuestas ajenas | sin ranking, agregados ni evaluaciones ajenas en payload | pruebas negativas y revisión de consultas |
| Reapertura abusiva | evaluación cambia sin evidencia | permiso separado, motivo, snapshot/evento y notificación | matriz de roles y auditoría |
| Correo filtra datos | notificación incluye proyecto/documentos | plantilla mínima, URL autenticada, job after-commit y logs redactados | prueba de contenido y falla SMTP |
| Zona horaria | cierre evaluado con UTC local | persistencia UTC, regla `America/Hermosillo`, bordes exactos | pruebas antes/en/después del corte |
| Dependencia de ganadores | retención borra residencia antes de resultado | sólo reporte dry-run hasta señal autorizada de ganador/no ganador | prueba archivo preservado |

## 7. Estrategia de pruebas y QA futura

### 7.1 Dominio y cálculo

- sólo snapshots admitidos son asignables;
- asignación única e idempotente;
- estados válidos/invalidos de asignación, conflicto y evaluación;
- evaluación enviada y rúbrica usada son inmutables;
- cálculo exacto con escala, pesos, precisión y redondeo aprobados;
- promedio reproducible a partir de calificaciones de jueces;
- cobertura incompleta no consolida cuando se apruebe la regla mínima;
- doble clic y concurrencia no duplican efectos;
- cálculo no cambia una propuesta a ganadora.

### 7.2 Autorización y privacidad

- anónimo, participante, reviewer, juez A, juez B, admin con/sin permiso y auditor;
- juez no asignado y juez conflictuado reciben 403/404 según Policy;
- juez asignado sólo obtiene la proyección allowlist;
- identidad, residencia, aclaraciones, notas internas y auditoría sensible no aparecen en HTML, relaciones, descargas, exports, correo ni excepciones;
- archivos evaluables autorizados validan Policy y registran acceso;
- permisos de consolidar, reabrir, anular, declarar y publicar se prueban por separado.

### 7.3 Integridad, correo y tiempo

- auditoría append-only para asignar, acceder, declarar conflicto, guardar, enviar, reabrir, anular y consolidar;
- jobs después del commit, con reintentos, timeout y falla SMTP sin error 500 ni rollback de la evaluación;
- UTC en base y presentación `America/Hermosillo`, incluidos límites de calendario;
- feature flag `FLOWERFLOW_JUDGING_ENABLED=false` apagada por defecto y rutas/menús cerrados;
- migración adelante/rollback y `migrate:fresh` sólo en base de pruebas desechable.

### 7.4 Navegador y accesibilidad

Cuando se autorice implementación, QA real con datos sintéticos en escritorio, tableta y móvil:

- juez sin asignaciones, con asignación, con conflicto, con borrador y enviado;
- administración con filtros, asignación, conflicto, seguimiento y excepción autorizada;
- teclado completo, foco visible, labels/errores asociados, zoom 200/400 %, contraste y lector de pantalla básico;
- sesión expirada, back/refresh, doble clic, offline/falla de red y consola sin errores;
- evidencias temporales fuera del repositorio y eliminadas al concluir.

## 8. Puerta de decisión previa a implementación

La implementación no debe comenzar mientras falte cualquiera de estas respuestas aprobadas y trazables:

1. Integración exacta del panel y elegibilidad verificable de jueces.
2. Política de asignación y cantidad mínima de evaluaciones por propuesta.
3. Protocolo de anonimización de texto, archivos y metadatos.
4. Catálogo, declaración, resolución y reasignación por conflicto/recusación.
5. Escala, pesos, precisión, redondeo y fórmula de calificación por juez.
6. Umbral de calidad mínima.
7. Regla jurídica de desempate mediante adenda canónica.
8. Apertura/cierre y excepciones del calendario de evaluación.
9. Reglas y visibilidad de comentarios.
10. Reapertura/anulación: autoridad, motivo, ventana y versionado.
11. Autoridad/quórum para consolidar, declarar y publicar resultados.
12. Visibilidad de avance/calificaciones/comentarios al participante.
13. Eventos, destinatarios y textos de notificaciones.
14. Señal definitiva de ganador/no ganador que habilitará retención, sin activar borrado en esta fase.

## 9. Alcance recomendado de implementación una vez desbloqueado

La Fase 02B implementaría perfiles de juez, asignación, conflicto, rúbrica versionada, evaluación en borrador/envío, proyección privada, auditoría y notificaciones aprobadas. La declaración/publicación de ganadores y la eliminación de residencia seguirían en un milestone posterior, aunque sus contratos de integración deben quedar definidos.

No requiere SPA, API pública, Redis, microservicios ni dependencias de producción nuevas. Reutilizaría Laravel 12, Blade, Materialize/Pixinvent, menús JSON, `audit_logs`, colas transaccionales y almacenamiento privado existentes.

## 10. Prompt exacto condicionado para implementación

El siguiente prompt **no debe ejecutarse** con marcadores pendientes. Antes de usarlo, se debe sustituir cada `<PENDING_...>` por una decisión aprobada y registrar la fuente jurídica o de negocio.

```text
# PROMPT PARA WINDOWS CODEX
## Implementación de la Fase 02B: jueces y evaluación de Flower Flow

Trabaja directamente en:

C:\wamp64\www\flowerflow

Todo el código debe escribirse en inglés. Toda la interfaz, mensajes, correos y documentación operativa deben estar en español de México. La zona horaria del negocio es America/Hermosillo; las fechas se almacenan en UTC.

Estado inicial obligatorio:

Rama: codex/phase-02-admissibility-review
Commit: <COMMIT_DOCUMENTAL_APROBADO_FASE_02B>
Working tree e índice: limpios

Antes de cualquier modificación ejecuta:

git status --short
git branch --show-current
git rev-parse HEAD
git diff --cached --quiet

Si el estado no coincide, detente y reporta la diferencia. Si coincide, crea y cambia a codex/phase-02b-judge-evaluation. No crees worktree, clone ni copia paralela.

Decisiones aprobadas que sustituyen los PENDING de la definición:

- Elegibilidad verificable e integración del panel: <PENDING_JUDGE_ELIGIBILITY_AND_PANEL>
- Política de asignación: <PENDING_ASSIGNMENT_POLICY>
- Evaluaciones mínimas por propuesta: <PENDING_MINIMUM_EVALUATIONS>
- Protocolo de anonimización: <PENDING_BLIND_REVIEW_PROTOCOL>
- Conflictos, recusación, resolución y reasignación: <PENDING_CONFLICT_POLICY>
- Escala de calificación: <PENDING_SCORE_SCALE>
- Pesos por criterio: <PENDING_CRITERION_WEIGHTS>
- Fórmula, precisión y redondeo: <PENDING_SCORE_FORMULA_AND_ROUNDING>
- Umbral de calidad mínima: <PENDING_MINIMUM_QUALITY_THRESHOLD>
- Regla jurídica de desempate: <PENDING_TIE_BREAK_RULE>
- Calendario y excepciones: <PENDING_JUDGING_CALENDAR>
- Comentarios y su visibilidad: <PENDING_COMMENT_POLICY>
- Reapertura/anulación y autoridad: <PENDING_REOPEN_VOID_POLICY>
- Autoridad y quórum de consolidación/resultados: <PENDING_RESULTS_AUTHORITY>
- Visibilidad para participante: <PENDING_PARTICIPANT_VISIBILITY>
- Eventos, destinatarios y textos de notificación: <PENDING_NOTIFICATION_POLICY>
- Contrato de integración con ganadores/retención: <PENDING_RESULTS_RETENTION_CONTRACT>

No continúes si un marcador permanece sin sustituir, si una decisión contradice un PDF canónico o si la regla de desempate no está respaldada por una adenda jurídica versionada.

Lee completamente AGENTS.md, .agent/PLANS.md, todos los ExecPlans, ADR, documentación Markdown, docs/15-phase-02b-judge-evaluation-definition.md y los tres PDF canónicos. Actualiza el ExecPlan vivo .agent/execplans/flowerflow-phase-02b-judge-evaluation.md.

Implementa únicamente:

1. Perfiles de juez y aceptación versionada de obligaciones aprobadas.
2. Asignación de snapshots admitidos con idempotencia y política aprobada.
3. Proyección evaluable allowlist que excluya identidad, residencia, aclaraciones, notas internas y auditoría sensible.
4. Declaración/resolución de conflicto y reasignación conforme a la política aprobada.
5. Rúbrica versionada con los cuatro criterios canónicos, escala/pesos/fórmula aprobados.
6. Evaluación propia en borrador, confirmación y envío inmutable.
7. Promedio calculado en servidor sin selección automática de ganador.
8. Excepciones de reapertura/anulación sólo conforme a autoridad y reglas aprobadas.
9. Policies, scopes y permisos granulares; el rol no sustituye autorización de recurso.
10. Auditoría inmutable y correos resilientes after-commit sin PII sensible.
11. Feature flag FLOWERFLOW_JUDGING_ENABLED=false por defecto, respetada por rutas, menús y jobs.
12. Interfaces responsive y accesibles para juez y administración, y la visibilidad participante aprobada.
13. Pruebas funcionales, negativas, concurrencia, archivos, correo, auditoría y UTC/America/Hermosillo.
14. Documentación y trazabilidad completas.

No implementes declaración/publicación de ganadores, desempate no aprobado, borrado de residencia, comunicaciones masivas, ARCO completo, reportes avanzados, producción, SPA, API pública, Redis ni dependencias de producción nuevas salvo autorización expresa y ADR.

Usa migraciones aditivas/reversibles y datos exclusivamente sintéticos. No modifiques el enum actual de propuestas draft/submitted/withdrawn ni el snapshot enviado. No reutilices la Policy/vista administrativa para jueces. No uses public ni storage:link para archivos privados.

Pruebas obligatorias mínimas:

- sólo snapshot admitido es asignable;
- asignación única/idempotente y reglas de cobertura aprobadas;
- juez A no consulta asignación/evaluación de B;
- juez no asignado, conflictuado o sin permiso queda denegado;
- ausencia de PII/residencia/notas/aclaraciones en todos los payloads y archivos de juez;
- rúbrica versionada, límites y cálculo exacto aprobado;
- borrador, confirmación, envío inmutable, doble clic y concurrencia;
- reapertura/anulación sólo con autoridad, motivo y auditoría;
- cálculo/promedio no declara ganador;
- falla SMTP no produce error 500 ni revierte el envío;
- feature flag apagada/encendida;
- persistencia UTC y presentación America/Hermosillo;
- regresión completa de Fases 01 y 02A.

Ejecuta las validaciones aplicables: php artisan test, migración adelante/rollback en base desechable, php artisan route:list, php artisan view:cache, composer validate --strict, composer audit, vendor/bin/pint --dirty, corepack yarn build, git diff --check y git status --short. Realiza QA en navegador únicamente de pantallas nuevas con datos sintéticos y documenta evidencia en docs/design-qa-phase-02b-judging.md.

No autoriza stage, commit, push, pull request, despliegue ni cambios de producción. Deja todo local y sin stage.

Entrega un reporte en español de México con archivos, migraciones, rutas, permisos, estados, controles, pruebas/aserciones, QA, riesgos, PENDING residuales, rollback y confirmación literal de que no hubo stage, commit, push ni despliegue.
```

## 11. Conclusión

La Fase 02B está definida hasta el límite permitido por las fuentes. Puede avanzarse en decisiones de producto/jurídicas, pero no en implementación: escala, pesos, umbral, asignación por propuesta y desempate afectan directamente integridad y resultado. En particular, la referencia de los Términos a reglas de desempate inexistentes en la Mecánica requiere una corrección jurídica canónica antes de construir esa parte.
