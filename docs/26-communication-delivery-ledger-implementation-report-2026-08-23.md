# Reporte de implementación — bitácora de comunicaciones — 2026-08-23

## Resultado

**GO LOCAL/TEST.** El módulo administrativo `Notificaciones` quedó implementado sobre el baseline `e8101ad565e96c0d6113639874dde74704c7ab18` de `codex/submission-deadline-extension`. No hubo stage, commit, push, despliegue, acceso productivo ni SMTP real.

El flag `FLOWERFLOW_COMMUNICATION_LEDGER_ENABLED` nace apagado. Con el flag activo, las nueve familias transaccionales existentes crean una entrega e intento correlacionados y el worker central determina `queued`, `processing`, `sent`, `failed`, `unknown` o `cancelled`. En interfaz, `sent` se presenta exclusivamente como **Aceptado por el servidor de correo**.

## Baseline y guard

- `pwd` y Git toplevel: `/home/ccortesg/workspace/flowerflow`.
- Rama: `codex/submission-deadline-extension`.
- HEAD, upstream y merge-base iniciales: `e8101ad565e96c0d6113639874dde74704c7ab18`.
- Árbol inicial: limpio.
- Guard final, sin imprimir secretos: `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_DATABASE=flowerflow_testing`, `DB_USERNAME=flowerflow_testing_user` y `SELECT DATABASE()=flowerflow_testing`.
- Pruebas dirigidas previas: 19 pruebas y 217 aserciones verdes.

## Implementación

### Persistencia y seguridad

- `communication_deliveries` conserva ULID, tipo/variante, versión, evento e idempotencia opacos, referencia técnica, máscara, HMAC del destinatario, dirección/contexto cifrados, estado, cola, conteo, versión optimista, códigos redactados y timestamps UTC.
- `communication_delivery_attempts` conserva cada intento automático o administrativo, actor, razón cifrada, cola, UUID técnico, estado, código redactado y reconocimiento del riesgo de duplicado.
- Los modelos usan `guarded`, prohíben delete y las mutaciones operativas se realizan dentro de transacciones con bloqueos.
- La migración es aditiva, añade dos permisos sólo al rol exacto `admin` y su `down()` se niega cuando existe evidencia.
- La dirección y el contexto se purgan al llegar a `sent|cancelled`; el contexto fallido/desconocido vence a los 90 días configurables.

### Envío y recuperación

- `ResilientMailDispatcher` conserva la ruta previa cuando el flag está apagado y crea el outbox cuando está activo.
- `DeliverCommunication` es cifrado, post-commit y recibe únicamente el ID interno. El registro allowlist reconstruye y revalida las nueve familias actuales.
- Una recuperación administrativa cancela intentos en cola sustituidos, incrementa `lock_version`, crea un solo intento nuevo y despacha a `high`; nunca envía SMTP dentro del request ni modifica `jobs` directamente.
- `unknown` exige reconocimiento explícito del posible duplicado. `sent|cancelled` no admiten acción.
- Los recordatorios mantienen sincronizado su estado de dominio con la entrega central.

### Panel y operación

- El menú muestra `Notificaciones` después de `Paquetes ciegos` y antes de `Cuenta y seguridad` sólo a un `admin` exacto con permiso y flag activo.
- Rutas: lista, detalle, confirmación y POST de procesamiento individual bajo CSRF, throttle, contraseña reciente, Policy, permiso y control de concurrencia.
- La lista filtra por estado, tipo, fecha y atención requerida, pagina 25 filas y nunca muestra correo completo, cuerpo, tokens, URLs, contenido de propuesta ni excepciones.
- Se añadieron backfill de recordatorios dry-run por defecto, diagnóstico read-only, reconciliación de `processing` estancado a `unknown` y purga de contexto.
- El scheduler ejecuta reconciliación cada cinco minutos y purga cada hora. El worker existente `--queue=high,exports,default,low` es suficiente.

## Migración y rollback

La secuencia forward/rollback/forward se ejecutó bajo el guard exacto y terminó verde. El primer intento detectó que un nombre implícito de FK excedía los 64 caracteres de MySQL; se comprobó que las tablas parciales de testing estaban vacías, se retiraron sólo esas tablas y se repitió con un nombre explícito corto. También se probó que `down()` funciona sin evidencia y falla cerrado cuando existe evidencia sintética.

