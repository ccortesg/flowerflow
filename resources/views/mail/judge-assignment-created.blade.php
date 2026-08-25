@extends('mail.layout')
@section('title', 'Nueva asignación de evaluación')
@section('preheader', 'Tienes una nueva asignación disponible en Flower Flow.')
@section('content')
    <p style="margin:0 0 8px;color:#0b5c42;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Evaluación</p>
    <h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#17352f;">Nueva asignación</h1>
    <p style="margin:0 0 18px;">Hola, {{ $userName }}:</p>
    <p style="margin:0 0 18px;">Se agregó una asignación a tu área de evaluación.</p>
    <ul style="margin:0 0 24px;padding-left:20px;">
        <li><strong>ID:</strong> {{ $assignmentPublicId }}</li>
        <li><strong>Categoría:</strong> {{ $categoryName }}</li>
        <li><strong>Plazo:</strong> {{ $dueAt }} (hora de Hermosillo)</li>
    </ul>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 26px;">
        <tr><td bgcolor="#167c5b" style="border-radius:8px;"><a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 24px;color:#ffffff;text-decoration:none;font-weight:700;">Ver asignación</a></td></tr>
    </table>
    <p style="margin:0;color:#4b625b;font-size:14px;">Inicia sesión para consultar el paquete ciego. Este correo no incluye identidad, contenido del proyecto, anexos ni otros jueces.</p>
@endsection
