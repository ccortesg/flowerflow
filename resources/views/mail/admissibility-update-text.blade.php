{{ $copy['title'] }}

{{ $copy['body'] }}

Folio: {{ $review->submission->folio }}
Propuesta: {{ $review->submission->title }}

Consulta la información de forma segura en:
{{ route('submissions.show', $review->submission) }}

Por seguridad, este correo no incluye documentos, notas internas ni información sensible. No envíes comprobantes como respuesta.
