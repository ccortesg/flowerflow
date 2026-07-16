# UX/UI, accesibilidad e identidad — Flower Flow 2026

## Sistema visual Fase 01

La primera implementación usó carbón `#17352f`, verde `#167c5b`, verde oscuro `#0b5c42`, lima `#d9ed55`, coral `#ff765f` y crema `#fffdf5`. La landing V2 encapsula en `.ff-public-landing` un sistema cálido derivado de las referencias aprobadas: naranja `#ed5b21`, naranja oscuro `#bd3f12`, crema `#fffaf4`, carbón `#2b221f` y superficies blancas. El acceso y la experiencia participante adoptan la misma dirección mediante contextos separados `.ff-auth-login-page` y `.ff-participant-*`, sin sustituir estilos del panel administrativo. El naranja se usa como fondo con texto carbón o como botón con blanco sólo en su variante oscura/medida; el foco visible nunca depende sólo del color de marca.

La V2 usa radio de 0.7–1.75rem según jerarquía, sombras suaves, tipografía `system-ui` y CTA naranja. Título, fecha, categorías, reglas y premio siempre viven en HTML. No se descarga ni enlaza material de Apple; el único dispositivo visible es un recorte local del cartel aprobado y no se utiliza como fuente jurídica.

## Activos autorizados

| Fuente | SHA-256 | MIME/dimensiones | Alpha | Uso y alt | Derivación/impacto |
|---|---|---|---|---|---|
| `imagen/logo_florecehermosillo.png` | `ae7262…68b37` | PNG 320×320 RGB | No | Marca sobre fondo complejo; alt de identidad cuando aplique | Copia idéntica; pequeña. |
| `imagen/logo_florecehermosillo_transparente.png` | `306ccc…e82e0` | PNG 320×320 RGBA | Sí | Marca sobre superficie clara/oscura con contraste | Copia idéntica. |
| `imagen/logo_flowerflow.png` | `fa4892…aca5` | PNG 320×320 RGB | No | Alternativa de marca | Copia idéntica. |
| `imagen/logo_flowerflow_transparente.png` | `472f0f…baf5` | PNG 320×320 RGBA | Sí | Navbar; `alt="Logo FlowerFlow"` o vacío cuando decorativo | Copia idéntica; 52×52 CSS sin deformar. |
| `imagen/poster_evento.png` | `6fb16d…ebd9` | PNG 1122×1402 RGB | No | Hero; alt resume cartel, fecha/categorías viven en HTML | Copia idéntica; `width/height` evita layout shift; es el mayor LCP. |
| `public/assets/flowerflow/landing/hermosillo-atardecer.webp` | `40c0e8…1e90` | WebP 1680×282 RGB | No | Franja panorámica del hero; `alt` describe Hermosillo al atardecer | Recorte determinista del cartel: origen `(0,500)`, `1122×188`, reescalado Lanczos; no contiene texto. |
| `public/assets/flowerflow/landing/premio-ipad-pro.webp` | `a1b782…2609ba` | WebP 514×757 RGB | No | Composición de premio; decorativa porque el nombre y reglas están en HTML | Recorte determinista del cartel: origen `(820,895)`, `302×445`, reescalado Lanczos; excluye el texto incorrecto “Max”. |

`scripts/publish_authorized_assets.sh` verifica y copia; originales nunca se sobrescriben. La landing, auth, perfil, propuesta y panel deben probarse a 360/768/1440 px, teclado, zoom 200/400 %, foco, errores y reduced motion.

## Landing pública V2 implementada

**Corte:** 2026-07-15. **Alcance original:** sólo `/`, su header y footer públicos. El acceso y el shell participante se rediseñaron después en el milestone documentado a continuación; la landing conserva su encapsulado y no fue sustituida.

- Contenedor central de máximo `73.75rem` (1180 px), flujo vertical natural y secciones con espacio consistente.
- Header sticky con ambos logotipos, anchors reales, login, CTA condicionado por registro y menú móvil con Escape, cierre por selección y `aria-expanded`.
- Hero con título HTML, cierre oficial en `America/Hermosillo`, estados de recepción/registro controlados por flags, CTAs y composición local del premio.
- Categorías servidas por base de datos y fallback seguro de tres categorías cuando no hay competencia activa.
- Proceso 2×2, requisitos 3×2, premio, documentos PDF descargables, FAQ Bootstrap con relaciones ARIA y CTA final.
- Breakpoints por contenido: navegación/hero a 992 px, grillas principales a 768 px y ajuste extremo a 375 px. Se cubre el intervalo 320–1920 px sin ancho fijo de página.
- Íconos: subconjunto de Remix/Iconify ya presente, incrustado como máscaras de datos locales; decorativos con `aria-hidden="true"`. No hay emojis ni dependencia nueva.
- Assets críticos incluyen `width`/`height`; la segunda imagen del premio usa `loading="lazy"`; `prefers-reduced-motion` elimina transiciones perceptibles.
- El cartel original contiene “iPad Pro Max”, pero los PDF oficiales y el HTML autoritativo indican `Apple iPad Pro`. La V2 no muestra ni afirma “Max”.

