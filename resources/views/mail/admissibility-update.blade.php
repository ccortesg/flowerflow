@extends('mail.layout')
@section('title', $copy['title'])
@section('preheader', $copy['body'])
@section('content')
    <p style="margin:0 0 8px;color:#0b5c42;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">{{ $copy['kicker'] }}</p>
    <h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#17352f;">{{ $copy['title'] }}</h1>
    <p style="margin:0 0 20px;">{{ $copy['body'] }}</p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;background:#f3f7f4;border-left:5px solid #d9ed55;border-radius:8px;">
        <tr><td style="padding:18px 20px;line-height:1.7;">
            <strong>Folio:</strong> {{ $review->submission->folio }}<br>
            <strong>Propuesta:</strong> {{ $review->submission->title }}
        </td></tr>
    </table>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
        <tr><td bgcolor="#167c5b" style="border-radius:8px;"><a href="{{ route('submissions.show', $review->submission) }}" style="display:inline-block;padding:13px 24px;color:#ffffff;text-decoration:none;font-weight:700;">{{ $copy['button'] }}</a></td></tr>
    </table>
    <p style="margin:0;color:#4b625b;font-size:14px;">Por seguridad, este correo no incluye documentos, notas internas ni información sensible. No envíes comprobantes como respuesta.</p>
@endsection
