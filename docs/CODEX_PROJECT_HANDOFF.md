# Handoff actual — Flower Flow

Fecha de corte: 2026-08-18.

## Estado canónico

- Checkout: `/home/ccortesg/workspace/flowerflow`.
- Rama auditada: `codex/submission-deadline-extension`.
- HEAD observado antes del seguimiento 503: `32adee0121ea20c557d4a4583680f8a5a62e146d`; el árbol contiene cambios locales aprobados aún no publicados y no debe describirse como limpio ni sustituirse.
- El diagnóstico vigente es `docs/16-project-status-by-module-and-role-2026-08-17.md`: producto maestro 58 %, alcance local aprobado 96 %, runtime aislado del RC 96 %, runtime local primario 42 % y preparación productiva 34 %.
- Fase 01, Fase 02A, cuarta categoría, exportación privada, ampliación de plazo y catálogo/vínculos/aceptaciones v1.1 están implementados, probados y recorridos localmente sin tocar v1.0 ni aceptaciones históricas.
- La Mecánica v1.1 definitiva confirma cuatro categorías y máximo cuatro propuestas. El propietario aceptó la superposición de accesibilidad sin cambios y resolvió la continuidad de aceptaciones v1.0 sin reaceptación forzada ni backfill.
- El propietario designó como v1.0 el archivo físico actual `3bcf31…`; la diferencia con `42bd5e…` permanece como incidencia histórica visible, pero deja de ser bloqueo operativo. Ver `docs/17-legal-v1-1-reconciliation-2026-08-17.md`.
- P2 503/CSP quedó resuelto localmente con vista accesible/de marca, assets Vite normales, cero estilos inline y soporte de pre-render de mantenimiento.
- Topología productiva confirmada por el propietario: checkout Git directo en `/var/www/flowerflow`, sin `releases/current/shared`; el VirtualHost informado `app.sguniformes.com.mx` apunta a esa ruta. No inferir por ello un cambio del host canónico público ni alterar Apache.
- Fase 02B, jueces, evaluación, ganadores, resultados, ARCO y despliegue de esta rama permanecen fuera de alcance.
- Existe evidencia pública histórica de una release anterior (`26256e3`), pero no prueba que `e2f4345` esté desplegado.

## Evidencia vigente

- Suite MySQL aislada: 109 pruebas y 1,049 aserciones verdes.
- Pint, Composer validate/platform/audit, JSON y build Vite: verdes.
- Yarn conserva un advisory bajo conocido de Quill 2.0.3 sin fix; sanitización servidor vigente.
- 66 rutas totales/41 propias; 12 migraciones aplicadas en `flowerflow_testing`.
- UAT previa visitante/participante/reviewer/admin en 1440/768/360 cerrada; seguimiento 503 revisado en Chrome 1440×1000 y Firefox 390×844, con mantenimiento HTTP 503 y reflow sin desbordamiento.
- `scripts/serve_local_testing.sh` valida base/cuenta/catálogos/flags antes de servir; usa correo array, cola sync y resultados apagados. La base terminó sembrada con cero usuarios/aceptaciones sintéticas.
- La base local primaria `flowerflow` conserva cuatro migraciones funcionales pendientes.
- El `.env` local conserva `FLOWERFLOW_MAX_SUBMISSIONS_PER_USER=3`; el contrato, código, ejemplo y pruebas usan cuatro.
- Flags observados: público/panel activos; registro/recepción/resultados/admisibilidad inactivos.

## Siguiente puerta

Publicar un `RELEASE_SHA` exacto que incluya este seguimiento y generar el runbook de instalación/compilación sobre el checkout directo `/var/www/flowerflow` para que lo ejecute el propietario. No crear ni asumir `releases/current/shared`. Codex no se conectará ni desplegará; el propietario confirmó que sus respaldos ya fueron realizados.

El ExecPlan vigente es `.agent/execplans/flowerflow-legal-v1-1-local-release-candidate.md`; la matriz jurídica está en `docs/17-legal-v1-1-reconciliation-2026-08-17.md`.

## Reglas de continuidad

- Leer `AGENTS.md`, `.agent/PLANS.md`, el ExecPlan activo y ADR antes de editar.
- No registrar secretos, PII, documentos reales ni contenido sensible de `.env`.
- Separar código implementado, flag activado, migración aplicada y despliegue verificado.
- No ejecutar `migrate:fresh` fuera de la base/cuenta de pruebas exactas y del guard previo.
- No hacer stage, commit, push, AWS ni despliegue sin autorización expresa de esa acción.
