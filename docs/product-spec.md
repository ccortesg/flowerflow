# Especificación de producto — Flower Flow 2026

> **Estado vigente M5 — 2026-08-18:** M4A y M5 están `GO LOCAL/TEST`. La operación conserva cuatro `primary` y dos `substitute` ilimitados; M5 añade un paquete ciego único por `submission_version`, payload allowlist con hash canónico, inventario neutro y descarga privada sólo para la asignación propia activa. El riesgo de autoidentificación dentro del contenido continúa aceptado. M6–M10 permanecen no implementados/no autorizados.

> **Adenda de estado productivo y Fase 02B — 2026-08-18:** el propietario confirma el release anterior como `OWNER_CONFIRMED_DEPLOYED`, sin evidencia técnica independiente y con `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR`. M1–M5 están conformes sólo en local/test; producción no se infiere.

> **Decisiones jurídicas del propietario — 2026-08-18:** Mecánica, Términos y Aviso v1.1 permanecen vigentes con cuatro categorías, máximo cuatro propuestas y cierre al 23 de agosto. La superposición temática de accesibilidad se acepta sin cambios. Las cuentas con aceptación v1.0 continúan operativamente sin reaceptación forzada ni modificación de evidencia; las nuevas aceptaciones registran v1.1. El archivo físico v1.0 designado es `3bcf31…` y la discrepancia histórica `42bd5e…` se conserva visible. Ver `docs/17-legal-v1-1-reconciliation-2026-08-17.md`.

> **Adenda histórica de plazo — 2026-08-17, reconciliada después por v1.1:** el cierre inclusivo de `hermosillo-florece-2026` se amplió al `2026-08-23 23:59:59 America/Hermosillo`, persistido como `2026-08-24 06:59:59 UTC`. En ese milestone los PDF no se modificaron; las versiones v1.1 posteriores ya regularizaron el plazo.

> **Adenda histórica “Hermosillo sin Barreras” — 2026-08-06, reconciliada después por v1.1:** para la plataforma se fijaron cuatro categorías y máximo cuatro propuestas. En ese corte los PDF v1.0 todavía divergían; las versiones v1.1 y decisiones del 2026-08-18 resolvieron cantidades y aceptaron la superposición temática sin recategorización. Esta adenda conserva la secuencia histórica.

> **Adenda autoritativa Fase 01 — 2026-07-15:** el alcance aprobado es recepción local/test, no el MVP completo histórico. Cierre inclusivo: 15 de agosto de 2026 a las 23:59:59 en `America/Hermosillo`; categorías exactas: Movilidad con Flow, Hermosillo Florece y Mi familia, mi mascota; participación individual/equipo hasta cinco; una propuesta por categoría y tres totales. Registro/recepción/resultados están apagados por defecto. Evaluación, jueces, ganadores y publicación permanecen fuera. Ver `docs/01-functional-scope.md` y `docs/legal-change-log.md`.

> **Estado vigente de implementación — 2026-08-18:** M1/M2 aportan RBAC/ciclo operativo de cuenta, M3 la rúbrica versionada/inmutable, M4/M4A asignaciones/conflictos y M5 la proyección ciega con anexos privados. La evidencia vigente se registra en `docs/23-phase-02b-m5-blind-package-implementation-report-2026-08-18.md`. El producto maestro se estima en 68 %: aún faltan evaluación/puntajes/consolidación, ganadores/resultados y ARCO, y producción no fue verificada independientemente.

**Fecha de corte de la baseline:** 2026-07-15; **corte vigente:** 2026-08-18

**Estado:** especificación viva; distingue código local, `OWNER_CONFIRMED_DEPLOYED`, evidencia productiva `POR_CONFIRMAR` y diseño futuro
**Propósito:** consolidar el producto que debe construirse, sus límites y las decisiones que requieren aprobación.

## Convenciones de decisión

- **DECISION:** dato confirmado por el solicitante, por el alcance recibido o por evidencia directa del repositorio.
- **ASSUMPTION:** supuesto de trabajo recomendado para poder planificar; requiere validación antes de implementar la parte afectada.
- **PENDING:** dato, aprobación o evidencia aún no disponible.
- **PROPOSAL_NEEDED:** contrato futuro con alternativas/recomendación que requiere decisión expresa antes de implementar.

## Integridad del insumo

