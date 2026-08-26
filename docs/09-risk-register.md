# Registro de riesgos

> **Riesgos ADR-0016 — 2026-08-26:** `R-EVAL-CURRENT-01` alto y mitigado local/test: un puntero vigente atrasado podría asociar una revisión incorrecta; el writer exige pertenencia y máximo append-only o aborta todo. `R-EVAL-CURRENT-02` medio: confundir filtro visible con alcance exportado; la confirmación declara que el XLSX es global. `R-EVAL-CURRENT-03` residual: juez es nombre actual, no snapshot histórico. Se conserva el riesgo operativo del worker/disk y el bloqueo jurídico/productivo existente.

> **Riesgos ADR-0015 — 2026-08-25:** `R-EVAL-EXPORT-01` alto: el XLSX combina identidad y evaluación; mitigado con permiso exclusivo, contraseña reciente, ownership, disk privado, expiración, snapshot inmutable y auditoría redactada. `R-JUDGE-PRE-SETUP-01` medio: confundir assignment activo con cuenta operativa; mitigado manteniendo middleware/Policies y omitiendo notificaciones. Riesgo residual: el nombre del juez es actual al exportar y no histórico; el archivo lo declara. Producción no verificada.

> **Riesgo de lote síncrono — 2026-08-25:** el límite fijo de veinte y la transacción por propuesta acotan locks y rollback. El preflight puede quedar obsoleto, por lo que la ejecución compara estado y falla individualmente. La medición local máxima de 200 MiB ejecutó en 2.589 s; capacidad y timeout productivos siguen `POR_CONFIRMAR`. Si un ambiente autorizado excede su timeout, el feature debe permanecer apagado; no se amplía ni vuelve asíncrono sin una decisión nueva.

> **Riesgos M8 — 2026-08-25:** `R108` mitigado local/test: eventos ID-only, plantillas allowlist y scans evitan filtrar evaluación/conflicto/PII. `R109` mitigado: idempotencia y `withoutOverlapping` evitan duplicados de listener/scheduler. `R110` mitigado: ventana exacta y preflight global bloquean digest tardío o con `due_at` divergente. `R111` residual: aceptación SMTP no acredita entrega. `R100` jurídico continúa bloqueando release/producción.

> **Riesgos M7 — 2026-08-24:** `R104` mitigado localmente: revisiones enviadas se sellan y reaperturas sólo clonan append-only. `R105` mitigado: actor real y juez sujeto se separan y prueban. `R106` mitigado: ventanas centralizadas inclusivas fallan cerrado ante deriva. `R107` residual: un admin autorizado puede actuar en nombre del juez, por lo que contraseña, motivo cifrado, auditoría y revisión operativa son controles obligatorios. `R100` jurídico continúa bloqueando release/producción.

> **Riesgos M6A — 2026-08-24:** `R100 OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED` (alto/bloqueante de release): el código permite cero asignaciones aunque la Mecánica v1.1 exige al menos tres jueces; sólo una reconciliación jurídica o aceptación formal separada puede cerrar el riesgo. `R101` (mitigado local/test): uso doble del enlace inicial se serializa por locks y consumo único. `R102` (mitigado): nueva asignación siempre requiere selección admin y unicidad; no hay automatismo. `R103` (residual): correo aceptado por transporte no acredita entrega y no revierte la asignación.

> **Estado vigente M6 — 2026-08-18:** M4A conserva `4+2` ilimitado, M5 mitiga fuga estructurada y M6 mitiga cálculo ambiguo/concurrencia mediante BCMath, lock optimista y auditoría redactada. Persisten autoidentificación semántica, producción no verificada y cadena de conflicto de replacement fail-closed.

## Riesgos del milestone de acciones del panel — 2026-08-22

