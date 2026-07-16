# Design QA: experiencia participante, inicio y asistente de nueva propuesta

## Fuente visual verdadera

### Acceso, perfil y listado

- `C:/Users/carlo/Downloads/1.inicio_sesion.png`
- `C:/Users/carlo/Downloads/2.mi_perfil.png`
- `C:/Users/carlo/Downloads/3.propuestas.png`

### Nueva propuesta

- `C:/Users/carlo/Downloads/4.nueva_propuesta_escritorio1.png`
- `C:/Users/carlo/Downloads/4.nueva_propuesta_escritorio2.png`
- `C:/Users/carlo/Downloads/4.nueva_propuesta_escritorio3.png`
- `C:/Users/carlo/Downloads/4.nueva_propuesta_movil1.png`
- `C:/Users/carlo/Downloads/4.nueva_propuesta_movil2.png`
- `C:/Users/carlo/Downloads/4.nueva_propuesta_movil3.png`

### Inicio participante

- `C:/Users/carlo/Downloads/panel_dashboard.png`

Las diez referencias fueron abiertas e inspeccionadas como fuente durante la implementación. No se copiaron como activos del producto.

## Implementación y evidencia requerida

- Implementaciones locales intentadas: `http://127.0.0.1:8127` para el asistente y `http://127.0.0.1:8126/inicio` para el inicio participante.
- Estado preparado: cuenta participante sintética, correo verificado, perfil completo y cero propuestas; recepción habilitada únicamente para el proceso local. Para `/inicio` también quedaron disponibles la convocatoria y categorías activas reales del entorno local.
- Captura de implementación: no disponible.
- Viewport: no disponible; la inicialización falló antes de abrir la página.
- Estados que debían capturarse: `/inicio` en 1536×864, 1440×900, 1366×768, 1280×800, 1024×768, 768×1024, 430×932, 390×844, 375×812 y 360×800; además de los pasos 1–4 en escritorio y móvil, selección individual/equipo, Quill, archivos/imágenes, enlaces, revisión, error y navegación móvil.
- Interacciones primarias: no ejecutadas en navegador.
- Consola: no disponible porque el runtime no creó una pestaña.

La cuenta sintética se eliminó y el servidor exclusivo de QA se detuvo después del intento.

## Evidencia de comparación

### Vista completa

No existe una captura renderizada de la implementación. Por ello no se produjo el compuesto referencia/implementación exigido para `/inicio` ni para los flujos anteriores, y no es válido calificar composición, densidad, jerarquía o responsive desde el código.

### Regiones enfocadas

No se compararon el hero, las tres tarjetas de resumen, los siguientes pasos, la información importante, el menú lateral/móvil, el encabezado/stepper, las tarjetas de selección, el editor, el upload, la ayuda ni las acciones. Esas regiones contienen tipografía pequeña, iconos, estados seleccionados y reorganización responsive que no pueden aprobarse con una inspección estática.

## Superficies de fidelidad

- **Tipografía:** jerarquía y límites están implementados, pero familia, peso óptico, wrapping y densidad permanecen pendientes de evidencia renderizada.
- **Espaciado y ritmo:** breakpoints, grillas y apilamiento existen en CSS; en `/inicio` las tres tarjetas pasan a dos y una columna según el ancho. Márgenes, altura total, overflow y alineación permanecen pendientes de captura.
- **Colores y tokens:** se reutiliza la paleta naranja/crema/carbón de la experiencia participante; contraste y balance visible permanecen pendientes.
- **Calidad de activos:** se usan ambos logotipos autorizados e iconos Remix existentes; nitidez, escala y alineación permanecen pendientes.
- **Contenido:** los textos están en español de México y las diferencias deliberadas son guardado explícito, datos reales y ausencia de campana ficticia; legibilidad y cortes permanecen pendientes.

## Hallazgos

- [P0] No hay evidencia de navegador para cerrar el rediseño.
  - Ubicación: `/inicio`, `/propuestas/nueva/crear`, pasos 1–3 de edición y revisión del borrador; también permanece pendiente el QA anterior de acceso/perfil/listado.
  - Evidencia: las referencias sí están disponibles y el servidor local respondió, pero el navegador integrado no pudo inicializar su runtime (`Cannot redefine property: process`) y no creó una pestaña ni captura.
  - Impacto: no es posible verificar overflow, responsive, foco, interacción, consola ni fidelidad visual con una comparación combinada.
  - Corrección: autorizar Playwright CLI local como navegador alternativo, capturar los mismos estados/viewports, crear comparaciones combinadas y corregir cualquier P0/P1/P2 antes de aprobar.

## Diferencias deliberadas de producto

- No se implementó el nombre, avatar ni campana ficticios de las referencias; se usa la persona autenticada y sólo funciones reales.
- En `/inicio` no se copió la fotografía de la referencia: se reutilizó el panorama autorizado del proyecto; tampoco se añadió el acceso ficticio a evaluación.
- El premio se corrigió conforme a la mecánica autorizada: un Apple iPad Pro por categoría, con máximo una propuesta ganadora por categoría; no se usó la laptop mostrada en la referencia.
- El menú participante se limita a Inicio, Mis propuestas, Nueva propuesta cuando realmente procede y Mi perfil. La página pública de documentos, sus PDF y la FAQ no se eliminaron.
- No se afirma autoguardado. La plataforma ofrece guardado explícito, estado de cambios locales y advertencia al abandonar.
- Los pasos 1–3 persisten en servidor; el paso 4 reutiliza finalización, aceptaciones, folio e idempotencia reales.
- Los documentos son opcionales para el borrador y obligatorios antes del envío final, conforme al contrato backend vigente.

## Lista de implementación pendiente para QA

1. Capturar cada referencia y su estado equivalente con el mismo viewport, incluidos los diez tamaños solicitados para `/inicio`.
2. Crear comparaciones combinadas de vista completa y regiones críticas.
3. Probar radios, equipo, contadores, Quill, drag/drop, remoción, cuota, YouTube, retroceso y envío por teclado.
4. Revisar consola, overflow horizontal, foco visible, zoom y reduced motion.
5. Corregir hallazgos P0/P1/P2 y repetir la comparación hasta aprobar.

## Historial de comparación

- 2026-07-16 01:40 MST — acceso/perfil/listado bloqueados: el paquete de navegador disponible entonces no expuso el cliente requerido; no se cambió de herramienta sin autorización.
- 2026-07-16 02:42 MST — nueva propuesta: referencias abiertas, servidor/cuenta sintética preparados y build disponible; el navegador integrado falló durante la inicialización antes de la captura. No hubo comparación visual ni iteración de corrección.
- 2026-07-16 07:41 MST — inicio participante: referencia abierta, servidor/cuenta sintética preparados, suite y build disponibles; el navegador integrado volvió a fallar durante la inicialización (`Cannot redefine property: process`). La cuenta se eliminó y el servidor exclusivo se detuvo. No hubo captura, comparación combinada, revisión de consola ni validación de los diez viewports.

final result: blocked
