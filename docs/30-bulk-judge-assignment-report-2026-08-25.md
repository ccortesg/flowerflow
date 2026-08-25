# Informe — asignación simultánea de múltiples propuestas a un juez

**Fecha:** 2026-08-25 · **Entorno:** local/testing sintético · **Producción:** no autorizada

## Resultado observable

El panel incorpora un mismo asistente desde Propuestas y Asignaciones. Un administrador exacto selecciona un juez y hasta veinte propuestas, ejecuta un preflight de sólo lectura y confirma tres fases. La ejecución conserva una transacción por propuesta, admite sólo expedientes elegibles, materializa un paquete ciego personalizado y crea la asignación manual con versión, rúbrica, plazo, actor y razón propios.

La operación devuelve estado por propuesta y permite éxito parcial. Un correo opcional se consolida en un único delivery al juez, sin participante, título, contenido, archivos o motivos.

## Contratos implementados

- Flags `FLOWERFLOW_BULK_JUDGE_ASSIGNMENT_ENABLED=false` y `FLOWERFLOW_BULK_JUDGE_ASSIGNMENT_LIMIT=20`.
- Rutas `GET/POST /panel/asignaciones/masiva`, preflight `/revisar` y resultado `/resultado`.
- Rol exacto admin y permisos acumulativos de admisibilidad, paquete y asignación.
- Intención cifrada de quince minutos y payload allowlist.
- Locks, comparación del estado observado, idempotencia por `operation_id` y auditoría terminal.
- Reutilización de workflows de admisibilidad, paquete ciego y asignación; no hay endpoint de admisión directa ni escritura desde GET.
- Tipo de outbox `judge.assignment_bulk_created`, plantilla HTML/texto dual-brand y revalidación por assignment.

## Evidencia ejecutada

- Guard exacto: `testing`, MySQL `127.0.0.1`, `flowerflow_testing`, usuario de pruebas y `SELECT DATABASE()` coincidente.
- Baseline previo: 33 pruebas/341 aserciones.
- Regresión dirigida conjunta: 40 pruebas/438 aserciones.
- Suite completa: 228 pruebas/2,653 aserciones, sin fallas.
- Prueba dirigida final del módulo: 9 pruebas/123 aserciones verdes y un benchmark opt-in omitido por defecto.
- Benchmark opt-in ejecutado por separado: 1 prueba/5 aserciones; 20 propuestas con un PDF sintético de 10 MiB cada una, 200 MiB acumulados. La fase de ejecución tardó 2.589 s y creó las veinte asignaciones sin fallas.
- La prueba de rollback fuerza una falla del paquete después de admitir y demuestra que esa propuesta no conserva admisión, paquete, asignación ni auditorías de esas fases.
- Pint, Composer validate/platform/audit, build Vite, JSON, `route:list`, `schedule:list`, 24 migraciones aplicadas, backfill dry-run con cero faltantes y `git diff --check`: verdes.
- `yarn audit`: únicamente el advisory bajo conocido de Quill, severidad baja y sin parche disponible.
- UAT Firefox con datos `example.test` en 1440x900, 1024x768 y 390x844: acceso desde Propuestas, contraseña reciente/actual, selección, preflight, tres confirmaciones y resultado parcial real de dos éxitos y un `selection_state_changed`; consola con cero errores y advertencias.
- El propietario pidió agilizar el cierre. No se repitió una segunda suite completa después de retirar el nombre del saludo del correo y agregar pruebas; esos únicos cambios posteriores quedaron cubiertos por la prueba dirigida final, Pint y build.

## Estado

`GO LOCAL/TEST`. No se añadió migración, dependencia o worker. La ruta continúa default-off y exige ejecutar previamente el backfill hasta obtener cero faltantes. El benchmark completó el request local, pero no acredita timeout, capacidad o configuración de producción.

## Rollback y release

Apagar `FLOWERFLOW_BULK_JUDGE_ASSIGNMENT_ENABLED`. No borrar expedientes, eventos, paquetes, asignaciones, deliveries o auditoría. Producción y despliegue no están autorizados. Se conserva `NO-GO RELEASE/PRODUCTION — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`.
