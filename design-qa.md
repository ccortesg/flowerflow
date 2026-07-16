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

## Evidencia de cierre

- Implementaciones locales revisadas: acceso, perfil, listado, asistente de nueva propuesta e inicio participante.
- Estados cubiertos: perfil completo/incompleto; sin propuestas, borrador, enviadas y máximo alcanzado; recepción habilitada/deshabilitada; competencia activa/ausente; pasos 1–4, selección individual/equipo, Quill, archivos, enlaces, revisión, errores y navegación móvil.
- Responsive cubierto: `/inicio` en 320×568, 360×800, 390×844, 412×915, 768×1024, 1024×768, 1280×800, 1366×768, 1440×900 y 1920×1080; acceso, perfil, propuestas y asistente en 390×844, 768×1024, 1366×768 y 1440×900.
- Interacciones cubiertas: teclado, foco visible, offcanvas, formularios, retroceso entre pasos, carga/remoción de archivos y envío.
- Calidad visual cubierta: composición, jerarquía, tipografía, espaciado, colores, activos, reflow, zoom, overflow horizontal y `prefers-reduced-motion`.
- Calidad técnica visible cubierta: consola, solicitudes inesperadas, assets, enlaces y controles interactivos.
- Fuente de aceptación: confirmación directa del usuario responsable el 2026-07-16, quien indicó haber realizado todas las validaciones para el cierre de QA visual y responsive.
- Evidencia binaria: no se recibió ni se incorporó al repositorio; las capturas permanecen fuera del control de versiones conforme a las restricciones del milestone.

Los intentos automatizados anteriores quedaron bloqueados por el runtime del navegador integrado. El cierre actual se registra como UAT manual realizada y aceptada por el usuario, sin atribuir a Codex capturas o resultados de navegador no ejecutados en esta sesión.

## Evidencia de comparación

### Vista completa

El usuario confirmó la comparación de las vistas completas contra las diez referencias y aceptó la composición, densidad, jerarquía y adaptación responsive del área participante. No se reportaron diferencias P0, P1 o P2 pendientes.

### Regiones enfocadas

El usuario confirmó la revisión del hero, las tres tarjetas de resumen, los siguientes pasos, la información importante, el menú lateral/móvil, el encabezado/stepper, las tarjetas de selección, el editor, el upload, la ayuda y las acciones. No comunicó defectos pendientes en esas regiones.

## Superficies de fidelidad

- **Tipografía:** jerarquía, peso óptico, wrapping y densidad aceptados en la validación manual.
- **Espaciado y ritmo:** breakpoints, grillas, apilamiento, márgenes, altura, overflow y alineación aceptados; en `/inicio` las tres tarjetas pasan a dos y una columna según el ancho.
- **Colores y tokens:** paleta naranja/crema/carbón, contraste funcional y balance visible aceptados.
- **Calidad de activos:** ambos logotipos autorizados, panorama e iconos Remix revisados en nitidez, escala y alineación.
- **Contenido:** textos en español de México, datos reales, legibilidad y cortes aceptados; se conservan las diferencias deliberadas frente a las referencias.

## Hallazgos

- [RESOLVED] El bloqueo de evidencia del navegador integrado se cerró mediante la validación manual completa confirmada por el usuario el 2026-07-16.
  - Alcance aceptado: `/inicio`, acceso, perfil, listado, creación/edición/revisión de propuesta y navegación participante de escritorio/móvil.
  - Resultado reportado: responsive, foco, interacción, consola, overflow y fidelidad visual validados; sin P0, P1 o P2 pendientes comunicados.
  - Limitación documental: no se proporcionaron capturas o reportes externos para conservar como evidencia; la autoridad del cierre es la aceptación explícita del usuario.

## Diferencias deliberadas de producto

- No se implementó el nombre, avatar ni campana ficticios de las referencias; se usa la persona autenticada y sólo funciones reales.
- En `/inicio` no se copió la fotografía de la referencia: se reutilizó el panorama autorizado del proyecto; tampoco se añadió el acceso ficticio a evaluación.
- El premio se corrigió conforme a la mecánica autorizada: un Apple iPad Pro por categoría, con máximo una propuesta ganadora por categoría; no se usó la laptop mostrada en la referencia.
- El menú participante se limita a Inicio, Mis propuestas, Nueva propuesta cuando realmente procede y Mi perfil. La página pública de documentos, sus PDF y la FAQ no se eliminaron.
- No se afirma autoguardado. La plataforma ofrece guardado explícito, estado de cambios locales y advertencia al abandonar.
- Los pasos 1–3 persisten en servidor; el paso 4 reutiliza finalización, aceptaciones, folio e idempotencia reales.
- Los documentos son opcionales para el borrador y obligatorios antes del envío final, conforme al contrato backend vigente.

## Lista de cierre de QA

- [x] Comparar cada referencia y su estado equivalente, incluidos los tamaños obligatorios de `/inicio`.
- [x] Revisar vistas completas y regiones críticas.
- [x] Probar radios, equipo, contadores, Quill, drag/drop, remoción, cuota, YouTube, retroceso y envío por teclado.
- [x] Revisar consola, overflow horizontal, foco visible, zoom y reduced motion.
- [x] Confirmar que no quedan hallazgos P0, P1 o P2 reportados.

## Historial de comparación

- 2026-07-16 01:40 MST — acceso/perfil/listado bloqueados: el paquete de navegador disponible entonces no expuso el cliente requerido; no se cambió de herramienta sin autorización.
- 2026-07-16 02:42 MST — nueva propuesta: referencias abiertas, servidor/cuenta sintética preparados y build disponible; el navegador integrado falló durante la inicialización antes de la captura. No hubo comparación visual ni iteración de corrección.
- 2026-07-16 07:41 MST — inicio participante: referencia abierta, servidor/cuenta sintética preparados, suite y build disponibles; el navegador integrado volvió a fallar durante la inicialización (`Cannot redefine property: process`). La cuenta se eliminó y el servidor exclusivo se detuvo. No hubo captura, comparación combinada, revisión de consola ni validación de los diez viewports.
- 2026-07-16 10:06 MST — cierre UAT: el usuario confirmó haber completado todas las validaciones visuales y responsive del área participante. Se marcan como aceptados los viewports, estados, interacciones, teclado, foco, zoom, reduced motion, consola y overflow; no se reportaron hallazgos P0/P1/P2. No se recibieron capturas para incorporar o referenciar en el repositorio.

final result: passed
