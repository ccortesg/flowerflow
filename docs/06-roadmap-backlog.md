# Roadmap y backlog

## Estado Fase 01 — 2026-07-15

Completado en código: locks/runtime, assets legales/branding, configuración/flags, auth/RBAC, modelo/migraciones/seed, perfil, borradores/equipos, sanitización, uploads/enlaces, finalización/snapshot/correo, panel mínimo y pruebas preparadas.

Gate inmediato: contraseña local colocada fuera de Git → migrate/seed MySQL → Feature/Pint/build → browser desktop/móvil → commits locales. Después: UAT y aprobación legal v1.1. Carriles posteriores separados: racionalizar Vite, antivirus, SMTP, staging/EC2, evaluación/jueces y resultados. No mezclar esos carriles con el cierre local.

**Fecha de planificación:** 2026-07-15  
**Fecha límite indicada:** 2026-08-15; hora PENDING  
**Ventana:** 31 días calendario, aproximadamente 23 laborables.  
**Conclusión:** el alcance completo no cabe con una sola persona. El MVP estricto requiere trabajo paralelo, decisiones de producto en 24 horas y recorte automático de extras.

## Recomendación de entrega

- **Equipo mínimo:** 2 backend, 1 frontend/Blade, 1 QA, DevOps 0.5 y producto/legal disponibles diariamente.
- **Capacidad estimada MVP:** 58 a 76 días-persona, más revisión legal.
- **Primera salida a producción recomendada:** 2026-08-07.
- **UAT integrada:** 2026-08-01 a 2026-08-06.
- **Congelamiento funcional:** 2026-08-07 al terminar el deploy; desde entonces sólo defectos críticos.
- **Ventana de estabilización:** 2026-08-08 a 2026-08-14.
- **Cierre:** 2026-08-15 en hora America/Hermosillo que debe aprobarse.

Si al 2026-07-18 no están resueltos calendario, elegibilidad, equipos, archivos, rúbrica, número de jueces, SMTP y acceso EC2, la fecha deja de ser confiable. La alternativa segura es reducir el MVP a recepción y revisión, y diferir evaluación integrada.

## Alcance de compromiso

### MVP estricto

- Base ejecutable, branding mínimo y plantilla limpia.
- Registro, login, reset, verificación, 2FA privilegiado y RBAC/Policies.
- Sitio público esencial, convocatoria, categorías y legales versionados.
- Perfil mínimo, residencia y revisión de elegibilidad.
- Un proyecto por regla aprobada, borrador, integrantes básicos, anexos privados y envío versionado/idempotente.
- Backoffice de revisión, asignaciones, conflicto, rúbrica y evaluación.
- Auditoría esencial y notificaciones transaccionales críticas.
- Reportes operativos mínimos; declaración de ganador separada y publicación apagada.
- QA, accesibilidad crítica, backup/restore, AWS y soporte de cierre.

### Recorte automático si hay desvío

1. Editores de contenido y Quill: despliegue por código.
2. Equipos complejos: representante + listado, sin flujo de aceptación si legal lo permite.
3. Exportaciones asíncronas avanzadas: CSV limitado y auditado.
4. Bandeja ARCO completa: procedimiento manual trazable.
5. Recordatorios sofisticados: ejecución manual autorizada.
6. Dashboards/gráficas: indicadores numéricos.
7. Publicación de resultados: permanece apagada.

Galería, API móvil, marketing, analítica, CMS general, S3/RDS si no son indispensables y automatización avanzada quedan fuera del MVP.

## Cronograma paralelo

~~~mermaid
gantt
    title Flower Flow MVP propuesto
    dateFormat  YYYY-MM-DD
    axisFormat  %d-%b
    section Base
    M0 decisiones y entorno       :crit, m0, 2026-07-15, 3d
    M1 baseline y plantilla       :crit, m1, after m0, 3d
    section Seguridad
    M2 auth RBAC seguridad        :crit, m2, 2026-07-18, 7d
    section Público y recepción
    M3 público y legales          :m3, 2026-07-20, 5d
    M4 perfil y elegibilidad      :crit, m4, 2026-07-23, 6d
    M5 proyecto archivos envío    :crit, m5, 2026-07-25, 9d
    section Operación
    M6 backoffice revisión        :crit, m6, 2026-07-29, 6d
    M7 jueces evaluación          :crit, m7, 2026-07-31, 6d
    M8 comunicación reportes      :m8, 2026-08-02, 4d
    section Salida
    M9 UAT hardening deploy       :crit, m9, 2026-08-03, 5d
    M10 estabilización            :m10, 2026-08-08, 7d
