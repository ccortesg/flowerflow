# FlowerFlow — Hermosillo Florece 2026

Aplicación Laravel 12 para la convocatoria Hermosillo Florece 2026. La Fase 01 implementa sitio público, autenticación, registro con perfil mínimo, recepción versionada detrás de flags y panel administrador mínimo. No incluye evaluación, resultados ni despliegue productivo.

## Entorno local soportado

- Ubuntu sobre WSL2
- PHP 8.3 con `pdo_mysql`
- MySQL 8, base `flowerflow`, usuario `flowerflow_user`
- Composer 2.10+
- Node 22.23.1 mediante NVM y Yarn Classic 1.22.22

La contraseña MySQL vive sólo en `.env` ignorado. No se pega en comandos, documentación ni commits. Consulta [desarrollo local](docs/11-local-development.md) y el [estado de Fase 01](docs/12-project-status-2026-07-15.md).

La interfaz, correos y validaciones usan español de México (`APP_LOCALE=es_MX` y HTML `lang="es-MX"`). La aplicación, la sesión MySQL y los timestamps persistidos operan en UTC (`APP_TIMEZONE=UTC`, `DB_TIMEZONE=+00:00`); las fechas de la convocatoria se presentan con `FLOWERFLOW_TIMEZONE=America/Hermosillo`. No cambies la sesión de la aplicación a `-07:00`: convierte sólo al consultar o presentar.

Las contraseñas nuevas requieren al menos 8 caracteres, mayúscula, minúscula, número, símbolo y confirmación; las pantallas muestran el avance sin sustituir la validación backend. El registro captura nombres, celular México `+52`, fecha de nacimiento, colonia, residencia y consentimientos desde el inicio; el perfil queda para revisión/cambio posterior. Verificación, recuperación y acuse de propuesta usan plantillas HTML/texto en español con ambas marcas y se programan en la cola `database/default` con payload cifrado, timeout y reintentos.

## Arranque

```bash
composer install
NVM_DIR="$HOME/.nvm"; . "$NVM_DIR/nvm.sh"
nvm use
scripts/build_frontend_production.sh
scripts/publish_authorized_assets.sh
php artisan key:generate
php artisan migrate --seed
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
scripts/build_frontend_production.sh
```

Los PDFs jurídicos y PNG autorizados se publican únicamente mediante `scripts/publish_authorized_assets.sh`, que verifica hashes antes de copiar.

Gate local actualizado el 2026-07-15: 28 pruebas/161 aserciones sobre MySQL, build reproducible y browser QA en escritorio y 390×844. Ver `docs/12-project-status-2026-07-15.md`.

## Despliegue

El destino es AWS EC2 Ubuntu donde ya opera Administratec, con host canónico `app.flowerflow.com.mx`. Node se instala por usuario con NVM para no reemplazar el runtime global de Administratec; nunca uses `sudo npm install -g corepack`. Ver [runbook EC2](docs/07-deployment-aws-ec2.md).
