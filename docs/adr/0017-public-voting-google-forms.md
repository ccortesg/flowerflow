# ADR-0017 — Votación pública mediante Google Forms

- **Estado:** aceptado para local/test por el propietario.
- **Fecha:** 2026-09-08.
- **Autorización:** plan de banner, botones y modal aprobado en esta tarea; sin despliegue.

## Contexto

La portada orientaba a registrar propuestas; se solicita invitar a votar con la estética del flyer autorizado. Ya existe Bootstrap 5.3.6 y una CSP que permite únicamente YouTube en `frame-src`. El formulario externo exige acceder a Google: sin sesión, el embed devuelve 401 y el enlace normal presenta un diálogo de acceso. El propietario decidió conservar ese requisito y ofrecer una alternativa externa.

## Decisión

1. Alcance inicial: actualizar hero, CTA de encabezado y CTA final conservando información de convocatoria y áreas autenticadas. La adenda del 2026-09-08 sustituye la conservación de las secciones del landing indicadas abajo.
2. Usar enlaces HTTPS reales que un módulo Vite exclusivo del landing mejora para abrir un único modal Bootstrap. Sin JavaScript o con fallo del módulo, los enlaces siguen abriendo el formulario en una pestaña nueva.
3. Guardar ambas URL entregadas por el propietario en `flowerflow.voting`. No admitir URL desde requests ni agregar endpoints de votación.
4. Crear el iframe sólo como elemento sin `src` inicial y asignarle el embed exacto al abrir. Conservar su instancia entre aperturas, sin leer su contenido ni intentar observar votos, respuestas o credenciales.
5. Mantener «Abrir en Google» visible junto al aviso de sesión. `load` no confirma que el formulario funcione, y no se usa `error` para diagnosticar un iframe remoto. El estado de espera es acotado y no se transforma en un mensaje de éxito.
6. Permitir `https://docs.google.com` exclusivamente en `frame-src` de la ruta `landing`, tanto para CSP actual como estricta. No relajar `script-src`, `connect-src`, `form-action`, `frame-ancestors` ni X-Frame-Options. No habilitar el login de Google dentro del iframe.
7. Separar el modal del shell para evitar clipping; restaurar foco, scroll e interactividad de fondo, respetar movimiento reducido y preservar la navegación móvil.
8. Reutilizar Bootstrap y fuentes locales del sistema. La ilustración generada queda en el repositorio con originales, exportes WebP reproducibles y hashes. No hay dependencias npm/Composer nuevas.

## Consecuencias y límites

- Google gestiona acceso, contenido, respuestas y confirmación. FlowerFlow no registra votos, no infiere duplicados y no garantiza votación íntegra dentro del modal.
- El primer clic que abre el iframe inicia una conexión con Google. No hay precarga, proxy, analítica ni transmisión de datos de cuentas FlowerFlow.
- Las políticas del navegador sobre cookies, sesiones o contenido externo pueden impedir la carga. La alternativa externa es parte del flujo aceptado, no un estado de éxito simulado.
- Escape dentro de un documento de otro origen no se propaga al documento principal. El cierre permanece fuera del iframe y accesible mediante Tab.
- La validación con sesión Google corresponde al propietario; no se introducen credenciales ni votos de prueba. «Option 1» observado en Google se registra como contenido ajeno pendiente de revisión.
- Rollback sin migraciones: revertir la integración y reconstruir Vite.

## Evidencia

Ver `.agent/execplans/flowerflow-public-voting.md` y `docs/34-public-voting-integration-2026-09-08.md`. La revisión local y la aprobación de la alternativa externa no autorizan despliegue.

## Adenda — landing centrado en votación (2026-09-08)

El propietario aprobó retirar del HTML público las secciones de categorías, proceso, requisitos, documentos y FAQ, adaptar la introducción a votar y comunicar «Los 2 proyectos con más votos serán los ganadores» y «Premio aún por definir». Son dos proyectos en total, sin presentar premios por categoría. La imagen del iPad deja de renderizarse; el original y sus derivados históricos se conservan.

Los enlaces a las secciones ocultas se sustituyen por `#ganadores` en el landing, acceso y navegación pública compartida. `/documentos`, los PDF, términos, privacidad y acceso a cuentas siguen disponibles. No hay nuevo flag ni ocultamiento mediante CSS. El modal, las URLs, la mejora progresiva y la CSP mantienen su contrato.

Esta decisión afecta a la presentación solicitada; no implementa conteo de votos, selección automática, desempates ni publicación de resultados. `PENDING`: reconciliar la regla comunicada con los PDF y pantallas de cuenta que todavía describen el premio anterior. No se editan esos contenidos ni se inventan reglas o premios en esta integración.

Evidencia de esta adenda: [informe 35](../35-voting-focused-landing-2026-09-08.md). Alcance local/test, sin stage, commit, push ni despliegue. Rollback por reversión del ajuste y reconstrucción de assets, sin cambios de datos.
