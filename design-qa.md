# Design QA: experiencia participante y asistente de nueva propuesta

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

Las nueve referencias fueron abiertas e inspeccionadas como fuente durante la implementación. No se copiaron como activos del producto.

## Implementación y evidencia requerida

- Implementación local intentada: `http://127.0.0.1:8127`.
- Estado preparado: cuenta participante sintética, correo verificado, perfil completo y cero propuestas; recepción habilitada únicamente para el proceso local.
- Captura de implementación: no disponible.
- Viewport: no disponible; la inicialización falló antes de abrir la página.
- Estados que debían capturarse: pasos 1–4 en escritorio y móvil; selección individual/equipo; Quill; archivos/imágenes; enlaces; revisión; error y navegación móvil.
- Interacciones primarias: no ejecutadas en navegador.
- Consola: no disponible porque el runtime no creó una pestaña.

La cuenta sintética se eliminó y el servidor exclusivo de QA se detuvo después del intento.

## Evidencia de comparación

### Vista completa

No existe una captura renderizada de la implementación. Por ello no se produjo el compuesto referencia/implementación exigido y no es válido calificar composición, densidad, jerarquía o responsive desde el código.

### Regiones enfocadas

No se compararon encabezado/stepper, tarjetas de selección, editor, upload, ayuda ni acciones. Esas regiones contienen tipografía pequeña, iconos, estados seleccionados y reorganización responsive que no pueden aprobarse con una inspección estática.

## Superficies de fidelidad

- **Tipografía:** jerarquía y límites están implementados, pero familia, peso óptico, wrapping y densidad permanecen pendientes de evidencia renderizada.
- **Espaciado y ritmo:** breakpoints, grillas y apilamiento existen en CSS; márgenes, altura total, overflow y alineación permanecen pendientes de captura.
- **Colores y tokens:** se reutiliza la paleta naranja/crema/carbón de la experiencia participante; contraste y balance visible permanecen pendientes.
- **Calidad de activos:** se usan ambos logotipos autorizados e iconos Remix existentes; nitidez, escala y alineación permanecen pendientes.
- **Contenido:** los textos están en español de México y las diferencias deliberadas son guardado explícito, datos reales y ausencia de campana ficticia; legibilidad y cortes permanecen pendientes.

## Hallazgos

- [P0] No hay evidencia de navegador para cerrar el rediseño.
  - Ubicación: `/propuestas/nueva/crear`, pasos 1–3 de edición y revisión del borrador; también permanece pendiente el QA anterior de acceso/perfil/listado.
  - Evidencia: las referencias sí están disponibles y el servidor local respondió, pero el navegador integrado no pudo inicializar su runtime (`Cannot redefine property: process`) y no creó una pestaña ni captura.
  - Impacto: no es posible verificar overflow, responsive, foco, interacción, consola ni fidelidad visual con una comparación combinada.
  - Corrección: autorizar Playwright CLI local como navegador alternativo, capturar los mismos estados/viewports, crear comparaciones combinadas y corregir cualquier P0/P1/P2 antes de aprobar.

## Diferencias deliberadas de producto

- No se implementó el nombre, avatar ni campana ficticios de las referencias; se usa la persona autenticada y sólo funciones reales.
- No se afirma autoguardado. La plataforma ofrece guardado explícito, estado de cambios locales y advertencia al abandonar.
- Los pasos 1–3 persisten en servidor; el paso 4 reutiliza finalización, aceptaciones, folio e idempotencia reales.
- Los documentos son opcionales para el borrador y obligatorios antes del envío final, conforme al contrato backend vigente.

## Lista de implementación pendiente para QA

1. Capturar cada referencia y su estado equivalente con el mismo viewport.
2. Crear comparaciones combinadas de vista completa y regiones críticas.
3. Probar radios, equipo, contadores, Quill, drag/drop, remoción, cuota, YouTube, retroceso y envío por teclado.
4. Revisar consola, overflow horizontal, foco visible, zoom y reduced motion.
5. Corregir hallazgos P0/P1/P2 y repetir la comparación hasta aprobar.

## Historial de comparación

- 2026-07-16 01:40 MST — acceso/perfil/listado bloqueados: el paquete de navegador disponible entonces no expuso el cliente requerido; no se cambió de herramienta sin autorización.
- 2026-07-16 02:42 MST — nueva propuesta: referencias abiertas, servidor/cuenta sintética preparados y build disponible; el navegador integrado falló durante la inicialización antes de la captura. No hubo comparación visual ni iteración de corrección.

final result: blocked
