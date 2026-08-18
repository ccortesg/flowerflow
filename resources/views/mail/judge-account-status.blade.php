@extends('mail.layout')
@section('title', 'Actualización de acceso de juez')
@section('preheader', 'Se realizó una acción administrativa sobre tu acceso de juez.')
@section('content')
    @php
        $content = match ($event) {
            'suspended' => ['Acceso suspendido', 'Tu acceso al área de juez fue suspendido y tus sesiones quedaron cerradas.'],
            'reactivated' => ['Acceso reactivado', 'La suspensión de tu cuenta terminó. El acceso dependerá de que tu correo y contraseña propia estén completos.'],
            default => ['Acceso 2FA recuperado', 'El material de autenticación en dos pasos fue eliminado y tus sesiones quedaron cerradas. Puedes iniciar sesión y configurar 2FA nuevamente si lo deseas.'],
        };
    @endphp
    <p style="margin:0 0 8px;color:#0b5c42;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Seguridad de cuenta</p>
    <h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#17352f;">{{ $content[0] }}</h1>
    <p style="margin:0 0 18px;">Hola, {{ $userName }}:</p>
    <p style="margin:0 0 18px;">{{ $content[1] }}</p>
    <p style="margin:0;color:#4b625b;font-size:14px;">Si no reconoces esta acción, comunícate con el equipo responsable mediante el contacto oficial.</p>
@endsection
