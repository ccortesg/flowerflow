# Estrategia de pruebas y calidad

## Evidencia vigente — 2026-08-17

La reconciliación jurídica v1.1 añade `LegalDocumentsV11Test`: valida existencia y SHA-256 de los tres PDF nuevos, una versión activa determinística por tipo, preservación de v1.0/aceptaciones durante rollback-forward, vínculos públicos/autenticados e identidad en superficies por rol. La inspección PDF combinó `pdfinfo`, extracción de texto y revisión visual de las 28 páginas de v1.0/v1.1. Los resultados finales del candidato se registran en el ExecPlan `.agent/execplans/flowerflow-legal-v1-1-local-release-candidate.md` y en `docs/17-legal-v1-1-reconciliation-2026-08-17.md`.

| Gate | Resultado actual |
|---|---|
| Base/cuenta de tests | `flowerflow_testing` / `flowerflow_testing_user`, MySQL loopback, guard obligatorio |
| Migraciones de test | 12/12 aplicadas después de guard exacto y `migrate:fresh --seed` |
| Suite | 107 pruebas/1,031 aserciones, verde; `LegalDocumentsV11Test`: 3/36 |
| Pint | Verde |
| Composer validate/platform/audit | Verde; cero advisories |
| Yarn dependencies | Un advisory bajo de Quill 2.0.3 sin fix; cero moderados/altos/críticos |
| Iconos/build | 97 iconos, 784 módulos y tres assets Vite, verde |
| Browser | UAT del candidato cerrada en Firefox: visitante, participante, reviewer y admin en 1440/768/360, teclado, foco, zoom/reflow, consola, 403/404/IDOR, límite 4/quinta rechazada, admisibilidad, 2FA, XLSX, expiración y cierre |

La base local primaria no sustituye este ambiente y no está autorizada para esta ejecución. El único runtime destructivo permitido es `flowerflow_testing` con `flowerflow_testing_user`, MySQL loopback, datos sintéticos y guard probado antes de cada `migrate:fresh`. La auditoría vigente está en `docs/16-project-status-by-module-and-role-2026-08-17.md`.

## Evidencia de reducción de riesgos — 2026-08-06

El ambiente destructivo quedó limitado a MySQL `flowerflow_testing` sobre loopback y al usuario exacto `flowerflow_testing_user`. `phpunit.xml` fija los valores no secretos; `Tests\TestCase` aborta antes de `RefreshDatabase` si cambian ambiente, driver, host, base, usuario o aparece `DB_URL`. La contraseña sólo puede vivir en `.env.testing`, ignorado.

Estado de la ejecución actual:

| Gate | Resultado |
|---|---|
| Guard MySQL, 8 pruebas negativas/positivas | Verde |
| Sintaxis PHP y Pint | Verde |
| Composer validate/platform/audit | Verde; cero advisories |
| Yarn audit de dependencias | Cero moderadas/altas/críticas; un advisory bajo de Quill sin fix |
| Iconos `--check` y build Vite | Verde; el build no reescribe el CSS rastreado |
| Manifest | Dos entradas; sin chunks demo/Mapbox/DataTables/Swiper |
| Rutas y `git diff --check` | Verde |
| Suite completa | Verde: 90 pruebas y 800 aserciones sobre MySQL `flowerflow_testing` con la cuenta exclusiva. |

La QA real de las páginas públicas comparó local contra producción en 360, 768 y 1440 px. Landing, registro y login conservaron composición y comportamiento; no hubo overflow horizontal, la navegación por teclado y el skip link funcionaron, el foco fue visible, el zoom 200 % no rompió el flujo y la consola terminó sin errores ni advertencias. Las capturas son locales e ignoradas en `output/playwright/`.

