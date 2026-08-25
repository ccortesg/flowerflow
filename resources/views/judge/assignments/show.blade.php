@extends('layouts.flowerflow')

@section('title', 'Detalle de asignación')

@section('content')
<section class="ff-evaluation-shell" aria-labelledby="assignment-title">
  <div class="card ff-card p-3 p-md-4 p-lg-5">
    <a href="{{ route('judge.assignments.index') }}" class="mb-3 align-self-start">← Volver a mis asignaciones</a>
    @include('judge.assignments._wizard-stepper', ['currentStep' => 1])

    <p class="ff-kicker mb-1">Paso 1 de 4</p>
    <h1 id="assignment-title" class="h2">Inicio de evaluación</h1>
    <p class="text-secondary">Confirma el contexto de la asignación antes de consultar el proyecto y capturar la rúbrica.</p>

    <dl class="row ff-detail-list">
      <dt class="col-sm-4">Asignación</dt><dd class="col-sm-8"><code>{{ $assignment->public_id }}</code></dd>
      <dt class="col-sm-4">Categoría</dt><dd class="col-sm-8">{{ $assignment->submissionVersion->submission->category->name }}</dd>
      <dt class="col-sm-4">Plazo</dt><dd class="col-sm-8">{{ $assignment->due_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s') }} (Hermosillo)</dd>
      <dt class="col-sm-4">Estado</dt><dd class="col-sm-8">{{ $assignment->status->label() }}</dd>
    </dl>

    @if($assignment->status === \App\Enums\JudgeAssignmentStatus::Active && ! $assignment->conflict)
      <section class="ff-next-action" aria-labelledby="assignment-decisions-title">
        <h2 id="assignment-decisions-title" class="h4">Tu siguiente acción</h2>
        <p>
          @if($hasSubmittedRevision)
            La evaluación fue enviada. Puedes consultar la revisión vigente y su historial inmutable.
          @elseif($evaluation)
            El borrador ya está iniciado. Continúa con el proyecto o abre directamente la evaluación.
          @else
            Inicia la evaluación para habilitar el proyecto asignado. Si existe un conflicto, decláralo antes de evaluar.
          @endif
        </p>
        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">
          @if($evaluation)
            <a class="btn btn-flower" href="{{ route('judge.assignments.evaluation.show', $assignment) }}">{{ $evaluation->status === \App\Enums\EvaluationStatus::Submitted ? 'Ver evaluación' : 'Continuar evaluación' }}</a>
            <a class="btn btn-outline-primary" href="{{ route('judge.assignments.project.show', $assignment) }}">Ver proyecto asignado</a>
          @elseif($canStartEvaluation)
            <form method="POST" action="{{ route('judge.assignments.evaluation.store', $assignment) }}">@csrf<button class="btn btn-flower" type="submit">Iniciar evaluación</button></form>
            <a class="btn btn-outline-warning" href="#declare-conflict">Declarar conflicto</a>
          @else
            <button class="btn btn-flower" type="button" disabled aria-describedby="evaluation-unavailable-help">Iniciar evaluación</button>
          @endif
        </div>
        @unless($evaluation || $canStartEvaluation)<p id="evaluation-unavailable-help" class="small text-secondary mt-2 mb-0">La evaluación se habilitará cuando el paquete ciego y las invariantes de la asignación estén vigentes.</p>@endunless
      </section>
    @endif

    @if($evaluationUnavailable)
      <div class="alert alert-warning mt-4" role="alert">La evaluación no está disponible porque una invariante de asignación, rúbrica o paquete dejó de cumplirse. No se modificó ningún dato.</div>
    @endif

    @if($assignment->conflict)
      <div class="alert alert-warning mt-4" role="status">Conflicto declarado: {{ $assignment->conflict->type->label() }}. La asignación permanece bloqueada.</div>
    @elseif($assignment->status === \App\Enums\JudgeAssignmentStatus::Active && ! $hasSubmittedRevision)
      <details id="declare-conflict" class="border rounded p-3 mt-4">
        <summary class="fw-bold">Declarar conflicto</summary>
        <p class="mt-3">Usa esta acción sólo si existe una situación que impide evaluar con imparcialidad. Al confirmar perderás inmediatamente el acceso a la evaluación.</p>
        <form method="POST" action="{{ route('judge.assignments.conflicts.store', $assignment) }}">
          @csrf
          <fieldset>
            <legend class="form-label">Tipo de conflicto</legend>
            @foreach($conflictTypes as $type)
              <div class="form-check mb-2"><input class="form-check-input" type="radio" name="type" id="type-{{ $type->value }}" value="{{ $type->value }}" required><label class="form-check-label" for="type-{{ $type->value }}">{{ $type->label() }}</label></div>
            @endforeach
          </fieldset>
          <div class="my-3"><label class="form-label" for="explanation">Explicación (sólo para “Otro conflicto”)</label><textarea class="form-control" id="explanation" name="explanation" maxlength="1000" aria-describedby="explanation-help"></textarea><small id="explanation-help" class="text-secondary">Si eliges otro conflicto, escribe entre 20 y 1,000 caracteres.</small></div>
          <button class="btn btn-outline-warning" type="submit">Confirmar declaración de conflicto</button>
        </form>
      </details>
    @endif
  </div>
</section>
@endsection
