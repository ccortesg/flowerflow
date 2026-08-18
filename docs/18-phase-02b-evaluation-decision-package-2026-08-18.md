# Paquete de decisiones Fase 02B — jueces y evaluación

**Fecha:** 2026-08-18 (`America/Hermosillo`)

**Estado:** `DESIGN APPROVED — M1/M2 GO LOCAL/TEST — M3 READY FOR EXPLICIT AUTHORIZATION`

**ExecPlan:** `.agent/execplans/flowerflow-phase-02b-evaluation-design.md`

## 1. Resumen ejecutivo

La Fase 02B tiene **20 % de implementación funcional**, limitado a M1 y M2. Además del aislamiento base, el repositorio aporta `judge_profiles`, función `primary|substitute`, capacidad coherente, alta directa, credencial propia, activación derivada, suspensión/reactivación, revocación de sesiones y recovery administrativo 2FA. No aporta asignaciones, proyección ciega, conflictos, rúbricas, evaluaciones, puntuaciones ni pantallas operativas del flujo sustantivo.

El propietario respondió las 21 decisiones el 2026-08-18 y después resolvió expresamente `P2B-BLOCK-001`: cuatro jueces principales evaluarán todas las propuestas elegibles sin límite fijo y un quinto juez, con capacidad máxima de diez, se reserva exclusivamente para sustituciones. M1 y M2 quedaron **`GO LOCAL/TEST`** y el perfil M2 fue alineado antes de su primer commit. Fase 02B permanece en **20 % funcional** y sube a **90 % de preparación**; M3–M10 no están implementados.

La siguiente puerta es la autorización expresa y ejecución local/test del **Milestone 3 —rúbrica versionada—** mediante el prompt de la sección 21. El cierre local de M1/M2 no autoriza M3–M10, producción, ganadores o resultados. `P2B-BLOCK-001` está `RESOLVED BY OWNER`; M4 deja de tener un bloqueo decisorio, pero continúa no autorizado y debe esperar M3 verde y un prompt propio.

## 2. Estado productivo y alcance de evidencia

| Hecho | Estado | Interpretación |
|---|---|---|
| Cambios actuales instalados en producción y plataforma publicada con más de 50 propuestas reales | `OWNER_CONFIRMED_DEPLOYED` | Confirmación expresa del propietario del 2026-08-18. |
| SHA productivo exacto | `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR` | El propietario no vinculó inequívocamente la instalación a un SHA en esta tarea. |
| Migraciones, flags, workers, scheduler, SMTP, monitoreo, integridad, smoke y UAT productiva | `POR_CONFIRMAR` | No existe evidencia técnica independiente en este milestone. |
| Baseline local | `VERIFIED` | Rama `codex/submission-deadline-extension`; local/remoto/merge-base en `e0fa0455e61afcb38593b62ae0d983f75a92b210`; árbol inicial limpio. |

Codex no accedió a la URL pública, producción, AWS, EC2, SSH/SSM, bases, logs ni servicios externos. No se consultó PII ni contenido de propuestas reales.

## 3. Metodología y diagnóstico de preparación

La preparación se evaluó por contrato, evidencia real, decisiones cerradas, seguridad y plan de prueba. La media siguiente es diagnóstica y no funcional:

| Contrato | Preparación | Evidencia y brecha dominante |
|---|---:|---|
| Identidad/acceso | 100 % | M1/M2 implementaron roles exclusivos, gates, perfil, alta directa, credencial propia, activación, suspensión/reactivación y recovery 2FA sólo en local/test. |
| Asignaciones | 90 % | Asignación manual, cuatro evaluaciones, cuatro principales sin límite y un sustituto exclusivo con capacidad diez ya están aprobados; falta implementar M4. |
| Evaluación ciega | 80 % | Ceguera simple estructural y riesgo de autoidentificación aceptados; falta implementar la proyección. |
| Rúbrica | 95 % | Cinco criterios, pesos, rangos, pasos y comentarios aprobados; falta versionar e implementar. |
| Ciclo de evaluación | 85 % | Estados, conflicto, envío y reapertura append-only aprobados; falta código y QA. |
| Cálculo/consolidación | 95 % | Fórmula, precisión, redondeo, media, faltantes y empate definidos. |
| Seguridad/auditoría | 90 % | M1/M2 cerraron shells/estados y añadieron step-up, auditoría y revocación; 2FA opcional y edición admin futura de puntajes conservan riesgo. |
| UX accesible | 90 % | Shell, estado seguro y gestión administrativa M2 pasaron QA responsive; superficies de rúbrica/asignación/evaluación aún no existen. |
| Notificaciones/operación | 80 % | M2 implementó el subconjunto indispensable de cuenta con HTML+texto y fallo observable; eventos M4+ y recordatorios siguen futuros. |
| Compatibilidad de datos | 95 % | Migraciones M1/M2 aditivas pasaron upgrade/rollback/forward sin cuentas/asignaciones automáticas; el perfil distingue función/capacidad y faltan esquemas M3+. |
| **Promedio de preparación** | **90 %** | **Implementación funcional 20 %, limitada a M1/M2.** |

## 4. Inventario reutilizable y brechas reales

| Capacidad actual | Evidencia de código | Reutilización propuesta | Brecha 02B |
|---|---|---|---|
| Auth y correo verificado | Fortify, `DashboardController`, acciones M2 y gates `auth`/`verified` | Login, reset, verify, credencial propia y desafío TOTP | Alta/activación M2 completa; entregabilidad externa no acreditada. |
| Roles/permisos | `BusinessRole`, `AssignExclusiveBusinessRole`, seeder y migraciones M1/M2 | Rol exacto, permisos separados y escritor fail-closed | Mantener invariantes al añadir permisos M3+. |
| Gate de panel | `routes/web.php`, `EnsureExclusiveBusinessRole`, `JudgeProfilePolicy` | Panel sólo `reviewer|admin`; `/panel/jueces` sólo admin exacto con permisos M2 | No es shell de juez y no debe hospedar vistas de evaluación judge. |
| Shells | Layout Flower Flow, `/cuenta/acceso`, `/juez` y `/juez/estado` | Marca, accesibilidad, flag, perfil activo y separación por rol exacto | `/juez` sólo es estado vacío; no existen datos/controles M3+. |
| Propiedad/descarga | `app/Policies/SubmissionPolicy.php:10-29` | Patrón Policy + comprobación de pertenencia | No sirve para juez: `view submissions` abre propuesta completa y PII. |
| Snapshot inmutable | `app/Actions/FinalizeSubmission.php:39-69`, `app/Models/SubmissionVersion.php:10-31`, `app/Models/Concerns/ImmutableRecord.php:7-13` | Fuente versionada de contenido enviado | Incluye identidad, equipo, nombres de archivo y enlaces; no es ciego. |
| Admisibilidad | `app/Models/EligibilityReview.php:11-61`, `app/Services/EligibilityReviewWorkflow.php:337-386` | `admitted` como precondición explícita | No crea estado de evaluación ni debe mutar propuestas por inferencia. |
| Storage privado | `SubmissionFileStore`, discos privados y Policies | Descarga controlada y auditada | Falta allowlist específica de anexos para juez y derivados anonimizados. |
| Auditoría | `AuditLogger`, eventos de envío/admisibilidad y acciones M2 | Actor, acción, sujeto técnico, transiciones redactadas y UTC | Falta catálogo de eventos M3+ y política de retención ejecutable. |
| Concurrencia | transacciones, `lockForUpdate`, idempotencia de envío | Activar/asignar/enviar/reabrir de forma serializable | Falta versionado optimista o lock de borrador y doble envío de evaluación. |
| Correo resiliente | `ResilientMailDispatcher`, notificaciones M2 HTML/texto | Configuración de acceso, verificación y cambios de estado post-commit sin PII | Faltan eventos de asignación/evaluación/recordatorios y ledger/idempotencia M4+. |
| 2FA/contraseña | `AccountSecurityController`, acciones M2 y rutas protegidas | Step-up, recuperación admin, limpieza TOTP y revocación de sesiones | 2FA sigue opcional por contrato; hardening futuro no debe convertirlo en obligatorio. |
| Pruebas negativas | `JudgeRbacIsolationTest`, `JudgeProfileOnboardingTest` y suites vigentes | Matriz roles/estados/flag/función/capacidad/IDOR/mass assignment/sesiones | M1+M2 cubren 16 pruebas/267 aserciones; estados M3+ aún no existen. |

### Riesgo de frontera actual

M1 resolvió la frontera por descarte y M2 conservó sus invariantes: participante, panel y juez exigen roles exactos; una cuenta sin rol o multirol no recibe ningún shell de negocio; `/juez` exige además permiso exclusivo, correo verificado, perfil activo y flag. Pending/suspended reciben estado seguro, no datos de negocio.

## 5. Contratos aprobados y diseño futuro

### 5.1 Invariantes confirmados

1. El juez sólo accede a recursos que tenga asignados y cuya asignación esté vigente.
2. PII de contacto, residencia, notas internas, aclaraciones e historial de admisibilidad quedan fuera de la proyección de juez.
3. El total se calcula y valida en servidor; el total del navegador se ignora.
4. Una evaluación enviada queda inmutable.
5. Evaluar, consolidar o detectar empate no declara ganador.
6. No existe selección aleatoria.
7. Ganadores, resultados, premios y categoría desierta quedan fuera de Fase 02B.
8. Las más de 50 propuestas reales se protegen con cambios aditivos, sin reescribir folios, snapshots, aceptaciones o estados.

### 5.2 Decisiones aprobadas por el propietario — 2026-08-18

- roles `participant`, `reviewer`, `judge` y `admin` estrictamente excluyentes;
- alta directa de jueces por `admin`; no se implementan invitaciones de juez en 02B;
- asignación manual y cuatro evaluaciones por propuesta en las cuatro categorías; cuatro jueces principales evaluarán todas las propuestas elegibles sin límite fijo y un quinto juez sustituto, sin asignaciones iniciales, admite como máximo diez reasignaciones activas; no hay especialidad;
- ceguera simple estructural: se ocultan identidad estructurada, contacto, residencia, notas internas, aclaraciones e historial de admisibilidad; se muestran todos los campos sustantivos y anexos evaluables, aceptando el riesgo de identidad contenida en texto o archivos;
- anonimización automática limitada a campos estructurados, nombres de archivo y metadatos técnicos; no se afirma anonimización semántica;
- rúbrica global de cinco criterios, escala 0–10, paso 0.5, total ponderado 0–100, precisión interna de cuatro decimales y visual de dos, `HALF_UP`;
- comentario general obligatorio y comentarios por criterio opcionales;
- media aritmética con igual peso, consolidación bloqueada si falta una evaluación e igualdad redondeada como empate técnico;
- catálogo mínimo cerrado de conflictos con sustitución administrativa; varios jueces pueden evaluar una misma propuesta mediante asignaciones independientes;
- cierre global `2026-08-27 23:59:59 America/Hermosillo`;
- reapertura por `admin` hasta `2026-08-27 20:00:00 America/Hermosillo`, revisión append-only y edición hasta el cierre global; `admin` puede modificar puntajes en nombre del juez sin ocultar al actor real en la auditoría;
- 2FA opcional para juez y recuperación autorizada por `admin`;
- notificaciones mínimas de juez y recordatorios de participantes el 20 y 22 de agosto de 2026 a las 09:00, hora de Hermosillo;
- retención uniforme de 24 meses desde el cierre administrativo del ciclo de evaluación.

`P2B-BLOCK-001 — RESOLVED BY OWNER 2026-08-18`: el contrato anterior de cuatro jueces × ocho proyectos y ausencia de reserva queda sustituido. Los cuatro jueces principales no tienen límite fijo y cubren todas las propuestas elegibles; el quinto es sustituto exclusivo y admite hasta diez reasignaciones activas. El sistema debe impedir que el sustituto reciba asignaciones iniciales y rechazar la undécima sustitución activa. Superar ese límite exige una decisión operativa nueva, pero no bloquea el diseño ni la autorización futura de M4.

## 6. Contrato 1 — identidad y acceso del juez

### Contrato aprobado

- Cada cuenta tiene exactamente uno de estos roles: `participant`, `reviewer`, `judge` o `admin`. Toda combinación queda prohibida y debe fallar cerrada.
- El alta de juez es directa por `admin`; no se crea `judge_invitations` ni se envía token de invitación.
- El correo debe quedar verificado antes del acceso al shell juez. Una credencial inicial nunca se guarda ni envía en texto plano; el primer acceso debe obligar a establecer una contraseña propia mediante el mecanismo seguro que se detalle en M2.
- 2FA es opcional. Su ausencia no bloquea acceso ni envío. La recuperación de 2FA sólo puede ejecutarla `admin`, con razón, confirmación de contraseña, revocación de sesiones y auditoría.
- Suspender o desactivar la cuenta bloquea el shell y las mutaciones de juez; la revocación de sesiones sigue siendo control técnico obligatorio.

### Alternativas históricas anteriores a la decisión

| Tema | Opción A | Opción B | Opción C | Recomendación |
|---|---|---|---|---|
| Alta | Creación directa con contraseña temporal | Invitación firmada | Ambos | Invitación como vía normal; alta directa sólo como recuperación excepcional auditada. |
| Roles | Acumulables sin restricción | Primario exclusivo | Matriz de combinaciones permitidas | Primario exclusivo para `participant`, `reviewer`, `judge`; `admin` separado. |
| Correo | No requerido | Verificado al aceptar | Verificado antes de invitar | Verificación obligatoria antes de entrar al shell juez. |
| 2FA | Opcional | Obligatorio al enviar | Obligatorio para todo acceso juez | Obligatorio antes de acceder a datos asignados. |

### Modelo propuesto

