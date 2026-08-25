@extends('layouts.flowerflow')

@section('title', 'Mis asignaciones')

@section('content')
<header class="mb-4">
  <p class="ff-kicker mb-1">Área de evaluación</p>
  <h1 class="h2 mb-2">Mis asignaciones</h1>
  <p class="text-secondary mb-0">Las vigentes aparecen primero, ordenadas por el plazo más próximo. No se muestran participantes ni otros jueces.</p>
</header>

@if($assignments->isEmpty())
  <div class="alert alert-info" role="status">No tienes asignaciones disponibles.</div>
@else
  <div class="row g-3">
  @foreach($assignments as $assignment)
    @php($revision = $assignment->evaluation?->currentRevision)
    @php($criterionCount = $assignment->rubricVersion?->criteria?->count() ?? $revision?->scores?->count() ?? 0)
    @php($captured = $revision?->scores?->whereNotNull('score')->count() ?? 0)
    @php($actionLabel = $assignment->status !== \App\Enums\JudgeAssignmentStatus::Active ? 'Ver detalle' : ($assignment->evaluation?->status === \App\Enums\EvaluationStatus::Submitted ? 'Ver evaluación' : ($assignment->evaluation ? 'Continuar evaluación' : 'Iniciar evaluación')))
    <div class="col-12 col-xl-6">
      <article class="card ff-card p-4 h-100" aria-labelledby="assignment-{{ $assignment->public_id }}">
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
          <h2 id="assignment-{{ $assignment->public_id }}" class="h5 mb-0">Asignación <code>{{ $assignment->public_id }}</code></h2>
          <span class="badge {{ $assignment->status === \App\Enums\JudgeAssignmentStatus::Active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $assignment->status->label() }}</span>
        </div>
        <dl class="row mb-3">
          <dt class="col-sm-5">Categoría</dt><dd class="col-sm-7">{{ $assignment->submissionVersion->submission->category->name }}</dd>
          <dt class="col-sm-5">Plazo</dt><dd class="col-sm-7">{{ $assignment->due_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }} (Hermosillo)</dd>
          <dt class="col-sm-5">Paquete</dt><dd class="col-sm-7">{{ $assignment->submissionVersion->blindReviewPackage?->status?->label() ?? 'No disponible' }}</dd>
          <dt class="col-sm-5">Progreso</dt><dd class="col-sm-7">@if($revision && $criterionCount)<progress value="{{ $captured }}" max="{{ $criterionCount }}">{{ $captured }} de {{ $criterionCount }}</progress> {{ $captured }} de {{ $criterionCount }}@elseSin iniciar @endif</dd>
        </dl>
        <a class="btn {{ $assignment->status === \App\Enums\JudgeAssignmentStatus::Active ? 'btn-flower' : 'btn-outline-dark' }} align-self-start mt-auto" href="{{ $assignment->evaluation ? route('judge.assignments.evaluation.show', $assignment) : route('judge.assignments.show', $assignment) }}">{{ $actionLabel }}</a>
      </article>
    </div>
  @endforeach
  </div>
  <div class="mt-4">{{ $assignments->links() }}</div>
@endif
@endsection
