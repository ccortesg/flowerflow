# ExecPlans de Flower Flow

Un ExecPlan es el documento ejecutable y vivo para trabajo de varias horas. Debe permitir que otra persona continúe sin depender del historial del chat.

## Cuándo es obligatorio

Usar un ExecPlan para cualquier cambio que cruce módulos, agregue tablas o dependencias, modifique autenticación/autorización, procese archivos o PII, cambie infraestructura, o requiera más de una sesión de trabajo.

## Estructura mínima

1. **Propósito y resultado observable.** Qué podrá hacer cada actor al terminar.
2. **Estado y alcance.** Incluido, excluido, `ASSUMPTION`, `PENDING` y decisiones aprobadas.
3. **Contexto del repositorio.** Rutas, convenciones, versiones, ADR y riesgos relevantes.
4. **Modelo y contratos.** Estados, invariantes, permisos, tablas, endpoints, archivos y eventos afectados.
5. **Plan por pasos.** Acciones pequeñas, ordenadas, con archivos probables y resultado verificable.
6. **Validación.** Comandos exactos, pruebas positivas/negativas, navegador, accesibilidad y evidencia esperada.
7. **Despliegue y rollback.** Precondiciones, backup, migraciones, workers, smoke tests y reversión.
8. **Registro vivo.** Progreso, decisiones, sorpresas, resultados y pendientes.

## Formato del registro vivo

Usar entradas fechadas en `America/Hermosillo`:

```text
- [ ] 2026-07-15 14:00 MST — Acción pendiente y criterio de cierre.
- [x] 2026-07-15 15:10 MST — Acción terminada; evidencia: comando/archivo/resultado.
- [!] 2026-07-15 15:30 MST — Hallazgo o bloqueo; impacto y siguiente acción.
```

No borrar entradas anteriores. Corregir decisiones mediante una nueva entrada y, cuando cambie arquitectura, actualizar o reemplazar el ADR con su estado correspondiente.

## Reglas de ejecución

- Leer `AGENTS.md`, este archivo, el ExecPlan y ADR antes de editar.
- Confirmar que el milestone fue aprobado y que no mezcla trabajo futuro.
- Establecer baseline de tests/build antes del primer cambio. Si el entorno no está instalable, resolver esa condición como milestone propio.
- No asumir que WSL2, staging y producción comparten credenciales, servicios o capacidades.
- Para cambios de esquema, documentar compatibilidad hacia atrás, volumen estimado, duración, backup y estrategia `down` o compensatoria.
- Para jobs/notificaciones, definir idempotencia, reintentos, timeout, fallos y observabilidad.
- Para archivos, incluir pruebas de acceso cruzado, MIME falso, tamaño, nombre hostil y descarga segura.
- Actualizar documentación de producto y operación en la misma iteración si cambia el comportamiento real.
- Mantener un solo paso `in_progress`; no declarar terminado por falta de tiempo.

## Criterio de finalización

Un ExecPlan se completa únicamente cuando el comportamiento observable cumple los criterios, las pruebas y el build pasan, la evidencia está registrada, la documentación está alineada, no quedan secretos/PII en el diff, el rollback es viable y los riesgos residuales fueron aceptados.

El ExecPlan inicial del MVP es `.agent/execplans/flowerflow-mvp.md`.