- `users` conserva credenciales, correo verificado y TOTP; no duplicar secretos.
- `judge_profiles` es uno-a-uno con `users`, con `public_id`, `assignment_role=primary|substitute`, estado operativo, capacidad `NULL` para principal o `10` para sustituto, alta/suspensión/reactivación, actores y timestamps. No tiene especialidad ni categorías asignadas.
- `judge_invitations` queda descartada por `P2B-DEC-002`; M2 no debe crear esa tabla, token o flujo.
- El alta directa debe crear cuenta, rol único y perfil pendiente dentro de una transacción; nunca marca el correo como verificado por inferencia ni expone una contraseña inicial. La activación requiere correo verificado y contraseña propia establecida mediante el mecanismo seguro de recuperación/configuración.
- Suspender bloquea rutas y mutaciones 02B y revoca sesiones. Dado que los roles son excluyentes, no existe una capacidad secundaria que deba conservar acceso a otro shell.
- Recuperación de TOTP requiere procedimiento operativo con identidad comprobada fuera de la aplicación, permiso separado, razón, revocación de sesiones, auditoría y notificación. No se diseña un bypass permanente.

### Gate de rutas recomendado

- Shell participante: `auth`, `verified`, rol/capacidad participante explícita.
- Shell juez: `auth`, `verified`, `judge.active`, eventual `2fa.confirmed`, permiso base de juez.
- Shell administrativo: permisos actuales; acciones 02B con permisos propios y confirmación de contraseña cuando sean críticas.
- Redirección post-login por capacidad explícita; usuario sin rol obtiene pantalla segura sin datos, no un shell por descarte.

## 7. Contrato 2 — asignaciones

### Contrato aprobado

- La asignación es exclusivamente manual.
- Cada propuesta elegible requiere cuatro evaluaciones, tanto en Movilidad con Flow como en Hermosillo Florece, Mi familia, mi mascota y Hermosillo sin Barreras.
- Los cuatro jueces principales evaluarán todas las propuestas elegibles de las cuatro categorías; no existe especialidad, filtro temático ni límite fijo de asignaciones para ellos.
- Un quinto juez será exclusivamente sustituto: no recibe asignaciones iniciales y admite como máximo diez reasignaciones activas. Para ese límite cuentan `assigned`, `in_progress`, `conflict_declared` pendiente de resolución y `reopened`; dejan de contar `submitted`, `voided`, `cancelled`, `replaced` y `expired`.
- El plazo global de envío es `2026-08-27 23:59:59 America/Hermosillo`.

`P2B-BLOCK-001` quedó `RESOLVED BY OWNER`: la cobertura inicial no se limita por capacidad y existe una reserva explícita para hasta diez sustituciones activas. M4 continúa sujeto a autorización separada, no a una decisión adicional de capacidad.

### Invariantes propuestos

- Sólo una propuesta `submitted` con revisión de admisibilidad `admitted` puede ser candidata; la elegibilidad se consulta, no se copia como nuevo estado de `submissions`.
- La asignación se liga a `submission_version_id`, versión de rúbrica y paquete ciego exactos.
- Una asignación queda activa únicamente después de validación completa y confirmación administrativa transaccional.
- Unique lógico por `submission_version_id + judge_profile_id`; reintentos son idempotentes.
- La cantidad requerida, funciones, capacidades, ausencia de especialidad y plazo son `OWNER_APPROVED`.
- Reasignar no borra: cancela/invalida la asignación original con razón y crea otra.
- Un conflicto bloquea de inmediato la asignación original. `admin` decide; si lo confirma, la asignación se marca `voided` y crea manualmente otra para el juez sustituto. No se reasigna automáticamente, no reutiliza al juez conflictuado y la undécima sustitución activa falla cerrada.
- Una asignación incompleta no cambia `submissions.status`. El estado de cobertura se deriva del número de asignaciones activas y evaluaciones válidas frente al contrato aprobado.
- Ninguna migración inicial crea asignaciones para las propuestas existentes.

### Estados de asignación propuestos

`draft → active → completed`, con salidas auditadas `cancelled`, `replaced` o `expired`. El estado de evaluación se mantiene separado para evitar que una asignación activa se confunda con una evaluación enviada.

## 8. Contrato 3 — evaluación ciega y minimización

### Matriz campo por campo

| Elemento | Clasificación propuesta | Regla |
|---|---|---|
| Identificador de asignación | Visible | Alias opaco propio de evaluación, no ID secuencial ni folio. |
| Categoría | Visible | Nombre de categoría necesario para contextualizar rúbrica. |
| Título | Visible | Se muestra desde el paquete estructural; el propietario acepta posible autoidentificación. |
| Resumen | Visible | Se muestra completo; el propietario acepta posible autoidentificación. |
| Contenido enriquecido/texto | Visible | Se muestra sanitizado desde la versión enviada, sin campos estructurados de identidad. |
| Tipo individual/equipo | Visible anonimizado | Se muestra modalidad sin nombres ni datos de integrantes. |
| Integrantes y representante | Oculto | Nombres, correos y relación con participante fuera de juez. |
| Folio | Anonimizado | Sustituir por alias de asignación; el folio puede ser conocido fuera del sistema. |
| Fecha/hora de envío | Visible | Se muestra en `America/Hermosillo`, sin actor ni contacto. |
| Categoría/edición | Visible | Sólo campos públicos necesarios. |
| Anexos de propuesta | Visible | Todos los anexos evaluables de la propuesta; nunca residencia ni archivos de aclaración. |
| Nombre original de archivo | Oculto | Puede contener identidad; presentar etiqueta neutra. |
| MIME, extensión y tamaño | Visible | Metadatos mínimos útiles; sin path, hash, actor o nombre interno. |
| Imágenes editor | Visible | Se muestran; metadatos técnicos se eliminan y el riesgo de rostro/logo se acepta. |
| Enlaces externos | Visible | Se muestran los enlaces evaluables; el riesgo de que revelen cuenta/organización se acepta. |
| Residencia/comprobantes | Oculto | Prohibición absoluta para juez. |
| Perfil/contacto/fecha de nacimiento/colonia | Oculto | PII sin necesidad de evaluación. |
| Notas internas | Oculto | Sólo operación de admisibilidad autorizada. |
| Aclaraciones y sus archivos | Oculto | Pueden contener PII; no se heredan al juez. |
| Historial de admisibilidad | Oculto | El juez sólo necesita la señal “habilitada para evaluación”, no razones ni actores. |
| Otros jueces, scores o ranking | Oculto | Independencia y mínimo privilegio. |

### Procedimiento aprobado de anonimización simple automática

1. Crear un `blind_review_package` derivado del `submission_version` sin alterar el snapshot original.
2. Construir automáticamente una proyección allowlist con campos sustantivos, categoría, modalidad, fechas, enlaces y anexos evaluables.
3. Excluir automáticamente nombres, correos, teléfonos, perfil, integrantes/representante, folio original, residencia, notas, aclaraciones, admisibilidad, paths, hashes, actores y nombres originales de archivo.
4. Presentar anexos con etiquetas neutras y sólo metadatos mínimos de tipo/tamaño. El contenido binario no se redacta.
5. Fijar una versión inmutable del paquete a cada asignación; una corrección futura crea una versión nueva.
6. Registrar acceso/descarga sin copiar texto, score, comentario o PII a logs.

El propietario acepta expresamente que texto, imágenes, enlaces o anexos puedan contener identidad y que el sistema no los bloquee ni someta a certificación humana. “Automática” significa anonimización estructural determinista; no significa detectar o eliminar identidad semántica. Residencia, PII estructurada, notas internas, aclaraciones e historial de admisibilidad permanecen prohibidos.

## 9. Contrato 4 — rúbrica versionada

### Rúbrica global aprobada

| Código | Criterio | Descripción aprobada | Peso | Escala | Paso |
|---|---|---|---:|---:|---:|
| `relevance` | Pertinencia | Correspondencia con la convocatoria y la categoría seleccionada. | 20 % | 0–10 | 0.5 |
| `clarity` | Claridad | Definición comprensible del problema, objetivo y propuesta. | 20 % | 0–10 | 0.5 |
| `feasibility` | Viabilidad | Factibilidad técnica, operativa y temporal. | 25 % | 0–10 | 0.5 |
| `impact` | Impacto | Beneficio esperado y alcance para la población objetivo. | 25 % | 0–10 | 0.5 |
| `coherence` | Coherencia | Consistencia integral y calidad de la presentación. | 10 % | 0–10 | 0.5 |
|  | **Total** |  | **100 %** |  |  |

La misma rúbrica aplica a las cuatro categorías. Su primera versión técnica será `1.0`; se activa antes de crear asignaciones y queda inmutable al activarse. No existe puntaje mínimo aprobado.

### Contrato aprobado

- La rúbrica 02B pertenece a la convocatoria y es global para las cuatro categorías; no existen overrides por categoría en este alcance.
- Una versión `draft` puede editarse; `active` exige validaciones; al activarse queda inmutable.
- Sustituirla crea una versión nueva; nunca muta asignaciones o evaluaciones anteriores.
- Cada asignación/evaluación guarda la versión exacta, no “la activa” por consulta tardía.
- Criterios incluyen código estable, nombre, descripción, peso o máximo de puntos, mínimo, máximo, paso, orden y regla de comentario.
- Rangos, pesos, precisión, redondeo y consolidación son `OWNER_APPROVED`; no existe mínimo total.
- No usar `float`; persistir decimales con escala aprobada y cálculo reproducible.
- La activación valida duplicados, rangos, pasos, cobertura de pesos/puntos y texto obligatorio; no inventa valores faltantes.

### Contrato técnico aprobado

```text
RUBRIC_SCOPE
- competition_slug: hermosillo-florece-2026
- category_slug: ALL
- version_label: 1.0
- title_es: Rúbrica de evaluación Flower Flow 2026
- instructions_es: Califica cada criterio de 0 a 10 en incrementos de 0.5. El sistema calcula y muestra el total ponderado; el comentario general es obligatorio.
- effective_from: al activarse administrativamente antes de asignar

SCORING_CONTRACT
- formula: NORMALIZED_WEIGHTED
- weights_total: 100
- internal_decimal_scale: 4
- displayed_decimal_scale: 2
- rounding_mode: HALF_UP
- minimum_total_if_any: NONE
- general_comment: REQUIRED; 100–2000 caracteres
- consolidation_rule: media aritmética de los cuatro totales válidos submitted, con igual peso
- missing_evaluation_rule: bloquear consolidación definitiva hasta completar cuatro evaluaciones válidas

CRITERIA
1. relevance | Pertinencia | Correspondencia con la convocatoria y la categoría seleccionada | 20 | 0 | 10 | 0.5 | OPTIONAL | 1
2. clarity | Claridad | Definición comprensible del problema, objetivo y propuesta | 20 | 0 | 10 | 0.5 | OPTIONAL | 2
3. feasibility | Viabilidad | Factibilidad técnica, operativa y temporal | 25 | 0 | 10 | 0.5 | OPTIONAL | 3
4. impact | Impacto | Beneficio esperado y alcance para la población objetivo | 25 | 0 | 10 | 0.5 | OPTIONAL | 4
5. coherence | Coherencia | Consistencia integral y calidad de la presentación | 10 | 0 | 10 | 0.5 | OPTIONAL | 5
```

## 10. Contrato 5 — ciclo de evaluación

### Máquina de estados aprobada para diseño

`assigned → in_progress → submitted`

Salidas controladas: `assigned|in_progress → conflict_declared`; `submitted → reopened → in_progress`; cualquier estado no final o una evaluación enviada invalidada puede pasar a `voided` mediante autoridad y razón aprobadas.

### Transiciones e invariantes

| Transición | Actor/permiso propuesto | Precondiciones | Transacción y auditoría | Notificación | Reversibilidad/efecto |
|---|---|---|---|---|---|
| creación → `assigned` | Admin `assign evaluations` | juez activo, paquete/rúbrica inmutables, propuesta admitida, sin duplicado, cobertura/capacidad aprobadas | lock de propuesta/asignación; evento con IDs públicos, no PII | Asignación | Cancelable antes de trabajo; no cambia propuesta. |
| `assigned` → `in_progress` | Juez asignado `edit own evaluation` | asignación activa, plazo abierto, sin conflicto | crear/actualizar revisión de borrador con lock/version | Ninguna por cada guardado | Borrador editable; total siempre recalculado. |
| `assigned`/`in_progress` → `conflict_declared` | Juez asignado `declare own conflict` | catálogo/motivo aprobado, asignación abierta | declaración append-only + bloqueo inmediato | Operación/admin | No vuelve automáticamente; resolución administrativa. |
| `in_progress` → `submitted` | Juez asignado `submit own evaluation` | criterios completos, comentario general, plazo, sin conflicto; 2FA no obligatorio | lock, validar rúbrica, recalcular servidor, crear versión inmutable, idempotency key | Acuse a juez y operación mínima | No editable; no declara ganador. |
| `submitted` → `reopened` | `admin` con `reopen evaluations` | antes de `2026-08-27 20:00:00 America/Hermosillo`, razón 20–1,000 y password confirm | conservar envío previo; crear nueva revisión ligada; auditoría reforzada | Juez | Excepcional; nunca sobrescribe el envío previo. |
| `reopened` → `in_progress` | Juez asignado o `admin` actuando en su nombre | reapertura vigente y antes de `2026-08-27 23:59:59 America/Hermosillo` | primer guardado en revisión nueva; actor real y `on_behalf_of` separados | Ninguna por guardado | Sólo la revisión nueva es editable. |
| `conflict_declared` → `voided` | Resolutor aprobado | conflicto confirmado y razón | anular evaluación/asignación, conservar evidencia; eventual reemplazo separado | Juez y operación | No aporta a consolidación. |
| cualquier aplicable → `voided` | Autoridad `void evaluations` | causa aprobada, confirmación de contraseña, concurrencia controlada | evento append-only; nunca delete | Juez y operación | Excluida del cálculo; no se restaura silenciosamente. |

