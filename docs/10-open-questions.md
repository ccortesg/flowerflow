# Preguntas abiertas y decisiones — Flower Flow 2026

> **Decisiones del propietario — 2026-08-18:** se conserva sin cambios la superposición temática de accesibilidad de la Mecánica v1.1; las cuentas que aceptaron v1.0 se tratan operativamente como aceptantes de v1.1 sin forzar reaceptación ni alterar evidencia histórica; y el archivo físico v1.0 designado por el propietario es `public/documentos/2026/01_Mecanica_Convocatoria_Hermosillo_Florece_2026.pdf`. La discrepancia entre su SHA-256 actual `3bcf31…` y el hash histórico registrado `42bd5e…` se conserva documentada, no se oculta ni se corrige mediante backfill.

> **Estado productivo y puerta actual — 2026-08-18:** el propietario registra `OWNER_CONFIRMED_DEPLOYED` y más de 50 propuestas reales. Sin evidencia externa en esta tarea, `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR`; M1/M2 se validaron sólo localmente y no se atribuyen a producción. Las decisiones 02B, incluida la corrección de capacidad/sustitución, están `OWNER_APPROVED`; M3 es la siguiente puerta. `P2B-BLOCK-001` está resuelto y M4 permanece no implementado/no autorizado.

> **Auditoría integral 2026-08-17:** Fase 01/02A está implementada en repositorio, pero el runtime local primario no está alineado: límite tres frente a cuatro y cuatro migraciones funcionales pendientes. Jueces/evaluación y resultados siguen bloqueados por decisiones de negocio. Ver `docs/16-project-status-by-module-and-role-2026-08-17.md`.

## Resoluciones Fase 01 — 2026-07-15

Resueltas: destino AWS EC2 Ubuntu (no GoDaddy), host/panel, MySQL local, cierre y zona, categorías, límites de equipo/propuestas/archivos, Fortify, Spatie, Quill+sanitizer, flags y formato de folio/snapshot.

Siguen abiertas: hora exacta de apertura; fecha de salida; licencia Pixinvent; aceptación de integrantes y persona en varios equipos; WhatsApp; proveedores adicionales; cantidad máxima definitiva de archivos e imágenes; remediación antimalware; SMTP/DNS; EC2/PHP/web server/capacidad; DB productiva; EBS/S3; staging, RPO/RTO, monitoreo y responsables UAT/soporte. Rúbrica, ceguera, conflicto, reapertura, consolidación, retención 02B, señal de empate y capacidad/sustitución quedaron definidos; la resolución de empate/ganadores sigue pendiente. Los puntos jurídicos/evidenciales anteriores quedaron resueltos por decisión expresa del propietario.

**Fecha de corte:** 2026-07-15  
**Uso:** registro de decisiones para planificación y aprobación.  
**Etiquetas:** `DECISION` confirmado; `ASSUMPTION` supuesto recomendado mientras llega respuesta; `PENDING` respuesta/aprobación necesaria.

## Advertencia sobre la fuente

**PENDING:** el input comienza truncado: faltan la introducción y los módulos 1–6. No debe asumirse que la reconstrucción contenida en los documentos actuales sustituye el texto original.

**Recomendación:** solicitar el fragmento completo, comparar requisito por requisito y registrar cualquier diferencia en esta bitácora.

**Impacto:** alto. Puede cambiar actores, campos, flujos, alcance, estimaciones, modelo de datos y pruebas.

## Decisiones ya fijadas

