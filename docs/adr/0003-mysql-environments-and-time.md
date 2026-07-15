# ADR 0003: MySQL por ambiente y contrato temporal

- Estado: `Proposed`
- Fecha: `2026-07-15`
- Alcance: local, test, staging y producción
- Decisión pendiente principal: RDS frente a MySQL en EC2

## Contexto

Flower Flow requiere MySQL, InnoDB y `utf8mb4`. El producto tiene fechas críticas de apertura/cierre, evaluaciones y publicación, con presentación de negocio en `America/Hermosillo`. También manejará PII, comprobantes y auditoría, por lo que no puede mezclar ambientes, credenciales o datos.

El baseline local se verificó directamente:

- WSL2 sobre Ubuntu 22.04.5;
- MySQL Community 8.0.46 activo;
- listeners sólo en `127.0.0.1`;
- esquema `flowerflow` existente;
- usuario `flowerflow_user@localhost` autenticado mediante cliente y PDO;
- charset `utf8mb4`, collation `utf8mb4_0900_ai_ci` e InnoDB default;
- base vacía, sin tablas ni tabla de migraciones;
- usuario con `ALL PRIVILEGES` sobre el esquema y `WITH GRANT OPTION`;
- timezone MySQL observado como `SYSTEM`;
- `.env` ausente y `.env.example` aún en SQLite.

La contraseña local fue provista fuera del repositorio y no forma parte de esta ADR.

La producción se ubicará en AWS, pero aún no se ha confirmado si MySQL estará en RDS, en la EC2 o en otro servicio administrado.

## Decisión propuesta

### 1. Aislamiento estricto por ambiente

Cada ambiente tendrá recursos exclusivos:

| Ambiente | Base | Credenciales | Datos permitidos |
|---|---|---|---|
| Local WSL2 | `flowerflow` | usuario local exclusivo | sintéticos |
| Test automatizado | base efímera o dedicada por suite | usuario test | fixtures sintéticos |
| Staging | base staging independiente | secreto staging | sintéticos/UAT |
| Producción | base productiva independiente | secreto productivo | datos reales autorizados |

Nunca se comparte entre ambientes:

- host/schema;
- usuario o contraseña;
- `APP_KEY`;
- backups o dumps sin proceso aprobado;
- buckets/directorios privados;
- cookies, sesiones, cache o queue prefixes.

### 2. MySQL como motor de integración

Las pruebas de integración y aceptación que dependan de SQL, índices, collation, foreign keys, locks o transacciones usarán MySQL compatible con producción.

SQLite puede emplearse en tests unitarios que no dependan de semántica específica de base, pero no valida por sí solo el comportamiento productivo.

### 3. Producción recomendada en Amazon RDS para MySQL

La opción recomendada es RDS MySQL 8 compatible, en subred privada, con:

- cifrado KMS;
- TLS obligatorio;
- Security Group sólo desde la aplicación;
- backups automáticos y point-in-time recovery;
- monitoreo y alarmas;
- credenciales en Secrets Manager;
- parameter group versionado;
- Multi-AZ si el SLO/presupuesto lo aprueba.

La versión exacta debe fijarse después de validar Composer, migraciones, collation y restore.

### 4. Fallback MySQL en EC2

MySQL en la misma EC2 sólo se aceptará si el análisis de costo/riesgo lo justifica y cumple:

- bind a loopback, sin 3306 público;
- esquema/usuarios separados de Administratec;
- EBS cifrado y capacidad/IOPS suficientes;
- binlogs y backups fuera de la instancia;
- restore drill;
- monitoreo de conexiones, disco, locks y latencia;
- RPO/RTO aprobados.

Esta opción concentra aplicación y datos en un solo failure domain y no es la preferida.

### 5. Separación de roles de base

Producción y staging deben separar:

- `runtime`: DML mínimo sobre tablas requeridas;
- `migrator`: DDL temporal durante deploy aprobado;
- `backup/monitoring`: permisos específicos;
- `admin`: fuera de la aplicación.

El usuario runtime no tendrá privilegios globales, administración de usuarios ni `WITH GRANT OPTION`.

El grant amplio local es una deuda de sandbox: no se cambia en fase 0 para evitar bloquear trabajo ajeno, pero no se replica.

### 6. Charset, collation y motor

Baseline propuesto:

- motor InnoDB;
- charset `utf8mb4`;
- collation `utf8mb4_0900_ai_ci` cuando el MySQL productivo elegido la soporte;
- foreign keys y reglas de borrado explícitas;
- strict mode habilitado;
- índices definidos para filtros, folios, estados, fechas y asignaciones.

Campos que requieran comparación case-sensitive, hashes, tokens o identificadores externos deben usar collation/tipo explícito. La collation general no debe decidir por accidente reglas de unicidad sensibles.

### 7. Contrato temporal

Se adopta como invariante:

- PHP/Laravel opera en UTC;
- timestamps persistidos se interpretan como UTC;
- la sesión MySQL se fija explícitamente en `+00:00`;
- la zona predeterminada de negocio es `America/Hermosillo`;
- cada convocatoria guarda su identificador IANA de zona;
- fecha/hora local ingresada se convierte a UTC en servidor;
- fecha/hora persistida se convierte a la zona autorizada sólo al presentar;
- jobs, auditoría, exports y APIs incluyen UTC inequívoco;
- reglas de deadline se calculan server-side, nunca con el reloj del navegador.