~~~

Las fechas se solapan deliberadamente. Cada carril trabaja en archivos/módulos propios y sólo integra contratos aprobados.

## Ruta crítica

M0 decisiones/acceso -> baseline instalable -> autenticación/RBAC -> convocatoria/legal -> elegibilidad -> envío versionado/archivos -> backoffice -> evaluación -> UAT/restore -> producción.

Bloqueos externos de la ruta: licencia Materialize, textos legales, reglas de elegibilidad/equipos, límites de archivo, rúbrica, SMTP y acceso/inventario EC2.

## Milestones

### M0 — Auditoría, decisiones y entorno

- **Objetivo/valor:** convertir el starter en una baseline reproducible y aprobar reglas que desbloquean diseño.
- **Actores:** equipo técnico, producto/legal, operación.
- **Historias:** como desarrollador quiero instalar exactamente las mismas dependencias; como operación quiero aislar Flower Flow de Administratec.
- **Reglas:** cero datos reales; cero secretos versionados; MySQL local sólo sandbox; no tocar EC2 sin preflight/aprobación.
- **Tareas:** inicializar control de versiones autorizado; preservar licencia; instalar/fijar PHP y JS; elegir package manager; crear entorno local; completar .env.example sin secretos; migrar tablas base en sandbox; validar Apache/Vite; inventariar EC2; cerrar decisiones P0.
- **Artefactos probables:** composer.lock, lock JS elegido, .env.example, README, configuración local y CI; sin dominio todavía.
- **Dependencias:** acceso a origen/licencia y EC2; PHP 8.3, Composer moderno, Node 20.
- **Riesgos:** dependencias incompatibles, assets rotos, acceso AWS tardío.
- **Estimación:** 4 a 6 días-persona.
- **Responsables:** backend + DevOps + producto.
- **Aceptación:** .env ignorado; instalación reproducible; MySQL conectado; migraciones base, tests, build y shell HTTP verdes; inventario EC2 firmado.
- **Validación:** composer validate/install/audit, artisan about/route:list/test, Pint, build, smoke /up.
- **Rollback:** borrar entorno generado y restaurar manifests/locks revisados; no revertir docs aprobadas.
- **Terminado:** evidencia en ExecPlan y decisiones P0 cerradas.

### M1 — Base, branding y limpieza de plantilla

- **Objetivo/valor:** shell coherente Flower Flow, pequeño y accesible.
- **Actores:** todos.
- **Historias:** navegación clara pública/autenticada; errores con marca sin filtrar detalles.
- **Reglas:** overrides fuera del core; assets sólo autorizados; customizer/demos no productivos.
- **Tareas:** tokens naranja/crema/carbón con contraste; layout front/vertical; español; navbar/footer/meta/robots; menús por rol; páginas 404/419/429/500; eliminar rutas demo y entradas Vite no usadas tras verificar.
- **Artefactos:** SCSS/JS Flower Flow, layouts/componentes, config, menús, errores.
- **Dependencias:** M0 y asset/logo autorizado.
- **Riesgos:** romper imports del proveedor o licencia.
- **Estimación:** 4 a 5 días-persona.
- **Responsables:** frontend + backend.
- **Aceptación:** sin Pixinvent visible, customizer off en production, no enlaces rotos, navegación teclado y build verde.
- **Validación:** build, smoke responsive, axe/Lighthouse y revisión visual.
- **Rollback:** revertir sólo overrides/menús, conservar core.
- **Terminado:** template-overrides actualizado.

### M2 — Autenticación, RBAC y seguridad base

