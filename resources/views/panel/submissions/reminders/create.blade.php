@extends('layouts.flowerflow')
@section('title', 'Enviar recordatorios de propuestas')
@section('content')
<p class="ff-kicker mb-1">Recepción</p>
<h1>Enviar recordatorios</h1>

<div class="card ff-card mt-4">
  <div class="card-body">
    <p>Esta acción considera <strong>todos los borradores de la convocatoria activa</strong>, sin limitarse a la página o filtros visibles del listado.</p>
    <dl class="row">
      <dt class="col-sm-5">Convocatoria</dt><dd class="col-sm-7">{{ $competition?->name ?? 'Sin convocatoria activa' }}</dd>
      <dt class="col-sm-5">Propuestas en borrador</dt><dd class="col-sm-7">{{ $draft_count }}</dd>
      <dt class="col-sm-5">Recordatorios que pueden programarse</dt><dd class="col-sm-7">{{ $sendable_count }}</dd>
      <dt class="col-sm-5">Omitidos</dt><dd class="col-sm-7">{{ $skipped_count }}</dd>
      @if($competition?->closes_at)
        <dt class="col-sm-5">Fecha límite</dt><dd class="col-sm-7">{{ $competition->closes_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s') }} (Hermosillo)</dd>
      @endif
    </dl>
    <div class="alert alert-info" role="note">
      Se enviará únicamente a la cuenta representante verificada. Un recordatorio reciente dentro de {{ config('flowerflow.submission_reminders.cooldown_hours') }} horas se omite para evitar duplicados.
    </div>
    <form method="POST" action="{{ route('panel.submissions.reminders.store') }}">
      @csrf
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-flower" type="submit" @disabled($sendable_count === 0)>
          <i class="ri-mail-send-line me-1" aria-hidden="true"></i> Programar recordatorios
        </button>
        <a class="btn btn-outline-secondary" href="{{ route('panel.submissions.index') }}">Cancelar</a>
      </div>
    </form>
  </div>
</div>
@endsection
