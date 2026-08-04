# ExecPlan propuesto: Fase 02B — jueces y evaluación

**Estado:** Blocked — definición lista; implementación no autorizada y decisiones críticas `PENDING`

**Creado:** 2026-08-04 (`America/Hermosillo`)

**Rama de definición:** `codex/phase-02-admissibility-review`

**Commit base de definición:** `5007b11a6c157898228ec027a387c86c270a33da`

## Propósito y resultado observable

Una vez que exista autorización posterior y se resuelvan los `PENDING`, un juez podrá consultar exclusivamente snapshots admitidos que tenga asignados, declarar conflicto, guardar un borrador y enviar una evaluación inmutable con la rúbrica aprobada. La administración podrá gestionar jueces, asignaciones, conflictos y excepciones mediante permisos granulares, y consultar una consolidación reproducible que nunca declarará ganadores por sí sola. Participantes, jueces no asignados y personal sin permiso quedarán fuera de identidad, residencia, notas internas y auditoría sensible.

Este ExecPlan no autoriza trabajo de implementación. La definición completa y el prompt condicionado están en `docs/15-phase-02b-judge-evaluation-definition.md`.

## Estado, alcance y fuentes

### Confirmado por fuentes canónicas

- panel de al menos tres jueces ciudadanos expertos;
- selección por experiencia en participación ciudadana, evaluación de proyectos y temas relacionados con las categorías;
- independencia, confidencialidad e imparcialidad;
- sólo propuestas admitidas pasan a evaluación;
- cuatro criterios canónicos de la Mecánica;
- cada juez asigna calificación y el resultado usa el promedio;
- máximo un ganador por categoría y posibilidad de categoría sin ganador;
- la plataforma no selecciona ganadores automáticamente;
- jueces reciben sólo información necesaria para evaluar;
- comunicaciones por correo relacionadas con evaluación y resultados;
- retención y publicación conforme al Aviso de Privacidad.

### `PENDING` que bloquea implementación

- integración/criterios verificables del panel y política de asignación;
- cantidad mínima de evaluaciones por propuesta;
- protocolo de evaluación ciega y anonimización de archivos/metadatos;
- conflictos, recusación, autoridad resolutora y reasignación;
- escala, pesos, fórmula, precisión y redondeo;
- umbral de calidad mínima;
- regla de desempate: los Términos remiten a la Mecánica, pero la Mecánica no contiene una;
- calendario, comentarios, reapertura/anulación y visibilidad participante;
- autoridad/quórum para consolidar, declarar y publicar;
- eventos/destinatarios/textos de correo;
- contrato con ganador/no ganador para retención.

### Incluido en la futura implementación, sólo tras desbloqueo

- perfiles de juez y obligaciones versionadas aprobadas;
- asignación idempotente al `submission_version` admitido;
- conflictos, rúbrica versionada y evaluaciones propias;
- proyección allowlist y descargas evaluables autorizadas;
- permisos, Policies, scopes, transacciones, idempotencia y auditoría;
- feature flag `FLOWERFLOW_JUDGING_ENABLED=false`;
- correos resilientes aprobados;
- interfaces Blade/Materialize responsive y accesibles;
- pruebas, QA y trazabilidad.

### Excluido

- declaración/publicación de ganadores y automatización de desempate;
- eliminación automática de residencia;
- comunicaciones masivas, ARCO completo y reportes avanzados;
- API pública, SPA, Redis, microservicios y dependencias nuevas no autorizadas;
- producción, AWS, Apache, MySQL productivo, Supervisor, SMTP real y datos personales reales.

## Contexto del repositorio