El rollback operativo es:

```dotenv
FLOWERFLOW_COMMUNICATION_LEDGER_ENABLED=false
```

No deben borrarse deliveries, intentos o auditoría para revertir. Producción requiere una autorización independiente.

## Pruebas y gates reales

- Pruebas dirigidas finales: 16 pruebas, 159 aserciones.
- Suite integral: 191 pruebas, 2,255 aserciones, 586.22 s.
- `vendor/bin/pint --test`: verde.
- `composer validate --strict`: verde.
- `composer check-platform-reqs`: verde.
- `composer audit`: cero advisories.
- `yarn audit`: conserva un advisory **low** de Quill, sin parche disponible; 1 vulnerabilidad baja entre 100 paquetes. No fue introducido por este milestone y el contenido continúa sanitizándose en servidor.
- `scripts/build_frontend_production.sh`: verde; 784 módulos y manifest Vite generado correctamente.
- `php artisan route:list --except-vendor`: 84 rutas; las cuatro rutas de notificaciones están registradas.
- `php artisan schedule:list`: reconciliación cada cinco minutos y purga horaria presentes.
- `php artisan migrate:status`: 21 migraciones `Ran`.
- JSON: `composer.json`, `package.json` y `resources/menu/verticalMenu.json` válidos.
- Enlaces Markdown locales: 12 destinos comprobados, cero rotos.
- `git diff --check`, secretos de alta confianza, URLs sensibles, PII y metadata de logs: verdes. Los únicos correos literales nuevos son dominios sintéticos `example.test` en pruebas.
- Diagnóstico final de testing: cero deliveries queued/processing/sent/failed/unknown/cancelled y cero registros estancados después de la suite integral.

## UAT Firefox local

Se ejecutó con mailer fake y datos sintéticos en 1440x900, 1024x768 y 390x844:

- orden y visibilidad del menú por rol/permiso/flag;
- lista, filtros, indicador de cola estancada, detalle y línea de tiempo;
- procesamiento de `queued`, reintento de `failed` y advertencia/confirmación adicional de `unknown`;
- un reintento real en cola `high`, un único nuevo intento y transición a aceptación por transporte;
- resumen único de errores, mensajes por campo y foco restaurado;
- teclado, zoom, reflow en tarjetas para anchos menores a 1200 px y acciones visibles;
- consola: cero errores y cero warnings.

Los casos 403/404/IDOR, multirol, permiso ausente, flag apagado, doble acción y `lock_version` obsoleto se cubrieron además mediante pruebas automatizadas.

## Archivos principales

- Modelo/migración: `app/Models/CommunicationDelivery.php`, `app/Models/CommunicationDeliveryAttempt.php`, `database/migrations/2026_08_23_120000_create_communication_delivery_ledger.php`.
- Ejecución: `app/Services/ResilientMailDispatcher.php`, `app/Services/CommunicationMessageRegistry.php`, `app/Jobs/DeliverCommunication.php`, `app/Actions/ForceCommunicationDelivery.php`.
- Panel: `app/Http/Controllers/Panel/CommunicationDeliveryController.php`, Request, Policy, middleware, rutas y `resources/views/panel/communication-deliveries/`.
- Operación: cuatro comandos `flowerflow:communications-*`, `routes/console.php`, `.env.example` y `config/flowerflow.php`.
- Pruebas: `tests/Feature/CommunicationDeliveryLedgerTest.php`.
- Diseño/documentación: ADR-0009, ExecPlan vivo y documentos de modelo, seguridad, UX, QA, riesgos, operación, producto y trazabilidad.

## Riesgos residuales

- SMTP no prueba entrega, apertura ni rebote; webhooks de proveedor continúan fuera de alcance.
- `unknown` puede representar un correo ya aceptado y su reenvío puede duplicarlo; por eso requiere decisión individual documentada.
- La metadata mínima no tiene purga automática hasta aprobar una política general de retención.
- El advisory bajo sin parche de Quill permanece visible.
- Migración, cache, scheduler, workers, smoke SMTP y UAT productivos están **NO VERIFICADOS / NO AUTORIZADOS**.
