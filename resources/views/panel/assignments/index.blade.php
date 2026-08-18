@extends('layouts.flowerflow')

@section('title', 'Asignaciones de evaluación')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <p class="ff-kicker mb-1">Fase 02B</p>
    <h1 class="h2 mb-1">Asignaciones de evaluación</h1>
    <p class="text-secondary mb-0">Sólo aparecen propuestas enviadas cuya versión final vigente está admitida.</p>
  </div>
</div>

@if($submissions->isEmpty())
  <div class="alert alert-info" role="status">No hay propuestas elegibles para asignación.</div>
@else
  <div class="card ff-card">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr><th scope="col">ID técnico</th><th scope="col">Categoría</th><th scope="col">Cobertura</th><th scope="col"><span class="visually-hidden">Acción</span></th></tr></thead>
        <tbody>
        @foreach($submissions as $submission)
          @php($coverage = $submission->getAttribute('assignment_coverage'))
          <tr>
            <td><code>{{ $submission->public_id }}</code></td>
            <td>{{ $submission->category->name }}</td>
            <td>{{ $coverage['covered'] ?? 0 }} de {{ $coverage['required'] ?? 4 }} @if(($coverage['pending_conflicts'] ?? 0) > 0)<span class="badge bg-warning text-dark">Conflicto pendiente</span>@endif</td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('panel.assignments.show', $submission) }}">Administrar</a></td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>
  <div class="mt-4">{{ $submissions->links() }}</div>
@endif
@endsection