> **RESOLVED para Fase 01, Fase 02A y M1–M5 de 02B; PENDING para el producto maestro:** identidad/alta/función, rúbrica versionada, asignaciones/conflictos y paquete ciego están probados en local/test. M6–M10 no están implementados; resolución de empates, ganadores, publicación y ARCO permanecen pendientes. `P2B-BLOCK-001` y `P2B-M4-CORRECTION-001` están cerrados localmente.

## Resumen ejecutivo

Flower Flow es la plataforma web de la convocatoria 2026. El repositorio ya registra participantes, recibe proyectos, verifica admisibilidad, asigna propuestas y expone al juez asignado una proyección estructural ciega con anexos neutros. La captura/evaluación con rúbrica y los resultados continúan como objetivo futuro.

El MVP se limita a lo indispensable para recibir, revisar y evaluar proyectos de forma segura antes del 15 de agosto de 2026. Desde la fecha de corte quedan 31 días calendario, de modo que seguridad, flujo de envío, revisión y evaluación tienen precedencia sobre funciones presentacionales. La publicación pública de ganadores se prepara con un interruptor desactivado por defecto; una galería enriquecida, marketing masivo y cualquier API o aplicación móvil quedan fuera del MVP.

La frase histórica “la primera fase es documental” quedó superada por las autorizaciones de Fase 01/02A. Producción, reglas futuras y cualquier ampliación funcional siguen requiriendo aprobación expresa, ExecPlan y gates.

## Decisiones confirmadas

| ID | Estado | Decisión |
| --- | --- | --- |
| DEC-001 | DECISION | La edición operativa es Flower Flow 2026. |
| DEC-002 | SUPERSEDED | La fecha histórica del 2026-08-15 fue sustituida por DEC-017. |
| DEC-003 | DECISION | La zona horaria de presentación es `America/Hermosillo`; los timestamps persistidos se planifican en UTC. |
| DEC-004 | DECISION | No existe selección aleatoria. La declaración de ganador es una acción administrativa separada del cálculo de puntuación. |
| DEC-005 | DECISION | Los jueces sólo acceden a proyectos asignados y nunca a comprobantes de residencia. |
| DEC-006 | DECISION | Los resultados públicos permanecen desactivados hasta confirmación administrativa. |
| DEC-007 | DECISION | El código futuro estará en inglés y la interfaz en español. |
| DEC-008 | DECISION | El entorno local/de pruebas usará MySQL en `127.0.0.1:3306`, base `flowerflow` y usuario `flowerflow_user`. |
| DEC-009 | DECISION | La contraseña de MySQL fue proporcionada fuera del repositorio y sólo se configura en el `.env` local no versionado. No se reproduce en documentación, ejemplos, logs ni fixtures. |
| DEC-010 | DECISION | Producción se planifica en una instancia AWS EC2 con Ubuntu, coexistente con el proyecto `administratec`. |
| DEC-011 | DECISION | La coexistencia en EC2 exige aislamiento por virtual host, ruta, usuario de sistema, variables, base de datos, storage, procesos, logs y backups. |
| DEC-012 | DECISION HISTÓRICA | La fase documental inicial no implementaba código; autorizaciones posteriores sí permitieron implementación local, nunca despliegue implícito. |
| DEC-013 | DECISION / RESOLVED LEGAL | La plataforma y la Mecánica v1.1 corregida conservan cuatro categorías activas, incluida `hermosillo-sin-barreras`. |
| DEC-014 | DECISION / RESOLVED LEGAL | Máximo cuatro propuestas por cuenta y una por categoría, confirmado por el propietario y la Mecánica v1.1 corregida. |
| DEC-015 | DECISION / RESOLVED LEGAL | La plataforma muestra máximo cuatro premios, uno por cada categoría; la Mecánica v1.1 corregida respalda cuatro categorías. |
| DEC-016 | DECISION / OWNER ACCEPTED | La Mecánica v1.1 menciona accesibilidad tanto en Movilidad con Flow como en Hermosillo sin Barreras; se conserva sin recategorización ni cambio operativo. |
| DEC-017 | DECISION / RESOLVED LEGAL | El cierre se amplía a `2026-08-23 23:59:59 America/Hermosillo`; Mecánica y Términos v1.1 ya reflejan el 23 de agosto de 2026. |
| DEC-018 | DECISION | Nuevas aceptaciones y vínculos usan documentos v1.1; v1.0 permanece inmutable e histórico. Responsable legal: FUNXT, A.C.; vigencia Mecánica/Términos 14-ago y Aviso 11-ago de 2026. |
| DEC-019 | DECISION / OWNER APPROVED | No se fuerza reaceptación v1.1 a cuentas existentes ni se alteran `legal_acceptances` históricas; nuevas cuentas y nuevos envíos registran v1.1. |
| DEC-020 | DECISION / OWNER DESIGNATION | El archivo físico Mecánica v1.0 que se conserva es `3bcf31…`; `42bd5e…` permanece como hash histórico registrado. |
| DEC-021 | DECISION OPERATIVA / OWNER CONFIRMED | Producción usa el checkout Git directo `/var/www/flowerflow`, sin `releases/current/shared`; el update inmediato se genera para esa topología y no cambia Apache ni dominios. |
| DEC-022 | `OWNER_CONFIRMED_DEPLOYED` | El propietario confirma el 2026-08-18 que instaló los cambios actuales y que existen más de 50 propuestas reales. `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR`; no equivale a verificación técnica independiente. |
| DEC-023 | SUPERSEDED 2026-08-18 | La espera de las 21 decisiones terminó con la respuesta expresa del propietario. |
| DEC-024 | OWNER_APPROVED / M1–M5 LOCAL | Las 21 decisiones quedan en ADR-0008; M1–M5 cerraron `GO LOCAL/TEST`. M6+ continúa no implementado y requiere alcance separado. |
| DEC-025 | OWNER FINAL / IMPLEMENTED LOCAL 2026-08-18 | `P2B-BLOCK-001`: cuatro principales y dos sustitutos, todos ilimitados; seis jueces operativos. M4A exige selección manual y no rechaza por volumen. |

