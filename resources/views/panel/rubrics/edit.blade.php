@extends('layouts.flowerflow')

@section('title', 'Editar borrador de rúbrica')

@section('content')
<p class="ff-kicker mb-1">Rúbrica global</p>
<h1>Editar borrador v{{ $rubric->version }}</h1>
<p class="text-body-secondary">Sólo puede guardarse el contrato exacto aprobado. Las versiones activas o sustituidas son inmutables.</p>

<form method="POST" action="{{ route('panel.rubrics.update', $rubric) }}" class="mt-4" novalidate>
  @csrf
  @method('PUT')
  @include('panel.rubrics.partials.form', ['version' => $rubric->version])
  <div class="d-flex flex-wrap gap-2 mt-4">
    <button class="btn btn-flower" type="submit">Guardar borrador</button>
    <a class="btn btn-outline-dark" href="{{ route('panel.rubrics.show', $rubric) }}">Cancelar</a>
  </div>
</form>
@endsection
