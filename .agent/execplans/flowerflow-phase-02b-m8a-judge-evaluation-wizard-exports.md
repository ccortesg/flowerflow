# Fase 02B M8A — wizard de evaluación y exportaciones ciegas del juez

Este ExecPlan es un documento vivo y se mantiene conforme a `.agent/PLANS.md`.

## Propósito y resultado observable

Separar el detalle monolítico de la asignación en un wizard accesible de cuatro pasos: inicio, proyecto asignado, evaluación y confirmación. El juez podrá alternar entre proyecto y evaluación, conservar un guardado manual sin JavaScript, autoguardar el borrador cada 30 segundos con `lock_version` y descargar PDF/XLSX profesionales construidos exclusivamente desde el paquete ciego inmutable.

## Baseline y guard

- Repositorio: `/home/ccortesg/workspace/flowerflow`.
- Rama: `codex/submission-deadline-extension`.
- HEAD/upstream/merge-base: `4aff0b0cd6e65a2101463657165e11e30379be00`.
- Árbol inicial limpio; 24 migraciones existentes. M8A no añade migración.
- Guard verificado el 2026-08-25: `APP_ENV=testing`, MySQL en `127.0.0.1`, base `flowerflow_testing`, usuario `flowerflow_testing_user` y `SELECT DATABASE()=flowerflow_testing`.
- Baseline dirigido: 26 pruebas/495 aserciones en paquete ciego, borrador, M7 y asignaciones/conflictos.

## Alcance

- Mantener el detalle de asignación como paso 1 y GET sin creación de evaluación.
- Añadir pantallas GET separadas para proyecto y evaluación; conservar confirmación M7 como paso 4.
- Redirigir el inicio al proyecto y continuar/ver evaluación al formulario separado.
- Mantener el único formulario de conflicto al final del paso 1; el acceso superior desaparece después de iniciar.
- Generar PDF y XLSX sincrónicos desde el payload/inventario M5, con enlaces autenticados a anexos.
- Extender el PATCH de borrador con `intent=autosave` y respuesta JSON server-authoritative.
- Reutilizar shell, tokens, iconos, Policies, Action transaccional, throttle y auditoría actuales.
- Añadir dependencias runtime documentadas: `barryvdh/laravel-dompdf:^3.1` y `phpoffice/phpspreadsheet:^5.9`.

## Exclusiones

- Sin cambios de esquema, snapshots, paquetes, archivos privados, rúbricas, cálculo BCMath, estados M7 o comunicaciones M8.
- Sin almacenar evaluación en `localStorage`, `sessionStorage`, logs o telemetría.
- Sin incrustar binarios de anexos en PDF/XLSX ni crear URLs públicas/capability links.
- Sin stage, commit, push, despliegue, producción, servicios externos, PDF jurídicos o datos reales.

## Contratos y rutas

- `GET /juez/asignaciones/{judgeAssignment}`: paso 1.
- `GET /juez/asignaciones/{judgeAssignment}/proyecto`: paso 2.
- `GET /juez/asignaciones/{judgeAssignment}/evaluacion`: paso 3.
- Confirmación GET existente: paso 4.
- `GET .../proyecto.pdf` y `GET .../proyecto.xlsx`: descarga privada, `no-store`, `nosniff`, seis por minuto por usuario/asignación/formato.
- Los pasos 2–4 requieren una Evaluation existente; conflicto/cancel/void y actor cruzado fallan cerrados.
- El payload M5 permitido contiene sólo categoría, modalidad, título, resumen, descripción sanitizada, vínculos externos e inventario neutral.

## Autosave y concurrencia

- `intent` admite `save|review|autosave`; la Action conserva allowlist, transacción, locks y recálculo servidor.
- Sólo un formulario sucio se envía al intervalo de 30 segundos y antes de navegar al proyecto.
- JSON exitoso: `lock_version`, `saved_at`, criterios capturados/totales, `is_complete` y `total_display`.
- 409 detiene autosave, conserva el contenido local visible y exige recargar; 422 presenta resumen/errores; red/offline conserva el formulario.
- El botón manual sigue usando POST/PATCH normal y funciona sin JavaScript.

## Exportaciones y seguridad