## Evidencia actual del repositorio

| Elemento | Estado | Evidencia al 2026-08-17 |
| --- | --- | --- |
| Backend | VERIFIED | Laravel 12.64.0 sobre PHP 8.3.33; 150 pruebas/1,703 aserciones verdes en MySQL aislado. |
| Plantilla | DECISION | `package.json` declara Materialize `3.0.0` con licencia comercial. |
| Frontend | DECISION | Bootstrap 5.3.6, Vite 6.3.5 y varios plugins de la plantilla están declarados; su presencia no autoriza usarlos todos. |
| Layouts | VERIFIED | `layouts/flowerflow.blade.php` sirve público, participante y panel; layouts heredados se conservan sin ser el contrato principal. |
| Navegación | VERIFIED / DEUDA | Navegación Flower Flow usa parciales/Blade por rol; los JSON heredados conservan demos no usados por este layout. |
| Aplicación | VERIFIED | Hay 66 rutas propias sin vendor y módulos de auth, perfil, propuestas, archivos, admisibilidad, panel, exportación, cuenta juez, rúbrica versionada, asignaciones y conflictos. |
| Variante/licencia exacta | PENDING | Debe confirmarse si el paquete adquirido es starter kit o full version, y el alcance de su licencia para dominio/proyecto. |

## Objetivo del producto

Permitir que una convocatoria opere de punta a punta con mínimo privilegio, trazabilidad y separación de datos sensibles:

1. Informar reglas, calendario y documentos vigentes.
2. Registrar y verificar participantes.
3. Capturar perfil, residencia y elegibilidad mínima.
4. Crear y conservar borradores de proyecto.
5. Recibir un envío final versionado, con folio y anexos controlados.
6. Revisar elegibilidad sin exponer comprobantes a jueces.
7. Asignar proyectos a jueces y gestionar conflictos.
8. Evaluar mediante rúbrica versionada y cálculo de servidor.
9. Declarar ganadores mediante una decisión administrativa auditable.
10. Comunicar eventos transaccionales y generar reportes autorizados.

## Resultados esperados del MVP

| Resultado | Indicador de aceptación |
| --- | --- |
| Envíos íntegros | Cada proyecto enviado tiene propietario, convocatoria, categoría, folio, versión auditable, aceptación legal vigente y sello de tiempo. |
| Elegibilidad separada | Sólo personal autorizado revisa residencia; el juez no puede consultar esos documentos. |
| Evaluación controlada | Cada evaluación está ligada a una asignación vigente, rúbrica versionada y ausencia de conflicto. |
| Cálculo reproducible | Los totales se calculan en servidor y pueden reconstruirse desde puntuaciones por criterio. |
| Trazabilidad | Transiciones, decisiones críticas, descargas sensibles y exportaciones identifican actor, fecha y contexto técnico proporcional. |
| Accesibilidad | Los recorridos críticos cumplen la lista de aceptación WCAG 2.2 AA definida en `docs/05-ux-ui.md`. |
| Operabilidad | Existen pruebas, backups, restauración, monitoreo y rollback documentados antes del despliegue. |

