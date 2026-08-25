@extends('mail.layout')
@section('title', 'Conflicto de evaluación resuelto')
@section('preheader', 'Tu asignación dejó de estar disponible.')
@section('content')
    <p style="margin:0 0 8px;color:#0b5c42;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Evaluación</p>
    <h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#17352f;">Conflicto resuelto</h1>
    <p style="margin:0 0 18px;">El conflicto asociado a la asignación <strong>{{ $assignmentPublicId }}</strong> fue resuelto y esa asignación dejó de estar disponible.</p>
    <p style="margin:0 0 24px;"><strong>Fecha:</strong> {{ $resolvedAt }} (hora de Hermosillo)</p>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 26px;"><tr><td bgcolor="#167c5b" style="border-radius:8px;"><a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 24px;color:#ffffff;text-decoration:none;font-weight:700;">Ver mis asignaciones</a></td></tr></table>
    <p style="margin:0;color:#4b625b;font-size:14px;">Este aviso no identifica al administrador, al juez entrante ni el motivo de la resolución.</p>
@endsection