- PDF A4 con logotipos locales, fuente UTF-8, colores/estructura institucional, enlaces y footer; DOMPDF sin remoto, PHP o JavaScript.
- XLSX con hojas `Proyecto`, `Enlaces` y `Anexos`, imágenes de ambos logotipos, texto literal, ajuste, freeze panes y configuración de impresión.
- Cada request revalida rol exacto, ownership, assignment activo, ausencia de conflicto, versión, paquete activo y hash canónico del payload.
- Auditoría `blind_review_package.project_exported|project_export_rejected` sólo con IDs técnicos, formato, conteos y `reason_code`.

## Validación

- Pruebas dirigidas nuevas de wizard, exportaciones, autosave, roles, IDOR, conflicto, payload/archivo hostil y 409.
- Una regresión conjunta M5/M6/M7 y una sola suite completa al cierre.
- Pint, Composer validate/platform/audit, Yarn audit, build Vite, rutas, scheduler, migraciones, enlaces, scans y `git diff --check`.
- Render visual de PDF y las tres hojas XLSX; UAT Firefox 1440×900, 1024×768 y 390×844 con teclado, foco, reflow, zoom y consola.

## Rollback

- M8A no persiste estado nuevo; revertir código/assets/dependencias restaura la UI previa.
- Borradores guardados siguen siendo M6/M7 compatibles. Nunca borrar evaluaciones, auditoría o paquetes para revertir.
- Release/producción conserva `NO-GO — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`.

## Progreso

- [x] 2026-08-25 — Baseline, lecturas obligatorias, guard exacto y regresión 26/495 verificados.
- [x] 2026-08-25 — Wizard y separación de pantallas implementados.
- [x] 2026-08-25 — PDF/XLSX privados y auditados implementados.
- [x] 2026-08-25 — Autosave/409 accesible implementado.
- [x] 2026-08-25 — Pruebas, documentación, gates y UAT cerrados: suite 233/2,849 con una prueba de carga máxima omitida de forma explícita; rutas, scheduler, migraciones, JSON, enlaces, scans, build y estilo verificados.

## Hallazgos y decisiones

- La vista actual mezcla proyecto, evaluación, historial y conflicto; la separación debe centralizar el contexto de lectura para no duplicar invariantes.
- OpenSpout 4.32 conserva los exports masivos; no soporta las imágenes requeridas para este libro pequeño. PhpSpreadsheet se limita a crear este XLSX y nunca lee archivos proporcionados por usuarios.
- “Toda la información” significa toda la proyección ciega M5, nunca el snapshot crudo o PII.
- El baseline esperado decía implícitamente 23 migraciones por arrastre del informe M8; Git ya contenía 24 por la recuperación de permisos de admisibilidad. Se corrigió el dato sin añadir migración M8A.
- La UAT móvil descubrió una colisión con `.ff-step-number` de la landing. Se cerró mediante `.ff-evaluation-stepper .ff-step-number`, sin modificar la landing ni usar `!important`.
- LibreOffice/Excel no están instalados en el runtime local. El PDF se renderizó con Poppler; el XLSX se validó con PhpSpreadsheet/OpenPyXL y una previsualización local de las tres hojas. Esta limitación no afecta la inspección estructural, pero el smoke en Excel/LibreOffice real sigue siendo recomendable antes de release.

## Resultados

`GO LOCAL/TEST`. La regresión dirigida final pasó 37 pruebas/812 aserciones y la suite completa 233 pruebas/2,849 aserciones, con una única prueba de carga máxima omitida y documentada. Pint, Composer validate/platform/audit, build Vite, 11 JSON, 15 enlaces Markdown locales, 112 rutas, cuatro tareas programadas, 24 migraciones, scans redactados y `git diff --check` quedaron verdes; `yarn audit` conserva sólo el advisory bajo conocido de Quill 2.0.3 sin parche. UAT Firefox sintética cubrió tres viewports, inicio→proyecto→evaluación→confirmación→sólo lectura, autosave real, guardado al navegar, 422, offline y 409 sin overwrite; consola sin errores. PDF A4 y las tres hojas XLSX quedaron renderizados/inspeccionados sin PII estructurada ni fórmulas. La base `flowerflow_testing` terminó en `migrate:fresh --seed`, con cero usuarios, perfiles de juez, evaluaciones, sesiones y jobs. Se conserva `NO-GO RELEASE/PRODUCTION — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED`.