| ID | Estado | Decisión | Consecuencia |
| --- | --- | --- | --- |
| D-001 | DECISION | La fecha de corte documental es 2026-07-15. | Toda versión/fuente debe registrar su fecha. |
| D-002 | DECISION | El MVP prioriza recibir, revisar y evaluar antes del 2026-08-15. | Funciones no críticas se recortan o mueven a fase 2. |
| D-003 | DECISION | Zona de presentación: `America/Hermosillo`; persistencia planificada en UTC. | Fechas, tests y scheduler usan zona explícita. |
| D-004 | DECISION | Base local/de pruebas MySQL en `127.0.0.1:3306`, base `flowerflow`, usuario `flowerflow_user`. | El entorno debe documentar estas variables. |
| D-005 | DECISION | La contraseña MySQL se proporcionó fuera del repositorio y sólo se usa en `.env` local. | No se escribe el valor en docs, `.env.example`, comandos, logs o fixtures. |
| D-006 | DECISION | Destino productivo: AWS EC2 Ubuntu compartida/coexistente con `administratec`. | Se exige inventario e aislamiento de sitios/procesos/datos antes de desplegar. |
| D-007 | DECISION | No se implementa ni despliega durante la fase de planificación. | El ExecPlan y las aprobaciones preceden todo cambio funcional. |
| D-008 | DECISION | No existe selección aleatoria. | El sistema no incluye sorteos. |
| D-009 | DECISION | El cálculo no declara ganador; la decisión es administrativa y auditable. | Permisos y eventos separados. |
| D-010 | DECISION | Resultados públicos desactivados por defecto. | Publicar requiere acción y permiso específicos. |
| D-011 | DECISION | La galería pública es fase 2 por defecto. | No entra en ruta crítica. |
| D-012 | DECISION | API móvil/integraciones externas quedan fuera del MVP por defecto. | No diseñar contratos externos en la ruta crítica. |
| D-013 | DECISION | Código en inglés e interfaz en español. | Nombres técnicos y contenidos se gestionan por separado. |
| D-014 | DECISION | No usar datos reales en desarrollo/pruebas. | Factories/fixtures sintéticos y redactados. |
| D-015 | RESOLVED POR v1.1 | El cierre se amplía al 2026-08-23 23:59:59 en Hermosillo. | Mecánica v1.1 p. 3 y Términos v1.1 p. 2 ya coinciden con configuración/base/UI; v1.0 queda histórico. |
| D-016 | DECISION DE AUDITORÍA | Separar avance del producto maestro, código local verificado, runtime activado y producción demostrada. | Ningún porcentaje o handoff puede presentar una suite verde como despliegue o acceso habilitado. |
| D-017 | SUPERSEDED 2026-08-18 | La siguiente puerta era un release candidate/UAT local de lo ya implementado. | Se conserva como secuencia histórica; fue sustituida por D-024. |
| D-018 | DECISION TÉCNICA LOCAL | Nuevas altas, cambios de consentimiento y envíos registran la versión jurídica activa v1.1; v1.0 y sus aceptaciones no se mutan. | Catálogo determinístico, migración reversible y tests de preservación. |
| D-019 | RESOLVED / OWNER DECISION 2026-08-18 | No se fuerza reaceptación v1.1 a cuentas que aceptaron v1.0; se consideran operativamente aceptantes de v1.1 porque los cambios fueron comunicados. | No bloquear cuentas ni propuestas y no fabricar, duplicar o modificar filas históricas de `legal_acceptances`; las nuevas aceptaciones continúan registrando v1.1. |
| D-020 | DECISION / RESOLVED LEGAL | La operación conserva cuatro categorías y máximo cuatro propuestas, una por categoría. | El propietario lo confirmó y la Mecánica v1.1 corregida lo respalda en pp. 2–3; no requiere rollback funcional. |
| D-021 | RESOLVED / OWNER DECISION 2026-08-18 | La referencia a accesibilidad se conserva como está en las dos categorías. | No recategorizar propuestas, cambiar UI ni reinterpretar la Mecánica. |
| D-022 | RESOLVED / OWNER DECISION 2026-08-18 | El PDF físico v1.0 que debe conservarse es `01_Mecanica_Convocatoria_Hermosillo_Florece_2026.pdf`, SHA-256 actual `3bcf31…`. | Mantener la discrepancia histórica `42bd5e…` en registros/aceptaciones; no sustituir el archivo ni reescribir evidencia. |
| D-023 | OWNER CONFIRMED 2026-08-18 | Producción usa un checkout Git directo en `/var/www/flowerflow`; no existen `releases/current/shared`. El propietario informa que el VirtualHost `app.sguniformes.com.mx` apunta a esa ruta. | El update actual trabaja en sitio por SHA exacto. La topología de symlinks queda futura; no se infiere un cambio del host canónico ni se modifica Apache. |
| D-024 | `OWNER_CONFIRMED_DEPLOYED` 2026-08-18 | El propietario confirma que instaló los cambios actuales y que la plataforma conserva más de 50 propuestas reales. | Actualiza la puerta operativa, pero `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR` y no demuestra migraciones, flags, servicios, integridad o UAT productiva. |
| D-025 | SUPERSEDED 2026-08-18 | La siguiente puerta era cerrar las 21 decisiones del paquete Fase 02B. | La respuesta del propietario la sustituyó por D-026. |
| D-026 | OWNER_APPROVED / DESIGN BASELINE 2026-08-18 | Las 21 decisiones Fase 02B están cerradas conforme a ADR-0008. | Fue la base histórica para autorizar M1; el estado vigente se registra en D-028. |
| D-027 | SUPERSEDED / RESOLVED 2026-08-18 | `P2B-BLOCK-001`: cobertura de cuatro evaluaciones, cuatro jueces disponibles, capacidad ocho y sustitución a otro juez eran incompatibles con más de 50 propuestas. | Sustituida por D-031; se conserva como hallazgo histórico. |
| D-028 | VERIFIED LOCAL 2026-08-18 | M1 implementa rol `judge`, permiso exclusivo, gates exactos, flag fail-closed, redirección segura y shell vacío sin datos. | Se conserva como baseline de aislamiento; no demuestra despliegue productivo. |
| D-029 | VERIFIED LOCAL 2026-08-18 | M2 implementa `judge_profiles`, alta directa admin, credencial propia, activación derivada, suspensión/reactivación, revocación de sesiones y recovery 2FA administrativo. | Sólo local/test; no crea asignaciones, no exige 2FA y no acredita producción. |
| D-030 | READY FOR EXPLICIT AUTHORIZATION 2026-08-18 | La siguiente puerta propuesta es M3, limitada a la rúbrica global versionada aprobada. | Usar el prompt canónico de `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`; M4 espera M3 verde y autorización propia. |
| D-031 | OWNER_APPROVED / `P2B-BLOCK-001 RESOLVED` 2026-08-18 | Cuatro jueces principales evaluarán todas las propuestas elegibles sin límite fijo; el quinto será exclusivamente sustituto con máximo diez reasignaciones activas. | Perfil M2 `primary=NULL`/`substitute=10`; M4 debe impedir carga inicial al sustituto y rechazar la undécima sustitución activa. |

