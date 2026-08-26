# Estrategia de pruebas y calidad

> **Cobertura ADR-0016 — 2026-08-26:** los filtros prueban folio/ULID parcial, comodines literales, categoría, estado enum, combinación, validación, query string y GET sin mutación. El XLSX vigente prueba una fila por evaluación/juez, reapertura sólo en revisión 2, v1/v2, `NULL`/cero, total 4/2, comentarios por rubro, fórmula hostil literal, ausencia de PII/motivo y fallo cerrado ante puntero vigente atrasado. OpenSpout, PhpSpreadsheet y XML ZIP inspeccionan estructura y tipos; resultados finales en informe 33.

> **Cobertura ADR-0015 — 2026-08-25:** las pruebas dirigidas abarcan `pending_setup` en asignación individual/masiva/reemplazo, aislamiento antes y después del onboarding, notificación mixta, folio/ULID y comodines literales, y exportación de todos los estados/revisiones con rúbricas v1/v2, snapshot inmutable/ausente/inválido, `NULL`/cero, texto hostil, ownership, cola, expiración, purga y rollback protegido. Los resultados finales se fijan en el informe 32 y no sustituyen UAT productivo.

> **Gate M8A — 2026-08-25:** las pruebas dirigidas cubren GET sin creación, inicio→proyecto→evaluación, ubicación del conflicto, IDOR, autosave parcial/completo, progreso/total servidor, 409 sin overwrite, PDF/XLSX privados, marcas, tres hojas, texto literal, hash divergente y auditoría redactada. La regresión conjunta M5/M6/M7/asignaciones quedó en 37 pruebas/812 aserciones. UAT Firefox cubrió tres viewports, autosave real de 30 s, guardado antes de navegar, 422, offline, dos pestañas/409, confirmación, envío de sólo lectura y consola limpia. PDF A4 y las tres hojas XLSX se inspeccionaron visual y estructuralmente. Los resultados finales de suite/gates se fijan en el informe 31.

> **Gate de asignación simultánea — 2026-08-25:** cubrir rol/permisos/flag, GET y preflight puros, 1/20 frente a 0/21/duplicados, intención alterada/vencida/cruzada, blockers de admisibilidad/versión/residencia/paquete, atomicidad por propuesta, éxito parcial, doble ejecución, un delivery consolidado sin PII y regresión del flujo individual. Medir veinte propuestas antes de cualquier release; timeout implica `NO-GO`, no aumento de límite ni asincronía inferida.

> **Gate M8 — 2026-08-25, `GO LOCAL/TEST`:** pruebas MySQL cubren eventos post-commit/rollback, flags sin fallback, destinatarios exactos, contenido HTML/texto, XSS, idempotencia, revalidación/cancelación, recuperación desde bitácora, digest dry-run/execute, conteos, límites exactos, deriva de `due_at` y auditoría redactada. Resultado: M8 7/99, concurrencia 1/11 y suite completa 216/2,495; 23 migraciones, 104 rutas, cuatro schedules y UAT Firefox en tres viewports verdes. La evidencia vive en el ExecPlan M8 y el informe 29.

> **Gate M7 — validación final 2026-08-25, `GO LOCAL/TEST`:** GET puro, 99/100/2,000/2,001, completitud, XSS, cálculo persistido, sellado, PATCH posterior, dos pestañas/409, submit/reopen simultáneos, carrera juez/admin, clon exacto, revisiones sucesivas, actor real, privacidad diferenciada, conflicto, replacement, v1/v2, segundos limítrofes, drift, auditoría redactada y ausencia de deliveries quedaron verdes. Resultado: M7 5/165, regresión dirigida M5–M7 60/847 y suite completa 208/2,385; 23 migraciones, 104 rutas, gates y UAT Firefox verdes. `yarn audit` conserva sólo el advisory bajo conocido de Quill sin parche. La evidencia está en el informe 28 y no se atribuye a producción.

