# Reconciliación jurídica v1.0 -> v1.1 — 2026-08-17

**Estado:** análisis técnico completo de los PDF; incorporación v1.1 local/test; cantidades de cuatro categorías/cuatro propuestas confirmadas. El 2026-08-18 el propietario cerró también accesibilidad, continuidad de aceptaciones v1.0 y designación del PDF físico v1.0. Producción/despliegue permanece sin ejecutar y fuera del alcance de Codex; el propietario solicitó únicamente un runbook de instalación y compilación.

**Alcance:** evidencia documental y técnica. No sustituye revisión o decisión jurídica, no autoriza producción y no interpreta contenido ausente.

## Método y evidencia

Los seis PDF publicados en `public/documentos/2026/` se validaron con `pdfinfo`, `pdftotext`, SHA-256 y renderizado PNG. Se inspeccionaron visualmente las 28 páginas para confirmar encabezados, tablas, notas de versión, fechas, pies y legibilidad. La Mecánica v1.1 se volvió a extraer y se volvieron a inspeccionar visualmente sus cinco páginas a 150 DPI después de cada reemplazo proporcionado durante esta ejecución. Ningún PDF está cifrado, es symlink o escapa del repositorio; todos son PDF 1.7, tamaño A4 y se renderizan sin texto recortado, superpuesto, glifos rotos o páginas ilegibles.

Los archivos `:Zone.Identifier` de 25 bytes presentes junto a v1.1 son metadatos de Windows. No forman parte del inventario jurídico, no se enlazan y deben excluirse de cualquier build, commit o release.

## Inventario inmutable

| Documento | Versión | Páginas | Bytes | SHA-256 | Estado técnico |
|---|---:|---:|---:|---|---|
| Mecánica | 1.0 publicada actualmente | 5 | 890682 | `3bcf31ece0bd1bdbf4392908a27ec3812495dfa588091e9bbce9f7c4ea1e5cb3` | HISTÓRICO con incidente de integridad descrito abajo |
| Términos | 1.0 | 4 | 875523 | `ca5fdb36f7a35f8268458144348e66485e8870f55a2bdd9da59137143ef4f28c` | HISTÓRICO |
| Aviso de Privacidad | 1.0 | 5 | 893697 | `056355c0405984a239e97b5074fc6b78eef61570022f8f94c062919620cc6898` | HISTÓRICO |
| Mecánica | 1.1 | 5 | 866607 | `11c399ca84735d7dbcb17174e192582c93589afa5100c0250753ca15def4db36` | VIGENTE en el release candidate local |
| Términos | 1.1 | 4 | 844116 | `4e3e6c272f9459b934004168bfccc26d6759a4dbce6c804c03afbb86cda6b144` | VIGENTE en el release candidate local |
| Aviso de Privacidad | 1.1 | 5 | 874312 | `041ae9704f80a0108ee69bb39b8646ee8098134573a730f801e4057642ae2da1` | VIGENTE en el release candidate local |

Las copias v1.0 actuales de `formatos/` coinciden byte a byte con las publicadas actualmente. No existen copias v1.1 en `formatos/`; los originales nuevos proporcionados están en `public/documentos/2026/` y no fueron modificados.

### Reemplazos pre-release observados de Mecánica v1.1

Durante esta ejecución el propietario reemplazó dos veces el mismo archivo v1.1 antes de cerrar el candidato. Se observaron, en orden: `0101aa79…` (tres categorías/tres propuestas), `1b537e42…` (cuatro categorías, pero aún dos referencias a tres) y el artefacto actual `11c399ca…` (cuatro categorías y máximo cuatro). No se alteró ninguno de esos PDF desde Codex. Las aceptaciones y archivos sintéticos creados durante la UAT previa a la última sustitución se eliminaron mediante `migrate:fresh` sobre `flowerflow_testing`; no hubo datos reales ni cambios productivos. El hash que debe congelarse para este candidato es únicamente `11c399ca…`.

