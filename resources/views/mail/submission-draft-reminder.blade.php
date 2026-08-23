@extends('mail.layout')
@section('title', 'Recordatorio de propuesta pendiente')
@section('preheader', 'Tu propuesta sigue en borrador. Confirma su envío antes del cierre.')
@section('content')
    <p style="margin:0 0 8px;color:#0b5c42;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Propuesta pendiente</p>
    <h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#17352f;">Aún puedes enviar tu propuesta</h1>
    <p style="margin:0 0 18px;">Hola, {{ $reminder->recipient->profile?->first_names ?: $reminder->recipient->name }}.</p>
    <p style="margin:0 0 20px;">Tu proyecto permanece en borrador. Te invitamos a revisar la información y confirmar el envío antes de que cierre la convocatoria.</p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;background:#f3f7f4;border-left:5px solid #d9ed55;border-radius:8px;">
        <tr><td style="padding:18px 20px;line-height:1.7;">
            <strong>Proyecto:</strong> {{ $reminder->submission->title }}<br>
            <strong>Fecha límite:</strong> {{ $reminder->submission->competition->closes_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s') }} (Hermosillo)
        </td></tr>
    </table>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
        <tr><td bgcolor="#167c5b" style="border-radius:8px;"><a href="{{ $confirmationUrl }}" style="display:inline-block;padding:13px 24px;color:#ffffff;text-decoration:none;font-weight:700;">Enviar propuesta</a></td></tr>
    </table>
    <p style="margin:0;color:#4b625b;font-size:14px;">El enlace es temporal. Abrirlo no envía nada: revisarás las aceptaciones y confirmarás mediante un segundo clic seguro.</p>
@endsection
