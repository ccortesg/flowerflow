# Handoff actual — Flower Flow

> **Adenda vigente M5 — 2026-08-18:** el estado canónico está en `docs/11-operations-handoff.md`. M1–M5 quedaron verdes local/test. El propietario aprobó seis jueces operativos: cuatro `primary` y dos `substitute`, todos sin límite; M5 añade paquete ciego allowlist e inventario/descarga neutros. M6 permanece separado y no autorizado.

Fecha de corte: 2026-08-18.

## Estado canónico

- Checkout: `/home/ccortesg/workspace/flowerflow`.
- Rama auditada: `codex/submission-deadline-extension`.
- HEAD/remoto/ancestro común observado al iniciar M5: `865059ad302ff4195ac18f671bd6fa13b99e398b`; el árbol contiene cambios locales M1–M5 no publicados por esta tarea.
- El diagnóstico vigente es `docs/16-project-status-by-module-and-role-2026-08-17.md`: producto maestro 68 %, alcance local aprobado hasta M5 100 %, runtime aislado del RC 100 %, runtime local primario 42 % y preparación productiva 34 %.
- Fase 01, Fase 02A, cuarta categoría, exportación privada, ampliación de plazo y catálogo/vínculos/aceptaciones v1.1 están implementados, probados y recorridos localmente sin tocar v1.0 ni aceptaciones históricas.
- La Mecánica v1.1 definitiva confirma cuatro categorías y máximo cuatro propuestas. El propietario aceptó la superposición de accesibilidad sin cambios y resolvió la continuidad de aceptaciones v1.0 sin reaceptación forzada ni backfill.
- El propietario designó como v1.0 el archivo físico actual `3bcf31…`; la diferencia con `42bd5e…` permanece como incidencia histórica visible, pero deja de ser bloqueo operativo. Ver `docs/17-legal-v1-1-reconciliation-2026-08-17.md`.
- P2 503/CSP quedó resuelto localmente con vista accesible/de marca, assets Vite normales, cero estilos inline y soporte de pre-render de mantenimiento.
- Topología productiva confirmada por el propietario: checkout Git directo en `/var/www/flowerflow`, sin `releases/current/shared`; el VirtualHost informado `app.sguniformes.com.mx` apunta a esa ruta. No inferir por ello un cambio del host canónico público ni alterar Apache.
- Fase 02B M1–M5 está implementada sólo en local/test. M4A lleva esquema, derivación y selección manual a `4+2` ilimitado; M5 materializa paquete allowlist/hash, inventario exacto y descarga privada neutra. Evaluaciones/puntajes M6+, ganadores, resultados, ARCO y despliegue de esta rama permanecen fuera.
- Existe evidencia pública histórica de una release anterior (`26256e3`), pero no prueba que `e2f4345` esté desplegado.

## Evidencia vigente

- Última evidencia ejecutada M5: suite MySQL aislada 150 pruebas/1,703 aserciones; M5 8/119 y M1–M5 dirigidas 41/654, verdes bajo el contrato ilimitado.
- Pint, Composer validate/platform/audit, JSON y build Vite: verdes.
- Yarn conserva un advisory bajo conocido de Quill 2.0.3 sin fix; sanitización servidor vigente.
- 71 rutas propias sin vendor y 18 migraciones aplicadas en `flowerflow_testing`.
- UAT Firefox M5: generación/preview/activación, allowlist, anexos neutros, descarga, conflicto y reemplazo manual hacia ambos sustitutos; escritorio/tableta/móvil/reflow y consola limpios.
- `scripts/serve_local_testing.sh` valida base/cuenta/catálogos/flags antes de servir; usa sesiones database, correo array, cola sync, limpia cache de permisos y mantiene resultados apagados. La base terminó sembrada con cero usuarios/perfiles/sesiones sintéticos.
- La base local primaria `flowerflow` conserva cuatro migraciones funcionales pendientes.
- El `.env` local conserva `FLOWERFLOW_MAX_SUBMISSIONS_PER_USER=3`; el contrato, código, ejemplo y pruebas usan cuatro.
- Flags observados: público/panel activos; registro/recepción/resultados/admisibilidad inactivos.

## Siguiente puerta

La siguiente puerta es M6, mediante el prompt canónico de la sección 21 del paquete de decisiones. Antes de modificar debe comprobar M4A/M5: exactamente cuatro `primary` y dos `substitute` activos, todos con capacidad `NULL`, selección manual, paquete único activo por versión, allowlist/hash e integridad/descarga privadas verdes.

M6 permanece separado: su prompt canónico está en la sección 21 de `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md` y autoriza sólo borrador/cálculo servidor, no envío/reapertura M7. Los planes M4A/M5 conservan la evidencia de precondición. Ninguna puerta autoriza producción ni el checkout `/var/www/flowerflow`.

## Reglas de continuidad

- Leer `AGENTS.md`, `.agent/PLANS.md`, el ExecPlan activo y ADR antes de editar.
- No registrar secretos, PII, documentos reales ni contenido sensible de `.env`.
- Separar código implementado, flag activado, migración aplicada y despliegue verificado.
- No ejecutar `migrate:fresh` fuera de la base/cuenta de pruebas exactas y del guard previo.
- No hacer stage, commit, push, AWS ni despliegue sin autorización expresa de esa acción.
