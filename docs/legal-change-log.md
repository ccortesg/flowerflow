# Registro de cambios jurídicos y pendientes

Estado: v1.1 corregida e incorporada al release candidate local el 2026-08-17. La cantidad de categorías y propuestas ya está resuelta; persisten decisiones y evidencia externa que impiden considerar autorizado un despliegue productivo.

La comparación completa, hashes y evidencia por página/sección están en `docs/17-legal-v1-1-reconciliation-2026-08-17.md`. Los PDF v1.0 y sus aceptaciones se conservan como historia; los tres PDF v1.1 son la versión enlazada y registrada para nuevas aceptaciones locales.

| ID | Diferencia detectada | Tratamiento local/test | Cambio v1.1 o adenda propuesto |
|---|---|---|---|
| LEGAL-PROFILE-001 | v1.0 no describe con igual precisión fecha de nacimiento, nombres separados y colonia usados en el perfil. | Datos mínimos, privados y separados del proyecto; recepción productiva apagada. | Enumerar campos, finalidad, base/consentimiento aplicable, retención y derechos. |
| LEGAL-WHATSAPP-001 | WhatsApp opcional no está claramente separado. | Checkbox independiente, inicialmente visible como marcado sólo en formulario nuevo, reversible y nunca inferido si no se envía. | Explicar canal, finalidad operativa, voluntariedad, revocación y efecto de no aceptar. |
| LEGAL-FUTURE-ACTIVITIES-001 | Se solicita autorización opcional para recibir información de futuras actividades de FLORECE HERMOSILLO y FLOWER FLOW. | Checkbox opcional marcado por defecto en registro, guardado como propósito independiente y reversible desde perfil. | Confirmar wording, canal, finalidad, vigencia, revocación y si el opt-in inicial marcado es jurídicamente aceptable. |
| LEGAL-REGISTRATION-WORDING-001 | Registro combina mayoría de edad, aceptación de términos y reconocimiento del aviso de privacidad en una casilla obligatoria. | Enlaces v1.1 y evidencia separada para términos/privacidad; consentimientos opcionales permanecen separados. | `PROPOSAL_NEEDED`: confirmar si el wording debe separar aceptación de términos, acceso al aviso y declaración de mayoría de edad. |
| LEGAL-UPLOAD-001 | v1.0 no cubre con precisión múltiples archivos, Office/ODF ni imágenes del editor. | Privados, 10 MiB, allowlist, hash, firma/MIME, sin enlaces públicos. | Listar formatos, límites, controles, retención, contenido prohibido y tratamiento de metadata. |
| LEGAL-EXTERNAL-LINKS-001 | Se añade video y carpeta pública de terceros. | Allowlist HTTPS; no fetch, descarga ni indexación del servidor. | Advertir responsabilidad de permisos/contenido, proveedores y transferencias/avisos de terceros. |
| LEGAL-ACCEPTANCE-001 | UI solicita “He leído y acepto”; v1.1 distingue aceptación, reconocimiento y consentimientos opcionales. | Nuevas cuentas/envíos registran v1.1; propósitos, FK, versión, fecha UTC, IP, agente y contexto siguen separados. No se reescribe v1.0. | `PROPOSAL_NEEDED`: decidir reaceptación de cuentas existentes y wording exacto por finalidad. |
| LEGAL-CATEGORY-BARRIERS-001 | La Mecánica v1.1 corregida enumera las cuatro categorías, incluida Hermosillo sin Barreras, y autoriza máximo cuatro propuestas, una por categoría. Aún usa “accesibilidad” tanto en Movilidad con Flow como en Hermosillo sin Barreras. | Código, datos, UI y pruebas permanecen alineados en cuatro categorías/máximo cuatro. No se reasigna el alcance de accesibilidad por inferencia. | Cantidades `VERIFIED`; `POR_CONFIRMAR P1` la delimitación temática de accesibilidad antes de comunicación productiva. |
| LEGAL-DEADLINE-EXTENSION-001 | Mecánica y Términos v1.1 fijan 23 de agosto de 2026 a las 23:59, tiempo de Hermosillo. | Configuración, base, UI, pruebas y documentos v1.1 están alineados con `2026-08-23 23:59:59 America/Hermosillo`. | `RESOLVED` para la fecha; queda pendiente comunicar/reaceptar si corresponde. |
| LEGAL-V1-HASH-001 | El PDF Mecánica v1.0 original asociado a `42bd5e…` fue cambiado bajo el mismo nombre/versión a `3bcf31…` en `dca0bfd`. | Registro/hash/aceptaciones v1.0 no se modifican; v1.1 usa ruta y hash nuevos. | P0: recuperar/publicar el artefacto histórico original mediante decisión explícita y preservar ambos. |

## Contradicciones de insumos editables

`Aviso de privacidad simplificado para el formulario.docx` menciona GoDaddy. La decisión vigente es AWS EC2 Ubuntu donde coexiste Administratec; el borrador v1.1 debe sustituir esa referencia por una descripción tecnológicamente correcta y jurídicamente revisada. `Casillas sugeridas.docx` agrupa obligaciones; FlowerFlow las presenta y registra por separado. Ambos DOCX se conservan como insumos, no como documentos vigentes.

## Gate de activación

No activar `FLOWERFLOW_REGISTRATION_ENABLED` ni `FLOWERFLOW_SUBMISSIONS_ENABLED` en producción hasta decidir la delimitación de accesibilidad, la reaceptación, la integridad v1.0 y los demás gates externos del runbook; además se requiere aprobación expresa. Los archivos, hashes, seeder y aceptaciones nuevas v1.1 ya están implementados y validados en local/test.

**Historial de sustituciones durante el candidato:** el propietario reemplazó el archivo v1.1 hasta corregir las dos cantidades. El artefacto definitivo enumera cuatro categorías y máximo cuatro propuestas; su SHA-256 congelado para este candidato es `11c399ca84735d7dbcb17174e192582c93589afa5100c0250753ca15def4db36`. La superposición temática de accesibilidad se conserva como `POR_CONFIRMAR`, no como permiso para reescribir el texto jurídico.
