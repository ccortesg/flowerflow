# Matriz de reutilización UI Pixinvent → FlowerFlow

Fecha: 2026-07-15

| Flujo FlowerFlow | Patrón de referencia | Decisión | Adaptación activa | Dependencias cargadas | Gate |
|---|---|---|---|---|---|
| Landing | Front landing, hero, cards | ADAPT | Poster propio, contenido semántico, CTA por flags | Bootstrap; sin Swiper/noUiSlider | Responsive, contenido crítico sin imagen, contraste. |
| Login/reset/verify/2FA | Auth basic y two steps | ADAPT | Vistas propias conectadas a Fortify; sin social login | Fortify, Bootstrap | Rate limit, enumeración, correo, teclado. |
| Perfil | Basic inputs + Account | ADAPT | Nombres separados, E.164, checkbox WhatsApp reversible, declaraciones | HTML nativo/Bootstrap | Validación servidor, 18+, privacidad y móvil. |
| Borrador de propuesta | Wizard numbered | FUTURE | Formulario seccionado en una página, guardado explícito | Sin bs-stepper | Recuperación de errores/foco y guardado. |
| Descripción | Quill Snow | ADAPT | Toolbar mínima, Delta+HTML+texto, sanitizer Symfony | Quill 2.0.3, HTML Sanitizer | XSS almacenado, Base64/hotlink, output re-sanitizado. |
| Adjuntos | Dropzone multiple | REJECT | Input file nativo multiple, ruta privada, hash, MIME, cuota | Sin Dropzone en la página | IDOR, macro/ZIP bomb, 10 MiB y no `storage:link`. |
| Confirmación de envío | SweetAlert/modal | REJECT | Checkboxes separados y confirmación progresiva nativa | JS mínimo | Doble clic/idempotencia, sin depender de JS. |
| Dashboard participante | Cards | ADOPT | Conteos y tabla de propuestas | Bootstrap | Móvil, estados y acceso propietario. |
| Panel | Dashboard/cards | ADAPT | Conteos, distribución textual y recientes | Sin gráficas | Consultas/roles, PII sólo admin, rendimiento. |
| Lista panel | DataTables | REJECT | Filtros GET y paginación Eloquent/HTML | Sin DataTables en vista | Paginación, filtros y no exportar PII. |
| Cuenta admin | Account/Security | ADAPT | Perfil, password y 2FA Fortify | Fortify | Confirmación, recovery codes y sesiones. |
| Roles/permisos | Access Roles/Permission | REJECT UI / ADAPT backend | Roles sembrados y Policies; sin CRUD visual | Spatie Permission | Vertical/horizontal authorization y auditoría. |
| Errores | Misc error | FUTURE | Respuesta framework actual | Ninguna adicional | Status HTTP real, no filtrar debug. |

## Regla de adopción

`ADOPT` significa reutilizar una convención visual simple del starter activo. `ADAPT` exige eliminar datos demo, conectar backend, traducir y probar seguridad/accesibilidad. `REJECT` evita la pieza para Fase 01. `FUTURE` no autoriza implementación. Ninguna decisión autoriza copiar la aplicación Full completa o versionar `_referencia/`.
