# QA de diseño — Flower Flow

## Votación pública — 2026-09-08

**Entorno:** checkout local `codex/submission-deadline-extension`, Chromium mediante Playwright, datos sintéticos en `flowerflow_testing`. Sólo local/test; sin credenciales ni votos. Plan aprobado y referencia: [ADR-0017](adr/0017-public-voting-google-forms.md), flyer conservado en `imagen/ff-flyer-votacion-original.jpg`.

**Estado:** interacción y comparación visual verificadas; cierre del control global en [informe 34](34-public-voting-integration-2026-09-08.md). UAT con sesión Google permanece `PENDING`. No constituye certificación completa de accesibilidad.

| Superficie | Resultado observado |
|---|---|
| Tipografía | Georgia/serif para «¡La gente elige!»; familia de interfaz existente. Texto HTML seleccionable y un único H1; sin fuentes nuevas. |
| Composición | Texto/acción a la izquierda y urna a la derecha en escritorio; texto/acción antes de ilustración en móvil. No se reproduce el formato vertical del cartel. |
| Color y controles | Crema/verde/naranja; botones con naranja oscuro existente, altura del CTA principal 52.77 px, cierre 44 px y foco visible. |
| Recursos | PNG generado sin textos/logos, WebP 640/1024 con dimensiones explícitas y `srcset`; logotipos oficiales conservados. No se conecta a Google antes de abrir. |
| Contenido | Textos aprobados, sin «Próximamente». Introducción, categorías, requisitos, premios inferiores, documentos y FAQ conservados byte por byte frente al HEAD inicial. |

### Medidas y comportamiento

| Viewport | Inicio del botón principal | Desbordamiento horizontal |
|---|---:|---|
| 1440×1000 | 537 px | No |
| 390×844 | 383 px (termina a 436 px) | No |
| 320×780 | 378 px | No |
| 768×1024 | 442 px | No |

En 390×844 el botón anterior comenzaba a 1155 px. Las nuevas capturas son `output/playwright/ff-voting-{1440,390,320,768}.png`; vista completa: `ff-voting-desktop-full.png`. Las capturas anteriores `ff-voting-audit-*` corresponden exclusivamente al baseline.

El modal mide 800×896 px en 1440×1000 y ocupa 390×844 px en móvil. Título/cierre y pie permanecen visibles; el iframe recibe la altura restante y su propio desplazamiento. Las pruebas con contenido sintético comprobaron una única carga, estado preservado al reabrir, los cuatro accesos, cierre por botón/overlay/Escape en documento principal, Tab/Shift+Tab, fondo `inert`, restauración de foco/scroll, menú móvil abierto y movimiento reducido. No se afirmó éxito de carga ni voto.

Sin JavaScript se conservaron enlaces normales a la URL corta; el botón móvil sigue visible. Se verificaron también fallo de iframe, enlace alternativo y clic modificado con redirección al formulario exacto.

La sesión limpia con iframe sintético confirmó **0 errores JavaScript y 0 advertencias/errores de consola** de la aplicación, FAQ operativa, cierre del menú con Escape y navegación secundaria a `#categorias`. Los errores de red de Google se documentaron en una prueba separada.

La ampliación se comprobó con **zoom CSS 200 % como aproximación**, y reflow equivalente en viewport 720×500; no es una medición del zoom del navegador ni de un dispositivo físico. La primera inspección detectó solapamiento de navegación/logos con zoom CSS; `flex-wrap` en el encabezado permite una segunda fila y la revisión posterior confirmó separación. En el viewport reducido el modal mide 688×450 px. Se alineó además el cierre al extremo derecho.

### Dependencia Google y evidencia

La prueba real anónima del embed respondió **HTTP 401** y mostró «Accede a tu cuenta de Google». «Abrir en Google» abrió `People Choice Award` con el diálogo de acceso obligatorio. Capturas: `ff-voting-modal-google-desktop.png`, `ff-voting-modal-google-mobile.png` y `ff-voting-google-external-anonymous.png`. La votación con sesión corresponde al propietario; no se inició sesión ni se enviaron respuestas.

Los archivos `ff-voting-modal-synthetic-*` usan un documento de QA identificado como sintético; no representan el contenido de Google. Los scripts y logs de navegador están en `output/playwright/ff-voting-*.{js,log}`, ignorados y fuera del release. Para evitar carreras con la suite que recrea la misma base, durante el QA se conservó la respuesta HTML pública obtenida del servidor local antes del gate y se sirvió con sus headers y los assets Vite vigentes. El smoke HTTP final se realiza nuevamente sobre la ruta real, sin esa captura.

**Smoke final ejecutado:** después del gate y del build desde lock, la ruta real `/` respondió 200 y conservó los cuatro accesos, el iframe diferido y el CTA a 383–436 px en móvil, sin overflow. `/login` respondió 200 y excluyó el módulo/modal y el permiso CSP de Google. Ver `ff-voting-final-smoke.log`. La suite final terminó 247 passed / 1 skipped / 4296 assertions; el gate global conserva únicamente el fallo Pint preexistente descrito en informe 34.

## Adenda Hermosillo sin Barreras — 2026-08-06

**Rama:** `codex/category-hermosillo-sin-barreras`
**Base productiva:** `26256e32cb7dcc38e94d8d46737a4c3b81e5c8a9`
**Entorno:** servidor Laravel local con MySQL desechable `flowerflow_testing`; datos exclusivamente sintéticos.
**Estado:** `PASSED` local; UAT del propietario y despliegue permanecen pendientes.

### Cobertura visual y funcional