- **Objetivo/valor:** identidades reales y acceso de mínimo privilegio.
- **Actores:** participante y roles privilegiados.
- **Historias:** registro/verificación/reset; invitación de roles; 2FA; suspensión y revocación.
- **Reglas:** respuestas neutras, rate limits, roles.manage exclusivo, Policies siempre.
- **Tareas:** aprobar/instalar auth y RBAC; endpoints POST; Form Requests; email verification; invitations; 2FA; roles/permisos seed sintético; middleware/Policies; comando superadmin; headers y session hardening.
- **Artefactos:** users/profile mínimo, roles/permisos, invitations, controllers/requests/policies/tests.
- **Dependencias:** M0, SMTP de prueba y decisión paquetes.
- **Riesgos:** template demo/Jetstream no instalado; escalada o bloqueo de acceso.
- **Estimación:** 8 a 10 días-persona.
- **Responsables:** backend A + QA; frontend apoya formularios.
- **Aceptación:** matriz RBAC crítica y pruebas IDOR verdes; 2FA privilegiado; revocación efectiva.
- **Validación:** feature tests por rol, rate limit, reset enumeration, browser auth.
- **Rollback:** feature flag de registro/invitación; conservar usuario admin de recuperación auditado.
- **Terminado:** threat controls T01/T02/T10/T12 cubiertos.

### M3 — Sitio público, convocatoria y legales

- **Objetivo/valor:** informar reglas exactas y capturar aceptación versionada.
- **Actores:** visitante/participante/admin contenido.
- **Historias:** consultar fechas, categorías, bases, privacidad y contacto; aceptar versión vigente.
- **Reglas:** no inventar premio/Apple; resultados off; noindex fuera del público; documentos inmutables tras publicar.
- **Tareas:** competitions/categories; páginas inicio/bases/FAQ/contacto/privacidad; legal_documents/acceptances; calendario servidor; SEO básico; carga por código salvo CMS aprobado.
- **Artefactos:** modelos/migrations, views front, Policies/admin mínimo y tests.
- **Dependencias:** textos legales, fechas y categorías aprobados; M1/M2.
- **Riesgos:** cambio legal tardío.
- **Estimación:** 5 a 7 días-persona.
- **Responsables:** backend B + frontend + producto/legal.
- **Aceptación:** versión/hash visibles, aceptación exacta y cierre en Hermosillo probado.
- **Validación:** tests zona/estado/legal, SEO/noindex, teclado/móvil.
- **Rollback:** despublicar contenido por flag; no borrar aceptaciones.
- **Terminado:** documentos aprobados cargados y trazables.

### M4 — Perfil, residencia y elegibilidad

- **Objetivo/valor:** aceptar sólo participantes elegibles sin exponer PII.
- **Actores:** participante, reviewer, admin.
- **Historias:** perfil mínimo, upload privado, estado de revisión y corrección.
- **Reglas:** datos sintéticos en test; jueces jamás acceden; decisión versionada.
- **Tareas:** participant_profiles, residency_documents, eligibility_reviews; pipeline upload; descarga autorizada; estados/corrección; masking/audit; cuotas y retención.
- **Artefactos:** storage privado, requests/policies/actions, vistas y tests.
- **Dependencias:** reglas/comprobantes/retención aprobados; M2.
- **Riesgos:** MIME hostil, fuga, storage insuficiente.
- **Estimación:** 7 a 9 días-persona.
- **Responsables:** backend A + QA/security + frontend.
- **Aceptación:** acceso cruzado imposible en pruebas; revisor decide; juez recibe 403; archivos fuera de public.
- **Validación:** matriz upload/download, fake storage, MIME/size, audit.
- **Rollback:** cerrar uploads por flag; preservar evidencia existente privada.
- **Terminado:** restore/borrado y retención ensayados.

### M5 — Wizard, equipos, archivos y envío

