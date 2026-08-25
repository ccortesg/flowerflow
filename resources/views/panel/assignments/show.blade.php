@extends('layouts.flowerflow')

@section('title', 'Asignación manual de jueces')

@section('content')
<a href="{{ route('panel.assignments.index') }}" class="d-inline-flex align-items-center gap-1 mb-3">← Volver a asignaciones</a>
<p class="ff-kicker mb-1">Operación administrativa</p>
<h1 class="h2">Asignar jueces manualmente</h1>
<p class="text-secondary">Cada alta depende de tu selección explícita. No existe reparto automático, mínimo de cobertura ni límite de propuestas por juez.</p>

@if($errors->any())
  <div class="alert alert-danger" role="alert" tabindex="-1" autofocus>
    <strong>Revisa los datos antes de continuar.</strong>
    <ul class="mb-0 mt-2">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
  </div>
@endif

<div class="row g-4">
  <div class="col-xl-5">
    <section class="card ff-card p-4" aria-labelledby="assignment-context-title">
      <h2 id="assignment-context-title" class="h4">1. Confirma el contexto</h2>
      <dl class="row mb-0">
        <dt class="col-sm-5">Propuesta</dt><dd class="col-sm-7"><code>{{ $submission->public_id }}</code></dd>
        <dt class="col-sm-5">Categoría</dt><dd class="col-sm-7">{{ $submission->category->name }}</dd>
        <dt class="col-sm-5">Versión admitida</dt><dd class="col-sm-7">{{ $version->version }}</dd>
        <dt class="col-sm-5">Rúbrica activa</dt><dd class="col-sm-7">@if($activeRubric)v{{ $activeRubric->version }} · {{ $activeRubric->criteria_count }} criterios @else<span class="text-danger">No disponible</span>@endif</dd>
        <dt class="col-sm-5">Plazo</dt><dd class="col-sm-7">{{ \Carbon\CarbonImmutable::parse(config('flowerflow.evaluation_close_at'), config('flowerflow.timezone'))->format('d/m/Y H:i:s') }} (Hermosillo)</dd>
        <dt class="col-sm-5">Paquete ciego</dt><dd class="col-sm-7">{{ $package?->status?->label() ?? 'Aún no generado' }}</dd>
      </dl>
      <p class="small text-secondary mt-3 mb-0">El paquete puede generarse o activarse sin asignaciones. Para evaluar, cada asignación necesita posteriormente un paquete activo.</p>
    </section>

    <section class="card ff-card p-4 mt-4" aria-labelledby="assignment-counts-title">
      <h2 id="assignment-counts-title" class="h4">Situación actual</h2>
      <ul class="list-unstyled mb-0">
        <li><strong>{{ $coverage['active'] }}</strong> vigentes</li>
        <li><strong>{{ $coverage['pending_conflicts'] }}</strong> conflictos pendientes</li>
        <li><strong>{{ $coverage['cancelled'] }}</strong> canceladas</li>
        <li><strong>{{ $coverage['replaced'] }}</strong> reemplazadas</li>
      </ul>
    </section>
  </div>

  <div class="col-xl-7">
    <form method="POST" action="{{ route('panel.assignments.judges.store', $submission) }}" class="card ff-card p-4" novalidate>
      @csrf
      <h2 class="h4">2. Selecciona uno o más jueces</h2>
      <p class="text-secondary">La función y la carga actual son informativas; nunca limitan la selección.</p>
      <fieldset>
        <legend class="visually-hidden">Jueces disponibles</legend>
        @forelse($availableJudges as $judge)
          <div class="form-check border rounded p-3 ps-5 mb-2">
            <input class="form-check-input" id="judge-{{ $judge->public_id }}" name="judge_profiles[]" type="checkbox" value="{{ $judge->public_id }}" @checked(in_array($judge->public_id, old('judge_profiles', []), true))>
            <label class="form-check-label d-block" for="judge-{{ $judge->public_id }}">
              <strong>{{ $judge->user->name }}</strong>
              <span class="d-block small text-secondary">{{ $judge->assignment_role->label() }} · {{ $judge->active_assignments_count }} asignaciones vigentes · sin límite</span>
              @if($judge->status === \App\Enums\JudgeProfileStatus::PendingSetup)
                <span class="badge text-bg-warning mt-1">Configuración pendiente</span>
                <span class="d-block small text-warning-emphasis mt-1">Puede recibir asignaciones, pero no podrá consultarlas ni evaluarlas hasta configurar su cuenta.</span>
              @endif
            </label>
          </div>
        @empty
          <div class="alert alert-info">No hay jueces activos o con configuración pendiente disponibles para agregar en este momento.</div>
        @endforelse
      </fieldset>

      <h2 class="h4 mt-4">3. Elige si deseas notificar</h2>
      <input type="hidden" name="notify_judges" value="0">
      <div class="form-check mb-3">
        <input class="form-check-input" id="notify_judges" name="notify_judges" type="checkbox" value="1" @checked(old('notify_judges')) @disabled(! config('flowerflow.judge_notifications.assignment_enabled'))>
        <label class="form-check-label" for="notify_judges">Notificar por correo a los jueces seleccionados</label>
        @unless(config('flowerflow.judge_notifications.assignment_enabled'))<div class="form-text">La notificación de nuevas asignaciones está deshabilitada globalmente.</div>@endunless
      </div>

      <h2 class="h4 mt-3">4. Justifica y confirma</h2>
      <div class="mb-3"><label class="form-label" for="reason">Razón administrativa</label><textarea class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" minlength="20" maxlength="1000" required>{{ old('reason') }}</textarea>@error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
      <div class="mb-3"><label class="form-label" for="current_password">Contraseña actual</label><input class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" type="password" autocomplete="current-password" required>@error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
      <button class="btn btn-flower align-self-start" type="submit" @disabled($availableJudges->isEmpty() || ! $activeRubric)>Confirmar asignaciones seleccionadas</button>
    </form>
  </div>