### Inmutabilidad y reapertura

El contrato aprobado usa un agregado `evaluations` con revisiones append-only. Cada envío crea una revisión final con scores y comentarios inmutables. Reabrir clona explícitamente la última versión a un nuevo borrador y conserva `source_revision_id`; la versión anterior sigue auditable. `admin` puede modificar puntajes en nombre del juez únicamente dentro de esa revisión reabierta y antes del cierre global. La UI puede conservar la relación con el juez, pero auditoría y datos deben identificar inequívocamente al actor administrativo; nunca se falsifica `updated_by` ni se sobrescribe la revisión del juez.

## 11. Contrato 6 — cálculo y consolidación

### Reglas técnicas aprobadas

- El request sólo envía valores por criterio y comentarios; cualquier `total`, componente ponderado o consolidado cliente se ignora.
- El servidor carga asignación, rúbrica/criterios inmutables y revisión bajo lock; valida rango y múltiplo de `step` con aritmética decimal.
- Guarda valor ingresado, componente calculado, total interno y total mostrado conforme a precisión/modo aprobados.
- El envío falla cerrado si falta un criterio o comentario obligatorio.
- Evaluaciones `voided`, con conflicto o no enviadas no aportan.
- El reemplazo de juez crea nueva asignación; no mueve ni atribuye scores.
- Si falta el número requerido, el consolidado queda `incomplete`; no se reduce denominador silenciosamente.
- Detectar empate sólo compara el consolidado canónico después del redondeo aprobado y emite `tie_detected`; no resuelve ni declara ganador.

### Fórmula aprobada

Por criterio:

```text
component = (score / 10) × weight
```

Por evaluación:

```text
total_raw = sum(component)
total_displayed = round_half_up(total_raw, 2)
```

- `score`: decimal entre 0 y 10, múltiplo de 0.5.
- `weight`: 20, 20, 25, 25 o 10 según el criterio.
- `component` y `total_raw`: `DECIMAL` con cuatro decimales internos.
- total visible: dos decimales, `HALF_UP`, rango 0.00–100.00.
- comentario general obligatorio de 100–2,000 caracteres; comentario por criterio opcional hasta 1,000.

Consolidación:

```text
consolidated_raw = sum(four submitted valid total_raw) / 4
consolidated_displayed = round_half_up(consolidated_raw, 2)
```

Las cuatro evaluaciones pesan igual. Si falta una, el estado es `incomplete` y se bloquea la consolidación definitiva; no existe excepción administrativa aprobada. Hay empate técnico cuando `consolidated_displayed` es exactamente igual a dos decimales. Detectar empate no lo resuelve ni declara ganador.

## 12. Contrato 7 — seguridad y auditoría

### Matriz negativa de acceso

| Actor/estado | Shell juez | Paquete ciego asignado | Guardar borrador | Enviar | PII/residencia/notas | Administrar 02B |
|---|---:|---:|---:|---:|---:|---:|
| Visitante | No | No | No | No | No | No |
| Participante | No | No | No | No | Sólo recursos propios actuales | No |
| Reviewer | No, salvo rol aprobado separado | No | No | No | Admisibilidad según permisos actuales | No por defecto |
| Admin | No como juez por defecto | Sólo vista administrativa explícita | No como juez | No como juez | Según permisos actuales, nunca mediante proyección juez | Sí, permisos granulares |
| Autenticado sin rol | No | No | No | No | No | No |
| Judge activo y asignado | Sí | Sí, sólo versión ligada | Sí, si vigente | Sí, si completo/vigente | Nunca | No |
| Judge no asignado | Sí, lista propia vacía | No | No | No | Nunca | No |
| Judge con conflicto | Sí | Recomendado: sólo contexto mínimo, no anexos | No | No | Nunca | No |
| Judge con asignación vencida | Sí, historial mínimo | Sólo lectura si se aprueba | No | No | Nunca | No |
| Judge con evaluación enviada | Sí | Sólo lectura propia | No | Idempotente, sin duplicar | Nunca | No |
| Judge suspendido/revocado | No | No | No | No | Nunca | No |

### Amenazas y mitigaciones

| Amenaza | Mitigación propuesta | Prueba futura mínima |
|---|---|---|
| IDOR | Route binding por `public_id`, scope a asignaciones propias y Policy; 404/403 consistente | Juez A altera IDs de B y de propuesta no asignada. |
| Acceso no asignado | Query scope obligatorio + Policy, nunca `SubmissionPolicy::view` actual | Lista, detalle y descarga negativos. |
| Fuga de PII | DTO/proyección allowlist ciega; no cargar relaciones de usuario/equipo/residencia | Assert payload/HTML y query review. |
| Residencia | Sin relación/ruta desde módulo juez; permiso imposible para rol judge | URL directa, route model binding y export negativos. |
| Anexo no autorizado | Tabla allowlist por paquete/version, controller/Policy, nombre neutro | Archivo de otra versión/kind/assignment rechazado. |
| Manipular puntaje | Recalcular servidor con versión fija; ignorar total cliente | Total hostil, rango/step alterado. |
| Doble envío | idempotency key, unique y lock | Dos procesos producen un solo envío. |
| Reapertura no autorizada | permiso separado, password confirm, razón y nueva revisión | juez/reviewer/admin sin permiso rechazados. |
| Cambio retroactivo rúbrica | inmutabilidad al activar y FK a versión exacta | intento de update/delete falla; evaluación previa igual. |
| Reasignación inconsistente | lock, estados terminales y reemplazo referenciado | carreras conflicto/reasignación. |
| Concurrencia de borrador | `lock_version`/optimistic locking más transacción | dos pestañas no pisan cambios silenciosamente. |
| Mass assignment | `$fillable` mínimo o DTO/Action con atributos explícitos | campos actor/total/status inyectados se ignoran/rechazan. |
| Exportaciones | Fuera del MVP 02B salvo aprobación; si existen, backend/permiso/password/allowlist | juez no descarga ranking ni PII. |
| Logs sensibles | IDs públicos, flags y conteos; sin scores/comentarios/texto/PII | scan de logs/audit metadata. |

### Eventos mínimos de auditoría

Cuenta de juez creada/activada/suspendida/recuperada, asignación activada/cancelada/reemplazada, paquete ciego generado/activado/accedido/descargado, conflicto declarado/resuelto, borrador iniciado —sin contenido—, evaluación enviada, reapertura, edición administrativa en nombre del juez, void, cambio/activación de rúbrica y fallos de autorización relevantes. Scores y comentarios no se duplican en `audit_logs`.

## 13. Contrato 8 — UX accesible

| Superficie | Rol | Propósito | Datos visibles | Acciones | Estados obligatorios | Teclado, foco y validación | Reflow/móvil | Confirmación y protección PII |
|---|---|---|---|---|---|---|---|---|
| Dashboard juez | Judge activo | Priorizar trabajo propio. | Etiqueta neutra, título sustantivo, categoría, plazo y estado propios; sin folio ni PII estructurada. | Abrir; filtrar; iniciar; declarar conflicto. | Vacío, carga, error, éxito, sin permiso, cerrado/vencido y suspendido. | Orden lógico; foco al `h1`; filtros con labels; errores anunciados. | Tarjetas/tabla adaptativa a 320 px sin scroll doble. | Puede haber autoidentificación en el título; no muestra folio/PII estructurada ni acciones de otro juez. |
| Instrucciones/rúbrica | Judge activo | Conocer contrato exacto antes de evaluar. | Versión, instrucciones, criterios, rangos y ayuda aprobados. | Consultar; volver a asignación. | Vacío/no configurada, carga, error, éxito, sin permiso y versión retirada/cerrada. | Encabezados/tabla semánticos; foco a versión; ayuda vinculada. | Criterios apilados; texto y tablas reflow 200 %. | Sin ranking ni scores ajenos; no requiere confirmación. |
| Detalle ciego | Judge asignado | Revisar paquete autorizado. | Todos los campos sustantivos y anexos evaluables de la versión ligada; sin PII estructurada. | Abrir anexo; iniciar; declarar conflicto. | Vacío, carga, error, éxito, 403/404 y paquete revocado/cerrado. | Foco al título; links descriptivos; error de archivo anunciado. | Contenido/medios sin desborde y botones táctiles. | Confirmar conflicto; HTML/URL/metadatos generados no agregan PII, aunque el contenido puede autoidentificar. |
| Declaración de conflicto | Judge asignado | Bloquear captura y escalar resolución. | Alias, catálogo y texto permitido; no datos de participante. | Seleccionar; explicar; confirmar; cancelar. | Catálogo vacío, carga, error conservando texto, éxito, sin permiso y asignación cerrada. | Diálogo accesible o página; trap/retorno de foco; error por campo. | Formulario de una columna y controles táctiles. | Confirmación explícita; motivo sensible no se envía completo por correo/log. |
| Evaluación en borrador | Judge asignado | Capturar scores y comentarios. | Rúbrica exacta, paquete ciego y total informativo del servidor. | Puntuar; comentar; guardar; continuar a confirmación. | Borrador vacío, carga, guardando, guardado, error, sin permiso, conflicto y vencido/cerrado. | Labels/fieldset; foco al primer error; live region de guardado; no sólo color. | Un criterio por bloque; controles no dependen de hover; 320 px/zoom 200 %. | No confirmar cada guardado; no guardar total cliente ni exponer PII. |
| Confirmación de envío | Judge asignado | Revisar y cerrar evaluación. | Resumen por criterio, comentarios permitidos y total recalculado. | Volver; confirmar envío. | Carga, error, listo, incompleto, sin permiso, conflicto y vencido. | Foco al `h1`; resumen semántico; validación enlaza al criterio. | Resumen apilado y CTA persistente sin ocultar contenido. | Confirmación explícita; 2FA opcional y sin requisito adicional inventado; doble clic idempotente. |
| Historial propio | Judge activo | Consultar envíos y reaperturas propias. | Alias, categoría, versiones propias, timestamps y estados permitidos. | Ver versión; descargar sólo si se aprueba. | Vacío, carga, error, éxito paginado, sin permiso y cuenta suspendida. | Paginación/filters accesibles; foco tras cambio de página. | Lista alternativa móvil; sin columnas truncadas críticas. | Sin jueces/scores/ranking ajenos; acciones sólo lectura. |
| Gestión de jueces | Admin con permiso | Controlar alta directa, estado y capacidad aprobada. | Datos mínimos de cuenta juez, estado y carga agregada. | Crear; activar; suspender; reactivar; recovery autorizado. | Vacío, carga, error, éxito, sin permiso y servicio cerrado. | Tabla/form accesibles; foco a resultado; validación sin enumeración. | Tabla a tarjetas; acciones no sólo iconos. | Confirmar suspensión/recovery; PII mínima y no exportable por defecto. |
| Gestión de asignaciones | Admin con permiso | Cubrir propuestas admitidas sin duplicados. | Alias admin, categoría, cobertura, capacidad, plazo y conflictos. | Crear; activar; cancelar; reemplazar; filtrar. | Sin elegibles, carga, error, éxito, sin permiso, cobertura incompleta y cierre. | Filtros con labels; foco a resumen; errores de carrera accionables. | Selección por pasos en móvil; sin drag-and-drop obligatorio. | Confirmar activar/cancelar/reemplazar; no mostrar residencia/notas. |
| Gestión de rúbricas | Admin con permiso | Crear/validar/activar versiones. | Draft, criterios, validaciones, versión/estado. | Crear; editar draft; validar; previsualizar; activar; superseder. | Vacío, carga, error, draft válido/inválido, éxito, sin permiso y versión inmutable. | Fieldsets; foco a primer error; tabla editable con alternativa lineal. | Editor de una columna en móvil y controles numéricos legibles. | Confirmar activación/sustitución; no permitir editar activa. |
| Reapertura excepcional | Admin con permiso | Crear revisión nueva sin tocar el envío. | Alias, versión enviada, razón, alcance y plazo global. | Reabrir; cancelar; editar nueva revisión en nombre del juez dentro de ventana. | No disponible, carga, error, éxito, sin permiso, ya reabierta y plazo cerrado. | Página separada; foco al encabezado/error; razón obligatoria. | Formulario lineal; confirmación visible completa. | Autoridad simple con password confirm; actor admin y juez sujeto separados; sin copiar scores/comentarios a logs/mail. |

Todas reutilizan layouts, componentes, assets y lenguaje visual actuales. No se propone SPA, API, microservicios, Redis ni dependencia nueva. Se requieren teclado, foco visible, mensajes asociados, zoom 200 %, reflow, estados vacíos/error/éxito/sin permiso/cerrado y ausencia de PII en títulos, breadcrumbs, URLs o HTML oculto.

## 14. Contrato 9 — notificaciones y operación

| Evento | Destino mínimo | Contenido permitido | Idempotencia |
|---|---|---|---|
| Alta directa de juez | juez creado | aviso de cuenta, siguiente paso seguro y soporte; nunca contraseña temporal ni propuesta | `judge_profile_id + account_created + template_version` |
| Asignación | juez | alias, categoría, plazo y acceso autenticado | `assignment_id + assigned_at` |
| Conflicto declarado | operación/resolutor | alias de asignación y estado; sin motivo sensible completo por email | `conflict_id + declared` |
| Conflicto resuelto | juez declarante | resultado operativo y siguiente paso; sin motivo sensible completo | `conflict_id + resolution_version` |
| Reasignación | juez saliente y juez entrante | cambio de estado y siguiente acción correspondiente | ID de reemplazo + evento + destinatario |
| Evaluación enviada | juez y operación mínima | acuse, alias, timestamp, versión; no scores/comentarios | `evaluation_revision_id + submitted` |
| Reapertura | juez | razón operativa mínima, nuevo plazo y enlace | `evaluation_revision_id + reopened` |
| Cierre | juez | estado final de sus pendientes | asignación + cierre |

