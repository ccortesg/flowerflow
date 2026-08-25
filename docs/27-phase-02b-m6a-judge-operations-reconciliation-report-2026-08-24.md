# Informe de implementación — Fase 02B M6A

**Fecha:** 2026-08-24 (`America/Hermosillo`)  
**Milestone:** onboarding, asignación manual, rúbrica legal y experiencia del juez  
**Resultado:** `GO LOCAL/TEST`  
**Release/producción:** `NO-GO RELEASE/PRODUCTION`

## 1. Baseline, alcance y guard

- Checkout y Git toplevel: `/home/ccortesg/workspace/flowerflow`.
- Rama: `codex/submission-deadline-extension`.
- HEAD/upstream/merge-base inicial: `d3f616c86d72bfd32c1545205df057e19cf765ea`.
- Árbol inicial limpio; 21 migraciones y 86 rutas propias.
- Baseline M1–M6 dirigido: 54 pruebas/888 aserciones, verde.
- Guard usado antes de cada operación de esquema: `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_DATABASE=flowerflow_testing`, `DB_USERNAME=flowerflow_testing_user` y `SELECT DATABASE()=flowerflow_testing`.
- Sólo se usaron usuarios, propuestas, paquetes y evaluaciones sintéticos. No hubo stage, commit, push, despliegue, producción, SMTP real ni servicio externo.

## 2. Divergencia jurídica y estado de salida

El PDF público v1.1 permanece inmutable, con SHA-256 `11c399ca84735d7dbcb17174e192582c93589afa5100c0250753ca15def4db36`. La página 4 fija cuatro criterios y “al menos tres jueces”, pero no pesos, escala, paso o precisión.

M6A aplica la decisión expresa del propietario de no exigir mínimo de asignaciones y registra `OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`. Por ello el código puede quedar `GO LOCAL/TEST`, pero ningún release o despliegue queda autorizado hasta reconciliación jurídica o aceptación formal separada. No se modificaron PDF, hashes ni aceptaciones.

## 3. Onboarding purpose-bound

La nueva tabla `judge_setup_links` conserva ULID, perfil, hash de token, fingerprint HMAC de correo, slot vigente, emisión, expiración, consumo, invalidación y actor. El token no se persiste en claro; el modelo es guarded y no eliminable.

- `GET /juez/configuracion/{setupLink}/{token}` valida firma y propósito sin mutar.
- `POST` bloquea link, perfil y usuario; revalida token/correo/rol/estado; guarda contraseña, `password_initialized_at`, `email_verified_at` y perfil activo en una transacción; consume e invalida links; emite `Verified` post-commit una vez; no inicia sesión.
- Reenvío invalida el link anterior. Reset genérico no verifica correo. Cambiar correo revoca verificación y devuelve el perfil a `pending_setup`.
- El alta permite elegir el correo sólo si el flag global está activo; un fallo al encolar no revierte la cuenta.

La prueba PCNTL real llevó dos POST simultáneos al mismo link. Se corrigió el orden de locks a perfil → usuario; un solo POST consume y el otro falla cerrado.

## 4. Rúbricas v1/v2 y cálculo

`EvaluationRubricContract` contiene dos contratos inmutables:

- v1 histórica: cinco criterios, preservada para asignaciones existentes.
- v2 activa: cuatro criterios de la Mecánica, pesos de producto `25.0000`, escala `0.0000–10.0000`, paso `0.5000`, cuatro decimales internos, dos visibles y `HALF_UP`.

La migración M6A añade origen técnico `admin|migration`, marca v1 como superseded cuando correspondía y activa v2 sin inventar usuario. Fresh `migrate --seed` y upgrade terminan en el mismo contrato. Estados divergentes fallan cerrados. No se modifican asignaciones o evaluaciones existentes.

M6 obtiene criterios sólo de la rúbrica fijada: v1 crea cinco scores; v2 cuatro. Progreso, validación, cálculo y UI son dinámicos. Vectores v2 ejecutados:

| Scores | Total interno | Presentación |
|---|---:|---:|
| `0,0,0,0` | `0.0000` | `0.00` |
| `10,10,10,10` | `100.0000` | `100.00` |
| `0.5,0,0,0` | `1.2500` | `1.25` |
| `7.5,8,6.5,9` | `77.5000` | `77.50` |
| un criterio ausente | `NULL` | no disponible |

