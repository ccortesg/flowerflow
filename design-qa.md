# Design QA: acceso y experiencia participante

## Evidencia requerida

- Fuente visual verdadera:
  - `C:/Users/carlo/Downloads/1.inicio_sesion.png`
  - `C:/Users/carlo/Downloads/2.mi_perfil.png`
  - `C:/Users/carlo/Downloads/3.propuestas.png`
- Implementación local: `http://127.0.0.1:8126`
- Captura de implementación: no disponible.
- Viewports pendientes: `360×800`, `390×844`, `768×1024`, `1024×768`, `1280×800`, `1366×768`, `1440×900` y `1920×1080`.
- Estados pendientes: login participante/panel, perfil resumen/edición, propuestas con datos, filtro, empty state y navegación móvil.

## Bloqueo

El navegador integrado no pudo inicializarse porque el paquete instalado no contiene `scripts/browser-client.mjs`, archivo requerido por el flujo autorizado del navegador. No se utilizó una herramienta alternativa sin autorización. Por ello no existe todavía una captura renderizada que pueda combinarse con cada referencia para una comparación visual válida.

## Superficies de fidelidad

- Tipografía: pendiente de comparación renderizada.
- Espaciado y ritmo: pendiente de comparación renderizada.
- Colores y tokens: pendiente de comparación renderizada.
- Calidad y fidelidad de activos: pendiente de comparación renderizada.
- Texto y contenido: validado mediante pruebas Feature, pero pendiente de revisión visual.

## Hallazgos

- [P0] Falta evidencia de navegador para cerrar el rediseño.
  - Ubicación: `/login`, `/perfil` y `/propuestas`.
  - Evidencia: las referencias sí están disponibles, pero no existe captura de la implementación.
  - Impacto: no es posible verificar overflow, responsive, foco, interacciones ni fidelidad visual.
  - Corrección: autorizar una alternativa de navegador, capturar los mismos viewports/estados y ejecutar la comparación combinada.

## Preguntas abiertas

- Confirmar autorización para usar Playwright CLI local como alternativa al navegador integrado.

## Lista de implementación

- Capturar login, perfil y propuestas en escritorio y móvil.
- Probar mostrar/ocultar contraseña, edición/cancelación de perfil, búsqueda/filtro y offcanvas.
- Revisar consola, overflow, teclado, foco y zoom.
- Crear comparaciones combinadas referencia/implementación fuera del repositorio.
- Corregir hallazgos P0/P1/P2 y repetir hasta aprobar.

## Historial de comparación

- 2026-07-16: comparación no iniciada; falta captura renderizada por bloqueo del navegador integrado.

final result: blocked