La opción mínima de juez no incluye recordatorios de vencimiento: sólo alta directa, asignación, conflicto resuelto, reasignación, evaluación enviada, reapertura y cierre.

Separadamente, el propietario aprobó dos recordatorios transaccionales de recepción para participantes:

- `2026-08-20 09:00:00 America/Hermosillo` y `2026-08-22 09:00:00 America/Hermosillo`;
- sólo cuentas `participant` con correo verificado que no tengan propuestas o con al menos un borrador;
- una cuenta con una propuesta enviada y otra en borrador sí recibe el correo;
- una cuenta cuyas propuestas estén todas enviadas no lo recibe;
- un correo por cuenta y ventana, no por propuesta, con diseño profesional coherente con la landing y sin títulos/contenido/PII adicional;
- clave idempotente `competition_id + participant_user_id + reminder_window + template_version`.

Estos recordatorios son un subalcance de comunicaciones de participantes, no una función de juez, y no forman parte de M1. Reutilizar notificaciones/Mailables HTML+texto, `ShouldQueueAfterCommit`, conexión/cola configurables, retries y `failed_jobs`. El evento de dominio se persiste en la transacción y un delivery ledger evita duplicados. Un fallo de enqueue no revierte la operación principal, pero deja aviso/estado recuperable. No incluir PII, residencia, título, contenido, score o comentario en asunto/log. SMTP real permanece fuera.

### Retención 02B aprobada

Evaluaciones en borrador o enviadas, scores, comentarios, conflictos, reaperturas y auditoría 02B se conservan 24 meses contados desde `evaluation_cycle_closed_at`, el cierre administrativo explícito del ciclo. El plazo no se cuenta desde el deadline ni desde resultados por inferencia. La futura purga debe contemplar backups y quedar sujeta a validación jurídica/operativa antes de ejecutarse; M1 no implementa retención ni borrado.

## 15. Contrato 10 — compatibilidad con datos existentes

### Estrategia futura obligatoria

- Migraciones exclusivamente aditivas; tablas nuevas y, sólo si es imprescindible, columnas nullable/indexadas.
- Foreign keys a `users`, `submission_versions`, `competitions` y `categories`; no reinterpretar `submissions.status`.
- No cambiar ni regenerar folios, snapshots, categorías, aceptaciones o archivos.
- No crear roles/asignaciones mediante migración de datos. Permisos/rol se introducirán con mecanismo idempotente revisado; producción no ejecutará seeders.
- Propuestas existentes no entran a evaluación hasta que el sistema genere/active el paquete estructural y un administrador active asignaciones expresamente.
- No asumir que toda propuesta `submitted` está admitida; exigir `eligibility_reviews.status=admitted` y versión correspondiente.
- Rollback de esquema sólo si no destruye evidencia. Tras existir perfiles/asignaciones/evaluaciones, un `down()` destructivo debe negarse o conservar tablas; el rollback operativo preferido es apagar el feature flag y usar código compatible.
- Ninguna migración descarga, copia, renombra o elimina archivos.
- Ensayar en futuro con dataset sintético que represente más de 50 propuestas, categorías, snapshots, archivos y estados, nunca con datos reales.

## 16. Arquitectura y modelo de datos propuestos

### Fronteras

| Módulo | Responsabilidad | No debe cargar |
|---|---|---|
| Judge IAM | alta directa, perfil, estado, gate, recuperación | perfiles participantes/propuestas |
| Rubrics | draft/validación/activación/versiones/criterios | identidad o resultados |
| Blind packages | proyección y anexos aprobados | residencia/notas/aclaraciones |
| Assignments | cobertura, plazo, reemplazo y relación juez-paquete-rúbrica | scores mutables |
| Conflicts | declaración, resolución y bloqueo | decisión de ganador |
| Evaluations | borrador/revisiones/envío/cálculo | total cliente/ranking global |
| Audit/notifications | eventos redactados y entrega idempotente | contenido sensible duplicado |

### Tablas propuestas para implementación posterior

| Tabla | Campos clave mínimos | Invariantes |
|---|---|---|
| `judge_profiles` | `public_id`, `user_id`, `assignment_role`, `status`, `max_active_assignments`, timestamps/actores | uno por usuario; principal=`NULL` sin límite fijo, sustituto=`10`; sin especialidad; estado gatea acceso. |
| `rubrics` | convocatoria, versión, status, title/instructions | unique competition+version; global para las cuatro categorías; activa inmutable. |
| `rubric_criteria` | rubric_id, code, text, range, step, weight/points, comment rule, order | unique code/order; restrict si usada. |
| `blind_review_packages` | submission_version_id, version, status, generated_by/at, payload allowlist | generado automáticamente e inmutable al activar; sin certificación humana; original intacto. |
| `blind_review_package_files` | package_id, submission_file/derivative reference, neutral label | sólo archivo autorizado y privado. |
| `judge_assignments` | public_id, submission_version_id, package_id, rubric_id, judge_profile_id, status, due_at, actors | unique versión+juez; reemplazo no delete. |
| `conflict_declarations` | assignment_id, category/code, reason encrypted/controlled, status, resolver, timestamps | append-only; bloqueo inmediato. |
| `evaluations` | public_id, assignment_id, rubric_id, state, current_revision, lock_version | una por asignación; total no mass-assignable. |
| `evaluation_revisions` | evaluation_id, revision, state, total_raw/rounded, general_comment, source_revision, submitted_at | submitted inmutable. |
| `evaluation_scores` | revision_id, criterion_id, score, calculated_component, comment | unique revision+criterion. |
| `evaluation_events` | evaluation_id, actor, event, from/to, metadata redactada, occurred_at | append-only. |
| `notification_deliveries` | event key, channel, status, attempts, timestamps | unique por evento/destino/plantilla. |

Se recomiendan Actions/Services para alta/activación directa, generar paquete estructural, activar/reasignar, declarar/resolver conflicto, guardar, enviar, reabrir y anular; Form Requests para entrada; Policies por recurso; enums respaldados; transacciones/locks en cada transición; DTO/proyección específica para juez.

## 17. Matriz vigente de decisiones del propietario

| ID | Decisión exacta aprobada | Consecuencia principal | Estado |
|---|---|---|---|
| P2B-DEC-001 | Roles estrictamente excluyentes; ninguna combinación. | Gate explícito por rol y rechazo de combinaciones. | `OWNER_APPROVED 2026-08-18` |
| P2B-DEC-002 | Alta directa por `admin`; sin invitación de juez. | M2 crea cuenta/perfil directamente y no tabla de invitaciones. | `OWNER_APPROVED 2026-08-18` |
| P2B-DEC-003 | Cuatro jueces por propuesta en cada una de las cuatro categorías; los cuatro principales evalúan todas las propuestas elegibles. | Cobertura requerida = 4; sin límite fijo para principales. | `OWNER_APPROVED / UPDATED 2026-08-18` |
| P2B-DEC-004 | Asignación manual. | Ningún algoritmo automático o semiautomático. | `OWNER_APPROVED 2026-08-18` |
| P2B-DEC-005 | Cuatro principales sin límite fijo; quinto sustituto exclusivo con máximo diez reasignaciones activas; sin especialidad. | Perfil tipado y checks server-side; sustituto nunca recibe carga inicial. | `OWNER_APPROVED / SUPERSEDES LIMIT 8 — 2026-08-18` |
| P2B-DEC-006 | Ceguera simple estructural. | Identidad estructurada oculta; contenido evaluable visible. | `OWNER_APPROVED 2026-08-18` |
| P2B-DEC-007 | Todos los campos sustantivos y anexos evaluables; nunca PII estructurada, residencia, notas, aclaraciones o historial. | Paquete allowlist y descargas propias; riesgo semántico aceptado. | `OWNER_APPROVED 2026-08-18` |
| P2B-DEC-008 | Anonimización automática estructural; se acepta identidad dentro de texto/anexos sin bloqueo. | Sin certificación humana ni promesa de anonimización semántica. | `OWNER_APPROVED / RISK_ACCEPTED 2026-08-18` |
| P2B-DEC-009 | Una rúbrica global de cinco criterios y pesos 20/20/25/25/10. | Versión 1.0 inmutable para las cuatro categorías. | `OWNER_APPROVED 2026-08-18` |
| P2B-DEC-010 | Escala 0–10, paso 0.5, ponderación normalizada, cuatro decimales internos, dos visibles, `HALF_UP`. | Cálculo sólo servidor, total 0–100. | `OWNER_APPROVED 2026-08-18` |
| P2B-DEC-011 | Comentario general obligatorio 100–2,000; comentarios por criterio opcionales hasta 1,000. | Validación de envío y UI. | `OWNER_APPROVED 2026-08-18` |
| P2B-DEC-012 | Media aritmética de cuatro totales válidos, igual peso. | Consolidado sólo cuando existe cobertura completa. | `OWNER_APPROVED 2026-08-18` |
| P2B-DEC-013 | Bloquear consolidación si falta una evaluación; sin excepción. | Estado derivado `incomplete` y reasignación necesaria. | `OWNER_APPROVED 2026-08-18` |
| P2B-DEC-014 | Catálogo cerrado: relación personal/familiar; profesional/económica; participación en propuesta; otro conflicto. | Código estable; “otro” exige explicación. | `OWNER_APPROVED 2026-08-18` |
| P2B-DEC-015 | `admin` resuelve y reasigna al quinto juez sustituto; varios jueces pueden compartir propuesta mediante asignaciones independientes. | Asignación original `voided`; reemplazo manual, exclusivo y con límite diez activo. | `OWNER_APPROVED / UPDATED 2026-08-18` |
| P2B-DEC-016 | Cierre global `2026-08-27 23:59:59 America/Hermosillo`. | Desde el segundo siguiente se rechazan envíos/reenvíos. | `OWNER_APPROVED 2026-08-18` |
| P2B-DEC-017 | `admin` reabre hasta `2026-08-27 20:00`; razón 20–1,000, password confirm, revisión nueva; juez o admin pueden editar hasta 23:59:59. | Actor real siempre auditable; no se sobrescribe envío previo. | `OWNER_APPROVED 2026-08-18` |
| P2B-DEC-018 | 2FA opcional; recovery autorizado por `admin`. | Sin enforcement TOTP; recuperación con razón/auditoría/revocación. | `OWNER_APPROVED / SECURITY RISK ACCEPTED 2026-08-18` |
| P2B-DEC-019 | Email mínimo para eventos de juez; recordatorios de participantes 20/22-ago a las 09:00 Hermosillo. | Ledger idempotente; recordatorio también si hay enviada + borrador. | `OWNER_APPROVED 2026-08-18` |
| P2B-DEC-020 | Retención uniforme 24 meses desde cierre administrativo de evaluación. | Purga futura sujeta a validación jurídica/backup. | `OWNER_APPROVED 2026-08-18` |
| P2B-DEC-021 | Empate por igualdad del consolidado redondeado a dos decimales. | Sólo señal técnica; resolución/ganador fuera de 02B. | `OWNER_APPROVED 2026-08-18` |

### Historial de alternativas anterior a la aprobación

La tabla siguiente conserva las opciones y recomendaciones presentadas al propietario. Su columna `Estado` describe el estado histórico previo a las respuestas y está sustituida por la matriz vigente anterior; no debe interpretarse como una decisión todavía abierta.

Las letras “Opción A/B/C” usadas por el propietario en su respuesta final corresponden al bloque explicativo de seguimiento que recibió después de esta tabla histórica. Para evitar cualquier ambigüedad, la autoridad no es la letra aislada sino el texto exacto `OWNER_APPROVED` de la matriz vigente y ADR-0008.

