@extends('layouts.flowerflow')

@section('title', 'Exportar evaluaciones')

@section('content')
<a href="{{ route('panel.evaluations.index') }}" class="d-inline-flex align-items-center gap-1 mb-3">← Volver a evaluaciones</a>
<p class="ff-kicker mb-1">Exportación confidencial</p>
<h1 class="h2">Exportar evaluaciones</h1>
<p class="text-secondary">El archivo reunirá identidad de participantes y jueces con todas las revisiones, comentarios y calificaciones registradas.</p>

<div class="alert alert-warning" role="alert">
  <strong>Este archivo contiene información confidencial.</strong> Descárgalo sólo en un equipo autorizado, no lo compartas por canales públicos y elimínalo cuando deje de ser necesario.
</div>

<section class="card ff-card p-4 mb-4" aria-labelledby="evaluation-export-scope-title">
  <h2 class="h4" id="evaluation-export-scope-title">Alcance del archivo</h2>
  <dl class="row mb-0">
    <dt class="col-sm-7">Evaluaciones</dt><dd class="col-sm-5">{{ $evaluationCount }}</dd>
    <dt class="col-sm-7">Revisiones append-only</dt><dd class="col-sm-5">{{ $revisionCount }}</dd>
    <dt class="col-sm-7">Filas de criterios</dt><dd class="col-sm-5">{{ $criterionCount }}</dd>
    <dt class="col-sm-7">Reaperturas</dt><dd class="col-sm-5">{{ $reopeningCount }}</dd>
  </dl>
  <p class="small text-secondary mt-3 mb-0">Se incluyen todos los estados y todas las revisiones, sin depender de filtros o paginación. La identidad de la propuesta procede únicamente de su versión inmutable enviada.</p>
</section>

<form method="POST" action="{{ route('panel.evaluations.exports.store') }}" class="d-flex flex-wrap gap-2">
  @csrf
  <button class="btn btn-flower" type="submit">
    <i class="ri-file-excel-2-line me-1" aria-hidden="true"></i> Generar exportación confidencial
  </button>
  <a class="btn btn-outline-secondary" href="{{ route('panel.evaluations.index') }}">Cancelar</a>
</form>
@endsection