| ID | Riesgo | Severidad/estado | Evidencia/mitigación | Acción pendiente |
|---|---|---|---|---|
| R90 | Un GET de recordatorio envía o crea evidencia sin consentimiento | **Mitigado local/test** | GET firmado es sólo lectura; POST separado exige CSRF, firma, legales y revalidación; pruebas comparan conteos antes/después | Repetir smoke en release autorizado |
| R91 | El lote omite drafts por filtros/paginación o envía a integrantes | **Mitigado local/test** | Selección servidor de todos los drafts activos; destinatario exclusivo `submission.user`; preview y suites cubren ambos contratos | Vigilar volumen/cooldown y rebotes en operación autorizada |
| R92 | Excepción administrativa aparenta aceptación del participante | **Mitigado local/test; riesgo legal residual** | Admin exacto, password reciente, confirmación/razón, snapshot modo/actor/waivers, evento/audit y cero `legal_acceptances` | Revisión jurídica/operativa antes de activar el flag en producción |
| R93 | Reintento de correo entrega duplicado tras fallo entre SMTP y persistencia | **Residual operativo** | Cooldown, job único, estados y reintentos reducen duplicados, pero SMTP sigue siendo entrega al-menos-una-vez | Añadir message-id/provider receipt sólo con diseño autorizado |
| R94 | Bitácora expone destinatario, token, cuerpo o excepción | Mitigado local/test | Dirección/contexto/razón cifrados, máscara/HMAC, job sólo con ID, allowlist y pruebas negativas de HTML/log/auditoría | Mantener scans y revisar cualquier nuevo tipo antes de registrarlo |
| R95 | `processing` queda huérfano tras muerte del worker | Mitigado local/test | Lease temporal, diagnóstico read-only y reconciliación programada hacia `unknown` sin reenviar | Operación revisa evidencia y decide individualmente |
| R96 | Admin fuerza dos veces o reenvía un `unknown` ya aceptado | Residual controlado | Lock optimista, un intento activo, contraseña/razón/confirmación y ack explícito de duplicado; sin acción masiva | Webhook/provider receipt requerirá diseño posterior |
| R94 | Exportación permanece indefinidamente `queued` por worker/migración/config | **Diagnosticado local; producción POR_CONFIRMAR** | Comando read-only, alerta por umbral y job `database/exports`; generador sigue verde | Verificar migración/cache/jobs/failed_jobs/disco y worker Flower Flow en rollout autorizado; no duplicar/borrar jobs |

## Recalificación de auditoría integral y diseño Fase 02B — 2026-08-18

