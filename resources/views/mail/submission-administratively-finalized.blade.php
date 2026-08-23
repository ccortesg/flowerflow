@extends('mail.layout')
@section('title', 'Propuesta registrada por la convocatoria')
@section('preheader', 'La organización registró tu propuesta con el folio ' . $submission->folio . '.')
@section('content')
    <p style="margin:0 0 8px;color:#0b5c42;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Registro administrativo</p>
    <h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#17352f;">Tu propuesta quedó registrada</h1>
    <p style="margin:0 0 20px;">El equipo de la convocatoria registró administrativamente tu propuesta para Hermosillo Florece 2026.</p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;background:#f3f7f4;border-left:5px solid #d9ed55;border-radius:8px;">
        <tr><td style="padding:18px 20px;line-height:1.7;">
            <strong>Folio:</strong> {{ $submission->folio }}<br>
            <strong>Proyecto:</strong> {{ $submission->title }}<br>
            <strong>Fecha:</strong> {{ $submission->submitted_at?->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }} (Hermosillo)
        </td></tr>
    </table>
    <p style="margin:0;color:#4b625b;font-size:14px;">Este registro no atribuye al participante aceptaciones realizadas por personal administrativo. Para cualquier aclaración, utiliza el contacto institucional.</p>
@endsection
