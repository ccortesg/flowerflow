@php($scoresByCriterion = $revision->scores->keyBy('rubric_criterion_id'))
<dl class="row">
  <dt class="col-sm-4">Revisión</dt><dd class="col-sm-8">{{ $revision->revision_number }}</dd>
  <dt class="col-sm-4">Estado</dt><dd class="col-sm-8">{{ $revision->status->label() }}</dd>
  <dt class="col-sm-4">Total del servidor</dt><dd class="col-sm-8">{{ $revision->total_raw === null ? 'Incompleto' : app(\App\Services\EvaluationDraftCalculator::class)->display($revision->total_raw).' de 100.00' }}</dd>
</dl>
@foreach($evaluation->rubricVersion->criteria as $criterion)
  @php($scoreRow = $scoresByCriterion->get($criterion->id))
  <section class="border rounded p-3 mb-3" aria-labelledby="read-{{ $revision->id }}-{{ $criterion->code }}">
    <h3 id="read-{{ $revision->id }}-{{ $criterion->code }}" class="h6">{{ $criterion->label }} — {{ $criterion->weight }} %</h3>
    <p class="mb-1"><strong>Puntaje:</strong> {{ $scoreRow?->score ?? 'Sin capturar' }}</p>
    <p class="mb-0 text-break"><strong>Comentario:</strong> {{ $scoreRow?->comment ?? 'Sin comentario' }}</p>
  </section>
@endforeach
<h3 class="h6">Comentario general</h3>
<p class="text-break">{{ $revision->general_comment ?? 'Sin comentario general' }}</p>
