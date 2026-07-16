@extends('mail.layout')
@section('title', 'Restablece tu contraseña')
@section('preheader', 'Usa el enlace temporal para definir una nueva contraseña de Flower Flow.')
@section('content')
    <p style="margin:0 0 8px;color:#0b5c42;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Seguridad de tu cuenta</p>
    <h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#17352f;">Restablece tu contraseña</h1>
    <p style="margin:0 0 18px;">Hola, {{ $userName }}:</p>
    <p style="margin:0 0 24px;">Recibimos una solicitud para definir una nueva contraseña en tu cuenta de Flower Flow.</p>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 26px;">
        <tr><td bgcolor="#167c5b" style="border-radius:8px;"><a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 24px;color:#ffffff;text-decoration:none;font-weight:700;">Crear nueva contraseña</a></td></tr>
    </table>
    <p style="margin:0 0 12px;color:#4b625b;font-size:14px;">Este enlace vence en {{ $expiresInMinutes }} minutos. Si no solicitaste el cambio, ignora el mensaje; tu contraseña actual no se modificará.</p>
    <p style="margin:0;color:#4b625b;font-size:13px;word-break:break-all;">Si el botón no funciona, copia y pega esta dirección en tu navegador:<br><a href="{{ $actionUrl }}" style="color:#0b5c42;">{{ $actionUrl }}</a></p>
@endsection
