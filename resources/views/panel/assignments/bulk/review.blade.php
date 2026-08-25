@extends('layouts.flowerflow')

@section('title', 'Revisar asignación de varias propuestas')

@section('content')
<div class="mb-4">
  <p class="ff-kicker mb-1">Confirmación</p>
  <h1 class="h2 mb-1">Revisar operación</h1>
  <p class="text-secondary mb-0">Este preflight no creó ni modificó expedientes, paquetes o asignaciones.</p>
</div>

<div class="alert alert-warning" role="alert">
  La ejecución puede tener éxito parcial. Cada propuesta se confirma dentro de su propia transacción.
</div>

<section class="card ff-card p-4 mb-4" aria-labelledby="bulk-context-title">
  <h2 class="h5" id="bulk-context-title">Contexto</h2>
  <dl class="row mb-0">
    <dt class="col-sm-4">Juez</dt><dd class="col-sm-8">{{ $judge->user->name }} · {{ $judge->assignment_role->label() }}</dd>
    <dt class="col-sm-4">Propuestas</dt><dd class="col-sm-8">{{ $submissions->count() }}</dd>
    <dt class="col-sm-4">Correo consolidado</dt><dd class="col-sm-8">{{ $validated['notify_judge'] ? 'Sí' : 'No' }}</dd>
  </dl>
</section>

<section class="card ff-card mb-4" aria-labelledby="bulk-proposals-title">
  <div class="card-header bg-transparent"><h2 class="h5 mb-0" id="bulk-proposals-title">Propuestas seleccionadas</h2></div>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th>Propuesta</th><th>Categoría</th><th>Admisibilidad actual</th><th>Paquete actual</th></tr></thead>
      <tbody>
      @foreach($submissions as $submission)
        <tr>
          <td><strong>{{ $submission->folio ?: 'Sin folio' }}</strong><span class="d-block small text-secondary">{{ $submission->title }}</span><code>{{ $submission->public_id }}</code></td>
          <td>{{ $submission->category->name }}</td>
          <td>{{ $submission->eligibilityReview->status->label() }}</td>
          <td>{{ $submission->versions->first()->blindReviewPackage?->status?->label() ?? 'Sin paquete' }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</section>

<section class="card ff-card p-4 mb-4" aria-labelledby="bulk-shared-title">
  <h2 class="h5" id="bulk-shared-title">Información que se registrará</h2>
  <dl class="mb-0">
    <dt>Motivo público de admisión</dt><dd class="text-break" style="white-space:pre-wrap">{{ $validated['participant_reason'] }}</dd>
    <dt>Nota interna compartida</dt><dd class="text-break" style="white-space:pre-wrap">{{ $validated['internal_notes'] ?: 'Sin nota interna' }}</dd>
    <dt>Razón del paquete</dt><dd class="text-break" style="white-space:pre-wrap">{{ $validated['package_reason'] }}</dd>
    <dt>Razón de asignación</dt><dd class="text-break mb-0" style="white-space:pre-wrap">{{ $validated['assignment_reason'] }}</dd>
  </dl>
</section>

<form method="POST" action="{{ route('panel.assignments.bulk.store') }}">
  @csrf
  <input type="hidden" name="intent" value="{{ $intent }}">
  <fieldset class="card ff-card p-4 mb-4">
    <legend class="h5">Confirmaciones obligatorias</legend>
    <div class="form-check mb-2">
      <input class="form-check-input" type="checkbox" id="confirm_admission" name="confirm_admission" value="1" required>
      <label class="form-check-label" for="confirm_admission">Confirmo la admisión de los expedientes elegibles con el motivo mostrado.</label>
    </div>
    <div class="form-check mb-2">
      <input class="form-check-input" type="checkbox" id="confirm_packages" name="confirm_packages" value="1" required>
      <label class="form-check-label" for="confirm_packages">Confirmo la generación, validación y activación de paquetes ciegos.</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="confirm_assignment" name="confirm_assignment" value="1" required>
      <label class="form-check-label" for="confirm_assignment">Confirmo la asignación manual al juez seleccionado.</label>
    </div>
  </fieldset>
  <div class="d-flex flex-wrap gap-2">
    <button class="btn btn-flower" type="submit">Ejecutar operación</button>
    <a class="btn btn-outline-secondary" href="{{ route('panel.assignments.bulk.create', ['judge_profile' => $judge->public_id]) }}">Volver sin ejecutar</a>
  </div>
</form>
@endsection