> **Gate M6A — 2026-08-24, `GO LOCAL/TEST`:** setup GET puro/POST único/concurrencia/expiración/cruce/cambio de correo, flags y reset genérico; migración v1/v2 fresh-upgrade-rollback y drift; selección manual uno/varios, más de cuatro proyectos por juez, cero mínimos, idempotencia y carreras; paquete sin cobertura; cancelación/reemplazo explícito; notificación sin PII; v1 cinco scores y v2 cuatro con BCMath/409; matriz de roles y UAT Firefox responsive. Resultado final: 40/459 dirigidas y 203/2,220 completa; 22 migraciones, 95 rutas, build/gates verdes y advisory bajo conocido de Quill sin parche. El zoom equivalente pasó con reflow a 390 CSS px; repetir porcentaje nativo manual antes de release. Ningún resultado local autoriza producción.

> **Gate de exportación de contactos — 2026-08-24:** bajo el guard exacto de `flowerflow_testing`, cubrir botón/rutas por permiso y contraseña, conteo global independiente de filtros, inclusión exclusiva de `submitted`, snapshot frente a datos vivos, perfil administrativo ausente, Unicode/saltos, fórmula hostil, tipo histórico, tipo desconocido, fallo cerrado, archivo privado, conteos, auditoría redactada y regresión del libro completo. La estructura XLSX se valida con OpenSpout/OpenPyXL y, si el runtime está disponible, mediante render de LibreOffice/Poppler.

> **Gate de bitácora de comunicaciones — 2026-08-23:** usar exclusivamente `flowerflow_testing`, Mail/Notification/Queue fake y datos `example.test`. Cubrir nueve familias, payload de job sólo con ID, cifrado crudo, idempotencia, GET sin mutación, permisos negativos, fallo/cancelación/unknown, lock 409, riesgo de duplicado, sincronización de recordatorios, backfill dry-run/idempotente, reconciliación sin envío y ausencia de PII en HTML/log/auditoría. UAT en Firefox verifica filtros, lista, detalle, confirmaciones, teclado, foco, zoom y reflow.

> **Adenda QA del panel — 2026-08-22:** las suites nuevas cubren columna/botones por rol/estado, recordatorio propietario-only, cooldown, lote completo independiente de filtros, mail dual y XSS, GET firmado puro, firmas alteradas/expiradas/cruzadas, POST sin archivo con legales, contenido mínimo, plazo inclusivo, excepción administrativa sin aceptaciones, password/razón/confirmación, idempotencia, auditoría redactada y diagnóstico/advertencia de exports. Los conteos finales y UAT local se registran en el ExecPlan y el informe de implementación de este milestone; no constituyen evidencia productiva.

> **Evidencia vigente M6 — 2026-08-18:** M1–M6 están verdes. M6 añade 13 pruebas/228 aserciones dirigidas; M1–M6 suma 54/888 y la suite completa 163/1,937. Cubre GET puro, apertura concurrente, payload hostil, decimales, vencimiento, 409, conflicto/replacement y auditoría redactada.

## Evidencia vigente — 2026-08-18

M4/M4A añaden pruebas dirigidas de asignación/conflicto y concurrencia MySQL: elegibilidad exacta, cuatro principales, dos sustitutos sin carga inicial, rúbrica/versión/plazo fijados, composiciones inválidas, idempotencia, catálogo/ownership, reemplazo append-only, selección manual, capacidad ilimitada, IDOR, mass assignment y canarios de ausencia. El UAT Firefox recorre cobertura, conflicto, reemplazo, tres viewports, teclado/foco, reflow y consola limpia. Los conteos finales están en `docs/22-phase-02b-m4a-unlimited-judges-implementation-report-2026-08-18.md`.

La reconciliación jurídica v1.1 añade `LegalDocumentsV11Test`: valida existencia y SHA-256 de los tres PDF nuevos, una versión activa determinística por tipo, preservación de v1.0/aceptaciones durante rollback-forward, vínculos públicos/autenticados e identidad en superficies por rol. La inspección PDF combinó `pdfinfo`, extracción de texto y revisión visual de las 28 páginas de v1.0/v1.1. Los resultados finales del candidato se registran en el ExecPlan `.agent/execplans/flowerflow-legal-v1-1-local-release-candidate.md` y en `docs/17-legal-v1-1-reconciliation-2026-08-17.md`.

