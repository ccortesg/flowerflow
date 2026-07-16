# ExecPlan: rediseño de acceso y experiencia participante

**Estado:** In progress

**Creado:** 2026-07-16 America/Hermosillo  
**Milestone:** rediseño local de inicio de sesión, perfil y propuestas

## Propósito y resultado observable

La persona participante podrá iniciar sesión, consultar y actualizar su perfil y administrar la vista de sus propuestas mediante una interfaz coherente con las referencias visuales aprobadas. El rediseño conservará rutas, nombres de campos, validaciones, autorización, estados, límites y feature flags existentes. Los datos visibles siempre provendrán de la persona autenticada y de sus propuestas reales.

## Estado y alcance

Incluido:

- `/login` y su variante `/panel/login`;
- shell autenticado compartido para participantes, incluida su adaptación móvil;
- `/perfil`, con resumen real de completitud y edición progresiva mediante `PUT /perfil`;
- `/propuestas`, con datos reales, máximo configurado, acciones autorizadas, búsqueda y filtro progresivos;
- accesibilidad, responsive, pruebas Feature, documentación y validación visual local.

Excluido:

- nuevas rutas, tablas, migraciones, estados o dependencias;
- datos ficticios, notificaciones inexistentes o verificación telefónica no implementada;
- cambios en `_referencia/`, `public/build`, originales de `imagen/` o PDF jurídicos;
- `git add`, commit, push, fetch, pull, despliegue o modificación de producción.

Decisiones y supuestos:

- Se reutilizan los logotipos autorizados y los iconos Remix existentes; no se generan activos nuevos.
- La completitud presenta `100%` únicamente cuando `ParticipantProfile::isComplete()` es verdadera; en otro caso comunica que hay información pendiente sin inventar porcentaje.
- La búsqueda y el filtro de propuestas son cliente, sin AJAX, y dejan todo visible cuando JavaScript no está disponible.
- La edición de perfil usa mejora progresiva: sin JavaScript el formulario completo permanece editable; con JavaScript inicia en modo resumen y permite editar por sección.
- El fallo global preexistente de Pint queda fuera del milestone. El usuario autorizó el 2026-07-16 continuar con Pint acotado a PHP modificado y mantener `_referencia` intacta.

## Contexto y contratos

- Laravel 12, Blade y Vite se conservan conforme a ADR-0001.
- Las rutas permanecen bajo middleware `auth` y `verified`; las consultas continúan filtradas por la relación del usuario y las Policies existentes, conforme a ADR-0004 y ADR-0005.
- Los estados válidos mostrados son `draft` (`Borrador`) y `submitted` (`Enviada`).
- El límite se obtiene de `flowerflow.limits.submissions_per_user`, actualmente tres; sólo se permite una propuesta por categoría.
- Los timestamps se guardan en UTC y se presentan con `flowerflow.timezone`, conforme a ADR-0003.
- El correo de ayuda es `convocatoria@flowerflow.com.mx`; privacidad usa `privacidad@flowerflow.com.mx` y el documento vigente existente.
- Los estilos nuevos se encapsulan en contextos `ff-auth-login-page` y `ff-participant-*`, fuera del core del proveedor.

## Plan por pasos

1. Crear este ExecPlan y registrar baseline, excepción aprobada y contratos reales.
2. Implementar el shell participante reutilizable y navegación móvil accesible sin alterar el panel privilegiado.
3. Rediseñar `/login` y preservar la variante administrativa de `/panel/login`.
4. Rediseñar `/perfil` con resumen real, secciones y edición progresiva funcional.
5. Rediseñar `/propuestas` con límite real, estados, fechas, acciones, búsqueda, filtro y empty state.
6. Añadir pruebas de regresión y actualizar UX, overrides y trazabilidad.
7. Ejecutar suite, Pint acotado, Composer, build, rutas, vistas y revisión del diff.
8. Verificar en navegador escritorio/móvil/teclado y cerrar `design-qa.md` contra las referencias.

## Validación

```text
php artisan test
./vendor/bin/pint --test <archivos PHP modificados>
composer validate --no-check-publish
cmd.exe /d /c "corepack yarn build"
php artisan view:cache
php artisan route:list
git diff --check
```

Criterios:

- `/login` mantiene contrato Fortify, errores y feature flag; `/panel/login` no muestra registro ni beneficios de participante.
- el shell muestra sólo rutas reales, nombre/iniciales reales y ninguna campana o fotografía ficticia;
- `/perfil` usa `isComplete()`, conserva `old()`, errores, nombres de campos y endpoint;
- `/propuestas` sólo muestra propuestas de la persona autenticada, estados reales, fecha en Hermosillo y acciones autorizadas;
- el CTA se oculta al deshabilitar recepción o alcanzar el máximo;
- móvil no tiene scroll horizontal y los controles principales cumplen 44 px;
- la comparación visual final queda registrada con resultado `passed` o, si el navegador no está disponible, `blocked` sin declarar el milestone completo.

## Despliegue y rollback

No se desplegará en este milestone. Un despliegue futuro requerirá el flujo productivo aprobado, backup/UAT y build reproducible. El rollback local consiste en revertir exclusivamente los archivos enumerados en el diff; no hay esquema ni datos que compensar.

## Registro vivo

- [x] 2026-07-16 00:50 MST — Referencias de login, perfil y propuestas inspeccionadas; se catalogaron activos, jerarquía, responsive y diferencias obligatorias con el sistema real.
- [x] 2026-07-16 01:00 MST — PDF jurídicos revisados sin modificarlos; límites, elegibilidad, premio, cierre y contactos coinciden con el contrato documentado.
- [x] 2026-07-16 01:05 MST — Baseline: 33 pruebas/211 aserciones, Composer y build Vite verdes; Pint global falla por deuda previa fuera del alcance.
- [x] 2026-07-16 01:10 MST — Usuario autoriza continuar con Pint acotado y `_referencia` intacta.
- [x] 2026-07-16 01:13 MST — Implementación de shell y tres pantallas completada; rutas y contratos backend preservados.
- [x] 2026-07-16 01:35 MST — Suite completa verde: 37 pruebas/274 aserciones; Pint acotado, Composer, vistas, rutas, build Vite y `git diff --check` verdes.
- [!] 2026-07-16 01:40 MST — QA visual bloqueado: el navegador integrado no contiene `scripts/browser-client.mjs`; `design-qa.md` registra `final result: blocked` y se requiere autorización antes de usar Playwright CLI como alternativa.

## Hallazgos y pendientes

- El componente telefónico existente usaba un emoji de bandera; se reemplazará por iconografía de la biblioteca existente para mantener consistencia.
- El shell previo mezclaba participante y panel privilegiado en la misma composición; el rediseño debe aislar la nueva experiencia sólo al rol participante.
- La suite pasó con Pint acotado; el fallo global preexistente no fue modificado ni ocultado.
- `PENDING`: la aprobación para despliegue y UAT productivo no forma parte de este trabajo local.
