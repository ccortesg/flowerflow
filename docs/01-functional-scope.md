# Alcance funcional — Flower Flow 2026

> **Estado vigente — 2026-08-18:** además del alcance anterior, Fase 02B M1/M2 implementa localmente rol/gates y perfil operativo de juez, función primary/substitute, capacidad derivada, alta directa admin, credencial propia, activación, suspensión/reactivación, sesiones y recovery 2FA. M3–M10 siguen sin implementar; `P2B-BLOCK-001` está resuelto por decisión del propietario. Producción permanece `OWNER_CONFIRMED_DEPLOYED`/SHA `POR_CONFIRMAR`; M1/M2 no se atribuyen a ella.

> **Reconciliación jurídica v1.1, actualizada 2026-08-18:** las nuevas versiones identifican a `FUNXT, A.C.`, confirman el cierre del 23 de agosto, cuatro categorías y máximo cuatro propuestas. El propietario aceptó sin cambios la superposición de accesibilidad, aprobó continuidad operativa sin reaceptación forzada para cuentas v1.0 y designó el archivo físico v1.0 `3bcf31…` conservando el antecedente `42bd5e…`. Evidencia: `docs/17-legal-v1-1-reconciliation-2026-08-17.md`.

> **Adenda autoritativa de plazo, 2026-08-17:** el cierre inclusivo de `hermosillo-florece-2026` se amplía a `2026-08-23 23:59:59 America/Hermosillo`, equivalente a `2026-08-24 06:59:59 UTC`. La Mecánica v1.1, p. 3, “Recepción de propuestas”, y los Términos v1.1, p. 2, “Vigencia y acceso”, confirman esta fecha; la contradicción de plazo con v1.0 queda resuelta para nuevas operaciones v1.1.

> **Adenda funcional histórica de categoría, resuelta 2026-08-18:** la implementación opera con cuatro categorías activas y ordenadas y máximo cuatro propuestas, una por categoría. La plataforma conserva sus descripciones actuales; la referencia adicional a accesibilidad en Movilidad dentro de la Mecánica v1.1 fue aceptada por el propietario y no produce recategorización.

> **Sustitución parcial aprobada, 2026-07-15 (histórica):** Fase 01 implementó sitio público, auth, perfil, borradores/envío, archivos y panel mínimo. La adenda Fase 02A al final de este documento autorizó e implementó después la revisión de admisibilidad; jueces, rúbrica, ganadores y resultados continúan sin implementar.

## Contrato Fase 01 vigente

- Responsable jurídico verificado en v1.1: FUNXT, A.C., RFC FUN110208BT0; nombre comercial FLORECE HERMOSILLO y movimiento ciudadano FLOWER FLOW.
- Participante: persona física 18+, residente de Hermosillo; cuenta representa al equipo.
- Equipo: máximo cinco incluyendo representante; declaración de elegibilidad de todos.
- Propuesta: una por categoría, máximo cuatro, español, resumen, contenido rico, al menos un archivo.
- Archivos: PDF, Office y ODF permitidos, más JPEG/PNG/WebP del editor; 10 MiB acumulados.
- Enlaces: YouTube y carpeta pública en proveedores allowlist; nunca fetch server-side.
- Finalización: correo verificado, perfil mínimo capturado desde registro, legales separados, snapshot/folio/idempotencia.
- Flags default: público/panel `true`; registro/recepción/resultados `false`.

La descripción funcional vigente de “Movilidad con Flow” es “Ideas para mejorar la movilidad, la vialidad y la seguridad de los desplazamientos en la ciudad.” Accesibilidad e inclusión permanecen destacadas en “Hermosillo sin Barreras”; la referencia adicional de la Mecánica v1.1 en Movilidad se acepta como está y no cambia categorías, descripciones ni propuestas existentes.

**Fecha de corte de baseline:** 2026-07-15; **estado vigente:** 2026-08-17

**Estado:** alcance vivo con implementación local parcial; no autoriza producción
**Regla de lectura:** `DECISION` está confirmado; `ASSUMPTION` permite estimar sin inventar una aprobación; `PENDING` bloquea la implementación afectada.

## Limitación de la fuente

**RESOLVED 2026-07-15:** el prompt Fase 01 v2 aportó la fuente completa y sustituye la reconstrucción inicial para el alcance de recepción.

## Meta del MVP