La colisión entre la ruta Laravel `/documentos` y el directorio físico `public/documentos/` se corrigió para Apache mediante una regla exacta anterior a `-d` en `public/.htaccess`; los PDF anidados continúan estáticos. `apache2ctl configtest` devolvió `Syntax OK`. El servidor incorporado conserva su limitación de resolución de directorios físicos, pero la UAT del candidato validó la superficie dinámica mediante el runtime seguro y los contratos automatizados. No se hizo smoke autenticado sobre el VirtualHost primario porque su `.env` no usa la base/cuenta exclusiva y esa ejecución no estaba autorizada.

El runtime UAT reproducible es `scripts/serve_local_testing.sh`: fija testing/MySQL loopback/base y usuario exclusivos, sesiones/cache en archivo, correo array, cola sync, flags autorizados y resultados apagados. Además falla cerrado si faltan esquema, rol participante, convocatoria, cuatro categorías o las tres versiones jurídicas activas. La primera alta posterior a la suite evidenció que `RefreshDatabase` deja el esquema sin seeders; la transacción se revirtió al faltar el rol. Se añadió el readiness gate, se ejecutó el seeder autorizado y la repetición registró cuatro aceptaciones v1.1 exactas de Términos/Privacidad. Después se recreó y sembró la base: quedó con cero usuarios/aceptaciones sintéticas.

## Suite Fase 01

Unit cubre sanitización. Feature preparado cubre landing/legales, registro con perfil mínimo, teléfono México `+52`, perfil 18+/E.164/WhatsApp reversible, flags seguros, límite de panel, IDOR, deadline inclusivo, allowlist, cuota, XSS, privacidad de archivos, una propuesta/categoría, máximo total, snapshot/idempotencia, legales separados y mail en cola. Debe ejecutarse sobre MySQL local, no SQLite, después de configurar `.env` ignorado.

Comandos de gate: `php artisan migrate --seed`, `php artisan test`, `./vendor/bin/pint --test`, `composer validate --strict`, `composer audit --locked`, `scripts/build_frontend_production.sh`, hashes y browser QA. No usar datos reales ni enviar correo real.

**Estado:** la baseline siguiente se conserva como registro histórico. La Fase 01 ya cuenta con suites de dominio, gates automatizados y UAT visual del área participante registrados en los ExecPlan y matrices vigentes.
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

## Cierre browser/UAT del área participante — 2026-07-16

El usuario responsable confirmó haber completado todas las validaciones visuales y responsive del área participante. La aceptación cubre acceso, inicio, perfil, propuestas y asistente de cuatro pasos en móvil, tablet y escritorio; estados representativos, teclado, foco, zoom, reflow, reduced motion, consola, assets, controles y overflow horizontal quedaron marcados como revisados sin hallazgos P0/P1/P2 reportados.

La evidencia de cierre es la aceptación manual explícita del usuario. No se recibieron ni versionaron capturas o reportes binarios. Los gates automatizados asociados permanecen registrados en el ExecPlan con resultado final de 50 pruebas y 500 aserciones, Pint PHP acotado, Composer y Vite verdes. El historial completo se conserva en `design-qa.md`.

## Matriz funcional

