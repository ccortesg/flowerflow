@extends('layouts.flowerflow')

@section('title', 'Detalle de asignación')

@section('content')
<section class="ff-narrow-card" aria-labelledby="assignment-title">
  <div class="card ff-card p-4 p-lg-5">
    <a href="{{ route('judge.assignments.index') }}" class="mb-3">← Volver a mis asignaciones</a>
    <h1 id="assignment-title" class="h2">Asignación {{ $assignment->public_id }}</h1>
    <dl class="row">
      <dt class="col-sm-4">Categoría</dt><dd class="col-sm-8">{{ $assignment->submissionVersion->submission->category->name }}</dd>
      <dt class="col-sm-4">Plazo</dt><dd class="col-sm-8">{{ $assignment->due_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s') }} (Hermosillo)</dd>
      <dt class="col-sm-4">Estado</dt><dd class="col-sm-8">{{ $assignment->status->label() }}</dd>
    </dl>
    @if($assignment->status === \App\Enums\JudgeAssignmentStatus::Active && ! $assignment->conflict)
      <section class="border rounded p-3 p-md-4 mb-4" aria-labelledby="assignment-decisions-title">
        <h2 id="assignment-decisions-title" class="h4">Tu siguiente acción</h2>
        <p class="mb-3">Revisa el paquete y avanza con tu evaluación. Si existe un conflicto, decláralo antes de evaluar.</p>
        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">
          @if($evaluation)
            <a class="btn btn-flower" href="#evaluation-form">Continuar evaluación</a>
          @elseif($canStartEvaluation)
            <form method="POST" action="{{ route('judge.assignments.evaluation.store', $assignment) }}">@csrf<button class="btn btn-flower" type="submit">Iniciar evaluación</button></form>
          @else
            <button class="btn btn-flower" type="button" disabled aria-describedby="evaluation-unavailable-help">Iniciar evaluación</button>
          @endif
          <a class="btn btn-outline-warning" href="#declare-conflict">Declarar conflicto</a>
        </div>
        @unless($evaluation || $canStartEvaluation)<p id="evaluation-unavailable-help" class="small text-secondary mt-2 mb-0">La evaluación se habilitará cuando el paquete ciego y las invariantes de la asignación estén vigentes.</p>@endunless
      </section>
    @endif
    @if($package)
      @php($payload = $package->payload)
      <div class="alert alert-warning" role="note"><strong>Anonimización estructural.</strong> Se ocultan los datos estructurados de identidad y operación. El texto, los enlaces o los anexos pueden identificar a su autor; este paquete no promete anonimato semántico.</div>
      <article aria-labelledby="blind-package-title">
        <h2 id="blind-package-title" class="h4">Proyecto asignado</h2>
        <dl class="row">
          <dt class="col-sm-4">Categoría</dt><dd class="col-sm-8">{{ data_get($payload, 'category.name') }}</dd>
          <dt class="col-sm-4">Modalidad</dt><dd class="col-sm-8">{{ data_get($payload, 'submission.participation_type') === 'team' ? 'Equipo' : 'Individual' }}</dd>
          <dt class="col-sm-4">Título</dt><dd class="col-sm-8">{{ data_get($payload, 'submission.title') }}</dd>
        </dl>
        <h3 class="h5">Resumen</h3><p>{{ data_get($payload, 'submission.summary') }}</p>
        <h3 class="h5">Descripción</h3><div>{!! data_get($payload, 'submission.description_html') !!}</div>
        <h3 class="h5 mt-4">Enlaces externos</h3>
        <ul>@forelse(data_get($payload, 'external_links', []) as $link)<li><a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">{{ $link['kind'] === 'youtube' ? 'Video del proyecto' : 'Carpeta pública del proyecto' }}</a> <small>{{ $link['normalized_host'] }}</small></li>@empty<li>Sin enlaces externos.</li>@endforelse</ul>
        <h3 class="h5 mt-4">Anexos evaluables</h3>
        <ul class="list-group list-group-flush mb-4">
          @forelse($package->files as $file)
            <li class="list-group-item d-flex flex-wrap justify-content-between gap-2"><span>{{ $file->neutral_label }} <small>({{ $file->file_class->label() }}, {{ number_format($file->expected_size_bytes / 1024, 1) }} KiB)</small></span><a href="{{ route('judge.assignments.packages.files.download', [$assignment, $file]) }}">Descargar</a></li>
          @empty<li class="list-group-item">Sin anexos capturados.</li>@endforelse
        </ul>
      </article>
    @else
      <div class="alert alert-info">El paquete ciego activo todavía no está disponible. No se genera automáticamente desde este acceso.</div>
    @endif

    @if($evaluationUnavailable)
      <section id="evaluation-form" class="mt-4" aria-labelledby="evaluation-title">
        <h2 id="evaluation-title" class="h4">Evaluación</h2>
        <div class="alert alert-warning" role="alert">El borrador no está disponible porque una invariante de asignación, rúbrica o paquete dejó de cumplirse. No se modificó ningún dato.</div>
      </section>
    @elseif($evaluation)
      @php($revision = $evaluation->currentRevision)
      @php($scoresByCriterion = $revision->scores->keyBy('rubric_criterion_id'))
      @php($capturedCriteria = $revision->scores->whereNotNull('score')->count())
      @php($criterionCount = $evaluation->rubricVersion->criteria->count())
      <section id="evaluation-form" class="mt-4" aria-labelledby="evaluation-title">
        <h2 id="evaluation-title" class="h4">Evaluación en borrador</h2>
        <div class="alert alert-info" role="status">
          Este borrador aún no se ha enviado. M6 sólo permite capturarlo y guardarlo.
        </div>
        <dl class="row">
          <dt class="col-sm-4">Estado</dt><dd class="col-sm-8">{{ $evaluation->status->label() }}</dd>
          <dt class="col-sm-4">Progreso</dt><dd class="col-sm-8"><progress value="{{ $capturedCriteria }}" max="{{ $criterionCount }}">{{ $capturedCriteria }} de {{ $criterionCount }}</progress> {{ $capturedCriteria }} de {{ $criterionCount }} criterios capturados</dd>
          <dt class="col-sm-4">Total del servidor</dt>
          <dd class="col-sm-8">{{ $evaluationTotalDisplay === null ? 'Disponible al capturar todos los criterios.' : $evaluationTotalDisplay.' de 100.00' }}</dd>
        </dl>

        @if($evaluationReadOnly)
          <div class="alert alert-warning" role="status">El plazo terminó. El borrador se conserva sólo para lectura y ya no puede modificarse.</div>
          @foreach($evaluation->rubricVersion->criteria as $criterion)
            @php($scoreRow = $scoresByCriterion->get($criterion->id))
            <section class="border rounded p-3 mb-3" aria-labelledby="criterion-read-{{ $criterion->code }}">
              <h3 id="criterion-read-{{ $criterion->code }}" class="h5">{{ $criterion->label }} — {{ $criterion->weight }} %</h3>
              <p class="mb-1">Rango 0.0000–10.0000; paso 0.5000.</p>
              <p class="mb-1"><strong>Puntaje:</strong> {{ $scoreRow->score ?? 'Sin capturar' }}</p>
              <p class="mb-0 text-break"><strong>Comentario:</strong> {{ $scoreRow->comment ?? 'Sin comentario' }}</p>
            </section>
          @endforeach
          <h3 class="h5">Comentario general</h3>
          <p class="text-break">{{ $revision->general_comment ?? 'Sin comentario general' }}</p>
        @else
          <form method="POST" action="{{ route('judge.assignments.evaluation.update', $assignment) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="lock_version" value="{{ $evaluation->lock_version }}">

            @foreach($evaluation->rubricVersion->criteria as $criterion)
              @php($scoreRow = $scoresByCriterion->get($criterion->id))
              @php($scoreError = "criteria.{$loop->index}.score")
              @php($commentError = "criteria.{$loop->index}.comment")
              <fieldset class="border rounded p-3 mb-3">
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
              <textarea class="form-control @error('general_comment') is-invalid @enderror" id="general-comment" name="general_comment" maxlength="2000" rows="5" aria-describedby="general-comment-help @error('general_comment') general-comment-error @enderror">{{ old('general_comment', $revision->general_comment) }}</textarea>
              <small id="general-comment-help" class="text-secondary">Puede quedar vacío en M6; máximo 2,000 caracteres.</small>
              @error('general_comment')<div class="invalid-feedback" id="general-comment-error">{{ $message }}</div>@enderror
            </div>

            <button class="btn btn-flower" type="submit">Guardar borrador</button>
            <p class="mt-2 mb-0 text-secondary" aria-live="polite">El servidor es la única autoridad de componentes, progreso y total.</p>
          </form>
        @endif
      </section>
    @elseif($canStartEvaluation)
      <section class="mt-4" aria-labelledby="evaluation-title">
        <h2 id="evaluation-title" class="h4">Evaluación</h2>
        <p>La evaluación todavía no existe. Usa la acción principal “Iniciar evaluación” para crear el borrador con los criterios de la rúbrica fijada.</p>
      </section>
    @elseif($package && $assignment->status === \App\Enums\JudgeAssignmentStatus::Active)
      <section class="mt-4" aria-labelledby="evaluation-title">
        <h2 id="evaluation-title" class="h4">Evaluación</h2>
        <div class="alert alert-info" role="status">No es posible iniciar una evaluación con las condiciones actuales de plazo o integridad. Abrir o refrescar esta página no crea ningún borrador.</div>
      </section>
    @endif

    @if($assignment->conflict)
      <div class="alert alert-warning" role="status">Conflicto declarado: {{ $assignment->conflict->type->label() }}. La asignación permanece bloqueada.</div>
    @elseif($assignment->status === \App\Enums\JudgeAssignmentStatus::Active)
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
