# Desarrollo local Flower Flow sobre WSL2 y MySQL

Fecha de evidencia: `2026-07-15`  
Estado: `BASELINE VERIFICADO — FASE 0 SIN MIGRACIONES`

## 1. Nombre correcto del ambiente

`FlowerFlow` es el nombre lógico del ambiente de trabajo y `flowerflow` es el nombre de la base MySQL. La distribución WSL registrada en Windows no se llama `flowerflow`: su nombre real observado es `Ubuntu`.

Baseline confirmado:

| Componente | Valor observado |
|---|---|
| Plataforma | WSL2 |
| Distribución | Ubuntu 22.04.5 LTS (`WSL_DISTRO_NAME=Ubuntu`) |
| Host Linux | `laptop-asus` |
| Proyecto | `/mnt/c/wamp64/www/flowerflow` |
| PHP CLI | 8.3.31 |
| Extensión PHP | `pdo_mysql` disponible |
| MySQL | Community Server 8.0.46 para Ubuntu |
| Servicio MySQL | activo y habilitado en systemd |
| Listener | `127.0.0.1:3306`, sólo loopback |
| Base | `flowerflow` |
| Usuario local | `flowerflow_user@localhost` |
| Charset/collation | `utf8mb4` / `utf8mb4_0900_ai_ci` |
| Motor default | InnoDB |
| Estado de esquema | 0 tablas, 0 vistas, sin tabla `migrations` |

La frase recomendada para otros documentos es: **ambiente local Flower Flow sobre la distribución Ubuntu de WSL2**.

## 2. Alcance de fase 0

En fase 0 se permite diagnosticar y documentar. No se debe:

- ejecutar `php artisan migrate`, `migrate:fresh`, `db:wipe` o seeders;
- crear, alterar o borrar tablas;
- instalar o actualizar dependencias;
- cargar dumps o datos productivos;
- cambiar grants, bind address o servicios;
- usar la base local como staging o producción.

La base vacía es el estado esperado hasta que se apruebe el ExecPlan del primer milestone de implementación.

## 3. Política de credenciales

La contraseña del usuario local fue suministrada fuera del repositorio. Su valor:

- vive únicamente en `.env` local o en un mecanismo local de credenciales;
- no se escribe en `.env.example`, documentación, scripts, commits, capturas o tickets;
- no se pasa como `--password=<valor>` ni aparece en historial o lista de procesos;
- no se reutiliza en staging, producción, correo, AWS o Administratec;
- debe rotarse si alguna vez se expone.

`.env` está destinado a configuración local y debe continuar ignorado por Git. Verificar antes de guardar secretos:

```bash
git check-ignore -v .env
```

Si el comando no confirma que `.env` está ignorado, detenerse y corregir la política antes de crear el archivo.

## 4. Estado actual del repositorio

Al momento de la auditoría:

- no existe `.env`;
- `.env.example` conserva el default de Laravel con SQLite;
- `config/database.php` sí define una conexión MySQL compatible;
- PHP 8.3 puede conectar mediante PDO a la base local;
- no se ejecutó ninguna migración para producir esta evidencia.

La configuración de MySQL debe integrarse en `.env.example` sólo con placeholders; nunca con la contraseña real.

## 5. `.env` local propuesto

Crear este archivo únicamente cuando el milestone de entorno haya sido aprobado. El placeholder de contraseña significa “insertar localmente el valor recibido por canal seguro”; no debe conservarse literalmente como una credencial válida.

```dotenv
APP_NAME="Flower Flow"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://flowerflow.local

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_MX

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=flowerflow
DB_USERNAME=flowerflow_user
DB_PASSWORD=<valor-recibido-fuera-del-repositorio>
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_0900_ai_ci

SESSION_DRIVER=file
QUEUE_CONNECTION=sync
CACHE_STORE=file

MAIL_MAILER=log
```

Mientras la base siga vacía, `file/sync/file` evita depender de tablas de sesión, jobs y cache que todavía no existen. Después de aprobar y ejecutar las migraciones correspondientes, cada driver debe reevaluarse y documentarse.