| ID | Tema | Evidencia actual | Decisión necesaria | Opción A | Opción B | Opción C | Recomendación | Impacto técnico | Impacto operativo | Riesgo | Archivos o módulos futuros | Estado |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| P2B-DEC-001 | Exclusividad de roles | Spatie admite acumulación; shell participante opera por descarte. | Combinaciones permitidas y rol primario. | Acumulables sin límite. | Participant/reviewer/judge exclusivos. | Matriz de combinaciones. | B; admin separado. | Gates, redirects, constraints y tests. | Cuentas separadas por función. | Privilegios cruzados. | IAM, rutas, Policies, tests. | `PROPOSAL_NEEDED` |
| P2B-DEC-002 | Alta de jueces | No existe onboarding controlado. | Mecanismo normal y excepción. | Creación directa. | Invitación firmada. | Ambos. | B; A sólo break-glass auditado. | Tokens, estados, Action transaccional. | Soporte de expiración/recovery. | Toma o enumeración de cuenta. | Invitaciones, mail, admin, IAM. | `PROPOSAL_NEEDED` |
| P2B-DEC-003 | Jueces por propuesta | No existe cantidad ni diferencia por categoría. | Cantidad exacta y alcance. | Fija global. | Tabla por categoría. | Rango/cobertura mínima. | B si difiere; en otro caso A. | Constraints y cálculo de cobertura. | Dimensionamiento de jueces. | Cobertura insuficiente. | Assignments, dashboard, tests. | `PROPOSAL_NEEDED` |
| P2B-DEC-004 | Modo de asignación | No hay algoritmo ni especialidades aprobados. | Nivel de automatización. | Manual. | Semiautomática con preview. | Automática. | A para primera versión. | Action simple, sin algoritmo opaco. | Mayor carga administrativa. | Asignación sesgada/inconsistente. | Assignments, admin UX. | `PROPOSAL_NEEDED` |
| P2B-DEC-005 | Capacidad/especialidad | Perfil juez no existe. | Límite y catálogo exactos. | Sin límites/especialidad. | Capacidad máxima. | Capacidad y categorías/especialidad. | C si hay catálogo; si no B. | Campos, validadores y filtros. | Mantenimiento de disponibilidad. | Sobrecarga o mala correspondencia. | Profiles, assignment validation. | `PROPOSAL_NEEDED` |
| P2B-DEC-006 | Modalidad ciega | Separación es obligatoria; profundidad no definida. | Modelo exacto de ceguera. | Quitar contacto directo. | Doble ciego con paquete humano. | Seudónimo/campos aprobados. | B. | DTO/package/Policy separado. | Revisión previa de paquetes. | Reidentificación. | Blind packages, Policies, UX. | `PROPOSAL_NEEDED` |
| P2B-DEC-007 | Campos/anexos visibles | Snapshot contiene PII, equipo, nombres y links. | Allowlist exacta por campo/tipo. | Sólo texto aprobado. | Texto y anexos revisados. | Paquete por categoría. | B con allowlist explícita. | Proyección y descargas propias. | Preparación/etiquetado de anexos. | Identidad dentro de archivos. | Packages, DTOs, downloads. | `PROPOSAL_NEEDED` |
| P2B-DEC-008 | Anonimización | Automatización no resuelve identidad semántica. | Proceso, aprobador y SLA. | Automática. | Humana. | Automática asistida + humana. | C; aprobación humana obligatoria. | Scanner opcional y certificación. | Capacitación y cola de revisión. | Falso anonimato. | Packages, admin review, audit. | `PROPOSAL_NEEDED` |
| P2B-DEC-009 | Rúbrica definitiva | No existen criterios ni textos. | Plantilla completa y scope. | Una global. | Una por categoría. | Global con overrides. | Usar plantilla de sección 9; elegir scope expreso. | Define tablas, validación y UI. | Capacitación/instrucciones. | Implementar reglas inventadas. | Rubrics, criteria, evaluation. | `PROPOSAL_NEEDED` |
| P2B-DEC-010 | Rangos/pesos/precisión/redondeo | Sólo cálculo servidor está fijado. | Fórmula y parámetros exactos. | Suma de puntos. | Normalizada ponderada. | Fórmula propia documentada. | Según rúbrica; decimal explícito. | Calculator, DECIMAL y pruebas de borde. | Explicabilidad del puntaje. | Totales irreproducibles. | Calculator, schema, tests. | `PROPOSAL_NEEDED` |
| P2B-DEC-011 | Comentarios | No se conoce obligatoriedad. | Regla general y por criterio. | Sólo general. | Cada criterio. | Condicional por criterio/rango. | Definir por criterio y general separado. | Requests, criterio y UI. | Tiempo de captura/revisión. | Envío incompleto o carga excesiva. | Rubric criteria, forms, tests. | `PROPOSAL_NEEDED` |
| P2B-DEC-012 | Consolidación | No existe regla multi-juez. | Operador y peso por juez. | Media. | Mediana. | Ponderada/otra fórmula. | A si todos pesan igual. | Aggregate reproducible. | Interpretación operativa. | Resultado numérico sesgado. | Aggregates, reports, tests. | `PROPOSAL_NEEDED` |
| P2B-DEC-013 | Evaluaciones faltantes | No hay cobertura ni cierre. | Bloqueo o excepción exacta. | Bloquear consolidación. | Consolidar parcial. | Excepción administrativa. | A; C sólo si se aprueba aparte. | Estado `incomplete` y gates. | Puede retrasar cierre. | Sesgo por denominador variable. | Coverage, closure, alerts. | `PROPOSAL_NEEDED` |
| P2B-DEC-014 | Catálogo de conflictos | No existe taxonomía. | Tipos, motivo y visibilidad. | Catálogo cerrado. | Texto libre. | Catálogo + otro controlado. | C. | Enum/catalog y validación. | Capacitación y privacidad del motivo. | Subregistro o exposición sensible. | Conflicts, UX, audit. | `PROPOSAL_NEEDED` |
| P2B-DEC-015 | Resolver/reasignar | No existe autoridad ni SLA. | Actor, plazo y efecto exactos. | Admin general. | Comité/permiso específico. | Resolución automática. | B; nunca C. | Policies, locks, reemplazos. | Segregación y tiempos de respuesta. | Reasignación arbitraria/concurrente. | Conflicts, assignments, mail. | `PROPOSAL_NEEDED` |
| P2B-DEC-016 | Fecha límite | No hay calendario de evaluación. | Fecha/zona y overrides. | Global. | Por categoría. | Por asignación. | A base y override auditado sólo si se aprueba. | UTC/Hermosillo, middleware y scheduler. | Comunicación/recordatorios. | Edición fuera de plazo. | Config/data, middleware, scheduler. | `PROPOSAL_NEEDED` |
| P2B-DEC-017 | Reapertura | Sólo se contempla si el owner la aprueba. | Permitir, autoridad, razón y plazo. | Prohibida. | Autoridad simple. | Doble autorización. | B o C según riesgo; revisión nueva. | Versiones append-only y permiso. | Soporte excepcional. | Reescritura de evidencia. | Revisions, permissions, audit. | `PROPOSAL_NEEDED` |
| P2B-DEC-018 | 2FA | Fortify TOTP existe pero es opcional. | Momento de enforcement y recovery. | Opcional. | Obligatoria al enviar. | Obligatoria al acceder. | C para judge y gestores 02B. | Middleware/gate y revocación. | Onboarding y recuperación. | Toma de cuenta privilegiada. | IAM, account security, UAT. | `PROPOSAL_NEEDED` |
| P2B-DEC-019 | Notificaciones | Dispatcher/colas existen; eventos no. | Eventos, audiencias, canales y reminders. | Email mínimo. | Email + dashboard. | B + recordatorios. | B y recordatorio configurable. | Mailables/jobs/ledger/schedule. | Soporte de fallos y vencimientos. | Duplicados, spam o fuga. | Mail, jobs, preferences, ops. | `PROPOSAL_NEEDED` |
| P2B-DEC-020 | Retención | No hay plazo para scores, comentarios, conflictos o audit. | Plazo por entidad y backups. | Un plazo legal fijo. | Matriz por entidad. | Indefinida. | B con aprobación legal. | Purga/anonimización futura y backups. | Procedimiento de conservación/baja. | Incumplimiento o pérdida de evidencia. | Data lifecycle, audit, ops. | `PROPOSAL_NEEDED` |
| P2B-DEC-021 | Empate técnico | No existe regla; ganador está fuera. | Comparación técnica exacta. | Igualdad tras redondeo. | Tolerancia decimal. | Señal manual. | A después de P2B-DEC-010. | Función determinista y evento. | Sólo alerta a operación. | Convertir señal en ganador. | Aggregates, events, tests. | `PROPOSAL_NEEDED` |

## 18. Plan futuro por milestones — no autorizado

### M1. Roles, permisos, gate y seguridad base — `COMPLETE / GO LOCAL`

- Resultado: `judge` existe sin acceso participante, reviewer o admin; sin permiso, correo verificado o flag obtiene 403/404/redirect seguro según contrato.
- Dependencias: P2B-DEC-001 y P2B-DEC-018.
- Archivos probables: seeder/migración idempotente de permisos, middleware, redirects, layout/menú, Policies, tests.
- Migración prevista: sólo permisos/rol aditivos; sin asignaciones.
- Pruebas: login por rol, sin rol, roles combinados prohibidos, rutas/IDOR, correo verificado, flag y ausencia de enforcement 2FA para juez; suspensión/recovery se difieren a M2.
- Riesgo: bloqueo de cuentas actuales o escalamiento por combinación.
- Rollback: feature flag 02B apagado; permisos nuevos sin borrar roles existentes.
- Evidencia: 13/13 migraciones; forward/rollback/forward; 6 pruebas/92 aserciones dirigidas; suite completa 115/1,141; build, gates y QA Firefox responsive verdes.
- Terminado: matriz de rutas verde y juez no puede abrir ninguna superficie participante/admisibilidad/admin. M1 no creó cuentas, perfiles ni datos 02B.

### M2. Perfil y alta directa — `COMPLETE / GO LOCAL`

- Resultado: `admin` crea directamente una cuenta `pending_setup`; el juez establece contraseña propia, verifica correo y sólo entonces queda `active`.
- Dependencias: P2B-DEC-002/005/018.
- Archivos: modelo/enum, Actions, Requests, Policy, middleware, notificaciones y vistas `/panel/jueces`/`/juez/estado`.
- Migración: `judge_profiles` aditiva con ULID, FKs/checks y permisos M2; sin `judge_invitations`.
- Pruebas vigentes: 10 pruebas/175 aserciones M2; M1+M2 16/267; suite 125/1,316.
- Riesgo: account takeover/enumeración.
- Rollback: apagar alta/activación de jueces; conservar perfiles/evidencia.
- Terminado: ciclo admin-create→password/verify→active, suspensión/reactivación, recovery y revocación demostrados con datos sintéticos; 2FA permanece opcional. Evidencia: `docs/19-phase-02b-m2-implementation-report-2026-08-18.md`.

### M3. Rúbrica versionada

- Objetivo: crear, validar, activar y fijar una versión inmutable aprobada.
- Dependencias: P2B-DEC-009/010/011 ya aprobadas.
- Archivos: rubric models/enums/actions/requests/policies/admin views/calculator contract.
- Migraciones: rúbricas/criterios aditivos.
- Pruebas: rangos, steps, suma, duplicados, activación, inmutabilidad y sustitución.
- Riesgo: cambio retroactivo o fórmula ambigua.
- Rollback: flag off; nunca borrar versión ya referenciada.
- Terminado: plantilla aprobada se representa sin pérdida y una activa no puede mutarse.

### M4. Asignaciones y conflictos — `READY AFTER M3 — REQUIRES SEPARATE AUTHORIZATION`

- Objetivo futuro: asignar sólo propuestas admitidas a los cuatro principales y resolver/reemplazar conflictos mediante el quinto sustituto sin duplicidad.
- Dependencias: P2B-DEC-003/004/005/014/015/016.
- Puerta: M3 debe quedar verde y el propietario debe autorizar un prompt M4 separado; `P2B-BLOCK-001` ya no bloquea.
- Archivos: assignment/conflict domain, Actions, Policies, Requests, admin UX.
- Migraciones: assignments/conflicts/eventos.
- Pruebas: no admitida, duplicado, capacidad, plazo, carrera, conflicto, cancelación/reemplazo.
- Riesgo: cobertura inconsistente.
- Rollback: pausar activación; conservar filas y anular con razón.
- Terminado: cuatro asignaciones principales válidas por propuesta, sustituto sin carga inicial, máximo diez reemplazos activos, reemplazo viable y ninguna propuesta cambia de estado por inferencia.

### M5. Vista ciega y anexos autorizados

- Objetivo: generar automáticamente un paquete estructural versionado y servir sólo proyección/anexos allowlist.
- Dependencias: P2B-DEC-006/007/008.
- Archivos: package models/services/DTOs/Policies/download controller/admin+judge views.
- Migraciones: packages/package files/versiones.
- Pruebas: payload sin canarios PII, archivos cruzados, nombres/EXIF, links, residencia/notas/aclaraciones.
- Riesgo: autoidentificación semántica.
- Rollback: revocar paquete/asignaciones; original intacto.
- Terminado: proyección estructural automática versionada, campos prohibidos ausentes y riesgo semántico aceptado documentado; no se afirma revisión humana.

### M6. Borrador y cálculo servidor

- Objetivo: guardar borrador concurrente y recalcular criterios sin confiar en cliente.
- Dependencias: P2B-DEC-010/011/012/013.
- Archivos: evaluation models/calculator/Actions/Requests/Policies/views/JS progresivo.
- Migraciones: evaluations/revisions/scores.
- Pruebas: rangos/step/decimal, total hostil, incompleto, dos pestañas, fecha, asignación ajena.
- Riesgo: precisión y pérdida de borrador.
- Rollback: flag de captura off; conservar borradores.
- Terminado: cálculo reproducible y ningún valor derivado cliente persiste como autoridad.

### M7. Confirmación, envío y reapertura

- Objetivo: envío idempotente/inmutable y, si se aprueba, reapertura versionada.
- Dependencias: P2B-DEC-013/017/018.
- Archivos: submit/reopen/void Actions, permissions, confirmation UX, events.
- Migraciones: campos/índices append-only si M6 no los incluye.
- Pruebas: doble POST/proceso, vencimiento, conflicto, password/2FA, inmutabilidad, reopen/void negativos.
- Riesgo: reescritura de evidencia.
- Rollback: cerrar nuevas mutaciones; conservar versiones.
- Terminado: envío previo nunca cambia y reapertura produce revisión nueva.

### M8. Notificaciones y auditoría

- Objetivo: eventos redactados y entregas idempotentes/reintentables.
- Dependencias: P2B-DEC-019/020 aprobadas; recordatorios de participantes se tratan como subalcance separado dentro de este milestone, nunca en M1.
- Archivos: Mailables/Notifications/jobs/listeners/templates/audit metadata/schedule.
- Migraciones: ledger de delivery si se aprueba.
- Pruebas: after-commit, duplicados, retries/failure, sin PII, schedule y failed_jobs.
- Riesgo: spam/fuga en correo/log.
- Rollback: desactivar delivery; eventos de negocio permanecen.
- Terminado: cada evento crítico tiene una entrega como máximo por plantilla/destino y fallo observable.

### M9. QA automatizada y UAT por rol

