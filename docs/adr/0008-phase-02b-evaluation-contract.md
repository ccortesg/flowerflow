# ADR-0008: contrato de jueces y evaluación de Fase 02B

- **Estado:** Accepted — M1/M2 implemented locally; M3–M10 pending
- **Fecha:** 2026-08-18
- **Decisor:** propietario de Flower Flow
- **Alcance:** Fase 02B; no incluye ganadores ni resultados

## Contexto

El propietario respondió las 21 decisiones del paquete `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`. Prompts posteriores autorizaron M1 y M2. El código local contiene el rol técnico `judge`, permiso exclusivo, gates exactos, flag fail-closed, `judge_profiles`, función de asignación, capacidad derivada, alta directa, credencial propia, activación por prerrequisitos, suspensión/reactivación, revocación de sesiones y recovery 2FA administrativo. No existen asignaciones, paquetes ciegos, conflictos, rúbricas ni evaluaciones. Este ADR fija el contrato restante sin autorizar por sí mismo M3–M10 o producción.

Producción tiene más de 50 propuestas reales según `OWNER_CONFIRMED_DEPLOYED`. El SHA productivo y la evidencia técnica independiente permanecen `POR_CONFIRMAR` y no son objeto de esta decisión.

## Decisión

### Identidad y acceso

- `participant`, `reviewer`, `judge` y `admin` son roles estrictamente excluyentes; cero roles o más de uno fallan cerrados.
- Los jueces se crean directamente por `admin`; no habrá invitaciones de juez.
- El correo verificado es obligatorio para acceder al shell juez.
- 2FA es opcional. M2 implementa recuperación sólo por `admin` con permiso separado, razón, confirmación de contraseña, revocación de sesiones, auditoría y notificación sin revelar material TOTP.
- M1 impide que `judge`, cero roles o multirol caigan por descarte en el shell participante o en `/panel`; M2 añade el estado activo como prerrequisito y conserva el fallo cerrado para perfiles pending/suspended.
- El alta directa solicita nombre, correo y función `primary|substitute`; la capacidad se deriva exclusivamente en servidor. Asigna una contraseña aleatoria que no se muestra ni envía y utiliza el broker seguro para que el juez establezca la propia. El perfil sólo queda `active` cuando existen correo verificado y `password_initialized_at`, salvo suspensión.

### Asignación y conflicto

- La asignación es manual y sin especialidad. Cada propuesta elegible de cualquiera de las cuatro categorías requiere cuatro evaluaciones de los cuatro jueces principales.
- Los cuatro jueces principales evalúan todas las propuestas elegibles sin límite fijo. Un quinto juez es exclusivamente sustituto, no recibe asignaciones iniciales y admite máximo diez reasignaciones activas.
- El catálogo de conflictos contiene relación personal/familiar, relación profesional/económica, participación en la propuesta y otro conflicto con explicación obligatoria.
- `admin` resuelve y reasigna al juez sustituto. Una propuesta admite varias asignaciones independientes; la undécima sustitución activa falla cerrada.
- El cierre global es `2026-08-27 23:59:59 America/Hermosillo`.

### Ceguera y datos

- La modalidad es ceguera simple estructural.
- El juez asignado ve todos los campos sustantivos y anexos evaluables de la propuesta.
- Siempre se excluyen PII estructurada de contacto, comprobantes de residencia, notas internas, aclaraciones e historial de admisibilidad.
- La automatización sólo anonimiza campos estructurados, nombres de archivo expuestos y metadatos técnicos. No promete eliminar identidad escrita o incrustada en texto, imágenes, enlaces o anexos.
- El propietario acepta el riesgo residual de autoidentificación en contenido evaluable sin bloquear la propuesta.

### Rúbrica, cálculo y consolidación

