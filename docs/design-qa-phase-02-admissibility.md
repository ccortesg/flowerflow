# QA de diseño — Fase 02A admisibilidad

**Fecha:** 2026-07-16 (`America/Hermosillo`)
**Rama:** `codex/phase-02-admissibility-review`
**Estado:** aprobado en entorno local desechable; no confundir con el QA manual ya aceptado de Fase 01.

## Superficies nuevas

- participante: detalle de propuesta, sección “Revisión de participación”;
- panel: `/panel/admisibilidad` y `/panel/admisibilidad/{review}`;
- estados de aclaración, carga privada, residencia, resolución y errores de archivo.

## Matriz ejecutada con datos sintéticos

| Actor/estado | Escritorio | Tableta | Móvil | Resultado |
|---|---:|---:|---:|---|
| Participante individual, expediente pendiente | — | 768×1024 | — | Aprobado |
| Representante de equipo, aclaración y residencia | 1440×1000 | 768×1024 | 390×844 | Aprobado |
| Reviewer, listado, detalle y resolución | 1440×1000 | 768×1024 | 390×844 | Aprobado |
| Administrador, listado y expediente | — | 768×1024 | — | Aprobado |
| Usuario autenticado sin permisos, ruta directa | — | 768×1024 | — | HTTP 403 correcto y mensaje en español |

## Criterios

- sin overflow horizontal; reflow y zoom 200%;
- labels, errores asociados, foco visible, orden de tabulación y confirmaciones comprensibles;
- separación visual de propuesta, identidad, residencia, notas internas y eventos;
- fechas en Hermosillo y estados/mensajes en español de México;
- carga inválida visible sin filtrar rutas o metadatos;
- consola sin errores y assets sin 404;
- participante nunca ve nota interna, identidad del reviewer ni información ajena;
- reviewer sin permiso y usuario cruzado reciben 403/404.

## Ejecución y evidencia

Se utilizó Playwright CLI sobre `http://127.0.0.1:8134`, conectado exclusivamente a `flowerflow_test` en MySQL local desechable. Los actores, folios, textos y dos archivos de prueba fueron sintéticos; no se usó PII ni documentación real.

Recorrido ejecutado:

1. El reviewer filtró `QA26-EQP-001`, abrió el expediente y verificó la separación visual entre versión enviada, aclaraciones, residencia, identidad, notas internas y eventos.
2. El representante intentó cargar un archivo `.exe`; el formulario conservó la página y mostró “El archivo debe ser PDF, JPEG, PNG o WebP.”, sin error 500.
3. El representante cargó un PDF sintético válido, recibió confirmación de almacenamiento privado y pudo descargarlo mediante la ruta autorizada.
4. El representante respondió la aclaración con texto y un adjunto privado. La respuesta quedó append-only y el estado cambió a “En revisión”.
5. El reviewer marcó el comprobante en revisión, verificó la residencia y admitió la propuesta mediante las dos confirmaciones explícitas. La bitácora mostró etiquetas en español.
6. Al volver como participante se mostró el motivo público y la leyenda de que admisión no significa ganar; la nota interna y la identidad del reviewer no aparecieron.
7. El participante individual vio su expediente “Pendiente de revisión” sin controles de carga, porque no existía una solicitud de residencia.
8. El administrador consultó el listado y observó el expediente admitido. La cuenta sin permisos recibió 403 y una explicación comprensible en español.

Accesibilidad y responsive:

- reflow legible en `1440×1000`, `768×1024` y `390×844`, sin traslape ni corte de formularios;
- simulación de zoom al 200% con `document.documentElement.style.zoom = '2'`: ancho visible `768`, ancho del documento `768`, sin desbordamiento horizontal;
- navegación con `Tab`: el control activo cumplió `:focus-visible` y mostró un `box-shadow` de 4 px y cambio de borde;
- encabezados, regiones, labels, estados, ayudas, confirmaciones y errores quedaron asociados en el árbol de accesibilidad;
- las pantallas autorizadas terminaron con 0 errores y 0 advertencias de consola;
- la navegación negativa a una respuesta 403 produjo únicamente el error de red esperado por el propio estatus HTTP, sin excepción JavaScript.

Hallazgos corregidos durante el QA:

- la CSP bloqueaba una clase agregada mediante script inline; la activación se trasladó al bundle Vite permitido y el reintento quedó con consola limpia;
- los nombres técnicos de eventos y la vista 403 predeterminada aparecían en inglés; ambos quedaron traducidos y protegidos por pruebas de regresión.

Las capturas, descarga, perfiles del navegador, base sintética, servidor local y archivos de prueba fueron temporales, se inspeccionaron fuera del repositorio y se eliminaron al cerrar el recorrido.

final result: APROBADO LOCAL
