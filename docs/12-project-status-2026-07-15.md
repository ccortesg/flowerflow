# Estado del proyecto — Fase 01

Fecha: 2026-07-15
Rama: `codex/phase-01-public-submissions`
Commit base verificado: `403656dce350709d066aaab0576175036a9f339c`
Estado: Fase 01 local completada y verificada; sin despliegue y con recepción deshabilitada por defecto.

## Resultado real

La baseline dejó de ser un starter sin dominio. El código contiene una vertical local y revisable para:

- landing pública semántica con poster/logos autorizados y tres PDFs jurídicos v1.0;
- Fortify para login, reset, verificación de correo, cambio de contraseña y 2FA;
- Spatie Permission más Policy para separar participante y panel administrador;
- perfil de participante con nombres separados, E.164, WhatsApp opcional reversible, fecha de nacimiento, colonia y declaraciones;
- concurso/categorías, participación individual/equipo, máximo de cinco integrantes, borradores y una propuesta por categoría;
- Quill con Delta, HTML sanitizado y texto plano; archivos privados, hashes y cuota de 10 MiB; allowlist de enlaces sin fetch del servidor;
- envío transaccional e idempotente, folio, snapshot inmutable, eventos, aceptaciones separadas y correo en cola después del commit;
- `/panel` con conteos, distribución, filtros, detalle, archivos bajo Policy y cuenta/seguridad;
- comando `flowerflow:admin` con captura oculta de contraseña.

No existen evaluación, jueces, rúbricas, ganadores, resultados públicos, despliegue productivo ni cambios en AWS.

## Decisiones reconciliadas

| Tema | Decisión vigente |
|---|---|
| Entorno local | Ubuntu sobre WSL2, MySQL `flowerflow`, cuenta `flowerflow_user`; secreto sólo en `.env` ignorado. |
| Hosting futuro | AWS EC2 Ubuntu compartida con Administratec, con aislamiento; GoDaddy queda rechazado. |
| Host | `https://app.flowerflow.com.mx`; panel en `/panel`. |
| Idioma | Español de México: locale `es_MX` y HTML `es-MX` en interfaz, correos y validaciones. |
| Cierre | Inclusivo hasta `2026-08-15 23:59:59 America/Hermosillo`; persistencia UTC. |
| Flags seguros | público y panel activos; registro, recepción y resultados inactivos por defecto. |
| Jurídico | PDFs 1.0 conservados por hash; v1.1/adenda pendiente de aprobación y publicación. |
| Dependencias | Fortify 1.37.2, Permission 8.3.0 y HTML Sanitizer 7.4.14 fijados en `composer.lock`. |
| Package manager | Yarn Classic 1.22.22; no se crea `package-lock.json`. |

## Evidencia ejecutada

- Los tres hashes PDF y cinco hashes PNG coinciden con el encargo.
- Las 14 páginas PDF se renderizaron y revisaron visualmente sin defectos observados.
- `scripts/publish_authorized_assets.sh` reproduce copias binarias verificadas.
- `composer validate --strict` y `composer audit --locked`: correctos, sin advisories.
- `yarn install --frozen-lockfile` y `yarn build`: correctos; 2,220 módulos transformados en el build final.
- Lint PHP del código, migraciones y configuración: correcto.
- `php artisan route:list`: 44 rutas con flags seguros; 46 al habilitar registro local, incluidas Fortify y panel.
- MySQL WSL2 verificado: 8.0.46, schema `flowerflow`, cuenta `flowerflow_user@localhost`; seis migraciones aplicadas y seeder reproducible.
- Suite completa sobre MySQL: 15 pruebas, 67 aserciones, verde; sesión de conexión fijada y comprobada en `+00:00`.
- Pint acotado al código activo, compilación de vistas, `git diff --check` y escaneo de patrones de secretos: verdes.
- Browser QA real con Playwright CLI: landing, login/logout, tablero participante, borrador con PDF privado, envío con folio y panel admin; escritorio y 390×844, sin errores ni advertencias de consola.
- El browser QA detectó y permitió corregir una interpretación horaria: la aplicación opera en UTC y presenta `America/Hermosillo`; existe una prueba de regresión específica.
- La localización activa se verificó en navegador: navegación y editor enriquecido en español de México, sin textos técnicos en inglés observados.
- Evidencia visual local en `output/playwright/` (ignorada por Git); las cuentas/datos QA son sintéticos y se eliminan al cerrar la revisión.

## Diferencias respecto de la documentación anterior

Las afirmaciones históricas “fase 0”, “sin Git”, “sin lock”, “dependencias ausentes”, “hora pendiente”, “GoDaddy” y “sin dominio implementado” describen la baseline y quedan sustituidas para Fase 01 por este documento. No se borran para conservar trazabilidad. El estado de producción continúa sin cambios.

## Bloqueos y pendientes

1. Resolver la licencia comercial Pixinvent antes de producción.
2. Obtener aprobación jurídica y publicar v1.1 antes de activar registro/recepción.
3. Definir SMTP, apertura exacta, antivirus, staging y decisiones EC2/RDS/EBS/S3.
4. Ejecutar UAT formal con responsables y preparar preflight AWS bajo autorización separada.

## Próximo milestone

El gate local está cerrado. El siguiente milestone es preparar UAT y resolver sus puertas jurídicas, de licencia y operación; no se despliega a EC2 en esta fase.
