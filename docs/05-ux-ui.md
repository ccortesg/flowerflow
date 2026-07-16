# UX/UI, accesibilidad e identidad — Flower Flow 2026

## Sistema visual Fase 01

Tokens derivados del poster/logos: carbón `#17352f`, verde `#167c5b`, verde oscuro `#0b5c42`, lima `#d9ed55`, coral `#ff765f` y crema `#fffdf5`. Carbón/blanco y verde oscuro/blanco superan 7:1 aproximadamente; verde principal/blanco supera 4.5:1 para texto normal. Lima y coral se usan como superficies/acento con texto carbón, no como texto claro. Se requiere confirmar con auditoría browser antes de declarar WCAG.

Radio principal 1.25–1.5rem, sombras suaves, jerarquía system-ui y CTA verde. El poster es la pieza hero principal, pero título, fecha, categorías y premio se repiten en HTML. No se usa logo/imagen de Apple.

## Activos autorizados

| Fuente | SHA-256 | MIME/dimensiones | Alpha | Uso y alt | Derivación/impacto |
|---|---|---|---|---|---|
| `imagen/logo_florecehermosillo.png` | `ae7262…68b37` | PNG 320×320 RGB | No | Marca sobre fondo complejo; alt de identidad cuando aplique | Copia idéntica; pequeña. |
| `imagen/logo_florecehermosillo_transparente.png` | `306ccc…e82e0` | PNG 320×320 RGBA | Sí | Marca sobre superficie clara/oscura con contraste | Copia idéntica. |
| `imagen/logo_flowerflow.png` | `fa4892…aca5` | PNG 320×320 RGB | No | Alternativa de marca | Copia idéntica. |
| `imagen/logo_flowerflow_transparente.png` | `472f0f…baf5` | PNG 320×320 RGBA | Sí | Navbar; `alt="Logo FlowerFlow"` o vacío cuando decorativo | Copia idéntica; 52×52 CSS sin deformar. |
| `imagen/poster_evento.png` | `6fb16d…ebd9` | PNG 1122×1402 RGB | No | Hero; alt resume cartel, fecha/categorías viven en HTML | Copia idéntica; `width/height` evita layout shift; es el mayor LCP. |

`scripts/publish_authorized_assets.sh` verifica y copia; originales nunca se sobrescriben. La landing, auth, perfil, propuesta y panel deben probarse a 360/768/1440 px, teclado, zoom 200/400 %, foco, errores y reduced motion.

**Fecha de corte:** 2026-07-15  
**Estado:** diseño de experiencia para revisión; no representa pantallas implementadas  
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
| Registro | `/registro` | Contraseña mínima de 8 con checklist visual/accesible, confirmación y mostrar/ocultar; mensajes no enumerables. |
| Verificar correo | `/correo/verificar` | Reenvío con rate limit y estado comprensible. |
| Login | `/login` | Recuperación visible; soporte a 2FA cuando aplique. |
| Recuperar/restablecer | `/contrasena/*` | No confirmar existencia de cuenta. |
| 2FA | `/cuenta/2fa` | Roles privilegiados; códigos de recuperación seguros. |
| Perfil | `/cuenta/perfil` | Datos mínimos, actualización y contexto de privacidad. |
| Seguridad/sesiones | `/cuenta/seguridad` | Contraseña y revocación de sesiones. |

### Participante

| Página | Ruta conceptual | Tarea principal |
| --- | --- | --- |
| Dashboard | `/participante` | Ver convocatoria, pendientes y proyectos. |
| Elegibilidad/perfil | `/participante/perfil` | Completar datos y residencia. |
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
3. Completa formulario breve con labels y ayuda.
4. Recibe pantalla neutral de confirmación.
5. Verifica correo y vuelve al siguiente paso correcto.

**Aceptación:** funciona con teclado, lector de pantalla y móvil; errores no borran valores seguros; no se enumera correo.

### 2. Perfil y residencia

1. Explicar qué dato se solicita, finalidad, visibilidad y retención pendiente.
2. Capturar perfil mínimo.
3. Cargar comprobante privado con formatos/límites visibles.
4. Confirmar recepción, no “aprobación”.
5. Mostrar estado: pendiente, corrección, elegible o no elegible.

**Aceptación:** jamás presentar el comprobante dentro de vistas de juez; corrección indica motivo y plazo.

### 3. Wizard de proyecto

**ASSUMPTION:** pasos recomendados:

1. Categoría y datos básicos.
2. Participación/equipo.
3. Descripción y contenido.
4. Anexos.
5. Revisión de elegibilidad.
6. Resumen y aceptaciones.
7. Envío.

Patrones:

- Título de paso, propósito, progreso textual “Paso n de total” y lista accesible.
- Guardado automático más acción manual secundaria.
- Estado “Guardando / Guardado / No se pudo guardar”.
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

- No extraer/copiar assets del cartel sin licencia.
- No usar imágenes ni logotipos de Apple o iPad Pro sin autorización.
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
| Wizard/envío | móvil y escritorio | obligatorio | autosave, reconexión, cierre | Sin pérdida y versión confirmada. |
| Revisión | escritorio/tablet | obligatorio | sin permiso, transición inválida | Datos separados y decisión auditada. |
| Evaluación | escritorio/tablet/móvil razonable | obligatorio | conflicto, cierre, reapertura | Sólo asignación propia; cálculo accesible. |
| Publicación | escritorio | obligatorio | apagado, preview, doble confirmación | Sin PII y sin publicación automática. |

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
