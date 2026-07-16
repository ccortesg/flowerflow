# Despliegue en AWS EC2 con Ubuntu

> **Adenda Fase 01:** host canónico `app.flowerflow.com.mx`, `DocumentRoot=/var/www/flowerflow/current/public` (ruta final sujeta a inventario), panel `/panel`, SMTP externo con remitente `notificaciones@flowerflow.com.mx`. No se accedió ni modificó la EC2. El preflight de sólo lectura de este runbook sigue siendo el siguiente paso autorizado por separado; debe identificar Ubuntu, Apache/Nginx, PHP-FPM/extensiones, capacidad, aislamiento de Administratec, MySQL vs RDS, EBS vs S3 privado, secretos, worker/scheduler, staging, backup/restore, RPO/RTO, monitoreo, TLS/DNS y salida SMTP.

Fecha de evidencia: `2026-07-15`  
Estado: `RUNBOOK PROPUESTO — NO EJECUTADO`  
Decisión de destino: `ACCEPTED` por [ADR 0002](adr/0002-aws-ec2-ubuntu.md)  
Decisión de base productiva: `PROPOSED` por [ADR 0003](adr/0003-mysql-environments-and-time.md)

## 1. Propósito y límites

Este runbook define cómo preparar, validar, desplegar y revertir Flower Flow en la instancia AWS EC2 con Ubuntu donde ya opera Administratec. Reemplaza cualquier supuesto previo sobre GoDaddy, cPanel o hosting compartido.

Este documento no autoriza un despliegue. Durante la fase 0:

- no se ejecutan los comandos aquí descritos;
- no se accede ni modifica la EC2;
- no se crean usuarios, bases, vhosts, workers, cron, DNS o secretos;
- no se copian datos personales ni documentos reales;
- no se despliega sin aprobación expresa, backup verificado, UAT aprobada y rollback ensayado.

Los valores entre `<...>` son placeholders. No deben pegarse sin resolverlos y nunca deben sustituirse por secretos dentro de este archivo, tickets, logs o commits.

## 2. Evidencia disponible y límites de la evidencia

### 2.1 Hechos confirmados

- El destino solicitado es AWS EC2 con Ubuntu y debe coexistir con Administratec.
- El endpoint público declarado por Administratec respondió el `2026-07-15` sobre HTTPS, identificó Apache sobre Ubuntu y su resolución inversa dio una señal pública de infraestructura AWS.
- HTTP redirigió a HTTPS y la aplicación redirigió a su login.
- El certificado público observado era de Let's Encrypt y estaba vigente al momento de la revisión.
- El checkout local de Administratec declara como objetivo AWS, Ubuntu, Apache2, build en servidor y workers con Supervisor.
- El repositorio de Administratec no conserva la configuración productiva real de Apache, Supervisor, cron, CloudWatch o backups.

### 2.2 Hechos aún no confirmados en la EC2

- release exacto de Ubuntu y AMI;
- región, zona de disponibilidad, tipo de instancia y volúmenes EBS;
- usuario de despliegue, método SSH o Session Manager;
- versión y SAPI de PHP usados por Administratec;
- vhosts, módulos y política TLS reales;
- si MySQL está en la EC2, en RDS o en otro servicio;
- workers, cron, Redis, backups, alarmas y rotación de logs actuales;
- capacidad libre de CPU, memoria, disco e inodos;
- estrategia actual de releases y rollback de Administratec.

La señal pública de AWS no sustituye inventario con AWS API, SSM o SSH autorizado. Todo dato de la instancia debe capturarse antes de aprobar el despliegue.

## 3. Arquitectura objetivo e aislamiento de Administratec

### 3.1 Fronteras obligatorias

Flower Flow no debe compartir con Administratec ninguno de estos elementos:

- directorio de aplicación o symlink de release;
- archivo `.env`, `APP_KEY`, credenciales o parámetros de secretos;
- base, usuario de base o privilegios;
- vhost, dominio, certificado o archivos de log;
- pool PHP-FPM y socket, si se adopta FPM;
- programa de Supervisor;
- entrada de cron;
- prefijo de cache, nombre de cookie o dominio de sesión;
- directorio de archivos privados;
- bucket/prefix de backups o permisos IAM;
- staging, datos de prueba o usuarios de UAT.

