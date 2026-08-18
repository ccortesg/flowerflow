# Release candidate local con documentos jurídicos v1.1

Este ExecPlan es un documento vivo y se rige por `AGENTS.md` y `.agent/PLANS.md`. La autorización proviene de la solicitud expresa del propietario del 2026-08-17 y del seguimiento del 2026-08-18 que autoriza cerrar P2 con una vista 503 propia. El trabajo es exclusivamente local/test: no autoriza stage, commit, push, acceso a AWS, cambios en servicios compartidos ni despliegue.

## Propósito y resultado observable

Incorporar de manera inmutable y trazable las versiones jurídicas v1.1 de la Mecánica, los Términos y el Aviso de Privacidad; reconciliarlas por página/sección contra v1.0, código y documentación; actualizar los vínculos y textos aplicables; preservar las aceptaciones históricas; y validar el release candidate completo sobre MySQL desechable con pruebas y navegador real para visitante, participante, reviewer y admin.

Al terminar, cada documento vigente tendrá ruta, versión y SHA-256 exactos; la aplicación registrará la versión realmente aceptada; las superficies públicas y autenticadas serán coherentes con v1.1; las decisiones jurídicas no explícitas quedarán marcadas `PROPOSAL_NEEDED`; y existirá una decisión local de go/no-go sustentada en gates reproducibles.

## Estado y alcance

- Rama/SHA iniciales: `codex/submission-deadline-extension` / `e2f4345dd7ec8c2e0b8285a06b5f560e3c3118d3`.
- Se preservan los cambios documentales preexistentes de la auditoría del 2026-08-17 y los seis PDF jurídicos.
- Base destructible exclusiva: MySQL `flowerflow_testing`, usuario `flowerflow_testing_user`, host `127.0.0.1`, con datos sintéticos.
- Incluye análisis textual y visual de los seis PDF, matriz v1.0 -> v1.1, versionado legal, vínculos, textos de landing/panel/formularios, tests, seeders/migración si resulta necesaria, documentación y UAT local.
- Excluye jueces, evaluación, ganadores, resultados, ARCO completo, nuevas reglas no contenidas en v1.1, SMTP real, EC2, DNS, TLS, AWS, producción, stage, commit y push.
- Los archivos `:Zone.Identifier` no son documentos jurídicos y se excluyen de vínculos, inventario jurídico, build y release.

## Fuentes e invariantes

Fuentes nuevas obligatorias:

- `public/documentos/2026/01_Mecanica_Convocatoria_Hermosillo_Florece_2026_v1.1.pdf`.
- `public/documentos/2026/02_Terminos_y_Condiciones_Plataforma_Flower_Flow_2026_v1.1.pdf`.
- `public/documentos/2026/03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026_v1.1.pdf`.

Invariantes:

- Los PDF v1.0, hashes y aceptaciones históricas son inmutables.
- Una aceptación nueva debe apuntar al registro activo exacto que la persona vio; ninguna operación reescribe `legal_acceptances` anteriores.
- Debe existir una versión activa determinística por tipo jurídico.
- Contenido jurídico no explícito, contradictorio o sin vigencia inequívoca se registra como `PENDING`, `POR_CONFIRMAR` o `PROPOSAL_NEEDED`; no se infiere.
- UTC permanece como persistencia y `America/Hermosillo` como zona de presentación y reglas de convocatoria.
- Residencia y anexos continúan privados y separados; no se amplían permisos ni exposición de PII.
- Resultados permanecen apagados y los roles sembrados siguen siendo `participant`, `reviewer` y `admin`.

## Modelo y contratos afectados

- `legal_documents`: incorporar registros v1.1 con `code`, `version`, ruta, SHA-256, vigencia y `active`; conservar v1.0 inactivo e inmutable cuando corresponda.
- `legal_acceptances`: conservar filas existentes y registrar `document_version=1.1`/documento activo para nuevas aceptaciones, sin backfill destructivo.
- Seeder/migración: estrategia idempotente y reversible sólo si hace falta trasladar el registro activo en instalaciones existentes.
- Superficies: landing, `/documentos`, registro, login, perfil, envío final, footer, paneles/correos únicamente donde el contenido v1.1 sea aplicable.
- Pruebas: archivos/hashes/rutas, única versión activa por tipo, preservación v1.0, aceptación real, permisos, enlaces y coherencia de contenido.