- Rúbrica global: Pertinencia 20 %, Claridad 20 %, Viabilidad 25 %, Impacto 25 % y Coherencia 10 %.
- Cada criterio usa escala 0–10 y paso 0.5. El servidor calcula un total 0–100 con cuatro decimales internos, dos visibles y redondeo `HALF_UP`.
- Se requiere comentario general de 100–2,000 caracteres; cada comentario por criterio es opcional y admite hasta 1,000.
- La consolidación es la media aritmética con igual peso de cuatro evaluaciones válidas. Si falta una, no existe consolidado ni excepción administrativa.
- Un empate técnico es igualdad del consolidado redondeado a dos decimales. Resolver el empate o declarar ganador queda fuera de Fase 02B.

### Ciclo, reapertura y autoría

- Los estados previstos son `assigned`, `in_progress`, `conflict_declared`, `submitted`, `reopened` y `voided`.
- Un envío es inmutable. La reapertura crea una revisión append-only y conserva la anterior.
- `admin` puede ordenar una reapertura hasta `2026-08-27 20:00:00 America/Hermosillo`, con razón de 20–1,000 caracteres y confirmación de contraseña.
- La revisión reabierta puede enviarse o reenviarse hasta `2026-08-27 23:59:59 America/Hermosillo`.
- `admin` puede modificar puntajes en nombre del juez, pero la aplicación debe conservar al juez sujeto, al actor administrativo real, la razón, la revisión previa y la nueva revisión. Nunca se sobrescribe ni se atribuye falsamente la acción al juez.

### Notificaciones y retención

- Las notificaciones mínimas de juez cubren alta directa, asignación, conflicto resuelto, reasignación, envío, reapertura y cierre; son idempotentes y no incluyen PII sensible.
- Los participantes verificados sin propuesta o con al menos una propuesta en borrador reciben recordatorios el 20 y 22 de agosto de 2026 a las 09:00 `America/Hermosillo`. Una persona con otra propuesta enviada y una en borrador sí recibe; quien tiene todas enviadas no recibe.
- Evaluaciones, revisiones, puntajes, conflictos, asignaciones y auditoría 02B se retienen 24 meses desde `evaluation_cycle_closed_at`. El borrado y su compatibilidad con backups requieren un milestone operativo posterior.

## Consecuencias

- Las migraciones futuras serán aditivas y no modificarán folios, snapshots, aceptaciones jurídicas, estados o datos reales por inferencia.
- El total enviado por navegador se ignora y se recalcula en servidor.
- La UI no puede afirmar anonimización total.
- La combinación de edición administrativa, 2FA opcional y ceguera simple exige auditoría append-only, Policies, locks y pruebas negativas estrictas.
- M1/M2 implementaron rol, gates, perfil y ciclo operativo de cuenta sólo en local/test. M3–M10 requieren autorización separada.

## Decisión que resolvió el bloqueo operativo

`P2B-BLOCK-001 — RESOLVED BY OWNER 2026-08-18`: el contrato anterior de máximo ocho para todos no era compatible con al menos 204 asignaciones ni proporcionaba reemplazo. El propietario lo sustituyó por cuatro jueces principales sin límite fijo y un quinto juez exclusivamente sustituto con capacidad diez.

El perfil M2 registra `primary|substitute`; para `primary`, `max_active_assignments` es `NULL` y significa sin límite fijo, mientras que para `substitute` es exactamente `10`. Esto no crea asignaciones ni autoriza M4. M4 debe impedir carga inicial al sustituto, contar sólo sustituciones activas y fallar cerrado ante la undécima.

## Alternativas descartadas

- invitación firmada o alta híbrida de jueces;
- roles acumulables;
- asignación semiautomática o automática;
- 2FA obligatorio;
- certificación humana previa de anonimización;
- consolidación parcial o excepción administrativa ante evaluaciones faltantes;
- sobrescritura de una evaluación enviada;
- desempate o selección de ganador dentro de Fase 02B.

## Validación

El contrato completo y la matriz de evidencia/decisiones se mantienen en `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`. M1/M2 demostraron aislamiento, alta, función/capacidad, activación, suspensión, revocación y recovery sólo en local/test; el siguiente prompt sólo puede autorizar M3 —rúbrica global versionada—. M4 ya no tiene bloqueo decisorio, pero sigue no implementado y requiere autorización posterior propia.