## Decisiones jurídicas prioritarias v1.1

| ID | Estado | Pregunta | Alternativas y recomendación | Impacto |
|---|---|---|---|---|
| Q-LEGAL-001 | RESOLVED / OWNER ACCEPTED 2026-08-18 | ¿La referencia a “accesibilidad” en Movilidad con Flow es deliberada aunque exista Hermosillo sin Barreras? | Sí; se conserva como está, sin recategorización ni cambio funcional. | Sin pendiente operativo. |
| Q-LEGAL-002 | RESOLVED / OWNER DECISION 2026-08-18 | ¿Quién debe reaceptar v1.1 y desde cuándo? | No se fuerza reaceptación a cuentas v1.0; se aplica equivalencia operativa sin alterar evidencia y las nuevas aceptaciones registran v1.1. | No requiere migración ni backfill de `legal_acceptances`. |
| Q-LEGAL-003 | RESOLVED / OWNER DESIGNATION 2026-08-18 | ¿Qué binario se conserva como Mecánica v1.0? | El archivo físico actual `01_Mecanica_Convocatoria_Hermosillo_Florece_2026.pdf` (`3bcf31…`); el hash `42bd5e…` permanece como antecedente histórico. | Discrepancia histórica aceptada y visible; no bloquea la continuidad operativa. |

