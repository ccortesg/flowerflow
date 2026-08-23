@extends('layouts.flowerflow')

@section('title', 'Procesar notificación')

@section('content')
<p class="ff-kicker mb-1">Notificaciones</p>
<h1>Confirmar acción operativa</h1>

<div class="card ff-card mt-4">
  <div class="card-body">
    <dl class="row">
      <dt class="col-sm-4">Referencia</dt><dd class="col-sm-8"><code>{{ $delivery->public_id }}</code></dd>
      <dt class="col-sm-4">Tipo</dt><dd class="col-sm-8">{{ $delivery->notification_type->label() }}</dd>
      <dt class="col-sm-4">Destinatario</dt><dd class="col-sm-8"><code>{{ $delivery->recipient_mask }}</code></dd>
      <dt class="col-sm-4">Estado actual</dt><dd class="col-sm-8">{{ $delivery->status->label() }}</dd>
    </dl>

    @if($delivery->status === \App\Enums\CommunicationDeliveryStatus::Unknown)
      <div class="alert alert-danger" role="alert">
        El servidor no pudo determinar si el intento anterior fue aceptado. Reenviar puede producir un correo duplicado. Revisa la evidencia disponible antes de continuar.
      </div>
    @else
      <div class="alert alert-warning" role="alert">
        La solicitud se enviará a la cola prioritaria. El worker volverá a validar destinatario, vigencia y estado del evento; no se enviará correo dentro de esta petición.
      </div>
    @endif

    <form method="POST" action="{{ route('panel.communication-deliveries.store', $delivery) }}" novalidate>
      @csrf
      <input type="hidden" name="lock_version" value="{{ $delivery->lock_version }}">
      <div class="mb-3">
        <label class="form-label" for="reason">Razón administrativa</label>
        <textarea class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" rows="5" minlength="20" maxlength="1000" required aria-describedby="reason-help @error('reason') reason-error @enderror">{{ old('reason') }}</textarea>
        <p class="form-text" id="reason-help">Entre 20 y 1,000 caracteres. Se conserva cifrada y no se muestra en la bitácora.</p>
        @error('reason')<p class="invalid-feedback" id="reason-error">{{ $message }}</p>@enderror
      </div>
      @if($delivery->status === \App\Enums\CommunicationDeliveryStatus::Unknown)
        <div class="form-check mb-3">
          <input class="form-check-input @error('duplicate_risk_acknowledged') is-invalid @enderror" id="duplicate_risk_acknowledged" name="duplicate_risk_acknowledged" type="checkbox" value="1" required @checked(old('duplicate_risk_acknowledged'))>
          <label class="form-check-label" for="duplicate_risk_acknowledged">Comprendo que el destinatario podría recibir un correo duplicado.</label>
          @error('duplicate_risk_acknowledged')<p class="invalid-feedback">{{ $message }}</p>@enderror
        </div>
      @endif
      <div class="form-check mb-4">
        <input class="form-check-input @error('confirm_processing') is-invalid @enderror" id="confirm_processing" name="confirm_processing" type="checkbox" value="1" required @checked(old('confirm_processing'))>
        <label class="form-check-label" for="confirm_processing">Confirmo que revisé esta comunicación y solicito procesarla.</label>
        @error('confirm_processing')<p class="invalid-feedback">{{ $message }}</p>@enderror
      </div>
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-flower" type="submit"><i class="ri-send-plane-line me-1" aria-hidden="true"></i> Programar en cola prioritaria</button>
        <a class="btn btn-outline-secondary" href="{{ route('panel.communication-deliveries.show', $delivery) }}">Cancelar</a>
      </div>
    </form>
  </div>
</div>
@endsection
