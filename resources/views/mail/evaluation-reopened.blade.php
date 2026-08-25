@extends('mail.layout')
@section('title', 'Evaluación reabierta')
@section('preheader', 'Revisa y vuelve a enviar tu evaluación dentro del plazo.')
@section('content')
    <p style="margin:0 0 8px;color:#0b5c42;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Evaluación</p>
    <h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#17352f;">Evaluación reabierta</h1>
    <p style="margin:0 0 18px;">La administración reabrió tu evaluación. Revisa cuidadosamente los datos antes de volver a enviarla.</p>
    <ul style="margin:0 0 24px;padding-left:20px;">
        <li><strong>ID de asignación:</strong> {{ $assignmentPublicId }}</li>
        <li><strong>Nueva revisión:</strong> {{ $revisionNumber }}</li>
        <li><strong>Plazo:</strong> {{ $dueAt }} (hora de Hermosillo)</li>
    </ul>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 26px;"><tr><td bgcolor="#167c5b" style="border-radius:8px;"><a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 24px;color:#ffffff;text-decoration:none;font-weight:700;">Revisar evaluación</a></td></tr></table>
    <p style="margin:0;color:#4b625b;font-size:14px;">Este aviso no incluye el motivo de reapertura, identidad del administrador, puntajes, total o comentarios.</p>
@endsection