## Actores

| Actor | Responsabilidad | Límite principal |
| --- | --- | --- |
| Visitante | Consultar convocatoria, reglas, categorías, calendario y documentos públicos. | No accede a información privada ni a resultados no publicados. |
| Participante | Gestionar su perfil, equipo, proyectos, anexos y seguimiento. | Sólo sus datos y recursos autorizados. |
| Integrante de equipo | Aceptar invitación y, si se aprueba, documentos y participación. | Sin acceso implícito a otros proyectos del representante. |
| Revisor de elegibilidad | Revisar residencia y requisitos; solicitar correcciones. | Sin modificar evaluaciones ni publicar resultados. |
| Juez | Consultar asignaciones, declarar conflicto y evaluar. | Sin PII innecesaria, comprobantes, proyectos ajenos ni ranking global. |
| Administrador de convocatoria | Operar convocatoria, categorías, revisión, asignaciones y comunicaciones. | Acciones críticas sujetas a permisos y auditoría. |
| Soporte de privacidad | Gestionar solicitudes de acceso, rectificación, cancelación u oposición. | Acceso limitado a lo necesario para el caso. |
| Auditor | Consultar reportes y bitácoras. | Sólo lectura; sin cambios operativos. |
| Superadministrador | Administrar roles y configuración excepcional. | 2FA, confirmación de contraseña y auditoría reforzada. |

## Requisitos funcionales consolidados

### Convocatoria y contenido público

- **CAL-001 — DECISION:** modelar una convocatoria versionable con slug, estado, fechas y zona horaria.
- **CAL-002 — ASSUMPTION:** el MVP tendrá una sola edición activa, pero el modelo no bloqueará ediciones posteriores.
- **CAL-003 — DECISION:** mostrar categorías, calendario, preguntas frecuentes y documentos legales vigentes.
- **CAL-004 — DECISION/PENDING:** cierre inclusivo aprobado `2026-08-23 23:59:59 America/Hermosillo`; fecha/hora de apertura pendiente y configurable.
- **CAL-005 — DECISION:** cerrar automáticamente nuevos envíos al vencer el plazo; una excepción requiere actor autorizado, justificación y auditoría.
- **CAL-006 — DECISION:** consultar sólo categorías activas de la convocatoria vigente en landing, dashboard participante y distribución administrativa; los listados históricos conservan las relaciones originales.

### Identidad y acceso

- **IAM-001 — DECISION:** registro, login, restablecimiento y verificación de correo.
- **IAM-002 — DECISION:** RBAC de mínimo privilegio complementado con Policies por recurso.
- **IAM-003 — DECISION DIFERENCIADA:** 2FA es opcional para `judge`; las acciones administrativas críticas, incluida reapertura/recuperación, exigen confirmación de contraseña. Reglas de otros roles privilegiados conservan su propio alcance.
- **IAM-004 — DECISION:** rate limiting por IP y cuenta, respuestas que no revelen si un correo existe y revocación de sesiones al suspender usuarios.
- **IAM-005 — OWNER_APPROVED / PENDING INTEGRANTES:** alta de jueces directa por `admin`, sin invitaciones. Las invitaciones de integrantes permanecen como contrato separado pendiente.

### Perfil, residencia y elegibilidad

- **ELG-001 — DECISION:** recolectar sólo los datos mínimos del participante.
- **ELG-002 — DECISION:** almacenar comprobantes de residencia separados de anexos evaluables.
- **ELG-003 — DECISION:** registrar decisión, razones, revisor, fecha y versión revisada.
- **ELG-004 — DECISION:** permitir solicitar una corrección con plazo y notificación.
- **ELG-005 — PENDING:** tipos aceptados de comprobante, vigencia, criterios, cifrado y retención.

### Proyectos y equipos