`config/app.php` mantiene la aplicación en UTC. Las fechas de negocio se presentan con `America/Hermosillo` y se convierten de forma explícita; ver [ADR 0003](adr/0003-mysql-environments-and-time.md).

## 6. Comprobaciones seguras de WSL y MySQL

### 6.1 Confirmar la distribución

Desde WSL:

```bash
printf '%s\n' "$WSL_DISTRO_NAME"
cat /etc/os-release
uname -a
```

Desde PowerShell:

```powershell
wsl.exe --list --verbose
```

El resultado esperado identifica `Ubuntu` con versión WSL `2`.

### 6.2 Revisar el servicio sin modificarlo

```bash
systemctl is-active mysql
systemctl is-enabled mysql
systemctl status mysql --no-pager
mysql --version
ss -ltn '( sport = :3306 or sport = :33060 )'
```

El resultado esperado es MySQL activo y listeners sólo en `127.0.0.1`. No cambiar `bind-address` a `0.0.0.0` para facilitar desarrollo.

Si el servicio está detenido, `sudo systemctl start mysql` cambia estado y sólo debe ejecutarse con autorización del owner del ambiente.

### 6.3 Conectar sin exponer la contraseña

Uso interactivo seguro:

```bash
mysql \
  --protocol=TCP \
  --host=127.0.0.1 \
  --port=3306 \
  --user=flowerflow_user \
  --password \
  --database=flowerflow
```

`--password` sin valor obliga al cliente a solicitarlo sin mostrarlo. No usar `-p<contraseña>`.

Para un login-path local:

```bash
mysql_config_editor set \
  --login-path=flowerflow-local \
  --host=127.0.0.1 \
  --port=3306 \
  --user=flowerflow_user \
  --password

mysql --login-path=flowerflow-local --database=flowerflow
```

`mysql_config_editor` escribe `~/.mylogin.cnf`; es conveniencia local, no un vault ni sustituto de rotación. No es un secreto compartible y no debe copiarse al repositorio. Revisar permisos del archivo y eliminar el login-path al retirar el ambiente.

### 6.4 Consultas de diagnóstico de solo lectura

Dentro del cliente MySQL:

```sql
SELECT VERSION();
SELECT CURRENT_USER(), DATABASE();
SELECT @@hostname, @@port, @@bind_address, @@session.time_zone;

SELECT default_character_set_name, default_collation_name
FROM information_schema.schemata
WHERE schema_name = 'flowerflow';

SELECT table_type, COUNT(*)
FROM information_schema.tables
WHERE table_schema = 'flowerflow'
GROUP BY table_type;

SHOW GRANTS;
```

Estas consultas no crean ni modifican datos.

### 6.5 Verificación desde Laravel/PDO

Después de crear `.env` de forma aprobada:

```bash
php -r 'echo extension_loaded("pdo_mysql") ? "pdo_mysql=ok\n" : "pdo_mysql=missing\n";'
php artisan about
php artisan tinker --execute='dump(DB::selectOne("select database() as name, current_user() as account"));'
```

No imprimir `config('database.connections.mysql.password')`, `$_ENV`, `env()` ni el contenido completo de `.env`.

## 7. Privilegios observados y remediación futura

El usuario local autenticó correctamente y actualmente tiene:

- `ALL PRIVILEGES` sobre `flowerflow.*`;
- `WITH GRANT OPTION`.

Esto es funcional para un sandbox aislado, pero `WITH GRANT OPTION` es excesivo. No debe replicarse fuera del ambiente local.

Plan de mínimos privilegios:

1. en local, conservar temporalmente permisos de migración sólo durante los milestones que los requieran;
2. retirar `GRANT OPTION` después de confirmar quién administra el sandbox;
3. en staging/producción, separar usuario runtime y usuario migrator;
4. prohibir privilegios globales y administración de usuarios a la aplicación;
5. registrar cambios de grants sin incluir hashes o contraseñas.

No ejecutar `REVOKE` en fase 0: cambiar privilegios puede bloquear trabajo ajeno.

## 8. Primer uso después de aprobar implementación

Secuencia futura, no autorizada en fase 0:

