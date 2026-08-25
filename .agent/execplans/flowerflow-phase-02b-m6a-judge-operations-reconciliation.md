# Fase 02B M6A — reconciliación operativa de jueces previa a M7

> **Contrato posterior:** M7 fue autorizado separadamente el 2026-08-24. Este ExecPlan conserva la evidencia M6A; envío/reapertura viven sólo en el ExecPlan M7 y no reescriben los resultados históricos inferiores.

Este ExecPlan es un documento vivo. `Progreso`, `Hallazgos inesperados`, `Decisiones` y `Resultados` se actualizan durante la ejecución conforme a `.agent/PLANS.md`.

## Propósito

Corregir, antes de M7, el onboarding purpose-bound de jueces, la asignación exclusivamente manual y sin mínimos ni límites, la rúbrica v2 derivada de la Mecánica v1.1 y la experiencia responsive del juez. El milestone conserva íntegramente la evidencia histórica M1–M6 y termina sólo con evidencia local/test sintética. La divergencia jurídica entre “al menos tres jueces” del PDF y la decisión del propietario de no exigir mínimo permite `GO LOCAL/TEST`, pero mantiene `NO-GO RELEASE/PRODUCTION` hasta reconciliación jurídica o aceptación formal separada.

## Baseline comprobado

- Repositorio y Git toplevel: `/home/ccortesg/workspace/flowerflow`.
- Rama: `codex/submission-deadline-extension`.
- `HEAD`, upstream y merge-base: `d3f616c86d72bfd32c1545205df057e19cf765ea`.
- Árbol inicial limpio: `git status --short --untracked-files=all`, `git diff`, `git diff --stat` y `git diff --check` sin salida.
- Baseline funcional: 21 migraciones, 86 rutas propias y M1–M6 `GO LOCAL/TEST`.
- Regresión dirigida previa M1–M6: 54 pruebas, 888 aserciones, verde.
- Guard demostrado antes de pruebas o esquema, sin imprimir secretos:
  - `APP_ENV=testing`
  - `DB_CONNECTION=mysql`
  - `DB_HOST=127.0.0.1`
  - `DB_DATABASE=flowerflow_testing`
  - `DB_USERNAME=flowerflow_testing_user`
  - `SELECT DATABASE()=flowerflow_testing`
- No se accedió a producción, URL pública, AWS, EC2, SSH/SSM, SMTP real, logs externos o datos reales.

## Evidencia jurídica y divergencia obligatoria

El PDF inmutable `public/documentos/2026/01_Mecanica_Convocatoria_Hermosillo_Florece_2026_v1.1.pdf` se verificó como PDF 1.7, A4, 5 páginas, 866607 bytes y SHA-256 `11c399ca84735d7dbcb17174e192582c93589afa5100c0250753ca15def4db36`. La extracción y revisión visual de la página 4 confirman:

- cuatro criterios exactos de la rúbrica v2;
- “al menos tres jueces” por proyecto;
- promedio de las calificaciones.

La Mecánica no define pesos, escala, paso o precisión. Los pesos iguales de 25 %, la escala 0–10, el paso 0.5 y la precisión 4/2 `HALF_UP` son decisiones de producto. La decisión del propietario de permitir cero, uno o cualquier cantidad de asignaciones no es una interpretación jurídica y se registra como `OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`. El PDF, sus hashes, vínculos y aceptaciones no se modifican.

## Alcance autorizado

1. Enlaces purpose-bound de configuración inicial de juez; sólo su POST válido inicializa contraseña, verifica correo y activa el perfil.
2. Flags globales y selección administrativa por operación para notificar alta y nueva asignación.
3. Catálogo inmutable de contratos de rúbrica v1 histórica y v2 legal activa; migración aditiva de activación con origen técnico.
4. Generalización de M6 para cualquier cantidad exacta de criterios de la rúbrica fijada.
5. Asignación explícita de uno o varios jueces elegidos por `admin`, sin mínimo, máximo, balanceo o creación automática.
6. Cancelación append-only antes de evaluación y cadenas de reemplazo exclusivamente por acciones administrativas explícitas.
7. Eliminación de gates de cobertura fija para generación/activación de paquetes ciegos.
8. Notificación opcional de nueva asignación mediante el outbox común.
9. Shell, dashboard, listado, detalle y cuenta responsive/accesibles para rol juez.
10. Pruebas, migración forward/rollback/forward, UAT local Firefox, documentación y ADR nuevo.

## Exclusiones estrictas

M7–M10 permanecen `NOT IMPLEMENTED / NOT AUTHORIZED`: no hay envío final, `submitted`, reapertura, consolidación, promedio, empate, ranking, ganador, resultados, nuevas campañas, retención o purga. Tampoco stage, commit, push, despliegue, producción, SMTP real, servicios externos ni datos reales. No se modifican PDF, hashes, aceptaciones, propuestas, snapshots, folios o archivos privados.

