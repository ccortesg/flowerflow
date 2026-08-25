# ADR 0010 — Reconciliación M6A de operaciones de jueces

Estado: aceptado para local/test · 2026-08-24 · `NO-GO RELEASE/PRODUCTION`

## Contexto

ADR-0008 y M1–M6 materializaron una operación `4 primary + 2 substitute`, sustitutos exclusivos, cobertura fija y rúbrica v1 de cinco criterios. La Mecánica pública v1.1 contiene cuatro criterios y exige “al menos tres jueces”, pero no define pesos, escala, paso o precisión. El propietario ordenó eliminar mínimos y máximos operativos, mantener la selección enteramente administrativa, hacer configurables dos correos y simplificar la experiencia del juez antes de M7.

El enlace genérico de reset de contraseña no demuestra que fue emitido específicamente para onboarding de juez. Por ello no debe reutilizarse como autoridad para verificar correo. La bitácora ADR-0009 ya ofrece el outbox idempotente y redactado para las comunicaciones existentes.

## Decisión

- Crear `judge_setup_links` con token opaco sólo en tránsito, hash persistido, fingerprint HMAC del correo, slot único, firma temporal, expiración, consumo/invalidación y actor emisor. GET es lectura; sólo POST válido configura contraseña, verifica correo, activa perfil y emite `Verified` post-commit. No inicia sesión. Reset genérico nunca verifica.
- Hacer configurable la emisión de setup globalmente y por alta. Un reenvío invalida el enlace anterior; fallo de enqueue no revierte la cuenta.
- Tratar `primary|substitute` sólo como clasificación informativa y conservar `max_active_assignments=NULL` para ambos.
- Crear asignaciones exclusivamente mediante selección explícita de uno o varios jueces por `admin`. No existen mínimo, máximo, balanceo, recomendación, sorteo o creación automática. GET, admisión, paquete, rúbrica, migración y seeder nunca crean assignments.
- Permitir cancelar una asignación activa sólo antes de conflicto/evaluación, conservando evidencia. Un conflicto se reemplaza sólo por otra selección admin; cualquier juez activo elegible puede ser replacement y una cadena requiere una acción por eslabón.
- Eliminar el gate de cobertura mínima de generación/activación del paquete ciego.
- Conservar rúbrica v1 histórica de cinco criterios. Activar mediante migración una v2 inmutable con los cuatro criterios de la Mecánica, pesos neutrales `25.0000`, escala `0..10`, paso `.5`, precisión 4/2 y `HALF_UP`. Los pesos son decisión de producto, no texto jurídico. Cada assignment fija su versión y M6 dimensiona scores/progreso/cálculo dinámicamente.
- Añadir notificación opcional de assignment mediante ADR-0009. El correo contiene categoría, ID opaco, plazo Hermosillo y CTA autenticado; excluye PII, título, contenido, archivos y otros jueces.
- Proveer shell exclusivo y responsive para juez. “Iniciar/Continuar evaluación” es primario; conflicto es secundario pero visible. Cuenta y seguridad incluye contraseña y 2FA opcional.
- Mantener M7–M10 fuera de alcance.

## Divergencia jurídica

La decisión de no exigir mínimo alguno contradice literalmente “al menos tres jueces” de la Mecánica v1.1. Se registra `OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`. No se modifica el PDF, su hash ni aceptaciones. Esta decisión permite validar código en local/test, pero bloquea release y producción hasta una reconciliación jurídica o aceptación formal separada.

## Consecuencias

- ADR-0008 permanece como historia de M1–M6, pero sus reglas operativas de `4+2`, cobertura fija, sustitutos exclusivos y v1 activa quedan sustituidas por este ADR para nuevas operaciones.
- Assignments/evaluations v1 siguen legibles y calculables; no hay backfill ni reinterpretación.
- Una propuesta puede tener cero o cualquier número de asignaciones vigentes; la UI debe mostrar conteos descriptivos, no “N de 4”.
- Error de correo no revierte cuenta o assignment. “Aceptado por transporte” no equivale a entrega.
- Rollback operativo: apagar `FLOWERFLOW_EVALUATION_ENABLED`, `FLOWERFLOW_JUDGE_ACCOUNT_SETUP_NOTIFICATION_ENABLED` y `FLOWERFLOW_JUDGE_ASSIGNMENT_NOTIFICATION_ENABLED`; conservar evidencia. La migración `down()` se niega cuando v2 o setup ya tienen evidencia.

## Alternativas rechazadas

- Verificar correo desde cualquier reset de contraseña.
- Crear cuatro assignments en bloque, imponer tres o más, seleccionar todos, repartir o balancear automáticamente.
- Reservar sustitutos exclusivamente para reemplazos.
- Reescribir assignments/evaluations v1 para adoptar v2.
- Inventar ponderaciones distintas o descripciones extensas no contenidas en la Mecánica.
- Ocultar la contradicción jurídica o tratarla como resuelta por código.

## Validación

Guard exacto `flowerflow_testing`; migración forward/rollback/forward; fresh seed y drift fail-closed; pruebas purpose-bound y concurrencia; selección manual y carrera; cero mínimos/más de cuatro; cancelación/cadena explícita; paquete sin cobertura; correo redactado; v1 cinco/v2 cuatro y BCMath; matriz RBAC/IDOR; suite completa y UAT Firefox en tres viewports. La evidencia final vive en el ExecPlan M6A y su informe.