Pruebas automatizadas: `tests/Feature/PublicLandingTest.php` cubre contenido, assets, PDF, flag público, combinación registro/recepción, fallback, anchors/FAQ y preservación del chrome de otras rutas invitadas.

## Acceso y experiencia participante rediseñados

**Corte:** 2026-07-16. **Alcance:** `/login`, `/panel/login`, shell participante, `/perfil` y `/propuestas`; sin cambios de ruta, esquema o dependencia.

- `/login` usa header de marca dual, tarjeta centrada, mostrar/ocultar accesible con `aria-pressed`, recuperación, registro por flag y banda informativa con texto jurídico/operativo aprobado. `/panel/login` conserva sus textos y omite registro y beneficios de participante.
- El shell participante usa sidebar carbón de 272 px, ambos logotipos, sólo destinos reales, ayuda mediante `mailto`, cierre de sesión y offcanvas Bootstrap en móvil. El chip superior usa nombre, iniciales y rol derivados de la cuenta; no hay fotografía, campana o contador ficticio.
- `/perfil` muestra `100%` únicamente cuando `ParticipantProfile::isComplete()` es verdadero. La mejora progresiva inicia en resumen con JavaScript y habilita edición real por sección; sin JavaScript el formulario completo permanece disponible. Correo readonly, teléfono E.164, edad, residencia y consentimientos conservan sus contratos backend.
- Las preferencias opcionales se separan visualmente de las declaraciones obligatorias. El teléfono se identifica como registrado, nunca verificado, y la franja de privacidad enlaza al PDF vigente sin modificarlo.
- `/propuestas` presenta sólo la relación del usuario autenticado, máximo configurable, estados `Borrador`/`Enviada`, folio real, última actualización convertida a `America/Hermosillo` y acciones reales. Nueva/editar respetan flag, límite y estado.
- Búsqueda por título/categoría y filtro por estado funcionan completamente en cliente para un máximo de tres registros. Sin JavaScript todos permanecen visibles; el contador usa `aria-live` y el listado se transforma en tarjetas en tablet/móvil.
- Remix/Iconify existente aporta iconografía local. Los únicos recursos de imagen son logotipos autorizados con dimensiones reservadas; no se añadieron emojis, imágenes remotas ni activos simulados.

Pruebas automatizadas: `tests/Feature/ParticipantExperienceRedesignTest.php` cubre variantes de acceso, datos/completitud de perfil, aislamiento de propuestas, zona horaria, acciones, vacío, límite y feature flag. La validación visual comparativa queda registrada en `design-qa.md`.

## Inicio participante rediseñado

**Corte:** 2026-07-16. **Alcance:** `/inicio` y el menú compartido del área participante; sin cambios al panel administrativo, rutas públicas, esquema o dependencias.

- El hero usa el nombre completo del perfil y conserva `users.name` como fallback. La franja autorizada `hermosillo-atardecer.webp` y el logotipo vigente aparecen como decoración tenue; el contenido y los datos permanecen en HTML.
- Tres tarjetas muestran conteo propio total, cantidad enviada, límite configurado y completitud real de `ParticipantProfile`. El CTA para crear sólo existe cuando recepción, convocatoria, perfil y límite lo permiten; en otro caso se ofrece perfil o un estado textual no accionable.
- “Siguientes pasos” usa una lista ordenada. Sólo “Crea tu propuesta” se convierte en enlace cuando la acción está disponible; evaluación y resultados son información sin controles muertos.
- “Información importante” usa cierre y categorías activas de base, ordenadas por `sort_order`, además del premio y gratuidad exactos de la mecánica vigente. Sin convocatoria activa, la pantalla conserva un estado informativo y no falla.
- El menú participante de escritorio y offcanvas queda reducido a Inicio, Mis propuestas, Nueva propuesta condicional y Mi perfil. Documentos y preguntas frecuentes se retiraron sólo de ese parcial; `/documentos`, los tres PDF y la FAQ de la landing se conservan.
- Las grillas pasan de tres a dos y una columna según contenido; el hero pierde altura fija en móvil, las acciones mantienen al menos 44 px y no hay overflow horizontal ni dependencia de hover.
- Frente a la imagen se omiten deliberadamente la campana, contadores de notificación, nombre ficticio, “Participa en la evaluación”, laptop y enlaces inexistentes. No se añadieron páginas de evaluación o resultados.