**DECISION Fase 01:** entregar la capacidad local/test para informar, registrar cuenta/perfil, preparar y enviar propuestas y administrarlas mínimamente. Revisión/evaluación quedan para fases posteriores.

**DECISION:** cierre `2026-08-23 23:59:59 America/Hermosillo`, inclusivo. **PENDING:** fecha/hora de apertura y fecha objetivo de salida.

La ruta crítica funcional es:

`convocatoria y legales → registro con perfil mínimo → verificación de correo → proyecto y archivos → envío versionado → panel mínimo`

Resultados públicos, reportes ampliados y comunicaciones no críticas no pueden retrasar esa ruta.

## Actores y límites

| Actor | Capacidades MVP | Prohibiciones relevantes |
| --- | --- | --- |
| Visitante | Ver convocatoria, categorías, calendario, FAQ y documentos públicos; iniciar registro. | No ver PII, proyectos privados ni resultados no publicados. |
| Participante | Gestionar cuenta/perfil; crear proyecto; invitar equipo si se aprueba; cargar archivos; enviar; atender correcciones; ver su estado. | No ver proyectos ajenos, notas internas, evaluaciones ni ranking. |
| Integrante | Aceptar invitación y realizar acciones expresamente delegadas. | No obtiene permisos por conocer un folio o URL. |
| Revisor de elegibilidad | Consultar datos necesarios, revisar residencia, solicitar corrección, declarar elegibilidad. | No evaluar, alterar rúbrica ni publicar ganadores. |
| Juez | Consultar sólo asignaciones propias; ver campos sustantivos/anexos evaluables; declarar conflicto; guardar/enviar evaluación. | No ver PII estructurada, residencia, notas, aclaraciones, historial, proyectos no asignados, otros jueces o ranking global. La autoidentificación dentro del contenido es riesgo aceptado. |
| Administrador de convocatoria | Operar convocatoria, revisión, asignaciones, comunicaciones y resultados según permiso. | No eludir auditoría ni acceso por recurso. |
| Soporte de privacidad | Registrar y gestionar solicitudes de privacidad. | Acceso sólo a datos necesarios para el caso. |
| Auditor | Consultar reportes, conflictos, decisiones y bitácora. | Sin escritura operativa. |
| Superadministrador | Roles, permisos y configuración excepcional. | 2FA y confirmación de contraseña obligatorios para acciones críticas. |

## Módulo 1 propuesto — Sitio público y convocatoria

**ASSUMPTION:** corresponde a parte del fragmento faltante.

### Incluido en MVP

- Landing con propósito, fechas, llamada a registro y estado de convocatoria.
- Bases/resumen, categorías, elegibilidad, proceso, calendario, FAQ, contacto y documentos legales vigentes.
- Comportamiento explícito para próxima apertura, abierta y cerrada.
- SEO público básico, sitemap, robots, canonical y Open Graph con asset autorizado.
- Enlaces visibles a privacidad y contacto.

### Criterios

- La fecha se presenta en `America/Hermosillo`.
- El estado viene de servidor; no depende de un contador del navegador.
- Login, participante, juez, administración y staging usan `noindex`.
- No se usan imágenes, logos o claims de Apple sin autorización.

## Módulo 2 propuesto — Identidad, cuenta y acceso

**ASSUMPTION:** corresponde a parte del fragmento faltante.

### Incluido en MVP

- Registro con nombres, correo, celular México `+52`, fecha de nacimiento, colonia, residencia y preferencias de comunicación.
- Verificación de correo con confirmación amigable, login, logout y restablecimiento.
- Aceptación obligatoria de documentos versionados y consentimiento opcional de futuras actividades desde el alta.
- Alta directa administrativa para jueces implementada en local/test; invitaciones firmadas/expirables sólo para integrantes si ese flujo se aprueba.
- Roles/permisos y Policies por recurso.
- 2FA opcional para juez; reglas reforzadas para otras acciones privilegiadas según su contrato.
- Rate limiting, sesiones revocables y confirmación de contraseña.

### Criterios

- Las respuestas sensibles no revelan si existe un correo.
- Ocultar botones no sustituye autorización de ruta/controlador/consulta/archivo.
- Un usuario suspendido pierde sus sesiones.
- El primer superadministrador se crea mediante mecanismo seguro sin contraseña hardcodeada.