| Área | Casos positivos | Casos negativos/límite | Nivel |
|---|---|---|---|
| Registro/login | alta con perfil mínimo, teléfono `+52`, aceptaciones, verificación, login, logout | duplicado, menor de edad, teléfono incompleto, faltan documentos legales, credenciales, rate limit, enumeración | Feature/browser |
| Reset/2FA | reset de uso único, enrolamiento y recuperación | expirado/reutilizado, rol privilegiado sin 2FA | Feature/browser |
| RBAC | acción permitida por rol | cada rol contra cada permiso crítico | Feature |
| Ownership | participante opera recurso propio | ULID de otro usuario, recurso archivado | Feature |
| Convocatoria | abre/cierra en fecha | antes/después, borde exacto, excepción sin permiso | Unit/feature |
| Legal | acepta versión vigente | falta aceptación, versión sustituida, hash distinto | Unit/feature |
| Perfil/elegibilidad | datos mínimos capturados desde registro, edición y decisión | menor/no elegible según regla PENDING, campos hostiles | Request/feature |
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
| Correo | plantilla/evento/locale correctos; HTML/texto y ambas marcas | retry, duplicado, falla de dispatch/SMTP, PII en body | Unit/feature |
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
- Verificación/reset/acuse son jobs cifrados, post-commit y prueban conexión `database`, cola `default`, cuatro intentos, timeout 30 y backoff 60/300/900.
- Falla al programar devuelve aviso/reintento sin 500; falla de transporte termina en `failed_jobs` después de los reintentos.
- Worker escucha `default`; SMTP usa timeout de 10 segundos.
- Worker escucha también `exports`; el job XLSX cifrado/post-commit usa timeout 120, tres intentos y backoff 60/300.
- Exportación prueba borrador versus snapshot enviado, cinco hojas, fórmula hostil como texto, permisos/ownership, links autenticados, expiración y purga.
- Scheduler no se superpone en tareas críticas y usa zona explícita.
- Smoke staging valida entrega real a buzones de prueba, SPF/DKIM/DMARC y bounce.

## Frontend, navegador y accesibilidad

Para el gate local de Fase 01 se usó Playwright CLI mediante la herramienta de Codex, sin añadir una dependencia E2E al repositorio. Se recorrieron landing, autenticación, participante, archivo privado, envío final y panel admin en escritorio y 390×844. La consola terminó con cero errores y cero advertencias. La selección de una herramienta E2E permanente para CI continúa como decisión posterior.

Evidencia del 2026-07-15:

- `php artisan test`: 28 pruebas, 161 aserciones, verde sobre MySQL `flowerflow`;
- foco posterior de registro completo: 18 pruebas/124 aserciones en `RegistrationProfileFlowTest`, `AuthMailHardeningTest`, `ProfileEligibilityTest` y `SubmissionFlowTest`;
- frontera inclusiva de cierre y conversión UTC -> `America/Hermosillo` cubiertas por Feature tests;
- locale `es_MX`, HTML `es-MX`, validaciones en español y zona de negocio cubiertos por prueba de regresión;
- browser QA detectó y cerró el defecto de interpretación horaria antes del cierre;
- editor enriquecido y navegación confirmados en español de México mediante navegador real;
- capturas locales en `output/playwright/`, excluidas de Git;
- sólo datos sintéticos `example.test`; sin correo real ni datos personales reales.

Recorridos:

1. Visitante -> registro con perfil mínimo -> verificación amigable -> login.
2. Participante -> revisión de perfil/elegibilidad -> borrador -> archivos -> preview -> submit.
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
scripts/build_frontend_production.sh
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

## Cobertura obligatoria Fase 02A

La suite usa exclusivamente MySQL desechable confirmado, storage fake, mail fake y personas sintéticas. Cubre:

- creación/backfill y doble clic idempotentes;
- transiciones, snapshot inmutable, aclaración abierta/contestada/cerrada y separación de notas;
- aislamiento owner/otro participante/reviewer/admin/sin rol/juez futuro;
- sujetos de equipo, archivo válido y fallas de nombre/firma/MIME/cifrado/activo/tamaño/cuota;
- descarga y auditoría; equivalente con justificación; ausencia de antigüedad automática;
- UTC/Hermosillo, fecha límite opcional, SMTP fallido, filtros/paginación/sin lazy loading y flag on/off;
- migración hacia adelante, rollback, `migrate:fresh --seed`, vistas, rutas, Pint acotado, Composer y Vite.

El QA de navegador sólo incluye superficies nuevas y se documenta en `docs/design-qa-phase-02-admissibility.md`. No se usan PII, documentos reales ni pruebas contra producción.

Gate final local del 2026-07-16: 72 pruebas y 696 aserciones verdes; Pint sobre cambios, `composer validate --strict`, `composer audit`, build Vite, vistas, rutas y diff sin errores. El recorrido real de navegador cerró las superficies autorizadas con consola limpia y eliminó todos los artefactos temporales.
