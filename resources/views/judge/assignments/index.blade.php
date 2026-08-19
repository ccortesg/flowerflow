@extends('layouts.flowerflow')

@section('title', 'Mis asignaciones')

@section('content')
<section class="ff-narrow-card" aria-labelledby="assignments-title">
  <div class="card ff-card p-4 p-lg-5">
    <p class="ff-kicker mb-2">Área de evaluación</p>
    <h1 id="assignments-title" class="h2">Mis asignaciones</h1>
    <p class="text-secondary">Abre una asignación activa para consultar su paquete ciego cuando esté disponible. La captura de evaluación permanece cerrada.</p>
    @if($assignments->isEmpty())
      <div class="alert alert-info" role="status">No tienes asignaciones disponibles.</div>
    @else
      <div class="list-group mb-4">
      @foreach($assignments as $assignment)
        <a class="list-group-item list-group-item-action" href="{{ route('judge.assignments.show', $assignment) }}">
          <strong>Asignación {{ $assignment->public_id }}</strong><br>
          <span>{{ $assignment->submissionVersion->submission->category->name }} · {{ $assignment->status->label() }}</span>
        </a>
      @endforeach
      </div>
      {{ $assignments->links() }}
    @endif
  </div>
</section>
@endsection
