# Estrategia de pruebas y calidad

## Suite Fase 01

Unit cubre sanitización. Feature preparado cubre landing/legales, perfil 18+/E.164/WhatsApp reversible, flags seguros, límite de panel, IDOR, deadline inclusivo, allowlist, cuota, XSS, privacidad de archivos, una propuesta/categoría, máximo total, snapshot/idempotencia, legales separados y mail en cola. Debe ejecutarse sobre MySQL local, no SQLite, después de configurar `.env` ignorado.

Comandos de gate: `php artisan migrate --seed`, `php artisan test`, `./vendor/bin/pint --test`, `composer validate --strict`, `composer audit --locked`, `corepack yarn@1.22.22 install --frozen-lockfile`, `corepack yarn@1.22.22 build`, hashes y browser QA. No usar datos reales ni enviar correo real.

**Estado:** plan; las suites de dominio no existen todavía.  
**Regla:** detener y reparar. Ningún milestone avanza con tests, build o criterios obligatorios fallando.

## Baseline 2026-07-15

| Verificación | Resultado |
|---|---|
| Sintaxis PHP de app/config/routes/tests | correcta |
| JSON de package y menús | correcto |
| composer validate | correcto, con deprecations del Composer 2.2.6 |
| artisan/test/route:list | bloqueado: vendor/autoload.php ausente |
| npm build | bloqueado: node_modules/Vite ausentes |
| composer audit | bloqueado: comando no existe en Composer 2.2.6 |
| npm audit | bloqueado: no hay package-lock compatible |
| Tests presentes | 2 ejemplos sin valor de dominio |
| MySQL local | conexión CLI/PDO correcta; esquema vacío |

La primera puerta de calidad es crear una baseline reproducible en M0; no se puede confundir sintaxis válida con aplicación operativa.

## Ambientes

| Ambiente | Datos | Base | Uso |
|---|---|---|---|
| Local WSL2 | sintéticos | MySQL flowerflow local | desarrollo y diagnóstico |
| Test automatizado | factories sintéticas por test | MySQL aislado; SQLite no es autoridad | CI/feature |
| Staging AWS | sintéticos representativos | DB y storage separados | E2E, UAT y restore |
| Producción | reales mínimos | recursos productivos | sólo tras aprobación |

La contraseña local se entrega fuera del repositorio y vive sólo en .env ignorado. CI/staging/producción usan credenciales diferentes. No clonar producción a entornos inferiores sin anonimización aprobada.

## Pirámide

1. **Unit:** estados, calendario, elegibilidad, weights, folio, redacción e idempotencia.
2. **Feature/integration:** rutas, Form Requests, Policies, transacciones, MySQL, storage, mail/queue.
3. **Browser:** recorridos críticos por rol, responsive, teclado y errores.
4. **Operación:** deploy, health, workers, backup/restore, observabilidad y rollback.

## Matriz funcional

| Área | Casos positivos | Casos negativos/límite | Nivel |
|---|---|---|---|
| Registro/login | alta, verificación, login, logout | duplicado, credenciales, rate limit, enumeración | Feature/browser |
| Reset/2FA | reset de uso único, enrolamiento y recuperación | expirado/reutilizado, rol privilegiado sin 2FA | Feature/browser |
| RBAC | acción permitida por rol | cada rol contra cada permiso crítico | Feature |
| Ownership | participante opera recurso propio | ULID de otro usuario, recurso archivado | Feature |
| Convocatoria | abre/cierra en fecha | antes/después, borde exacto, excepción sin permiso | Unit/feature |
| Legal | acepta versión vigente | falta aceptación, versión sustituida, hash distinto | Unit/feature |
| Perfil/elegibilidad | datos mínimos y decisión | menor/no elegible según regla PENDING, campos hostiles | Request/feature |
| Residencia | upload/revisión/descarga | juez, participante ajeno, MIME falso, sobrecuota | Feature/security |
| Borrador | create/update/autosave | stale version, conflicto, campos largos | Feature/browser |
| Equipo | invite/accept/remove según regla | duplicado, máximo, email no autorizado | Unit/feature |
| Envío | snapshot, folio y confirmación | sin email/legal/elegibilidad, después del cierre | Feature/browser |
| Idempotencia | mismo key devuelve resultado previo | keys concurrentes, mismo key distinto payload | Integration |
| Corrección | solicitud y nueva versión | alterar snapshot anterior | Feature |
| DataTables | filtro/paginación/orden | columna no permitida, N+1, filtro hostil | Integration/perf |
| Asignación | proyecto elegible a juez | duplicada, juez inactivo/no disponible | Unit/feature |
| Conflicto | declaración bloquea evaluación | editar score tras conflicto | Feature/browser |
| Evaluación | borrador, total y submit | no asignado, score fuera rango, incomplete, tardío | Unit/feature |
| Reopen | reabre con permiso/razón | juez se reabre a sí mismo | Feature |
| Ganador | decisión separada con razón | selección aleatoria, publicar sin permiso/consentimiento | Feature |
| Correo | plantilla/evento/locale correctos | retry, duplicado, fallo SMTP, PII en body | Unit/feature |
| Export | allowlist y auditoría | rol/columnas ajenas, expirado | Feature |
| Privacidad | intake/transiciones/evidencia | acceso de rol ajeno, cierre sin evidencia | Feature |
| Auditoría | actor/acción/entidad/redacción | secreto/PII en before-after o job payload | Unit/feature |