| Gate | Resultado actual |
|---|---|
| Base/cuenta de tests | `flowerflow_testing` / `flowerflow_testing_user`, MySQL loopback, guard obligatorio |
| Migraciones de test | 23/23 aplicadas; M8 no añade migración y un `migrate:fresh --seed` final dejó el esquema canónico limpio |
| Suite | M8 7/99; concurrencia M8 1/11; completa 216/2,495, verdes |
| Pint | Verde |
| Composer validate/platform/audit | Verde; cero advisories |
| Yarn dependencies | Un advisory bajo de Quill 2.0.3 sin fix; cero moderados/altos/críticos |
| Iconos/build | 98 iconos, 784 módulos y tres assets Vite, verde |
| Browser | UAT M8 Firefox: bitácora/filtros/detalle, proceso prioritario, worker database, aceptación/cancelación redactada, 403/redirect, tres viewports, teclado/foco/zoom-reflow y consola limpia. |

La base local primaria no sustituye este ambiente y no está autorizada para esta ejecución. El único runtime destructivo permitido es `flowerflow_testing` con `flowerflow_testing_user`, MySQL loopback, datos sintéticos y guard probado antes de cada `migrate:fresh`. La auditoría vigente está en `docs/16-project-status-by-module-and-role-2026-08-17.md`.

## Contrato de QA Fase 02B aprobado — 2026-08-18

M1–M8 permanecen en alcance local/test. M6A sustituyó la composición fija; M5 conserva la proyección ciega, M6 apertura/guardado/cálculo, M7 sellado/reapertura y M8 comunicaciones/digest. M9–M10 siguen futuros/no autorizados.

- M1 — `VERIFIED LOCAL`: visitante, `participant`, `reviewer`, `admin`, `judge`, sin rol y multirol; roles estrictamente excluyentes, gates fail-closed, rutas directas/IDOR y flag de evaluación apagado/encendido.
- M2 — `VERIFIED LOCAL`: alta directa por `admin`, función `primary|substitute`, capacidad `NULL|10`, correo verificado, primer cambio seguro de contraseña, activación idempotente en cualquier orden, suspensión/reactivación, revocación de sesiones y recovery administrativo. 2FA de juez es opcional y su ausencia no se trató como fallo.
- M3 `VERIFIED LOCAL`: rúbrica global 20/20/25/25/10, escala 0–10/paso 0.5, comentarios futuros 100–2,000/1,000, precisión 4/2 y `HALF_UP`; ciclo versionado/inmutable y concurrencia probados.
- M4 — `HISTORICAL VERIFIED 1×10`: cuatro iniciales, conflicto/void/reemplazo y ausencia M5 probados. M4A — `VERIFIED UNLIMITED`: dos sustitutos sin iniciales, 31 reemplazos aceptados, selección manual y carreras cerradas.
- M5 `VERIFIED LOCAL`: proyección estructural separada y archivos con etiquetas/metadatos neutros; ausencia comprobada de PII estructurada, residencia, notas, aclaraciones, historial, rutas/nombres originales. La UI no promete eliminar identidad semántica de texto/imágenes/enlaces/anexos; ese riesgo está aceptado.
- M6 `VERIFIED LOCAL`: GET no muta, POST converge en 1/1/5, PATCH valida allowlist y lock, total BCMath permanece nulo incompleto y produce los vectores exactos; total cliente/XSS/IDs/códigos hostiles se rechazan o quedan inertes. El segundo exacto es inclusivo y el siguiente bloquea mutaciones.
- M7 `VERIFIED LOCAL`: envío inmutable; reapertura sólo por `admin` hasta 20:00 Hermosillo con razón 20–1,000/password confirmation; revisión append-only editable hasta 23:59:59. Actor real, juez sujeto, preservación de fuente, carreras y 409 están probados.
- Consolidación: media aritmética sólo con cuatro evaluaciones válidas; cualquier faltante mantiene `incomplete`; empate técnico sólo por igualdad a dos decimales y nunca declara ganador.
- M8 `VERIFIED LOCAL`: cinco tipos nuevos para conflicto declarado/resuelto, envío/reenvío, reapertura y digest único por juez; alta y asignación permanecen en M6A. No existe replay ni recordatorio programado retroactivo del 20/22 de agosto. Post-commit, destinatarios exactos, idempotencia, cancelación/recovery, conteos, ventanas y drift están probados.
- Retención: 24 meses desde `evaluation_cycle_closed_at`; antes de implementar purga se prueban legal hold, auditoría, idempotencia y compatibilidad documentada con backups.

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

