# Reglas de trabajo para Flower Flow

## Autoridad y alcance

- Antes de editar, leer este archivo, `.agent/PLANS.md`, el ExecPlan activo y los ADR aplicables.
- La documentación aprobada manda sobre supuestos. Registrar contradicciones como `PENDING`; no inventar reglas de negocio, textos legales, premios, fechas, licencias ni credenciales.
- Ejecutar un milestone por vez. No desplegar ni alterar producción sin aprobación expresa, backup verificado, UAT y rollback probado.
- Mantener el código en inglés y la interfaz/documentación operativa para usuarios en español.

## Autorización actual y límites

- La Fase 01 `public-submissions` fue aprobada expresamente el 2026-07-15 mediante `Prompt_Optimo_Codex_FlowerFlow_Fase_01_v2.md` y se ejecuta en `codex/phase-01-public-submissions`.
- La Fase 02A `admissibility-review` fue aprobada expresamente el 2026-07-16 y se ejecuta localmente en `codex/phase-02-admissibility-review`, sin stage, commit, push ni despliegue.
- Están autorizados en local/test: dependencias compatibles, migraciones revisadas, datos sintéticos, sitio público, auth/perfil participante, propuestas, archivos privados, aceptaciones, envío idempotente y panel privilegiado mínimo.
- En Fase 02A también están autorizados la revisión administrativa, aclaraciones, verificación privada de residencia, resolución de admisibilidad, auditoría, notificaciones transaccionales y sus interfaces/pruebas locales.
- Quedan fuera de esta fase: jueces, asignación, rúbrica, evaluación, selección/publicación de ganadores, comunicaciones masivas, ARCO completo, borrado automático de residencia y reportes avanzados.
- No desplegar ni modificar EC2, DNS, TLS, SMTP real o `administratec`; AWS sólo se documenta y prepara mediante preflight de solo lectura para una tarea posterior.
- `formatos/` conserva los PDF jurídicos v1.0 exactos y versionables; `imagen/` conserva originales autorizados; `_referencia/` es sólo lectura, local, ignorada y nunca forma parte del build o release.

## Seguridad y datos

- Nunca versionar secretos. La contraseña de MySQL local se recibe por canal seguro y vive sólo en `.env`, que está ignorado.
- No copiar PII ni documentos reales a desarrollo, pruebas, fixtures, capturas, logs o tickets.
- Separar comprobantes de residencia de anexos evaluables. Los jueces no pueden acceder a identidad ni comprobantes.
- Autorizar cada recurso con middleware, permisos y Policies; filtrar también consultas y descargas. Ocultar botones no es autorización.
- Usar almacenamiento privado, nombres internos aleatorios, allowlist de tipo/tamaño/MIME/firma y auditoría de accesos sensibles.
- Guardar fechas en UTC y presentar reglas de convocatoria en `America/Hermosillo`.

## Arquitectura y plantilla

- Conservar Laravel 12 y Materialize/Pixinvent 3.0.0 hasta que un ADR aprobado disponga otra cosa.
- Reutilizar `resources/views/layouts`, `config/custom.php` y menús JSON. Los overrides Flower Flow deben vivir fuera del core del proveedor y registrarse en `docs/template-overrides.md`.
- Controladores delgados; Form Requests, Policies, Actions/Services y enums respaldados para reglas; transacciones en cambios críticos; Events/Listeners/Jobs sólo donde reduzcan acoplamiento.
- No crear repositories genéricos, APIs, microservicios, SPA ni Redis sin necesidad aprobada.
- No añadir dependencias de producción sin actualizar `docs/dependency-register.md` y crear/actualizar un ADR.
- No editar `public/build` manualmente. Importar JS/CSS por página mediante Vite y retirar demos sólo después de verificar el build.
- No sobrescribir originales de `imagen/` ni `formatos/`; publicar copias o derivaciones reproducibles y verificar hashes.

## Calidad

- Cada milestone debe incluir pruebas de permisos negativos, estados, fecha/zona horaria, archivos y auditoría.
- Ejecutar los comandos reales definidos en el ExecPlan: tests, Pint, validación JSON, build y auditorías disponibles.
- Regla de detener y reparar: no marcar un milestone completo mientras falle una validación requerida.
- Verificar flujos críticos en navegador real, móvil y teclado antes de UAT.
- Mantener trazabilidad requisito -> historia -> implementación -> prueba en `docs/requirements-traceability.md`.

## Cambios y evidencia

- Preservar cambios ajenos y evitar ediciones solapadas. Dividir trabajo paralelo por archivos/módulos con propietario explícito.
- Actualizar el ExecPlan vivo: progreso, decisiones, hallazgos inesperados, evidencia y próximos pasos.
- Entregar lista exacta de archivos, comandos ejecutados, resultados, riesgos residuales y rollback.