## Módulo 3 propuesto — Perfil, residencia y elegibilidad

**ASSUMPTION:** corresponde a parte del fragmento faltante.

### Incluido en MVP

- Perfil mínimo necesario para la convocatoria capturado en el registro y editable después.
- Captura de elegibilidad declarativa.
- Carga privada de comprobante de residencia.
- Revisión separada con decisión, razones, actor, fecha y versión.
- Solicitud de corrección y reenvío controlado.
- Retención/eliminación según política pendiente.

### Criterios

- Comprobantes separados de anexos del proyecto.
- Sólo `eligibility_reviewer` y permisos excepcionales pueden descargarlos.
- Toda descarga sensible se registra.
- Jueces nunca reciben el comprobante ni sus metadatos reveladores.

## Módulo 4 propuesto — Proyecto, equipo, archivos y envío

**ASSUMPTION:** corresponde a parte del fragmento faltante.

### Wizard MVP

1. Categoría y datos básicos.
2. Participación individual/equipo.
3. Contenido de propuesta.
4. Anexos autorizados.
5. Elegibilidad y documentos aplicables.
6. Revisión de resumen y aceptaciones vigentes.
7. Confirmación y envío.

El orden es ajustable tras conocer los campos finales.

### Capacidades

- Crear, editar y eliminar borradores conforme a política.
- Autosave con indicador de guardado y recuperación ante error.
- Integrantes, invitaciones y aceptación si se aprueban equipos.
- Upload privado con progreso, validación y recuperación.
- Vista previa/resumen antes del envío.
- Envío transaccional e idempotente, folio y acuse.
- Correcciones como nueva versión auditable.
- Retiro conforme a regla pendiente.

### Criterios

- Correo verificado, elegibilidad mínima y documentos vigentes son precondiciones.
- Después del cierre no se acepta envío ordinario.
- Doble clic/reintento no crea dos envíos o folios.
- La versión enviada no se sobrescribe.
- Archivos se validan por tamaño, extensión, MIME y firma; se almacenan fuera del web root.

## Módulo 5 propuesto — Backoffice y revisión

**ASSUMPTION:** corresponde a parte del fragmento faltante.

### Incluido en MVP

- Dashboard operativo con conteos útiles y sin exposición excesiva de PII.
- Listados server-side de participantes/proyectos.
- Filtros por convocatoria, categoría, estado, elegibilidad y fecha.
- Detalle autorizado, historial y versión revisada.
- Solicitud de corrección; elegible/no elegible; reapertura excepcional.
- Notas internas.
- Exportación mínima según permiso.
- Asignación de jueces.

### Criterios

- Consultas paginadas, autorizadas, indexadas y sin N+1.
- Filtros persistentes no guardan PII en `localStorage`.
- Exportaciones limitan columnas, redactan PII y registran actor/fecha.
- Cada transición exige permiso, precondiciones y auditoría.

## Módulo 6 propuesto — Legal, contenido y configuración

**ASSUMPTION:** corresponde a parte del fragmento faltante.

### Incluido en MVP

- Documentos legales versionados con vigencia, hash y estado.
- Registro de aceptación por usuario, versión, fecha y contexto proporcional.
- Configuración tipada de fechas, límites y constantes; no duplicarlas en código.
- Categorías ordenables/activables.
- Contenido por código en MVP salvo aprobación de editor administrativo.

### Criterios

- Un documento nuevo no borra evidencia de aceptación anterior.
- Contenido enriquecido, si se aprueba, se sanitiza en servidor.
- Textos legales finales requieren aprobación; el sistema no afirma sustituir asesoría legal.

## Módulo 7 — Jueces

**DECISION:** este módulo sí aparece explícitamente en el insumo.

### Incluido en MVP

- Dashboard de asignaciones pendientes, en borrador y finalizadas.
- Instrucciones y rúbrica vigentes.
- Declaración de conflicto por proyecto.
- Vista anónima del proyecto y anexos autorizados.
- Evaluación por criterio con comentarios.
- Guardado de borrador.
- Confirmación antes de enviar.
- Historial personal.
- Cierre automático por calendario y excepción auditada.

### Contrato 02B aprobado — todavía no implementado