- **SUB-001 — DECISION:** crear borradores y autosave recuperable.
- **SUB-002 — ASSUMPTION:** el formulario será un wizard con pasos de datos, equipo, contenido, anexos, revisión y envío.
- **SUB-003 — DECISION:** el envío final exige correo verificado, elegibilidad mínima y aceptación de documentos vigentes.
- **SUB-004 — DECISION:** el envío genera folio, versión inmutable y acuse transaccional.
- **SUB-005 — DECISION:** una corrección posterior crea una nueva versión; no sobrescribe la enviada.
- **SUB-006 — DECISION:** archivos privados se almacenan fuera del web root, con nombres aleatorios, allowlist, límites, validación MIME/firma y descarga autorizada.
- **SUB-007 — DECISION/PENDING:** participación individual o equipo de máximo cinco; máximo cuatro propuestas por cuenta y una por categoría; límites de texto, tipos y cuota de anexos están configurados. Invitaciones de equipo y cualquier cambio jurídico posterior siguen pendientes.

### Elegibilidad administrativa

- **REV-001 — DECISION:** listados administrativos paginados, autorizados e indexados.
- **REV-002 — DECISION:** el revisor puede marcar elegible, no elegible o solicitar corrección conforme a transición válida.
- **REV-003 — DECISION:** notas internas no son visibles a participantes ni jueces.
- **REV-004 — DECISION:** cambios de estado, descarga de comprobantes y exportaciones se auditan.

### Jueces y evaluación

- **JUD-001 — OWNER_APPROVED / PARTIAL — M1 VERIFIED:** rol `judge`, permiso mínimo exclusivo, correo verificado, gates y shell vacío detrás de flag están implementados/probados. M2 aborda perfil, alta directa por `admin`, activación, suspensión y recovery; no hay dashboard de asignaciones.
- **JUD-002 — OWNER_APPROVED / M5 VERIFIED LOCAL:** ceguera simple estructural mediante paquete allowlist; todos los campos sustantivos y anexos evaluables capturados son visibles con nombres neutros, mientras PII estructurada, residencia, notas, aclaraciones e historial permanecen ocultos. El riesgo de autoidentificación dentro del contenido fue aceptado y se comunica en UI.
- **JUD-003 — OWNER_APPROVED / NOT IMPLEMENTED:** catálogo cerrado de cuatro tipos de conflicto; `admin` resuelve y reasigna a otro juez mediante una asignación independiente.
- **JUD-004 — OWNER_APPROVED / M3 IMPLEMENTED LOCAL:** rúbrica global versionada con `pertinence`/Pertinencia 20, `clarity`/Claridad 20, `feasibility`/Viabilidad 25, `impact`/Impacto 25 y `coherence`/Coherencia 10; escala 0–10/paso 0.5; precisión 4/2 `HALF_UP`; comentarios futuros 100–2,000/1,000. Descripciones extensas `NULL`/`POR_CONFIRMAR`; no existe captura de evaluación.
- **JUD-005 — OWNER_APPROVED / NOT IMPLEMENTED:** envío inmutable, reapertura append-only por `admin` hasta las 20:00 con razón/password confirmation y edición hasta las 23:59:59; toda edición administrativa conserva actor real y revisión previa.
- **JUD-006 — OWNER_APPROVED / NOT IMPLEMENTED:** cierre global `2026-08-27 23:59:59 America/Hermosillo`; notificaciones mínimas de juez y recordatorios de participantes 20/22 de agosto a las 09:00.
- **JUD-007 — OWNER_APPROVED / NOT IMPLEMENTED:** total sólo servidor, precisión 4/2 `HALF_UP`, media aritmética de cuatro evaluaciones, consolidación bloqueada ante faltantes y empate por igualdad a dos decimales. Ganador/desempate quedan fuera.
- **JUD-008 — OWNER FINAL / M4A VERIFIED:** cuatro primary cubren todas las elegibles y dos substitute exclusivos reciben reemplazos; los seis son ilimitados. M4A aplica capacidad nula y selección manual, con más de treinta reemplazos probados.

### Ganadores y resultados

- **WIN-001 — DECISION:** la puntuación calculada no declara automáticamente un ganador.
- **WIN-002 — DECISION:** declarar ganador requiere permiso específico, actor, justificación y fecha.
- **WIN-003 — PENDING:** reglas para empate, categoría desierta y número/naturaleza del premio.
- **PUB-001 — DECISION:** módulo público desactivado por defecto.
- **PUB-002 — DECISION:** publicar sólo tras confirmación administrativa y únicamente campos autorizados.
- **PUB-003 — DECISION:** nunca publicar comprobantes, correos, teléfonos, domicilios ni anexos no autorizados.
- **PUB-004 — PENDING:** datos consentidos de ganador, texto legal definitivo y política del archivo 2026.