El valor MySQL `SYSTEM` observado localmente no es suficientemente determinista entre WSL, staging y AWS. En un milestone aprobado se agregará una configuración equivalente a `DB_TIMEZONE=+00:00` y se comprobará la zona de sesión al conectar.

Para apertura/cierre se guarda, como mínimo:

```text
opens_at_utc
closes_at_utc
timezone = America/Hermosillo
```

Los nombres físicos finales pueden variar, pero el contrato no. No almacenar sólo una fecha local, abreviatura `MST` u offset fijo como fuente de verdad.

## Razones

- MySQL local ya está disponible y coincide con el requisito del producto.
- MySQL real descubre diferencias que SQLite puede ocultar.
- separar ambientes reduce exposición y errores operativos.
- RDS reduce el failure domain y ofrece PITR administrado.
- roles separados limitan el impacto de una aplicación comprometida.
- UTC evita ambigüedad técnica; la zona IANA conserva el significado de negocio.
- deadlines requieren pruebas deterministas y cálculo server-side.

## Consecuencias positivas

- paridad mayor entre pruebas y producción;
- menor probabilidad de mezclar PII o secretos;
- backups y restauración con objetivos medibles;
- migraciones auditables con usuario separado;
- fechas consistentes en web, jobs, logs y reportes;
- posibilidad de cambiar la zona de una futura edición sin reinterpretar datos históricos.

## Consecuencias y costos

- RDS incrementa costo y operación AWS;
- tests MySQL son más pesados que SQLite;
- se necesitan secretos y bases por ambiente;
- la separación runtime/migrator complica el pipeline;
- las conversiones de zona deben diseñarse y probarse;
- una collation incorrecta puede afectar búsquedas y unicidad;
- cambiar el timezone de sesión exige actualización de configuración y pruebas.

## Alternativas rechazadas

### Una base y credencial para todos los ambientes

Rechazada por riesgo de pérdida, exposición de PII y ejecución accidental de migraciones/tests sobre producción.

### SQLite como única base de pruebas

Rechazada como evidencia suficiente por diferencias de tipos, foreign keys, collation, locking e índices.

### Reutilizar la credencial local en AWS

Rechazada. La credencial local es sandbox y sus grants son demasiado amplios.

### Depender de `SYSTEM` para zona horaria

Rechazada porque WSL, EC2 y RDS pueden tener zonas distintas.

### Guardar fechas de negocio sólo en `America/Hermosillo`

Rechazada porque dificulta integraciones, auditoría y comparación; se guarda UTC y se conserva la zona IANA del contexto.

### Guardar sólo offset o abreviatura

Rechazada porque un offset no representa reglas históricas de una zona IANA y `MST` es ambiguo fuera del contexto.

## Seguridad y privacidad

- secretos sólo en `.env` local ignorado o gestores AWS;
- TLS para conexiones remotas;
- backups cifrados y con acceso separado;
- datos productivos prohibidos en local/test/staging salvo proceso excepcional aprobado y transformación irreversible validada;
- consultas/logs no imprimen credenciales, comprobantes ni PII;
- exports y restore drills dejan auditoría;
- eliminación y retención deben considerar copias de backup.

## Validación requerida para aceptar la ADR

### Local

- conexión Laravel/PDO con `pdo_mysql`;
- sesión MySQL en UTC una vez implementada;
- migraciones revisadas sobre base vacía aprobada;
- foreign keys, índices y collation verificados;
- tests con datos sintéticos;
- remoción planificada de `GRANT OPTION`.

### Staging

- versión/collation iguales o compatibles con producción;
- migración desde cero y desde snapshot de versión previa;
- concurrencia, locks e idempotencia;
- deadlines antes/en/después de cierre en `America/Hermosillo`;
- backup y restore drill;
- usuario runtime incapaz de hacer DDL o grants.

### Producción

- decisión RDS/EC2 aprobada;
- RPO/RTO, retención, cifrado y PITR aprobados;
- SG privado y TLS comprobados;
- secretos rotables;
- métricas/alarmas activas;
- migrator disponible sólo durante la ventana de deploy;
- restore y rollback documentados.

## Preguntas abiertas

1. ¿RDS MySQL o MySQL en la EC2 existente?
2. ¿Qué versión exacta y ventana de mantenimiento?
3. ¿Multi-AZ desde go-live o después del MVP?
4. ¿RPO/RTO aprobados y ventana crítica exacta?
5. ¿Retención de backups compatible con privacidad y obligaciones legales?
6. ¿Qué campos requieren collation case-sensitive?
7. ¿Se usará Redis para queue/cache o database/file en MVP?
8. ¿Quién custodiará el usuario migrator y aprobará su activación?
9. ¿Cómo se anonimizará cualquier dataset excepcional de soporte?
10. ¿Qué fecha/hora exacta de apertura y cierre se aprobará para la edición 2026?

## Criterio de aceptación

Cambiar esta ADR a `Accepted` cuando el owner apruebe RDS o MySQL en EC2, versión, aislamiento, RPO/RTO, retención y contrato de zona, y staging demuestre migración, restore y límites temporales.

## Referencias

- [Runbook AWS EC2](../07-deployment-aws-ec2.md)
- [Desarrollo local](../11-local-development.md)
- [ADR 0002](0002-aws-ec2-ubuntu.md)