- Laravel 12, Blade, Materialize/Pixinvent 3.0.0 y Vite continúan.
- `Submission.status` permanece `draft`, `submitted`, `withdrawn`.
- Fase 02A separa la admisibilidad en `eligibility_reviews` y fija el snapshot mediante `submission_version_id`.
- `SubmissionPolicy` permite hoy owner/admin/reviewer; no debe añadirse `judge` a esa vista amplia.
- `audit_logs`, colas transaccionales y almacenamiento privado ya existen y deben reutilizarse.
- El rol sintético `judge` sólo aparece en una prueba negativa de Fase 02A; no hay permisos, rutas, modelos ni esquema productivo de evaluación.
- UTC es persistencia; `America/Hermosillo` es presentación y regla temporal.
- ADR-0004 permanece `Proposed` hasta aprobar la matriz y las decisiones pendientes.

## Modelo y contratos propuestos

Tablas candidatas aditivas y reversibles:

- `judge_profiles`;
- `judge_assignments`;
- `conflict_declarations`;
- `rubrics` y `rubric_criteria`;
- `evaluations` y `evaluation_scores`;
- `evaluation_events`;
- `result_snapshots` sólo si se aprueba su contrato; `winner_decisions` queda fuera del núcleo.

Estados candidatos:

- asignación: `assigned`, `in_progress`, `conflict_declared`, `submitted`, `reopened`, `voided`;
- evaluación: `draft`, `submitted`, `reopened`, `voided`;
- conflicto: `declared`, `confirmed`, `dismissed`;
- rúbrica: `draft`, `active`, `retired`.

`reopened`, `voided`, `confirmed`, `dismissed` y sus actores no son implementables hasta aprobar gobernanza. La asignación y la evaluación se actualizan en una sola transacción y el avance se deriva; no se agregan estados a `submissions`.

Permisos candidatos:

- juez: `view judge dashboard`, `view assigned submissions`, `declare evaluation conflict`, `create evaluations`, `submit evaluations`, `view own evaluations`;
- gestión: `manage judges`, `manage judge assignments`, `resolve evaluation conflicts`, `manage rubrics`, `view all evaluations`, `reopen evaluations`, `void evaluations`;
- separado/futuro: `consolidate results`, `declare winners`, `publish winners`;
- auditoría: `view evaluation audit`.

Invariantes:

1. Sólo snapshots admitidos pueden asignarse.
2. Juez sólo ve asignación propia activa.
3. Conflicto bloquea inmediatamente lectura y escritura.
4. Proyección de juez excluye PII, residencia, aclaraciones, notas y auditoría sensible.
5. Evaluación enviada, snapshot y versión de rúbrica no se mutan.
6. Cálculo y promedio no declaran ganador.
7. Operaciones críticas son transaccionales, idempotentes y auditadas.
8. Fechas en UTC y presentación `America/Hermosillo`.

## Plan por pasos condicionado

1. Obtener aprobación trazable de todos los `PENDING` y actualizar PDF/adenda jurídica cuando corresponda.
2. Autorizar expresamente implementación, confirmar baseline Git limpio y crear la rama aprobada.
3. Actualizar este ExecPlan con decisiones, nombres finales, estados y criterios exactos.
4. Crear migraciones aditivas/reversibles, enums, modelos, relaciones y factories sintéticas.
5. Implementar perfiles, aceptación versionada y permisos sin ampliar `SubmissionPolicy` a juez.
6. Implementar proyección allowlist, asignación idempotente y acceso a anexos evaluables autorizado/auditado.
7. Implementar conflicto/recusación conforme a la política aprobada.
8. Implementar rúbrica versionada y cálculo exacto en servidor.
9. Implementar borrador, confirmación, envío y excepciones autorizadas con locks/transacciones.
10. Implementar correos after-commit y auditoría con redacción de PII.
11. Implementar rutas, menús JSON y vistas por página detrás de `FLOWERFLOW_JUDGING_ENABLED=false`.
12. Ejecutar pruebas de dominio, permisos negativos, concurrencia, archivos, correo y zona horaria.
13. Ejecutar QA real sólo de pantallas nuevas con datos sintéticos y eliminar evidencia temporal.
14. Actualizar documentación, trazabilidad, riesgos, runbook y rollback; no publicar sin una autorización posterior.

## Validación futura