- Objetivo: cubrir matrices positiva/negativa, accesibilidad, concurrencia y móvil con datos sintéticos.
- Dependencias: M1–M8 y todas las decisiones.
- Archivos: Feature/Unit/browser tests, fixtures/factories sintéticas, QA docs.
- Migraciones: ninguna nueva salvo defecto aprobado.
- Pruebas: visitor/participant/reviewer/admin/judge/sin rol; asignado/no asignado/conflicto/vencido/enviado/suspendido; 1440/768/360, teclado, foco, zoom/reflow, consola.
- Riesgo: falsa confianza si sólo se prueba UI.
- Rollback: no aplica a datos; defectos vuelven al milestone dueño.
- Terminado: suites/gates y UAT firmada sin hallazgos abiertos P0/P1.

### M10. Release candidate local

- Objetivo: reconstruir base testing protegida, validar migración desde baseline sintético y cerrar RC local, no producción.
- Dependencias: M9, decisiones/ADR/dependencias y rollback revisados.
- Archivos: ExecPlan/runbooks/status/handoff; código sólo por defectos reproducibles aprobados.
- Migraciones: todas aditivas probadas desde cero y upgrade; nunca datos reales.
- Pruebas: suite, Pint, Composer/Yarn audit, build, JSON, rutas, schedule, diff/secrets/PII.
- Riesgo: drift de runtime/flags/worker.
- Rollback: feature flag 02B off y código previo compatible con esquema aditivo.
- Terminado: `GO` local explícito; producción sigue en tarea separada autorizada.

## 19. Contradicciones y riesgos residuales

- `R60` quedó mitigado en local/test por M1: roles exactos, estado seguro cero/multirol y permiso/flag exclusivo. Debe preservarse en todos los milestones posteriores.
- Las 21 decisiones fueron respondidas el 2026-08-18; las alternativas históricas se conservan, pero ya no gobiernan el contrato vigente.
- El modelo preliminar no resolvía inmutabilidad tras reapertura; se recomienda revisión append-only.
- El snapshot actual contiene identidad/metadatos y no puede servirse directamente a jueces.
- `admitted` es señal de elegibilidad, no transición automática de propuesta ni asignación.
- 2FA funciona y el propietario decidió que sea opcional; este riesgo se acepta para el diseño, pero no elimina suspensión/revocación y recovery auditado.
- `P2B-BLOCK-001` está `RESOLVED BY OWNER`: cuatro principales sin límite fijo y quinto sustituto exclusivo con capacidad diez. M4 sigue no implementado, pero ya no requiere una decisión de capacidad/cobertura.
- Si existen más de diez conflictos/reasignaciones simultáneas, la undécima falla cerrada y requiere una nueva decisión operativa; este riesgo residual no autoriza sobreasignación silenciosa.
- La ceguera simple no elimina identidad contenida en texto/anexos; el propietario aceptó expresamente ese riesgo. La aplicación no debe prometer anonimización total.
- Permitir que `admin` cambie puntajes en nombre del juez exige revisión append-only y actor real visible en auditoría para no falsificar autoría.
- La detección de empate permanece técnica y no puede convertirse en desempate, ganador o resultado.
- Producción contiene más de 50 propuestas según propietario; cualquier futura migración debe probar upgrade aditivo y permanecer sin backfill inferido.

## 20. Respuesta consolidada del propietario — 2026-08-18

Las 21 respuestas quedaron incorporadas en la matriz vigente de la sección 17. Resumen contractual:

```text
1. Roles exclusivos, sin combinaciones.
2. Alta directa por admin; sin invitaciones de juez.
3. Cuatro jueces por propuesta en las cuatro categorías; los cuatro disponibles evalúan todas.
4. Asignación manual.
5. Cuatro jueces principales sin límite fijo; quinto juez exclusivamente sustituto con máximo diez reasignaciones activas; sin especialidad.
6. Ceguera simple estructural.
7. Todos los campos sustantivos y anexos evaluables; exclusión absoluta de PII estructurada, residencia, notas, aclaraciones e historial.
8. Anonimización automática estructural; riesgo semántico aceptado.
9. Rúbrica global: Pertinencia 20, Claridad 20, Viabilidad 25, Impacto 25, Coherencia 10.
10. Escala 0–10/paso 0.5; total ponderado 0–100; precisión 4/2; HALF_UP.
11. Comentario general obligatorio 100–2,000; por criterio opcional hasta 1,000.
12. Media aritmética de cuatro evaluaciones válidas con igual peso.
13. Sin consolidación si falta una evaluación; sin excepción.
14. Catálogo cerrado mínimo con “otro conflicto” y explicación obligatoria.
15. Admin resuelve y reasigna a otro juez; múltiples asignaciones independientes por propuesta.
16. Cierre global 2026-08-27 23:59:59 America/Hermosillo.
17. Admin reabre hasta 2026-08-27 20:00; razón 20–1,000, password confirm y revisión nueva; juez/admin editan hasta 23:59:59.
18. 2FA opcional; recuperación por admin.
19. Email mínimo de juez; recordatorios participante 2026-08-20/22 09:00 Hermosillo, incluida cuenta con enviada+borrador.
20. Retención uniforme 24 meses desde cierre administrativo de evaluación.
21. Empate por igualdad del consolidado redondeado a dos decimales; resolución fuera de 02B.
```

Corrección posterior `OWNER_APPROVED` del 2026-08-18: la respuesta 5 anterior queda sustituida por cuatro jueces principales sin límite fijo y un quinto juez exclusivamente sustituto con capacidad máxima de diez reasignaciones activas. `P2B-BLOCK-001` queda `RESOLVED`; M4 sigue sin implementar y requiere autorización posterior propia.

## 21. Siguiente prompt recomendado — implementar únicamente M3

```text
Trabaja exclusivamente en el repositorio local `/home/ccortesg/workspace/flowerflow`.

Lee completamente antes de modificar: `AGENTS.md`, `.agent/PLANS.md`, `.agent/execplans/flowerflow-phase-02b-evaluation-design.md`, `.agent/execplans/flowerflow-phase-02b-m1-judge-rbac.md`, `.agent/execplans/flowerflow-phase-02b-m2-judge-profile-onboarding.md`, `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`, `docs/19-phase-02b-m2-implementation-report-2026-08-18.md`, `docs/16-project-status-by-module-and-role-2026-08-17.md`, `docs/product-spec.md`, `docs/01-functional-scope.md`, `docs/02-architecture.md`, `docs/03-data-model.md`, `docs/04-security-privacy.md`, `docs/05-ux-ui.md`, `docs/06-roadmap-backlog.md`, `docs/08-testing-qa.md`, `docs/09-risk-register.md`, `docs/10-open-questions.md`, `docs/11-operations-handoff.md`, `docs/requirements-traceability.md` y ADR 0001/0003/0004/0005/0006/0007/0008.

Objetivo: implementar exclusivamente el Milestone 3 de Fase 02B: rúbrica global versionada, validación de su contrato, ciclo administrativo borrador→activa→sustituida, inmutabilidad y trazabilidad. Conserva sin regresión M1 y M2.

No implementes asignaciones, paquetes ciegos, conflictos, evaluaciones, borradores de evaluación, captura de puntajes, consolidación, reapertura, notificaciones de evaluación, retención/purga, ganadores o resultados. No crees jueces ni cambies perfiles/capacidad. No implementes todavía un calculador de evaluaciones: M3 sólo persiste y valida el contrato que M6 consumirá; ningún total proveniente del navegador debe aceptarse ahora o después.

Contrato `OWNER_APPROVED` obligatorio:
1. La rúbrica es global para las cuatro categorías.
2. Criterios exactos y ordenados: `pertinence` / Pertinencia / 20 %, `clarity` / Claridad / 20 %, `feasibility` / Viabilidad / 25 %, `impact` / Impacto / 25 % y `coherence` / Coherencia / 10 %.
3. Cada criterio usa escala 0–10 y paso 0.5.
4. El total futuro será 0–100, calculado sólo en servidor, con cuatro decimales internos, dos visibles y redondeo `HALF_UP`.
5. Comentario general futuro obligatorio de 100–2,000 caracteres; comentarios por criterio opcionales hasta 1,000.
6. Cada evaluación futura debe conservar la versión exacta de rúbrica; una versión activa o referenciada nunca se modifica ni se borra.
7. Activar una versión nueva sustituye la anterior sin reescribirla. Debe existir como máximo una versión activa global para la competencia.

M1 y M2 están `GO LOCAL/TEST`: roles exclusivos, permiso/shell juez, flag, `judge_profiles`, función `primary|substitute`, capacidad derivada, alta directa, credencial propia, activación, suspensión/reactivación, sesiones y recovery 2FA son invariantes. 2FA sigue opcional.

Registra `P2B-BLOCK-001=RESOLVED BY OWNER 2026-08-18`: los cuatro jueces principales evaluarán todas las propuestas elegibles sin límite fijo; un quinto juez será exclusivamente sustituto, no recibirá asignaciones iniciales y tendrá máximo diez reasignaciones activas. Esta decisión ya está reflejada en el perfil M2. No la modifiques en M3 y no crees jueces ni asignaciones. M4 deja de estar bloqueado por decisión, pero no está autorizado por este prompt y sólo puede proponerse después de M3 verde.

Antes de modificar:
1. confirma `pwd`, Git toplevel, rama, SHA local/remoto, ancestro común, `git status --short`, `git diff` y archivos preexistentes;
2. preserva todo cambio preexistente; no hagas stage, commit, push, reset, clean ni checkout destructivo;
3. crea `.agent/execplans/flowerflow-phase-02b-m3-versioned-rubric.md` con baseline, alcance, modelo, invariantes, pasos, pruebas, resultados, riesgos y rollback;
4. usa sólo MySQL local `flowerflow_testing`, usuario `flowerflow_testing_user`, host loopback y datos sintéticos;
5. antes de migrar/sembrar/probar esquema demuestra sin secretos `APP_ENV=testing`, driver MySQL, host loopback, base/usuario exactos y `SELECT DATABASE()=flowerflow_testing`; detente si algo difiere;
6. no accedas a producción, URL pública, AWS, EC2, SSH/SSM, servicios externos, MySQL/logs/datos productivos.

Implementación autorizada M3:
- crea migraciones aditivas y reversibles para una entidad de versión de rúbrica ligada a la competencia y sus criterios ordenados; usa ULID público, versión positiva única por competencia, estado respaldado por enum (`draft`, `active`, `superseded`), título interno, precisión/escala/paso/límites de comentarios, `activated_at`, `activated_by_user_id`, `superseded_at`, `superseded_by_user_id` y timestamps;
- los criterios deben tener código estable, etiqueta española, descripción aprobada, peso, mínimo, máximo, paso y orden; protege con FKs, índices, unicidades y checks MySQL compatibles, además de validación server-side;
- no inventes descripciones extensas para los criterios. Si no existe texto aprobado más allá del nombre, persiste una descripción nula y muéstralo como `POR_CONFIRMAR`, sin bloquear pesos/escala;
- exige exactamente cinco códigos, orden y pesos aprobados; peso total exactamente 100.0000, escala 0–10, paso 0.5, precisión 4/2, `HALF_UP` y límites de comentarios 100/2,000/1,000. Rechaza faltantes, duplicados, extras, NaN, negativos o valores fuera de contrato;
- añade permisos separados `view evaluation rubrics` y `manage evaluation rubrics`, sólo para `admin`. No los concedas a participant/reviewer/judge; no concedas al admin `access judge workspace`;
- crea modelos guarded, enums, Actions/Services transaccionales, Form Requests y Policies para listar, ver, crear borrador exacto y activar. Toda activación usa locks, confirma contraseña administrativa, exige razón de 20–1,000 caracteres y registra auditoría redactada;
- una rúbrica `draft` puede corregirse sólo antes de activarse y siempre dentro del contrato aprobado. Al activar, se vuelve inmutable; la versión activa anterior pasa atómicamente a `superseded`. No permitas actualizar/reactivar/borrar una activa o sustituida;
- evita depender únicamente de un índice único con nullable para garantizar una activa. Usa transacción, lock por competencia y prueba de concurrencia; añade la mejor restricción de base compatible sin fingir una garantía que MySQL no provea;
- crea `/panel/rubricas` con listado, alta de versión, detalle y activación para admin, paginación/estados accesibles y componentes actuales. No expongas esta gestión al shell juez ni muestres propuestas/evaluaciones;
- incorpora de forma idempotente en local/test la versión 1 con los cinco criterios aprobados, sin activarla por migración y sin reescribir una fila divergente. Si ya existe y difiere, falla cerrado y documenta. La activación debe ser una acción administrativa explícita;
- no agregues seeder productivo que cree evaluaciones/asignaciones. No modifiques propuestas, folios, snapshots, aceptaciones, `judge_profiles`, sesiones ni auditoría histórica;
- registra auditoría de creación/edición de draft/activación/sustitución con actor, IDs técnicos, versión, estado y razón; nunca copies tokens, contraseñas, PII o contenido de propuestas;
- actualiza ADR-0008, diagnóstico, producto, alcance, arquitectura, datos, seguridad, UX, roadmap, QA, riesgos, preguntas, handoff, trazabilidad y ExecPlans. M4 debe seguir `NOT IMPLEMENTED / NOT AUTHORIZED`, sin volver a marcar `P2B-BLOCK-001` como abierto.

Pruebas mínimas:
- migración/rollback/forward preserva usuarios, roles, jueces, perfiles y datos sintéticos preexistentes; no crea asignaciones/evaluaciones ni cambia perfiles;
- seeder/provisionador repetido produce una sola versión 1 draft exacta, cero cuentas y cero datos de evaluación; divergencia falla sin sobrescribir;
- sólo admin puede listar/ver/crear/editar draft/activar; demás roles, sin rol y multirol reciben 403/404;
- validación rechaza códigos/pesos/orden/escala/paso/precisión/redondeo/límites inválidos y mass assignment de estado, versión, actor o timestamps;
- activar requiere contraseña, razón y contrato completo; deja exactamente una activa, sustituye la anterior atómicamente y conserva ambas;
- activa/sustituida son inmutables y no eliminables; ULID alterado e IDOR no revelan existencia;
- dos activaciones concurrentes no dejan dos versiones activas;
- M1/M2 completos continúan verdes: aislamiento, alta, función primary/substitute, capacidad null/10, estados, sesiones y 2FA opcional sin regresión;
- `/juez` continúa vacío y sin mostrar la rúbrica en M3; su consumo pertenece a milestones posteriores.

Validación final sólo después del guard:
- forward/rollback/forward M3 en `flowerflow_testing` con usuarios/perfiles/rúbrica sintéticos preexistentes;
- pruebas dirigidas M3, M2, M1 y `php artisan test` completo;
- `vendor/bin/pint --test`;
- Composer validate/platform/audit y Yarn audit, dejando visible el advisory bajo de Quill si continúa;
- `scripts/build_frontend_production.sh`, JSON, rutas, schedule, migrate status, `git diff --check`, enlaces Markdown y scan de secretos/PII;
- UAT Firefox local de `/panel/rubricas` en escritorio/tableta/móvil, teclado, foco, reflow, consola, 403/404 e inmutabilidad, con correo array y datos sintéticos.

No hagas stage, commit, push ni despliegue. No toques producción/AWS/Apache/PHP-FPM/Supervisor/MySQL productivo/DNS/TLS/SMTP real.

Entrega:
1. resumen y `GO/NO-GO` local M3;
2. baseline y guard;
3. modelo/invariantes/versiones;
4. archivos y comandos/resultados;
5. matriz efectiva por rol;
6. evidencia de validación, activación, sustitución, inmutabilidad y concurrencia;
7. migración/compatibilidad/rollback;
8. riesgos/auditoría y evidencia de `P2B-BLOCK-001=RESOLVED`, incluido el límite residual de diez sustituciones activas;
9. documentación/trazabilidad;
10. siguiente prompt exacto limitado a M4 —asignaciones y conflictos— sólo si M3 queda completamente verde; generarlo no autoriza ejecutarlo ni permite mezclar M5+.
```