La estructura propuesta es:

```text
/var/www/flowerflow/
├── current -> /var/www/flowerflow/releases/<timestamp>-<git-sha>
├── releases/
│   ├── <timestamp>-<git-sha>/
│   └── ...
└── shared/
    ├── .env
    └── storage/
        ├── app/private/
        ├── app/public/
        ├── framework/
        └── logs/
```

El `DocumentRoot` de Apache debe ser exclusivamente:

```text
/var/www/flowerflow/current/public
```

Nunca debe apuntar a la raíz del repositorio, `shared`, `storage/app/private` o una ruta de Administratec.

### 3.2 Usuarios y permisos propuestos

- usuario de aplicación/worker: `flowerflow`;
- usuario de despliegue: `<flowerflow-deploy-user>`;
- grupo de lectura del servidor web: `www-data`;
- releases de solo lectura para el runtime, excepto symlinks compartidos;
- escritura limitada a `shared/storage` y, cuando Laravel lo requiera durante el deploy, `bootstrap/cache`;
- `.env` con propietario/grupo mínimos y modo `0640` o más restrictivo según el usuario real de PHP;
- directorios privados sin permisos para otros usuarios;
- prohibido `chmod -R 777`.

La matriz final de propietario/grupo depende de si Apache usa mod_php o un pool PHP-FPM dedicado. Debe probarse con el usuario efectivo, no resolverse ampliando permisos.

### 3.3 Bootstrap de filesystem propuesto

Antes de crear recursos, comprobar que los nombres no existen y que no colisionan con Administratec:

```bash
getent passwd flowerflow
getent group flowerflow
getent group www-data
namei -l /var/www
```

En una ventana aprobada, el bootstrap debe:

1. crear una cuenta de servicio sin login interactivo para PHP/worker;
2. crear o asignar un usuario de despliegue individual/auditable;
3. crear `releases`, `shared/storage` y `/var/log/flowerflow`;
4. asignar grupo y permisos mínimos, con setgid/ACL sólo si están justificados;
5. enlazar `storage` de cada release a `shared/storage`;
6. comprobar con `namei -l` que Apache, PHP y worker acceden sólo a lo requerido;
7. registrar propietario, grupo y modo esperado de cada path.

No copiar propietarios o ACL de Administratec sin entender su usuario efectivo. Las cuentas de servicio no deben recibir sudo, llave SSH ni shell de login.

## 4. Gates de aprobación

El despliegue sólo puede comenzar cuando todos estos gates estén en verde:

| Gate | Evidencia requerida | Responsable |
|---|---|---|
| Infraestructura | Inventario EC2, capacidad, puertos y volúmenes | DevOps |
| Aislamiento | Mapa de recursos de Administratec y nombres exclusivos Flower Flow | DevOps |
| Seguridad | SG/UFW, IAM, secretos, TLS y almacenamiento privado revisados | Seguridad/DevOps |
| Datos | Motor productivo decidido, backup y restore drill exitoso | DBA/DevOps |
| Aplicación | Tests, Pint, build y auditorías aprobados | Desarrollo/QA |
| UAT | Flujos críticos y permisos negativos aprobados en staging | Producto/QA |
| Operación | Workers, scheduler, logs, alarmas y runbooks validados | Operaciones |
| Cambio | Ventana, responsables, rollback y comunicación aprobados | Change owner |

## 5. Preflight AWS, SSM y SSH

### 5.1 Preferencia de acceso

Se prefiere AWS Systems Manager Session Manager con IAM y auditoría sobre SSH público. Si SSH es indispensable:

- limitar el puerto 22 a VPN o IPs administrativas explícitas;
- usar llave individual o certificado SSH, nunca contraseña compartida;
- no reutilizar llaves de Administratec;
- deshabilitar login directo de `root`;
- registrar quién accedió, cuándo y con qué ticket de cambio.

### 5.2 Inventario AWS de solo lectura

Comandos propuestos, a ejecutar desde un perfil autorizado y sin imprimir secretos:

