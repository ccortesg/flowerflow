# Handoff actual — Flower Flow

> **Adenda M2 — 2026-08-18:** el estado vigente está en `docs/11-operations-handoff.md`. Fase 02B M1/M2 quedó verde sólo en local/test; el perfil ya distingue cuatro principales sin límite fijo y quinto sustituto con capacidad diez. El siguiente gate es M3; `P2B-BLOCK-001` está resuelto y M4 requiere autorización posterior.

Fecha de corte: 2026-08-18.

## Estado canónico

- Checkout: `/home/ccortesg/workspace/flowerflow`.
- Rama auditada: `codex/submission-deadline-extension`.
- HEAD/remoto/ancestro común observado al iniciar M2: `e0fa0455e61afcb38593b62ae0d983f75a92b210`; el árbol contiene cambios locales aprobados aún no publicados y no debe describirse como limpio ni sustituirse.
- El diagnóstico vigente es `docs/16-project-status-by-module-and-role-2026-08-17.md`: producto maestro 63 %, alcance local aprobado 97 %, runtime aislado del RC 97 %, runtime local primario 42 % y preparación productiva 34 %.
- Fase 01, Fase 02A, cuarta categoría, exportación privada, ampliación de plazo y catálogo/vínculos/aceptaciones v1.1 están implementados, probados y recorridos localmente sin tocar v1.0 ni aceptaciones históricas.
- La Mecánica v1.1 definitiva confirma cuatro categorías y máximo cuatro propuestas. El propietario aceptó la superposición de accesibilidad sin cambios y resolvió la continuidad de aceptaciones v1.0 sin reaceptación forzada ni backfill.
- El propietario designó como v1.0 el archivo físico actual `3bcf31…`; la diferencia con `42bd5e…` permanece como incidencia histórica visible, pero deja de ser bloqueo operativo. Ver `docs/17-legal-v1-1-reconciliation-2026-08-17.md`.
- P2 503/CSP quedó resuelto localmente con vista accesible/de marca, assets Vite normales, cero estilos inline y soporte de pre-render de mantenimiento.
- Topología productiva confirmada por el propietario: checkout Git directo en `/var/www/flowerflow`, sin `releases/current/shared`; el VirtualHost informado `app.sguniformes.com.mx` apunta a esa ruta. No inferir por ello un cambio del host canónico público ni alterar Apache.
- Fase 02B M1/M2 está implementada sólo en local/test; rúbrica, asignaciones, paquetes ciegos, conflictos, evaluaciones, ganadores, resultados, ARCO y despliegue de esta rama permanecen fuera de alcance.
- Existe evidencia pública histórica de una release anterior (`26256e3`), pero no prueba que `e2f4345` esté desplegado.

## Evidencia vigente

- Suite MySQL aislada: 125 pruebas y 1,316 aserciones verdes; M1+M2: 16/267.
- Pint, Composer validate/platform/audit, JSON y build Vite: verdes.
- Yarn conserva un advisory bajo conocido de Quill 2.0.3 sin fix; sanitización servidor vigente.
- 77 rutas totales/52 propias; 14 migraciones aplicadas en `flowerflow_testing`.
- UAT previa por roles más M2 en Firefox: alta/listado/detalle admin, pending/active/suspended, recovery, invalidación de sesión, reactivación, 403/404, 1440/768/390, teclado/foco, reflow y consola limpia.
- `scripts/serve_local_testing.sh` valida base/cuenta/catálogos/flags antes de servir; usa sesiones database, correo array, cola sync, limpia cache de permisos y mantiene resultados apagados. La base terminó sembrada con cero usuarios/perfiles/sesiones sintéticos.
- La base local primaria `flowerflow` conserva cuatro migraciones funcionales pendientes.
- El `.env` local conserva `FLOWERFLOW_MAX_SUBMISSIONS_PER_USER=3`; el contrato, código, ejemplo y pruebas usan cuatro.
- Flags observados: público/panel activos; registro/recepción/resultados/admisibilidad inactivos.

## Siguiente puerta

Autorizar, mediante un prompt posterior, únicamente M3 —rúbrica global versionada— en local/test. M1/M2 se conservan como invariantes. `P2B-BLOCK-001` está resuelto, pero M4 debe esperar M3 verde y una autorización separada. Esta puerta no autoriza producción ni el checkout `/var/www/flowerflow`.

El cierre M2 está en `.agent/execplans/flowerflow-phase-02b-m2-judge-profile-onboarding.md` y `docs/19-phase-02b-m2-implementation-report-2026-08-18.md`; el prompt canónico M3 está en la sección 21 de `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`.

## Reglas de continuidad

- Leer `AGENTS.md`, `.agent/PLANS.md`, el ExecPlan activo y ADR antes de editar.
- No registrar secretos, PII, documentos reales ni contenido sensible de `.env`.
- Separar código implementado, flag activado, migración aplicada y despliegue verificado.
- No ejecutar `migrate:fresh` fuera de la base/cuenta de pruebas exactas y del guard previo.
- No hacer stage, commit, push, AWS ni despliegue sin autorización expresa de esa acción.