- Roles estrictamente excluyentes y alta directa de juez por `admin`.
- Asignación manual; cuatro principales evalúan todas las propuestas elegibles sin límite fijo; quinto sustituto exclusivo con máximo diez reasignaciones activas; sin especialidad.
- Ceguera simple estructural, con todos los campos sustantivos y anexos evaluables, nunca PII estructurada/residencia/notas/aclaraciones/historial.
- Rúbrica: Pertinencia 20 %, Claridad 20 %, Viabilidad 25 %, Impacto 25 % y Coherencia 10 %; escala 0–10/paso 0.5.
- Total en servidor 0–100, precisión interna cuatro, visible dos y `HALF_UP`; media aritmética de cuatro evaluaciones; faltantes bloquean consolidación.
- Comentario general obligatorio 100–2,000; comentarios por criterio opcionales hasta 1,000.
- Cierre `2026-08-27 23:59:59 America/Hermosillo`; reapertura append-only por `admin` hasta las 20:00 con razón/password confirmation.
- Retención 02B de 24 meses desde el cierre administrativo; empate por igualdad a dos decimales sin declarar ganador.
- `P2B-BLOCK-001` está resuelto: la cobertura recae en cuatro principales sin límite fijo y el reemplazo en un quinto sustituto exclusivo con capacidad diez. M4 sigue sujeto a implementación y autorización separadas.

### Excluido

- Acceso a otros jueces.
- Resultados globales o ranking.
- Identidad de participantes salvo decisión explícita de evaluación no ciega.
- Comprobantes de residencia.
- Configuración o administración.

## Módulo 8 — Resultados públicos

**DECISION:** se planifica desactivado por defecto.

### MVP mínimo

- Declaración administrativa separada del cálculo.
- Vista previa interna de ganadores.
- Interruptor de publicación protegido por permiso específico.
- Publicación por categoría sólo tras confirmación.
- Nombre de proyecto, categoría, resumen y participante/equipo únicamente si reglas y consentimiento lo permiten.
- Archivo básico de la edición 2026.

### Criterios

- No publicar comprobantes, correo, teléfono, domicilio ni anexos no autorizados.
- La puntuación no publica ni declara automáticamente.
- La eventual nota de independencia de Apple sólo aparece si el texto legal final lo exige.

## Comunicaciones

### MVP crítico

- Verificación de correo.
- Alta directa y aviso seguro de cuenta de juez; invitación de integrante sólo si ese flujo se aprueba.
- Confirmación de comprobante recibido.
- Solicitud de corrección.
- Confirmación de proyecto y folio.
- Cambio de elegibilidad.
- Asignación a juez.
- Confirmación de evaluación enviada.
- Aviso de resultado cuando esté autorizado.
- Comunicación administrativa excepcional con permiso.

### Reglas

- Mailables/Notifications de Laravel y plantillas HTML/texto plano.
- Cola, idempotencia, reintentos y registro del resultado.
- Entorno local mediante log, Mailpit o equivalente.
- `convocatoria@flowerflow.com.mx` como remitente o reply-to funcional y `privacidad@flowerflow.com.mx` para privacidad.
- Sin comprobantes o PII sensible en el cuerpo.
- Marketing masivo fuera de alcance sin consentimiento.
- Configuración SMTP y SPF/DKIM/DMARC pendientes.

## Reportes y auditoría

### MVP

- Reporte por categoría, estado y elegibilidad.
- Seguimiento de asignaciones y evaluaciones.
- Conflictos y recusaciones.
- Bitácora de declaración/publicación de ganadores.
- Auditoría de actor, acción, entidad, fecha, contexto técnico y cambios antes/después redactados.
- Registro de accesos/descargas sensibles y exportaciones.

### Fase 2

- Analítica histórica y dashboards avanzados.
- Reportes programados.
- Exportaciones de gran volumen no necesarias para operar la edición.

## Privacidad

**ASSUMPTION:** bandeja mínima de solicitudes provenientes de `privacidad@flowerflow.com.mx` o formulario aprobado.

- Registrar tipo, solicitante, estado, responsable, fechas, evidencia y cierre.
- Apoyar acceso, rectificación, cancelación u oposición sin afirmar cumplimiento legal automático.
- Exportación, corrección y eliminación controladas.
- Acceso limitado al rol de soporte y excepciones auditadas.
- Política de identidad del solicitante, SLA y retención: **PENDING**.

## Flujos principales

### Participante