| ID | Riesgo residual | Nivel actual | Evidencia/mitigación | Próxima puerta |
|---|---|---:|---|---|
| R57 | Runtime local primario no representa el SHA actual | Alto operativo local | El baseline de `flowerflow` no es autoridad productiva; M1–M6 se validaron en `flowerflow_testing` con 19/19 y guard exacto | Release candidate únicamente en ambiente autorizado y sintético; no migrar por inferencia |
| R58 | Límite de propuestas divergente | Alto funcional local | `.env` fija 3; código, `.env.example`, UI contractual y pruebas fijan 4 | Alinear ambiente local autorizado y probar cuarta/quinta propuesta |
| R59 | Seguridad privilegiada documentada como completa cuando 2FA es opcional | Medio/alto aceptado | M2 conserva 2FA opcional por decisión del propietario y prueba recovery administrativo, razón/password confirmation, auditoría, aviso y revocación; otras acciones privilegiadas futuras aún requieren su control específico | Mantener la distinción entre 2FA opcional y controles compensatorios; no extender el recovery a otros roles por inferencia |
| R60 | Futuros roles pueden caer en superficies participantes | **Mitigado local por M1** | Rutas participant/panel/judge tienen rol exacto; dashboard/layout fallan cerrados para cero/multirol; 92 aserciones M1 y UAT Firefox por rol | Mantener `AssignExclusiveBusinessRole`, middleware y matriz al crear cualquier escritor o rol nuevo |
| R61 | Estado documental podía confundirse con disponibilidad/deploy | Medio, mitigado documentalmente | Nuevo diagnóstico separa plan maestro, código aprobado, runtime local y producción | Mantener `docs/16-project-status-by-module-and-role-2026-08-17.md` en cada milestone |
| R62 | Alcance de accesibilidad se superpone entre dos categorías | **Resuelto / owner accepted** | El propietario indicó que la redacción y operación se conservan como están | No recategorizar ni modificar UI/reglas por este punto |
| R63 | El binario actual de Mecánica v1.0 no coincide con el hash histórico | **Histórico aceptado** | El propietario designó el archivo físico actual `3bcf31…` como v1.0; `42bd5e…` permanece en registros/aceptaciones históricas | Mantener visible la discrepancia y no sustituir archivo ni reescribir evidencia |
| R64 | Reaceptación de v1.1 no definida | **Resuelto / owner decision** | Cuentas v1.0 continúan operativamente sin reaceptación forzada; nuevas aceptaciones registran v1.1 | No bloquear ni hacer backfill/falsificación de `legal_acceptances` |
| R65 | El 503 predeterminado generaba reportes CSP por estilos inline | **Resuelto local** | Vista FlowerFlow propia, accesible, con assets Vite normales y sin `<style>`/`style=`; pruebas bajo CSP estricta y render de mantenimiento | Incluir el cambio en el SHA publicado por el propietario |
| R66 | Confundir despliegue informado con evidencia técnica independiente | Medio/Alto operativo | `OWNER_CONFIRMED_DEPLOYED` se registra por separado; `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR` y no se infieren migraciones, flags, servicios, integridad o UAT | Handoff y diagnóstico mantienen ambas capas hasta evidencia autorizada |
| R67 | Crear `judge` abre accidentalmente el shell participante | **Mitigado local por M1/M2** | Rol/permiso exclusivos, gates explícitos, perfil activo, redirección segura y `/juez` vacío pasaron pruebas y navegador para pending/active/suspended; el seeder no crea jueces | Reutilizar el escritor exclusivo y `judge.active` en toda superficie futura sin relajar rutas |
| R68 | Snapshot enviado filtra identidad al juez | **Mitigado local/test por M5** | El snapshot crudo nunca se sirve; builder allowlist/hash, Policy por assignment y canarios excluyen PII/folio/fechas/evidencia operativa | Mantener regresión M5 y verificar volumen/operación antes de cualquier release; riesgo semántico separado en R77 |
| R82 | Contrato final ilimitado no materializado | **Resuelto local/test** | M4A migra `10→NULL`, exige `4+2`, selección manual y supera treinta reemplazos | Mantener regresión M4A en M5+; no atribuir a producción |
| R83 | Fuga de contenido por paquete ciego incorrecto | **Mitigado local/test por M5** | Payload exacto, inventario separado sin nombres/rutas, sanitización, descargas neutras e IDOR/canarios negativos | Conservar allowlist cerrada; cualquier campo nuevo requiere decisión, migración/versionado y prueba canary |
| R84 | Carrera o cobertura parcial | **Mitigado local** | Locks de propuesta/versión/competencia/rúbrica/perfiles/asignaciones, checks/unicidades y prueba real con dos procesos convergen en cuatro filas | Repetir bajo carga autorizada antes de producción; no activar por lote |
| R69 | Rúbrica o cálculo ambiguo altera puntajes | **Mitigado local/test por M3+M6** | Rúbrica fijada, BCMath 4 decimales, presentación HALF_UP, total nulo incompleto y vectores límite/total cliente hostil probados | Conservar calculator como única autoridad y repetir antes de cualquier release |
| R87 | Dos pestañas sobrescriben silenciosamente un borrador | **Mitigado local/test por M6** | `lock_version`, locks de agregado/revisión/scores y HTTP 409; UAT confirmó que la segunda pestaña no sobrescribe | Mantener 409 y no introducir last-write-wins en M7 |
| R88 | Auditoría de evaluación filtra contenido sensible | **Mitigado local/test por M6** | Eventos redactados y tests/inspección de keys excluyen total, componentes, scores, comentarios, contenido, archivos y PII | Mantener allowlist de metadata en M7+ y scan por release |
| R89 | Rollback borra borradores/evidencia | **Mitigado local/test por M6** | `down()` aborta ante filas o audits M6; rollback operativo es flag off | Nunca borrar evidencia como rollback; backup/UAT obligatorio para release futuro |
| R70 | Reapertura contradice inmutabilidad | **Mitigado local/test por M7** | Revisión append-only, motivo cifrado, contraseña/confirmación, ventanas, actor real y hashes de la fuente probados | Repetir gates y revisión de integridad antes de cualquier release autorizado |
| R71 | Conflicto/reasignación inconsistente por concurrencia | **Mitigado local/test** | M4A usa locks determinísticos, selección manual y unicidad vigente con dos sustitutos | Conservar pruebas de carreras e historia append-only en M5+ |
| R72 | 2FA opcional en cuentas de juez | Alto aceptado por propietario | TOTP existe; M2 implementa recovery sólo `admin`, permiso separado, razón/password confirmation, notificación, auditoría y revocación efectiva | Mantener opción y controles compensatorios; no documentarlo como obligatorio ni mostrar material TOTP al admin |
| R73 | Migraciones 02B afectan un entorno productivo con más de 50 propuestas reales | **Crítico para releases futuros; M2 controlado localmente** | M2 añadió sólo `judge_profiles`/permisos, sin backfill ni asignaciones, y pasó upgrade sintético, rollback y forward preservando usuarios | Repetir compatibilidad en cada milestone; no inferir que la prueba local acredita producción |
| R74 | Detección de empate se convierte en ganador | **Crítico de alcance** | Igualdad del consolidado a dos decimales quedó aprobada sólo como señal técnica | Mantener resolución/ganadores/resultados fuera de 02B y sin selección automática |
| R75 | Diseño presentado como implementación | Bajo documental controlado | M1–M8 están conformes local/test; M9–M10 no están implementados | Mantener estado por milestone y no atribuir evidencia local a producción |
| R76 | Capacidad/cobertura/sustitución (`P2B-BLOCK-001`) | **Sustituido por M6A; bloqueante jurídico residual** | Selección administrativa explícita sin mínimo/máximo ni automatismo; el PDF exige “al menos tres jueces” | No liberar hasta reconciliación jurídica o aceptación formal separada |
| R77 | Ceguera simple permite autoidentificación dentro del contenido | Alto aceptado por propietario | M5 elimina PII estructurada, pero texto, imagen, enlace o anexo pueden revelar identidad y el owner acepta no bloquear | UI M5 informa “anonimización estructural”, allowlist/metadatos limpios y auditoría redactada; no prometer anonimización total |
| R85 | Drift o sustitución de un anexo después de activar paquete | **Mitigado local/test** | Generación/activación/descarga comparan metadata, bytes, SHA, MIME y firma; missing/drift rechaza sin revelar path | Monitorear rechazos técnicos y no reparar sustituyendo evidencia; restaurar sólo el binario exacto autorizado |
| R86 | Activación concurrente duplica o deja paquete parcial | **Mitigado local/test** | Unicidad por versión, locks de versión/paquete/archivos/asignaciones y dos procesos MySQL convergen en una activa/un audit | Mantener carrera en CI/local y repetir bajo carga autorizada antes de release |
| R78 | Admin modifica puntajes y puede aparentar suplantación del juez | **Residual controlado local/test por M7/M8** | Sólo revisiones reabiertas; `submitted_by_user_id` real, juez sujeto estable, motivo cifrado, contraseña/confirmación, auditoría/fuente inmutable y acuse M8 sin falsa atribución | Revisión operativa previa al release; conservar actor real y contenido mínimo |
| R79 | Cache persistente de permisos conserva una matriz anterior al cambio | **Mitigado local en M2** | La primera UAT devolvió 403 a `/panel/jueces` pese al permiso correcto; se reprodujo como cache Spatie obsoleta y el runtime local ahora ejecuta `permission:cache-reset` tras el guard/readiness | Limpiar cache de aplicación/permisos en cada procedimiento de release autorizado y conservar prueba de ruta administrativa |
| R80 | Selección incorrecta entre dos sustitutos | Medio operativo | Owner elimina límites pero exige selección manual; M4A valida ULID/rol/estado/prerrequisitos y audita sujeto | Mantener selector explícito y no introducir balanceo automático |
| R81 | Doble activa o mutación retroactiva de rúbrica | Bajo local / producción no acreditada | M3 usa locks, `active_slot`, unique/checks, modelos guarded, Policies y auditoría; prueba concurrente dejó una sola activa | Mantener negativos/locks en M4+ y no ejecutar rollback destructivo sobre versiones activas/sustituidas |
| R85 | Descripción de criterio no aprobada | Medio de UX, sin bloqueo estructural | M3 persiste `NULL` y muestra `POR_CONFIRMAR`; código/pesos/escala sí están aprobados | Recibir texto propietario antes de mostrar instrucciones extensas al juez; crear nueva versión, nunca mutar una activa |
| R86 | Un sustituto asignado declara conflicto o queda inoperativo | Medio/alto operativo | El límite numérico fue eliminado, pero no se aprobó encadenar reemplazos de sustitutos | Mantener `POR_CONFIRMAR` el tratamiento del replacement y fallar cerrado; no reasignar por inferencia |

