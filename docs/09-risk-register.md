# Registro de riesgos

## Altas/abiertas de Fase 01

| Riesgo | Estado/mitigación | Gate |
|---|---|---|
| Legal v1.0 no cubre todos los campos/canales | Recepción productiva apagada; v1.1 en `legal-change-log.md` | Aprobación y publicación versionada. |
| Licencia Pixinvent no comprobada | `_referencia` sólo local; adaptación puntual | Evidencia comercial antes de producción. |
| Upload Office/ODF complejo | Firma, macros OOXML y ZIP bomb; privado | ClamAV/cuarentena y pruebas corpus antes de activar. |
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
| R07 | Yarn lock sin Yarn; node WSL no normalizado | Alto | Alto | Frontend | elegir un package manager y Node 20 reproducible | build en entorno controlado | Abierto |
| R08 | Auditorías CVE bloqueadas por herramientas/locks | Alto | Alto | Security | actualizar Composer dentro de M0, lock y audit JS correcto | revisión manual/lista de CVE; no deploy | Abierto |
| R09 | .gitignore permitía .env | Medio | Crítico | Líder técnico | corregido a /.env y patrón .env.*; secret scan | rotar cualquier clave expuesta | Mitigado |
| R10 | Credencial local con GRANT OPTION | Alto | Medio | DBA/dev | mantener loopback/sandbox; crear usuario sin grant option en futuros entornos | revocar/rotar antes de datos | Abierto |
| R11 | Reutilizar credencial local en EC2 | Bajo | Crítico | DevOps | secretos separados y least privilege | rotación inmediata y auditoría | Control |
| R12 | EC2 real no inventariada/sin SSH disponible | Alto | Crítico | DevOps | preflight firmado: OS, web, PHP, DB, recursos, procesos, backup | no deploy; preparar instancia separada | Abierto P0 |
| R13 | Coexistencia impacta Administratec | Medio | Crítico | DevOps | vhost/path/user/pool/DB/cookies/workers/logs separados; capacity test | detener Flower Flow y revertir vhost/release | Abierto |
| R14 | Headers públicos de Administratec sugieren hardening pendiente | Medio | Alto | DevOps/security | TLS/header/server token review en staging | WAF/reglas y parche urgente | Abierto |
| R15 | MySQL de producción local vs RDS no decidido | Alto | Alto | DevOps/DBA | medir carga, RPO/RTO/costo y aislamiento | RDS o DB separada antes de UAT | Abierto |
| R16 | Backup existe pero no restaura | Medio | Crítico | DevOps | restore drill con dump+archivos+secretos documentados | modo mantenimiento y recuperación manual | Abierto |
| R17 | No staging/UAT owner confirmado | Alto | Crítico | Producto/QA | provisionar y nombrar aprobador antes de M5 | limitar release a piloto o aplazar | Abierto P0 |
| R18 | Login/registro son demos sin backend | Alto | Crítico | Backend | M2 completo con tests y rate limits | deshabilitar acceso público | Conocido |
| R19 | Jetstream referenciado pero no instalado | Alto | Alto | Backend | retirar/acoplar navbar al auth elegido en M1/M2 | fallback de navbar seguro | Abierto |
| R20 | Assets demo referencian archivos inexistentes | Alto | Medio | Frontend | inventario visual/build y reemplazo autorizado | ocultar componente roto | Abierto |
| R21 | Customizer/metadatos/robots Pixinvent | Alto | Alto | Frontend/SEO | M1 limpia, noindex por ambiente y metadata propia | bloquear indexación temporal | Abierto |
| R22 | Paquetes JS duplicados aumentan bundle/CVE | Alto | Medio | Frontend | registrar uso y retirar sólo tras build/regresión | conservar temporalmente con riesgo aceptado | Abierto |
| R23 | Fuga de comprobantes a jueces/exports | Medio | Crítico | Backend/security | storage/tablas/Policies/proyecciones separadas y tests negativos | revocar acceso, incident response y notificación legal | Abierto |
| R24 | Upload malicioso o agotamiento de disco | Alto | Crítico | Backend/DevOps | quotas, allowlist, private storage, scan, alarms | cerrar uploads, cuarentena y ampliar volumen | Abierto |
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
