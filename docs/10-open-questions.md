# Preguntas abiertas y decisiones — Flower Flow 2026

## Resoluciones Fase 01 — 2026-07-15

Resueltas: destino AWS EC2 Ubuntu (no GoDaddy), host/panel, MySQL local, cierre y zona, categorías, límites de equipo/propuestas/archivos, Fortify, Spatie, Quill+sanitizer, flags y formato de folio/snapshot.

Siguen abiertas y no bloquean código local detrás de flags: hora exacta de apertura; fecha de salida; licencia Pixinvent; aceptación de integrantes y persona en varios equipos; texto v1.1/WhatsApp; proveedores adicionales; cantidad máxima definitiva de archivos e imágenes; remediación antimalware después de la aceptación temporal del riesgo; SMTP/DNS; EC2/PHP/web server/capacidad; DB productiva; EBS/S3; staging, backups, RPO/RTO, monitoreo y responsables UAT/soporte. Rúbrica, desempate, conflictos y anonimización pertenecen a fase posterior.

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

## Preguntas prioritarias

### Especificación, producto y calendario

| ID | Estado | Pregunta | Recomendación / supuesto de trabajo | Impacto si cambia |
| --- | --- | --- | --- | --- |
| Q-001 | RESOLVED 2026-07-15 | ¿Dónde está la introducción y el contenido original de módulos 1–6? | El prompt Fase 01 v2 es la fuente completa para esta fase. | Sin impacto pendiente en recepción. |
| Q-002 | PENDING | ¿Quién tiene autoridad final para aprobar alcance, UAT, textos legales y producción? | Nombrar una persona de producto y una legal; separar aprobación técnica de publicación. | **Crítico:** bloquea decisiones y salida. |
| Q-003 | PENDING | ¿Cuál es la fecha deseada de lanzamiento público? | Salir con margen de al menos 7–10 días antes del cierre, sujeto a la ruta crítica real. | **Crítico:** determina calendario, recortes y soporte. |
| Q-004 | PENDING | ¿Cuál es la fecha/hora de apertura? | No abrir hasta completar UAT, backup/restauración y smoke test. | Alto: afecta estados, scheduler y comunicación. |
| Q-005 | RESOLVED 2026-07-15 | ¿Cuál es la hora exacta de cierre del 2026-08-15? | `23:59:59 America/Hermosillo`, inclusivo; persistir UTC. | Implementado y cubierto por prueba de frontera. |
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
| Q-026 | PENDING | ¿La rúbrica provisional es definitiva, con pesos/rangos y puntaje mínimo? | Versionar rúbrica; total calculado por servidor; bloquear cambios tras iniciar evaluación. | **Crítico:** modelo, cálculo y pruebas. |
| Q-027 | PENDING | ¿Cuántos jueces evaluarán cada proyecto? | Parámetro por convocatoria/categoría; no codificar un número fijo. | Alto: asignación, estado “evaluated” y capacidad. |
| Q-028 | PENDING | ¿La evaluación será ciega? | Asumir ciega y mostrar sólo información necesaria; definir proceso de anonimización. | **Crítico:** vistas, archivos y PII. |
| Q-029 | PENDING | ¿Cómo se resuelven empates y recusaciones? | Regla administrativa documentada; nunca selección aleatoria; toda excepción auditada. | **Crítico:** ganador y confianza. |
| Q-030 | PENDING | ¿Se puede declarar una categoría desierta? | Permitirlo con permiso, razón y acta/registro. | Alto: estados y publicación. |
| Q-031 | PENDING | ¿Se requiere reapertura de evaluaciones y quién la autoriza? | Permiso separado, razón obligatoria, notificación y auditoría. | Alto: integridad y UX de juez. |
| Q-032 | PENDING | ¿Cuáles tipos de conflicto y quién resuelve la reasignación? | Catálogo mínimo + texto; conflicto bloquea y notifica a administrador. | Alto: asignaciones y privacidad. |

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
| Q-043 | PENDING | ¿Retención de auditoría, evaluaciones, exports y backups? | Matriz por tipo; backups deben respetar expiración documentada. | **Crítico:** almacenamiento y cumplimiento. |
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