## Incidente de integridad histórico v1.0

`VERIFIED`: la Mecánica v1.0 incorporada en `5d62f6f` tenía SHA-256 `42bd5ea13e491dc64a6520f0e26d9663e8e8f973b35a3febf226999118685aa2`, que sigue registrado en el seeder y pudo quedar ligado a aceptaciones. El commit `dca0bfd` cambió el binario bajo el mismo nombre/versión a `3bcf31…` para incluir la cuarta categoría, sin actualizar versión ni registro jurídico. Por ello, el PDF v1.0 actualmente descargable no demuestra el contenido exacto asociado al hash histórico `42bd5e…`.

Tratamiento actualizado por decisión del propietario del 2026-08-18: no se reescribe el registro v1.0, ningún hash ni aceptación. El archivo físico que se conserva como v1.0 es el actual `3bcf31…`; `42bd5e…` permanece como hash histórico registrado y la discrepancia se conserva visible. No se recupera ni publica otra copia como parte de este release.

## Matriz de cambios v1.0 -> v1.1

| ID | Documento y evidencia | Cambio exacto | Superficies afectadas | Tratamiento local/test | Estado |
|---|---|---|---|---|---|
| LEGAL-V11-ORG-001 | Mecánica pp. 1-2 y 5; Términos pp. 1-2 y 4; Aviso pp. 1-2 y 5 | El responsable pasa de una referencia genérica a `FUNXT, A.C.`, RFC `FUN110208BT0`, nombre comercial `FLORECE HERMOSILLO`, movimiento ciudadano `FLOWER FLOW` y domicilio `ESPOLI 6 TOSCANA, BLVD. MORELOS Y SANTA CECILIA, C.P. 83143, Hermosillo, Sonora, México.` | Configuración, landing, `/documentos`, footer público, panel y documentación | Datos centralizados y mostrados literalmente; vínculos de contacto no cambiaron | VERIFIED / IMPLEMENTED |
| LEGAL-V11-DEADLINE-001 | Mecánica pp. 1, 3 y 5; Términos p. 2, Alcance | El cierre cambia de 15 a 23 de agosto de 2026 a las 23:59, tiempo de Hermosillo | Config, base, landing, dashboard, middleware y pruebas | Ya estaba implementado como `2026-08-23 23:59:59 America/Hermosillo` / `2026-08-24 06:59:59 UTC`; v1.1 regulariza la fecha | VERIFIED / IMPLEMENTED |
| LEGAL-V11-VERSION-001 | Portadas/pies de los tres PDF | Mecánica y Términos: v1.1 vigente desde 14-ago-2026; Aviso: v1.1 vigente desde 11-ago-2026; sustituyen v1.0 | `legal_documents`, seeder, migración, links, UI y aceptaciones nuevas | Tres registros v1.1 inmutables y activos; v1.0 permanece inactivo e histórico | VERIFIED / IMPLEMENTED |
| LEGAL-V11-CATEGORY-001 | Mecánica p. 2, sección 2 | La versión corregida enumera cuatro categorías, incluida Hermosillo sin barreras. Movilidad y Hermosillo sin Barreras incluyen referencias a accesibilidad | Landing, seeder, wizard, dashboard, panel, correo, snapshots y pruebas | Cuatro categorías y la redacción temática se conservan sin recategorizar ni cambiar la operación, por decisión expresa del propietario | VERIFIED / OWNER ACCEPTED |
| LEGAL-V11-LIMIT-001 | Mecánica p. 2, sección 3, y p. 3, sección 5 | “Una propuesta por categoría, con un máximo de cuatro propuestas” y “una de las cuatro categorías” | Config, bloqueo transaccional, UI, pruebas concurrentes y estados de cuentas | La aplicación ya opera con máximo cuatro y una por categoría; se conservó y revalidó esa conducta | VERIFIED / IMPLEMENTED |
| LEGAL-V11-SCOPE-001 | Portada de los tres PDF frente a sus cuerpos/pies | La portada dice que v1.1 sustituye v1.0 en identificación, RFC, domicilio y carácter jurídico; Mecánica/Términos también cambian el plazo y Mecánica regulariza catálogo/límite | Interpretación de vigencia, comunicación y reglas funcionales | Se implementaron responsable, versión, vínculos, plazo, cuatro categorías y límite cuatro; la superposición de accesibilidad fue aceptada sin cambios por el propietario | VERIFIED / OWNER ACCEPTED |
| LEGAL-V11-PROMOTION-001 | Aviso p. 3, sección 6 | Finalidades secundarias nombran a FLORECE HERMOSILLO y su movimiento FLOWER FLOW | Registro y perfil | El texto visible ya nombra ambas identidades y el consentimiento sigue separado/reversible | VERIFIED |
| LEGAL-V11-PRIVACY-001 | Aviso pp. 2-5, secciones 3-17 | Datos, finalidades primarias, publicación, cookies, retención 24 meses/90 días, ARCO 20+15 días, revocación y seguridad conservan el contrato sustantivo de v1.0; cambia principalmente quién es “la Responsable” | Documentación de datos/privacidad y operación futura | Se actualizó identidad; no se inventó ni activó ARCO completo o borrado automático | VERIFIED / PARTIAL por alcance del producto |
| LEGAL-V11-REACCEPT-001 | Términos p. 4, sección 14; Aviso p. 5, sección 15 | Los documentos no ordenan expresamente reaceptar v1.1; el propietario confirma que los cambios fueron comunicados | Cuentas existentes, registro, envío y comunicaciones | Las cuentas v1.0 se tratan operativamente como aceptantes de v1.1 sin bloqueo ni backfill; las filas históricas permanecen intactas y las nuevas aceptaciones registran v1.1 | RESOLVED / OWNER DECISION |

