@extends('layouts.flowerflow')

@section('title', 'Rúbricas de evaluación')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
  <div>
    <p class="ff-kicker mb-1">Evaluación</p>
    <h1 class="mb-1">Rúbricas</h1>
    <p class="text-body-secondary mb-0">Versiones globales para {{ $competition->name }}. M3 no captura evaluaciones ni puntajes.</p>
  </div>
  @can('create', \App\Models\RubricVersion::class)
    <a class="btn btn-flower align-self-start" href="{{ route('panel.rubrics.create') }}">Crear nueva versión</a>
  @endcan
</div>

<div class="card ff-card mt-4 overflow-hidden">
  @if($rubrics->isEmpty())
    <div class="p-4 p-lg-5 text-center">
      <span class="ri ri-file-list-3-line fs-1" aria-hidden="true"></span>
      <h2 class="h4 mt-3">Todavía no hay versiones</h2>
      <p class="mb-0">No existe una rúbrica activa y no se crea ninguna evaluación por inferencia.</p>
    </div>
  @else
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <caption class="visually-hidden">Versiones de la rúbrica global de evaluación</caption>
        <thead>
          <tr>
            <th scope="col">Versión</th>
            <th scope="col">Título interno</th>
            <th scope="col">Estado</th>
            <th scope="col">Criterios</th>
            <th scope="col">Activación</th>
            <th scope="col"><span class="visually-hidden">Acciones</span></th>
          </tr>
        </thead>
        <tbody>
          @foreach($rubrics as $rubric)
            <tr>
              <td><strong>v{{ $rubric->version }}</strong></td>
              <td>{{ $rubric->title }}</td>
              <td><span class="badge text-bg-secondary">{{ $rubric->status->label() }}</span></td>
              <td>{{ $rubric->criteria_count }} de 5</td>
              <td>{{ $rubric->activated_at?->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') ?? 'No activada' }}</td>
              <td class="text-end"><a class="btn btn-sm btn-outline-dark" href="{{ route('panel.rubrics.show', $rubric) }}">Ver detalle</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

@if($rubrics->hasPages())
  <nav class="mt-4" aria-label="Paginación de rúbricas">{{ $rubrics->links() }}</nav>
@endif
@endsection