Las pruebas v1 y los vectores directos `HALF_UP` continúan verdes.

## 5. Asignación manual, cancelación y reemplazo

El admin dispone de lista, detalle por propuesta, creación manual y confirmación de cancelación. La pantalla explica siete invariantes visibles: propuesta/versión admitida, rúbrica, plazo, paquete, selección explícita, notificación opcional y justificación/password.

- No existe mínimo o máximo de jueces ni límite de propuestas por juez; `max_active_assignments` permanece `NULL`.
- `primary|substitute` sólo informa. Ambos pueden ser asignación inicial o reemplazo.
- No existe selección automática, balanceo, aleatoriedad ni “seleccionar todos”.
- Ningún GET, migración, seeder, admisión o paquete crea assignments.
- Peticiones repetidas/concurrentes convergen mediante locks y unicidad.
- Cancelación sólo para assignment activo, sin conflicto ni evaluación; limpia el slot y conserva evidencia.
- Todo reemplazo exige selección administrativa explícita de cualquier juez activo elegible. Un replacement en conflicto requiere otra acción explícita y conserva la cadena.
- Los paquetes ciegos pueden generarse y activarse con cero asignaciones.

## 6. Correo de nueva asignación

`CommunicationType::JudgeAssignmentCreated` usa el outbox común. Con el flag global activo, la casilla inicia desmarcada. Sólo las asignaciones creadas en esa operación generan delivery post-commit. El worker revalida assignment, perfil/verificación y plazo; cancela si el assignment ya no es operativo.

HTML y texto plano muestran ambas marcas, categoría, ID opaco, plazo Hermosillo y CTA autenticado. No incluyen identidad, título, contenido, anexos u otros jueces. La UAT con correo fake mostró el tipo “Nueva asignación de evaluación”, destinatario enmascarado y estado de transporte, sin PII expuesta.

## 7. UX y autorización

El rol juez usa un shell exclusivo con Inicio, Mis asignaciones, Cuenta y seguridad y Cerrar sesión; sidebar desktop, offcanvas móvil, skip link, `aria-current`, identidad/rol y foco visible. Dashboard y listado priorizan vencimiento y siguiente acción. En detalle, “Iniciar/Continuar evaluación” es el CTA verde primario y “Declarar conflicto” es secundario.

La cuenta permite cambio de contraseña y 2FA sin campos de participante. El redirect del setup usa `/login?context=judge`, con texto exclusivo de juez y sin registro de participante.

El panel de asignaciones exige rol exacto admin y permisos existentes. Juez sólo accede a su assignment. Reviewer, participant, visitor, roleless y multirol fallan cerrados. La UAT obtuvo 403 al intentar usar un assignment de juez con cuenta admin.

## 8. Migración, compatibilidad y rollback

- Migración 22 aditiva; no hace backfill de assignments/evaluations.
- Forward/rollback/forward verde en `flowerflow_testing`.
- Upgrade sintético: v1 activa previa quedó superseded con origen `migration`; v2 quedó activa; v1 conservó cinco criterios y v2 cuatro.
- Fresh seed produjo v1 histórica y v2 activa.
- `down()` rechazó evidencia sintética de setup; no borró datos para forzar rollback.
- Rollback operativo: apagar evaluación y flags de setup/asignación; conservar links, rúbricas, asignaciones, conflictos, deliveries, evaluaciones y auditoría.

## 9. Pruebas y gates reales

| Gate | Resultado |
|---|---|
| Dirigidas definitivas | 40 pruebas/459 aserciones, verde |
| Suite completa | 203 pruebas/2,220 aserciones, 746.96 s, verde |
| Pint | verde |
| Composer validate/platform/audit | verde; cero advisories |
| Yarn audit | un advisory bajo conocido de Quill; sin parche disponible; cero moderados/altos/críticos |
| Build | 101 iconos, 784 módulos, tres assets Vite, verde |
| JSON | 11 archivos válidos |
| Sintaxis PHP modificada/nueva | 84 archivos válidos |
| Rutas propias | 95 |
| Scheduler | exports purge; communication reconcile; context purge |
| Migraciones | 22/22 aplicadas con variables testing explícitas |
| Diff/links/scans | whitespace, links Markdown, secretos y metadata M6A, verdes |