## Preguntas prioritarias

### Especificación, producto y calendario

| ID | Estado | Pregunta | Recomendación / supuesto de trabajo | Impacto si cambia |
| --- | --- | --- | --- | --- |
| Q-001 | RESOLVED 2026-07-15 | ¿Dónde está la introducción y el contenido original de módulos 1–6? | El prompt Fase 01 v2 es la fuente completa para esta fase. | Sin impacto pendiente en recepción. |
| Q-002 | PENDING | ¿Quién tiene autoridad final para aprobar alcance, UAT, textos legales y producción? | Nombrar una persona de producto y una legal; separar aprobación técnica de publicación. | **Crítico:** bloquea decisiones y salida. |
| Q-003 | PENDING | ¿Cuál es la fecha deseada de lanzamiento público? | Salir con margen de al menos 7–10 días antes del cierre, sujeto a la ruta crítica real. | **Crítico:** determina calendario, recortes y soporte. |
| Q-004 | PENDING | ¿Cuál es la fecha/hora de apertura? | No abrir hasta completar UAT, backup/restauración y smoke test. | Alto: afecta estados, scheduler y comunicación. |
| Q-005 | SUPERSEDED 2026-08-17 | ¿Cuál era la hora exacta de cierre del 2026-08-15? | `23:59:59 America/Hermosillo`, inclusivo; sustituido por la ampliación siguiente. | Se conserva como decisión histórica. |
| Q-005-EXT | RESOLVED 2026-08-17 | ¿Cuál es el nuevo cierre de la convocatoria? | `2026-08-23 23:59:59 America/Hermosillo`, inclusivo; persistir `2026-08-24 06:59:59 UTC`. | Implementar config/base/UI y prueba de frontera; PDF jurídicos pendientes. |
| Q-006 | PENDING | ¿Habrá periodo de gracia o excepciones administrativas? | Sin gracia automática; sólo excepción individual, justificada, autorizada y auditada. | Alto: estados, permisos y legalidad operativa. |
| Q-007 | PENDING | ¿Qué alcance puede recortarse si el calendario no es viable? | Recortar resultados públicos, CMS, dashboards, exports avanzados y colaboración compleja antes que seguridad/revisión/evaluación. | **Crítico:** viabilidad del MVP. |

### Plantilla, licencia y branding

| ID | Estado | Pregunta | Recomendación / supuesto de trabajo | Impacto si cambia |
| --- | --- | --- | --- | --- |
| Q-008 | PENDING | ¿Materialize 3.0.0 es starter kit o full version? | Tratar el repositorio como starter funcional hasta inventariar páginas/assets. | Medio: limpieza, esfuerzo y componentes disponibles. |
| Q-009 | PENDING | ¿Qué licencia comercial se adquirió y para qué dominio/proyecto? | Verificar comprobante y alcance antes de publicar o copiar componentes. | Alto: riesgo de licencia. |
| Q-010 | PENDING | ¿Existen logo, fotografías, tipografías y manual de marca autorizados? | Usar placeholders identificados; no extraer assets del cartel. | Medio: fidelidad visual y calendario. |
| Q-011 | PENDING | ¿El premio y cualquier referencia a Apple/iPad cuentan con texto y assets autorizados? | Mostrar sólo texto aprobado; no usar logos/imágenes de producto. | Alto: legal y marca. |

### Participantes, equipos y elegibilidad