## 21A. Prompt histórico ejecutado — implementar únicamente M2

```text
Trabaja exclusivamente en el repositorio local `/home/ccortesg/workspace/flowerflow`.

Lee completamente antes de modificar: `AGENTS.md`, `.agent/PLANS.md`, `.agent/execplans/flowerflow-phase-02b-evaluation-design.md`, `.agent/execplans/flowerflow-phase-02b-m1-judge-rbac.md`, `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`, `docs/16-project-status-by-module-and-role-2026-08-17.md`, `docs/product-spec.md`, `docs/01-functional-scope.md`, `docs/02-architecture.md`, `docs/03-data-model.md`, `docs/04-security-privacy.md`, `docs/05-ux-ui.md`, `docs/06-roadmap-backlog.md`, `docs/08-testing-qa.md`, `docs/09-risk-register.md`, `docs/10-open-questions.md`, `docs/11-operations-handoff.md`, `docs/requirements-traceability.md` y ADR 0001/0003/0004/0005/0006/0007/0008.

Objetivo: implementar exclusivamente el Milestone 2 de Fase 02B: perfil operativo de juez, alta directa controlada por `admin`, establecimiento seguro de credencial, activación por prerrequisitos, suspensión/reactivación, revocación de sesiones y recuperación administrativa de 2FA. Conserva sin regresión el aislamiento M1.

No implementes invitaciones, asignaciones, paquetes ciegos, conflictos, rúbricas, evaluaciones, puntajes, consolidación, reapertura de evaluaciones, recordatorios masivos, retención/purga, ganadores o resultados. La única notificación autorizada en M2 es la transaccional indispensable para alta/configuración de acceso, suspensión/reactivación y recuperación de 2FA del juez.

Contratos `OWNER_APPROVED` aplicables a M2:
1. `participant`, `reviewer`, `judge` y `admin` son roles estrictamente excluyentes.
2. El alta de juez es directa por `admin`; no existe invitación de juez ni tabla/token equivalente.
3. Cada perfil fija `max_active_assignments=8`; no maneja especialidad ni categorías.
4. El correo debe verificarse antes de acceder a `/juez`.
5. 2FA es opcional y su ausencia nunca bloquea al juez.
6. Sólo `admin` puede recuperar 2FA, con permiso separado, razón obligatoria de 20–1,000 caracteres, confirmación de contraseña, auditoría, notificación y revocación de sesiones.
7. Suspensión bloquea el shell y toda mutación futura de juez; reactivación sólo procede si correo y contraseña propia están establecidos.
8. M1 ya está `GO LOCAL/TEST`; su permiso exclusivo, roles exactos, rutas fail-closed, flag y matrices negativas son invariantes.

Preserva `P2B-BLOCK-001`: con más de 50 propuestas, cuatro jueces × ocho proyectos sólo aportan 32 cupos frente a al menos 204 requeridos, sin quinto juez sustituto. Este bloqueo no impide M2, pero prohíbe iniciar M4, crear asignaciones, elevar capacidades o cambiar el número de jueces por inferencia. M2 no debe auto-crear cuatro jueces ni imponer un máximo global de cuentas juez.

Antes de modificar:
1. confirma `pwd`, Git toplevel, rama, SHA local, SHA remoto, ancestro común, `git status --short`, `git diff` y archivos preexistentes modificados/no rastreados;
2. preserva todos los cambios preexistentes; no hagas stage, commit, push, reset, clean ni checkout destructivo;
3. crea `.agent/execplans/flowerflow-phase-02b-m2-judge-profile-onboarding.md` con baseline, alcance, decisiones, modelo, pasos, pruebas, resultados, riesgos y rollback;
4. usa exclusivamente MySQL local `flowerflow_testing`, usuario `flowerflow_testing_user`, host loopback y datos sintéticos;
5. antes de cualquier migración, seeder o prueba que pueda recrear esquema, demuestra sin exponer secretos: `APP_ENV=testing`, `DB_CONNECTION=mysql`, host `127.0.0.1` o `localhost`, base configurada `flowerflow_testing`, usuario configurado `flowerflow_testing_user` y `SELECT DATABASE()=flowerflow_testing`; si difiere cualquier valor, detente;
6. no accedas a producción, URL pública, AWS, EC2, SSH/SSM, servicios externos, MySQL productivo, logs productivos ni datos reales.

Implementación autorizada M2:
- crea una migración aditiva y reversible para `judge_profiles`, uno-a-uno con `users`, usando el patrón ULID público existente. Incluye como mínimo: `public_id`, `user_id` único, `status` respaldado por enum (`pending_setup`, `active`, `suspended`), `max_active_assignments` con valor fijo ocho, `created_by_user_id`, `password_initialized_at`, `activated_at`, `suspended_at`, `suspended_by_user_id`, `suspension_reason`, `reactivated_at`, `reactivated_by_user_id` y timestamps. No agregues especialidades, categorías, invitaciones ni datos de evaluación;
- protege integridad con FKs/índices/checks compatibles con MySQL y validación server-side. No modifiques ni backfillees usuarios existentes y no crees perfiles por seeder o migración;
- añade permisos administrativos mínimos y separados para ver/administrar jueces y recuperar 2FA. Sólo `admin` los recibe; `reviewer`, `participant` y `judge` no. Conserva `access judge workspace` como permiso exclusivo únicamente de `judge`;
- crea Actions/Services transaccionales y Form Requests/Policies para alta, activación derivada, suspensión, reactivación y recovery. Reutiliza `AssignExclusiveBusinessRole`; cualquier correo existente, cuenta con otro rol, perfil duplicado o carrera debe fallar sin mutación parcial ni sustitución de rol;
- el formulario admin de alta solicita sólo nombre y correo. Normaliza el correo, crea `users` + rol `judge` + perfil `pending_setup` en una transacción y asigna una contraseña aleatoria criptográficamente fuerte que nunca se muestra, registra ni envía;
- después del commit, envía mediante el dispatcher resiliente una plantilla HTML+texto de marca para establecer contraseña usando el broker/token seguro ya existente. No crees token de invitación ni incluyas contraseña, secretos TOTP, PII adicional o datos de propuestas. El fallo de correo no revierte la cuenta y debe quedar observable/reintentable conforme al patrón actual;
- integra el flujo de reset para fijar `password_initialized_at` sólo la primera vez que una cuenta juez establece su propia contraseña. No alteres el contrato de participantes. El correo se verifica mediante el flujo firmado vigente y nunca se marca verificado por inferencia administrativa;
- deriva `active` únicamente cuando existen `password_initialized_at` y `email_verified_at`, salvo que el perfil esté suspendido. La transición debe ser idempotente, transaccional y auditada; `pending_setup` no puede abrir `/juez` aunque el flag esté encendido;
- añade middleware/gate `judge.active` al shell M1. `pending_setup` y `suspended` reciben una pantalla segura, accesible y sin datos de negocio; no caen en participant/panel y no revelan propuestas;
- crea en `/panel/jueces` listado, alta y detalle mínimos para `admin`, con paginación, estados vacíos/error/éxito, acciones autorizadas y UI Materialize/Pixinvent. No agregues dashboard de asignaciones, filtros por especialidad, importación masiva ni exportación;
- suspensión y reactivación requieren confirmación de contraseña y razón de 20–1,000 caracteres. Suspender conserva usuario/rol/perfil, rota `remember_token`, revoca sesiones persistidas de la cuenta y bloquea inmediatamente `/juez`. Reactivar conserva la historia y sólo deja `active` si los prerrequisitos están completos; de lo contrario vuelve a `pending_setup`;
- recovery 2FA requiere permiso separado, confirmación de contraseña y razón de 20–1,000 caracteres. Elimina de forma segura secreto, recovery codes y confirmación TOTP del juez, rota `remember_token`, revoca sus sesiones, conserva actor/fecha/razón en auditoría y notifica al correo verificado. Nunca muestra secretos o recovery codes al admin;
- registra auditoría redactada para alta, correo de configuración solicitado/fallido, activación, suspensión, reactivación y recovery 2FA. Conserva actor real, sujeto por ID técnico, transición y timestamps UTC; no copies contraseñas, tokens, TOTP ni datos de propuestas;
- actualiza documentación, ADR-0008, trazabilidad, riesgos y registros vivos con el comportamiento realmente implementado. Mantén M3–M10 como no implementados y M4 como bloqueado.

Pruebas mínimas obligatorias:
- migración y seeder idempotentes preservan usuarios, roles/permisos y datos existentes; no crean jueces, perfiles, invitaciones ni asignaciones automáticamente;
- `admin` puede crear una cuenta sintética nueva y sólo obtiene un perfil `pending_setup` con rol exclusivo `judge` y capacidad ocho; ningún otro rol puede listar, ver, crear, suspender, reactivar o recuperar 2FA;
- correo existente participant/reviewer/admin/judge, duplicado de perfil, request repetido y carrera concurrente no sustituyen roles ni dejan filas parciales;
- contraseña/token nunca aparecen en respuesta, HTML, correo, audit metadata o logs; Mail fake/array prueba plantilla HTML+texto y fallo resiliente;
- establecer contraseña marca una sola vez `password_initialized_at`; verificar correo y completar ambos prerrequisitos activa de forma idempotente, en cualquier orden. Correo no verificado o contraseña no establecida mantiene `pending_setup`;
- judge activo conserva la matriz M1: sólo `/juez` con flag, nunca participant/panel/archivos/residencia/exportaciones. Pending, suspendido, sin rol y multirol fallan cerrados;
- suspensión revoca sesiones/remember token y bloquea accesos existentes/directos; reactivación exige razón, password confirmation y prerrequisitos;
- 2FA sigue opcional. Recovery sólo admin, exige razón/password confirmation, revoca sesiones, limpia material TOTP, audita/notifica y no concede acceso adicional;
- URLs directas, ULID alterado e IDOR devuelven 403/404 sin revelar existencia; mass assignment no permite cambiar rol, status, capacidad, actores o timestamps;
- regresión positiva de visitante, participant, reviewer y admin, incluida la suite M1 completa.

Validación final, únicamente después del guard MySQL:
- migración/rollback/forward M2 sólo en `flowerflow_testing`, incluyendo upgrade con usuarios sintéticos preexistentes;
- pruebas dirigidas M2, pruebas M1 y `php artisan test` completo;
- `vendor/bin/pint --test`;
- `composer validate --strict --no-check-publish`, `composer check-platform-reqs --no-dev` y `composer audit --locked`;
- `corepack yarn audit --groups dependencies --level moderate`, manteniendo visible el advisory bajo conocido si continúa;
- `scripts/build_frontend_production.sh`;
- validación JSON de menús, `php artisan route:list --except-vendor`, `php artisan schedule:list`, `php artisan migrate:status --env=testing`, `git diff --check`, enlaces Markdown y revisión de secretos/PII;
- QA real local con correo fake/array para admin y estados `pending_setup`/`active`/`suspended` de juez en escritorio, tableta y móvil; teclado, foco, zoom/reflow, consola, 403/404 e invalidación de sesión.

No hagas stage, commit, push ni despliegue. No toques producción, AWS, Apache, PHP-FPM, Supervisor, MySQL productivo, DNS, TLS o SMTP real. Usa exclusivamente cuentas y correos sintéticos.

Entrega:
1. resumen y decisión `GO/NO-GO` local de M2;
2. baseline Git y guard MySQL sin secretos;
3. modelo/estados/invariantes y archivos modificados;
4. comandos y resultados reales;
5. matriz efectiva por rol y estado del perfil/flag;
6. evidencia de alta, credencial segura, activación, suspensión, revocación y recovery 2FA;
7. migración, compatibilidad con datos existentes y rollback;
8. defectos, riesgos, auditoría y `P2B-BLOCK-001` preservado;
9. documentación/trazabilidad actualizadas;
10. siguiente prompt limitado a M3 —rúbrica versionada— sólo si M2 queda completamente verde. No autorices M4 mientras `P2B-BLOCK-001` siga abierto.
```