## Modelo y migraciones

### `judge_setup_links`

ULID público, `judge_profile_id`, hash SHA-256 del token, fingerprint HMAC del correo, `active_slot` nullable, emisión/expiración/consumo/invalidación UTC, actor administrativo y timestamps. FKs restrictivas, token único, único enlace vigente por perfil y checks de ciclo. Modelo guarded, no eliminable y mutado sólo mediante Actions transaccionales.

### Rúbrica v2

La v1 histórica conserva cinco criterios y continúa fijada en asignaciones/evaluaciones existentes. La v2 activa contiene cuatro criterios exactos, todos con peso `25.0000`, escala `0.0000..10.0000`, paso `0.5000`, precisión interna 4, visible 2 y `HALF_UP`; descripciones nulas. El catálogo de aplicación valida ambos contratos por versión/códigos, no por el estado activo actual.

La migración añade origen `admin|migration` a activación/sustitución, valida fail-closed el estado canónico, registra v2 y la activa de forma técnica sin inventar usuario. No crea competencias, asignaciones, paquetes o evaluaciones. El seeder produce el mismo estado final en instalación nueva. `down()` sólo procede sin referencias/evidencia v2 y restaura exactamente el estado anterior.

## Onboarding purpose-bound

- `GET /juez/configuracion/{setupLink}/{token}`: firma temporal y token válidos, sólo lectura.
- `POST /juez/configuracion/{setupLink}/{token}`: CSRF, firma, rate limit, password policy; bloquea link/perfil/usuario, revalida token, correo, rol y estado, inicializa contraseña, verifica correo, activa perfil, consume el enlace e invalida cualquier otro vigente en una transacción. Emite `Verified` post-commit una vez y redirige al login sin iniciar sesión.
- Reenvío invalida el enlace anterior. Reset genérico nunca verifica correo automáticamente.
- Cambio posterior de correo anula verificación y sincroniza el perfil a `pending_setup`.

Flags: `FLOWERFLOW_JUDGE_ACCOUNT_SETUP_NOTIFICATION_ENABLED=true`, `FLOWERFLOW_JUDGE_SETUP_LINK_TTL_MINUTES=2880`. El alta permite elegir el envío; sin selección queda `pending_setup` sin link/delivery. Fallo de enqueue no revierte la cuenta.

## Asignación manual, cancelación y conflicto

Rutas canónicas:

- `GET /panel/asignaciones`
- `GET /panel/asignaciones/propuestas/{submission}`
- `POST /panel/asignaciones/propuestas/{submission}/jueces`
- `GET /panel/asignaciones/{judgeAssignment}/cancelar`
- `POST /panel/asignaciones/{judgeAssignment}/cancelar`

La creación exige propuesta enviada/admitida con versión vigente, paquete activo coherente, única rúbrica activa v2, jueces exactos activos/verificados/elegibles, razón, password reciente y selección explícita. El navegador sólo aporta ULID de perfiles seleccionados y flag de notificación; IDs duplicados/extraños provocan rechazo atómico. No existe mínimo o máximo total ni por juez. `primary|substitute` es sólo informativo. Reintentos y carreras convergen sin duplicados.

Cancelar sólo es posible para `active`, sin conflicto y sin `Evaluation`; conserva actor, razón, fecha y limpia `current_slot`. El reemplazo por conflicto admite cualquier juez activo elegible y preserva cada eslabón. Se excluyen juez original, vigentes y quienes ya declararon conflicto en la propuesta. Nunca se crea reemplazo automático.

Los servicios de paquete ciego ya no exigen cobertura fija. Generar/activar paquete, admitir, migrar, sembrar o ejecutar GET nunca crea asignaciones.

## Notificación opcional de asignación

`FLOWERFLOW_JUDGE_ASSIGNMENT_NOTIFICATION_ENABLED=false`. Con flag encendido, la casilla “Notificar por correo a los jueces seleccionados” aparece desmarcada. Cada asignación nueva puede generar un delivery `judge_assignment_created` post-commit en `database/default`, idempotente por asignación/plantilla. El worker revalida assignment, perfil/verificación y plazo; cancelación/conflicto previos cancela la entrega. El mensaje muestra sólo categoría, ID opaco, plazo Hermosillo y CTA autenticado; no incluye participante, título, contenido, archivos u otros jueces.

## Generalización M6 y fórmula

El contexto de evaluación toma criterios únicamente de la rúbrica fijada. La apertura crea una fila de score por criterio de esa versión: cinco para v1 y cuatro para v2. Progreso, Request, cálculo, total y UI usan el conteo dinámico. El total permanece `NULL` si falta cualquier criterio.

