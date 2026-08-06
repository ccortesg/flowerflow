# Reglas de trabajo para Flower Flow

Flower Flow es un monolito Laravel 12/Blade para la convocatoria Hermosillo Florece 2026. Implementa registro, perfil, propuestas y revisión de admisibilidad; jueces/evaluación permanece sólo como definición documental. Para el inventario, riesgos, estado Git y continuidad, lee primero [`docs/CODEX_PROJECT_HANDOFF.md`](docs/CODEX_PROJECT_HANDOFF.md).

## Entorno y comandos canónicos

- Entorno esperado: Ubuntu en WSL2, ruta `/home/ccortesg/workspace/flowerflow`.
- Stack observado el 2026-08-04: PHP 8.3.31, Composer 2.10.2, Laravel 12.64.0, Node 22.23.1, Corepack 0.34.6, Yarn 1.22.22, Vite 6.3.5 y MySQL 8.
- `/mnt/c/wamp64/www/flowerflow` es la procedencia Windows/WampServer; no mantengas dos checkouts activos con cambios divergentes.

```bash
composer install
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"
nvm use 22.23.1
corepack yarn install --frozen-lockfile
php artisan serve --host=127.0.0.1 --port=8000
scripts/build_frontend_production.sh
```

Gate aplicable, siempre con una base de pruebas MySQL desechable confirmada:

```bash
php artisan test
vendor/bin/pint --test
composer validate --strict
composer audit --locked
corepack yarn audit --groups dependencies --level moderate
scripts/build_frontend_production.sh
git diff --check
```

Desde el 2026-08-05, `phpunit.xml` fuerza MySQL local y la base desechable `flowerflow_testing`; `Tests\TestCase` aborta antes de `RefreshDatabase` si ambiente, driver, host, base o `DB_URL` rompen ese contrato. Usuario y contraseña permanecen únicamente en `.env`. No ejecutes `migrate:fresh`, `db:wipe` o migraciones manuales contra la base principal. Pint quedó verde con `pint.json`, que excluye `_referencia/` y `bootstrap/cache`; las auditorías de dependencias continúan en rojo y no autorizan cambios de lockfiles por inferencia.

## Autoridad y alcance

- Antes de editar, leer este archivo, `.agent/PLANS.md`, el ExecPlan activo y los ADR aplicables.
- La documentación aprobada manda sobre supuestos. Registrar contradicciones como `PENDING`; no inventar reglas de negocio, textos legales, premios, fechas, licencias ni credenciales.
- Ejecutar un milestone por vez. No desplegar ni alterar producción sin aprobación expresa, backup verificado, UAT y rollback probado.
- Mantener el código en inglés y la interfaz/documentación operativa para usuarios en español.

## Autorización actual y límites

- La Fase 01 `public-submissions` fue aprobada expresamente el 2026-07-15 mediante `Prompt_Optimo_Codex_FlowerFlow_Fase_01_v2.md` y se ejecuta en `codex/phase-01-public-submissions`.
- La Fase 02A `admissibility-review` fue aprobada expresamente el 2026-07-16 y quedó registrada en `codex/phase-02-admissibility-review`; esto no autoriza despliegue.
- La Fase 02B está autorizada únicamente como definición documental en esa misma rama desde el commit base `5007b11a6c157898228ec027a387c86c270a33da`. Su implementación permanece bloqueada por decisiones `PENDING` y por falta de autorización expresa.
- Están autorizados en local/test: dependencias compatibles, migraciones revisadas, datos sintéticos, sitio público, auth/perfil participante, propuestas, archivos privados, aceptaciones, envío idempotente y panel privilegiado mínimo.
- En Fase 02A también están autorizados la revisión administrativa, aclaraciones, verificación privada de residencia, resolución de admisibilidad, auditoría, notificaciones transaccionales y sus interfaces/pruebas locales.
- Quedan fuera de la autorización actual: implementación de jueces, asignación, rúbrica, evaluación, selección/publicación de ganadores, comunicaciones masivas, ARCO completo, borrado automático de residencia y reportes avanzados.
- No desplegar ni modificar EC2, DNS, TLS, SMTP real o `administratec`; AWS sólo se documenta y prepara mediante preflight de solo lectura para una tarea posterior.
- `formatos/` conserva los PDF jurídicos v1.0 exactos y versionables; `imagen/` conserva originales autorizados; `_referencia/` es sólo lectura, local, ignorada y nunca forma parte del build o release.