La auditoría vigente cuantifica estos riesgos y el avance en `docs/16-project-status-by-module-and-role-2026-08-17.md`.

## Recalificación posterior al hardening local — 2026-08-06

| ID | Riesgo residual | Nivel actual | Evidencia/mitigación | Próxima puerta |
|---|---|---:|---|---|
| R42 | Regresión automatizada MySQL | Bajo controlado | Gate verde: 90 pruebas/800 aserciones con base y cuenta exclusivas; secreto ignorado | Repetir en cada SHA candidato |
| R43 | Producción usa checkout Git directo sin release alterno | Crítico operativo confirmado | El propietario confirmó `/var/www/flowerflow` directamente vinculado a GitHub, sin `releases/current/shared`; el runbook inmediato se alinea a esa realidad | Actualizar por SHA exacto sin `git clean/reset --hard`; preservar `.env`/`storage`. Conversión a symlink queda como milestone futuro |
| R44 | EC2 compartida con otras seis aplicaciones | Crítico operativo | Prohibidos paquetes globales; sólo vhost Flower Flow, `configtest` y reload graceful | Baseline/smoke de las siete aplicaciones antes y después |
| R45 | Consistencia archivo/SQL | Bajo tras mitigación | Compensación, borrado post-commit y auditor read-only verificados en MySQL, incluso fallos sintéticos | Repetir prueba operativa con el storage del ambiente candidato |
| R46 | Estados/abuso de mutaciones administrativas | Bajo tras mitigación | Matriz `pending/in_review`, rechazo de demás estados y throttle por actor/ruta verificados | QA real de doble clic antes de UAT |
| R47 | Dependencias PHP vulnerables | Bajo | Guzzle 7.15.3; Composer sin advisories | Repetir audit por release |
| R48 | Grafo JS heredado | Bajo/Medio | Grafo mínimo y cero avisos moderados/altos/críticos | Vigilar advisory bajo de Quill; actualizar cuando exista fix compatible |
| R49 | CSP/HSTS pueden romper superficie productiva | Medio | CSP estricta Report-Only y HSTS de un día, ambos promovibles por flags | Consola/smoke limpios y soak de siete días |
| R50 | Antimalware ausente | Alto aceptado | Allowlist, firma, cuota, storage privado y capacidad de cierre permanecen | Decisión posterior; cerrar uploads ante señal |
| R51 | IP/user-agent crudos y fallback legal | Medio aceptado | Acceso restringido; sin cambio por instrucción del propietario | Revisión jurídica/privacidad posterior |
| R52 | Restore no ensayado | Crítico externo | Fuera del alcance técnico de esta rama | Evidencia externa antes de autorizar despliegue |
| R53 | Históricamente la plataforma y los primeros PDF divergieron en categorías/propuestas/premios | Resuelto / histórico | El PDF v1.1 definitivo y el propietario confirman cuatro categorías/máximo cuatro; R62 acepta la superposición temática | Mantener cantidades, decisión y evidencia histórica sin reinterpretar categorías |
| R54 | La migración de categoría es deliberadamente no reversible después de recibir datos | Medio controlado | `down` no elimina ni recategoriza; código anterior conserva relaciones y la configuración debe permanecer en cuatro si existe cualquier propuesta asociada | Backup/UAT antes de desplegar; rollback sólo de presentación compatible tras existir datos |
| R55 | Dos creaciones simultáneas pueden intentar superar el límite por cuenta | Bajo tras mitigación | Bloqueo de la fila de usuario, recuento y unicidad dentro de una transacción; prueba MySQL con dos procesos | Repetir prueba de carga en ambiente candidato si cambia el flujo de creación |
| R56 | El cierre técnico se amplía al 23 de agosto, pero los PDF jurídicos v1.0 mantienen el 15 de agosto | Resuelto para v1.1; histórico | Mecánica v1.1 p. 3 y Términos v1.1 p. 2 confirman 23 de agosto de 2026, 23:59 Hermosillo; v1.0 se conserva como historia | Verificar vínculos/aceptaciones v1.1 y no reescribir v1.0 |