`visita → registro → verificación → perfil/elegibilidad → borrador → anexos → revisión → aceptación → envío → folio → seguimiento/corrección → resultado`

### Revisión

`proyecto enviado → revisión de versión → elegible | no elegible | corrección solicitada → nueva versión si aplica → cierre de elegibilidad`

### Juez

`alta directa administrativa → acceso seguro → asignación → conflicto | evaluación en borrador → confirmación → evaluación enviada → eventual reapertura append-only`

### Ganador

`evaluaciones cerradas → consolidación calculada en servidor → revisión administrativa → declaración justificada → vista previa → publicación autorizada`

## Estados y transiciones

Las tablas siguientes ya aplican la simplificación recomendada en `docs/03-data-model.md`. La revisión de elegibilidad no es un estado global de convocatoria; las asignaciones y evaluaciones son entidades propias y no duplican estado en el proyecto.

### Convocatoria

| De | A | Actor/condición mínima |
| --- | --- | --- |
| `draft` | `scheduled` | Administrador; fechas y documentos aprobados. |
| `scheduled` | `open` | Scheduler o administrador autorizado; apertura alcanzada. |
| `open` | `closed` | Scheduler o administrador; cierre alcanzado o justificado. |
| `closed` | `judging` | Administrador; revisión de elegibilidad suficiente, rúbrica activa y asignaciones preparadas. |
| `judging` | `results_published` | Permiso de publicación; evaluaciones y decisión administrativa completas. |
| cualquier finalizable | `archived` | Administrador; retención y exportaciones verificadas. |

### Proyecto

| De | A | Actor/condición mínima |
| --- | --- | --- |
| `draft` | `submitted` | Participante; invariantes de envío satisfechas. |
| `submitted` | `under_eligibility_review` | Sistema/revisor; versión fijada. |
| `under_eligibility_review` | `correction_requested` | Revisor; razón y plazo. |
| `correction_requested` | `submitted` | Participante; nueva versión e idempotencia. |
| `under_eligibility_review` | `eligible` / `ineligible` | Revisor; decisión y razones. |
| `eligible` | `evaluated` | Sistema; las asignaciones existen y las evaluaciones requeridas cerraron. |
| `evaluated` | `finalist` | Sólo si se aprueba etapa de finalistas. |
| `evaluated` / `finalist` | `winner` / `not_selected` | Administrador; decisión separada y auditada. |
| permitido | `withdrawn` | Participante/administrador según regla pendiente. |
| final | `archived` | Administrador; política de archivo. |

### Evaluación

| De | A | Actor/condición mínima |
| --- | --- | --- |
| `assigned` | `conflict_declared` | Juez; conflicto explicado; bloquea captura. |
| `assigned` | `in_progress` | Juez asignado; acceso vigente. |
| `in_progress` | `submitted` | Juez; criterios completos y confirmación. |
| `submitted` | `reopened` | Administrador con permiso, razón y auditoría. |
| `reopened` | `in_progress` | Sistema/juez asignado. |
| estado permitido | `voided` | Administrador; causa y trazabilidad. |

## Invariantes

1. Un proyecto no se envía sin correo verificado, elegibilidad mínima y aceptación vigente.
2. No hay envío ordinario después del cierre.
3. Un juez no accede a proyecto no asignado.
4. Conflicto declarado impide evaluar.
5. Comprobantes de residencia nunca llegan al juez.
6. Puntuación y totales se calculan en servidor.
7. Declaración de ganador es una acción administrativa separada.
8. No existe selección aleatoria.
9. Cada envío conserva snapshot/versionado auditable.
10. Toda transición crítica registra actor y contexto.
11. Endpoints y archivos aplican autorización en servidor.
12. Resultados públicos permanecen apagados hasta autorización.

## MVP estricto

### Debe estar

- Sitio público y documentos.
- Identidad/verificación y mínimo privilegio.
- Perfil/elegibilidad y residencia privada.
- Proyecto, borrador, archivos y envío versionado.
- Backoffice de revisión/corrección.
- Jueces, conflictos, rúbrica y evaluación.
- Decisión de ganador separada.
- Correos críticos.
- Auditoría y reportes operativos mínimos.
- WCAG en recorridos críticos.
- Pruebas, backups, observabilidad y runbook AWS.

### Puede recortarse si compromete la fecha