## Matriz de autorización negativa

Cada celda denegada se materializa al menos una vez en una prueba Feature.

| Recurso | anónimo | participante ajeno | reviewer | juez no asignado | juez asignado | auditor |
|---|---|---|---|---|---|---|
| Proyecto borrador | 401 | 403 | 403 salvo flujo | 403 | 403 hasta asignación/elegible | lectura redactada PENDING |
| Comprobante residencia | 401 | 403 | permitido por asignación | 403 | 403 | metadata redactada |
| Anexo evaluable | 401 | 403 | según Policy | 403 | permitido | lectura autorizada |
| Evaluación | 401 | 403 | 403 | 403 | propia | lectura redactada |
| Roles/settings | 401 | 403 | 403 | 403 | 403 | lectura limitada |
| Export completo | 401 | 403 | alcance propio | 403 | 403 | redactado |
| Audit log | 401 | 403 | 403 | 403 | 403 | permitido |

## Estados y concurrencia

- Tabla de transición con data provider para cada from/to, actor y precondición.
- Propiedad: todo salto no listado falla sin modificar historial.
- Dos submits concurrentes producen un snapshot/folio.
- Autosave exige versión optimista y devuelve conflicto claro.
- Asignación única por juez/proyecto mediante constraint y transacción.
- Evaluation submit bloquea edición; reopen crea audit event.
- Winner declare y publish son acciones distintas y serializadas.

## Fecha y zona horaria

Congelar reloj en casos:

- un segundo antes, instante exacto y un segundo después de opens_at/closes_at;
- conversión America/Hermosillo a UTC;
- servidor/MySQL con zona distinta;
- job en cola ejecutado después del cierre para solicitud creada antes;
- excepción administrativa con y sin permiso;
- cambio de año y fecha inválida.

El navegador sólo muestra la fecha; el servidor decide. No depender del reloj cliente.

## Archivos

Casos mínimos:

- PDF/JPEG/PNG permitido real y nombre Unicode/hostil;
- extensión permitida con MIME/magic bytes incorrectos;
- HTML/SVG/script/executable;
- tamaño exacto, +1 byte y cuota acumulada;
- archivo vacío, truncado y ZIP/bomba si ZIP se aprueba;
- mismo hash, upload concurrente y fallo de storage;
- scan clean/infected/error/timeout cuando exista antivirus;
- download propio, cruzado, juez, link expirado y tras revocación;
- headers Content-Type, nosniff y Content-Disposition;
- eliminación/retención y restore.

Usar Storage fake para lógica y storage real en una suite de integración.

## Correo, colas y scheduler

- Notification/Mail fakes prueban destinatario, locale, evento y ausencia de anexos/PII.
- event_id único evita duplicados.
- Reintento aplica backoff; excepción termina en failed_jobs con payload redactado.
- Worker usa colas/timeout/tries documentados.
- Scheduler no se superpone en tareas críticas y usa zona explícita.
- Smoke staging valida entrega real a buzones de prueba, SPF/DKIM/DMARC y bounce.

## Frontend, navegador y accesibilidad

Para el gate local de Fase 01 se usó Playwright CLI mediante la herramienta de Codex, sin añadir una dependencia E2E al repositorio. Se recorrieron landing, autenticación, participante, archivo privado, envío final y panel admin en escritorio y 390×844. La consola terminó con cero errores y cero advertencias. La selección de una herramienta E2E permanente para CI continúa como decisión posterior.

Evidencia del 2026-07-15:

