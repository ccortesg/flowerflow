# ADR 0002: AWS EC2 con Ubuntu como destino de despliegue

- Estado: `Accepted`
- Fecha: `2026-07-15`
- Alcance: infraestructura de staging y producción
- Reemplaza: supuestos de GoDaddy/cPanel del texto inicial

## Contexto

El planteamiento inicial solicitaba evaluar GoDaddy y alternativas de hosting compartido. La decisión confirmada por el owner es implementar Flower Flow en una instancia AWS EC2 con Ubuntu donde ya fue desplegado Administratec.

La revisión pública del `2026-07-15` observó que el endpoint de Administratec:

- resuelve sobre infraestructura con señal DNS pública de AWS;
- responde con Apache sobre Ubuntu;
- redirige HTTP a HTTPS;
- presenta un certificado Let's Encrypt vigente al momento de la revisión.

El checkout local de Administratec también documenta un target AWS/Ubuntu/Apache, pero no contiene los archivos productivos reales de vhost, Supervisor, cron, CloudWatch o backups. No hubo acceso SSH/SSM a la EC2 durante esta ADR, por lo que la versión exacta de Ubuntu y el inventario interno permanecen `PENDING`.

## Decisión

Flower Flow se desplegará en AWS EC2 con Ubuntu y Apache, con PHP 8.3 como runtime objetivo, mediante releases inmutables y un symlink `current`.

La coexistencia con Administratec exige aislamiento explícito de:

- usuario y directorios de aplicación;
- release actual y releases históricos;
- vhost, dominio, TLS y logs;
- pool/socket PHP-FPM cuando se adopte;
- `.env`, `APP_KEY` y secretos;
- esquema, usuarios y backups de base;
- worker Supervisor y cron scheduler;
- cache, sesiones, cookies y storage;
- IAM, parámetros, buckets y alarmas.

El path lógico propuesto es `/var/www/flowerflow`, con webroot `/var/www/flowerflow/current/public`.

Se prefiere AWS Systems Manager Session Manager frente a SSH público. Si SSH se mantiene, debe limitarse a VPN/IP administrativa, con credenciales individuales y sin acceso directo de `root`.

No se realizará ningún despliegue durante fase 0. El runbook, preflight, staging, UAT, backup, smoke tests y rollback deben aprobarse antes de modificar la instancia.

## Razones

- Es el destino confirmado por el owner.
- Reutiliza conocimiento operativo de Apache/Ubuntu sin asumir que los proyectos deben compartir configuración.
- EC2 permite workers persistentes, scheduler, PHP-FPM, observabilidad y almacenamiento privado sin las restricciones de hosting compartido.
- La estrategia de releases permite rollback de código más claro que sobrescribir un checkout vivo.
- IAM, SSM, CloudWatch, EBS, S3 y RDS permiten controles operativos que el MVP puede adoptar progresivamente.

## Consecuencias positivas

- se elimina la incertidumbre de cPanel, `public_html` y workers por cron;
- se puede usar Supervisor para queues y cron por minuto para Laravel;
- existe control de Apache, PHP, TLS, logs y headers;
- se puede aislar storage privado fuera del webroot;
- se habilita observabilidad y backup off-host;
- staging puede reproducir la topología de producción.

## Consecuencias y riesgos

- compartir EC2 crea blast radius de CPU, memoria, disco y cambios globales;
- una modificación de Apache, MPM, PHP o paquetes puede afectar Administratec;
- si DB y aplicación comparten EC2, una falla impacta ambos;
- la operación requiere parches, hardening, backups, alarmas y guardias;
- un symlink revierte código, pero no revierte migraciones destructivas;
- la capacidad de la instancia debe medirse durante picos de convocatoria.

## Controles obligatorios

1. inventario autorizado de AWS y del host antes de instalar;
2. baseline y smoke test de Administratec antes/después de cada cambio;
3. recursos Flower Flow con nombres, usuarios y paths exclusivos;
4. `DocumentRoot` limitado a `current/public` y `Options -Indexes`;
5. TLS, Security Groups, UFW e IAM de mínimo privilegio;
6. secretos fuera de Git y sin claves AWS estáticas;
7. storage privado y cifrado;
8. backup y restore drill dentro de RPO/RTO;
9. staging/UAT con datos sintéticos;
10. rollback probado y owner disponible durante deploy.

## Alternativas rechazadas

### GoDaddy compartido/cPanel

Rechazado porque dejó de ser el destino solicitado y obliga a diseñar alrededor de capacidades no confirmadas de cron, workers, document root y SSH.

### Sobrescribir o reutilizar el checkout de Administratec

Rechazado por riesgo de colisión de dependencias, secretos, assets, sesiones, deploy y rollback.

### Un solo vhost o webroot para ambos proyectos

Rechazado por exposición accidental y ausencia de frontera operativa.

### Usar `php artisan serve` en producción

Rechazado: es un servidor de desarrollo y no sustituye Apache/PHP-FPM, TLS, logs y límites operativos.

### Contenedores como requisito inmediato

No seleccionado para el MVP porque la instancia existente y Administratec deben inventariarse primero. Puede reevaluarse si reduce, y no aumenta, el riesgo operativo.

## Pendientes de implementación

- confirmar AMI/release Ubuntu, región, AZ, instance type y EBS;
- definir acceso SSM/SSH y usuario de despliegue;
- confirmar Apache MPM, mod_php/FPM y PHP 8.3;
- medir capacidad y blast radius de Administratec;
- decidir RDS o MySQL local mediante ADR 0003;
- aprobar dominios, DNS, staging, SMTP, storage y SLO;
- crear configuraciones revisables de Apache, FPM, Supervisor, cron y CloudWatch;
- ejecutar UAT y restore/rollback drills.

## Criterios para revisar esta ADR

Revisar si:

- el inventario demuestra capacidad insuficiente o riesgo inaceptable para Administratec;
- se decide usar una EC2 separada, ECS u otra plataforma;
- la seguridad exige eliminar SSH o separar cuentas/VPC;
- el costo/SLO obliga a RDS, Multi-AZ o balanceo;
- cambia el dominio o la arquitectura de storage.

## Referencias

- [Runbook AWS EC2](../07-deployment-aws-ec2.md)
- [Desarrollo local](../11-local-development.md)
- [ADR 0003](0003-mysql-environments-and-time.md)