```bash
aws sts get-caller-identity --profile <audited-profile>
aws ec2 describe-instances \
  --instance-ids <instance-id> \
  --profile <audited-profile> \
  --query 'Reservations[].Instances[].{State:State.Name,Type:InstanceType,AZ:Placement.AvailabilityZone,PrivateIp:PrivateIpAddress,Subnet:SubnetId,Vpc:VpcId,Role:IamInstanceProfile.Arn,Volumes:BlockDeviceMappings[].Ebs.VolumeId}'
aws ec2 describe-security-groups \
  --group-ids <security-group-id> \
  --profile <audited-profile>
```

No guardar la salida completa si contiene identificadores que no deban circular. Registrar sólo la evidencia necesaria en el ticket de infraestructura protegido.

### 5.3 Inventario del host de solo lectura

Tras acceso autorizado:

```bash
hostnamectl
cat /etc/os-release
uname -r
timedatectl
uptime
free -h
df -hT
df -ih
lsblk -f
apache2 -v
apache2ctl -S
apache2ctl -M
php -v
php -m
php --ini
systemctl status php8.3-fpm --no-pager
composer --version
node --version
npm --version
mysql --version
systemctl status mysql --no-pager
systemctl status supervisor --no-pager
supervisorctl status
systemctl status cron --no-pager
systemctl status amazon-cloudwatch-agent --no-pager
ss -ltnp
```

No usar `cat`, `env`, `printenv`, `phpinfo()` ni dumps sobre archivos `.env`, credenciales, metadata IAM o configuración que pueda contener secretos.

### 5.4 Captura del precedente Administratec

Antes de instalar o habilitar nada:

```bash
apache2ctl -S
find /etc/apache2/sites-enabled -maxdepth 1 -type l -ls
find /etc/supervisor/conf.d -maxdepth 1 -type f -printf '%f\n'
crontab -l
sudo find /etc/cron.d -maxdepth 1 -type f -printf '%f\n'
systemctl list-units --type=service --state=running
```

Registrar sólo nombres, rutas, usuarios efectivos, puertos y relaciones. No copiar contenidos secretos. Cualquier cambio global de Apache, PHP, MySQL, Redis o paquetes debe demostrar que no interrumpe Administratec.

## 6. Capacidad y compatibilidad

Antes de compartir la instancia se debe medir:

- CPU y memoria base y pico de Administratec;
- espacio e inodos disponibles y crecimiento mensual;
- conexiones Apache/PHP/MySQL activas;
- procesos de queue y cron existentes;
- latencia y tamaño actual de backups;
- capacidad durante apertura, cierre y publicación de resultados de Flower Flow.

No se aprueba coexistencia si el pico combinado proyectado supera el margen operativo acordado. Como guardrail inicial, mantener al menos 30 % de CPU y memoria disponibles en pico y alertar al 80 % de disco; estos umbrales deben ajustarse con pruebas de carga.

## 7. Red: Security Groups, UFW y DNS

### 7.1 Security Group mínimo

| Puerto | Origen | Regla |
|---|---|---|
| 443/TCP | Internet | permitido para HTTPS público |
| 80/TCP | Internet | sólo redirección a HTTPS y ACME |
| 22/TCP | VPN/IP administrativa o cerrado con SSM | nunca abierto a `0.0.0.0/0` |
| 3306/TCP | SG de aplicación si se usa RDS | nunca público |
| 6379/TCP | sólo loopback o SG interno aprobado | nunca público |

UFW debe reflejar, no sustituir, el Security Group. No cerrar SSH hasta verificar SSM o una segunda sesión administrativa.

Comandos propuestos:

```bash
sudo ufw status verbose
sudo ufw allow 'Apache Full'
sudo ufw allow from <admin-cidr> to any port 22 proto tcp
sudo ufw enable
```

### 7.2 DNS

Definir antes de TLS:

- `<production-domain>` y decisión `www`/no `www`;
- `<staging-domain>` separado;
- zona Route 53 o proveedor DNS autorizado;
- TTL reducido durante la primera salida y normalizado después;
- registro de propietario y procedimiento de rollback DNS.

No reutilizar el dominio, certificado o cookie domain de Administratec.

## 8. IAM, SSM y gestión de secretos

