# Inventario de overrides de Materialize

Fecha de corte: 2026-07-15  
Template declarado localmente: Materialize 3.0.0, Pixinvent, Commercial

## 1. Estado actual

No se identificó ningún override específico de Flower Flow en la baseline. Tampoco se encontraron marca, colores, contenido, menús o módulos Flower Flow.

Esto no demuestra que el template esté intacto:

- no existe .git ni historial;
- no se dispone del ZIP/licencia originales para comparar;
- la numeración 3.0.0 de package.json no coincide necesariamente con la serie pública de releases del producto Pixinvent;
- faltan varios assets que las vistas demo referencian.

Por ello, el estado se clasifica como snapshot vendorizado de procedencia por confirmar, no como upstream limpio.

## 2. Fronteras

### 2.1 Core vendorizado: evitar edición directa

- resources/assets/vendor/**
- resources/assets/vendor/js/template-customizer.js
- recursos fuente de plugins copiados bajo resources/assets/vendor/libs/**
- SCSS base bajo resources/assets/vendor/scss/**

Las correcciones inevitables dentro de estas rutas deben registrar:

- archivo y líneas;
- motivo;
- issue o limitación upstream;
- prueba visual;
- impacto al actualizar el template;
- estrategia para reaplicar o retirar el parche.

### 2.2 Capa de integración del template

Estas rutas son parte del starter y pueden adaptarse con revisión explícita:

- resources/views/layouts/**
- resources/views/_partials/**
- resources/menu/verticalMenu.json
- resources/menu/horizontalMenu.json
- config/custom.php
- config/variables.php
- vite.config.js
- vite.icons.plugin.js

Siempre se prefiere configuración, composición o un componente Flower Flow sobre copiar y divergir un layout completo.

### 2.3 Capa propia recomendada

La implementación futura debe concentrar identidad y comportamiento nuevo fuera del core:

- resources/css/app.css para tokens/overrides pequeños o un entrypoint Flower Flow dedicado aprobado;
- resources/js/app.js y módulos por página;
- componentes Blade propios;
- vistas de dominio separadas de demos;
- assets autorizados con nombres propios;
- configuración de producto centralizada.

No editar public/build manualmente. Es un artefacto generado.

## 3. Inventario baseline

| Ruta | Observación | Clasificación | Acción futura |
|---|---|---|---|
| package.json | name Materialize, version 3.0.0, license Commercial | Metadata vendorizada | Confirmar release y licencia adquirida |
| config/variables.php | Nombre, enlaces, OG, canonical y redes de Pixinvent | Config demo | Sustituir por configuración Flower Flow aprobada |
| config/custom.php | Layout vertical; customizer visible y activo | Config demo | Mantener layout; desactivar customizer en producción |
| resources/assets/css/demo.css | Estilos de marca/demo | Demo | Retirar del entrypoint cuando existan estilos propios equivalentes |
| resources/views/_partials/macros.blade.php | Logotipo SVG de Materialize | Core visual demo | Reemplazar mediante componente/logo autorizado |
| resources/views/layouts/commonMaster.blade.php | Metadatos Pixinvent y noindex global | Integración sensible | Separar SEO público, noindex privado y staging |
| resources/views/layouts/layoutFront.blade.php | Shell front disponible | Reutilizable | Usarlo para sitio público tras limpiar navbar/footer |
| resources/views/layouts/contentNavbarLayout.blade.php | Shell vertical administrativo | Reutilizable | Mantener como base de backoffice |
| resources/views/layouts/sections/navbar/navbar-partial.blade.php | John Doe, Admin, billing y referencias Jetstream ausente | Demo con fallo latente | Reescribir según auth/RBAC aprobado |
| resources/views/layouts/sections/navbar/navbar-front.blade.php | CTA Login/Register sin destino real | Demo | Conectar a rutas reales y navegación pública |
| resources/views/layouts/sections/footer/footer.blade.php | Créditos/enlaces Pixinvent | Demo | Sustituir por footer Flower Flow/licencia requerida |
| resources/views/layouts/sections/footer/footer-front.blade.php | Newsletter, demos, app stores y copy React | Demo | Reemplazar completo con contenido aprobado |
| resources/menu/*.json | Cinco entradas demo | Demo | Reemplazar por menús autorizados por rol |
| resources/views/content/pages/pages-home.blade.php | Home genérico | Demo | Sustituir por landing aprobada |
| resources/views/content/pages/pages-page2.blade.php | Page 2 | Demo | Retirar ruta y vista tras smoke |
| resources/views/content/pages/pages-misc-error.blade.php | 404 visual en ruta 200 | Demo defectuoso | Crear resources/views/errors con status real |
| resources/views/content/authentications/* | Formularios GET, social links falsos | Demo defectuoso | Adaptar después de aprobar Fortify/auth |
| resources/assets/js/* | Scripts de academy, ecommerce, logistics, kanban y demos UI | Biblioteca demo | Excluir por página; retirar sólo con evidencia de no uso |
| vite.config.js | Globs registran toda la biblioteca | Integración de build | Reducir entradas después de mapear imports |
| public/assets/img/avatars/* | Avatares demo | Demo/PII ficticia | Retirar o sustituir por placeholder neutro |
| public/assets/img/customizer/* | Capturas del customizer | Demo | Retirar cuando el customizer quede deshabilitado |

## 4. Assets faltantes

Las vistas actuales referencian, pero la baseline no contiene:

- public/assets/img/illustrations/auth-basic-login-mask-light.png
- public/assets/img/illustrations/auth-basic-login-mask-dark.png
- public/assets/img/illustrations/auth-basic-register-mask-light.png
- public/assets/img/illustrations/auth-basic-register-mask-dark.png
- public/assets/img/front-pages/backgrounds/footer-bg.png
- public/assets/img/front-pages/landing-page/apple-icon.png
- public/assets/img/front-pages/landing-page/google-play-icon.png

No deben recuperarse copiándolos de una demo pública sin confirmar licencia. Se deben usar assets incluidos legalmente en la compra, suministrados por el usuario o reemplazos propios autorizados.

## 5. Cambios demo a ejecutar en milestone posterior

Nada de esta lista fue ejecutado durante la fase documental:

1. Confirmar y archivar evidencia de la licencia Pixinvent.
2. Crear baseline visual de las cinco pantallas antes de editar layouts.
3. Definir tokens Flower Flow accesibles.
4. Sustituir metadata, favicon, logo y copy de Pixinvent.
5. Desactivar customizer en producción.
6. Limpiar menús y rutas demo.
7. Separar layout público de layout autenticado.
8. Eliminar acoplamientos Jetstream si se adopta Fortify sin Jetstream.
9. Corregir errores 404/419/429/500 con status reales.
10. Racionalizar imports Vite por página.
11. Sustituir assets faltantes con material autorizado.
12. Validar shell vertical, front, blank, RTL deshabilitado, móvil y dark mode si se conserva.

## 6. Registro de overrides reales

| Fecha | Archivo | Líneas | Cambio | Motivo | Prueba | Reaplicación en upgrade |
|---|---|---|---|---|---|---|
| 2026-07-15 | Ninguno | N/A | No hay overrides Flower Flow | Baseline documental | Auditoría estática | N/A |

Toda modificación futura al core vendorizado debe añadir una fila. Los cambios en componentes propios no son overrides del proveedor, pero deben quedar trazables en Git y en el ExecPlan.

## 7. Regla de actualización

Antes de actualizar Materialize:

1. Leer el changelog oficial.
2. Confirmar compatibilidad Laravel, Bootstrap, Vite y Node.
3. Comparar upstream contra la baseline vendorizada.
4. Reaplicar sólo overrides documentados.
5. Instalar en rama/worktree aislado.
6. Ejecutar tests, build y auditoría de dependencias.
7. Hacer revisión visual de layouts públicos, autenticados y auth.
8. No mezclar actualización del template con un milestone de dominio.
