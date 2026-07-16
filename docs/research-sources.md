# Fuentes de investigación

## Fuentes aplicadas en Fase 01

| Fuente primaria | Uso aplicado |
|---|---|
| https://laravel.com/docs/12.x/starter-kits | Fortify como backend de autenticación, verificación, reset y 2FA. |
| https://spatie.be/docs/laravel-permission/v8/prerequisites | Compatibilidad Laravel 12/13 y PHP 8.3+ de v8. |
| https://symfony.com/doc/7.4/html_sanitizer.html | Configuración de allowlist, esquemas y hosts del sanitizer. |
| https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html | Allowlist, firma/MIME, nombres generados, storage privado, tamaño y expansión. |
| https://getcomposer.org/download/ | Instalación/verificación de Composer 2.10.2 para el usuario del proyecto. |

Las versiones exactas también se verificaron contra metadata de Composer/Packagist y `composer.lock`; las fuentes no sustituyen pruebas del código local.

Fecha de consulta: 2026-07-15  
Política: sólo documentación oficial o repositorios oficiales de los proyectos

## 1. Uso y límites

Estas fuentes sustentan decisiones técnicas de planificación. No constituyen asesoría legal, certificación de seguridad, declaración de accesibilidad ni garantía de cumplimiento.

La evidencia autoritativa de la baseline local sigue siendo:

- composer.json;
- package.json y yarn.lock;
- config/variables.php y config/custom.php;
- código, migraciones, tests, vistas y assets inspeccionados;
- salidas de diagnóstico registradas en docs/00-repository-audit.md.

Las versiones exactas instaladas deben volver a documentarse después de crear lockfiles e instalar dependencias.

## 2. Laravel

| Fuente oficial | Versión/fecha relevante | Conclusión aplicada |
|---|---|---|
| https://laravel.com/docs/12.x/releases | Laravel 12; consultado 2026-07-15 | Laravel 12 soporta PHP 8.2-8.5; release 2025-02-24; bug fixes hasta 2026-08-13 y security fixes hasta 2027-02-24. Requiere plan de ciclo de vida |
| https://laravel.com/docs/12.x/deployment | Laravel 12 | Servir exclusivamente desde public; PHP >=8.2 y extensiones requeridas; permisos de escritura sólo para storage/bootstrap/cache; APP_DEBUG=false; optimize; reiniciar workers; usar /up |
| https://laravel.com/docs/12.x/configuration | Laravel 12 | .env no debe entrar a control de versiones; .env.example usa placeholders; existe cifrado de environments si se aprueba |
| https://laravel.com/docs/12.x/starter-kits | Laravel 12 | Los starter kits oficiales usan Fortify para login, registro, reset, verificación y 2FA; el repositorio local no contiene ese backend |
| https://laravel.com/docs/12.x/filesystem | Laravel 12 / Flysystem 3 | S3 requiere league/flysystem-aws-s3-v3 ^3.0; local y S3 admiten URLs temporales; documentos sensibles deben permanecer privados |
| https://laravel.com/docs/12.x/queues | Laravel 12 | Workers son procesos de larga vida y deben reiniciarse tras deploy; requieren process monitor en EC2 |

Decisión: conservar Laravel 12 durante Milestone 0 para no mezclar una actualización major. Crear una decisión separada sobre Laravel 13 antes de que termine el soporte de seguridad de Laravel 12.

## 3. MySQL

| Fuente oficial | Versión | Conclusión aplicada |
|---|---|---|
| https://dev.mysql.com/doc/refman/8.0/en/charset.html | MySQL 8.0 | Usar utf8mb4; utf8 es alias de utf8mb3 y está deprecado |
| https://dev.mysql.com/doc/refman/8.0/en/charset-applications.html | MySQL 8.0 | Configurar charset/collation de aplicación y base explícitamente |
| https://dev.mysql.com/doc/refman/8.0/en/innodb-introduction.html | MySQL 8.0 | InnoDB es el engine por defecto; aporta transacciones, recuperación, locking por fila y foreign keys |

Decisión: MySQL local flowerflow debe usar InnoDB y utf8mb4. La collation exacta debe confirmarse en el servidor y mantenerse consistente entre local, staging y producción.

## 4. AWS EC2, IAM, EBS y S3

| Fuente oficial | Conclusión aplicada |
|---|---|
| https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/ec2-best-practices.html | Security groups de mínimo privilegio, parches, cifrado EBS, snapshots, backup/restore probado, monitoreo y separación de datos |
| https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/iam-roles-for-amazon-ec2.html | Aplicaciones en EC2 deben usar IAM role/instance profile y credenciales temporales, no access keys persistentes |
| https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/data-protection.html | TLS en tránsito, cifrado en reposo, MFA, CloudTrail y responsabilidad compartida |
| https://docs.aws.amazon.com/ebs/latest/userguide/encryption-by-default.html | Habilitar cifrado EBS por defecto y verificar la región/cuenta objetivo |
| https://docs.aws.amazon.com/AmazonS3/latest/userguide/security-best-practices.html | Deshabilitar ACLs, Block Public Access, cifrado, policies mínimas y monitoreo |
| https://docs.aws.amazon.com/AmazonS3/latest/userguide/access-control-block-public-access.html | Para comprobantes/documentos privados deben activarse los cuatro controles de Block Public Access salvo excepción revisada |