## Plan de trabajo

1. Congelar baseline Git/runtime, demostrar el guard MySQL y ejecutar pruebas/build iniciales.
2. Inventariar, extraer y renderizar los seis PDF; inspeccionar cada página y producir la matriz jurídica con evidencia.
3. Mapear cada cambio verificado a código, configuración, datos, vistas, correos, pruebas y documentación.
4. Implementar el versionado v1.1 y la actualización de vínculos/textos con cambios mínimos y preservación histórica.
5. Actualizar documentación, trazabilidad, diagnóstico y riesgos; registrar decisiones que requieren aprobación.
6. Recrear exclusivamente `flowerflow_testing`, migrar/sembrar y ejecutar gates completos.
7. Levantar el runtime temporal con flags autorizados y correo no real; ejecutar UAT Playwright por rol, viewport, teclado, permisos y flujos críticos.
8. Revisar secretos/PII/diff, documentar rollback, decisión go/no-go y siguiente prompt.

## Validación

Guard previo obligatorio:

    php artisan env --env=testing
    php artisan tinker --env=testing --execute='consulta de config y SELECT DATABASE()/CURRENT_USER()/@@session.time_zone sin secretos'

Gates:

    php artisan test
    vendor/bin/pint --test
    composer validate --strict --no-check-publish
    composer check-platform-reqs --no-dev
    composer audit --locked
    corepack yarn audit --groups dependencies --level moderate
    scripts/build_frontend_production.sh
    php artisan route:list --except-vendor
    php artisan schedule:list
    php artisan migrate:status --env=testing
    git diff --check

Validación adicional:

- PDF válidos, páginas renderizadas e inspeccionadas, hashes y rutas canónicas de v1.0/v1.1.
- Búsqueda global de vínculos v1.0 y comprobación HTTP de PDF v1.1 con MIME correcto.
- Migración/seed idempotentes y preservación de versiones/aceptaciones.
- UAT en escritorio, tableta y móvil para visitante, participante, reviewer y admin; consola, teclado, foco, zoom/reflow, 403/404, IDOR, rate limits, fechas y exportación.
- Correo `array`/fake y datos exclusivamente sintéticos.

## Despliegue y rollback

No hay despliegue. El rollback local consiste en retirar las referencias activas v1.1 y restaurar código/configuración a la versión previa sin borrar PDF v1.0, v1.1, aceptaciones ni uploads. Si se crea migración, su `down` debe reactivar v1.0 sólo cuando encuentre exactamente el estado esperado de v1.1 y nunca borrar aceptaciones. La base `flowerflow_testing` puede recrearse después de repetir el guard; ninguna otra base se modifica.

## Registro vivo

