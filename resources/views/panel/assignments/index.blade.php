@extends('layouts.flowerflow')

@section('title', 'Asignaciones de evaluación')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <p class="ff-kicker mb-1">Fase 02B</p>
    <h1 class="h2 mb-1">Asignaciones de evaluación</h1>
    <p class="text-secondary mb-0">Sólo aparecen propuestas enviadas cuya versión final vigente está admitida.</p>
  </div>
  @if(config('flowerflow.flags.bulk_judge_assignment'))
    @if(auth()->user()->can('decide admissibility') && auth()->user()->can('manage blind review packages') && auth()->user()->can('manage evaluation assignments'))
      <a class="btn btn-flower" href="{{ route('panel.assignments.bulk.create') }}">
        <i class="ri-user-add-line me-1" aria-hidden="true"></i> Asignar varias propuestas
      </a>
    @endif
  @endif
</div>

<form method="GET" class="card ff-card p-3 mb-4" aria-label="Filtros de asignaciones">
  <div class="row g-3 align-items-end">
    <div class="col-sm-6 col-xl-5">
      <label class="form-label" for="folio">Folio o ID de propuesta</label>
      <input class="form-control" id="folio" name="folio" maxlength="64" value="{{ request('folio') }}">
    </div>
    <div class="col-sm-6 col-xl-5">
      <label class="form-label" for="category">Categoría</label>
      <select class="form-select" id="category" name="category">
        <option value="">Todas</option>
        @foreach($categories as $category)
          <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-xl-2 d-grid gap-2">
      <button class="btn btn-flower" type="submit">Filtrar</button>
      @if(request()->filled('folio') || request()->filled('category'))
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('panel.assignments.index') }}">Limpiar</a>
      @endif
    </div>
  </div>
</form>

<div class="card ff-card">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th scope="col">Propuesta</th><th scope="col">Categoría</th><th scope="col">Asignaciones</th><th scope="col"><span class="visually-hidden">Acción</span></th></tr></thead>
      <tbody>
        @forelse($submissions as $submission)
          @php($coverage = $submission->getAttribute('assignment_coverage'))
          <tr>
            <td>
              <span class="d-block">{{ $submission->folio ?: 'Sin folio' }}</span>
              <code class="small">{{ $submission->public_id }}</code>
            </td>
            <td>{{ $submission->category->name }}</td>
            <td>
              <span class="d-block">{{ $coverage['active'] ?? 0 }} vigentes</span>
              <small class="text-secondary">{{ $coverage['pending_conflicts'] ?? 0 }} conflictos, {{ $coverage['cancelled'] ?? 0 }} canceladas, {{ $coverage['replaced'] ?? 0 }} reemplazadas</small>
            </td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('panel.assignments.show', $submission) }}">Administrar</a></td>
          </tr>
        @empty
          <tr><td colspan="4" class="p-4">No hay propuestas elegibles que coincidan con los filtros.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
<div class="mt-4">{{ $submissions->links() }}</div>
@endsection
