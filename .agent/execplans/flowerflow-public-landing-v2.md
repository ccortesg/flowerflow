# ExecPlan: rediseño UX/UI de la landing pública V2

**Estado:** Completed

**Creado:** 2026-07-15 America/Hermosillo

## Propósito y resultado observable

La ruta pública `/` presenta la convocatoria Hermosillo Florece 2026 con una jerarquía visual cálida, clara y responsive, alineada con las referencias de escritorio y móvil entregadas. La persona visitante puede comprender categorías, proceso, requisitos, premio, documentos y preguntas frecuentes; puede crear una cuenta cuando el flag de registro está activo, iniciar sesión y descargar los PDF oficiales sin que cambien rutas, reglas de negocio, documentos, paneles autenticados ni datos.

## Estado y alcance

Incluido:

- landing `/`, header y footer exclusivos de la página pública principal;
- componentes Blade propios, CSS encapsulado y JavaScript mínimo para navegación móvil;
- categorías dinámicas y fallback seguro cuando no existe una competencia activa;
- estados derivados de `FLOWERFLOW_REGISTRATION_ENABLED` y `FLOWERFLOW_SUBMISSIONS_ENABLED`;
- uso de ambos logotipos, cartel oficial y derivados locales reproducibles del cartel;
- accesibilidad, responsive de 320 a 1920 px, pruebas Feature y documentación.

Excluido:

- panel autenticado, registro, login, recuperación, correo y formularios internos;
- rutas, migraciones, modelos, datos, reglas de negocio o texto jurídico de los PDF;
- dependencias nuevas, recursos remotos, imágenes de Apple o despliegue en producción;
- publicación o copia de los archivos de referencia visual.

## Contexto y contratos

- Rama aislada: `codex/ui-public-landing-v2`, basada en `origin/main` en `31cb4d5`.
- Laravel 12.64, Vite 6.3.5, Node 22.23.1 y Yarn 1.22.22 permanecen sin cambio.
- Los hechos jurídicos provienen de los tres PDF de `public/documentos/2026`; el cierre oficial es 15 de agosto de 2026 a las 23:59 en `America/Hermosillo`.
- El cartel y los dos logotipos de `public/assets/flowerflow` son los únicos recursos gráficos aprobados. Los recortes WebP son optimizaciones locales del cartel, sin texto jurídico nuevo.
- El layout autenticado y el chrome de otras rutas invitadas deben conservar su comportamiento. El nuevo chrome se activa únicamente cuando `request()->routeIs('landing')`.
- La página utiliza HTML semántico; los íconos salen del set Remix/Iconify ya presente y se marcan decorativos.

## Plan por pasos

1. Registrar baseline de pruebas, build, estado documental y recursos locales.
2. Preparar derivados visuales locales del cartel con nombres deterministas y documentar origen.
3. Separar header, secciones y footer del landing en parciales Blade.
4. Implementar estilos encapsulados y navegación móvil accesible sin dependencia nueva.
5. Extender `PublicLandingTest` con flags, fallback, contenido, anchors, FAQ y regresiones de texto.
6. Actualizar UX/UI, trazabilidad, overrides y evidencia de QA.
7. Ejecutar pruebas, Pint acotado, build, auditorías, validación de vistas/rutas y QA visual.

## Validación

```text
php artisan test
./vendor/bin/pint --test tests/Feature/PublicLandingTest.php
composer validate --strict
composer audit --locked --no-interaction
corepack yarn build
corepack yarn audit --groups dependencies
php artisan view:cache
php artisan route:list
git diff --check
```

Criterios:

- `/` responde 200 cuando el flag público está activo y 404 cuando está inactivo;
- registro activo muestra CTA navegable y registro inactivo muestra estado no interactivo;
- recepción activa/inactiva se comunica sin contradecir el flag;
- falta de competencia activa conserva tres categorías seguras;
- título, cierre, `Apple iPad Pro`, máximo un ganador por categoría y PDF oficiales son visibles;
- anchors del header apuntan a IDs existentes y el acordeón conserva relaciones ARIA válidas;
- no aparece `iPad Pro Max`, emoji como ícono, recurso remoto o cambio del panel;
- no hay desbordamiento horizontal ni controles inaccesibles entre 320 y 1920 px.