- [x] 2026-08-17 MST — `pwd`, Git toplevel, rama, SHA y estado verificados; se identificaron cambios documentales preexistentes y los tres PDF v1.1 sin rastrear.
- [x] 2026-08-17 MST — Guard MySQL demostrado sin secretos: `testing`, `mysql`, `127.0.0.1`, `flowerflow_testing`, `flowerflow_testing_user`, `SELECT DATABASE()=flowerflow_testing`, sesión `+00:00`.
- [x] 2026-08-17 MST — Reglas, plan maestro, diagnóstico, ExecPlans vigentes, ADR aplicables y skills PDF/Playwright leídos.
- [x] 2026-08-17 MST — Baseline automatizado: 104 pruebas/986 aserciones, Pint, Composer validate/platform/audit y build Vite verdes; Yarn conserva un advisory bajo conocido de Quill 2.0.3.
- [x] 2026-08-17 MST — Los seis PDF son A4/PDF 1.7, no cifrados, no symlinks y permanecen dentro del repositorio; 28 páginas renderizadas e inspeccionadas sin defectos visuales.
- [x] 2026-08-18 MST — El PDF definitivo `11c399ca…` confirma cuatro categorías y máximo cuatro propuestas. El propietario aceptó que la referencia a accesibilidad permanezca en Movilidad con Flow y Hermosillo sin Barreras; no se recategoriza ni cambia la operación.
- [x] 2026-08-18 MST — El propietario designó el PDF físico v1.0 actual (`3bcf31…`) como la versión que debe conservarse. El registro/seeder histórico `42bd5e…` y las aceptaciones relacionadas permanecen intactos; la discrepancia sigue documentada, pero deja de ser un bloqueo.
- [x] 2026-08-17 MST — Inventario definitivo: Mecánica v1.1, 866607 bytes/5 páginas/SHA-256 `11c399ca84735d7dbcb17174e192582c93589afa5100c0250753ca15def4db36`; Términos v1.1, 844116/4/`4e3e6c27…`; Aviso v1.1, 874312/5/`041ae970…`. Los `:Zone.Identifier` se reportan por separado y se excluyen mediante `.gitignore` sin leer su contenido.
- [x] 2026-08-17 MST — Se implementó catálogo v1.1 inmutable en configuración, seeder y migración reversible; cada consumo crítico falla cerrado si no existe exactamente un documento activo por tipo. No se alteran filas de `legal_acceptances` históricas.
- [x] 2026-08-17 MST — `flowerflow_testing` se recreó con las 12 migraciones del árbol y datos sintéticos. La verificación posterior mostró seis registros jurídicos, una v1.1 activa por tipo, cuatro categorías activas, cierre UTC `2026-08-24T06:59:59+00:00` y cero aceptaciones tras la limpieza final.
- [x] 2026-08-17 MST — Una primera repetición del guard usó por error el alias inexistente `DB` dentro de Tinker; el comando terminó sin abortar la shell. Aunque el `migrate:fresh` siguiente llevaba explícitamente el ambiente/base/usuario exactos y existía evidencia previa correcta, se repitieron de inmediato el guard completo válido y luego `migrate:fresh --seed`. Ese segundo ciclo es el baseline efectivo documentado.
- [x] 2026-08-17 MST — Se creó `scripts/serve_local_testing.sh`: valida ambiente `testing`, MySQL loopback, base/usuario exclusivos y `SELECT DATABASE()` antes de servir; fuerza sesiones/cache en archivos, correo array, cola sync, flags UAT autorizados y resultados apagados. `FLOWERFLOW_TEST_GUARD_ONLY=true` y `bash -n` fueron verdes.
- [x] 2026-08-17 MST — Se corrigió el enrutamiento Apache de `/documentos`: el directorio físico homónimo impedía llegar a Laravel; una regla exacta anterior a `-d` preserva los PDF estáticos. `apache2ctl configtest` devolvió `Syntax OK`. La UAT Apache autenticada no se ejecutó porque el `.env` primario no usa la base/cuenta exclusiva; el runtime seguro se levantó con el script de testing.
- [x] 2026-08-17 MST — UAT visitante/participante/reviewer/admin realizada en 1440/768/360 px con teclado, foco, zoom/reflow y consola. Se verificaron alta/verificación controlada, aceptaciones v1.1, perfil, cuatro propuestas y rechazo de quinta, wizard/archivo privado/envío/folio, aclaración/residencia/admisión, panel/paginación, 2FA, exportación XLSX/expiración/purga, 403/404/IDOR y cierre 503. Todo fue sintético y se eliminó con `migrate:fresh`.
- [x] 2026-08-18 MST — P2 503/CSP resuelto por autorización expresa: `resources/views/errors/503.blade.php` reutiliza layout, Vite y marca FlowerFlow, no contiene estilos inline, expone encabezado/descripción accesibles y funciona tanto para cierre por middleware como para `artisan down --render="errors::503"`. Prueba dirigida bajo CSP estricta verde y render real revisado en Chrome 1440 px y Firefox 390 px sin desbordamiento.
- [x] 2026-08-17 MST — `LegalDocumentsV11Test` contra el PDF definitivo: 3 pruebas/36 aserciones verdes. Suite final: 107 pruebas/1,031 aserciones. Pint, Composer validate/platform/audit, build (784 módulos/97 iconos), JSON, 66 rutas/41 propias, schedule, PHP lint y `git diff --check` verdes. Yarn conserva sólo el advisory bajo conocido de Quill 2.0.3 y devuelve código 2.
- [x] 2026-08-17 MST — La alta browser final posterior a la suite encontró que `RefreshDatabase` deja el esquema sin catálogos sembrados: faltó el rol `participant`, la transacción revirtió y no quedó aceptación parcial. `scripts/serve_local_testing.sh` ahora valida esquema, rol, convocatoria, cuatro categorías, tres legales activos y flags; usa salida no cero porque Tinker normaliza `exit(72)` a código 1, suficiente para `set -e`.
- [x] 2026-08-17 MST — Tras ejecutar el seeder autorizado, una alta Firefox real registró cuatro evidencias v1.1 de Términos/Privacidad con sus FK/hash definitivos. Se cerró navegador/servidor y se repitió guard + `migrate:fresh --seed`; estado final: 12 migraciones, cuatro categorías, tres v1.1 activas con hashes exactos, cero usuarios y cero aceptaciones.
- [x] 2026-08-17 MST — Decisión final: `GO` técnico para el release candidate exclusivamente local/test. Producción/despliegue sigue `NO-GO`, fuera de alcance y condicionado a decisiones jurídicas/evidenciales y gates externos.
- [x] 2026-08-18 MST — Seguimiento P2 final: guard MySQL exacto repetido; cuenta sintética eliminada; 11 pruebas dirigidas/53 aserciones y suite completa 109/1,049 verdes. Pint global/acotado, Composer validate/platform/audit, build Vite (784 módulos/97 iconos), estado de mantenimiento activo y `git diff --check` verdes. Codex no accedió ni desplegó producción.
- [x] 2026-08-18 MST — El propietario confirmó la topología productiva real: checkout Git directo `/var/www/flowerflow`, sin `releases/current/shared`, asociado al VirtualHost informado `app.sguniformes.com.mx`. Se actualizan ADR/runbooks/handoff/diagnóstico como documentación; no se accede al servidor ni se infiere un cambio del host canónico `app.flowerflow.com.mx`.
- [x] 2026-08-18 MST — Entrada histórica append-only: el propietario confirma que instaló los cambios actuales y que la plataforma continúa publicada con más de 50 propuestas reales (`OWNER_CONFIRMED_DEPLOYED`). Esta confirmación sustituye la puerta documental “desplegar el RC” por “cerrar decisiones/diseño Fase 02B”, pero no reescribe la evidencia anterior de que Codex no desplegó ni accedió a producción. Sin evidencia inequívoca del release, `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR`; migraciones, flags, workers, scheduler, SMTP, monitoreo, integridad, smoke y UAT productiva permanecen sin verificación independiente.

## Decisiones, sorpresas y pendientes

- `RESOLVED / OWNER DECISION`: las cuentas con v1.0 se tratan operativamente como aceptantes de v1.1, sin forzar reaceptación ni fabricar/modificar filas históricas. Nuevas aceptaciones siguen registrando v1.1.
- `RESOLVED / OWNER ACCEPTED`: accesibilidad permanece en ambas categorías como está, sin recategorización.
- `RESOLVED / OWNER DESIGNATION`: se conserva el archivo físico v1.0 `3bcf31…`; `42bd5e…` permanece como antecedente histórico visible.
- `PENDING`: licencia Materialize/Pixinvent, SMTP, storage productivo, backup/RPO/RTO y evidencia técnica independiente de producción permanecen fuera de este milestone; el despliegue sólo está `OWNER_CONFIRMED_DEPLOYED`.