## 10. UAT Firefox sintética

Se recorrió Firefox a 1440×900, 1024×768 y 390×844 con servidor/testing y correo fake:

- enlace inicial, configuración y login de juez;
- dashboard, cinco asignaciones para el mismo juez, listado y cuenta;
- CTA de evaluación primario y conflicto secundario;
- v2 inicia en `0 de 4`, parcial queda `1 de 4` con total `NULL`, completa muestra `4 de 4` y `77.50` del servidor;
- dos pestañas con el mismo `lock_version`: primera guarda, segunda recibe 409 accesible y no sobrescribe;
- asignación admin manual, notificación opcional y bitácora;
- cancelación append-only;
- conflicto del juez, pérdida inmediata de evaluación y reemplazo manual independiente;
- 403 para actor incorrecto, consola con cero errores/warnings, teclado, foco, offcanvas y reflow sin overflow (`scrollWidth=clientWidth`) a 390 y 1024.

La inspección visual detectó y corrigió el texto de login de juez y la compresión del contexto administrativo a 1024 px. Capturas sintéticas:

- `output/playwright/m6a-judge-dashboard-1440x900.png`
- `output/playwright/m6a-judge-assignments-1024x768.png`
- `output/playwright/m6a-judge-detail-390x844.png`
- `output/playwright/m6a-admin-manual-assignment-1024x768-corrected.png`

El CLI no expuso un porcentaje confiable de zoom nativo de Firefox pese a ejecutar el atajo. El reflow equivalente, y más estricto que 200 % sobre 1024 px, sí pasó a 390 CSS px. Se recomienda repetir el porcentaje de zoom manual antes de release.

## 11. Archivos afectados

Áreas exactas:

- Configuración/rutas: `.env.example`, `config/flowerflow.php`, `routes/web.php`, `app/Providers/AppServiceProvider.php`.
- Setup: `app/Models/JudgeSetupLink.php`, `app/Actions/Judges/ConsumeJudgeSetupLink.php`, `app/Actions/Judges/SendJudgeSetupNotification.php`, `app/Services/JudgeSetupLinkValidator.php`, `app/Exceptions/JudgeSetupLinkRejected.php`, controller/request y vistas `judge/setup` y mail de alta.
- Rúbrica/evaluación: `app/Services/EvaluationRubricContract.php`, `app/Services/EvaluationDraftCalculator.php`, Actions/Requests de rúbrica y evaluation, vistas y pruebas versionadas.
- Asignación: nuevos Actions `AssignJudgesToSubmission`, `CancelJudgeAssignment`, `SendJudgeAssignmentNotification`; controller/requests/policy/coverage; vistas de panel y mail; se retiró `ActivateSubmissionCoverage` y su Request.
- UX juez: controllers de dashboard/asignación/cuenta, `resources/views/layouts/flowerflow.blade.php`, `resources/views/partials/judge-navigation.blade.php`, `resources/views/judge/*`, login y CSS generado de iconos.
- Migración: `database/migrations/2026_08_24_120000_reconcile_judge_operations_and_activate_legal_rubric_v2.php`.
- Pruebas: `JudgeSetupLinkTest`, `JudgeSetupLinkConcurrencyTest`, RBAC, onboarding, assignments/conflicts/concurrencia, blind package, rubrics, evaluation/decimal, ledger y scenario support.
- Documentación: ExecPlan M6A; ADR-0010; docs 01–06, 08–11, 16, 18–24, product spec, trazabilidad, handoff y este informe.

La lista autoritativa del árbol no publicado es `git status --short`; no se realizó stage ni commit.

## 12. Riesgos residuales

1. Bloqueante: reconciliar jurídicamente “al menos tres jueces” frente al override sin mínimos.
2. Repetir zoom nativo manual y UAT de aceptación antes de release.
3. El advisory bajo de Quill no tiene parche disponible; conservar sanitización servidor y no usar HTML cliente como autoridad.
4. SMTP real, entregabilidad, workers, scheduler y producción siguen no verificados/no autorizados.
5. M7–M10 continúan `NOT IMPLEMENTED / NOT AUTHORIZED`.