| ID | Estado | Pregunta | Recomendación / supuesto de trabajo | Impacto si cambia |
| --- | --- | --- | --- | --- |
| Q-012 | PENDING | ¿Se confirma participación sólo de mayores de 18 años? | Asumir mayores de edad hasta recibir reglas finales; no codificar fecha de corte sin aprobación. | **Crítico:** datos, consentimiento y elegibilidad. |
| Q-013 | PENDING | ¿Se permite participación individual, equipos o ambos? | Modelar propietario + integrantes opcionales, activados por configuración. | Alto: wizard, permisos y modelo de datos. |
| Q-014 | PENDING | ¿Máximo cinco integrantes? | No fijar el número; usar límite configurable después de aprobación. | Medio: validación y UX. |
| Q-015 | PENDING | ¿Todos los integrantes se registran y aceptan documentos o basta el representante? | Requerir invitación/aceptación individual si hay derechos/consentimientos personales. | **Crítico:** legal, identidad y envío. |
| Q-016 | PENDING | ¿Qué comprobantes de residencia se aceptan, vigencia y criterios de revisión? | Allowlist explícita aprobada; nunca inferir desde archivos genéricos. | **Crítico:** elegibilidad y uploads. |
| Q-017 | PENDING | ¿Cuánto se conservan comprobantes y demás PII? | Definir tabla de retención por entidad antes de producción; minimizar y eliminar de forma controlada. | **Crítico:** privacidad, backups y borrado. |
| Q-018 | PENDING | ¿Cuántos proyectos puede enviar una persona/equipo y por categoría? | Límites configurables y validados transaccionalmente al enviar. | Alto: reglas, UI y concurrencia. |
| Q-019 | PENDING | ¿Se permite retirar un proyecto y hasta cuándo? | Permitir retiro antes de evaluación; después, sólo proceso administrativo auditado. | Medio: estados y reportes. |

### Proyecto, contenido y archivos

| ID | Estado | Pregunta | Recomendación / supuesto de trabajo | Impacto si cambia |
| --- | --- | --- | --- | --- |
| Q-020 | PENDING | ¿Cuáles son campos y límites de texto definitivos? | Configuración central por convocatoria; contador accesible y validación de servidor. | Alto: modelo, wizard y tests. |
| Q-021 | PENDING | ¿Cuántos anexos, de qué tipo y tamaño? | Allowlist mínima; cuotas por proyecto; archivos privados; validar MIME/firma. | **Crítico:** seguridad, disco y UX. |
| Q-022 | PENDING | ¿Se requiere video o enlaces externos? | Excluir upload de video del MVP; aceptar enlaces sólo con sanitización si se aprueba. | Alto: almacenamiento, seguridad y evaluación. |
| Q-023 | DECISION TEMPORAL | La recepción puede abrirse temporalmente sin motor antimalware por aceptación expresa del owner el 2026-07-15. | Mantener controles de formato, firma, cuota y privacidad; evaluar ClamAV en EC2 y documentar cuarentena/fallback. La aceptación no equivale a riesgo resuelto. | Alto: seguridad y capacidad; revisión obligatoria. |
| Q-024 | PENDING | ¿Los documentos Word finales ya están en el repositorio? | No crear textos legales ficticios; solicitar versiones aprobadas y hashes. | **Crítico:** apertura y aceptaciones. |
| Q-025 | PENDING | ¿La administración editará contenido o basta despliegue por código? | Contenido por código para MVP; CMS sólo si un caso operativo aprobado lo exige. | Medio: alcance, XSS y mantenimiento. |

### Jueces, rúbrica y decisión