Vectores v2: todos 0=`0.0000/0.00`; todos 10=`100.0000/100.00`; sólo primer criterio 0.5=`1.2500/1.25`; `7.5,8,6.5,9`=`77.5000/77.50`; incompleto=`NULL`. Se preservan vectores v1 y helper `HALF_UP`.

## UX del juez

Reutilizar el sistema visual Flower Flow existente, sin nueva dependencia o lenguaje visual. Shell exclusivo con sidebar desktop y offcanvas móvil: Inicio, Mis asignaciones, Cuenta y seguridad, Cerrar sesión, `aria-current`, skip link, foco visible e identidad/rol. Dashboard con métricas accionables y aviso de que el envío final no está habilitado. Listado ordenado por vencimiento, progreso dinámico y CTA contextual. Detalle con “Iniciar/Continuar evaluación” verde primario y “Declarar conflicto” `outline-warning` secundario, visible y accesible. Cuenta con nombre/correo/verificación, cambio de contraseña y 2FA opcional. Todo flujo esencial funciona sin JavaScript y cumple objetivos WCAG 2.2 AA.

## Autorización y auditoría

Panel sólo `admin` exacto con permisos existentes; juez sólo su asignación propia. Reviewer, participant, visitor, roleless y multirol fallan cerrados. Ningún ID de usuario/rúbrica/package/versión/evaluación se acepta como autoridad cliente.

Eventos: `judge.setup_link.issued|consumed|rejected`, `assignment.created|cancelled|replacement_created|notification_requested|notification_skipped`. Metadata limitada a IDs técnicos, cantidades, versión de rúbrica, booleanos, reason codes y transiciones; nunca correo, token, URL, nombre, propuesta, score, comentario, archivo o PII.

## Pruebas previstas

1. GET de setup y GET de asignaciones sin mutación.
2. Setup válido/expirado/usado/alterado/cruzado/correo cambiado y concurrencia.
3. Flags/checkboxes de ambos correos; reset genérico no verifica.
4. Migración v2 upgrade/fresh, drift, down seguro y preservación v1.
5. v1 cinco scores, v2 cuatro; vectores, límites, `NULL`, 409 y cálculo servidor.
6. Asignación de uno/varios jueces, más de cuatro por juez, cero mínimos y roles informativos.
7. Ningún GET, seeder, admisión o paquete crea assignments.
8. Idempotencia/concurrencia, cancelación antes/después de Evaluation.
9. Reemplazo por cualquier juez elegible y cadena explícita tras conflicto de replacement.
10. Paquete sin cobertura mínima.
11. Notificación exacta, idempotente, cancelable y sin PII.
12. Matriz de roles, IDOR, XSS, auditoría redactada y regresión M1–M6.

## Validaciones y UAT previstas

Forward/rollback/forward bajo guard; suites dirigidas M6A/M1–M6 y suite completa; Pint; Composer validate/platform/audit; Yarn audit; build Vite; JSON; rutas; scheduler; migrate status; diff check; enlaces Markdown; scans de secretos/PII/contenido de evaluación. UAT Firefox con correo fake y datos sintéticos a 1440×900, 1024×768 y 390×844 para onboarding, navegación, cuenta, asignación manual ilimitada/sin mínimos, paquete sin cobertura, rúbrica v2, evaluación prioritaria, conflicto, cancelación, replacement, 409, 403/404, teclado, foco, zoom, reflow y consola.

## Rollback

- Operativo: `FLOWERFLOW_EVALUATION_ENABLED=false`; apagar flags de setup/asignación para nuevas entregas.
- No borrar links, asignaciones, conflictos, rúbricas, evaluaciones, deliveries o auditoría.
- `down()` sólo sin evidencia/referencias M6A. Con evidencia, aplicar corrección aditiva.
- Código previo puede continuar leyendo v1/asignaciones históricas; no debe reinterpretar v2 ni forzar cobertura.

## Progreso

- [x] 2026-08-24: baseline exacto, árbol limpio, 21 migraciones y 86 rutas comprobados.
- [x] 2026-08-24: lecturas obligatorias, ADR, informes y PDF completados.
- [x] 2026-08-24: PDF página 4 extraído/renderizado y divergencia jurídica confirmada.
- [x] 2026-08-24: guard exacto `flowerflow_testing` demostrado sin secretos.
- [x] 2026-08-24: baseline dirigido M1–M6 verde, 54 pruebas/888 aserciones.
- [x] 2026-08-24: modelo/migración/Actions de setup purpose-bound y rúbrica v2 implementados.
- [x] 2026-08-24: asignación manual, cancelación, reemplazo explícito y paquete sin cobertura fija implementados.
- [x] 2026-08-24: notificación opcional de asignación integrada al outbox común.
- [x] 2026-08-24: M6 generalizado a v1/v2 y UX responsive del juez/cuenta completada.
- [x] 2026-08-24: forward/rollback/forward, rechazo de rollback con evidencia, pruebas, gates, UAT Firefox y documentación ejecutados.

