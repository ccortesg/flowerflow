@extends('mail.layout')
@section('title', 'Evaluación enviada')
@section('preheader', 'Se registró una revisión inmutable de evaluación.')
@section('content')
    <p style="margin:0 0 8px;color:#0b5c42;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Evaluación</p>
    <h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#17352f;">Evaluación enviada</h1>
    <p style="margin:0 0 18px;">Flower Flow registró una revisión inmutable de evaluación.</p>
    <ul style="margin:0 0 24px;padding-left:20px;">
        <li><strong>ID de asignación:</strong> {{ $assignmentPublicId }}</li>
        <li><strong>ID de evaluación:</strong> {{ $evaluationPublicId }}</li>
        <li><strong>Revisión:</strong> {{ $revisionNumber }}</li>
        <li><strong>Fecha:</strong> {{ $submittedAt }} (hora de Hermosillo)</li>
        <li><strong>Modo:</strong> {{ $submissionMode }}</li>
    </ul>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 26px;"><tr><td bgcolor="#167c5b" style="border-radius:8px;"><a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 24px;color:#ffffff;text-decoration:none;font-weight:700;">Ver evaluación</a></td></tr></table>
    <p style="margin:0;color:#4b625b;font-size:14px;">Este acuse no incluye puntajes, total, comentarios, identidad de otros actores ni contenido del proyecto.</p>
@endsection
