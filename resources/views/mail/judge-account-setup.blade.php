@extends('mail.layout')
@section('title', 'Configura tu acceso de juez')
@section('preheader', 'Establece tu contraseña para continuar con la configuración de tu cuenta de juez.')
@section('content')
    <p style="margin:0 0 8px;color:#0b5c42;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Acceso de juez</p>
    <h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#17352f;">Configura tu acceso</h1>
    <p style="margin:0 0 18px;">Hola, {{ $userName }}:</p>
    <p style="margin:0 0 24px;">Se creó para ti una cuenta de juez en Flower Flow. Usa este enlace temporal para establecer una contraseña propia.</p>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 26px;">
        <tr><td bgcolor="#167c5b" style="border-radius:8px;"><a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 24px;color:#ffffff;text-decoration:none;font-weight:700;">Establecer mi contraseña</a></td></tr>
    </table>
    <p style="margin:0 0 12px;color:#4b625b;font-size:14px;">El enlace vence en {{ $expiresInMinutes }} minutos. Después deberás verificar tu correo. La autenticación en dos pasos es opcional.</p>
    <p style="margin:0;color:#4b625b;font-size:13px;word-break:break-all;">Si el botón no funciona, copia esta dirección:<br><a href="{{ $actionUrl }}" style="color:#0b5c42;">{{ $actionUrl }}</a></p>
@endsection