Decisión: el destino es una EC2 Ubuntu compartida con Administratec, no GoDaddy. La instancia debe inspeccionarse antes de decidir vhost, PHP-FPM, base, workers o almacenamiento. Los documentos privados no deben hacerse públicos mediante storage:link.

## 5. Ubuntu

| Fuente oficial | Versión/soporte | Conclusión aplicada |
|---|---|---|
| https://ubuntu.com/about/release-cycle?product=ubuntu&release=ubuntu&version=24.04+LTS | Ubuntu 24.04 LTS: mantenimiento estándar hasta mayo de 2029 | Una LTS es la base recomendada para producción estable; primero debe verificarse la versión real de la EC2 de Administratec |

No se asume que la instancia actual sea 24.04. Si usa otra versión soportada, se documentará su ciclo y plan de parcheo; no se hará upgrade de SO dentro del despliegue Flower Flow sin un carril separado.

## 6. Accesibilidad y seguridad

| Fuente oficial | Versión | Conclusión aplicada |
|---|---|---|
| https://www.w3.org/TR/WCAG22/ | W3C Recommendation 2024-12-12 | Objetivo WCAG 2.2 AA; la conformidad se evalúa sobre páginas completas y procesos completos, con pruebas automáticas y manuales |
| https://owasp.org/www-project-application-security-verification-standard/ | ASVS 5.0.0, estable a la consulta | Base trazable para requisitos y pruebas técnicas de seguridad; citar IDs con versión |

Decisión: no declarar conformidad por implementar componentes aislados. Login, wizard, uploads, evaluación y exportación deben probarse como procesos completos, por teclado y con controles ASVS trazables.

## 7. Materialize/Pixinvent

| Fuente oficial | Conclusión aplicada |
|---|---|
| https://demos.pixinvent.com/materialize-html-admin-template/documentation/laravel-introduction.html | La documentación distingue starter-kit/full-version, describe layouts/config/custom.php, sugiere Jetstream para auth y Spatie para ACL. Son sugerencias del proveedor, no decisiones automáticas |
| https://demos.pixinvent.com/materialize/changelog.html | La actualización HTML+Laravel v13.7.0 migró a Laravel 12, Bootstrap 5.3.5, Iconify, Notiflix y Notyf. La serie del changelog no coincide con el 3.0.0 local |

Conclusión: el checkout se comporta como starter skeleton aunque incluye muchos assets demo. No puede afirmarse la variante exacta ni licencia comprada sin evidencia comercial. Se debe conservar un registro de overrides antes de actualizar.

## 8. Dependencias propuestas

| Fuente oficial | Propuesta | Conclusión |
|---|---|---|
| https://spatie.be/docs/laravel-permission/v8/prerequisites | Spatie Permission | Laravel 12 es compatible; v7/v8 exige PHP 8.3+, mientras v6 cubre Laravel 12 con PHP 8.2. La versión depende del PHP real de EC2 |
| https://github.com/laravel/fortify | Laravel Fortify | Backend frontend-agnostic para auth y 2FA, MIT; adaptar a Blade Materialize sin asumir Jetstream |
| https://docs.clamav.net/Introduction.html | ClamAV | Ofrece clamd multihilo, clamscan y freshclam; aprobar sólo tras medir capacidad y operación |
| https://docs.clamav.net/manual/Usage/Scanning.html | ClamAV scanning | clamd necesita base de firmas actualizada; definir timeouts, fallas y cuarentena |
| https://playwright.dev/docs/intro | Playwright | E2E multip navegador; sólo dev/CI, con versión fijada y datos controlados |
| https://github.com/microsoft/playwright/blob/main/LICENSE | Playwright | Apache-2.0 |
| https://raw.githubusercontent.com/thephpleague/flysystem-aws-s3-v3/3.x/LICENSE | Flysystem S3 adapter | MIT |

Todas permanecen PENDING. No fueron instaladas durante la planificación.

## 9. Licencias y términos especiales

| Fuente | Uso |
|---|---|
| https://formvalidation.io/license | Revisar licencia de FormValidation 2.4.0 incluida/declarada por el template |
| https://fullcalendar.io/license | Revisar términos del plugin Timeline/Scheduler antes de uso |
| https://www.mapbox.com/legal/tos | Revisar Mapbox GL 3.8.0, token, telemetría, costo y uso permitido |
| https://github.com/Cisco-Talos/clamav | Confirma ClamAV GPL-2.0 |
| https://raw.githubusercontent.com/spatie/laravel-permission/main/LICENSE.md | Confirma Spatie Permission MIT |

La revisión comercial de Pixinvent, FormValidation, FullCalendar y Mapbox es un bloqueo previo a producción si esos componentes permanecen.

## 10. Preguntas que la investigación no resuelve

- Versión exacta y configuración de Ubuntu en la EC2.
- PHP-FPM, Nginx/Apache, MySQL, Supervisor/systemd y cron ya usados por Administratec.
- Capacidad de CPU, RAM, disco y crecimiento de EBS.
- Si MySQL de producción estará en la misma EC2 o en un servicio administrado.
- Licencia Pixinvent adquirida y alcance por dominio/proyecto.
- Licencia efectiva de FormValidation/FullCalendar Timeline/Mapbox bajo esa compra.
- SMTP y DNS operativos.
- Bucket S3, retención, KMS y región.
- Política legal de retención/eliminación.
- Si ClamAV cabe en la instancia sin afectar Administratec.

Estas preguntas deben resolverse mediante inspección autorizada, documentos de compra y decisiones del producto; no mediante supuestos.
