# ExecPlan: rediseño de acceso y experiencia participante

**Estado:** In progress

**Creado:** 2026-07-16 America/Hermosillo  
**Milestone:** rediseño local de inicio de sesión, inicio participante, perfil, propuestas y asistente de nueva propuesta

## Propósito y resultado observable

La persona participante podrá iniciar sesión, consultar y actualizar su perfil, administrar sus propuestas y preparar una nueva propuesta mediante un asistente persistente de cuatro pasos coherente con las referencias visuales aprobadas. El rediseño conserva las rutas, autorización, estados, límites y feature flags existentes; distribuye los campos ya aprobados entre pasos reales y mantiene la finalización transaccional existente. Los datos visibles siempre provienen de la persona autenticada y de sus propuestas reales.

## Estado y alcance

Incluido:

- `/login` y su variante `/panel/login`;
- shell autenticado compartido para participantes, incluida su adaptación móvil;
- `/inicio`, con saludo real, resumen de propuestas/perfil, guía de participación e información de la convocatoria activa;
- `/perfil`, con resumen real de completitud y edición progresiva mediante `PUT /perfil`;
- `/propuestas`, con datos reales, máximo configurado, acciones autorizadas, búsqueda y filtro progresivos;
- `/propuestas/nueva/crear`, `/propuestas/{submission}/editar?step=1|2|3` y la revisión del borrador en `/propuestas/{submission}`, con persistencia explícita por paso;
- modalidad individual/equipo, categoría, contenido Quill seguro, adjuntos privados, enlaces permitidos, cuota compartida y revisión/finalización existentes;
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
- El asistente usa guardado explícito; no afirma autoguardado porque no existe un endpoint ni un control de concurrencia aprobado para esa función.
- `?step=1|2|3` selecciona la sección renderizada en servidor y cualquier valor inválido vuelve de forma segura al paso 1. Cada `PUT` sólo puede modificar los campos del paso declarado.
- El paso 3 es opcional para conservar un borrador, pero la revisión y `FinalizeSubmission` mantienen el requisito real de al menos un documento antes del envío final.
- Drag and drop, vistas previas y advertencia de cambios sin guardar son mejoras progresivas sobre inputs de archivo y formularios estándar; el servidor conserva toda la autoridad.
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
9. Rediseñar la nueva propuesta como asistente real de cuatro pasos, cubrir preservación/validación por paso, archivos/enlaces y revisión final, actualizar documentación y repetir todos los gates.
10. Rediseñar `/inicio` con datos reales y simplificar el menú participante compartido, preservando documentos/FAQ públicos y el panel administrativo.

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
- `/inicio` muestra nombre completo con fallback, conteos propios, perfil real, límite configurado, cierre/categorías de la convocatoria activa y un CTA que además exige perfil completo;
- el menú participante de escritorio y móvil contiene sólo Inicio, Mis propuestas, Nueva propuesta condicional y Mi perfil; `/documentos`, sus PDF y la FAQ pública permanecen intactos;
- cada paso guarda únicamente su sección, volver atrás no borra otras secciones y continuar conduce 1 → 2 → 3 → revisión;
- la descripción sólo es obligatoria al continuar, los documentos sólo al finalizar y la suma de archivos existentes/nuevos respeta la cuota configurada;
- enlaces con hosts ajenos o credenciales integradas se rechazan y la vista previa usa `youtube-nocookie.com` sin solicitudes de servidor;
- propietario/estado se autorizan mediante Policy y una propuesta enviada no puede editarse ni perder archivos;
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
- [x] 2026-07-16 02:10 MST — Seis referencias de nueva propuesta y el prompt específico fueron inspeccionados; se conservaron reglas jurídicas, contratos de seguridad y diferencias deliberadas frente a datos/autoguardado simulados.
- [x] 2026-07-16 02:32 MST — Asistente server-rendered de pasos 1–4 implementado en la capa propia, con guardado explícito por sección, Quill seguro, archivos/enlaces progresivos y revisión final real.
- [x] 2026-07-16 02:32 MST — Pruebas enfocadas verdes: `SubmissionFlowTest` (3/18) y `SubmissionWizardTest` (7/75); la suite completa y QA visual permanecen como gates abiertos.
- [x] 2026-07-16 02:39 MST — Suite completa verde inicial: 44 pruebas/355 aserciones; Pint acotado y build Vite con Yarn 1.22.22 verdes.
- [!] 2026-07-16 02:42 MST — QA visual del asistente bloqueado: servidor y cuenta sintética funcionaron, pero el navegador integrado falló al inicializar (`Cannot redefine property: process`) antes de crear captura. La cuenta fue eliminada y el servidor exclusivo se detuvo; Playwright CLI requiere autorización.
- [x] 2026-07-16 02:55 MST — Cobertura obligatoria completada y suite final verde: 45 pruebas/393 aserciones; CSP permite únicamente previews `blob:` locales y frames de `youtube-nocookie.com`.
- [x] 2026-07-16 07:20 MST — Referencia de `/inicio`, prompt, reglas, ADR, documentación y PDF jurídicos inspeccionados; baseline verde: 45 pruebas/393 aserciones, Composer y Vite. Pint global conserva la deuda previa aprobada.
- [x] 2026-07-16 07:33 MST — Dashboard dinámico y menú participante reducido implementados sin migraciones, dependencias ni activos nuevos; prueba enfocada verde: 9 pruebas/170 aserciones.
- [x] 2026-07-16 07:39 MST — Gates automatizados finales verdes: 50 pruebas/500 aserciones, Pint PHP acotado, Composer, caché de vistas y build Vite con Yarn 1.22.22.
- [!] 2026-07-16 07:41 MST — QA visual de `/inicio` bloqueado: el navegador integrado falló durante la inicialización (`Cannot redefine property: process`) antes de abrir una pestaña. La cuenta sintética fue eliminada y el servidor exclusivo se detuvo; `design-qa.md` conserva `final result: blocked` y Playwright CLI requiere autorización expresa.

## Hallazgos y pendientes

- El componente telefónico existente usaba un emoji de bandera; se reemplazará por iconografía de la biblioteca existente para mantener consistencia.
- El shell previo mezclaba participante y panel privilegiado en la misma composición; el rediseño debe aislar la nueva experiencia sólo al rol participante.
- La suite pasó con Pint acotado; el fallo global preexistente no fue modificado ni ocultado.
- El requisito histórico `SUB-001` mencionaba autoguardado como aspiración de MVP; para esta Fase 01 se resuelve como guardado explícito y advertencia local de cambios, sin simular persistencia automática.
- El formulario monolítico anterior mezclaba campos, archivos y enlaces en un solo `POST`; el asistente exige `wizard_step`/`wizard_action` y limita cada actualización a una sección para evitar sobreescrituras laterales.
- La referencia de `/inicio` contenía una campana, textos de evaluación participativa y un premio incorrecto; se omitieron deliberadamente y se usaron sólo rutas, estados, premio y datos aprobados.
- `PENDING`: la aprobación para despliegue y UAT productivo no forma parte de este trabajo local.
