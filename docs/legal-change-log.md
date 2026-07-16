# Registro de cambios jurídicos propuestos

Estado: borrador técnico para revisión jurídica; no sustituye los PDFs v1.0.
Vigencia de PDFs preservados: 2026-07-15.
Publicación de v1.1: pendiente de aprobación expresa.

| ID | Diferencia detectada | Tratamiento local/test | Cambio v1.1 o adenda propuesto |
|---|---|---|---|
| LEGAL-PROFILE-001 | v1.0 no describe con igual precisión fecha de nacimiento, nombres separados y colonia usados en el perfil. | Datos mínimos, privados y separados del proyecto; recepción productiva apagada. | Enumerar campos, finalidad, base/consentimiento aplicable, retención y derechos. |
| LEGAL-WHATSAPP-001 | WhatsApp opcional no está claramente separado. | Checkbox independiente, inicialmente visible como marcado sólo en formulario nuevo, reversible y nunca inferido si no se envía. | Explicar canal, finalidad operativa, voluntariedad, revocación y efecto de no aceptar. |
| LEGAL-FUTURE-ACTIVITIES-001 | Se solicita autorización opcional para recibir información de futuras actividades de FLORECE HERMOSILLO y FLOWER FLOW. | Checkbox opcional marcado por defecto en registro, guardado como propósito independiente y reversible desde perfil. | Confirmar wording, canal, finalidad, vigencia, revocación y si el opt-in inicial marcado es jurídicamente aceptable. |
| LEGAL-REGISTRATION-WORDING-001 | Registro combina mayoría de edad, aceptación de términos y aviso de privacidad en una casilla obligatoria. | Texto solicitado por producto con enlaces descargables a PDFs v1.0 y evidencia separada para términos/privacidad. | Aprobar wording final y, si aplica, separar reconocimiento, aceptación y consentimiento en documentos v1.1. |
| LEGAL-UPLOAD-001 | v1.0 no cubre con precisión múltiples archivos, Office/ODF ni imágenes del editor. | Privados, 10 MiB, allowlist, hash, firma/MIME, sin enlaces públicos. | Listar formatos, límites, controles, retención, contenido prohibido y tratamiento de metadata. |
| LEGAL-EXTERNAL-LINKS-001 | Se añade video y carpeta pública de terceros. | Allowlist HTTPS; no fetch, descarga ni indexación del servidor. | Advertir responsabilidad de permisos/contenido, proveedores y transferencias/avisos de terceros. |
| LEGAL-ACCEPTANCE-001 | UI solicita “He leído y acepto” mientras v1.0 distingue reconocimiento/consentimientos. | Se registran finalidades por separado con versión, fecha UTC, IP, agente y contexto; no se reutiliza una aceptación para otra finalidad. | Definir wording exacto, cuáles actos son reconocimiento, aceptación obligatoria o consentimiento opcional. |

## Contradicciones de insumos editables

`Aviso de privacidad simplificado para el formulario.docx` menciona GoDaddy. La decisión vigente es AWS EC2 Ubuntu donde coexiste Administratec; el borrador v1.1 debe sustituir esa referencia por una descripción tecnológicamente correcta y jurídicamente revisada. `Casillas sugeridas.docx` agrupa obligaciones; FlowerFlow las presenta y registra por separado. Ambos DOCX se conservan como insumos, no como documentos vigentes.

## Gate de activación

No activar `FLOWERFLOW_REGISTRATION_ENABLED` ni `FLOWERFLOW_SUBMISSIONS_ENABLED` en producción hasta que producto/jurídico apruebe el texto, se publiquen archivos con versión/hash/vigencia nuevos, se actualice el seeder sin reemplazar v1.0 y UAT valide el wording.