| ID | Estado | Pregunta | Recomendación / supuesto de trabajo | Impacto si cambia |
| --- | --- | --- | --- | --- |
| Q-026 | RESOLVED / OWNER_APPROVED | Rúbrica completa, escala, pesos y comentarios. | Pertinencia 20, Claridad 20, Viabilidad 25, Impacto 25, Coherencia 10; escala 0–10/paso 0.5; comentario general 100–2,000 y por criterio opcional hasta 1,000. | Contrato ADR-0008; implementación M3/M6. |
| Q-027 | RESOLVED / UPDATED 2026-08-18 | Cuatro principales cubren todas las propuestas elegibles sin límite fijo; quinto sustituto exclusivo con máximo diez activas; sin especialidad. | Implementar exactamente en M4, sin carga inicial al sustituto. | Contrato compatible; flujo pendiente. |
| Q-028 | RESOLVED / RISK ACCEPTED | Ceguera simple estructural; todos los campos sustantivos/anexos evaluables; PII estructurada/residencia/notas/aclaraciones/historial ocultos. | Automatización estructural y nombres/metadatos limpios; autoidentificación dentro del contenido aceptada sin bloqueo. | Implementación M5 y comunicación honesta del riesgo. |
| Q-029 | PARTIAL RESOLVED / RESULTADOS FUERA DE ALCANCE | Conflictos con catálogo cerrado y reemplazo por admin al quinto sustituto; empate técnico por igualdad a dos decimales. | Resolución del empate/ganador queda fuera de 02B y nunca usa azar; reemplazo admite máximo diez activos. | Consolidación definida; ganador pendiente. |
| Q-030 | PENDING | ¿Se puede declarar una categoría desierta? | Permitirlo con permiso, razón y acta/registro. | Alto: estados y publicación. |
| Q-031 | RESOLVED / OWNER_APPROVED | Reapertura por `admin` hasta 20:00, razón 20–1,000, password confirmation y revisión append-only editable hasta 23:59:59; admin puede editar en nombre del juez con actor real. | Implementar sin sobrescribir ni suplantar autoría. | Alto: integridad/auditoría en M7. |
| Q-032 | RESOLVED / UPDATED 2026-08-18 | Catálogo personal/familiar, profesional/económico, participación u otro explicado; admin resuelve y reasigna manualmente al quinto sustituto. | Varias asignaciones por propuesta; sustituto sólo reemplaza y tiene máximo diez activas. | Alto de implementación/concurrencia; decisión cerrada. |

### Premio, publicación y comunicación

| ID | Estado | Pregunta | Recomendación / supuesto de trabajo | Impacto si cambia |
| --- | --- | --- | --- | --- |
| Q-033 | PENDING | ¿Se confirma un premio por categoría y cuál es su descripción exacta? | No inventar cantidad/modelo; usar constante aprobada. | Alto: contenido y legal. |
| Q-034 | PENDING | ¿Qué datos de ganadores se publicarán y con qué autorización? | Mínimo: proyecto/categoría/resumen; nombre sólo con base legal/consentimiento aprobado. | **Crítico:** privacidad y resultados. |
| Q-035 | PENDING | ¿Se requiere acta o PDF de resultados? | Fase 2 salvo obligación legal/operativa; para MVP conservar registro estructurado exportable. | Medio: alcance y firma/archivo. |
| Q-036 | PENDING | ¿Se necesita galería de proyectos? | Mantener en fase 2; requerir consentimiento y moderación. | Medio: alcance y privacidad. |
| Q-037 | PENDING | ¿Qué SMTP/proveedor enviará correo? | Proveedor transaccional con SPF, DKIM, DMARC, webhooks y límites conocidos. | **Crítico:** verificación y notificaciones. |
| Q-038 | PENDING | ¿Quién monitorea rebotes, jobs fallidos y buzones funcionales? | Asignar dueño operativo y alertas antes de abrir convocatoria. | Alto: soporte y entregabilidad. |
| Q-039 | PENDING | ¿Se requiere CAPTCHA y cuál? | Empezar con rate limits/honeypot; agregar opción accesible sólo si el riesgo lo justifica. | Medio: abuso, privacidad y accesibilidad. |
| Q-040 | PENDING | ¿Se desea analítica y qué proveedor/consentimiento? | Sin analítica no esencial en MVP; evaluar en fase 2. | Bajo/medio: cookies, CSP y privacidad. |

### Privacidad, auditoría y soporte