Pruebas automatizadas: `ParticipantExperienceRedesignTest` cubre datos dinámicos, aislamiento, CTA por perfil/límite/flag, ausencia de convocatoria, navegación compartida, redirección privilegiada y preservación pública/administrativa. La comparación visual se registra en `design-qa.md`.

## Asistente de nueva propuesta implementado

**Corte:** 2026-07-16. **Alcance:** creación, edición y revisión del borrador con las rutas ya existentes; sin migraciones, dependencias ni activos nuevos.

- La creación y edición usan un asistente server-rendered de cuatro pasos: (1) modalidad, categoría y datos básicos; (2) descripción; (3) archivos y enlaces; (4) revisión y envío. El progreso es un `nav`/`ol`, anuncia “Paso n de 4”, identifica el paso actual y sólo enlaza pasos anteriores disponibles.
- `wizard_step` y `wizard_action` hacen explícito el contrato. Cada solicitud valida y modifica únicamente su sección; “Guardar borrador” permanece en el paso y “Continuar” avanza. Un parámetro `step` inválido vuelve al paso 1.
- No se simula autoguardado. La interfaz informa “Cambios sin guardar”, advierte antes de abandonar el paso y comunica el guardado después de la respuesta del servidor. Sin JavaScript, los formularios y botones continúan siendo funcionales.
- La modalidad y categoría usan radios nativos dentro de tarjetas. Equipo muestra hasta cuatro personas adicionales porque la cuenta representante integra el máximo de cinco; la declaración de elegibilidad sigue siendo obligatoria sólo para equipo.
- Título, resumen y descripción obtienen sus límites de `config/flowerflow.php`; los contadores son ayuda visual y los Form Requests mantienen la autoridad. Quill sincroniza Delta, HTML y texto; el servidor sanitiza antes de persistir y vuelve a sanitizar al presentar.
- Documentos e imágenes conservan inputs `multiple` nativos. Arrastrar/soltar, listar, quitar y previsualizar imágenes son mejoras progresivas. Todos los archivos permanecen privados, con nombre interno aleatorio, firma/MIME/allowlist y descarga/eliminación autorizadas.
- La cuota configurada de 10 MiB se calcula sobre documentos e imágenes existentes más los recién seleccionados. El borrador admite paso 3 vacío, pero la revisión y la acción final exigen al menos un archivo con `kind=document`.
- YouTube acepta sólo HTTPS y hosts exactos configurados, rechaza credenciales embebidas y genera la vista previa cliente con `youtube-nocookie.com`, `loading=lazy` y sin autoplay. La aplicación no consulta URLs externas. La carpeta pública conserva su allowlist y una advertencia contra PII.
- La CSP conserva `default-src 'self'`, permite `blob:` únicamente en `img-src` para miniaturas locales y limita `frame-src` exclusivamente a `https://www.youtube-nocookie.com`; no se añadieron CDN, trackers ni `connect-src` externos.
- La revisión reutiliza `FinalizeSubmission`: aceptaciones jurídicas separadas, folio, snapshot inmutable, idempotencia, evento y correo post-commit no cambiaron. Enviar sigue siendo irreversible y una propuesta enviada no se puede editar.
- En móvil, el encabezado usa ambos logotipos y la cuenta real; el stepper adopta etiquetas cortas, tarjetas/ayuda se apilan y acciones/archivos se reorganizan sin alterar el orden semántico. No se incorporaron campana, usuario ficticio ni estado de autoguardado de las referencias.

Pruebas automatizadas: `tests/Feature/SubmissionWizardTest.php` cubre renderizado/pasos, perfil, equipo, categoría ajena, preservación por sección, sanitización, guardado parcial, hosts, credenciales, cuota compartida, requisito documental, propietario y estado inmutable. `tests/Feature/SubmissionFlowTest.php` recorre creación, contenido, archivo y finalización mediante el nuevo contrato.

**Fecha de corte:** 2026-07-16
**Estado:** baseline histórica más landing pública V2 y experiencia participante implementadas; los módulos futuros permanecen como diseño de experiencia
**Etiquetas:** `DECISION` = confirmado; `ASSUMPTION` = dirección recomendada; `PENDING` = requiere insumo o aprobación.

## Limitación del insumo

**PENDING:** el texto base comienza truncado y omite la introducción y los módulos 1–6. El mapa de páginas reconstruye esos módulos para poder planificar. Debe reconciliarse con la fuente completa antes de convertir wireframes o rutas propuestas en contrato.