Esta adenda prevalece para el estado actual; las tablas históricas siguientes se conservan para trazabilidad y no deben interpretarse como verificación vigente.

## Altas/abiertas de Fase 01

| Riesgo | Estado/mitigación | Gate |
|---|---|---|
| Continuidad jurídica v1.1 | R62/R64 y el tratamiento del PDF v1.0 fueron resueltos por el propietario; cantidades y vínculos están reconciliados | Conservar la decisión, las aceptaciones históricas y los hashes sin backfill. |
| Licencia Pixinvent no comprobada | `_referencia` sólo local; adaptación puntual | Evidencia comercial antes de producción. |
| Upload Office/ODF complejo | Firma, macros OOXML, ZIP bomb y ausencia temporal de antimalware; privado | Riesgo aceptado temporalmente por el owner el 2026-07-15; conservar allowlist, validación de firma, cuota, storage privado y monitoreo. ClamAV/cuarentena y pruebas corpus siguen pendientes. |
| Bundle demo excesivo | Build verde pero chunks grandes | Racionalizar entradas tras browser baseline. |
| MySQL Feature aún no ejecutado | Suite lista, secreto no expuesto | Configurar `.env`, migrar y correr. |
| EC2 compartida con Administratec desconocida | Cero cambios AWS; preflight read-only | Inventario/capacidad/aislamiento aprobados. |
| SMTP/DNS desconocidos | local `log`, mail en cola | Proveedor, SPF/DKIM/DMARC y captura staging. |
| WhatsApp preseleccionado | sólo UI nueva; no persiste hasta submit y es reversible | Validación jurídica/UAT. |

