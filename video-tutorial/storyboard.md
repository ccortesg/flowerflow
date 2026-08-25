# Storyboard — Mis asignaciones y evaluación

| Escena | Objetivo | URL o pantalla | Acción visible | Selector utilizado | Narración | Duración estimada | Resultado esperado | Validación posterior |
|---:|---|---|---|---|---|---:|---|---|
| 1 | Presentar el tutorial | Tarjeta institucional | Logotipos y título | HTML aislado del tutorial | “Flower Flow.” | 2.5 s | Identidad visual legible | Resolución 1920×1080 |
| 2 | Explicar el objetivo | **Mis asignaciones** | Entrada al módulo | `getByRole('heading', {name: 'Mis asignaciones'})` | Introducción del flujo | 27 s | Listado visible sin login | Sin credenciales en cuadro |
| 3 | Interpretar la tarjeta | **Mis asignaciones** | Resaltado de estado, plazo y progreso | `getByRole('article')` | Lectura de la asignación | 29 s | Una asignación sintética | Estado “Activa” y acción “Iniciar evaluación” |
| 4 | Distinguir evaluación y conflicto | **Inicio de evaluación** | Abrir detalle y resaltar acciones | `getByRole('link', {name: 'Iniciar evaluación'})` | Uso responsable del conflicto | 30 s | Detalle propio disponible | GET no crea evaluación |
| 5 | Crear el borrador | **Inicio de evaluación** | Clic en **Iniciar evaluación** | `getByRole('button', {name: 'Iniciar evaluación'})` | Inicio explícito y wizard | 24 s | Redirección al paso 2 | Existe una evaluación draft |
| 6 | Revisar el paquete ciego | **Proyecto asignado** | Recorrer contenido y exportaciones | `getByRole('heading', {name: 'Proyecto asignado'})` | Proyecto, anonimización y exports | 42 s | Contenido sintético sin PII | PDF/Excel sólo se mencionan |
| 7 | Comprender la rúbrica | **Evaluación** | Clic en **Ir a evaluación** | `getByRole('link', {name: /Ir a evaluación/})` | Pesos, rango y total servidor | 34 s | Cuatro fieldsets visibles | Total aún pendiente |
| 8 | Capturar la evaluación | **Evaluación** | Llenado de puntajes y comentarios | `getByRole('group', {name: <criterio>})` | Captura ficticia | 29 s | Formulario sucio y válido | Puntajes 8.5, 8, 7.5, 9 |
| 9 | Demostrar autosave | **Evaluación** | Espera verificable del ciclo real | `getByText('Guardado automáticamente', {exact: false})` | Guardado cada 30 segundos | 34 s | Total 82.50 del servidor | `lock_version` actualizado |
| 10 | Revisar antes de enviar | **Revisar y enviar** | Clic en **Revisar y enviar** | `getByRole('button', {name: 'Revisar y enviar'})` | Resumen persistido | 31 s | Paso 4 visible | Total y comentarios presentes |
| 11 | Confirmar el envío | **Revisar y enviar** | Casilla y clic en **Enviar evaluación** | `getByLabel('Confirmo que revisé…')` | Confirmación inmutable | 31 s | Envío aceptado una vez | Redirección a sólo lectura |
| 12 | Mostrar inmutabilidad | **Evaluación enviada** | Resaltado de estado y total | `getByText('La evaluación fue enviada.', {exact: false})` | Estado final | 28 s | Sin campos editables | Estado “Enviada” |
| 13 | Resumir y divulgar IA | Cierre y tarjeta final | Resumen, logotipos y divulgación | HTML aislado del tutorial | Cierre | 29 s | Mensaje final legible | Divulgación de voz IA |

Las duraciones definitivas se sustituyen en `timeline.json` después de medir cada WAV con `ffprobe`.
