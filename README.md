# FlowerFlow — Hermosillo Florece 2026

Aplicación Laravel 12 para la convocatoria Hermosillo Florece 2026. La Fase 01 implementa sitio público, autenticación, perfil, recepción versionada detrás de flags y panel administrador mínimo. No incluye evaluación, resultados ni despliegue productivo.

## Entorno local soportado

- Ubuntu sobre WSL2
- PHP 8.3 con `pdo_mysql`
- MySQL 8, base `flowerflow`, usuario `flowerflow_user`
- Composer 2.10+
- Node 20 y Yarn Classic 1.22.22

La contraseña MySQL vive sólo en `.env` ignorado. No se pega en comandos, documentación ni commits. Consulta [desarrollo local](docs/11-local-development.md) y el [estado de Fase 01](docs/12-project-status-2026-07-15.md).

La interfaz, correos y validaciones usan español de México (`APP_LOCALE=es_MX` y HTML `lang="es-MX"`). La aplicación y los timestamps persistidos operan en UTC (`APP_TIMEZONE=UTC`); las fechas de la convocatoria se presentan con `FLOWERFLOW_TIMEZONE=America/Hermosillo`.

## Arranque

```bash
composer install
corepack yarn@1.22.22 install --frozen-lockfile
scripts/publish_authorized_assets.sh
php artisan key:generate
php artisan migrate --seed
corepack yarn@1.22.22 build
php artisan serve --host=127.0.0.1 --port=8000
```

Antes de migrar, copia `.env.example` a `.env`, cambia `APP_URL` para local y captura `DB_PASSWORD` en un editor seguro. Registro y recepción vienen deshabilitados; actívalos sólo en local/test para UAT aprobada.

Crear una cuenta administradora sin exponer la contraseña:

```bash
php artisan flowerflow:admin
```

## Gate de calidad

```bash
php artisan test
./vendor/bin/pint --test
composer validate --strict
composer audit --locked
corepack yarn@1.22.22 install --frozen-lockfile
corepack yarn@1.22.22 build
```

Los PDFs jurídicos y PNG autorizados se publican únicamente mediante `scripts/publish_authorized_assets.sh`, que verifica hashes antes de copiar.

Gate local de Fase 01 verificado el 2026-07-15: 15 pruebas/67 aserciones sobre MySQL, build reproducible y browser QA en escritorio y 390×844.

## Despliegue

El destino futuro es AWS EC2 Ubuntu donde ya opera Administratec, con host canónico `app.flowerflow.com.mx`. Esta rama no accede ni modifica AWS. Ver [runbook EC2](docs/07-deployment-aws-ec2.md).