Escala: probabilidad e impacto Bajo/Medio/Alto/Crítico. El dueño es un perfil hasta asignar una persona.

| ID | Riesgo / señal | Prob. | Impacto | Dueño | Mitigación preventiva | Contingencia | Estado |
|---|---|---:|---:|---|---|---|---|
| R01 | El input inicia en módulo 7; faltan módulos 1-6 | Alto | Crítico | Producto | recuperar especificación completa y reconciliar traceability | congelar sólo alcance confirmado | Abierto P0 |
| R02 | 31 días para 58-76 días-persona | Alto | Crítico | Sponsor | equipo paralelo, decisiones 24 h, MVP estricto y freeze | diferir evaluación o apertura | Abierto P0 |
| R03 | Reglas legales/producto sin aprobar | Alto | Crítico | Producto/legal | cerrar calendario, elegibilidad, equipos, retención, premio y publicación | desactivar función o contenido por código | Abierto P0 |
| R04 | Licencia/variante Materialize no confirmada | Medio | Alto | Sponsor | localizar factura/licencia y dominio autorizado | usar sólo código autorizado o reemplazar shell | Abierto P0 |
| R05 | No hay Git en Flower Flow | Alto | Alto | Líder técnico | inicializar/importar con baseline y revisión, .env ignorado | snapshot firmado antes de cambios | Abierto |
| R06 | Sin composer.lock/vendor; backend no reproducible | Alto | Crítico | Backend | instalar con Composer aprobado, fijar lock y baseline | detener implementación | Abierto P0 |
| R07 | Node global de EC2 puede pertenecer a Administratec; Node 18 no soporta Corepack actual | Alto | Alto | Frontend/DevOps | Node 22.23.1 por usuario con NVM, Yarn 1.22.22 y helper que rechaza root/versiones incompatibles | conservar Node global y construir artefacto aislado | Mitigado |
| R08 | Auditorías CVE bloqueadas por herramientas/locks | Alto | Alto | Security | actualizar Composer dentro de M0, lock y audit JS correcto | revisión manual/lista de CVE; no deploy | Abierto |
| R09 | .gitignore permitía .env | Medio | Crítico | Líder técnico | corregido a /.env y patrón .env.*; secret scan | rotar cualquier clave expuesta | Mitigado |
| R10 | Credencial local con GRANT OPTION | Alto | Medio | DBA/dev | mantener loopback/sandbox; crear usuario sin grant option en futuros entornos | revocar/rotar antes de datos | Abierto |
| R11 | Reutilizar credencial local en EC2 | Bajo | Crítico | DevOps | secretos separados y least privilege | rotación inmediata y auditoría | Control |
| R12 | EC2 real no inventariada/sin SSH disponible | Alto | Crítico | DevOps | preflight firmado: OS, web, PHP, DB, recursos, procesos, backup | no deploy; preparar instancia separada | Abierto P0 |
| R13 | Coexistencia impacta Administratec | Medio | Crítico | DevOps | vhost/path/user/pool/DB/cookies/workers/logs separados; capacity test | detener Flower Flow y revertir vhost/release | Abierto |
| R14 | Headers públicos de Administratec sugieren hardening pendiente | Medio | Alto | DevOps/security | TLS/header/server token review en staging | WAF/reglas y parche urgente | Abierto |
| R15 | MySQL de producción local vs RDS no decidido | Alto | Alto | DevOps/DBA | medir carga, RPO/RTO/costo y aislamiento | RDS o DB separada antes de UAT | Abierto |
| R16 | Backup existe pero no restaura | Medio | Crítico | DevOps | restore drill con dump+archivos+secretos documentados | modo mantenimiento y recuperación manual | Abierto |
| R17 | No staging/UAT owner confirmado | Alto | Crítico | Producto/QA | provisionar y nombrar aprobador antes de cualquier release externo de M1–M6 | limitar release a piloto o aplazar | Abierto P0 |
| R18 | Login/registro son demos sin backend | Alto | Crítico | Backend | M2 completo con tests y rate limits | deshabilitar acceso público | Conocido |
| R19 | Jetstream referenciado pero no instalado | Alto | Alto | Backend | retirar/acoplar navbar al auth elegido en M1/M2 | fallback de navbar seguro | Abierto |
| R20 | Assets demo referencian archivos inexistentes | Alto | Medio | Frontend | inventario visual/build y reemplazo autorizado | ocultar componente roto | Abierto |
| R21 | Customizer/metadatos/robots Pixinvent | Alto | Alto | Frontend/SEO | M1 limpia, noindex por ambiente y metadata propia | bloquear indexación temporal | Abierto |
| R22 | Paquetes JS duplicados aumentan bundle/CVE | Alto | Medio | Frontend | registrar uso y retirar sólo tras build/regresión | conservar temporalmente con riesgo aceptado | Abierto |
| R23 | Fuga de comprobantes a jueces/exports | Medio | Crítico | Backend/security | storage/tablas/Policies/proyecciones separadas y tests negativos | revocar acceso, incident response y notificación legal | Abierto |
| R24 | Upload malicioso o agotamiento de disco | Alto | Crítico | Backend/DevOps | quotas, allowlist, firma real, storage privado y alarmas; antimalware aún no instalado | cerrar uploads, cuarentena y ampliar volumen | Aceptado temporalmente 2026-07-15; remediación pendiente |
| R25 | Doble envío cerca del cierre | Alto | Crítico | Backend | idempotencia, unique constraints, locks y load test | deduplicación auditada sin borrar evidencia | Abierto |
| R26 | Timezone/hora de cierre ambigua | Alto | Crítico | Producto | aprobar instante exacto Hermosillo, persistir UTC y test bordes | extensión administrativa auditada | Abierto P0 |
| R27 | Rúbrica/jueces/empates sin definir | Alto | Crítico | Producto | decisión antes del 18-jul | diferir módulo de evaluación | Abierto P0 |
| R28 | SMTP/SPF/DKIM/DMARC sin definir | Alto | Alto | Operación | proveedor/cuenta/dominio de prueba antes de M2 | notificación in-app y procedimiento manual autorizado | Abierto |
| R29 | Email duplicado o con PII | Medio | Alto | Backend/privacy | event id, plantillas allowlist y tests | parar worker y revocar plantilla | Abierto |
| R30 | Publicación prematura/errónea | Medio | Crítico | Producto/backend | flag off, permiso separado y doble confirmación | despublicar, audit y plan de comunicación | Abierto |
| R31 | Retención/eliminación no aprobada | Alto | Alto | Legal/privacy | matriz por entidad antes de datos reales | preservar acceso restringido hasta decisión | Abierto |
| R32 | Datos reales llegan a pruebas/logs | Medio | Crítico | QA/security | factories sintéticas, redacción, permisos y revisión | purga segura, rotación y respuesta a incidente | Abierto |
| R33 | Accesibilidad se descubre al final | Alto | Alto | Frontend/QA | componentes accesibles y QA teclado por milestone | recortar componentes/flujo, no waiver silencioso | Abierto |
| R34 | Performance al pico del cierre | Medio | Crítico | DevOps/backend | índices, DataTables server-side, load/capacity y freeze | modo degradado, colas y extensión auditada | Abierto |
| R35 | Laravel 12 sale de bug-fix support el 13-ago-2026 | Alto | Medio | Líder técnico | fijar último patch 12 seguro; monitorear security hasta 2027 | plan de upgrade Laravel 13 post-cierre | Abierto |
| R36 | GoDaddy persiste en documentación/decisiones | Bajo | Alto | Líder técnico | reemplazo global por AWS y ADR-0002 | bloquear aprobación si aparece como destino | Mitigando |