La EC2 debe usar un Instance Profile de mínimo privilegio. Separar, cuando sea posible, roles de runtime, despliegue y backup.

Permisos candidatos, siempre acotados por ARN:

- `AmazonSSMManagedInstanceCore` o política mínima equivalente;
- escritura del agente CloudWatch sólo en log groups y métricas Flower Flow;
- lectura únicamente de parámetros/secretos Flower Flow;
- acceso únicamente al bucket y prefijo privados de Flower Flow;
- uso de una clave KMS específica cuando aplique.

No colocar `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, claves SSH o contraseñas en Git, AMI, user-data legible, historial shell o unidades systemd.

Secretos recomendados:

- AWS Secrets Manager para credenciales con rotación;
- SSM Parameter Store `SecureString` para parámetros protegidos de menor complejidad;
- KMS con acceso separado por ambiente;
- materialización temporal/controlada de `.env` durante deploy, con permisos restrictivos y sin imprimir contenido.

La aplicación no debe iniciar si falta un secreto obligatorio. Los logs deben informar el nombre de la variable faltante, nunca su valor.

## 9. PHP 8.3 y aislamiento de runtime

El runtime objetivo es PHP 8.3, compatible con el requisito `^8.2` del proyecto. La lista final de extensiones debe salir de Composer y pruebas, no de copiar paquetes de otro proyecto.

Paquetes candidatos a validar:

```text
php8.3-cli php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml
php8.3-curl php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath
```

Después de instalar en una ventana aprobada:

```bash
php -v
php -m
composer check-platform-reqs
```

### 9.1 Pool PHP-FPM propuesto

Usar un pool dedicado, por ejemplo `flowerflow`, con:

- socket propio `/run/php/php8.3-fpm-flowerflow.sock`;
- usuario de aplicación dedicado;
- `clear_env = yes` salvo variables explícitamente inyectadas;
- límites de procesos calculados con memoria real;
- logs y slow log separados;
- `opcache` habilitado y validado;
- límites de upload alineados con reglas del producto;
- `date.timezone = UTC`.

No deshabilitar mod_php, cambiar MPM ni reemplazar la versión global de PHP hasta demostrar que Administratec no depende de ellos. Habilitar `proxy_fcgi` para Flower Flow puede ser compatible, pero requiere `apache2ctl configtest` y prueba de ambos sitios.

## 10. Apache y TLS

### 10.1 Vhost propuesto

Ejemplo conceptual; completar dominio, socket y certificados:

```apache
<VirtualHost *:80>
    ServerName <production-domain>
    Redirect permanent / https://<production-domain>/
</VirtualHost>

<VirtualHost *:443>
    ServerName <production-domain>
    DocumentRoot /var/www/flowerflow/current/public

    <Directory /var/www/flowerflow/current/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch "\.php$">
        SetHandler "proxy:unix:/run/php/php8.3-fpm-flowerflow.sock|fcgi://localhost/"
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/flowerflow-error.log
    CustomLog ${APACHE_LOG_DIR}/flowerflow-access.log combined

    SSLEngine on
    SSLCertificateFile <managed-certificate-fullchain>
    SSLCertificateKeyFile <managed-certificate-key>

    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "DENY"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "camera=(), microphone=(), geolocation=()"
</VirtualHost>
```

Notas:

- revisar `AllowOverride All`; puede reemplazarse por reglas explícitas y `AllowOverride None` tras validar el `.htaccess` de Laravel;
- CSP debe comenzar en `Report-Only` y ajustarse a Materialize/Vite antes de bloquear;
- HSTS sólo se habilita después de validar HTTPS y todos los subdominios incluidos;
- ocultar versión detallada con `ServerTokens Prod` y `ServerSignature Off` sin afectar diagnóstico interno;
- dimensionar `LimitRequestBody`, timeouts y proxy según los límites aprobados de anexos;
- negar archivos ocultos y backups aunque estén fuera del webroot.

Validaciones obligatorias:

```bash
sudo apache2ctl configtest
sudo apache2ctl -S
sudo systemctl reload apache2
curl -I http://<production-domain>/
curl -I https://<production-domain>/
```

### 10.2 TLS con Let's Encrypt

Después de confirmar DNS y vhost HTTP:

```bash
sudo certbot certonly --webroot \
  --webroot-path /var/www/flowerflow/current/public \
  -d <production-domain>
