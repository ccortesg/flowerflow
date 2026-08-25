@php($scoresByCriterion = $revision->scores->keyBy('rubric_criterion_id'))
@foreach($evaluation->rubricVersion->criteria as $criterion)
  @php($scoreRow = $scoresByCriterion->get($criterion->id))
  @php($scoreError = "criteria.{$loop->index}.score")
  @php($commentError = "criteria.{$loop->index}.comment")
  <fieldset class="border rounded p-3 mb-3">
    <legend class="h6 px-2">{{ $criterion->label }} — {{ $criterion->weight }} %</legend>
    <p id="draft-help-{{ $criterion->code }}" class="text-secondary">Rango 0.0000–10.0000; paso exacto 0.5000.</p>
    <input type="hidden" name="criteria[{{ $loop->index }}][code]" value="{{ $criterion->code }}">
    <div class="mb-3">
      <label class="form-label" for="draft-score-{{ $criterion->code }}">Puntaje</label>
      <input class="form-control @error($scoreError) is-invalid @enderror" type="number" inputmode="decimal" min="0" max="10" step="0.5" id="draft-score-{{ $criterion->code }}" name="criteria[{{ $loop->index }}][score]" value="{{ old("criteria.{$loop->index}.score", $scoreRow?->score) }}" aria-describedby="draft-help-{{ $criterion->code }}">
      @error($scoreError)<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div>
      <label class="form-label" for="draft-comment-{{ $criterion->code }}">Comentario del criterio (opcional)</label>
      <textarea class="form-control @error($commentError) is-invalid @enderror" id="draft-comment-{{ $criterion->code }}" name="criteria[{{ $loop->index }}][comment]" maxlength="1000" rows="3">{{ old("criteria.{$loop->index}.comment", $scoreRow?->comment) }}</textarea>
      @error($commentError)<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
  </fieldset>
@endforeach
<div class="mb-3">
  <label class="form-label" for="draft-general-comment">Comentario general</label>
  <textarea class="form-control @error('general_comment') is-invalid @enderror" id="draft-general-comment" name="general_comment" maxlength="2000" rows="6" aria-describedby="draft-general-help">{{ old('general_comment', $revision->general_comment) }}</textarea>
  <small id="draft-general-help" class="text-secondary">Puede quedar vacío al guardar; para enviar debe contener entre 100 y 2,000 caracteres.</small>
  @error('general_comment')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