- Página pública de resultados, manteniendo sólo declaración interna.
- Invitaciones/edición colaborativa compleja de equipos.
- CMS; usar contenido desplegado por código.
- Exportaciones avanzadas.
- Dashboards visuales; conservar listados y conteos operativos.
- Recordatorios sofisticados; conservar avisos indispensables.

## Fase 2

- Galería pública de proyectos.
- CMS enriquecido.
- Analítica consentida.
- Automatizaciones y dashboards avanzados.
- Colaboración avanzada de equipo.
- Archivo histórico enriquecido.
- Reportes programados y exports masivos.

## Fuera de alcance

- Marketing masivo.
- Aplicación móvil.
- API pública o integraciones externas no aprobadas.
- Selección aleatoria.
- Ranking global para jueces.
- Evaluación basada en comprobantes de residencia.
- Publicación de PII/documentos.
- Asesoría o afirmación de cumplimiento legal.
- Assets de terceros sin licencia.

## Ambientes que condicionan el alcance

### Local/pruebas

**DECISION:** MySQL en `127.0.0.1:3306`, base `flowerflow`, usuario `flowerflow_user` y contraseña provista fuera del repositorio para el `.env` local.

**VERIFIED para tests:** la suite usa exclusivamente `flowerflow_testing` y `flowerflow_testing_user`, valida ambiente/driver/host/base/usuario antes de `RefreshDatabase` y el candidato tiene las 12 migraciones aplicadas. La base primaria `flowerflow` no es desechable por inferencia y conserva migraciones pendientes; no usarla para `migrate:fresh`.

### Producción

**DECISION:** AWS EC2 Ubuntu compartida/coexistente con `administratec`.

El MVP operativo exige aislamiento de vhost, ruta, usuario de sistema, `.env`, base/usuario DB, storage, sesiones/cache, workers, scheduler, logs y backups. Inventario de servidor, capacidad y estrategia final: **PENDING**.

## Definition of Done funcional del MVP

- Todos los requisitos MVP aprobados tienen historia, criterio y prueba trazados.
- Los recorridos de participante, revisor, juez y administrador completan pruebas end-to-end con datos sintéticos.
- Las Policies impiden acceso cruzado incluso mediante URL directa.
- Estados e invariantes se prueban en servidor.
- Archivos privados no son servidos directamente.
- Aceptaciones, envíos, evaluaciones y ganador tienen evidencia auditable.
- No hay secretos ni PII real en código, fixtures, logs o documentación.
- Build, tests, lint y chequeos de accesibilidad acordados pasan.
- UAT, backup/restauración y smoke tests AWS se completan antes de producción.
- Producto y responsables legales aprueban textos, publicación y retención.

## Aprobaciones necesarias antes de implementar

1. Fragmento faltante o aceptación formal de la reconstrucción.
2. Reglas de participación/equipos y límites de proyecto/archivos.
3. Documentos legales, residencia y retención.
4. Rúbrica, jueces por proyecto, anonimato, empate y recusación.
5. Premio y datos publicables.
6. Fecha/hora de apertura, cierre y lanzamiento.
7. Variante/licencia Materialize.
8. Inventario y aislamiento AWS EC2 con `administratec`.
9. ExecPlan y capacidad real del equipo para la ruta crítica.

## Adenda de alcance ejecutable — Fase 02A, 2026-07-16

La aprobación específica de Fase 02A sustituye, sólo para este milestone, los bloqueos históricos que impedían diseñar revisión y residencia. Están incluidos:

- expediente idempotente por propuesta enviada y snapshot;
- revisión, aclaración formal y resolución de admisibilidad;
- solicitud privada de residencia para representante o integrantes;
- controles de archivo, descarga autorizada, auditoría y cálculo dry-run de retención;
- listado/filtros y detalle para reviewer/admin, más seguimiento participante;
- correos transaccionales resilientes y feature flag apagada por defecto.

Los límites técnicos de archivos provienen del prompt autorizado. Edad, residencia, tipos de comprobante, alcance de aclaración y motivos de resolución provienen de los PDF canónicos. Continúan `PENDING` la antigüedad numérica de “reciente”, el catálogo cerrado de documentos equivalentes y el borrado posterior a ganadores.

No se implementan jueces, asignación, evaluación, rúbrica, ganadores, publicación, campañas, ARCO completo, reportes avanzados ni producción.
