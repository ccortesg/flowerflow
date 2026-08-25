@extends('mail.layout')
@section('title', 'Conflicto de evaluación pendiente')
@section('preheader', 'Una asignación requiere revisión administrativa.')
@section('content')
    <p style="margin:0 0 8px;color:#0b5c42;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Evaluación</p>
    <h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#17352f;">Conflicto pendiente</h1>
    <p style="margin:0 0 18px;">Una asignación bajo tu responsabilidad registró un conflicto y requiere revisión.</p>
    <ul style="margin:0 0 24px;padding-left:20px;">
        <li><strong>ID de asignación:</strong> {{ $assignmentPublicId }}</li>
        <li><strong>Categoría:</strong> {{ $categoryName }}</li>
        <li><strong>Declarado:</strong> {{ $declaredAt }} (hora de Hermosillo)</li>
        <li><strong>Estado:</strong> Pendiente de resolución</li>
    </ul>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 26px;"><tr><td bgcolor="#167c5b" style="border-radius:8px;"><a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 24px;color:#ffffff;text-decoration:none;font-weight:700;">Revisar conflicto</a></td></tr></table>
    <p style="margin:0;color:#4b625b;font-size:14px;">Inicia sesión para consultar la evidencia autorizada. Este mensaje no incluye el tipo ni la explicación del conflicto, identidad del juez o contenido del proyecto.</p>
@endsection