## Despliegue y rollback

Este plan no despliega. En un despliegue posterior se publican código y assets compilados, se regeneran cachés y se hacen smoke tests de `/`, `/register`, `/login` y los PDF bajo ambos estados de flags. No hay migraciones ni workers nuevos. El rollback consiste en volver al commit anterior y reconstruir `public/build`; no modifica datos.

## Registro vivo

- [x] 2026-07-15 MST — Prompt, referencias visuales, repositorio, `_referencia/`, reglas del agente, ADR aplicables y PDF oficiales revisados.
- [x] 2026-07-15 MST — Baseline backend verde: 28 pruebas/161 aserciones; Composer válido. Pint global reporta deuda previa fuera del alcance.
- [!] 2026-07-15 MST — Baseline Vite llegó a transformación pero excedió 120 segundos sobre WSL/NTFS; se repetirá con ventana suficiente y sin cambiar dependencias.
- [x] 2026-07-15 22:20 MST — Componentes, estilos, comportamiento móvil y derivados visuales implementados sin dependencia ni ruta nueva.
- [x] 2026-07-15 22:37 MST — Pruebas focales verdes: 6 pruebas/61 aserciones; manifest Vite generado con Node 22.23.1/Yarn 1.22.22.
- [x] 2026-07-15 22:42 MST — Suite completa verde: 33 pruebas/211 aserciones; Composer validate/audit, Pint acotado, vistas, rutas y `git diff --check` verdes.
- [x] 2026-07-15 22:49 MST — Build final verde en 3m 2s; manifest contiene los entrypoints CSS/JS. Se conserva la advertencia baseline de chunks demo mayores de 500 kB.
- [!] 2026-07-15 22:49 MST — `yarn audit` no está disponible: el endpoint oficial de Yarn Classic responde HTTP 410. No se crea `package-lock.json`; Composer audit queda verde y el límite se documenta.
- [!] 2026-07-15 22:49 MST — El intento automatizado de QA visual quedó bloqueado porque el navegador integrado no inicializó por una colisión global `process`.
- [x] 2026-07-16 10:06 MST — El usuario responsable confirmó la ejecución y aceptación manual del QA visual y responsive del área participante. La aceptación posterior reconcilia el pendiente histórico sin atribuir a Codex capturas ni comandos no ejecutados.
- [x] 2026-07-16 MST — Milestone cerrado documentalmente con base en la aceptación registrada en `design-qa.md` y `docs/12-project-status-2026-07-15.md`; no se repite el QA ya aceptado.

## Decisiones

- [x] 2026-07-15 MST — El rediseño se encapsula por ruta; no se reemplaza el shell autenticado ni el chrome de registro/login.
- [x] 2026-07-15 MST — No se descarga ni enlaza ningún recurso Apple. El premio se comunica con texto oficial y composición derivada del cartel aprobado.
- [x] 2026-07-15 MST — No se agrega una librería de íconos: se reutiliza Remix/Iconify ya incluido en el proyecto.
- [x] 2026-07-15 22:20 MST — El recorte panorámico excluye texto; el recorte del dispositivo excluye “Max”. Los hechos viven en HTML y los hashes/proceso quedan en docs 05.
- [x] 2026-07-15 22:25 MST — La URL de registro se genera como path estable `/register` sólo cuando el flag está activo, evitando un 500 si config y caché de rutas están temporalmente desalineados.

## Hallazgos

- El landing anterior usa una paleta verde global, cartel completo y símbolos Unicode como íconos.
- El build genera el CSS local de Iconify antes de transformar; esa generación no produjo diff en el baseline.
- Los tres PDF respaldan cierre, edad, residencia, equipos de hasta cinco, máximo tres propuestas, premio y máximo un ganador por categoría.
- Compartir `vendor` mediante symlink entre worktrees hacía que Composer resolviera PSR-4 y vistas desde el checkout original. Se instaló `vendor` local ignorado para obtener pruebas autoritativas sin tocar el repositorio base.
- El build existente incluye grandes globs de recursos demo y produce chunks de Mapbox/DataTables/Iconify aunque el landing no los consume directamente. La poda sigue fuera de alcance y debe tratarse en un carril separado con smoke visual.
