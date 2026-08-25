# ADR-0014 — Wizard de evaluación del juez y exportaciones del paquete ciego

- **Estado:** Accepted para local/test; release/producción bloqueados.
- **Fecha:** 2026-08-25
- **Complementa:** ADR-0008 (evaluación), ADR-0011 (envío/reapertura) y ADR-0012 (comunicaciones).

## Contexto

El detalle del juez reunía contexto de asignación, proyecto, captura de rúbrica, historial y conflicto en una sola pantalla. El flujo era funcional, pero aumentaba la carga cognitiva y hacía difícil alternar entre la información del proyecto y la captura. Además, el juez no podía obtener una copia estructurada del proyecto asignado sin abrir cada sección en el navegador.

La fuente autorizada para el juez sigue siendo el paquete ciego M5 fijado a la versión de la asignación. El snapshot crudo, identidad, contacto, admisibilidad, residencia, nombres originales y notas internas permanecen fuera de esa frontera.

## Decisión

1. Mantener el detalle existente como paso 1 de sólo lectura y separar el recorrido en cuatro pasos semánticos: inicio, proyecto asignado, evaluación y revisar/enviar.
2. El POST explícito de M6 continúa siendo la única forma de crear el borrador. Después de abrirlo redirige al proyecto; los GET de proyecto/evaluación/exportación nunca crean evaluaciones.
3. Centralizar el estado de lectura en `JudgeEvaluationWorkspace` y la proyección exportable en `BlindReviewProjectResolver`, revalidando ownership, estado, conflicto, versión, rúbrica, paquete, schema y hash canónico.
4. Conservar `SaveEvaluationDraft` como única frontera de escritura. `intent=autosave` usa exactamente la misma allowlist, transacción, cálculo decimal y lock optimista; el navegador recibe progreso/total exclusivamente del servidor.
5. Comprobar cambios cada treinta segundos y guardar sólo formularios sucios. Un 409 bloquea nuevos intentos y conserva los valores locales visibles; 422 y desconexión no descartan el formulario. Sin JavaScript permanecen guardado, navegación, confirmación y envío normales.
6. Generar exportaciones síncronas y acotadas, sin anexos embebidos:
   - PDF A4 mediante `barryvdh/laravel-dompdf`, con recursos locales, remoto/PHP/JavaScript deshabilitados.
   - XLSX mediante `phpoffice/phpspreadsheet`, con hojas `Proyecto`, `Enlaces` y `Anexos`, logotipos reales y todas las entradas no confiables escritas como texto literal.
7. Crear cada archivo en almacenamiento temporal privado, responder con `private, no-store` y `nosniff`, y borrarlo después de la descarga. Los anexos continúan tras rutas autenticadas.
8. Limitar a seis exportaciones por minuto por juez, asignación y formato y auditar sólo IDs técnicos, formato, conteos y código de rechazo.
9. Usar el shell, tokens, iconos y patrones responsivos existentes. El conflicto superior desaparece tras iniciar; el formulario permitido permanece al final del paso 1.

## Consecuencias

- No hay migración, estado persistente o worker nuevo.
- La evaluación enviada sigue sellada; la reapertura append-only y el actor administrativo de M7 no cambian.
- El PDF/XLSX puede contener identidad semántica introducida en texto, enlaces o anexos; la interfaz lo advierte y no promete anonimato semántico.
- OpenSpout continúa siendo la herramienta de streaming de exportaciones masivas. PhpSpreadsheet queda acotado a este libro pequeño porque se requieren imágenes reales.
- El rollback consiste en retirar las rutas/vistas/servicios y dependencias M8A; los borradores guardados siguen siendo compatibles con M6/M7.

## Seguridad y operación

Las exportaciones revalidan en cada request rol exacto, asignación propia activa, ausencia de conflicto, evaluación iniciada, paquete activo, versión coincidente, schema e integridad. No consultan el snapshot crudo ni exponen nombres/rutas originales. Los archivos temporales no son públicos y no hay URL de capacidad.

## Riesgo jurídico

M8A no modifica la decisión de cero cobertura mínima. Permanece `NO-GO RELEASE/PRODUCTION — OWNER_OVERRIDE / LEGAL_RECONCILIATION_REQUIRED` por la contradicción entre “al menos tres jueces” de la Mecánica v1.1 y ADR-0010.

## Verificación

La evidencia ejecutada y el resultado final viven en `.agent/execplans/flowerflow-phase-02b-m8a-judge-evaluation-wizard-exports.md` y `docs/31-phase-02b-m8a-judge-evaluation-wizard-exports-report-2026-08-25.md`.