## Punto de partida comprobado

- **DECISION:** el repositorio usa Materialize 3.0.0 con Bootstrap 5 y Vite.
- **DECISION:** existen un layout público (`resources/views/layouts/layoutFront.blade.php`) y layouts administrativos, incluido el vertical.
- **DECISION:** `config/custom.php` mantiene customizer activo y configuración demo; debe desactivarse en producción durante implementación aprobada.
- **DECISION:** `resources/menu/verticalMenu.json` conserva navegación demo.
- **DECISION:** las rutas actuales son páginas base/demo, idioma, error, login y registro; ningún flujo de negocio descrito aquí está implementado todavía.
- **PENDING:** confirmar starter kit/full version, licencia, componentes realmente autorizados y assets de marca.

## Principios de experiencia

1. **Claridad temporal:** el participante siempre entiende si la convocatoria no abrió, está abierta o cerró, con fecha y zona horaria explícitas.
2. **Progreso recuperable:** un fallo de red o validación no debe hacer perder el borrador.
3. **Privacidad visible:** explicar por qué se solicita un dato y quién puede verlo, especialmente residencia.
4. **Una acción crítica, una confirmación:** envío, evaluación, reapertura, ganador y publicación requieren resumen y confirmación.
5. **Mínimo privilegio perceptible:** cada rol ve sólo las tareas y datos que necesita.
6. **Accesibilidad estructural:** semántica, teclado, foco, errores y contraste se diseñan desde el componente, no como revisión final.
7. **Móvil primero en participante:** registro, wizard, carga y seguimiento deben funcionar en un teléfono.
8. **Densidad controlada en backoffice:** tablas potentes sin ocultar estado, permisos o siguiente acción.

## Arquitectura de información

### Sitio público

| Página propuesta | Ruta conceptual | Propósito | Fase |
| --- | --- | --- | --- |
| Inicio | `/` | Propuesta, estado, fechas, CTA y categorías. | MVP |
| Convocatoria | `/convocatoria` | Resumen de bases, elegibilidad y proceso. | MVP |
| Categorías | `/categorias` | Describir categorías activas. | MVP |
| Categoría | `/categorias/{slug}` | Detalle y reglas específicas. | MVP |
| Cómo participar | `/como-participar` | Pasos, requisitos y archivos. | MVP |
| Calendario | `/calendario` | Fechas en `America/Hermosillo`. | MVP |
| Preguntas frecuentes | `/preguntas-frecuentes` | Resolver dudas aprobadas. | MVP |
| Bases/documentos | `/documentos` | Versiones vigentes descargables. | MVP |
| Contacto | `/contacto` | Canal de convocatoria sin exponer datos. | MVP si se aprueba formulario |
| Privacidad | `/privacidad` | Aviso, derechos y canal de solicitud. | MVP |
| Resultados | `/resultados` | Ganadores autorizados; apagada por defecto. | MVP recortable |
| Archivo 2026 | `/ediciones/2026` | Registro público mínimo de edición. | Fase 2 o MVP recortable |
| Galería | `/proyectos` | Proyectos autorizados. | Fase 2 |

### Autenticación y cuenta

| Página | Ruta conceptual | Consideraciones |
| --- | --- | --- |
| Registro | `/registro` | Perfil mínimo desde el alta, teléfono México `+52`, documentos descargables, consentimientos, contraseña mínima de 8 con checklist visual/accesible, confirmación y mostrar/ocultar; mensajes no enumerables. |
| Verificar correo | `/correo/verificar` y `/correo-verificado` | Reenvío con rate limit, estado comprensible y confirmación amigable al abrir el enlace firmado. |
| Login | `/login` | Recuperación visible; soporte a 2FA cuando aplique. |
| Recuperar/restablecer | `/contrasena/*` | No confirmar existencia de cuenta. |
| 2FA | `/cuenta/2fa` | Roles privilegiados; códigos de recuperación seguros. |
| Perfil | `/cuenta/perfil` | Datos mínimos, actualización y contexto de privacidad. |
| Seguridad/sesiones | `/cuenta/seguridad` | Contraseña y revocación de sesiones. |

### Participante

