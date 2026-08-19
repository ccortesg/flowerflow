# ADR-0008: contrato de jueces y evaluación de Fase 02B

- **Estado:** Accepted — M1–M5 verified local/test; M6–M10 not authorized
- **Fecha:** 2026-08-18
- **Decisor:** propietario de Flower Flow
- **Alcance:** Fase 02B; no incluye ganadores ni resultados

## Contexto

El propietario respondió las 21 decisiones del paquete. Prompts posteriores autorizaron M1–M5. Tras los contratos históricos `1×10` y `2×30`, la decisión final exige cuatro principales y dos sustitutos ilimitados. El código local implementa identidad/perfil, rúbrica, asignaciones/conflictos `4+2` y paquete ciego estructural. No existen evaluaciones ni puntajes. Este ADR no autoriza por sí mismo M6–M10 o producción.

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
- Los cuatro jueces principales evalúan todas las propuestas elegibles sin límite fijo. Dos jueces adicionales son exclusivamente sustitutos, no reciben asignaciones iniciales y tampoco tienen límite de reasignaciones. La composición operativa es seis perfiles activos: cuatro `primary` y dos `substitute`; todos usan `max_active_assignments=NULL`.
- El catálogo de conflictos contiene relación personal/familiar, relación profesional/económica, participación en la propuesta y otro conflicto con explicación obligatoria.
- `admin` resuelve y selecciona manualmente uno de los dos sustitutos operativos. Una propuesta admite varias asignaciones independientes, pero un mismo sustituto no recibe dos asignaciones vigentes de esa propuesta. No existe límite individual ni combinado por volumen; los rechazos se basan en identidad/estado/prerrequisitos, duplicidad o invariantes del conflicto.
- El cierre global es `2026-08-27 23:59:59 America/Hermosillo`.

### Ceguera y datos

- La modalidad es ceguera simple estructural.
- El juez asignado ve todos los campos sustantivos y anexos evaluables de la propuesta.
- M5 materializa una proyección separada y única por `submission_version`: payload allowlist con SHA-256 canónico e inventario técnico sin nombres/rutas originales. Nunca se sirve el snapshot crudo.
- Sólo rol exacto, perfil activo y asignación propia `active` consumen un paquete `active`; conflicto/void/cancel, no asignado, pending/suspended, cero/multirol fallan cerrados. El replacement comparte el mismo paquete.
- El anexo se entrega por ruta privada M5 con etiqueta neutra y revalidación de tamaño, SHA, MIME y firma; no existe fetch remoto ni preview de vínculos.
- Siempre se excluyen PII estructurada de contacto, comprobantes de residencia, notas internas, aclaraciones e historial de admisibilidad.
- La automatización sólo anonimiza campos estructurados, nombres de archivo expuestos y metadatos técnicos. No promete eliminar identidad escrita o incrustada en texto, imágenes, enlaces o anexos.
- El propietario acepta el riesgo residual de autoidentificación en contenido evaluable sin bloquear la propuesta.

### Rúbrica, cálculo y consolidación

- Rúbrica global: Pertinencia 20 %, Claridad 20 %, Viabilidad 25 %, Impacto 25 % y Coherencia 10 %.
- Los códigos estables implementados son `pertinence`, `clarity`, `feasibility`, `impact` y `coherence`, en ese orden. No existe descripción extensa aprobada: M3 persiste `NULL` y muestra `POR_CONFIRMAR`.
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
- M1/M2 implementaron rol, gates, perfil y ciclo operativo de cuenta sólo en local/test. M3 implementó rúbrica versionada; M4/M4A el flujo `4+2` ilimitado; M5 el paquete allowlist y anexos privados. M6–M10 requieren autorización separada.

## Decisión que resolvió el bloqueo operativo

`P2B-BLOCK-001 — OWNER FINAL/RESOLVED 2026-08-18`: el contrato original de máximo ocho para todos fue sustituido primero por `4+1` con diez para el sustituto, luego por `4+2` con treinta para cada sustituto y finalmente por seis jueces sin límite. Los dos sustitutos siguen siendo exclusivos para reemplazos y no reciben asignaciones iniciales.

El perfil conserva `primary|substitute`; `max_active_assignments` es `NULL` para ambos. M4A migra aditivamente los sustitutos históricos `10→NULL`, exige exactamente dos sustitutos operativos, impide su carga inicial, requiere selección manual del admin y no cuenta ni rechaza por volumen. `P2B-M4-CORRECTION-001` queda `RESOLVED LOCAL/TEST`.

El límite de seis se aplica a perfiles activos operativos. Cuentas suspendidas o históricas pueden conservarse para no destruir auditoría. Esta corrección no autoriza balanceo automático ni una cadena de reemplazo cuando un sustituto declara conflicto.

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

El contrato completo y la matriz de evidencia/decisiones se mantienen en `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`. M1–M5 demostraron aislamiento, cuenta, rúbrica, asignaciones/conflictos y paquete ciego. El prompt M6 conserva esas precondiciones. M6–M10 siguen no implementados y requieren autorización propia.