1. confirmar `.env` ignorado y crear configuración local;
2. generar `APP_KEY` sólo para el ambiente local;
3. revisar todas las migraciones y su rollback;
4. tomar backup si la base deja de estar vacía;
5. ejecutar primero `php artisan migrate:status`;
6. ejecutar `php artisan migrate` únicamente con aprobación del milestone;
7. usar seeders sintéticos y deterministas;
8. correr tests y comprobar que no se cargó PII;
9. registrar evidencia y estado del esquema.

Comandos destructivos como `migrate:fresh`, `db:wipe`, `DROP DATABASE` o restaurar un dump requieren autorización explícita incluso en local.

## 9. Datos de prueba

Reglas obligatorias:

- usar nombres, correos, teléfonos, domicilios y documentos completamente sintéticos;
- no copiar comprobantes de residencia, proyectos, evaluaciones o anexos reales;
- usar archivos de prueba fabricados y sin metadata personal;
- no reutilizar exports, logs o backups de producción;
- no enviar correo a destinatarios reales;
- marcar visualmente el ambiente como local;
- limpiar fixtures temporales conforme al ExecPlan.

Un dato anonimizado de forma reversible sigue siendo dato personal y no es válido como fixture.

## 10. Zona horaria

El servidor MySQL local reportó `SYSTEM`; esto depende del sistema y no constituye una garantía portable.

Contrato propuesto:

- aplicación y timestamps persistidos: UTC;
- sesión MySQL: `+00:00` cuando se implemente `DB_TIMEZONE`;
- zona de negocio predeterminada: `America/Hermosillo`;
- cada convocatoria conserva su identificador IANA de zona;
- entradas administrativas se interpretan en la zona de la convocatoria y se convierten a UTC;
- salidas se convierten explícitamente para la persona usuaria;
- tests cubren segundos antes, en y después de apertura/cierre.

No usar abreviaturas `MST`, offsets fijos o la zona `SYSTEM` como regla de negocio.

## 11. Troubleshooting

### `SQLSTATE[HY000] [2002] Connection refused`

- confirmar `systemctl status mysql`;
- confirmar listener `127.0.0.1:3306`;
- confirmar que el comando se ejecuta dentro de WSL;
- no abrir MySQL a la red como solución rápida.

### `Access denied for user`

- confirmar usuario, host y login-path;
- introducir la contraseña de forma interactiva;
- no pegar la contraseña en el comando, chat o log;
- no cambiar grants sin autorización.

### `Unknown database 'flowerflow'`

La evidencia inicial confirma que existe. Si desaparece, detenerse: no recrearla automáticamente. Registrar quién modificó el ambiente y obtener aprobación.

### `Base vacía` o `migrations table not found`

Es el baseline confirmado de fase 0. No ejecutar migraciones hasta aprobar el ExecPlan.

### PHP no encuentra `pdo_mysql`

```bash
which php
php -v
php --ini
php -m | grep -i mysql
```

Usar el PHP de Ubuntu/WSL verificado. No mezclar inadvertidamente PHP de Windows/WAMP y PHP de WSL.

### Problemas de permisos o rendimiento bajo `/mnt/c`

El proyecto vive en un filesystem montado desde Windows. Diagnosticar propietario, permisos y latencia antes de ampliar permisos. Si se propone mover una copia de ejecución a ext4 de WSL, hacerlo como cambio de entorno separado y documentado.

## 12. Checklist local

- [x] Distro real identificada como Ubuntu sobre WSL2.
- [x] MySQL 8.0.46 activo y limitado a loopback.
- [x] Base y usuario local autenticados por CLI/PDO.
- [x] `utf8mb4`, collation e InnoDB verificados.
- [x] Base vacía y ausencia de migraciones registradas.
- [x] Grants amplios identificados como riesgo sandbox.
- [ ] `.env` local creado después de aprobación.
- [ ] `APP_KEY` local generado después de aprobación.
- [ ] Migraciones revisadas y autorizadas.
- [ ] Seeders sintéticos aprobados.
- [ ] Integración MySQL y límites temporales probados.