## Implementación trazable

- `config/flowerflow.php` centraliza responsable legal y catálogo v1.1 con rutas, versiones, vigencias y hashes.
- `database/migrations/2026_08_17_220000_publish_legal_documents_v1_1.php` incorpora v1.1 de manera idempotente, desactiva v1.0 sin borrarla y revierte la versión activa sin eliminar registros o aceptaciones.
- `database/seeders/FlowerFlowSeeder.php` crea históricos sólo si faltan, valida que v1.1 coincida con el artefacto inmutable y garantiza una versión activa por código.
- Registro, perfil y envío bloquean si no existe exactamente una versión activa por documento requerido; nuevas aceptaciones registran `document_version=1.1` y FK al documento visto.
- Landing, `/documentos`, registro, login, perfil, envío, footer y panel apuntan a v1.1 y muestran la identidad legal aplicable.
- `LegalDocumentsV11Test` valida hashes, catálogo activo, rollback/forward, preservación histórica y superficies por rol.

## Decisión de release

`GO` técnico para el candidato exclusivamente local/test. Los gates vigentes fueron verdes: 109 pruebas/1,049 aserciones, Pint, Composer validate/platform/audit, build Vite, JSON, rutas, scheduler, 12 migraciones, lint PHP y `git diff --check`. La vista 503 se validó bajo CSP estricta, pre-render de mantenimiento y reflow Chrome/Firefox. Yarn conserva únicamente el advisory bajo conocido de Quill 2.0.3 sin parche. La UAT por rol previa verificó enlaces v1.1, registro/aceptaciones, límite 4/rechazo de quinta, envío, admisibilidad, panel, paginación, 2FA, XLSX, expiración y cierre. No quedaron cuentas sintéticas de este seguimiento.

Los tres puntos anteriores quedaron resueltos por decisión expresa del propietario el 2026-08-18. Esto no demuestra ni ejecuta un despliegue: el release sigue condicionado a que el propietario publique un SHA inmutable y ejecute su procedimiento productivo. Resultados permanecen apagados y no se autorizan módulos futuros.
