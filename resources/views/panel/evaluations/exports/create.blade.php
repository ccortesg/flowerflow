@extends('layouts.flowerflow')

@section('title', 'Exportar evaluaciones')

@section('content')
<a href="{{ route('panel.evaluations.index') }}" class="d-inline-flex align-items-center gap-1 mb-3">← Volver a evaluaciones</a>
<p class="ff-kicker mb-1">Exportación confidencial</p>
<h1 class="h2">Exportar evaluaciones</h1>
<p class="text-secondary">Selecciona entre la revisión vigente de cada evaluación propuesta–juez o el historial append-only completo.</p>

<div class="alert alert-warning" role="alert">
  <strong>Este archivo contiene información confidencial.</strong> Descárgalo sólo en un equipo autorizado, no lo compartas por canales públicos y elimínalo cuando deje de ser necesario.
</div>

<form method="POST" action="{{ route('panel.evaluations.exports.store') }}">
  @csrf
  <section class="card ff-card p-4 mb-4" aria-labelledby="evaluation-export-scope-title">
    <h2 class="h4" id="evaluation-export-scope-title">Alcance del archivo</h2>
    <fieldset>
      <legend class="visually-hidden">Tipo de exportación</legend>
      @foreach($scopes as $scope)
        <div class="form-check border rounded p-3 ps-5 mb-3">
          <input class="form-check-input" type="radio" name="scope_version" id="scope-{{ $scope->value }}" value="{{ $scope->value }}" @checked(old('scope_version', \App\Enums\EvaluationExportScope::CurrentRevisions->value) === $scope->value)>
          <label class="form-check-label d-block" for="scope-{{ $scope->value }}">
            <strong>{{ $scope->label() }}</strong>
            @if($scope === \App\Enums\EvaluationExportScope::CurrentRevisions)
              <span class="d-block text-secondary mt-1">Una fila por juez/evaluación con su revisión vigente, estado, total, comentario general y desglose por rubro. {{ $evaluationCount }} evaluaciones y {{ $currentCriterionCount }} rubros actuales.</span>
            @else
              <span class="d-block text-secondary mt-1">Conserva el archivo histórico existente con todas las revisiones: {{ $revisionCount }} revisiones, {{ $criterionCount }} criterios y {{ $reopeningCount }} reaperturas de {{ $evaluationCount }} evaluaciones.</span>
            @endif
          </label>
        </div>
      @endforeach
      @error('scope_version')<p class="text-danger small">{{ $message }}</p>@enderror
    </fieldset>
    <p class="small text-secondary mb-0">Ambos alcances son globales y no dependen de los filtros o la paginación del listado. La propuesta procede de su versión inmutable enviada; los nombres de jueces reflejan el valor actual al exportar.</p>
  </section>

  <div class="d-flex flex-wrap gap-2">
    <button class="btn btn-flower" type="submit">
      <i class="ri-file-excel-2-line me-1" aria-hidden="true"></i> Generar exportación confidencial
    </button>
    <a class="btn btn-outline-secondary" href="{{ route('panel.evaluations.index') }}">Cancelar</a>
  </div>
</form>
@endsection