- **Objetivo/valor:** recibir proyectos íntegros, recuperables y auditables.
- **Actores:** participante/equipo.
- **Historias:** borrador/autosave, integrantes, anexos, vista previa, envío único y folio.
- **Reglas:** email/elegibilidad/legal vigentes; deadline servidor; snapshot; idempotencia.
- **Tareas:** submissions, versions, members, files, histories; wizard accesible; autosave con control de versión; resumen; SubmitSubmission transaccional; folio; notificación.
- **Artefactos:** modelos/migrations/actions/policies/requests/views/JS y tests.
- **Dependencias:** M2/M3/M4 y límites/reglas de equipo.
- **Riesgos:** pérdida de borrador, doble envío, carrera al cierre.
- **Estimación:** 11 a 14 días-persona.
- **Responsables:** backend B + frontend + QA.
- **Aceptación:** enviar una vez crea un folio/snapshot; reintento no duplica; cierre bloquea; wizard usable móvil/teclado.
- **Validación:** unit/feature/concurrency/browser, archivos y timezone.
- **Rollback:** desactivar envío/autosave; snapshots ya enviados permanecen inmutables.
- **Terminado:** recorrido participante E2E verde.

### M6 — Backoffice y revisión

- **Objetivo/valor:** operar volumen con trazabilidad.
- **Actores:** admin, reviewer, auditor.
- **Historias:** listar/filtrar, asignar revisión, solicitar corrección y exportar sólo columnas permitidas.
- **Reglas:** server-side, queries autorizadas/indexadas, no N+1, before/after redactado.
- **Tareas:** dashboards mínimos; DataTables server-side; filtros; notas; transiciones; excepciones; export CSV limitado; audit views.
- **Artefactos:** query services/endpoints/views/policies/tests.
- **Dependencias:** M4/M5 y volúmenes esperados.
- **Riesgos:** export/consulta filtra PII o lentitud.
- **Estimación:** 7 a 9 días-persona.
- **Responsables:** backend A + frontend + QA.
- **Aceptación:** p95 objetivo con datos sintéticos; cada rol ve sólo su alcance; export auditado.
- **Validación:** EXPLAIN, query count, feature/access/export tests.
- **Rollback:** deshabilitar export/listados no críticos sin afectar envíos.
- **Terminado:** procedimiento operativo ensayado.

### M7 — Jueces, rúbrica y evaluación

- **Objetivo/valor:** evaluar de forma ciega, consistente y auditable.
- **Actores:** juez, admin, auditor.
- **Historias:** ver asignados, declarar conflicto, guardar borrador, enviar evaluación y reabrir excepcionalmente.
- **Reglas:** sólo asignados; conflicto bloquea; total servidor; ranking global oculto.
- **Tareas:** judge_profiles, assignments, conflicts, rubrics/criteria, evaluations/scores; proyección ciega; transitions; cálculo; confirmación/reopen.
- **Artefactos:** dominio y panel de juez separado, policies/tests.
- **Dependencias:** rúbrica/número jueces/empates aprobados; M2/M5/M6.
- **Riesgos:** identidad accidental, cálculo o reasignación incorrectos.
- **Estimación:** 9 a 12 días-persona.
- **Responsables:** backend B + frontend + QA/security.
- **Aceptación:** juez no asignado/conflictuado recibe 403; total exacto; submitted bloqueado; reopen auditado.
- **Validación:** matrices por rol/estado, cálculo límite, browser de juez.
- **Rollback:** pausar asignaciones/evaluación; conservar borradores.
- **Terminado:** evaluación ciega E2E con datos sintéticos.

### M8 — Decisiones, comunicaciones, reportes y auditoría

- **Objetivo/valor:** cerrar operación sin publicar prematuramente.
- **Actores:** admin, auditor, participantes.
- **Historias:** declarar ganador con razón; enviar notificaciones críticas; consultar bitácora.
- **Reglas:** cálculo no declara ganador; publicación off; correo sin PII sensible; reintentos idempotentes.
- **Tareas:** winner_decisions; notifications/templates; delivery logs; reportes mínimos; failed jobs; flags.
- **Artefactos:** acciones, jobs, mailables, views y tests.
- **Dependencias:** SMTP y reglas de empate/publicación; M6/M7.
- **Riesgos:** correo duplicado, resultado incorrecto/prematuro.
- **Estimación:** 6 a 8 días-persona.
- **Responsables:** backend A + QA + producto.
- **Aceptación:** doble confirmación, no publicación, correos fake/idempotencia y audit log.
- **Validación:** notification fakes, queue retries, permisos/exports.
- **Rollback:** detener worker/flags; revocar decisión con evento, no borrar.
- **Terminado:** runbook de comunicación y fallos probado.

