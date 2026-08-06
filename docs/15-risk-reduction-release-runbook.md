# Runbook de releases de reducción de riesgos Fases 01/02A

Fecha: 2026-08-06. Estado: **preparación local; no autoriza acceso, push, PR ni despliegue**.

## Invariantes

- Línea base productiva: `baff7892f886af3fd4e42132c686620f1ae76d91`.
- No desplegar durante la recepción, cuyo cierre configurado es 2026-08-15 23:59:59 `America/Hermosillo`.
- Fase 02A permanece apagada en producción.
- Ninguna release contiene migraciones, seeders o cambios de datos.
- No cambiar paquetes, módulos, MPM, PHP, MySQL, Node o Composer globales.
- Sólo puede cambiar el vhost Flower Flow y sólo después de `apache2ctl configtest` exitoso.
- Backup, restore, RPO y RTO pertenecen al propietario. Sin evidencia externa de preparación no se autoriza la ventana.

## Releases independientes

| Orden | Commit local | Contenido | Soak mínimo |
|---|---|---|---:|
| 1 | `84e34d1` | Guzzle 7.15.3 y transitivas indispensables | 24 h |
| 2 | `f530e6a` | Integridad de archivos, auditor read-only, estados, throttle, contratos y 2FA | 48 h antes de Release 3 |
| 3 | `7f660b0` | Grafo Vite mínimo, iconos, CSP Report-Only y HSTS gradual | CSP sólo se promueve tras smoke adicional |

Cada SHA debe pasar de forma independiente `scripts/quality_gate_local.sh`. No combinar releases para recuperar tiempo.

## Preflight redactado del propietario

Registrar sin contenidos secretos:

1. SHA, rama y estado del checkout activo.
2. Vhost/`DocumentRoot`, SAPI o pool PHP, módulos Apache y `configtest`.
3. CPU, memoria, disco e inodos disponibles.
4. Estado de Apache, MySQL, Supervisor/colas y cron.
5. Ubicación —no contenido— de `.env`, storage privado y logs.
6. `migrate:status` y evidencia de que Fase 02A está apagada.
7. Evidencia externa de preparación de recuperación.
8. Baseline HTTP y funcional de las siete aplicaciones compartidas.

## Conversión inicial a releases inmutables

Conservar `/var/www/flowerflow` intacto como rollback legado. Crear, con el usuario de despliegue y permisos mínimos:

```text
/var/www/flowerflow-releases/<timestamp>-<sha>
/var/www/flowerflow-shared/.env
/var/www/flowerflow-shared/storage
/var/www/flowerflow-current -> /var/www/flowerflow-releases/<timestamp>-<sha>
```

Preparar el release fuera del webroot activo, enlazar `.env` y `storage` compartidos y construir sin root. Verificar que el artefacto corresponde al SHA aprobado. Cambiar únicamente el `DocumentRoot` de Flower Flow a `/var/www/flowerflow-current/public`; ejecutar `apache2ctl configtest` y una recarga graceful, nunca restart.

## Smoke obligatorio

Antes y después del switch, comprobar Flower Flow y administratec, biru, festypass, pulso, sguniformes y sinc. Para Flower Flow, el propietario valida:

- landing, registro y ambos logins;
- acceso, dashboard y propuestas existentes de participante;
- dashboard, listado, detalle y descarga de administrador;
- 403/404 esperados con una cuenta sin permiso;
- consola sin errores, assets sin 404, logs sin excepciones y latencia sin degradación material.

El envío nuevo posterior al cierre sólo se prueba localmente; no reabrir la convocatoria para un smoke.

Release 3 inicia con la CSP estricta en Report-Only y HSTS `max-age=86400`. La CSP se promueve sólo con consola y logs limpios. HSTS puede elevarse a `15552000` después de siete días sin incidentes; nunca se añaden `includeSubDomains` o `preload` en este plan.

## Rollback

Activar rollback inmediato ante cualquier fallo en los nueve flujos protegidos, error 500, asset crítico ausente, descarga fallida o degradación de otra aplicación:

1. Reapuntar atómicamente `/var/www/flowerflow-current` al release anterior o al checkout legado conservado.
2. Ejecutar `apache2ctl configtest`.
3. Recargar Apache de forma graceful.
4. Limpiar únicamente las cachés del release restaurado.
5. Repetir el smoke de las siete aplicaciones y registrar la incidencia.

No revertir datos: estas releases son compatibles hacia atrás y no tienen migraciones. Si el rollback de código no restaura el servicio, detener la ventana y escalar al procedimiento externo de recuperación del propietario.