```text
php artisan test
php artisan route:list
php artisan view:cache
composer validate --strict
composer audit
vendor/bin/pint --dirty
corepack yarn build
git diff --check
git status --short
```

Además:

- `migrate`, rollback de migraciones nuevas y `migrate:fresh --seed` sólo en MySQL desechable confirmado;
- pruebas de juez A/B, participante, reviewer, admin con/sin permiso, auditor y usuario sin rol;
- canarios sintéticos que demuestren ausencia de PII/residencia en vista, query, descarga, export, correo y log;
- cálculo con límites, decimales, redondeo, empate y umbral aprobados;
- doble clic/concurrencia e idempotencia de asignación/envío;
- falla SMTP sin 500 ni rollback;
- flag apagado/encendido, UTC/Hermosillo y regresión Fases 01/02A;
- navegador en escritorio/tableta/móvil, teclado, foco, zoom y consola limpia.

Una validación requerida en rojo impide cerrar el plan.

## Despliegue y rollback futuro

Este milestone documental no se despliega. Para una implementación posterior:

- el flag permanece apagado durante migración y smoke tests;
- las migraciones nuevas deben ser aditivas y reversibles;
- el rollback funcional apaga `FLOWERFLOW_JUDGING_ENABLED`, detiene nuevas asignaciones/jobs de evaluación y conserva evidencia;
- no se eliminan evaluaciones enviadas ni eventos para revertir una publicación;
- no se toca residencia ni se ejecuta retención automática;
- cualquier despliegue exige autorización distinta, backup verificado, UAT y rollback probado.

## Registro vivo

- [x] 2026-08-04 MST — Preflight exacto: rama `codex/phase-02-admissibility-review`, commit `5007b11a6c157898228ec027a387c86c270a33da`, árbol e índice limpios.
- [x] 2026-08-04 MST — Leídos completamente `AGENTS.md`, `.agent/PLANS.md`, seis ExecPlans, seis ADR, 39 archivos Markdown rastreados y los tres PDF canónicos.
- [x] 2026-08-04 MST — Renderizados e inspeccionados visualmente los 14 folios jurídicos; extracción textual revisada y hashes verificados.
- [!] 2026-08-04 MST — Los Términos exigen reglas de desempate de la Mecánica, pero la Mecánica v1.0 no incluye ninguna; se requiere adenda jurídica y no se permite inventar una.
- [!] 2026-08-04 MST — “Al menos tres jueces” fija el tamaño mínimo del panel, no la cantidad de evaluaciones por propuesta; asignación y cobertura quedan `PENDING`.
- [!] 2026-08-04 MST — Los cuatro criterios y el promedio están confirmados, pero escala, pesos, fórmula individual, redondeo y calidad mínima permanecen `PENDING`.
- [x] 2026-08-04 MST — Preparadas matriz de decisiones, RBAC/datos, estados, flujos, amenazas, pruebas y prompt condicionado en `docs/15-phase-02b-judge-evaluation-definition.md`.
- [ ] Obtener decisiones aprobadas para los 14 grupos de bloqueo de la puerta de implementación.
- [ ] Sustituir marcadores del prompt condicionado y registrar el commit documental aprobado como nueva base.
- [ ] Obtener autorización expresa antes de crear rama o implementar.

## Decisiones

- [x] 2026-08-04 MST — Se distinguen reglas jurídicas, decisiones técnicas vigentes, propuestas condicionadas y `PENDING`.
- [x] 2026-08-04 MST — No se amplía `Submission.status`; el avance de evaluación se deriva.
- [x] 2026-08-04 MST — No se reutiliza la Policy/vista administrativa de propuestas para jueces.
- [x] 2026-08-04 MST — La exclusión de identidad, residencia, aclaraciones y notas es fail-closed en todas las capas.
- [x] 2026-08-04 MST — Ganadores/publicación y retención ejecutable siguen fuera del núcleo de Fase 02B.
- [x] 2026-08-04 MST — No se eligieron valores por defecto para decisiones que cambian calificaciones o resultados.
