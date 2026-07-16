@extends('mail.layout')
@section('title', 'Verifica tu correo electrónico')
@section('preheader', 'Confirma tu correo para continuar con tu participación en Hermosillo Florece 2026.')
@section('content')
    <p style="margin:0 0 8px;color:#0b5c42;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Cuenta participante</p>
    <h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#17352f;">Verifica tu correo electrónico</h1>
    <p style="margin:0 0 18px;">Hola, {{ $userName }}:</p>
    <p style="margin:0 0 24px;">Confirma que este correo te pertenece para continuar con tu registro y enviar propuestas en Hermosillo Florece 2026.</p>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 26px;">
        <tr><td bgcolor="#167c5b" style="border-radius:8px;"><a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 24px;color:#ffffff;text-decoration:none;font-weight:700;">Verificar mi correo</a></td></tr>
    </table>
    <p style="margin:0 0 12px;color:#4b625b;font-size:14px;">Por seguridad, el enlace es temporal. Si no creaste esta cuenta, puedes ignorar el mensaje.</p>
    <p style="margin:0;color:#4b625b;font-size:13px;word-break:break-all;">Si el botón no funciona, copia y pega esta dirección en tu navegador:<br><a href="{{ $actionUrl }}" style="color:#0b5c42;">{{ $actionUrl }}</a></p>
@endsection
