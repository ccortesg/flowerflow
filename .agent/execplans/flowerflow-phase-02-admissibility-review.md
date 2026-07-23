# ExecPlan: Fase 02A — revisión de admisibilidad y residencia privada

**Estado:** Ready for review
**Creado:** 2026-07-16 (`America/Hermosillo`)
**Rama:** `codex/phase-02-admissibility-review`
**Commit base:** `68e7002fb6cd1d300bd6f05584ce8ee96de0481c`

## Propósito y resultado observable

Una persona participante podrá consultar el estado de admisibilidad de una propuesta enviada, responder aclaraciones sin alterar el snapshot y cargar comprobantes privados para sí o para integrantes de su equipo cuando se soliciten. Personal con permisos de revisión podrá iniciar el expediente, solicitar aclaraciones o residencia, revisar evidencia, resolver con motivo público y notas internas separadas, y consultar una bitácora inmutable. Una falla de correo no revertirá decisiones ni producirá error 500.

## Alcance, fuentes y pendientes

Incluido:

- expediente idempotente ligado a la propuesta y a su versión inmutable enviada;
- estados respaldados para revisión, aclaración y residencia;
- aclaraciones append-only con texto de hasta 2,000 caracteres y adjuntos privados controlados;
- solicitudes de residencia por representante o integrante, máximo tres archivos y 10 MiB por persona/solicitud;
- PDF, JPEG, PNG y WebP con extensión, MIME, firma, nombre seguro, hash, ULID y rechazo de PDF cifrado;
- decisiones transaccionales, permisos granulares, Policies, consultas filtradas y auditoría sensible;
- correos dual-brand en español de México, en cola después del commit y con fallas resilientes;
- feature flag `FLOWERFLOW_ADMISSIBILITY_REVIEW_ENABLED=false`;
- comando idempotente de backfill y reporte dry-run de retención, sin eliminar archivos;
- interfaz Blade responsive para participante, reviewer y admin.

Excluido:

- jueces, asignación, evaluación ciega, rúbricas, ponderaciones, calificaciones y ganadores;
- comunicaciones masivas, ARCO completo, reportes avanzados y borrado automático;
- dependencias de producción nuevas, Redis, API, SPA, microservicios y despliegue.

Fuentes autoritativas:

- `formatos/01_Mecanica_Convocatoria_Hermosillo_Florece_2026.pdf`;
- `formatos/02_Terminos_y_Condiciones_Plataforma_Flower_Flow_2026.pdf`;
- `formatos/03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026.pdf`;
- prompt autorizado de Fase 02A para límites técnicos, estados y alcance.

`PENDING`:

- no existe una antigüedad máxima definida para interpretar “comprobante reciente”; no se automatiza rechazo por fecha;
- el tipo “documento equivalente” requiere justificación humana y no una lista inventada;
- la eliminación depende de la futura determinación de ganadores; esta fase sólo calcula y reporta candidaturas de retención;
- el catálogo final de motivos jurídicos puede requerir aprobación adicional; se almacena motivo público libre y comprensible sin inventar resoluciones automáticas.

## Contexto e invariantes

- Laravel 12, Blade, Materialize/Pixinvent 3.0.0 y Vite actuales permanecen.
- `Submission.status` conserva exclusivamente `draft`, `submitted` y `withdrawn`.
- El expediente apunta a `submission_versions`; el snapshot no se actualiza ni se duplica en formularios editables.
- UTC es la persistencia; `America/Hermosillo` es la presentación y regla de negocio.
- Los documentos de residencia usan un disco privado dedicado, sin URL pública ni `storage:link`.
- Una aclaración abierta impide decisión final hasta quedar contestada o cerrada.
- Si existe solicitud activa de residencia, todas las personas requeridas deben estar verificadas para admitir.
- Rechazar residencia no resuelve silenciosamente la propuesta; requiere una decisión humana posterior.
- Una operación repetida no crea una segunda resolución ni un segundo evento final.
- Notas internas y auditoría sensible nunca se exponen al participante ni a roles sin permiso.

## Modelo y contratos

Tablas aditivas y reversibles:

- `eligibility_reviews`: expediente, snapshot, reviewer, estado, motivo público, notas internas y fechas;
- `eligibility_review_events`: transiciones inmutables y actor;
- `clarification_requests`, `clarification_responses`, `clarification_response_files`;
- `residency_document_requests`, `residency_documents`;
- `audit_logs` para cargas, vistas, descargas, revisiones y decisiones sensibles.

Estados:

- revisión: `pending`, `in_review`, `clarification_requested`, `admitted`, `not_admitted`;
- aclaración: `open`, `answered`, `closed`;
- residencia: `requested`, `under_review`, `verified`, `rejected`, `cancelled`.

Permisos:

- `view admissibility reviews`;
- `review admissibility`;
- `request clarification`;
- `decide admissibility`;
- `view residency documents`;
- `download residency documents`;
- `manage admissibility reviews`.

## Plan por pasos