## 21B. Prompt histórico ejecutado — implementar únicamente M1

```text
Trabaja exclusivamente en el repositorio local `/home/ccortesg/workspace/flowerflow`.

Lee completamente antes de modificar: `AGENTS.md`, `.agent/PLANS.md`, `.agent/execplans/flowerflow-phase-02b-evaluation-design.md`, `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`, `docs/16-project-status-by-module-and-role-2026-08-17.md`, `docs/product-spec.md`, `docs/01-functional-scope.md`, `docs/02-architecture.md`, `docs/03-data-model.md`, `docs/04-security-privacy.md`, `docs/05-ux-ui.md`, `docs/06-roadmap-backlog.md`, `docs/09-risk-register.md`, `docs/10-open-questions.md`, `docs/11-operations-handoff.md`, `docs/requirements-traceability.md` y ADR 0001/0003/0004/0005/0006/0007/0008.

Objetivo: implementar exclusivamente el Milestone 1 de Fase 02B: rol `judge`, permisos mínimos, exclusividad de roles, gates explícitos de rutas, redirección/shell seguros y pruebas de aislamiento. No implementes todavía alta directa de jueces, `judge_profiles`, invitaciones, asignaciones, paquetes ciegos, conflictos, rúbricas, evaluaciones, puntajes, consolidación, reapertura, notificaciones, retención, ganadores o resultados.

Decisiones `OWNER_APPROVED` del 2026-08-18 que debes conservar como contrato futuro, sin implementarlas fuera de M1:
1. Roles `participant`, `reviewer`, `judge` y `admin` estrictamente excluyentes; ninguna combinación válida.
2. Alta de juez directa por `admin`; no habrá invitaciones de juez.
3. Cuatro jueces por propuesta en las cuatro categorías; los cuatro jueces disponibles evaluarían todas.
4. Asignación manual.
5. Máximo ocho asignaciones activas por juez, sin especialidad.
6. Ceguera simple estructural.
7. Visibles todos los campos sustantivos y anexos evaluables; siempre ocultos PII estructurada, residencia, notas internas, aclaraciones e historial de admisibilidad.
8. Anonimización automática estructural; el propietario acepta el riesgo de identidad dentro de texto, imágenes, enlaces y anexos sin bloqueo.
9. Rúbrica global: Pertinencia 20 %, Claridad 20 %, Viabilidad 25 %, Impacto 25 % y Coherencia 10 %.
10. Escala 0–10, paso 0.5, total ponderado 0–100, cuatro decimales internos, dos visibles y `HALF_UP`.
11. Comentario general obligatorio de 100–2,000 caracteres; comentarios por criterio opcionales hasta 1,000.
12. Media aritmética de cuatro evaluaciones válidas con igual peso.
13. Bloquear consolidación si falta cualquier evaluación; sin excepción administrativa.
14. Conflictos: relación personal/familiar, relación profesional/económica, participación en la propuesta u otro conflicto; “otro” exige explicación.
15. `admin` resuelve y reasigna a otro juez; varios jueces pueden evaluar una propuesta mediante asignaciones independientes.
16. Cierre global `2026-08-27 23:59:59 America/Hermosillo`.
17. `admin` puede reabrir hasta `2026-08-27 20:00:00 America/Hermosillo`, con razón de 20–1,000 caracteres, confirmación de contraseña y revisión append-only; juez o admin pueden modificar la revisión nueva hasta las 23:59:59, conservando actor real en auditoría.
18. 2FA opcional; recuperación autorizada por `admin` con trazabilidad.
19. Email mínimo para alta, asignación, conflicto resuelto, reasignación, envío, reapertura y cierre. Recordatorios de participantes el 20 y 22 de agosto de 2026 a las 09:00 Hermosillo para cuentas verificadas sin propuesta o con borrador, incluida una cuenta con otra propuesta enviada.
20. Retención 02B uniforme de 24 meses desde el cierre administrativo del ciclo de evaluación.
21. Empate técnico por igualdad del consolidado redondeado a dos decimales; resolución y ganador fuera de 02B.

Registra y preserva `P2B-BLOCK-001`: con más de 50 propuestas, cuatro jueces × ocho proyectos aportan sólo 32 cupos de asignación, suficientes para ocho propuestas con cuatro evaluaciones; se requieren al menos 204 cupos y no existe un quinto juez de reemplazo ante conflicto. Este bloqueo impide M4/asignaciones, pero no M1. No cambies, “corrijas” ni implementes por inferencia cantidad, capacidad o reemplazos.

Antes de modificar:
1. confirma `pwd`, Git toplevel, rama, SHA local, SHA remoto, ancestro común, `git status --short`, `git diff` y archivos preexistentes modificados/no rastreados;
2. preserva todos los cambios preexistentes; no hagas stage, commit, push, reset, clean ni checkout destructivo;
3. crea `.agent/execplans/flowerflow-phase-02b-m1-judge-rbac.md` con baseline, alcance, decisiones, pasos, pruebas, resultados, riesgos y rollback;
4. usa exclusivamente MySQL local `flowerflow_testing`, usuario `flowerflow_testing_user`, host loopback y datos sintéticos;
5. antes de cualquier migración, seeder o prueba que pueda recrear esquema, demuestra sin exponer secretos: `APP_ENV=testing`, `DB_CONNECTION=mysql`, host `127.0.0.1` o `localhost`, base configurada `flowerflow_testing`, usuario configurado `flowerflow_testing_user` y `SELECT DATABASE()=flowerflow_testing`; si difiere cualquier valor, detente;
6. no accedas a producción, URL pública, AWS, EC2, SSH/SSM, servicios externos, MySQL productivo, logs productivos ni datos reales.

Implementación autorizada M1:
- añadir de forma aditiva e idempotente el rol `judge` y únicamente el permiso base mínimo necesario para acceder al futuro shell de juez; no concederle permisos de participante, `view submissions`, panel, admisibilidad, residencia, exportaciones, configuración, usuarios, rúbricas, asignaciones o evaluaciones todavía;
- impedir que `admin`, `reviewer` o `participant` obtengan el permiso exclusivo del shell juez por una asignación global de “todos los permisos”;
- aplicar exclusividad fail-closed: una cuenta con cero roles o más de un rol no cae por descarte en ningún shell de negocio y recibe una pantalla segura sin datos; toda acción futura de asignación de rol debe rechazar combinaciones;
- proteger explícitamente rutas de participante con rol/capacidad `participant`, conservando además `auth`, `verified`, ownership, flags, fechas y Policies actuales;
- proteger `/panel` con las capacidades actuales de `reviewer`/`admin` y mantener sus permisos existentes;
- crear el gate y redirección segura de `judge` sin tratarlo como participante ni como panel administrativo. Si se crea una superficie mínima `/juez`, debe ser sólo un estado vacío/cerrado accesible y de marca, sin propuestas, PII ni controles de evaluación;
- añadir `FLOWERFLOW_EVALUATION_ENABLED=false` como flag fail-closed. Con flag apagado, el juez sigue sin caer al participante; con flag encendido en test sólo puede abrir la superficie mínima autorizada;
- no exigir 2FA al juez: la decisión aprobada es opcional. M1 no implementa recovery, suspensión o perfil activo, pero no debe bloquear su incorporación posterior;
- mantener rutas, contratos y UX actuales de visitante, participant, reviewer y admin salvo el gate mínimo necesario para cerrar el acceso por descarte;
- actualizar documentación, ADR 0008, trazabilidad, riesgos y registro vivo con comportamiento realmente implementado; conservar M2–M10 como no implementados.

Pruebas mínimas obligatorias:
- matriz positiva/negativa para visitante, participant, reviewer, admin, judge, autenticado sin rol y cuenta sintética con roles múltiples creada sólo para probar fallo cerrado;
- participant conserva inicio, perfil, propuestas, archivos y admisibilidad propia según flags/Policies actuales;
- reviewer/admin conservan `/panel` y no acceden al shell juez como juez;
- judge no accede a `/inicio`, `/perfil`, `/propuestas`, admisibilidad participante, `/panel`, descargas privadas, residencia ni exportaciones;
- judge sólo accede a la superficie mínima cuando el flag está habilitado y su correo está verificado;
- sin rol y multirol no reciben navegación ni datos de participant/judge/panel;
- URLs directas e IDs alterados continúan devolviendo 403/404 sin filtrar existencia;
- el mecanismo idempotente de rol/permisos preserva `participant`, `reviewer`, `admin` y usuarios/datos existentes, y no crea jueces ni asignaciones automáticamente.

Validación final, únicamente después del guard MySQL:
- migración/rollback/forward del cambio M1 si existe migración, sólo en `flowerflow_testing`;
- pruebas dirigidas M1 y `php artisan test` completo;
- `vendor/bin/pint --test`;
- `composer validate --strict --no-check-publish`, `composer check-platform-reqs --no-dev` y `composer audit --locked`;
- `corepack yarn audit --groups dependencies --level moderate`, documentando sin ocultar el advisory bajo conocido si continúa;
- `scripts/build_frontend_production.sh`;
- validación JSON de menús, `php artisan route:list --except-vendor`, `php artisan schedule:list`, `php artisan migrate:status --env=testing`, `git diff --check` y revisión de secretos/PII;
- QA real de la superficie mínima y rechazos por rol en escritorio, tableta y móvil, teclado, foco, zoom/reflow y consola, sólo con cuentas sintéticas y correo fake/array.

No hagas stage, commit, push ni despliegue. No toques producción, AWS, Apache, PHP-FPM, Supervisor, MySQL productivo, DNS, TLS o SMTP real.

Entrega:
1. resumen y decisión `GO/NO-GO` local de M1;
2. baseline Git y guard MySQL sin secretos;
3. archivos modificados y justificación;
4. comandos ejecutados y resultados reales;
5. matriz efectiva de acceso por rol y estado del flag;
6. defectos/hallazgos, riesgos y `P2B-BLOCK-001` preservado;
7. migración/compatibilidad/rollback;
8. documentación y trazabilidad actualizadas;
9. siguiente prompt limitado a M2 —perfil y alta directa— sólo si M1 queda completamente verde; no autorices M4 mientras `P2B-BLOCK-001` siga abierto.
```

## 22. Validación documental y técnica vigente

| Gate | Resultado |
|---|---|
| Baseline Git | `pwd`/toplevel correctos; rama `codex/submission-deadline-extension`; local/remoto/merge-base inicial `e0fa0455e61afcb38593b62ae0d983f75a92b210`. M2 preservó el diff M1/documental preexistente hasta que el propietario autorizó expresamente commit/push acumulado; no se autorizó despliegue. |
| Guard MySQL | `APP_ENV=testing`, MySQL, loopback, `flowerflow_testing`, `flowerflow_testing_user` y `SELECT DATABASE()` exactos, sin exponer contraseña. |
| Migración | M2 pasó forward/rollback/forward preservando un usuario sintético; estado final 14/14. Seeder idempotente no creó cuentas/perfiles juez, invitaciones ni asignaciones. |
| Pruebas | M2 dirigido 10/175; M1+M2 16/267; suite completa 125/1,316, sin fallos. |
| Calidad/dependencias/build | Pint, Composer validate/platform/audit, dos JSON de menú y build Vite (98 iconos, 784 módulos, tres assets) verdes. Yarn no presenta moderados+ y mantiene visible el advisory bajo conocido de Quill. |
| QA real local | Firefox con admin y judge pending/active/suspended; alta, recovery, revocación/relogin, reactivación, flag, 1440/768/390, teclado/foco, reflow, CSS zoom 200 %, 403/404 y consola limpia. Tras la corrección se repitió alta principal en escritorio y sustituto en 390 px, con capacidades correctas, cero overflow y consola limpia. No se afirma zoom nativo. |
| Enlaces Markdown locales | 12 destinos locales comprobados en el repositorio; cero rotos. |
| Cobertura/contradicciones | 21 decisiones `OWNER_APPROVED`; 02B=20 % funcional/90 % preparación; M1/M2 locales separados de producción; M3 siguiente; `P2B-BLOCK-001` resuelto; M4–M10 no implementados/no autorizados; resultados fuera. |
| Secretos/PII | Revisión de altas del diff sin claves privadas/cloud, asignaciones de secretos ni dominios de correo personales; código/pruebas/UAT usaron datos sintéticos. |
| Prompt M3 | La sección 21 es la fuente canónica completa; `docs/16` la referencia y conserva un bloque operativo abreviado. No autoriza M4. |
| Limpieza/alcance | `migrate:fresh --seed` tras guard dejó cero usuarios, perfiles y sesiones sintéticos; 14/14 migraciones, cuatro categorías y tres tipos jurídicos activos. Esta entrega puede finalizar con el commit/push expresamente solicitado, pero sin despliegue o acceso externo. |
| Whitespace/Git | `git diff --check` verde; archivos preexistentes preservados. |

Los resultados técnicos completos, hallazgos y rollback M2 se conservan en `.agent/execplans/flowerflow-phase-02b-m2-judge-profile-onboarding.md` y `docs/19-phase-02b-m2-implementation-report-2026-08-18.md`; el ExecPlan M1 permanece como historia.

## 23. Regla de actualización

Las 21 filas vigentes están `OWNER_APPROVED` desde el 2026-08-18. Cualquier cambio posterior debe registrar fecha, texto exacto, decisión sustituida, consecuencias y actor, sin reescribir la historia. Una aprobación de diseño no equivale a implementación; cada milestone requiere prompt y ExecPlan propios. La respuesta expresa posterior del propietario resolvió `P2B-BLOCK-001` mediante cuatro principales sin límite fijo y un quinto sustituto exclusivo con capacidad diez; esta resolución no equivale a implementar ni autorizar M4.