| ID | Estado | Pregunta | Recomendación / supuesto de trabajo | Impacto si cambia |
| --- | --- | --- | --- | --- |
| Q-041 | PENDING | ¿Formulario de privacidad o sólo recepción por correo? | Bandeja interna mínima que pueda registrar casos de ambos canales. | Medio: alcance y operación. |
| Q-042 | PENDING | ¿SLA, verificación de identidad y responsables de solicitudes? | Definir con asesoría legal; el sistema sólo apoya el proceso. | **Crítico:** privacidad y acceso. |
| Q-043 | RESOLVED 02B / PENDING global | Retención 02B de 24 meses desde `evaluation_cycle_closed_at`; exports, backups y matriz global conservan contratos separados. | Implementar purga sólo en milestone autorizado y compatible con backups/conservación. | **Crítico:** 02B definido; operación global pendiente. |
| Q-044 | PENDING | ¿Qué campos exactos deben redactarse en logs y cambios antes/después? | Denylist/allowlist explícita; nunca documentos, tokens, passwords o contenido sensible completo. | Alto: observabilidad segura. |

### AWS EC2 Ubuntu y operación

| ID | Estado | Pregunta | Recomendación / supuesto de trabajo | Impacto si cambia |
| --- | --- | --- | --- | --- |
| Q-045 | PENDING | ¿Qué versión/tamaño/arquitectura tiene la EC2 y cuánto recurso consume `administratec`? | Inventario y medición antes de elegir SLO o workers. | **Crítico:** capacidad y estabilidad compartida. |
| Q-046 | PENDING | ¿Servidor web, PHP-FPM, extensiones, Composer y Node disponibles? | Crear matriz real; aislar versiones si difieren. | **Crítico:** compatibilidad de Laravel 12/PHP 8.2+. |
| Q-047 | PENDING | ¿Dominio/DNS/TLS y virtual host de Flower Flow? | Vhost propio con document root en `public`, TLS y redirección canónica. | **Crítico:** despliegue y seguridad. |
| Q-048 | PENDING | ¿Base de producción en la EC2, RDS u otro host? | Usuario/esquema exclusivos; preferir servicio separado si presupuesto/operación lo permiten. | **Crítico:** seguridad, backups y rendimiento. |
| Q-049 | PENDING | ¿Cómo se aislarán `administratec` y Flower Flow? | Usuarios, rutas, pools PHP-FPM, vhosts, env, DB, prefijos, storage, workers, scheduler y logs independientes. | **Crítico:** riesgo de impacto cruzado. |
| Q-050 | PENDING | ¿Hay staging separado y quién aprueba UAT? | Staging protegido, `noindex` y datos sintéticos; UAT con responsables por rol. | **Crítico:** calidad de salida. |
| Q-051 | PENDING | ¿Worker persistente y scheduler mediante `systemd`/Supervisor/cron? | Unidades propias de Flower Flow, límites y reinicio controlado. | Alto: correo, exports y cierres. |
| Q-052 | PENDING | ¿Límites de upload, disco y crecimiento esperado? | Presupuesto por proyecto + alarma de disco; no compartir storage sin cuotas. | **Crítico:** disponibilidad cercana al cierre. |
| Q-053 | PENDING | ¿Backups, cifrado, destino externo, RPO/RTO y prueba de restauración? | Backup DB/storage antes de releases y prueba de restauración previa a apertura. | **Crítico:** recuperación. |
| Q-054 | PENDING | ¿Monitoreo y alertas disponibles? | Health check, 5xx, latencia, disco, jobs, correo, CPU/RAM y certificado; dueño de guardia. | Alto: operación. |
| Q-055 | PENDING | ¿Estrategia de release y rollback compatible con `administratec`? | Releases atómicos por ruta/symlink si la infraestructura lo permite; nunca reiniciar servicios compartidos sin evaluación. | **Crítico:** continuidad de ambos proyectos. |

### Calidad y compatibilidad

| ID | Estado | Pregunta | Recomendación / supuesto de trabajo | Impacto si cambia |
| --- | --- | --- | --- | --- |
| Q-056 | PENDING | ¿Navegadores/dispositivos mínimos y usuarios con necesidades de accesibilidad conocidas? | Últimas dos versiones modernas + Safari/iOS/Android; confirmar matriz UAT. | Alto: componentes y pruebas. |
| Q-057 | PENDING | ¿Se aprueba Playwright/Dusk y análisis estático? | Empezar con herramientas existentes; añadir sólo con ADR, compatibilidad y tiempo. | Medio: esfuerzo y cobertura. |
| Q-058 | PENDING | ¿Qué SLOs de disponibilidad/latencia y volumen se esperan? | Definir con métricas de participantes, proyectos, archivos y concurrencia del cierre. | Alto: capacidad e índices. |
| Q-059 | PENDING | ¿Quién atiende soporte durante apertura y cierre? | Calendario de guardia, runbook y escalamiento técnico/producto. | Alto: continuidad. |