### Comunicaciones, privacidad y reportes

- **COM-001 — DECISION:** plantillas transaccionales para verificación, alta directa de juez, invitaciones de integrante si se aprueban, correcciones, envío, elegibilidad, asignación, evaluación y resultados.
- **COM-002 — DECISION:** colas, idempotencia, reintentos y registro de resultado sin contenido sensible innecesario.
- **COM-003 — DECISION:** usar `convocatoria@flowerflow.com.mx` como remitente o reply-to funcional y `privacidad@flowerflow.com.mx` para privacidad; credenciales SMTP permanecen pendientes y no deben inventarse.
- **COM-004 — DECISION:** no construir marketing masivo sin consentimiento y alcance explícitos.
- **PRV-001 — ASSUMPTION:** bandeja administrativa para solicitudes recibidas por correo o formulario, sin afirmar que sustituye revisión legal.
- **PRV-002 — DECISION:** conservar evidencia de atención y cierre, con exportación, rectificación, retención y eliminación controladas.
- **RPT-001 — DECISION:** reportes por categoría, estado, elegibilidad y evaluación, sujetos a permiso.
- **RPT-002 — DECISION:** exportaciones backend con columnas mínimas, marca de actor/fecha y redacción de PII.
- **RPT-003 — DECISION:** reporte de conflictos/recusaciones y bitácora de declaración de ganadores.

## Máquinas de estado

Las listas del texto recibido son una hipótesis que debe simplificarse, no un contrato de persistencia. La recomendación canónica de planificación está en `docs/03-data-model.md`: evita guardar estados que pueden derivarse de revisiones o asignaciones y reduce el riesgo de desincronización.

### Convocatoria

Hipótesis recibida: `draft → scheduled → open → closed → eligibility_review → judging → results_published → archived`.

Recomendación: `draft → scheduled → open → closed → judging → results_published → archived`. La revisión de elegibilidad pertenece a cada proyecto; no se persiste como pseudoestado global de la convocatoria.

- **ASSUMPTION:** `scheduled` sólo se usa si existe apertura automática; si no, se elimina para simplificar.
- **DECISION:** publicar resultados requiere confirmación administrativa y no ocurre como efecto automático de `judging`.

### Proyecto

Hipótesis recibida: `draft → submitted → under_eligibility_review → correction_requested → eligible / ineligible → assigned_to_judges → under_evaluation → evaluated → finalist → winner / not_selected → archived`.

Recomendación: persistir `draft`, `submitted`, `under_eligibility_review`, `correction_requested`, `eligible`, `ineligible`, `evaluated`, `finalist` si existe la etapa, `winner`, `not_selected`, `withdrawn` y `archived`. `assigned_to_judges` y `under_evaluation` se derivan de asignaciones/evaluaciones y no se duplican en el proyecto.

Transiciones laterales controladas: `correction_requested → submitted` mediante nueva versión; cualquier estado permitido puede terminar en `withdrawn` conforme a reglas pendientes.

- **ASSUMPTION:** `finalist` se conservará sólo si existe una etapa real de finalistas.
- **PENDING:** definir desde qué estados y hasta qué fecha se permite retirar un proyecto.

### Evaluación

Contrato de diseño aprobado, todavía no implementado: `assigned → in_progress → submitted`.

Transiciones aprobadas: `assigned|in_progress → conflict_declared`; `submitted → reopened → in_progress`; y paso controlado a `voided`. El envío es inmutable; la reapertura administrativa crea una revisión append-only conforme a ADR-0008.

El contrato completo está en `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`; ninguna transición existe en código y cada milestone requiere autorización expresa.

## Invariantes de negocio

1. No se envía un proyecto sin correo verificado, elegibilidad mínima y aceptación legal vigente.
2. No se envía después del cierre salvo excepción explícita, autorizada y auditada.
3. Un juez no puede ver ni evaluar un proyecto no asignado.
4. Un juez con conflicto no puede evaluar ese proyecto.
5. Los jueces nunca acceden a comprobantes de residencia.
6. La puntuación se calcula en servidor.
7. Declarar ganador es distinto de calcular puntuación.
8. No existe selección aleatoria.
9. Todo envío conserva una versión auditable.
10. Archivos y endpoints se autorizan en servidor; ocultar un botón no concede seguridad.
11. Datos reales no se usan en desarrollo o pruebas.
12. Ninguna credencial se almacena en repositorio, documentación rastreada, frontend o logs.

