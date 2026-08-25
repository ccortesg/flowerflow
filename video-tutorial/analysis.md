# Análisis del módulo “Mis asignaciones”

## Alcance inspeccionado

El tutorial cubre únicamente el espacio autenticado del rol exacto **Juez** y el flujo vigente para evaluar una propuesta sintética. La revisión se realizó contra código y documentación local del milestone M8A. El navegador integrado de Codex no expuso un backend utilizable en esta sesión; la inspección visual se ejecuta con Chromium visible de Playwright contra `127.0.0.1`.

## Flujo observado

1. **Mis asignaciones** lista las asignaciones propias y coloca primero las vigentes con vencimiento más cercano.
2. **Inicio de evaluación** presenta categoría, plazo y estado. Abrir este GET no crea el borrador.
3. **Iniciar evaluación** crea explícitamente el agregado y redirige a **Proyecto asignado**.
4. **Proyecto asignado** muestra exclusivamente el paquete ciego inmutable: categoría, modalidad, título, resumen, descripción, enlaces y anexos neutros.
5. **Evaluación** captura cuatro criterios de la rúbrica v2, comentarios opcionales y comentario general.
6. El autosave se ejecuta cada 30 segundos sólo cuando existen cambios. El servidor devuelve progreso, total y nueva versión de bloqueo.
7. **Revisar y enviar** guarda y abre una confirmación de sólo lectura.
8. **Enviar evaluación** exige confirmación expresa. La revisión queda sellada e inmutable.

## Rutas implicadas

| Método | Ruta | Propósito |
|---|---|---|
| GET | `/juez/asignaciones` | Listado propio |
| GET | `/juez/asignaciones/{asignacion}` | Paso 1, sin mutación |
| POST | `/juez/asignaciones/{asignacion}/evaluacion` | Inicio explícito |
| GET | `/juez/asignaciones/{asignacion}/proyecto` | Paso 2 |
| GET | `/juez/asignaciones/{asignacion}/proyecto.pdf` | Exportación PDF privada |
| GET | `/juez/asignaciones/{asignacion}/proyecto.xlsx` | Exportación Excel privada |
| GET | `/juez/asignaciones/{asignacion}/evaluacion` | Paso 3 |
| PATCH | `/juez/asignaciones/{asignacion}/evaluacion` | Guardado manual, autosave o revisión |
| GET | `/juez/asignaciones/{asignacion}/evaluacion/confirmar` | Paso 4, sin mutación |
| POST | `/juez/asignaciones/{asignacion}/evaluacion/enviar` | Sellado inmutable |

## Autorización e invariantes

- Autenticación, rol exacto `judge`, correo verificado y perfil de juez activo.
- Permisos `access judge workspace`, `manage own evaluation drafts` y `submit own evaluations`.
- Asignación propia en estado activo, sin conflicto, paquete ciego activo y rúbrica fijada válida.
- El plazo de la asignación debe coincidir exactamente con `2026-08-27 23:59:59 America/Hermosillo`.
- Los puntajes aceptan de 0 a 10 en pasos exactos de 0.5.
- El comentario por criterio es opcional y admite hasta 1,000 caracteres.
- El comentario general admite hasta 2,000 en borrador y exige entre 100 y 2,000 para enviar.
- Un `lock_version` obsoleto devuelve 409 y nunca sobrescribe el guardado más reciente.
- El navegador no es autoridad de pesos, componentes ni total.

## Estados de error relevantes

- 403 por rol, permiso, propietario o IDOR.
- 409 por borrador no iniciado, invariante divergente o concurrencia obsoleta.
- 422 por puntajes, comentarios o payload inválidos.
- Mutación rechazada por conflicto, cancelación, paquete inactivo o vencimiento.
- Tras el envío, la revisión se muestra sólo para lectura.

## Diferencia entre código e interfaz real

La plantilla intenta presentar “Sin iniciar” en la fila **Progreso**, pero la interfaz real dejó esa celda vacía durante la revisión en Chromium. El botón **Iniciar evaluación** sí representa correctamente el estado y se utiliza como señal visible en el tutorial. La causa observable está en la construcción Blade `@elseSin iniciar`, que no separa la directiva de su contenido. No se corrigió porque el alcance autorizado prohíbe modificar código funcional.

## Selectores estables

La interfaz actual ofrece contratos semánticos suficientes. La automatización usa encabezados, enlaces, botones, fieldsets y labels mediante `getByRole`, `getByLabel` y `getByText`. No fue necesario modificar vistas ni agregar `data-testid`.

## Privacidad del tutorial

El escenario usa el nombre de demostración “Carlos Cortés”, correo `example.test`, una propuesta ficticia y un identificador opaco sintético. El login ocurre fuera de la grabación. No se muestran contraseñas, cookies, tokens, participantes, teléfonos, domicilios ni datos reales.
