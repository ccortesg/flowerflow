@extends('layouts.flowerflow')

@section('title', 'Notificaciones')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <p class="ff-kicker mb-1">Operación de correo</p>
    <h1 class="h2 mb-1">Notificaciones</h1>
    <p class="text-secondary mb-0">Bitácora técnica de correos transaccionales. “Aceptado” no confirma entrega al buzón.</p>
  </div>
</div>

<form method="GET" class="card ff-card p-3 mb-4" aria-label="Filtros de notificaciones">
  <div class="row g-3 align-items-end">
    <div class="col-md-3">
      <label class="form-label" for="status">Estado</label>
      <select class="form-select" id="status" name="status">
        <option value="">Todos</option>
        @foreach($statuses as $status)
          <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label" for="type">Tipo</label>
      <select class="form-select" id="type" name="type">
        <option value="">Todos</option>
        @foreach($types as $type)
          <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-sm-6 col-md-2">
      <label class="form-label" for="from">Desde</label>
      <input class="form-control" id="from" name="from" type="date" value="{{ request('from') }}">
    </div>
    <div class="col-sm-6 col-md-2">
      <label class="form-label" for="to">Hasta</label>
      <input class="form-control" id="to" name="to" type="date" value="{{ request('to') }}">
    </div>
    <div class="col-md-2">
      <div class="form-check mb-2">
        <input class="form-check-input" id="requires_attention" name="requires_attention" type="checkbox" value="1" @checked(request()->boolean('requires_attention'))>
        <label class="form-check-label" for="requires_attention">Requiere atención</label>
      </div>
      <button class="btn btn-flower w-100" type="submit">Filtrar</button>
    </div>
  </div>
</form>

<div class="card ff-card">
  <div class="table-responsive">
    <table class="table align-middle mb-0 ff-communication-table">
      <thead>
        <tr>
          <th scope="col">Generación</th>
          <th scope="col">Tipo</th>
          <th scope="col">Destinatario</th>
          <th scope="col">Referencia técnica</th>
          <th scope="col">Estado</th>
          <th scope="col">Intentos</th>
          <th scope="col">Actualización</th>
          <th scope="col">Acción</th>
        </tr>
      </thead>
      <tbody>
      @forelse($deliveries as $delivery)
        @php($isStalled = $delivery->status === \App\Enums\CommunicationDeliveryStatus::Queued && $delivery->queued_at?->lessThanOrEqualTo($stalledBefore))
        <tr>
          <td data-label="Generación">{{ $delivery->created_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }}</td>
          <td data-label="Tipo">{{ $delivery->notification_type->label() }}</td>
          <td data-label="Destinatario"><code>{{ $delivery->recipient_mask ?: 'No disponible' }}</code></td>
          <td data-label="Referencia técnica"><code>{{ $delivery->public_id }}</code></td>
          <td data-label="Estado">
            {{ $delivery->status->label() }}
            @if($isStalled)<span class="badge text-bg-warning ms-1">En cola fuera de umbral</span>@endif
          </td>
          <td data-label="Intentos">{{ $delivery->attempts_count }}</td>
          <td data-label="Actualización">{{ $delivery->updated_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }}</td>
          <td data-label="Acción">
            <div class="d-flex flex-wrap gap-1">
              <a class="btn btn-sm btn-outline-primary" href="{{ route('panel.communication-deliveries.show', $delivery) }}">Ver detalle</a>
              @can('manage', $delivery)
                @if($delivery->status->canBeForced())
                  <a class="btn btn-sm btn-outline-success" href="{{ route('panel.communication-deliveries.process', $delivery) }}">
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
            </div>
          </td>
        </tr>
      @empty
        <tr class="ff-empty-row"><td colspan="8" class="p-4">No hay notificaciones que coincidan con los filtros.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
<div class="mt-4">{{ $deliveries->links() }}</div>
@endsection
