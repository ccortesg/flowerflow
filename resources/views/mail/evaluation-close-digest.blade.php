@extends('mail.layout')
@section('title', 'Resumen de cierre de evaluación')
@section('preheader', 'Consulta el resumen de tus asignaciones al cierre.')
@section('content')
    <p style="margin:0 0 8px;color:#0b5c42;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Evaluación</p>
    <h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#17352f;">Resumen de cierre</h1>
    <p style="margin:0 0 18px;">La ventana de evaluación cerró el {{ $closedAt }} (hora de Hermosillo). Este es el resumen de tus asignaciones.</p>
    <ul style="margin:0 0 24px;padding-left:20px;">
        <li><strong>Evaluaciones enviadas:</strong> {{ $submitted }}</li>
        <li><strong>Pendientes:</strong> {{ $pending }}</li>
        <li><strong>Conflictos o reemplazos:</strong> {{ $conflicts_replacements }}</li>
        <li><strong>Canceladas:</strong> {{ $cancelled }}</li>
    </ul>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 26px;"><tr><td bgcolor="#167c5b" style="border-radius:8px;"><a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 24px;color:#ffffff;text-decoration:none;font-weight:700;">Ver mis asignaciones</a></td></tr></table>
    <p style="margin:0;color:#4b625b;font-size:14px;">El resumen muestra únicamente conteos y no contiene títulos, puntajes, comentarios, otros jueces ni contenido de proyectos.</p>
@endsection