El runtime UAT reproducible es `scripts/serve_local_testing.sh`: fija testing/MySQL loopback/base y usuario exclusivos, sesiones en base, cache en archivo, correo array, cola sync, flags autorizados y resultados apagados. Además falla cerrado si faltan esquema, roles, permisos, convocatoria, cuatro categorías o las tres versiones jurídicas activas, y limpia la cache de permisos de Spatie. Después de M3 se recreó y sembró la base para retirar cuentas/versiones/sesiones sintéticas; quedó v1 draft canónica, cero usuarios y ninguna asignación/evaluación.

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
| Reset/2FA | reset de uso único, enrolamiento y recuperación | expirado/reutilizado; enforcement sólo para roles/acciones cuyo contrato lo exija; juez puede operar sin 2FA | Feature/browser |
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
| Asignación | proyecto admitido a cuatro principales; admin selecciona manualmente uno de dos sustitutos; volumen ilimitado | duplicada, juez inactivo, sustituto en inicial, selección omitida/ajena, composición distinta de `4+2` y concurrencia | Unit/feature |
| Conflicto | declaración bloquea evaluación | editar score tras conflicto | Feature/browser |
| Evaluación | borrador, fórmula 20/20/25/25/10, comentario general, total servidor y submit | no asignado, score fuera rango/paso, comentario incompleto, payload total hostil, tardío | Unit/feature |
| Reopen | admin crea revisión append-only antes de 20:00 con razón/password y actor real | juez se reabre; admin fuera de hora; overwrite; envío después de 23:59:59 | Feature/security |
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
- Evaluation submit bloquea edición; reopen crea nueva revisión y evento, nunca overwrite.
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

## Seguimiento 503/CSP — 2026-08-18

El cierre de recepción y el modo mantenimiento usan ahora `resources/views/errors/503.blade.php`, con layout/recursos Vite normales y sin etiquetas `<style>` ni atributos `style=`. `SecurityAndFlagsTest` cubre estado 503, título/descripcion accesibles, enlaces de retorno/documentos y respuesta con CSP estricta; una prueba adicional renderiza la vista sin la bolsa de errores del middleware para representar `artisan down --render="errors::503"`.

La vista se pre-renderizó localmente en mantenimiento y devolvió HTTP 503. Se revisó visualmente con Chrome a 1440×1000 y Firefox a 390×844: marca, jerarquía, alerta, acciones y reflow permanecen legibles y sin desbordamiento. El HTML renderizado conserva `aria-labelledby`/`aria-describedby`, orden DOM de teclado y cero estilos inline. No se usaron cuentas ni datos reales.

## Acciones operativas del panel — 2026-08-22

El milestone de recordatorios, confirmación firmada, finalización administrativa y diagnóstico de exports cerró `GO LOCAL/TEST` con 179 pruebas y 2,130 aserciones. Incluye concurrencia real MySQL, matriz de roles/permisos/flags/estados, fecha inclusiva, firmas 403/404/410, CSRF, cooldown, propietario único, XSS, auditoría redactada, idempotencia por modo y no regresión M1–M6.

Firefox cubrió 1440×900, 1024×768 y 390×844 con teclado, foco, zoom, reflow y consola. Un worker local `database/exports --queue=exports --once` completó un XLSX privado sintético. Detalle y límites en `docs/25-panel-submission-actions-reminders-implementation-report-2026-08-22.md`.