## Datos y privacidad

### Clasificación

| Clase | Ejemplos | Tratamiento |
| --- | --- | --- |
| Pública | convocatoria, categorías, calendario, documentos publicados | Cacheable; revisión editorial y legal. |
| Interna | notas, asignaciones, rúbrica no publicada | Acceso por rol y Policy. |
| PII | nombre, correo, teléfono, integrantes | Minimización, masking en listados/exports y retención definida. |
| Alto riesgo | comprobantes de residencia, solicitudes de privacidad | Almacenamiento privado, permisos separados, descargas auditadas y cifrado viable. |
| Evaluación | puntuaciones, comentarios, conflictos | Separada de PII; acceso por asignación/rol. |
| Auditoría | actor, acción, entidad, antes/después redactado, contexto técnico | Inmutable en operación normal; retención pendiente. |

### Reglas de persistencia

- MySQL, InnoDB y `utf8mb4`.
- Claves foráneas y reglas de borrado intencionales.
- Índices por categoría, estado, fechas, propietario, folio, asignación y filtros frecuentes.
- Identificador interno eficiente y ULID/UUID público cuando reduzca enumeración.
- UTC en persistencia y `America/Hermosillo` en presentación.
- JSON sólo para configuración o snapshots justificados, no para datos centrales consultables.
- Soft delete únicamente cuando sea compatible con privacidad y retención.

## Ambientes

### Local y pruebas