## Hallazgos inesperados

- La Mecánica v1.1 sí fija cuatro criterios y “al menos tres jueces”, pero no fija pesos, escala, paso o precisión. La rúbrica v1 de cinco criterios es una decisión histórica de producto, no una transcripción del PDF.
- El código M4A considera las funciones `primary|substitute` normativas para cobertura/reemplazo y exige `4+2`; M6A debe conservarlas sólo como etiqueta informativa sin reescribir historia.
- M6 contiene literales de cinco criterios en creación, validación, cálculo, progreso y pruebas; la generalización debe quedar fijada por `rubric_version_id`, nunca por la activa actual.
- La carrera real PCNTL del enlace inicial expuso un orden de locks distinto entre consumo y sincronización de perfil; se unificó a perfil → usuario y la prueba concurrente quedó verde.
- El build detectó que el CSS generado de iconos no incluía los iconos nuevos. `yarn icons:write` lo regeneró de forma reproducible y el contrato final verifica 101 iconos.
- La UAT detectó dos defectos de claridad: el login posterior al setup decía “Cuenta participante” y la tarjeta de contexto administrativo comprimía etiquetas a 1024 px. Se corrigieron con contexto de login exclusivo de juez y apilado del formulario hasta el breakpoint `xl`; la revisión visual posterior quedó limpia.
- El wrapper local de Playwright tenía finales CRLF incompatibles con `/usr/bin/env bash`; la UAT continuó con el CLI oficial vía `npx --package @playwright/cli`, sin instalar una dependencia del proyecto.
- Un `migrate:status` sin variables testing explícitas leyó una configuración ambiental distinta y mostró un estado parcial no autoritativo. Se descartó ese resultado y el cierre se repitió con las cinco variables del guard y `SELECT DATABASE()` exacto: 22/22 migraciones `Ran` en `flowerflow_testing`.

## Decisiones

- Registrar el override jurídico de cero mínimos como riesgo de release, no esconderlo ni alterar el PDF.
- Mantener v1 como contrato histórico inmutable y activar v2 sólo para nuevas asignaciones.
- No reutilizar tokens genéricos del broker para verificar correo: el nuevo enlace purpose-bound tiene token/hash/ciclo propios.
- Reutilizar el outbox y el sistema visual actuales; no añadir dependencias ni workers.

## Resultados

`GO LOCAL/TEST` para M6A y `NO-GO RELEASE/PRODUCTION` por `OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`.

- Migración aditiva número 22: fresh `migrate --seed` termina con v1 histórica de cinco criterios y v2 activa de cuatro; el upgrade conserva v1 y activa v2 con origen `migration`. Forward/rollback/forward fue verde. `down()` rechazó correctamente evidencia sintética de setup y no eliminó datos.
- Pruebas dirigidas finales: 40 pruebas/459 aserciones. Suite completa definitiva: 203 pruebas/2,220 aserciones, 746.96 s, sin fallos.
- Cálculo v2 verificado: `0.0000/0.00`, `100.0000/100.00`, `1.2500/1.25`, `77.5000/77.50` e incompleto `NULL`; v1 y `HALF_UP` permanecen verdes.
- Gates: Pint, Composer validate/platform/audit, build Vite (784 módulos, 101 iconos), JSON (11), sintaxis PHP modificada (84), rutas (95), scheduler, 22 migraciones, diff check, enlaces Markdown y scans redactados verdes. `yarn audit` conserva únicamente el advisory bajo conocido de Quill, sin parche disponible.
- UAT Firefox sintética: setup GET puro/POST único y login de juez; shell/dashboard/listado/detalle/cuenta; cinco asignaciones para un juez sin límite; evaluación v2 parcial/completa con total servidor `77.50`; dos pestañas con 409; asignación manual con notificación opcional; bitácora sin PII; cancelación append-only; conflicto con revocación inmediata; reemplazo manual independiente; 403 para actor incorrecto; offcanvas, teclado, foco, consola limpia y reflow sin overflow a 1440×900, 1024×768 y 390×844.
- El intento de automatizar el porcentaje de zoom nativo de Firefox no expuso un cambio medible desde el CLI; el reflow equivalente y más estricto sí se comprobó a 390 CSS px. Se conserva como comprobación manual recomendable antes de cualquier release, sin afectar el `GO LOCAL/TEST` automatizado.
- No hubo stage, commit, push, despliegue, producción, SMTP real, servicios externos, PDF modificado ni datos reales.

Informe: `docs/27-phase-02b-m6a-judge-operations-reconciliation-report-2026-08-24.md`.