</div>

<section class="card ff-card mt-4" aria-labelledby="assignments-history-title">
  <div class="p-4 pb-2"><h2 id="assignments-history-title" class="h4 mb-0">Historial de asignaciones</h2></div>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th>Juez</th><th>Función</th><th>Tipo</th><th>Estado</th><th>Rúbrica</th><th>Plazo</th><th>Acciones</th></tr></thead>
      <tbody>
      @forelse($assignments as $assignment)
        <tr>
          <td>{{ $assignment->judgeProfile->user->name }}</td>
          <td>{{ $assignment->judgeProfile->assignment_role->label() }}</td>
          <td>{{ $assignment->type->label() }}</td>
          <td>{{ $assignment->status->label() }}</td>
          <td>v{{ $assignment->rubricVersion->version }}</td>
          <td>{{ $assignment->due_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }}</td>
          <td>@if($assignment->status === \App\Enums\JudgeAssignmentStatus::Active && ! $assignment->conflict && ! $assignment->evaluation)<a class="btn btn-sm btn-outline-danger" href="{{ route('panel.assignments.cancel', $assignment) }}">Cancelar</a>@else<span class="text-secondary small">Sin acción</span>@endif</td>
        </tr>
        @if($assignment->conflict && $assignment->conflict->status === \App\Enums\JudgeConflictStatus::Declared)
          <tr><td colspan="7">
            <div class="alert alert-warning mb-0">
              <strong>Conflicto pendiente: {{ $assignment->conflict->type->label() }}</strong>
              @if($assignment->conflict->explanation)<p class="mb-2">{{ $assignment->conflict->explanation }}</p>@endif
              <form method="POST" action="{{ route('panel.assignments.conflicts.resolve', $assignment->conflict) }}" novalidate>
                @csrf
                <div class="mb-2">
                  <label class="form-label" for="judge-profile-{{ $assignment->id }}">Juez de reemplazo</label>
                  <select class="form-select" id="judge-profile-{{ $assignment->id }}" name="judge_profile" required>
                    <option value="">Selecciona un juez</option>
                    @foreach($replacementJudges as $candidate)<option value="{{ $candidate->public_id }}">{{ $candidate->user->name }} · {{ $candidate->assignment_role->label() }} · {{ $candidate->active_assignments_count }} vigentes{{ $candidate->status === \App\Enums\JudgeProfileStatus::PendingSetup ? ' · Configuración pendiente' : '' }}</option>@endforeach
                  </select>
                  <div class="form-text">Los jueces con configuración pendiente pueden recibir la asignación, pero no consultarla ni evaluarla hasta activar su cuenta.</div>
                </div>
                <input type="hidden" name="notify_judge" value="0">
                <div class="form-check mb-2"><input class="form-check-input" id="notify-replacement-{{ $assignment->id }}" name="notify_judge" type="checkbox" value="1" @disabled(! config('flowerflow.judge_notifications.assignment_enabled'))><label class="form-check-label" for="notify-replacement-{{ $assignment->id }}">Notificar la nueva asignación</label></div>
                <div class="mb-2"><label class="form-label" for="replacement-reason-{{ $assignment->id }}">Razón de reasignación</label><textarea class="form-control" id="replacement-reason-{{ $assignment->id }}" name="reason" minlength="20" maxlength="1000" required></textarea></div>
                <div class="mb-2"><label class="form-label" for="replacement-password-{{ $assignment->id }}">Contraseña actual</label><input class="form-control" id="replacement-password-{{ $assignment->id }}" name="current_password" type="password" autocomplete="current-password" required></div>
                <button class="btn btn-warning" type="submit" @disabled($replacementJudges->isEmpty())>Resolver y reasignar manualmente</button>
              </form>
            </div>
          </td></tr>
        @endif
      @empty
        <tr><td colspan="7" class="text-center text-secondary py-4">Aún no hay asignaciones. Elige manualmente al menos un juez cuando quieras iniciar la cobertura.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</section>
@endsection