| Página | Ruta conceptual | Tarea principal |
| --- | --- | --- |
| Dashboard | `/participante` | Ver convocatoria, pendientes y proyectos. |
| Elegibilidad/perfil | `/participante/perfil` | Revisar o actualizar los datos capturados en registro y las preferencias reversibles. |
| Mis proyectos | `/participante/proyectos` | Listar borradores, enviados y estados. |
| Nuevo proyecto | `/participante/proyectos/crear` | Iniciar wizard. |
| Wizard | `/participante/proyectos/{id}/editar/{paso?}` | Completar y guardar pasos. |
| Vista previa | `/participante/proyectos/{id}/vista-previa` | Revisar versión antes de enviar. |
| Confirmación/folio | `/participante/proyectos/{id}/acuse` | Acuse y siguientes pasos. |
| Detalle/seguimiento | `/participante/proyectos/{id}` | Estado, historial visible y correcciones. |
| Equipo | `/participante/proyectos/{id}/equipo` | Invitaciones si se aprueban. |
| Archivos | integrado al wizard | Upload, validación, privacidad y progreso. |

### Juez

| Página | Ruta conceptual | Tarea principal |
| --- | --- | --- |
| Dashboard | `/juez` | Asignaciones pendientes, en borrador y finalizadas. |
| Instrucciones/rúbrica | `/juez/instrucciones` | Mostrar versión vigente. |
| Proyecto asignado | `/juez/asignaciones/{id}` | Vista anónima y anexos autorizados. |
| Conflicto | modal o sección en asignación | Declarar conflicto antes de evaluar. |
| Evaluación | `/juez/asignaciones/{id}/evaluacion` | Puntuar, comentar y guardar borrador. |
| Confirmar envío | paso final | Revisar puntajes/comentarios y confirmar. |
| Historial | `/juez/evaluaciones` | Sólo evaluaciones propias. |

### Administración y revisión

| Página | Ruta conceptual | Roles |
| --- | --- | --- |
| Dashboard | `/admin` | Según permisos |
| Convocatoria | `/admin/convocatorias/*` | Administrador |
| Categorías | `/admin/categorias/*` | Administrador |
| Participantes | `/admin/participantes` | Acceso autorizado |
| Proyectos | `/admin/proyectos` | Administrador/revisor |
| Detalle de proyecto | `/admin/proyectos/{id}` | Según Policy |
| Revisión de elegibilidad | `/admin/elegibilidad/{id}` | Revisor |
| Jueces | `/admin/jueces` | Administrador |
| Asignaciones | `/admin/asignaciones` | Administrador |
| Rúbricas | `/admin/rubricas` | Administrador autorizado |
| Evaluaciones | `/admin/evaluaciones` | Permiso explícito |
| Ganadores | `/admin/ganadores` | Declarar/publicar con permisos separados |
| Comunicaciones | `/admin/comunicaciones` | Permiso de envío |
| Reportes/exports | `/admin/reportes` | Auditor/administrador |
| Auditoría | `/admin/auditoria` | Auditor |
| Privacidad | `/admin/privacidad` | Soporte de privacidad |
| Usuarios/roles | `/admin/acceso/*` | Superadministrador |
| Configuración | `/admin/configuracion` | Permiso explícito |

### Estados de sistema

- `/404`, `/419`, `/429` y `/500` con identidad y siguiente acción segura.
- Estado sin permiso separado de recurso inexistente cuando no filtre información.
- Mantenimiento, convocatoria cerrada y enlace expirado.
- Sin resultados publicados.
- Sin asignaciones, proyectos o datos de tabla.

## Navegación por rol

### Pública

Logo/inicio, convocatoria, categorías, cómo participar, calendario, FAQ, documentos, privacidad, entrar/registrarse.

### Participante

Resumen, mi perfil/elegibilidad, mis proyectos, documentos/ayuda y cuenta. La acción “Nuevo proyecto” sólo aparece si la convocatoria y límites lo permiten.

### Juez

Resumen, asignaciones, instrucciones/rúbrica, historial y cuenta. No incluir participantes, comprobantes, configuración o ranking.

### Backoffice

La navegación se genera según permiso, no sólo rol. Debe agrupar operación, evaluación, comunicaciones/reportes y configuración. La búsqueda estática sólo indexa destinos de navegación, nunca nombres, correos, folios o datos dinámicos.

## Flujos UX críticos

### 1. Descubrimiento y registro

1. Visitante llega a inicio y ve estado/fecha.
2. Revisa requisitos y documentos antes de registrarse.
3. Completa sus datos de participante, teléfono México `+52`, contraseña y consentimientos en un solo formulario con labels y ayuda.
4. Recibe pantalla neutral para revisar su correo.
5. Verifica correo y ve una confirmación amigable antes de iniciar sesión o continuar.

**Aceptación:** funciona con teclado, lector de pantalla y móvil; errores no borran valores seguros; no se enumera correo.

### 2. Perfil y residencia

1. Explicar qué dato se solicita, finalidad, visibilidad y retención pendiente.
2. Revisar o corregir el perfil mínimo capturado en registro.
3. Cargar comprobante privado con formatos/límites visibles.
4. Confirmar recepción, no “aprobación”.
5. Mostrar estado: pendiente, corrección, elegible o no elegible.

