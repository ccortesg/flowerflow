@extends('layouts.flowerflow')

@section('title', 'Detalle de evaluación')

@section('content')
<header class="mb-4">
  <a href="{{ route('panel.evaluations.index') }}">← Volver a evaluaciones</a>
  <p class="ff-kicker mt-3 mb-1">Evaluación {{ $evaluation->public_id }}</p>
  <h1 class="h2">Detalle e historial</h1>
</header>

<section class="card ff-card p-4 mb-4" aria-labelledby="evaluation-summary">
  <div class="d-flex flex-wrap justify-content-between gap-3">
    <div><h2 id="evaluation-summary" class="h4">Estado actual</h2><dl class="row mb-0">
      <dt class="col-sm-5">Juez sujeto</dt><dd class="col-sm-7">{{ $evaluation->judgeAssignment->judgeProfile->user->name }}</dd>
      <dt class="col-sm-5">Asignación</dt><dd class="col-sm-7"><code>{{ $evaluation->judgeAssignment->public_id }}</code></dd>
      <dt class="col-sm-5">Categoría</dt><dd class="col-sm-7">{{ $evaluation->judgeAssignment->submissionVersion->submission->category->name }}</dd>
      <dt class="col-sm-5">Estado</dt><dd class="col-sm-7">{{ $evaluation->status->label() }}</dd>
      <dt class="col-sm-5">Revisión vigente</dt><dd class="col-sm-7">{{ $evaluation->currentRevision->revision_number }}</dd>
      <dt class="col-sm-5">Plazo</dt><dd class="col-sm-7">{{ $evaluation->judgeAssignment->due_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s') }} (Hermosillo)</dd>
    </dl></div>
    <div class="d-flex flex-column align-items-start gap-2">
      @if($evaluation->status === \App\Enums\EvaluationStatus::Submitted && auth()->user()->can('reopen', $evaluation) && config('flowerflow.flags.evaluation_finalization'))
        <a class="btn btn-warning" href="{{ route('panel.evaluations.reopen', $evaluation) }}">Reabrir evaluación</a>
      @endif
      @if($evaluation->status === \App\Enums\EvaluationStatus::Reopened && auth()->user()->can('manageReopened', $evaluation))
        <a class="btn btn-outline-danger" href="{{ route('panel.evaluations.confirm', $evaluation) }}">Revisar envío administrativo</a>
      @endif
    </div>
  </div>
</section>

@if($evaluation->status === \App\Enums\EvaluationStatus::Reopened && auth()->user()->can('manageReopened', $evaluation))
<section class="card ff-card p-4 mb-4" aria-labelledby="admin-edit-title">
  <h2 id="admin-edit-title" class="h4">Editar revisión reabierta</h2>
  <div class="alert alert-warning" role="alert">Actúas en nombre del juez. Flower Flow conservará tu cuenta como actor real y no atribuirá esta acción al juez.</div>
  @if(config('flowerflow.flags.evaluation_finalization'))
    <form method="POST" action="{{ route('panel.evaluations.draft.update', $evaluation) }}">
      @csrf @method('PATCH')
      <input type="hidden" name="lock_version" value="{{ $evaluation->lock_version }}">
      @include('evaluations._draft-fields', ['revision' => $evaluation->currentRevision])
      <div class="mb-3"><label class="form-label" for="admin-current-password">Contraseña actual</label><input class="form-control @error('current_password') is-invalid @enderror" type="password" id="admin-current-password" name="current_password" required autocomplete="current-password">@error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
      <div class="d-flex flex-wrap gap-2"><button class="btn btn-outline-primary" name="intent" value="save" type="submit">Guardar borrador reabierto</button><button class="btn btn-flower" name="intent" value="review" type="submit">Guardar, revisar y enviar</button></div>
    </form>
  @else
    <div class="alert alert-info">Las mutaciones M7 están deshabilitadas; la evidencia permanece sólo para lectura.</div>
  @endif
</section>
@endif

<section class="card ff-card p-4 mb-4" aria-labelledby="history-title">
  <h2 id="history-title" class="h4">Historial de revisiones</h2>
  @foreach($evaluation->revisions->sortByDesc('revision_number') as $revision)
    <article class="border rounded p-3 mb-3" aria-labelledby="revision-{{ $revision->revision_number }}">
      <h3 id="revision-{{ $revision->revision_number }}" class="h5">Revisión {{ $revision->revision_number }} — {{ $revision->status->label() }}</h3>
      <dl class="row">
        <dt class="col-sm-4">Creada por</dt><dd class="col-sm-8">{{ $revision->createdBy->name }}</dd>
        <dt class="col-sm-4">Último guardado por</dt><dd class="col-sm-8">{{ $revision->lastSavedBy->name }}</dd>
        <dt class="col-sm-4">Enviada por</dt><dd class="col-sm-8">{{ $revision->submittedBy?->name ?? 'No enviada' }}</dd>
        <dt class="col-sm-4">Modo</dt><dd class="col-sm-8">{{ $revision->submission_mode?->label() ?? 'No aplica' }}</dd>
        <dt class="col-sm-4">Fecha de envío</dt><dd class="col-sm-8">{{ $revision->submitted_at?->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s') ?? 'No enviada' }}</dd>
      </dl>
      <details><summary>Ver contenido de la revisión</summary><div class="mt-3">@include('evaluations._revision-read')</div></details>
    </article>
  @endforeach
</section>

@if($evaluation->reopenings->isNotEmpty())
<section class="card ff-card p-4" aria-labelledby="reopenings-title">
  <h2 id="reopenings-title" class="h4">Reaperturas administrativas</h2>
  @foreach($evaluation->reopenings as $reopening)
    <article class="border rounded p-3 mb-3">
      <dl class="row mb-0">
        <dt class="col-sm-4">Revisión fuente</dt><dd class="col-sm-8">{{ $reopening->sourceRevision->revision_number }}</dd>
        <dt class="col-sm-4">Nueva revisión</dt><dd class="col-sm-8">{{ $reopening->targetRevision->revision_number }}</dd>
        <dt class="col-sm-4">Actor real</dt><dd class="col-sm-8">{{ $reopening->reopenedBy->name }}</dd>
        <dt class="col-sm-4">Fecha</dt><dd class="col-sm-8">{{ $reopening->reopened_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s') }}</dd>
        <dt class="col-sm-4">Motivo</dt><dd class="col-sm-8 text-break">{{ $reopening->reason }}</dd>
      </dl>
    </article>
  @endforeach
</section>
@endif
@endsection
