@extends('layouts.flowerflow')

@section('title', 'Conflicto de guardado')

@section('content')
<section class="container py-5" aria-labelledby="conflict-title" aria-describedby="conflict-description">
  <div class="card ff-card ff-narrow-card p-4 p-lg-5">
    <p class="ff-kicker mb-2">Borrador protegido</p>
    <h1 id="conflict-title" class="h2">No sobrescribimos cambios más recientes</h1>
    <p id="conflict-description">{{ $message ?? 'Otra pestaña guardó este borrador. Recarga antes de volver a guardar.' }}</p>
    <div class="alert alert-warning" role="alert">Ningún dato fue sobrescrito. La versión más reciente permanece guardada en el servidor.</div>
    <a class="btn btn-flower align-self-start" href="{{ isset($assignment) ? route('judge.assignments.show', $assignment) : route('judge.assignments.index') }}">Recargar la evaluación</a>
  </div>
</section>
@endsection