**Aceptación:** jamás presentar el comprobante dentro de vistas de juez; corrección indica motivo y plazo.

### 3. Wizard de proyecto

**DECISION Fase 01:** cuatro pasos implementados:

1. Modalidad, categoría y datos básicos.
2. Descripción y contenido.
3. Archivos y enlaces.
4. Revisión, aceptaciones y envío.

Patrones:

- Título de paso, propósito, progreso textual “Paso n de total” y lista accesible.
- Guardado explícito con acción secundaria “Guardar borrador”; no afirmar autoguardado sin endpoint/versionado aprobado.
- Estado “Cambios sin guardar / Guardando / Borrador guardado”, respaldado por respuesta real.
- Navegar hacia atrás sin pérdida.
- Resumen de errores al inicio y error asociado a campo.
- Campos condicionales anunciados y con foco razonable.
- Vista previa equivalente a la versión que se enviará.
- Confirmación crítica explica que el envío crea versión y folio.

### 4. Revisión administrativa

1. Tabla server-side con filtros y columnas mínimas.
2. Abrir detalle conservando filtros/página.
3. Distinguir contenido del proyecto, residencia y notas internas.
4. Revisar una versión fija.
5. Elegir elegible, no elegible o corrección.
6. Escribir razón y confirmar.
7. Mostrar resultado y registro auditable.

### 5. Evaluación de juez

1. Ver asignación y estado.
2. Declarar ausencia o presencia de conflicto.
3. Consultar proyecto anónimo y anexos autorizados.
4. Completar cada criterio con rango, peso y ayuda.
5. Ver total calculado por servidor como información, no como ranking.
6. Guardar borrador.
7. Revisar resumen y confirmar envío.

**Aceptación:** conflicto desactiva evaluación; un juez no ve asignaciones ajenas; reapertura se comunica de forma explícita.

### 6. Ganador y publicación

1. Personal autorizado consulta consolidado.
2. Declara decisión con justificación separada del cálculo.
3. Revisa vista previa pública sin PII indebida.
4. Segundo permiso/acción publica.
5. Interfaz confirma fecha, actor y alcance.

**Aceptación:** el módulo está apagado por defecto y no existe publicación automática.

## Componentes y uso de Materialize

| Necesidad | Componente recomendado | Condición |
| --- | --- | --- |
| Estructura | Bootstrap 5 | Ya incluido; mantener semántica. |
| Wizard | `bs-stepper` o implementación ligera | Auditar accesibilidad antes de elegir. |
| Validación visual | FormValidation | Siempre junto a Form Requests de servidor. |
| Upload | Input estándar o Dropzone | Elegir mínimo; no comprometer teclado ni fallback. |
| Tablas | DataTables server-side | Sólo backoffice y conjuntos grandes. |
| Catálogos | Select2 | Sólo si el volumen justifica; label y teclado probados. |
| Fechas | Flatpickr | Entrada manual y formato accesible disponibles. |
| Confirmaciones | SweetAlert2 | Sólo acciones críticas; manejo de foco. |
| Toasts | Notyf | No usar como único canal para errores importantes. |
| Bloqueo/carga | Notiflix sólo si es necesario | Evitar duplicar con Notyf. |
| Iconos | Iconify | Icono decorativo oculto; texto para acciones ambiguas. |
| Gráficas | ApexCharts o Chart.js | Elegir una; datos equivalentes en texto/tabla. |
| Texto enriquecido | Quill | Sólo con aprobación y sanitización estricta. |

**DECISION:** no cargar todos los plugins por defecto. Importar por página y registrar la razón de cualquier dependencia nueva. No modificar el core de la plantilla; overrides Flower Flow van separados.

## Identidad visual

### Dirección

**ASSUMPTION:** lenguaje inspirado conceptualmente en naranja intenso, crema cálido, carbón oscuro y atardecer de Hermosillo.

**PENDING:** archivos oficiales de logo, tipografías, fotografías, licencia y manual de marca.

### Tokens provisionales

Los valores finales deben medirse en contexto; no se consideran aprobados por aparecer aquí.

| Token | Uso propuesto |
| --- | --- |
| `--ff-color-primary` | Acción principal naranja accesible. |
| `--ff-color-primary-hover` | Hover/foco con diferencia perceptible. |
| `--ff-color-surface-warm` | Fondo crema claro. |
| `--ff-color-text` | Carbón de alto contraste. |
| `--ff-color-success` | Confirmaciones, acompañadas de icono/texto. |
| `--ff-color-warning` | Fechas/pendientes, nunca único indicador. |
| `--ff-color-danger` | Errores/acciones destructivas con texto. |
| `--ff-focus-ring` | Anillo visible en fondos claros y oscuros. |

