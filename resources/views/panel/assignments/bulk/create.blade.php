@extends('layouts.flowerflow')

@section('title', 'Asignar varias propuestas')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <p class="ff-kicker mb-1">Asignación manual</p>
    <h1 class="h2 mb-1">Asignar varias propuestas</h1>
    <p class="text-secondary mb-0">Selecciona un juez y hasta {{ config('flowerflow.bulk_judge_assignment.limit') }} propuestas de esta página. Revisar no realiza cambios.</p>
  </div>
  <a class="btn btn-outline-secondary" href="{{ route('panel.assignments.index') }}">Volver a asignaciones</a>
</div>

<div class="alert alert-info" role="note">
  Cada propuesta se procesa por separado: admitir expediente, activar paquete ciego y crear asignación. Si una falla, las demás pueden continuar.
</div>

<form method="GET" action="{{ route('panel.assignments.bulk.create') }}" class="card ff-card p-3 mb-4" aria-label="Filtros de propuestas para asignación masiva">
  <div class="row g-3 align-items-end">
    <div class="col-lg-4">
      <label class="form-label" for="judge_profile_filter">Juez</label>
      <select class="form-select" id="judge_profile_filter" name="judge_profile">
        <option value="">Selecciona un juez</option>
        @foreach($judges as $judge)
          <option value="{{ $judge->public_id }}" @selected($selectedJudge?->id === $judge->id)>
            {{ $judge->user->name }} · {{ $judge->assignment_role->label() }} · {{ $judge->active_assignments_count }} vigentes{{ $judge->status === \App\Enums\JudgeProfileStatus::PendingSetup ? ' · Configuración pendiente' : '' }}
          </option>
        @endforeach
      </select>
    </div>
    <div class="col-sm-6 col-lg-2">
      <label class="form-label" for="category_filter">Categoría</label>
      <select class="form-select" id="category_filter" name="category">
        <option value="">Todas</option>
        @foreach($categories as $category)
          <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-sm-6 col-lg-2">
      <label class="form-label" for="admissibility_filter">Admisibilidad</label>
      <select class="form-select" id="admissibility_filter" name="admissibility">
        <option value="">Todas</option>
        <option value="missing" @selected(request('admissibility') === 'missing')>Sin expediente</option>
        @foreach($eligibilityStatuses as $status)
          <option value="{{ $status->value }}" @selected(request('admissibility') === $status->value)>{{ $status->label() }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-sm-6 col-lg-2">
      <label class="form-label" for="package_filter">Paquete</label>
      <select class="form-select" id="package_filter" name="package">
        <option value="">Todos</option>
        <option value="missing" @selected(request('package') === 'missing')>Sin paquete</option>
        @foreach($packageStatuses as $status)
          <option value="{{ $status->value }}" @selected(request('package') === $status->value)>{{ $status->label() }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-sm-6 col-lg-2">
      <label class="form-label" for="assignment_filter">Asignación</label>
      <select class="form-select" id="assignment_filter" name="assignment" @disabled(!$selectedJudge)>
        <option value="">Todas</option>
        <option value="unassigned" @selected(request('assignment') === 'unassigned')>Sin asignar al juez</option>
        <option value="assigned" @selected(request('assignment') === 'assigned')>Ya asignadas</option>
      </select>
    </div>
    <div class="col-12"><button class="btn btn-flower" type="submit">Aplicar filtros y juez</button></div>
  </div>
</form>

@if(!$selectedJudge)
  <div class="alert alert-warning" role="status">Selecciona primero al juez para habilitar las propuestas.</div>
@elseif($selectedJudge->status === \App\Enums\JudgeProfileStatus::PendingSetup)
  <div class="alert alert-warning" role="status">Este juez puede recibir asignaciones, pero no podrá consultarlas ni evaluarlas hasta configurar su cuenta. Si solicitas el correo consolidado, se omitirá sin dejar un envío pendiente.</div>
@endif

<form method="POST" action="{{ route('panel.assignments.bulk.review') }}" novalidate>
  @csrf
  <input type="hidden" name="judge_profile" value="{{ $selectedJudge?->public_id }}">
  <div class="card ff-card mb-4">
    <div class="card-header bg-transparent">
      <h2 class="h5 mb-1">1. Selecciona propuestas de esta página</h2>
      <p class="text-secondary small mb-0">No se seleccionan registros de otras páginas de forma implícita.</p>
    </div>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr><th scope="col">Seleccionar</th><th scope="col">Propuesta</th><th scope="col">Categoría</th><th scope="col">Admisibilidad</th><th scope="col">Paquete</th><th scope="col">Asignación</th><th scope="col">Disponibilidad</th></tr></thead>
        <tbody>
        @forelse($submissions as $submission)
          @php($snapshot = $submission->getAttribute('bulk_assignment_snapshot'))
          @php($blocker = $submission->getAttribute('bulk_assignment_blocker'))
          @php($selectable = $selectedJudge && !$blocker)
          <tr>
            <td>
              <input class="form-check-input" type="checkbox" name="submissions[]" value="{{ $submission->public_id }}" id="submission_{{ $submission->public_id }}" @disabled(!$selectable) @checked(in_array($submission->public_id, old('submissions', []), true))>
            </td>
            <td><label for="submission_{{ $submission->public_id }}"><strong>{{ $submission->folio ?: 'Sin folio' }}</strong><span class="d-block small text-secondary">{{ $submission->title }}</span><code>{{ $submission->public_id }}</code></label></td>
            <td>{{ $submission->category->name }}</td>
            <td>{{ $submission->eligibilityReview?->status?->label() ?? 'Sin expediente' }}</td>
            <td>{{ $submission->versions->first()?->blindReviewPackage?->status?->label() ?? 'Sin paquete' }}</td>
            <td>{{ $snapshot['current_assignment_id'] ? 'Ya vigente; se omitirá' : 'Sin asignar al juez' }}</td>
            <td>
              @if($blocker)
                <span class="text-danger">{{ $blocker['message'] }}</span>
              @elseif(!$selectedJudge)
                <span class="text-secondary">Selecciona un juez.</span>
              @else
                <span class="text-success">Disponible</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="p-4">No hay propuestas enviadas para estos filtros.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="mb-4">{{ $submissions->links() }}</div>

  <fieldset class="card ff-card p-4 mb-4" @disabled(!$selectedJudge)>
    <legend class="h5">2. Información común</legend>
    <div class="mb-3">
      <label class="form-label" for="participant_reason">Motivo público de admisión</label>
      <textarea class="form-control" id="participant_reason" name="participant_reason" rows="4" maxlength="2000" required>{{ old('participant_reason') }}</textarea>
      <div class="form-text">Se registra únicamente en expedientes nuevos que sean admitidos; no sobrescribe expedientes ya admitidos.</div>
    </div>
    <div class="mb-3">
      <label class="form-label" for="internal_notes">Nota interna compartida <span class="text-secondary">(opcional)</span></label>
      <textarea class="form-control" id="internal_notes" name="internal_notes" rows="3" maxlength="5000">{{ old('internal_notes') }}</textarea>
    </div>
    <div class="row g-3">
      <div class="col-lg-6">
        <label class="form-label" for="package_reason">Razón de generación y activación del paquete</label>
        <textarea class="form-control" id="package_reason" name="package_reason" rows="3" minlength="20" maxlength="1000" required>{{ old('package_reason') }}</textarea>
      </div>
      <div class="col-lg-6">
        <label class="form-label" for="assignment_reason">Razón administrativa de asignación</label>
        <textarea class="form-control" id="assignment_reason" name="assignment_reason" rows="3" minlength="20" maxlength="1000" required>{{ old('assignment_reason') }}</textarea>
      </div>
    </div>
    <div class="form-check mt-3">
      <input type="hidden" name="notify_judge" value="0">
      <input class="form-check-input" type="checkbox" id="notify_judge" name="notify_judge" value="1" @checked(old('notify_judge')) @disabled(!config('flowerflow.flags.communication_ledger') || !config('flowerflow.judge_notifications.assignment_enabled'))>
      <label class="form-check-label" for="notify_judge">Enviar un único correo consolidado al juez</label>
      @if(!config('flowerflow.flags.communication_ledger') || !config('flowerflow.judge_notifications.assignment_enabled'))
        <div class="form-text">La bitácora o las notificaciones de asignación están deshabilitadas.</div>
      @endif
    </div>
    <div class="mt-3">
      <label class="form-label" for="current_password">Contraseña actual</label>
      <input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
    </div>
  </fieldset>

  <div class="d-flex flex-wrap gap-2">
    <button class="btn btn-flower" type="submit" @disabled(!$selectedJudge)>Revisar operación</button>
    <a class="btn btn-outline-secondary" href="{{ route('panel.assignments.index') }}">Cancelar</a>
  </div>
</form>
@endsection