## Seguridad y datos

- Nunca versionar secretos. La contraseña de MySQL local se recibe por canal seguro y vive sólo en `.env`, que está ignorado.
- No copiar PII ni documentos reales a desarrollo, pruebas, fixtures, capturas, logs o tickets.
- Separar comprobantes de residencia de anexos evaluables. Los jueces no pueden acceder a identidad ni comprobantes.
- Autorizar cada recurso con middleware, permisos y Policies; filtrar también consultas y descargas. Ocultar botones no es autorización.
- Usar almacenamiento privado, nombres internos aleatorios, allowlist de tipo/tamaño/MIME/firma y auditoría de accesos sensibles.
- Guardar fechas en UTC y presentar reglas de convocatoria en `America/Hermosillo`.

## Arquitectura y plantilla

- Conservar Laravel 12 y Materialize/Pixinvent 3.0.0 hasta que un ADR aprobado disponga otra cosa.
- Reutilizar `resources/views/layouts`, `config/custom.php` y menús JSON. Los overrides Flower Flow deben vivir fuera del core del proveedor y registrarse en `docs/template-overrides.md`.
- Controladores delgados; Form Requests, Policies, Actions/Services y enums respaldados para reglas; transacciones en cambios críticos; Events/Listeners/Jobs sólo donde reduzcan acoplamiento.
- No crear repositories genéricos, APIs, microservicios, SPA ni Redis sin necesidad aprobada.
- No añadir dependencias de producción sin actualizar `docs/dependency-register.md` y crear/actualizar un ADR.
- No editar `public/build` manualmente. Importar JS/CSS por página mediante Vite y retirar demos sólo después de verificar el build.
- No sobrescribir originales de `imagen/` ni `formatos/`; publicar copias o derivaciones reproducibles y verificar hashes.
- Mantener nombres de clases, métodos, variables, tablas y archivos de código en inglés; interfaz y documentación operativa en español de México.
- Usar nombres de migración y enums respaldados consistentes con el dominio; no cambiar estados históricos sin una migración aditiva y reversible.

## Calidad

- Cada milestone debe incluir pruebas de permisos negativos, estados, fecha/zona horaria, archivos y auditoría.
- Ejecutar los comandos reales definidos en el ExecPlan: tests, Pint, validación JSON, build y auditorías disponibles.
- Regla de detener y reparar: no marcar un milestone completo mientras falle una validación requerida.
- Verificar flujos críticos en navegador real, móvil y teclado antes de UAT.
- Mantener trazabilidad requisito -> historia -> implementación -> prueba en `docs/requirements-traceability.md`.
- La definición de terminado exige código o revisión documental en alcance, pruebas aplicables, autorización negativa, build, formato, auditorías y documentación actualizada. Un fallo o bloqueo debe reportarse; no maquillarse como éxito.

## Cambios y evidencia

- Preservar cambios ajenos y evitar ediciones solapadas. Dividir trabajo paralelo por archivos/módulos con propietario explícito.
- Actualizar el ExecPlan vivo: progreso, decisiones, hallazgos inesperados, evidencia y próximos pasos.
- Entregar lista exacta de archivos, comandos ejecutados, resultados, riesgos residuales y rollback.
- No cambiar de rama, hacer push, reescribir historial, limpiar el árbol ni borrar cambios locales sin solicitud expresa. Nunca usar force-push por inferencia.
- No instalar o actualizar dependencias ni tocar lockfiles sin autorización; toda dependencia de producción nueva requiere registro y ADR.
- No modificar `.env`, `vendor`, `node_modules`, `public/build`, `storage` o `bootstrap/cache` salvo que la tarea lo requiera expresamente. Nunca versionar secretos, PII, documentos reales, logs ni artefactos QA.