**DECISION**

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=flowerflow
DB_USERNAME=flowerflow_user
DB_PASSWORD=<provista-fuera-del-repositorio>
```

- El valor real de `DB_PASSWORD` vive sólo en el `.env` local ignorado por control de versiones.
- `.env.example`, documentación y CI deben usar placeholders o secretos administrados.
- No ejecutar migraciones o seeders contra una base que no esté confirmada como desechable.
- Fixtures y pruebas deben usar datos sintéticos.

### Producción

**DECISION:** AWS EC2 Ubuntu coexistente con `administratec`.

**PENDING:** inventario de Ubuntu, servidor web, PHP-FPM, extensiones, CPU/RAM/disco, base de datos, TLS/DNS, correo, cron, workers, monitoreo y backups.

La arquitectura de despliegue deberá aislar:

- dominio y virtual host;
- ruta de release y document root;
- usuario/grupo del sistema y permisos;
- `.env` y secretos;
- esquema/usuario de base de datos;
- storage público y privado;
- sesiones, cache y prefijos;
- unidades `systemd`/Supervisor, workers y scheduler;
- logs, alertas, backups y rollback;
- presupuesto de CPU, memoria y disco frente a `administratec`.

No se desplegará hasta contar con backup probado, staging/UAT, smoke tests y aprobación expresa.

## Requisitos no funcionales

| Área | Requisito de planificación |
| --- | --- |
| Seguridad | OWASP, mínimo privilegio, CSRF, XSS/SQLi/IDOR, headers, cookies seguras, 2FA privilegiado y secretos fuera del repositorio. |
| Accesibilidad | WCAG 2.2 AA en recorridos críticos, teclado, foco, labels, errores, contraste, modales y stepper. |
| Rendimiento | Listados server-side, índices, eager loading, paginación, assets por página y presupuesto móvil pendiente. |
| Disponibilidad | SLO específico para apertura/cierre; capacidad y monitoreo de EC2 por verificar. |
| Integridad | Transacciones e idempotencia en envío, revisión, evaluación, ganador, correo y exportaciones. |
| Recuperación | Backup cifrado, restauración probada y rollback de código/base. |
| Observabilidad | Logs redactados con rotación, jobs fallidos, correo, errores 5xx, espacio y health check. |
| Compatibilidad | Navegadores modernos y Safari/iOS/Android; matriz exacta pendiente. |
| Mantenibilidad | Controladores delgados, Form Requests, Policies, Services/Actions, enums, eventos/jobs justificados y ADRs. |

## Alcance por fase

### MVP estricto

- Convocatoria pública, categorías, calendario y documentos.
- Registro, verificación, login, recuperación y RBAC/Policies.
- Perfil, residencia/elegibilidad y consentimiento versionado.
- Wizard con borrador, equipo sujeto a decisión, archivos privados y envío versionado.
- Backoffice de revisión y correcciones.
- Jueces, asignaciones, conflictos, rúbrica, borrador y envío de evaluación.
- Cálculo de servidor y declaración administrativa de ganador.
- Notificaciones transaccionales críticas.
- Reportes operativos mínimos y auditoría.
- Seguridad, accesibilidad, pruebas, backups, observabilidad y runbook AWS requeridos para operar.

### Fase 2

- Galería pública de proyectos.
- Gestión editorial enriquecida si el contenido por código resulta insuficiente.
- Analítica con consentimiento.
- Automatizaciones/reportes no críticos.
- Mejoras avanzadas de autosave, colaboración de equipos y archivo histórico, según datos reales.

### Fuera de alcance del MVP

- Marketing masivo o newsletters.
- Aplicación móvil o API pública/terceros.
- Selección aleatoria.
- Ranking global visible a jueces.
- Sustituir la revisión legal o afirmar cumplimiento legal.
- Publicar PII, comprobantes o anexos no autorizados.
- Uso de logos, fotografías o assets de Apple sin autorización.

## Restricciones de implementación posterior

- Leer `AGENTS.md`, ExecPlan y ADRs antes de editar.
- Un milestone por vez o worktrees sin solapamiento.
- No agregar dependencias ni cambiar versiones mayores sin aprobación.
- No modificar manualmente `public/build` ni el core de la plantilla.
- Mantener overrides de Flower Flow separados.
- Detener el avance ante fallos de tests, build, lint o aceptación.
- No desplegar sin UAT, backup, checklist y aprobación.

## Criterios de aceptación de esta especificación

- [ ] Se recibió y reconcilió el fragmento faltante de módulos 1–6, o producto aceptó expresamente los supuestos.
- [ ] Se aprobaron actores, MVP, fase 2 y fuera de alcance.
- [ ] Se aprobaron estados, transiciones e invariantes.
- [ ] Se resolvieron hora de cierre, reglas de participación, límites, rúbrica, empates, premio y datos publicables.
- [ ] Se aprobó la separación de PII, residencia y evaluación.
- [ ] Se confirmó variante/licencia de Materialize.
- [ ] Se inventarió la instancia AWS EC2 y el aislamiento con `administratec`.
- [ ] Se confirmó que la base local `flowerflow` es exclusiva/desechable para pruebas antes de ejecutar migraciones.
- [ ] Existe trazabilidad de requisitos a historias, páginas y pruebas.
- [ ] La implementación permanece bloqueada hasta aprobación del ExecPlan.

## Referencias internas

- `docs/01-functional-scope.md`
- `docs/05-ux-ui.md`
- `docs/10-open-questions.md`
- `docs/requirements-traceability.md`
- `docs/11-operations-handoff.md`
- `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`

## Adenda aprobada — Fase 02A, 2026-07-16

Esta adenda conserva el historial anterior y registra el alcance autorizado para revisión administrativa:

- la propuesta conserva `draft/submitted/withdrawn`; la admisibilidad usa expediente propio con `pending/in_review/clarification_requested/admitted/not_admitted`;
- cada expediente referencia la propuesta y la versión inmutable enviada;
- las aclaraciones permiten respuestas append-only de hasta 2,000 caracteres y nunca habilitan edición del proyecto enviado;
- residencia se solicita por representante o integrante y usa almacenamiento privado separado de anexos evaluables;
- PDF, JPEG, PNG y WebP; máximo tres archivos y 10 MiB por persona/solicitud son controles técnicos autorizados, no reglas jurídicas;
- “reciente” no tiene antigüedad automática porque los PDF no fijan meses;
- un documento equivalente exige justificación humana y una residencia rechazada no produce por sí sola no admisión;
- admitir requiere resolver/cerrar aclaraciones abiertas y verificar las solicitudes activas de residencia;
- `admitted` sólo habilita una futura fase de evaluación y no equivale a ganador;
- roles `reviewer` y `admin` operan por permisos granulares; jueces y usuarios sin permiso no acceden a identidad, residencia, notas ni auditoría;
- las notificaciones de aclaración, residencia, respuesta y resolución se encolan después del commit y una falla temporal no revierte datos;
- el cálculo de retención registra una fecha candidata a 90 días, pero no borra mientras falte determinar ganadores.

En el alcance histórico de Fase 02A, jueces, evaluación, rúbricas, ganadores, comunicaciones masivas, ARCO completo, reportes avanzados y producción quedaron fuera. El estado vigente posterior es M1–M5 local bajo contrato `4+2` ilimitado; M6+ y producción permanecen fuera.