sudo certbot renew --dry-run
systemctl list-timers | grep -i certbot
```

El modo `certonly --webroot` evita que Certbot reescriba vhosts de otros proyectos. Después se referencian las rutas administradas en el vhost Flower Flow y se valida `apache2ctl configtest`. No ejecutar emisión repetida durante pruebas. Usar el entorno staging de ACME cuando corresponda, proteger las llaves privadas y confirmar que la renovación recarga Apache de forma segura.

## 11. MySQL: EC2 local frente a RDS

La selección final está pendiente en ADR 0003.

### 11.1 Recomendación: Amazon RDS para MySQL

- MySQL 8 compatible y versión fijada después de pruebas;
- subredes privadas y sin IP pública;
- Security Group que acepte 3306 sólo desde la aplicación;
- cifrado KMS en reposo y TLS en tránsito;
- backups automáticos, point-in-time recovery y ventana de mantenimiento definida;
- monitoreo y alarmas de conexiones, almacenamiento, CPU, latencia y réplica;
- credenciales exclusivas por ambiente en Secrets Manager;
- parameter group con charset/collation y zona horaria coherentes;
- Multi-AZ según SLO y presupuesto aprobados.

### 11.2 Fallback: MySQL en la misma EC2

Sólo se acepta con riesgo explícito y controles compensatorios:

- usuario y esquema exclusivos de Flower Flow;
- bind a loopback; 3306 cerrado en SG/UFW;
- volumen EBS cifrado y capacidad/IOPS comprobadas;
- backups lógicos y/o snapshots enviados fuera de la instancia;
- binlogs y política de retención acordes al RPO;
- monitoreo y restore drill;
- prohibido compartir credenciales o esquema con Administratec.

La caída o pérdida de la EC2 afectaría aplicación y datos simultáneamente; por ello RDS es la opción recomendada.

### 11.3 Usuarios de base

Separar:

- usuario runtime con DML mínimo;
- usuario migrator usado sólo durante despliegues aprobados;
- usuario de backup/monitoring con permisos específicos;
- administrador gestionado fuera de la aplicación.

Ningún usuario de aplicación productivo debe tener `WITH GRANT OPTION`, privilegios globales o capacidad de administrar otros usuarios.

## 12. Composer, Node y build de release

La unidad de despliegue debe ser un release inmutable identificado por fecha y SHA de Git. Se prefiere artefacto construido en CI; si el build se hace en EC2, validar capacidad y retirar dependencias de build no requeridas por runtime.

Secuencia propuesta dentro de un nuevo release:

```bash
cd /var/www/flowerflow/releases/<timestamp>-<git-sha>
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
corepack yarn@1.22.22 install --frozen-lockfile
corepack yarn@1.22.22 build
composer check-platform-reqs --no-dev
test -f public/build/manifest.json
```

Después del build puede retirarse `node_modules` del artefacto runtime si no lo requiere ninguna tarea. No editar manualmente `public/build`.

Preparación Laravel, sólo con `.env` y `shared/storage` enlazados:

El archivo de entorno protegido debe conservar este contrato no secreto: `APP_LOCALE=es_MX`, `APP_FALLBACK_LOCALE=es_MX`, `APP_TIMEZONE=UTC`, `DB_TIMEZONE=+00:00` y `FLOWERFLOW_TIMEZONE=America/Hermosillo`. Así, la interfaz y las reglas de negocio usan español de México y horario de Hermosillo, mientras PHP/MySQL persisten instantes sin ambigüedad en UTC.

```bash
php artisan about
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Si `route:cache` falla por closures u otra causa, detener el despliegue y corregir; no ocultar el fallo.

## 13. Almacenamiento privado

Los comprobantes de residencia, anexos restringidos, exportaciones sensibles y evidencias no deben estar bajo `public` ni bajo un symlink público.

Controles mínimos:

