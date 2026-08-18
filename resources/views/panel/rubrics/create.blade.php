@extends('layouts.flowerflow')

@section('title', 'Nueva versión de rúbrica')

@section('content')
<p class="ff-kicker mb-1">Rúbrica global</p>
<h1>Nueva versión</h1>
<p class="text-body-secondary">Se creará un borrador para {{ $competition->name }}. La creación nunca activa la versión.</p>

<form method="POST" action="{{ route('panel.rubrics.store') }}" class="mt-4" novalidate>
  @csrf
  @include('panel.rubrics.partials.form', ['rubric' => null, 'version' => $nextVersion])
  <div class="d-flex flex-wrap gap-2 mt-4">
    <button class="btn btn-flower" type="submit">Crear borrador</button>
    <a class="btn btn-outline-dark" href="{{ route('panel.rubrics.index') }}">Cancelar</a>
  </div>
</form>
@endsection