- `php artisan test`: 15 pruebas, 67 aserciones, verde sobre MySQL `flowerflow`;
- frontera inclusiva de cierre y conversión UTC -> `America/Hermosillo` cubiertas por Feature tests;
- locale `es_MX`, HTML `es-MX`, validaciones en español y zona de negocio cubiertos por prueba de regresión;
- browser QA detectó y cerró el defecto de interpretación horaria antes del cierre;
- editor enriquecido y navegación confirmados en español de México mediante navegador real;
- capturas locales en `output/playwright/`, excluidas de Git;
- sólo datos sintéticos `example.test`; sin correo real ni datos personales reales.

Recorridos:

1. Visitante -> registro -> verificación -> login.
2. Participante -> perfil/elegibilidad -> borrador -> archivos -> preview -> submit.
3. Revisor -> documento privado -> corrección -> decisión.
4. Admin -> convocatoria -> asignación -> excepción auditada.
5. Juez -> asignado -> conflicto o evaluación -> confirmación.
6. Auditor -> reporte/log sin mutar.

Variantes: 360x800 móvil, tablet y desktop; Chrome/Firefox/Safari representativo, iOS/Android definido en UAT.

Checklist manual WCAG 2.2 AA:

- orden de foco y skip link;
- foco visible y no atrapado;
- labels/nombres/ayuda;
- errores de campo y resumen enlazado;
- stepper anunciable y operable sin ratón;
- modal devuelve foco;
- contraste, zoom 200/400 por ciento y reflow;
- tablas con headers/caption y alternativa móvil;
- contador no anuncia cada segundo;
- estados no dependen sólo de color;
- alt significativo y reduced motion.

## Rendimiento y capacidad

Dataset sintético inicial PENDING por volumen; mínimo recomendado: 10 mil participantes, 5 mil proyectos, 25 mil archivos metadata, 50 mil evaluaciones y 250 mil audit events.

- EXPLAIN para filtros de DataTables.
- Detectar N+1 con query count en pruebas.
- p95 listados menor a 2 s y páginas públicas menor a 800 ms en staging.
- Upload concurrente hasta volumen esperado y límite de disco.
- Prueba de pico de última hora con rampa controlada, nunca contra producción.
- Queue lag, jobs/minuto, failed jobs, CPU/RAM/disco y conexiones MySQL.

## Seguridad técnica

- Escaneo de secretos sobre todos los archivos/commits una vez exista Git.
- composer audit con Composer compatible y lock; auditoría del package manager elegido.
- Revisión de headers/TLS/CSP desde staging externo.
- Tests CSRF, mass assignment, XSS almacenado/reflejado, SQLi en filtros y open redirect.
- Fuzz limitado de parámetros/ULID; no pentest destructivo sin autorización.
- Revisión manual de Policies, queries y exports por segundo revisor.

## Comandos objetivo

Se fijarán exactamente en M0; secuencia propuesta:

~~~text
composer validate --strict
composer install --no-interaction
php artisan about
php artisan route:list
php artisan test
./vendor/bin/pint --test
composer audit --locked
corepack yarn@1.22.22 install --frozen-lockfile
corepack yarn@1.22.22 build
~~~

Yarn Classic 1.22.22 y `yarn.lock` son autoritativos. No generar `package-lock.json` ni ejecutar ambos package managers. La auditoría JavaScript del árbol heredado permanece como carril de riesgo separado y no bloquea este gate local.

Para MySQL local, cargar la contraseña desde .env o un prompt/archivo protegido; no incluirla en la línea de comando ni logs.

## Puertas

### Entrada a implementación

- Reglas P0 y paquetes aprobados.
- Dependencias fijadas e instalación reproducible.
- DB test aislada, storage y mail fake.
- baseline test/build verde.

### Entrada a UAT

- Requisitos críticos trazados a pruebas.
- Cero suite roja y cero P0/P1 abierto.
- migración sobre copia vacía y con dataset sintético.
- roles/archivos/fechas/concurrencia cubiertos.

### Entrada a producción

- UAT firmada, backup y restore demostrados.
- pruebas externas de HTTPS/headers/health.
- workers/scheduler/alerts observados.
- smoke y rollback ensayados.
- resultados públicos off.

### Salida de producción

- smoke por rol crítico.
- migraciones y worker estables.
- logs sin errores/secretos.
- monitor, queue lag, disco y backup en rango durante la ventana acordada.

## Evidencia

Cada ejecución registra commit/release, ambiente, comandos, salida resumida, fixtures, capturas sin PII, defectos y aprobación. La evidencia sensible se almacena con acceso limitado y retención definida.
