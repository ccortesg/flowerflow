@extends('layouts.flowerflow')

@section('title', 'Confirmar envío de evaluación')

@section('content')
<section class="ff-narrow-card" aria-labelledby="confirm-title">
  <div class="card ff-card p-4 p-lg-5">
    @php($commentLength = mb_strlen(preg_replace('/^\s+|\s+$/u', '', (string) $revision->general_comment) ?? ''))
    @php($complete = $revision->total_raw !== null && $revision->scores->whereNull('score')->isEmpty() && $commentLength >= 100 && $commentLength <= 2000)
    <a href="{{ route('judge.assignments.evaluation.show', $assignment) }}" class="mb-3">← Volver a la evaluación</a>
    @include('judge.assignments._wizard-stepper', ['currentStep' => 4, 'evaluationComplete' => $complete, 'evaluationReadOnly' => false])
    <p class="ff-kicker mb-1">Revisión {{ $revision->revision_number }}</p>
    <h1 id="confirm-title" class="h2">Revisar y enviar</h1>
    <div class="alert alert-warning" role="alert"><strong>El envío es inmutable.</strong> Después de confirmar no podrás modificar esta revisión.</div>
    @include('evaluations._revision-read', ['evaluation' => $evaluation, 'revision' => $revision])

    @if(! $finalizationEnabled)
      <div class="alert alert-info" role="status">El envío final está deshabilitado. La revisión permanece disponible sólo para consulta desde esta pantalla.</div>
    @elseif(! $complete)
      <div class="alert alert-danger" role="alert">Completa todos los criterios y escribe un comentario general de al menos 100 caracteres antes de enviar.</div>
    @else
      <form method="POST" action="{{ route('judge.assignments.evaluation.submit', $assignment) }}">
        @csrf
        <input type="hidden" name="lock_version" value="{{ $evaluation->lock_version }}">
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" value="1" id="confirm-submission" name="confirm_submission" required>
          <label class="form-check-label" for="confirm-submission">Confirmo que revisé los datos y deseo enviar esta evaluación de forma inmutable.</label>
        </div>
        <button class="btn btn-flower" type="submit">Enviar evaluación</button>
      </form>
    @endif
  </div>
</section>
@endsection
