# ExecPlan: contraseñas y correo transaccional resiliente

**Estado:** Completed

**Creado:** 2026-07-15 America/Hermosillo
**Milestone:** endurecimiento de autenticación y correo posterior a Fase 01

## Propósito y resultado observable

Permitir contraseñas de mínimo ocho caracteres sin retirar los requisitos de mayúscula, minúscula, número, símbolo y confirmación; mostrar esos requisitos en tiempo real en registro, restablecimiento y cambio de contraseña. Todos los correos de usuario deben estar en español de México, usar las marcas Flower Flow y Florece Hermosillo, enviarse mediante cola con timeout y reintentos, y nunca convertir una falla de programación SMTP/cola en un error HTTP 500.

## Alcance

Incluido:

- política backend única de ocho caracteres con complejidad existente;
- componente Blade y JavaScript progresivo para checklist, coincidencia y mostrar/ocultar contraseña;
- verificación de correo, recuperación de contraseña y acuse de propuesta;
- cola `database/default`, cuatro intentos totales y backoff 60/300/900 segundos;
- aviso no destructivo cuando el correo no puede programarse y reenvíos manuales existentes/nuevos;
- HTML responsive, texto plano, asunto y contenido en español de México con ambos logotipos;
- pruebas Feature/Unit con mail/notification fake y simulación de falla de dispatch.

Excluido:

- cambiar proveedor SMTP, credenciales, DNS, SPF, DKIM o DMARC;
- comunicaciones masivas, tracking, adjuntos o nuevas dependencias;
- desplegar o modificar directamente la EC2.

## Contexto y contratos

- Laravel 12.64 y Fortify 1.37 permanecen sin upgrade.
- El backend es autoritativo; JavaScript replica la regla para ayuda inmediata, pero no la sustituye.
- Una contraseña válida tiene ocho o más caracteres y al menos una mayúscula, una minúscula, un número y un símbolo.
- Los correos se programan después del commit en la conexión configurada por `FLOWERFLOW_MAIL_QUEUE_CONNECTION=database`, cola `default`.
- El worker de producción debe escuchar `default`; una falla SMTP se reintenta y finalmente queda en `failed_jobs`.
- Si ni siquiera puede crearse el job, la operación principal ya confirmada se conserva, se registra un evento técnico sin correo/PII y se presenta un aviso con mecanismo de reintento.
- Los enlaces de correo usan el host canónico y las plantillas no contienen adjuntos ni información sensible adicional.

## Plan de ejecución

1. Unificar política de contraseña en `Password::defaults` y comando administrativo.
2. Crear componente de campos de contraseña, estilos y validación progresiva; integrarlo en las tres pantallas que definen una contraseña.
3. Crear notificaciones en cola para verificación/restablecimiento y un despachador resiliente con estado request-scoped.
4. Endurecer el Mailable de acuse, agregar reenvío autorizado y respuestas Fortify sin 500.
5. Crear layout HTML/texto plano de marca y las tres plantillas en español.
6. Añadir pruebas de política, UI, contenido, cola, retry y falla de programación.
7. Ejecutar tests, Pint acotado, validación Composer, build y revisión de secretos/diff.

## Validación

```text
php artisan test
./vendor/bin/pint --test <archivos PHP modificados>
composer validate --strict
cmd.exe /d /c "corepack yarn build"
php artisan view:cache
php artisan route:list
git diff --check
```

Criterios adicionales:

- una contraseña `Aa1!aaaa` se acepta y una de siete caracteres se rechaza;
- checklist y confirmación cambian de estado accesible en pantalla;
- registro y recuperación no responden 500 si el dispatcher no puede encolar;
- los tres correos renderizan español, Flower Flow, Florece Hermosillo y alternativa de texto;
- el acuse se puede reprogramar sólo por la persona propietaria de una propuesta enviada;
- ninguna prueba contacta SMTP real.

## Despliegue y rollback

Despliegue: publicar código, conservar `QUEUE_CONNECTION=database`, añadir los valores `FLOWERFLOW_MAIL_*`, regenerar cachés, reiniciar únicamente `flowerflow-worker:*` y verificar cola/`failed_jobs`. No se requieren migraciones.

Rollback: volver al commit previo, regenerar cachés y reiniciar el worker. Los jobs serializados de las clases nuevas deben drenarse o eliminarse de forma explícita antes de retirar el código; no borrar `failed_jobs` sin revisión.

## Registro vivo

- [x] 2026-07-15 20:00 MST — Referencias visuales revisadas; se confirmó checklist requerido y correo predeterminado en inglés sin marca.
- [x] 2026-07-15 20:05 MST — Baseline: 15 pruebas/67 aserciones verdes; Pint global falla previamente por template heredado y `_referencia`.
- [x] 2026-07-15 20:35 MST — Política backend, componente visual reutilizable, respuestas Fortify resilientes, reenvío de acuse y tres correos HTML/texto en español implementados.
- [x] 2026-07-15 20:50 MST — Suite completa verde: 22 pruebas/117 aserciones; Pint acotado, Composer validate/audit, compilación Vite, caché de vistas, rutas, secretos y `git diff --check` verificados.
- [x] 2026-07-15 21:00 MST — Recorrido real en navegador sobre restablecimiento: cinco reglas, coincidencia, mostrar/ocultar y estados accesibles correctos; cero errores o advertencias de consola y sin enviar el formulario.

## Hallazgos

- `Password::defaults` y el comando de administrador tenían límites distintos si sólo se cambiaba la vista; ambos deben usar la misma regla.
- Verificación y reset heredaban las notificaciones Markdown predeterminadas de Laravel, causa del correo en inglés observado.
- `SubmissionReceived` ya estaba en cola, pero no declaraba timeout/backoff y la creación del job podía propagarse desde `afterCommit`.
- La pantalla de propuesta no tenía mecanismo para reprogramar el acuse.
- El navegador integrado no pudo inicializar su módulo local por una incompatibilidad del entorno; la misma validación visual se completó con Playwright CLI contra `127.0.0.1`, sin modificar datos.