- `shared/storage/app/private` o bucket S3 privado separado por ambiente;
- nombres internos aleatorios y nombre original sólo como metadato sanitizado;
- allowlist de tamaño, extensión, MIME y firma real;
- escaneo antimalware según la decisión técnica aprobada;
- descarga por controlador autorizado o URL temporal de duración mínima;
- auditoría de actor, recurso, fecha y resultado de acceso;
- cifrado EBS/S3 con KMS y bloqueo de acceso público;
- lifecycle alineado con retención y solicitudes de privacidad.

`php artisan storage:link` sólo puede exponer archivos explícitamente públicos. Nunca debe enlazar el directorio privado.

## 14. Supervisor para workers

Para el MVP se propone `QUEUE_CONNECTION=database` hasta aprobar Redis. Las migraciones de `jobs`, `job_batches` y `failed_jobs` deben existir antes de iniciar el worker.

Configuración conceptual:

```ini
[program:flowerflow-worker]
process_name=%(program_name)s_%(process_num)02d
directory=/var/www/flowerflow/current
command=/usr/bin/php /var/www/flowerflow/current/artisan queue:work --queue=high,default,low --sleep=3 --tries=3 --timeout=120 --max-time=3600
user=flowerflow
numprocs=1
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stopwaitsecs=130
redirect_stderr=true
stdout_logfile=/var/log/flowerflow/worker.log
stdout_logfile_maxbytes=20MB
stdout_logfile_backups=10
environment=APP_ENV="production"
```

`numprocs`, colas, timeout, memoria y reintentos son `PENDING` de pruebas de carga y contratos de cada job. Los jobs de correo y exportación deben ser idempotentes.