### M9 — QA, accesibilidad, hardening, rendimiento y despliegue

- **Objetivo/valor:** demostrar que el MVP puede operar y recuperarse.
- **Actores:** todos y operación.
- **Historias:** UAT por rol; operación despliega/restaura sin improvisar.
- **Reglas:** detener y reparar; no datos reales fuera de producción; backup antes de migrar.
- **Tareas:** regresión/E2E; WCAG teclado/contraste; dependency/secret scan; carga; headers/TLS; backup/restore; staging; UAT; deploy EC2 y smoke con aprobación.
- **Artefactos:** evidencia QA, checklist, release, backup y rollback.
- **Dependencias:** M0-M8, acceso EC2 y aprobaciones.
- **Riesgos:** falta de staging/capacidad o defectos P0.
- **Estimación:** 9 a 12 días-persona.
- **Responsables:** QA + DevOps + equipo completo.
- **Aceptación:** suites/build verdes, cero P0/P1 abierto, restore exitoso, UAT firmada.
- **Validación:** docs/08-testing-qa.md y runbook AWS.
- **Rollback:** release anterior + restore sólo si migración no compatible; modo mantenimiento.
- **Terminado:** smoke externo y monitoreo estable.

### M10 — Estabilización y soporte al cierre

- **Objetivo/valor:** reducir riesgo operativo hasta el deadline.
- **Actores:** participantes, soporte, operación.
- **Historias:** soporte responde incidentes; admins ven colas/espacio/errores.
- **Reglas:** freeze funcional; cambios sólo por severidad y aprobación.
- **Tareas:** monitoreo, triage, capacity checks, failed jobs, entregabilidad, backups más frecuentes, simulacro de cierre, reporte diario.
- **Artefactos:** bitácora de operación e incidentes.
- **Dependencias:** producción estable.
- **Riesgos:** pico de última hora y soporte insuficiente.
- **Estimación:** 7 días calendario de guardia; 5 a 8 días-persona.
- **Responsables:** operación + soporte + guardia técnica.
- **Aceptación:** alarmas, owners y escalamiento; backup reciente; cierre probado.
- **Validación:** health, cola, disco, mail y smoke automatizado.
- **Rollback:** modo degradado/controlado y procedimiento de incidente.
- **Terminado:** convocatoria cerrada con evidencia y sin pérdida.

## Carriles sin solapamiento

| Carril | Propietario | Áreas principales |
|---|---|---|
| A Identity/eligibility/admin | Backend A | Auth, Profiles, Eligibility, Admin queries |
| B Submission/judging | Backend B | Submissions, Files contracts, Judging |
| C UI/accessibility | Frontend | layouts, components, page JS/SCSS |
| D QA/security | QA | tests, fixtures sintéticos, E2E y evidencia |
| E Platform | DevOps | local baseline, EC2, workers, backups, monitoring |

Contratos de modelos, enums, rutas y componentes se acuerdan antes del paralelo. Migraciones las integra una sola persona en orden serial.

## Puertas de decisión

- **2026-07-16:** confirmar especificación completa, licencia, calendario, mayoría de edad, categorías y premio.
- **2026-07-17:** confirmar equipos, archivos, residencia/retención, rúbrica/jueces/empates.
- **2026-07-18:** confirmar SMTP, paquetes auth/RBAC, EC2/staging/backups.
- **2026-07-25:** go/no-go para evaluación integrada.
- **2026-08-01:** go/no-go para UAT y recorte final.
- **2026-08-06:** aprobación UAT/deploy.
- **2026-08-07:** freeze.

## Definición global de terminado

- Criterios funcionales y seguridad por rol pasan.
- Build/tests/auditorías disponibles en verde.
- WCAG crítica revisada manualmente.
- No hay PII/secreto en fixtures, logs o artefactos.
- Migraciones y rollback/compatibilidad revisados.
- Backup restaurado.
- Documentación/traceability/ExecPlan actualizados.
- UAT y aprobación de producción explícitas.
