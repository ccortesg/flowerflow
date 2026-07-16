FLOWER FLOW · FLORECE HERMOSILLO

Recibimos tu propuesta

Tu envío para Hermosillo Florece 2026 quedó registrado correctamente.

Folio: {{ $submission->folio }}
Título: {{ $submission->title }}
Fecha: {{ $submission->submitted_at?->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }} (Hermosillo)

Consulta tu propuesta:
{{ route('submissions.show', $submission) }}

Conserva este folio para cualquier aclaración. No respondas con documentos adjuntos.

Contacto: {{ config('flowerflow.mail.reply_to') }}