## Supuestos operativos vigentes

| ID | Estado | Supuesto | Se invalida cuando |
| --- | --- | --- | --- |
| A-001 | ASSUMPTION | Una sola convocatoria 2026 estará activa en MVP. | Producto requiere operación simultánea de ediciones. |
| A-002 | ASSUMPTION | Evaluación ciega por defecto. | Reglas aprobadas exigen identificar participantes. |
| A-003 | ASSUMPTION | Contenido público por código en MVP. | Se aprueba CMS como requisito de apertura. |
| A-004 | ASSUMPTION | Página pública de resultados puede recortarse sin afectar recepción/evaluación. | Existe obligación contractual de publicar desde el sistema. |
| A-005 | ASSUMPTION | La base local `flowerflow` puede dedicarse a pruebas. | Se descubre que contiene datos no desechables/compartidos. |
| A-006 | ASSUMPTION | AWS EC2 puede alojar workers persistentes propios. | El inventario operativo demuestra restricción. |
| A-007 | ASSUMPTION | Correos se enviarán por proveedor transaccional y no por MTA local. | Operación aprueba otra arquitectura con entregabilidad demostrada. |

## Puertas de aprobación

### Antes de diseñar esquema/migraciones

- Resolver Q-001, Q-012–Q-021, Q-024 y Q-026–Q-032.
- Confirmar que la base local es desechable/aislada.

### Antes de implementar recorridos públicos

- Resolver Q-003–Q-005, Q-009–Q-011, Q-024, Q-033–Q-040.

### Antes de UAT

- Resolver inventario AWS Q-045–Q-055 y compatibilidad Q-056–Q-058.
- Aprobar textos legales, rúbrica, publicación, retención y soporte.

### Antes de producción

- UAT firmado.
- Backup y restauración probados.
- Rollback y smoke tests ensayados.
- Aislamiento con `administratec` verificado.
- Monitoreo/guardia activos.
- Aprobación expresa de producto, legal y responsable técnico.

## Adenda de decisiones Fase 02A — 2026-07-16

### Confirmado por PDF canónico

- 18 años o más; residencia en Hermosillo; individual o equipo de hasta cinco.
- revisión de plazo, categoría, información mínima, archivo abrible y reglas de participación.
- aclaración formal que no sustituye ni mejora materialmente el proyecto.
- identificación con domicilio, comprobante reciente, constancia, arrendamiento o equivalente; se pueden ocultar datos innecesarios.
- motivos de incumplimiento descritos en mecánica/términos se registran como explicación humana, no resolución automática.

### Decisión técnica autorizada

- estados, límites de tres archivos/10 MiB, formatos, feature flag, permisos, backfill, correo resiliente y reporte dry-run.
- revisión separada de `Submission.status` y ligada al snapshot.

### PENDING

- `Q-F2A-001`: ¿cuántos meses, si alguno, definen “comprobante reciente”? Hasta resolver, no rechazar automáticamente.
- `Q-F2A-002`: ¿existe un catálogo/criterio legal para “documento equivalente”? Hasta resolver, justificación manual obligatoria.
- `Q-F2A-003`: ¿qué responsable autoriza la eliminación después de determinar ganadores? Sin esa integración no existe delete/scheduler.
- `Q-F2A-004`: ¿catálogo cerrado/códigos de motivos o texto libre controlado? Fase 02A conserva motivo público obligatorio y notas separadas.
- `Q-F2A-005`: antivirus, cuarentena y procedimiento ante malware para producción.