## Riesgos P0 para aprobar implementación

R01, R02, R03, R04, R06, R12, R17, R26 y R27 deben tener decisión o recorte explícito. Un supuesto no basta para reglas que cambian elegibilidad, cierre, evaluación o publicación.

## Cadencia

- Revisión diaria hasta producción.
- Cada riesgo cambia estado, owner, fecha objetivo y evidencia en el ExecPlan.
- Un riesgo materializado se convierte en incidente o tarea y conserva vínculo al ID.
- Riesgo aceptado requiere quién, hasta cuándo y por qué; no se cierra por silencio.

## Adenda de riesgos Fase 02A — 2026-07-16

| ID | Riesgo | Prob. | Impacto | Mitigación actual | Pendiente/rollback | Estado |
|---|---|---|---|---|---|---|
| R37 | “Reciente” se interpreta con meses inventados | Medio | Alto | no existe regla automática ni campo de rechazo por antigüedad | decisión jurídica antes de automatizar | Mitigado en código; PENDING jurídico |
| R38 | Documento equivalente aceptado sin criterio | Medio | Alto | resolución verificada exige justificación manual | revisión de catálogo por legal | Mitigado |
| R39 | Retención elimina antes de conocer ganadores | Medio | Crítico | sólo cálculo/reporte dry-run; sin scheduler ni delete | integrar módulo de resultados y autorización | Bloqueado por diseño |
| R40 | Nota/residencia se filtra a futuros jueces | Bajo | Crítico | discos/tablas/Policies separados, permisos granulares y tests de rol juez sin permisos | repetir matriz al crear rol juez real | Mitigado local |
| R41 | Correo falla después de decisión | Medio | Medio | commit previo, mail cifrado en cola, reintentos y aviso sin 500 | SMTP/worker/failed_jobs operativos | Mitigado local; OPS pendiente |
