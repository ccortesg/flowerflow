@extends('layouts.flowerflow')

@section('title', 'Detalle de notificación')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <p class="ff-kicker mb-1">Notificaciones</p>
    <h1 class="h2 mb-1">Detalle de comunicación</h1>
    <p class="text-secondary mb-0"><code>{{ $delivery->public_id }}</code></p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    @can('manage', $delivery)
      @if($delivery->status->canBeForced())
        <a class="btn btn-flower" href="{{ route('panel.communication-deliveries.process', $delivery) }}">
          <i class="ri-send-plane-line me-1" aria-hidden="true"></i>
          {{ match($delivery->status) {
            \App\Enums\CommunicationDeliveryStatus::Queued => 'Procesar ahora',
            \App\Enums\CommunicationDeliveryStatus::Failed => 'Reintentar',
            \App\Enums\CommunicationDeliveryStatus::Unknown => 'Reenviar con riesgo',
            default => 'Procesar',
          } }}
        </a>
      @endif
    @endcan
    <a class="btn btn-outline-secondary" href="{{ route('panel.communication-deliveries.index') }}">Volver</a>
  </div>
</div>

<div class="card ff-card mb-4">
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-4">Tipo</dt><dd class="col-sm-8">{{ $delivery->notification_type->label() }}</dd>
      <dt class="col-sm-4">Variante</dt><dd class="col-sm-8"><code>{{ $delivery->variant ?: '—' }}</code></dd>
      <dt class="col-sm-4">Destinatario enmascarado</dt><dd class="col-sm-8"><code>{{ $delivery->recipient_mask ?: 'No disponible' }}</code></dd>
      <dt class="col-sm-4">Estado</dt><dd class="col-sm-8">{{ $delivery->status->label() }}</dd>
      <dt class="col-sm-4">Cola</dt><dd class="col-sm-8"><code>{{ $delivery->queue }}</code></dd>
      <dt class="col-sm-4">Intentos iniciados</dt><dd class="col-sm-8">{{ $delivery->attempts_count }}</dd>
      <dt class="col-sm-4">Generación</dt><dd class="col-sm-8">{{ $delivery->created_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s') }}</dd>
      <dt class="col-sm-4">Última actualización</dt><dd class="col-sm-8">{{ $delivery->updated_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s') }}</dd>
      @if($delivery->failure_code)
        <dt class="col-sm-4">Código operativo</dt><dd class="col-sm-8"><code>{{ $delivery->failure_code }}</code></dd>
      @endif
    </dl>
  </div>
</div>

<section class="card ff-card" aria-labelledby="attempts-title">
  <div class="card-body">
    <h2 class="h4" id="attempts-title">Línea de tiempo de intentos</h2>
    <p class="text-secondary">No se muestran cuerpo, tokens, URLs, excepciones completas ni datos personales.</p>
    <ol class="list-group list-group-numbered">
      @foreach($delivery->attempts as $attempt)
        <li class="list-group-item d-flex flex-column gap-1">
          <div class="d-flex flex-wrap justify-content-between gap-2">
            <strong>{{ $attempt->source->label() }} · {{ $attempt->status->label() }}</strong>
            <time datetime="{{ $attempt->created_at->toIso8601String() }}">{{ $attempt->created_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s') }}</time>
          </div>
          <span>Intento {{ $attempt->attempt_number }} · cola <code>{{ $attempt->queue }}</code></span>
          @if($attempt->failure_code)<span>Código: <code>{{ $attempt->failure_code }}</code></span>@endif
          @if($attempt->duplicate_risk_acknowledged)<span class="text-warning-emphasis">Se reconoció el posible envío duplicado.</span>@endif
        </li>
      @endforeach
    </ol>
  </div>
</section>
@endsection