1. Reconciliar documentalmente el milestone anterior y registrar baseline real.
2. Crear migración, enums, modelos, relaciones, casts, Policies y permisos.
3. Implementar creación/backfill idempotentes, máquina de estados y auditoría.
4. Implementar inspección, almacenamiento y descarga de archivos privados.
5. Implementar correos resilientes y despacho posterior al commit.
6. Implementar rutas, Form Requests, controladores y vistas participante/panel.
7. Cubrir reglas, permisos, archivos, UTC/Hermosillo, filtros, flag e idempotencia.
8. Ejecutar migración/rollback y validaciones sobre MySQL desechable.
9. Ejecutar QA en navegador sólo para pantallas nuevas y documentarlo.
10. Actualizar documentación, trazabilidad, riesgos, pendientes y runbook.

## Validación

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

- `migrate`, rollback de las migraciones nuevas y `migrate:fresh --seed` únicamente en MySQL desechable confirmado;
- pruebas negativas de roles, accesos cruzados, descarga directa, MIME/firma/tamaño/cuota/nombre/PDF cifrado;
- query log o conteo para impedir N+1 en el listado;
- configuración y rutas con flag apagado/encendido;
- navegador real con datos sintéticos en escritorio, tableta, móvil, teclado, foco, zoom y consola.

Si Pint global encuentra deuda previa, se ejecuta sobre archivos PHP modificados y se documenta la diferencia. Una validación requerida roja impide cerrar el plan.

## Despliegue y rollback

No se despliega en este milestone. Las migraciones son aditivas y su `down()` elimina sólo tablas nuevas en orden inverso. El rollback local consiste en desactivar el flag, detener workers de prueba, revertir la migración nueva en la base desechable y restaurar el código local. Los archivos de prueba se eliminan de los discos privados temporales. No se modifica producción.

## Registro vivo

- [x] 2026-07-16 MST — Preflight Git exacto: árbol limpio, rama `codex/phase-01-public-submissions`, commit `68e7002fb6cd1d300bd6f05584ce8ee96de0481c`.
- [x] 2026-07-16 MST — Creada y activada `codex/phase-02-admissibility-review`; no se ejecutó fetch, pull, stage, commit ni push.
- [x] 2026-07-16 MST — Releídos AGENTS, PLANS, todos los ExecPlans/ADR/Markdown de `docs/`, README y documentos jurídicos canónicos.
- [x] 2026-07-16 MST — Los 14 folios de los tres PDF se renderizaron e inspeccionaron; hashes canónicos coinciden con las copias publicadas.
- [x] 2026-07-16 MST — Baseline en MySQL 8.0 desechable aislado: 50 pruebas, 500 aserciones, verde; Composer validate/audit y build Vite verdes.
- [!] 2026-07-16 MST — Pint global de prueba reporta deuda histórica fuera del alcance, incluida `_referencia/`; se usará Pint acotado a archivos modificados.
- [x] 2026-07-16 MST — Reconciliado el QA histórico: se conserva el fallo del navegador integrado y se cierra el milestone con la aceptación manual posterior del usuario, sin inventar evidencia.
- [x] 2026-07-16 MST — Implementados modelo separado, acciones transaccionales, permisos y Policies, almacenamiento privado, auditoría, correos resilientes e interfaces participante/panel.
- [x] 2026-07-16 MST — Migración nueva validada hacia adelante, rollback y `migrate:fresh --seed` exclusivamente sobre MySQL 8.0 desechable en el puerto 3307.
- [x] 2026-07-16 MST — QA real de las superficies nuevas con participante individual, representante de equipo, reviewer, admin y cuenta sin permisos; escritorio, tableta, móvil, teclado, foco y zoom 200% documentados en `docs/design-qa-phase-02-admissibility.md`.
- [x] 2026-07-16 MST — El QA detectó y permitió corregir un script inline bloqueado por CSP, etiquetas de eventos en inglés y la página 403 sin localizar; las pantallas autorizadas cerraron con consola limpia.
- [x] 2026-07-16 MST — Los archivos privados, capturas, descargas, datos sintéticos, servidor y perfiles temporales se eliminaron al terminar el QA.
- [x] 2026-07-16 MST — Gate final posterior al QA: 72 pruebas y 696 aserciones verdes; Pint sobre cambios, `composer validate --strict` y `composer audit` verdes; Vite 6.3.5 construyó 2,220 módulos con la advertencia conocida de chunks grandes y sin error.

## Decisiones

- [x] 2026-07-16 MST — La admisibilidad se modela aparte de `Submission.status`.
- [x] 2026-07-16 MST — Los límites de archivos autorizados por el prompt se documentan como controles técnicos, no requisitos jurídicos.
- [x] 2026-07-16 MST — “Reciente” no recibe un número de meses inventado; la antigüedad permanece en revisión humana.
- [x] 2026-07-16 MST — La residencia rechazada requiere una decisión posterior explícita; no dispara no admisión automática.
- [x] 2026-07-16 MST — La fecha candidata de retención se calcula y reporta, pero no se elimina mientras falte la determinación de ganadores.