### Restricciones de assets

- No extraer/copiar assets del cartel fuera de los derivados locales autorizados y documentados para la landing V2.
- No descargar, enlazar ni incorporar imágenes o logotipos de Apple. El recorte del dispositivo proviene exclusivamente del cartel entregado por el usuario.
- Placeholders deben identificarse como tales.
- Fotografías con texto necesitan overlay y prueba de contraste.
- Imágenes optimizadas a WebP/AVIF cuando sea viable, con dimensiones y `alt`.

## Diseño responsive

| Rango | Comportamiento |
| --- | --- |
| Móvil | Una columna; acciones críticas visibles; tablas como tarjetas/lista accesible cuando corresponda; uploads tolerantes a cámara/archivos. |
| Tablet | Dos columnas sólo si preservan orden lógico; stepper compacto. |
| Escritorio | Contenedor legible; backoffice puede usar mayor densidad y panel lateral. |

- No fijar acciones de forma que oculten contenido o teclado virtual.
- Evitar modales para formularios largos.
- Mantener objetivos táctiles de al menos 24×24 CSS px, procurando 44×44 en acciones principales.
- Probar zoom a 200 %, reflow a 320 CSS px y orientación.

## WCAG 2.2 AA — criterios obligatorios

### Estructura y teclado

- [ ] Un `h1` por página y jerarquía de encabezados coherente.
- [ ] Landmarks (`header`, `nav`, `main`, `footer`) y enlace “Saltar al contenido”.
- [ ] Orden DOM coincide con orden visual.
- [ ] Toda acción funciona con teclado sin trampa.
- [ ] Foco visible, no oculto por headers fijos y restaurado al cerrar modal.
- [ ] Menús, tabs, acordeones y stepper exponen estado accesible.

### Formularios y errores

- [ ] Cada input tiene `label` persistente; placeholder no lo sustituye.
- [ ] Ayuda y error se asocian con `aria-describedby` cuando aplica.
- [ ] Campos requeridos se indican en texto y semántica.
- [ ] Error por campo más resumen con enlaces/foco.
- [ ] Mensajes no dependen sólo del color.
- [ ] Validación cliente complementa, no reemplaza, servidor.
- [ ] Tiempo de sesión/cierre se comunica y permite recuperar borrador cuando sea seguro.

### Visual y contenido

- [ ] Texto normal cumple contraste 4.5:1; texto grande 3:1.
- [ ] Componentes/estados/foco alcanzan 3:1 donde WCAG lo exige.
- [ ] Zoom 200 % y reflow sin pérdida funcional.
- [ ] `alt` describe propósito; decorativas usan `alt=""`.
- [ ] Contenido usa lenguaje claro, fechas completas y zona horaria.
- [ ] No hay animación intrusiva; respetar `prefers-reduced-motion`.

### Componentes complejos

- [ ] Modales tienen nombre, foco inicial razonable, contención y retorno de foco.
- [ ] DataTables ofrece encabezados, caption/contexto, navegación y alternativa móvil usable.
- [ ] Stepper anuncia paso actual, errores y progreso sin depender de iconos.
- [ ] Upload permite input estándar/fallback, estado textual y cancelación.
- [ ] Gráficas tienen resumen y tabla equivalente.
- [ ] Toasts importantes se reflejan de forma persistente y usan live regions prudentes.
- [ ] Contador de cierre no actualiza agresivamente; el deadline textual es la fuente principal.

### Autenticación

- [ ] Password managers y pegar contraseñas están permitidos.
- [ ] Campos tienen `autocomplete` correcto.
- [ ] CAPTCHA, si se aprueba, tiene alternativa accesible.
- [ ] 2FA ofrece instrucciones y recuperación accesibles.

## Estados de interfaz obligatorios

Cada pantalla de datos debe diseñar:

- carga inicial;
- vacío con siguiente acción;
- éxito;
- error recuperable;
- error no recuperable;
- sin conexión/autosave pendiente;
- sin permiso;
- recurso no disponible;
- convocatoria cerrada;
- enlace/invitación expirado;
- rate limit;
- sesión expirada/419;
- mantenimiento.

No usar skeleton indefinido. Los errores deben incluir qué ocurrió, qué se preservó y qué puede hacer la persona.

## Contenido y tono

