# Reglas de trabajo para Flower Flow

## Autoridad y alcance

- Antes de editar, leer este archivo, `.agent/PLANS.md`, el ExecPlan activo y los ADR aplicables.
- La documentación aprobada manda sobre supuestos. Registrar contradicciones como `PENDING`; no inventar reglas de negocio, textos legales, premios, fechas, licencias ni credenciales.
- Ejecutar un milestone por vez. No desplegar ni alterar producción sin aprobación expresa, backup verificado, UAT y rollback probado.
- Mantener el código en inglés y la interfaz/documentación operativa para usuarios en español.

## Límites de la fase 0

- Hasta que se apruebe `.agent/execplans/flowerflow-mvp.md`, sólo se permiten diagnóstico y documentación.
- No instalar o actualizar dependencias, crear migraciones, sembrar datos, modificar la base, implementar pantallas o cambiar infraestructura.
- El destino previsto es AWS EC2 con Ubuntu, en coexistencia aislada con `administratec`; GoDaddy está fuera de alcance.

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

