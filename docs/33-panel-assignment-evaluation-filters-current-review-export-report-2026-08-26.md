# Informe de implementación — filtros y exportación de revisiones vigentes

**Fecha:** 2026-08-26 (`America/Hermosillo`)  
**Ámbito de validación:** local/test; sin despliegue, producción o servicios externos. El código funcional quedó incorporado en `ba543f4` antes del cierre de UAT; los ajustes de cierre permanecen sin stage, commit o push.

**Estado:** implementación funcional validada en local/test; `NO-GO RELEASE/PRODUCTION` por los bloqueos globales descritos abajo.

## Resultado funcional

- `/panel/asignaciones` filtra por folio/ID público parcial y categoría sin cambiar el conjunto base enviado+admitido. Muestra folio e ID público y conserva filtros al paginar.
- `/panel/evaluaciones` filtra por folio/ID público, estado enum y categoría. Muestra la propuesta junto a cada evaluación y conserva filtros al paginar.
- El administrador autorizado elige `Revisiones vigentes` o `Historial completo`. El alcance legado conserva `all_revisions_v1`; la vista nueva usa `current_revisions_v1`.
- `current_revisions_v1` produce `Evaluaciones vigentes` y `Rubros vigentes`: una fila principal por evaluación/juez, revisión señalada por `current_revision_id`, estado, total 4/2, comentario general y detalle persistido por rubro.
- El writer falla cerrado si el puntero vigente no pertenece al agregado o no coincide con la última revisión append-only. No calcula consolidado, promedio, ranking o ganador.

## Seguridad y privacidad

Se mantienen flag default-off, rol exacto admin, permisos acumulativos, contraseña reciente, ownership, job cifrado/post-commit, cola `exports`, disk privado, expiración y auditoría redactada. El libro vigente no incluye identidad/contacto del participante, nombre del proyecto, archivos, reaperturas ni motivos. Todas las celdas son texto literal; `NULL` queda vacío y cero se conserva.

## Compatibilidad y datos

No hay migración, rutas, permisos, dependencias o configuración nuevos. Las solicitudes antiguas sin `scope_version` siguen generando el historial completo. No se modifica ninguna evaluación, revisión, score, asignación, propuesta, snapshot o exportación previa.

## Evidencia ejecutada

- Preflight WSL: repositorio/rama esperados, árbol limpio, `core.autocrlf` sin configurar y toolchain Linux disponible, incluido `rg 13.0.0`.
- Guard de base: 8 pruebas/8 aserciones verdes.
- Baseline dirigido previo: 16 pruebas/834 aserciones verdes.
- Pruebas nuevas/dirigidas iniciales: 6 pruebas/1,224 aserciones verdes, incluida lectura independiente con PhpSpreadsheet y XML ZIP sin fórmulas.
- Regresión relacionada: 44 pruebas/1,741 aserciones verdes y 1 prueba opt-in omitida.
- Suite completa: 244 pruebas/4,131 aserciones verdes y 1 prueba opt-in omitida.
- Pint del alcance: verde en los 10 archivos PHP modificados. Pint global conserva un único fallo preexistente y fuera de alcance en `video-tutorial/scripts/freeze-time.php` (`fully_qualified_strict_types`).
- Composer validate/platform/audit: verdes y sin advisories. Build Vite: verde, 784 módulos. `yarn audit --groups dependencies`: un aviso LOW conocido de Quill sin parche disponible.
- Inventarios: 115 rutas, 4 tareas programadas, 25 migraciones aplicadas en `flowerflow_testing`, 15 JSON válidos y 2 enlaces Markdown locales revisados; búsqueda de secretos sin coincidencias.
- UAT real con Chromium/Playwright dentro de WSL: autenticación admin sintética, filtros y limpieza con query string, wildcard `%` literal, reconfirmación de contraseña, alcance vigente predeterminado, selección de ambos alcances, navegación por teclado y cero errores/warnings de consola.
- Responsive en 390, 1,024 y 1,440 px sin desbordamiento del documento. Durante el UAT se detectó y corrigió el overflow móvil de la tarjeta de exportaciones recientes sin retirar el scroll interno de su tabla.
- El servidor local y el navegador se cerraron y `flowerflow_testing` fue reconstruida con su seed canónico; el usuario sintético de UAT no permanece.

## Rollback y riesgos residuales

Rollback operativo: `FLOWERFLOW_EVALUATION_EXPORT_ENABLED=false`. El rollback de código puede retirar filtros y `current_revisions_v1` sin borrar evidencia ni tocar `all_revisions_v1`. Persisten el bloqueo jurídico global de cobertura mínima, el fallo global preexistente de Pint, el aviso LOW de Quill, la identidad del juez como dato actual al exportar y la necesidad de validar worker, disk, capacidad y UAT de release en un despliegue expresamente autorizado.
