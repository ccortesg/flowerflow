@extends('layouts.flowerflow')

@section('title', 'Cobertura de evaluación')

@section('content')
<a href="{{ route('panel.assignments.index') }}" class="d-inline-flex align-items-center gap-1 mb-3">← Volver a asignaciones</a>
<h1 class="h2">Cobertura de evaluación</h1>
<dl class="row">
  <dt class="col-sm-3">ID técnico</dt><dd class="col-sm-9"><code>{{ $submission->public_id }}</code></dd>
  <dt class="col-sm-3">Categoría</dt><dd class="col-sm-9">{{ $submission->category->name }}</dd>
  <dt class="col-sm-3">Versión final</dt><dd class="col-sm-9">{{ $version->version }}</dd>
  <dt class="col-sm-3">Cobertura vigente</dt><dd class="col-sm-9">{{ $coverage['covered'] }} de {{ $coverage['required'] }}</dd>
</dl>

@if($assignments->isEmpty())
  <div class="card ff-card p-4 mb-4">
    <h2 class="h5">Crear cobertura inicial</h2>
    <p>Se fijarán exactamente los cuatro jueces principales activos, la versión actual de la rúbrica y el plazo global.</p>
    <form method="POST" action="{{ route('panel.assignments.activate', $submission) }}">
      @csrf
      <div class="mb-3"><label class="form-label" for="reason">Razón administrativa</label><textarea class="form-control" id="reason" name="reason" minlength="20" maxlength="1000" required>{{ old('reason') }}</textarea></div>
      <div class="mb-3"><label class="form-label" for="current_password">Contraseña actual</label><input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
      <button class="btn btn-primary" type="submit">Crear cuatro asignaciones</button>
    </form>
  </div>
@else
  <div class="card ff-card mb-4">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr><th>Juez</th><th>Tipo</th><th>Estado</th><th>Rúbrica</th><th>Plazo</th></tr></thead>
        <tbody>
        @foreach($assignments as $assignment)
          <tr>
            <td>{{ $assignment->judgeProfile->user->name }}</td>
            <td>{{ $assignment->type->label() }}</td>
            <td>{{ $assignment->status->label() }}</td>
            <td>v{{ $assignment->rubricVersion->version }}</td>
            <td>{{ $assignment->due_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }}</td>
          </tr>
          @if($assignment->conflict && $assignment->conflict->status === \App\Enums\JudgeConflictStatus::Declared)
            <tr><td colspan="5">
              <div class="alert alert-warning mb-0">
                <strong>{{ $assignment->conflict->type->label() }}</strong>
                @if($assignment->conflict->explanation)<p class="mb-2">{{ $assignment->conflict->explanation }}</p>@endif
                @if($assignment->type === \App\Enums\JudgeAssignmentType::Initial)
                  <form method="POST" action="{{ route('panel.assignments.conflicts.resolve', $assignment->conflict) }}" novalidate>
                    @csrf
                    <div class="mb-2">
                      <label class="form-label" for="substitute-{{ $assignment->id }}">Juez sustituto</label>
                      <select class="form-select @error('substitute_judge_profile') is-invalid @enderror" id="substitute-{{ $assignment->id }}" name="substitute_judge_profile" required>
                        <option value="">Selecciona un sustituto</option>
                        @foreach($substitutes as $substitute)
                          <option value="{{ $substitute->public_id }}" @selected(old('substitute_judge_profile') === $substitute->public_id)>{{ $substitute->user->name }} — Sin límite</option>
                        @endforeach
                      </select>
                      @error('substitute_judge_profile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-2"><label class="form-label" for="reason-{{ $assignment->id }}">Razón de reasignación</label><textarea class="form-control" id="reason-{{ $assignment->id }}" name="reason" minlength="20" maxlength="1000" required>{{ old('reason') }}</textarea></div>
                    <div class="mb-2"><label class="form-label" for="password-{{ $assignment->id }}">Contraseña actual</label><input class="form-control" id="password-{{ $assignment->id }}" name="current_password" type="password" autocomplete="current-password" required></div>
                    @error('replacement')<div class="alert alert-danger py-2" role="alert">{{ $message }}</div>@enderror
                    <button class="btn btn-warning" type="submit">Resolver y reasignar</button>
                  </form>
                @else
                  <p class="mb-0 mt-2">El conflicto de un sustituto deja la cobertura incompleta. No existe una cadena de reemplazo aprobada.</p>
                @endif
              </div>
            </td></tr>
          @endif
        @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif
@endsection
