# Handoff actual — Flower Flow

Fecha de corte: 2026-08-17.

## Estado canónico

- Checkout: `/home/ccortesg/workspace/flowerflow`.
- Rama auditada: `codex/submission-deadline-extension`.
- SHA base del candidato jurídico local: `e2f4345`; el árbol ya contenía cambios documentales y los tres PDF v1.1 sin seguimiento al iniciar esta tarea, por lo que no debe describirse como limpio ni sustituir cambios preexistentes.
- El diagnóstico vigente es `docs/16-project-status-by-module-and-role-2026-08-17.md`: producto maestro 58 %, alcance local aprobado 96 %, runtime aislado del RC 96 %, runtime local primario 42 % y preparación productiva 34 %.
- Fase 01, Fase 02A, cuarta categoría, exportación privada, ampliación de plazo y catálogo/vínculos/aceptaciones v1.1 están implementados, probados y recorridos localmente sin tocar v1.0 ni aceptaciones históricas.
- La Mecánica v1.1 definitiva confirma cuatro categorías y máximo cuatro propuestas. Sólo queda `POR_CONFIRMAR P1` la superposición de accesibilidad entre Movilidad con Flow y Hermosillo sin Barreras. El RC es `GO` técnico sólo local/test; producción continúa `NO-GO` y fuera de alcance.
- Existe una incidencia de integridad histórica: el archivo actual bajo el nombre Mecánica v1.0 no coincide con el SHA-256 original conservado en Git/seeder. Ver `docs/17-legal-v1-1-reconciliation-2026-08-17.md`.
- Fase 02B, jueces, evaluación, ganadores, resultados, ARCO y despliegue de esta rama permanecen fuera de alcance.
- Existe evidencia pública histórica de una release anterior (`26256e3`), pero no prueba que `e2f4345` esté desplegado.

## Evidencia vigente

- Suite MySQL aislada: 107 pruebas y 1,031 aserciones verdes.
- Pint, Composer validate/platform/audit, JSON y build Vite: verdes.
- Yarn conserva un advisory bajo conocido de Quill 2.0.3 sin fix; sanitización servidor vigente.
- 66 rutas totales/41 propias; 12 migraciones aplicadas en `flowerflow_testing`.
- UAT Firefox visitante/participante/reviewer/admin en 1440/768/360 cerrada: vínculos v1.1, aceptación real, límite cuatro/quinta rechazada, envío/folio, admisibilidad, paginación, 2FA, XLSX/expiración, cierre e IDOR.
- `scripts/serve_local_testing.sh` valida base/cuenta/catálogos/flags antes de servir; usa correo array, cola sync y resultados apagados. La base terminó sembrada con cero usuarios/aceptaciones sintéticas.
- La base local primaria `flowerflow` conserva cuatro migraciones funcionales pendientes.
- El `.env` local conserva `FLOWERFLOW_MAX_SUBMISSIONS_PER_USER=3`; el contrato, código, ejemplo y pruebas usan cuatro.
- Flags observados: público/panel activos; registro/recepción/resultados/admisibilidad inactivos.

## Siguiente puerta

Congelar el RC local con los hashes definitivos y obtener decisiones formales sobre la delimitación de accesibilidad, reaceptación e integridad/publicación histórica v1.0. Después puede prepararse —sin ejecutarse— un preflight externo autorizado. No usar `flowerflow`, no implementar Fase 02B y no tocar producción.

El ExecPlan vigente es `.agent/execplans/flowerflow-legal-v1-1-local-release-candidate.md`; la matriz jurídica está en `docs/17-legal-v1-1-reconciliation-2026-08-17.md`.

## Reglas de continuidad

- Leer `AGENTS.md`, `.agent/PLANS.md`, el ExecPlan activo y ADR antes de editar.
- No registrar secretos, PII, documentos reales ni contenido sensible de `.env`.
- Separar código implementado, flag activado, migración aplicada y despliegue verificado.
- No ejecutar `migrate:fresh` fuera de la base/cuenta de pruebas exactas y del guard previo.
- No hacer stage, commit, push, AWS ni despliegue sin autorización expresa de esa acción.