- Español directo, respetuoso y no legalista salvo textos aprobados.
- Botones nombran acción: “Guardar borrador”, “Enviar proyecto”, “Declarar conflicto”.
- Acciones irreversibles explican consecuencia y objeto.
- No usar “Aprobado” cuando sólo se recibió un archivo.
- Fechas: “15 de agosto de 2026, 23:59 (hora de Hermosillo)” una vez aprobada la hora.
- Correos y pantallas no incluyen comprobantes ni PII sensible innecesaria.

## SEO y privacidad de navegación

### Indexable

- Inicio, convocatoria, categorías, proceso, calendario, FAQ, documentos públicos y resultados publicados.

### `noindex`

- Login/registro técnico cuando corresponda, cuenta, participante, juez, administración, staging, previews y URLs firmadas.

### Reglas

- Título/metadescripción únicos.
- Canonical y slugs estables.
- Sitemap sólo con páginas públicas activas.
- Open Graph con asset autorizado.
- Búsqueda pública estática nunca incluye PII o datos dinámicos.

## Aceptación UX por recorrido

| Recorrido | Dispositivos | Teclado/lector | Estados/error | Criterio |
| --- | --- | --- | --- | --- |
| Registro/verificación | móvil y escritorio | obligatorio | correo existente neutral, rate limit | Completable sin asistencia. |
| Perfil/residencia | móvil y escritorio | obligatorio | archivo inválido, reintento | Privacidad y estado comprensibles. |
| Wizard/envío | móvil y escritorio | obligatorio | guardado explícito, error, cierre | Sin pérdida entre pasos y versión confirmada. |
| Revisión | escritorio/tablet | obligatorio | sin permiso, transición inválida | Datos separados y decisión auditada. |
| Evaluación | escritorio/tablet/móvil razonable | obligatorio | conflicto, cierre, reapertura | Sólo asignación propia; cálculo accesible. |
| Publicación | escritorio | obligatorio | apagado, preview, doble confirmación | Sin PII y sin publicación automática. |

### Cierre visual del área participante — 2026-07-16

El usuario responsable confirmó la ejecución y aceptación del QA visual y responsive de acceso, inicio, perfil, listado de propuestas y asistente de cuatro pasos. El cierre comprende móvil, tablet y escritorio, así como estados representativos, teclado, foco, zoom al 200 %, reflow, overflow horizontal, `prefers-reduced-motion`, consola y controles interactivos.

- [x] Referencias y vistas completas comparadas.
- [x] Menú lateral y offcanvas participante revisados.
- [x] Matriz responsive y estados del área participante cubierta.
- [x] Flujo de propuesta revisado por teclado y en pantallas táctiles.
- [x] Foco, zoom, reflow, reduced motion y ausencia de overflow revisados.
- [x] Consola, assets, enlaces y acciones principales revisados.
- [x] Sin hallazgos P0, P1 o P2 reportados al cierre.

La aceptación se registra como UAT manual confirmada por el usuario. No se incorporaron capturas al repositorio; el detalle, las referencias y el historial de bloqueos previos se conservan en `design-qa.md`. Este cierre aplica al área participante y no sustituye las revisiones WCAG o UAT todavía pendientes para módulos futuros fuera de la Fase 01.

## Preguntas UX pendientes

- **RESOLVED Fase 01:** campos y flujo definidos por prompt v2; ver perfil/propuesta y docs 01/14.
- **RESOLVED/PENDING:** cierre 23:59:59 Hermosillo; no se implementa contador hasta tener apertura aprobada.
- **RESOLVED Fase 01:** individual o equipo de hasta cinco, representante incluida; una cuenta opera el envío.
- **PENDING:** ¿evaluación ciega y qué metadatos deben anonimizarse?
- **PENDING:** ¿qué datos del ganador tienen consentimiento para publicación?
- **PARTIAL:** cinco PNG autorizados recibidos y documentados; manual de marca y licencia Pixinvent siguen pendientes.
- **PENDING:** ¿contenido administrable o por despliegue?
- **PENDING:** ¿CAPTCHA y proveedor?
- **PENDING:** navegadores/dispositivos de UAT y necesidades de accesibilidad conocidas.

## Definition of Done de diseño

- [ ] El fragmento faltante fue reconciliado o los supuestos fueron aprobados.
- [ ] Mapa de páginas y navegación por rol aprobados.
- [ ] Campos, contenido, límites y estados definidos.
- [ ] Wireframes de recorridos críticos revisados por producto.
- [ ] Tokens pasan contraste en sus usos reales.
- [ ] Componentes seleccionados superan revisión de teclado y lector de pantalla.
- [ ] Matriz de responsive, estados y errores cubierta.
- [ ] Pruebas WCAG 2.2 AA y browser están trazadas.
- [ ] Assets/licencias y textos legales aprobados.
- [ ] No se implementó ni desplegó como parte de esta fase documental.
