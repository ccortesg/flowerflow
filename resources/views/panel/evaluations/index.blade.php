@extends('layouts.flowerflow')

@section('title', 'Evaluaciones')

@section('content')
<header class="mb-4"><p class="ff-kicker mb-1">Administración</p><h1 class="h2 mb-2">Evaluaciones</h1><p class="text-secondary mb-0">Consulta estados y revisiones sin consolidar calificaciones ni mostrar resultados.</p></header>
<div class="card ff-card table-responsive">
  <table class="table align-middle mb-0">
    <thead><tr><th>Evaluación</th><th>Juez sujeto</th><th>Categoría</th><th>Estado</th><th>Revisión</th><th>Actualización</th><th><span class="visually-hidden">Acción</span></th></tr></thead>
    <tbody>
      @forelse($evaluations as $evaluation)
        <tr>
          <td><code>{{ $evaluation->public_id }}</code></td>
          <td>{{ $evaluation->judgeAssignment->judgeProfile->user->name }}</td>
          <td>{{ $evaluation->judgeAssignment->submissionVersion->submission->category->name }}</td>
          <td>{{ $evaluation->status->label() }}</td>
          <td>{{ $evaluation->currentRevision->revision_number }}</td>
          <td>{{ $evaluation->updated_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s') }}</td>
          <td><a class="btn btn-sm btn-outline-primary" href="{{ route('panel.evaluations.show', $evaluation) }}">Ver detalle</a></td>
        </tr>
      @empty<tr><td colspan="7">No hay evaluaciones registradas.</td></tr>@endforelse
    </tbody>
  </table>
</div>
<div class="mt-4">{{ $evaluations->links() }}</div>
@endsection
