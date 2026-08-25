@extends('layouts.flowerflow')

@section('title', 'Evaluación')

@section('content')
@php($revision = $evaluation->currentRevision)
@php($scoresByCriterion = $revision->scores->keyBy('rubric_criterion_id'))
@php($capturedCriteria = $revision->scores->whereNotNull('score')->count())
@php($criterionCount = $evaluation->rubricVersion->criteria->count())
<section class="ff-evaluation-shell" aria-labelledby="evaluation-title">
  <div class="card ff-card p-3 p-md-4 p-lg-5">
    <a href="{{ route('judge.assignments.index') }}" class="mb-3 align-self-start">← Volver a mis asignaciones</a>
    @include('judge.assignments._wizard-stepper', ['currentStep' => 3])

    <p class="ff-kicker mb-1">Paso 3 de 4</p>
    <h1 id="evaluation-title" class="h2">Evaluación — revisión {{ $revision->revision_number }}</h1>
    @if($evaluation->status === \App\Enums\EvaluationStatus::Reopened)
      <div class="alert alert-warning" role="status">La administración reabrió esta evaluación. Revisa cuidadosamente los datos antes de volver a enviarla.</div>
    @elseif($evaluation->status === \App\Enums\EvaluationStatus::Submitted)
      <div class="alert alert-success" role="status">La evaluación fue enviada. Esta revisión está sellada y permanece sólo para lectura.</div>
    @else
      <div class="alert alert-info" role="status">Este borrador aún no se ha enviado.</div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger" role="alert" tabindex="-1" data-evaluation-errors>
        <h2 class="h5">Revisa los siguientes campos</h2>
        <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
      </div>
    @else
      <div class="alert alert-danger" role="alert" tabindex="-1" data-evaluation-errors hidden></div>
    @endif

    <dl class="row ff-evaluation-summary">
      <dt class="col-sm-4">Estado</dt><dd class="col-sm-8">{{ $evaluation->status->label() }}</dd>
      <dt class="col-sm-4">Progreso</dt><dd class="col-sm-8"><progress data-evaluation-progress value="{{ $capturedCriteria }}" max="{{ $criterionCount }}">{{ $capturedCriteria }} de {{ $criterionCount }}</progress> <span data-evaluation-progress-text>{{ $capturedCriteria }} de {{ $criterionCount }} criterios capturados</span></dd>
      <dt class="col-sm-4">Total del servidor</dt>
      <dd class="col-sm-8" data-evaluation-total>{{ $evaluationTotalDisplay === null ? 'Disponible al capturar todos los criterios.' : $evaluationTotalDisplay.' de 100.00' }}</dd>
    </dl>

    @if($evaluationReadOnly)
      @if($evaluation->status !== \App\Enums\EvaluationStatus::Submitted)<div class="alert alert-warning" role="status">Las mutaciones no están disponibles. La revisión se conserva sólo para lectura.</div>@endif
      @foreach($evaluation->rubricVersion->criteria as $criterion)
        @php($scoreRow = $scoresByCriterion->get($criterion->id))
        <section class="ff-criterion-card" aria-labelledby="criterion-read-{{ $criterion->code }}">
          <h2 id="criterion-read-{{ $criterion->code }}" class="h5">{{ $criterion->label }} — {{ $criterion->weight }} %</h2>
          <p class="mb-1">Rango 0.0000–10.0000; paso 0.5000.</p>
          <p class="mb-1"><strong>Puntaje:</strong> {{ $scoreRow->score ?? 'Sin capturar' }}</p>
          <p class="mb-0 text-break"><strong>Comentario:</strong> {{ $scoreRow->comment ?? 'Sin comentario' }}</p>
        </section>
      @endforeach
      <h2 class="h5">Comentario general</h2>
      <p class="text-break">{{ $revision->general_comment ?? 'Sin comentario general' }}</p>
    @else
      <form method="POST" action="{{ route('judge.assignments.evaluation.update', $assignment) }}" data-evaluation-autosave data-autosave-interval="30000">
        @csrf
        @method('PATCH')
        <input type="hidden" name="lock_version" value="{{ $evaluation->lock_version }}" data-evaluation-lock-version>

        @foreach($evaluation->rubricVersion->criteria as $criterion)
          @php($scoreRow = $scoresByCriterion->get($criterion->id))
          @php($scoreError = "criteria.{$loop->index}.score")
          @php($commentError = "criteria.{$loop->index}.comment")
          <fieldset class="ff-criterion-card">
            <legend class="h5 px-2">{{ $criterion->label }} — {{ $criterion->weight }} %</legend>
            <p id="criterion-help-{{ $criterion->code }}" class="text-secondary">Rango 0.0000–10.0000; paso exacto 0.5000.</p>
            <input type="hidden" name="criteria[{{ $loop->index }}][code]" value="{{ $criterion->code }}">
            <div class="mb-3">
              <label class="form-label" for="score-{{ $criterion->code }}">Puntaje</label>
              <input class="form-control @error($scoreError) is-invalid @enderror" type="number" inputmode="decimal" min="0" max="10" step="0.5" id="score-{{ $criterion->code }}" name="criteria[{{ $loop->index }}][score]" value="{{ old("criteria.{$loop->index}.score", $scoreRow->score) }}" aria-describedby="criterion-help-{{ $criterion->code }} @error($scoreError) score-error-{{ $criterion->code }} @enderror">
              @error($scoreError)<div class="invalid-feedback" id="score-error-{{ $criterion->code }}">{{ $message }}</div>@enderror
            </div>
            <div>
              <label class="form-label" for="comment-{{ $criterion->code }}">Comentario del criterio (opcional)</label>
              <textarea class="form-control @error($commentError) is-invalid @enderror" id="comment-{{ $criterion->code }}" name="criteria[{{ $loop->index }}][comment]" maxlength="1000" rows="3" aria-describedby="comment-help-{{ $criterion->code }} @error($commentError) comment-error-{{ $criterion->code }} @enderror">{{ old("criteria.{$loop->index}.comment", $scoreRow->comment) }}</textarea>
              <small id="comment-help-{{ $criterion->code }}" class="text-secondary">Máximo 1,000 caracteres.</small>
              @error($commentError)<div class="invalid-feedback" id="comment-error-{{ $criterion->code }}">{{ $message }}</div>@enderror
            </div>
          </fieldset>
        @endforeach

        <div class="mb-3">
          <label class="form-label" for="general-comment">Comentario general (opcional en borrador)</label>
          <textarea class="form-control @error('general_comment') is-invalid @enderror" id="general-comment" name="general_comment" maxlength="2000" rows="6" aria-describedby="general-comment-help @error('general_comment') general-comment-error @enderror">{{ old('general_comment', $revision->general_comment) }}</textarea>
          <small id="general-comment-help" class="text-secondary">Puede quedar vacío al guardar; para enviar debe contener entre 100 y 2,000 caracteres.</small>
          @error('general_comment')<div class="invalid-feedback" id="general-comment-error">{{ $message }}</div>@enderror
        </div>

        <div class="ff-evaluation-actionbar">
          <div>
            <p class="mb-0 fw-semibold" data-autosave-status aria-live="polite">Todos los cambios guardados.</p>
            <small class="text-secondary">El servidor es la única autoridad de progreso y total.</small>
          </div>
          <div class="d-flex flex-column flex-sm-row gap-2">
            <button class="btn btn-outline-primary" name="intent" value="save" type="submit">Guardar borrador</button>
            @if($finalizationEnabled)<button class="btn btn-flower" name="intent" value="review" type="submit">Revisar y enviar</button>@endif
          </div>
        </div>
      </form>
    @endif

    @if($evaluation->revisions->count() > 1)
      <section class="mt-4" aria-labelledby="judge-history-title">
        <h2 id="judge-history-title" class="h4">Historial de revisiones</h2>
        <p class="text-secondary">Las revisiones enviadas anteriores permanecen inmutables.</p>
        @foreach($evaluation->revisions->where('id', '<>', $revision->id)->sortByDesc('revision_number') as $historicalRevision)
          <details class="border rounded p-3 mb-2"><summary>Revisión {{ $historicalRevision->revision_number }} — {{ $historicalRevision->status->label() }}</summary><div class="mt-3">@include('evaluations._revision-read', ['revision' => $historicalRevision])</div></details>
        @endforeach
      </section>
    @endif
  </div>
</section>
@endsection
