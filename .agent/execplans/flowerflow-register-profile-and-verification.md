# ExecPlan: registro completo, teléfono México y verificación amigable

**Estado:** Complete

**Creado:** 2026-07-15 America/Hermosillo

## Propósito y resultado observable

La persona participante captura desde `register` todos los datos mínimos que hoy exige su perfil, acepta los documentos legales vigentes y decide los consentimientos opcionales antes de crear la cuenta. El celular se captura con un componente visual inspirado en el grupo “Phone Number” de Pixinvent, con `México (+52)` visible por defecto, máscara nacional de 10 dígitos y normalización backend a E.164. Al abrir el enlace firmado del correo se muestra una confirmación amigable de cuenta verificada. Los estados y errores de formularios propios y de Fortify se presentan en español de México.

## Alcance

Incluido:

- nombres, apellidos, correo, celular, fecha de nacimiento, colonia, residencia, WhatsApp, contraseña y confirmación en el registro;
- creación transaccional de `users`, `participant_profiles`, rol y aceptaciones legales;
- aceptación obligatoria conjunta de mayoría de edad, Términos y Aviso de Privacidad con descargas PDF;
- consentimiento opcional de futuras actividades activo por defecto, pero revocable antes de enviar;
- componente telefónico reutilizable en registro y edición de perfil, sin dependencia nueva;
- respuesta/página propia después de verificar correo;
- traducciones de mensajes Fortify y presentación comprensible de estados y validaciones;
- pruebas Feature, build y navegador real en escritorio/móvil/teclado.

Excluido:

- cambiar los PDF jurídicos v1.0, sus hashes o publicar una versión v1.1;
- selector mundial de países o dependencia `intl-tel-input`;
- desplegar en EC2, contactar SMTP real o alterar producción;
- eliminar la edición posterior del perfil.

## Contexto y contratos

- La demo de Pixinvent presenta un `input-group` con prefijo de país estático y máscara; FlowerFlow replica el patrón con México en lugar de copiar assets o código del proveedor.
- El backend es autoritativo: acepta una captura nacional mexicana de 10 dígitos, elimina separadores de presentación y persiste `+52` más los diez dígitos.
- La cuenta, perfil, rol y evidencias se crean dentro de una sola transacción; cualquier validación o ausencia de documentos activos deja cero registros parciales.
- La casilla obligatoria registra aceptaciones separadas para `terms` y `privacy`, preservando versión, fecha UTC, IP, agente y contexto `registration`.
- Los consentimientos `whatsapp_contact` y `future_activities` se registran aun cuando sean rechazados, para conservar la decisión expresa.
- La residencia se declara por separado porque sigue siendo una precondición de elegibilidad.
- La verificación firmada conserva el middleware `auth`, `signed` y rate limit de Fortify; sólo cambia la respuesta final.

## Plan de ejecución

1. Centralizar normalización mexicana y reutilizarla en registro/perfil.
2. Ampliar `CreateNewUser` con validación y creación transaccional del perfil/aceptaciones.
3. Rediseñar `register` con datos completos, documentos descargables y consentimiento opcional activo.
4. Crear respuesta y vista de correo verificado; actualizar el recorrido público.
5. Normalizar estados Fortify y mensajes de validación del panel en español claro.
6. Actualizar pruebas, trazabilidad, seguridad/UX/QA/estado del proyecto.
7. Ejecutar suite, Pint acotado, Composer, build y navegador real.

## Validación

```text
php artisan test
vendor/bin/pint <archivos PHP modificados>
composer validate --no-check-publish
composer audit --locked --no-interaction
cmd.exe /d /c "corepack yarn build"
php artisan view:cache
php artisan route:list
git diff --check
```

Criterios:

- un alta válida crea usuario, perfil completo, rol y cuatro evidencias de consentimiento;
- menor de 18 años, teléfono incompleto, residencia o aceptación legal ausentes no crean cuenta parcial;
- `662 123 4567` se guarda como `+526621234567`;
- registro muestra +52, PDFs descargables y consentimiento futuro marcado inicialmente;
- enlace firmado verifica y termina en mensaje amigable;
- estados Fortify no muestran tokens ni textos en inglés;
- pruebas y navegador no contactan SMTP ni contienen PII real.

## Despliegue y rollback

No requiere migración ni dependencia. Despliegue: publicar código/assets compilados, regenerar cachés y hacer smoke del registro con datos sintéticos antes de abrir el flag. Rollback: volver al commit previo y regenerar cachés; los perfiles/aceptaciones creados son compatibles con el esquema anterior. No borrar evidencias legales durante rollback.

## Registro vivo

- [x] 2026-07-15 21:20 MST — Demo Pixinvent revisada: prefijo estático `US (+1)` más máscara; se decide equivalente propio `México (+52)` sin dependencia.
- [x] 2026-07-15 21:25 MST — Baseline verde: 22 pruebas/117 aserciones sobre MySQL.
- [x] 2026-07-15 21:30 MST — Implementación del registro, teléfono México, legales y verificación amigable completada.
- [x] 2026-07-15 21:45 MST — Pruebas focales verdes: 18 pruebas/124 aserciones en registro, correo, perfil y envío.
- [x] 2026-07-15 21:50 MST — Suite completa verde: 28 pruebas/161 aserciones; Pint acotado y `git diff --check` verdes.
- [x] 2026-07-15 21:55 MST — Build Vite verificado con Node 22.23.1/Yarn 1.22.22; `scripts/build_frontend_production.sh` no completó en WSL/NTFS por timeout en `yarn install` linking, pero `corepack yarn build` generó manifest correcto.
- [x] 2026-07-15 22:05 MST — Browser real con Playwright: registro desktop/móvil, teléfono, checklist de contraseña, PDFs/consentimientos y página `/correo-verificado`; consola 0 errores/0 advertencias.
- [x] 2026-07-15 22:10 MST — Diff final revisado y commit local creado.

## Hallazgos

- El registro sólo creaba `users`; el perfil completo se exigía después de verificar el correo.
- Los documentos ya existen como `legal_documents` activos v1.0 y sus PDF públicos están versionados por hash.
- Fortify coloca tokens como `password-updated` en sesión; el parcial global los mostraba sin traducir.
- La respuesta estándar de verificación redirige a `/inicio?verified=1` sin confirmación dedicada.