Activación propuesta:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status flowerflow-worker:*
```

Después de cambiar el symlink de release:

```bash
php artisan queue:restart
sudo supervisorctl restart 'flowerflow-worker:*'
```

Verificar que sólo reinicie Flower Flow. No usar `supervisorctl restart all`.

## 15. Scheduler con cron

Usar una sola fuente de scheduling. Entrada propuesta en `/etc/cron.d/flowerflow`:

```cron
* * * * * flowerflow cd /var/www/flowerflow/current && /usr/bin/php artisan schedule:run >> /var/log/flowerflow/scheduler.log 2>&1
```

Validar:

```bash
php artisan schedule:list
sudo test -r /etc/cron.d/flowerflow
systemctl status cron --no-pager
```

No duplicar la entrada en crontab de usuario y `/etc/cron.d`. Las tareas críticas deben usar `withoutOverlapping`, `onOneServer` sólo si el backend de locks lo soporta, y logs sin PII.

## 16. Backups, restore, RPO y RTO

### 16.1 Objetivos propuestos

Pendientes de aprobación de producto, legal y presupuesto:

| Recurso | RPO propuesto | RTO propuesto |
|---|---:|---:|
| Base productiva en RDS | 5 minutos mediante PITR | 4 horas; 2 horas en ventana crítica |
| Archivos privados | 1 hora | 4 horas; 2 horas en ventana crítica |
| Código/configuración | un release confirmado | 1 hora |
| Logs de auditoría | 15 minutos | 4 horas |

Ventana crítica propuesta: desde siete días antes del cierre de convocatoria hasta completar la publicación controlada de resultados. Si MySQL queda en la EC2, el diseño debe demostrar cómo alcanza estos objetivos; un backup diario aislado no es suficiente.

### 16.2 Política mínima

- cifrado en origen y destino;
- backup previo a toda migración productiva;
- copia fuera de la EC2 y de la cuenta lógica de aplicación;
- retención y lifecycle aprobados según privacidad;
- verificación de checksum/consistencia;
- alertas por fallo o antigüedad excesiva;
- acceso a restauración separado del acceso runtime;
- registro auditable de cada backup y restore.

### 16.3 Restore drill

Antes de go-live y después de cambios relevantes:

1. restaurar DB y archivos en un entorno aislado;
2. usar secretos y dominios de staging, nunca producción;
3. verificar integridad referencial y conteos esperados;
4. probar login, lectura autorizada de archivo y flujo crítico sintético;
5. medir RPO/RTO reales;
6. destruir de forma controlada la copia temporal según retención;
7. guardar evidencia sin PII.

Un backup no se considera válido hasta que una restauración haya sido probada.

## 17. Logs, rotación y CloudWatch

Fuentes mínimas:

- Laravel `daily` con retención local definida;
- Apache access/error de Flower Flow;
- PHP-FPM error y slow log del pool Flower Flow;
- Supervisor worker;
- scheduler;
- MySQL/RDS y auditoría según decisión;
- CloudTrail/SSM para cambios de infraestructura.

Aplicar `logrotate` y enviar a CloudWatch sólo los campos necesarios. Redactar contraseñas, tokens, cookies, cuerpos de webhook, comprobantes, contenido de anexos, direcciones, teléfonos y correos cuando no sean indispensables.

Alarmas mínimas:

- HTTP 5xx y health check;
- worker detenido, `failed_jobs` y antigüedad de cola;
- scheduler sin heartbeat;
- CPU, memoria, load, disco e inodos;
- conexiones/almacenamiento/latencia de MySQL;
- backup fallido o vencido;
- expiración de certificado;
- errores de correo y almacenamiento privado.

Usar correlation ID para enlazar solicitud, job y log sin exponer datos personales.

## 18. Staging y UAT

Staging debe ser un ambiente separado, no una bandera dentro de producción:

- dominio, vhost y certificado propios;
- base, usuario, secretos y storage propios;
- `APP_ENV=staging`, `APP_DEBUG=false`;
- cookies y prefijos de cache exclusivos;
- SMTP sandbox o destinatarios allowlist;
- `noindex` y acceso restringido;
- datos completamente sintéticos;
- workers y scheduler identificables como staging;
- backups y restore de prueba sin datos productivos.

UAT mínima antes de go-live:

- registro, verificación y restablecimiento;
- creación, borrador, archivos y envío idempotente;
- cierre por fecha y `America/Hermosillo`;
- elegibilidad y comprobantes privados;
- asignación, conflicto y evaluación ciega;
- cálculo server-side y declaración separada de ganador;
- permisos negativos entre participante, juez, revisor y administrador;
- exportaciones y auditoría;
- correo, reintentos y `failed_jobs`;
- navegación móvil, teclado y accesibilidad;
- backup/restore y rollback de release.

La aprobación debe registrar versión/SHA, ambiente, casos, defectos aceptados y firmantes.

## 19. Procedimiento de despliegue

### 19.1 Preparación

1. confirmar ticket, ventana, responsables y canal de coordinación;
2. verificar que Administratec está saludable y capturar baseline;
3. confirmar tests, build, auditorías y UAT del SHA exacto;
4. confirmar backup y restore drill vigente;
5. validar capacidad, DNS, certificado, SG/UFW e IAM;
6. confirmar migraciones revisadas y compatibilidad hacia atrás;
7. identificar release actual y release previo sano;
8. pausar si cualquier evidencia falta.

### 19.2 Crear release

```bash
release=/var/www/flowerflow/releases/<timestamp>-<git-sha>
sudo -u <flowerflow-deploy-user> mkdir -p "$release"
# Extraer aquí el artefacto verificado del SHA aprobado.
cd "$release"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
corepack yarn@1.22.22 install --frozen-lockfile
corepack yarn@1.22.22 build
```

Crear symlinks a `shared/.env` y `shared/storage` sin imprimir el archivo de entorno. Validar permisos con el usuario efectivo de PHP y del worker.

### 19.3 Validación antes del switch

```bash
php artisan about
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate:status
test -f public/build/manifest.json
composer check-platform-reqs --no-dev
```

No ejecutar `migrate --force` si no existe backup inmediato, revisión de la migración y aprobación del change owner.

### 19.4 Migración y switch atómico

Cuando el release lo requiera y esté autorizado:

```bash
php artisan down --render="errors::503"
php artisan migrate --force
ln -sfn "$release" /var/www/flowerflow/current.new
mv -Tf /var/www/flowerflow/current.new /var/www/flowerflow/current
php artisan queue:restart
sudo supervisorctl restart 'flowerflow-worker:*'
sudo apache2ctl configtest
sudo systemctl reload apache2
php artisan up
```

Las migraciones deben seguir expand/contract. Un switch de symlink no revierte una migración destructiva.

### 19.5 Smoke tests

```bash
curl -fsS https://<production-domain>/up
curl -I http://<production-domain>/
curl -I https://<production-domain>/
php artisan migrate:status
php artisan schedule:list
sudo supervisorctl status 'flowerflow-worker:*'
```

Confirmar además:

- página pública sin debug ni assets rotos;
- login y CSRF;
- escritura/lectura sintética controlada;
- descarga privada autorizada y rechazo no autorizado;
- envío de correo de prueba allowlist;
- consumo de un job sintético idempotente;
- cero 5xx nuevos en logs;
- Administratec continúa saludable.

La ruta `/up` debe existir y ser inocua; si no existe, crear un health check mínimo en un milestone aprobado.

## 20. Rollback

### 20.1 Disparadores

- 5xx sostenidos o health check fallido;
- pérdida de autorización o exposición de archivos;
- worker/scheduler no recuperable;
- migración incompleta o corrupción;
- degradación de Administratec;
- error de assets, login o flujo crítico;
- alarma de infraestructura sin margen seguro.

### 20.2 Código compatible con la base

```bash
previous=/var/www/flowerflow/releases/<previous-known-good>
ln -sfn "$previous" /var/www/flowerflow/current.new
mv -Tf /var/www/flowerflow/current.new /var/www/flowerflow/current
php artisan config:cache
php artisan queue:restart
sudo supervisorctl restart 'flowerflow-worker:*'
sudo apache2ctl configtest
sudo systemctl reload apache2
```

Repetir smoke tests y registrar tiempos/resultados.

### 20.3 Base incompatible o datos dañados

Detener escrituras y workers. No ejecutar automáticamente `migrate:rollback`. La reversión de DB requiere decisión del owner, análisis de pérdida de datos y restauración desde snapshot/PITR o procedimiento compensatorio probado.

Registrar:

- punto de restauración elegido;
- pérdida máxima estimada frente al RPO;
- integridad verificada;
- comunicación a usuarios;
- causa raíz y acciones preventivas.

## 21. Limpieza post-deploy

Después del periodo de observación:

- conservar al menos el release activo y dos releases conocidos sanos;
- eliminar releases antiguos sin tocar `shared`;
- confirmar backups y alarmas;
- revisar errores, jobs fallidos, cron y capacidad;
- normalizar TTL DNS si se redujo;
- cerrar accesos temporales y retirar permisos IAM de la ventana;
- actualizar inventario, ADR, ExecPlan y evidencia;
- nunca borrar el release previo antes de validar rollback.

## 22. Checklist de salida

- [ ] Inventario EC2/Ubuntu/Apache/PHP/MySQL confirmado.
- [ ] Aislamiento de Administratec verificado y baseline sano.
- [ ] SG, UFW, IAM, SSM y secretos aprobados.
- [ ] Dominio, TLS y headers validados.
- [ ] Release inmutable y build reproducible.
- [ ] Storage privado sin exposición pública.
- [ ] DB productiva decidida y usuarios mínimos.
- [ ] Backup y restore drill dentro de RPO/RTO.
- [ ] Worker Supervisor y cron separados.
- [ ] Logs, CloudWatch y alarmas activos.
- [ ] Staging y UAT aprobados con datos sintéticos.
- [ ] Smoke tests de Flower Flow y Administratec en verde.
- [ ] Rollback ensayado y responsables disponibles.
- [ ] Evidencia sin secretos ni PII archivada.

## 23. Pendientes para convertir este runbook en ejecutable

1. inventariar la EC2 real mediante SSM/SSH autorizado;
2. aprobar RDS o MySQL local y sus SLO;
3. confirmar dominio, Route 53, certificado y staging;
4. definir usuarios, grupos, paths y PHP SAPI sin afectar Administratec;
5. aprobar límites de archivos y capacidad;
6. definir SMTP y controles de entregabilidad;
7. aprobar RPO, RTO y retención;
8. crear health check, worker config, cron, vhost y CloudWatch como cambios revisables;
9. ejecutar restore drill y UAT;
10. obtener aprobación expresa de despliegue.
