FLOWER FLOW · FLORECE HERMOSILLO

Aún puedes enviar tu propuesta

Hola, {{ $reminder->recipient->profile?->first_names ?: $reminder->recipient->name }}.

Tu proyecto permanece en borrador. Revisa la información y confirma el envío antes del cierre.

Proyecto: {{ $reminder->submission->title }}
Fecha límite: {{ $reminder->submission->competition->closes_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s') }} (Hermosillo)

Enviar propuesta:
{{ $confirmationUrl }}

Abrir el enlace no envía nada. Deberás revisar las aceptaciones y confirmar mediante un segundo clic.

Contacto: {{ config('flowerflow.mail.reply_to') }}