| Superficie | Evidencia local | Resultado |
|---|---|---|
| Landing pública | 1440 px: cuatro columnas; 768 px: dos; 360 px: una | Cuatro categorías ordenadas, icono de accesibilidad, destaque por slug y sin overflow |
| Participante: dashboard | 1440 px | Máximo cuatro, cuatro categorías y premio visibles sin colisiones |
| Participante: crear propuesta | 1440 px: dos columnas; 360 px: una | Nueva categoría seleccionable, icono correcto y sin overflow |
| Participante: listado y detalle | 360 px | Borrador sintético de la nueva categoría visible, buscable y consultable |
| Administrador: dashboard | 1440 px | Distribución con las cuatro categorías, incluida la nueva con conteo cero |
| Administrador: filtro y detalle | 1440 px | `?category=hermosillo-sin-barreras` filtra y el detalle conserva la relación |

La navegación por teclado mostró foco visible de 3 px en el enlace de salto y `Enter` desplazó correctamente a `#contenido`. La comprobación de reflow al 200 % se ejecutó mediante zoom CSS controlado como aproximación automatizable; no apareció desplazamiento horizontal. Las dos sesiones finales, participante y administrador, terminaron con cero errores y cero advertencias de consola. La descarga administrativa y los estados crear/editar/enviar se validaron además mediante pruebas Feature.

Las capturas locales se conservaron como artefactos ignorados en `output/playwright/hermosillo-sin-barreras/`; no contienen datos reales ni forman parte del release.

### Hallazgo corregido durante QA

La primera verificación detectó que el selector genérico `.ff-category-grid` permitía que la regla de dos columnas del formulario sobrescribiera las cuatro columnas de la landing. La regla del formulario se acotó al contexto `.ff-participant-submission-wizard-page`; la repetición del QA confirmó 4/2/1 en landing y 2/1 en el formulario. No quedó defecto visual abierto.

### Ajuste de alternancia visual — 2026-08-06

“Hermosillo sin Barreras” reutiliza el mismo formato `.is-featured` de “Hermosillo Florece”. La asignación se realiza por los dos slugs autorizados, no por posición, y produce la secuencia visual normal/destacada/normal/destacada sin modificar el CSS ni las demás superficies. Playwright confirmó ambos slugs destacados, cero overflow y cero mensajes de consola en 1440, 768 y 360 px; la inspección visual de las tres capturas no mostró colisiones o recortes.

## Baseline landing pública Flower Flow V2 — 2026-07-15

**Fecha:** 2026-07-15 (`America/Hermosillo`)  
**Rama:** `codex/ui-public-landing-v2`  
**Ruta:** `/`  
**Estado:** cerrado por aceptación manual del usuario responsable; gates automatizados verdes. No se recibió evidencia binaria para versionar.

## Referencias y estados

- Escritorio: `mejora_flowerflow_escritoriov2.png`, consultada desde Downloads y no copiada al repositorio.
- Móvil: `mejora_flowerflow_movilv2.png`, consultada desde Downloads y no copiada al repositorio.
- Estado visual controlado: registro y recepción activos para comparar los CTA de las referencias.
- Estados funcionales adicionales: registro desactivado, recepción desactivada, público desactivado y competencia ausente cubiertos en Feature tests.

## Comparación de intención

| Área | Referencia | Implementación | Resultado actual |
|---|---|---|---|
| Header | Dos marcas, navegación compacta, CTA naranja; menú móvil | Header exclusivo de `/`, ambos logos, 4 anchors, login, CTA por flag y menú accesible | Implementado |
| Hero | Atardecer, titular dominante, dispositivo/premio, CTA | Contenedor naranja cálido, panorama derivado del cartel, título HTML, dispositivo derivado, cierre y estados reales | Implementado |
| Categorías | Tres tarjetas escritorio; filas compactas móvil | Grid 3 columnas y filas con ícono/texto/flecha bajo 768 px | Implementado |
| Proceso | Cuatro pasos 2×2 | Lista ordenada 2×2, con numeración e íconos existentes | Implementado |
| Requisitos | Seis elementos 3×2 | Grid 3×2; baja a 2 y 1 columna por contenido | Implementado |
| Premio | Bloque oscuro/naranja y dispositivo | Tarjeta carbón con visual autorizado, reglas y máximos en HTML | Implementado |
| Documentos/FAQ | Dos columnas y acordeón | PDF descargables y acordeón Bootstrap con ARIA explícito | Implementado |
| CTA/footer | Franja naranja y footer carbón | CTA por flag y footer con marcas, contacto y legales | Implementado |

## Gates ejecutados

- `PublicLandingTest`: 6 pruebas, 61 aserciones, verde.
- Vite: Node 22.23.1, Yarn 1.22.22, build verde y manifest generado.
- HTML/Blade: IDs de anchors, `aria-controls`, `aria-labelledby`, `aria-expanded`, un `h1` y texto legal autoritativo cubiertos por pruebas/revisión.
- Recursos: ambos WebP son locales, tienen dimensiones declaradas y hashes registrados; no hay URL remota ni asset Apple descargado.
- Regresión: `/login` conserva `ff-navbar` y no recibe el nuevo header/CTA.

## Reconciliación del gate visual

El runtime de navegador integrado de Codex no pudo inicializarse (`setupAtlasRuntime` encontró una colisión no recuperable en su global `process`) después del bootstrap y reinicio prescritos. Este bloqueo histórico se conserva como evidencia de lo ocurrido.

El 2026-07-16 el usuario responsable confirmó que realizó y aceptó las validaciones visuales y responsive del área participante. Esa aceptación posterior cerró el milestone previo, incluidos los estados y tamaños documentados en `design-qa.md`, sin inventar capturas, resultados de consola ni comandos ejecutados por Codex. No se reportaron defectos P0, P1 o P2 pendientes.

Resultado final: `PASSED` por UAT manual aceptada.
