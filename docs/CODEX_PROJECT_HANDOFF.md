# Handoff actual — Flower Flow

Fecha de corte: 2026-08-06.

## Estado canónico

- Checkout: `/home/ccortesg/workspace/flowerflow`.
- Rama de trabajo: `codex/f01-f02a-risk-reduction`.
- Base productiva: `baff7892f886af3fd4e42132c686620f1ae76d91`.
- Releases locales cerradas: Composer `84e34d1`, backend `f530e6a` y frontend/headers `7f660b0`.
- Producción está fuera de alcance: no hubo acceso, push, PR, despliegue, migraciones ni cambios de servicios.
- Fase 02B, jueces, evaluación, resultados y ganadores permanecen fuera de alcance.

## Siguiente evidencia obligatoria

El propietario debe introducir la contraseña sólo en `.env.testing`, ya ignorado, y ejecutar `scripts/quality_gate_local.sh`. El contrato exige MySQL `flowerflow_testing`, loopback, usuario `flowerflow_testing_user` y ausencia de `DB_URL`. La última ejecución fue rechazada de forma segura porque no se proporcionó contraseña; por tanto, no debe calificarse todavía como regresión funcional verde.

Después de un gate completo exitoso se requiere QA autenticada local de participante, administrador, archivos, estados, rate limit, 2FA y flag Fase 02A. Producción sólo puede tratarse mediante autorización separada y el runbook `docs/15-risk-reduction-release-runbook.md`.

## Evidencia y continuidad

- ExecPlan vivo: `.agent/execplans/flowerflow-f01-f02a-risk-reduction.md`.
- Gate local: `scripts/quality_gate_local.sh`.
- Auditor privado read-only: `php artisan flowerflow:storage-audit --disk=<disk> --json`.
- Worktree anterior preservado en `/home/ccortesg/workspace/flowerflow-worktree-archive/2026-08-06-ui-public-landing-v2/` mediante patch, tar, bundle y hashes.
- Capturas QA públicas en `output/playwright/`, ignoradas.
- Rollback y operación futura: `docs/15-risk-reduction-release-runbook.md`.

No registrar secretos, PII, documentos reales ni contenido de `.env` en evidencia o comandos.
