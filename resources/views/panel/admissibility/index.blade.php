@extends('layouts.flowerflow')
@section('title', 'Revisión de admisibilidad')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
  <div><p class="ff-kicker mb-1">Revisión administrativa</p><h1>Admisibilidad</h1><p>Expedientes ligados a la versión inmutable enviada.</p></div>
</div>

<form method="GET" class="card ff-card p-3 my-4" aria-label="Filtros de admisibilidad">
  <div class="row g-3 align-items-end">
    <div class="col-sm-6 col-xl-2"><label class="form-label" for="status">Estado</label><select class="form-select" id="status" name="status"><option value="">Todos</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
    <div class="col-sm-6 col-xl-2"><label class="form-label" for="category">Categoría</label><select class="form-select" id="category" name="category"><option value="">Todas</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>@endforeach</select></div>
    <div class="col-sm-6 col-xl-2"><label class="form-label" for="folio">Folio</label><input class="form-control" id="folio" name="folio" value="{{ request('folio') }}"></div>
    <div class="col-sm-6 col-xl-2"><label class="form-label" for="reviewer">Revisor</label><select class="form-select" id="reviewer" name="reviewer"><option value="">Todos</option>@foreach($reviewers as $reviewer)<option value="{{ $reviewer->id }}" @selected((string) request('reviewer') === (string) $reviewer->id)>{{ $reviewer->name }}</option>@endforeach</select></div>
    <div class="col-sm-6 col-xl-1"><label class="form-label" for="from">Desde</label><input class="form-control" id="from" name="from" type="date" value="{{ request('from') }}"></div>
    <div class="col-sm-6 col-xl-1"><label class="form-label" for="to">Hasta</label><input class="form-control" id="to" name="to" type="date" value="{{ request('to') }}"></div>
    <div class="col-xl-2 d-flex gap-2"><button class="btn btn-flower flex-fill">Filtrar</button><a class="btn btn-outline-secondary" href="{{ route('panel.admissibility.index') }}">Limpiar</a></div>
  </div>
</form>

<div class="card ff-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Folio</th><th>Propuesta</th><th>Categoría</th><th>Estado</th><th>Revisor</th><th>Envío</th></tr></thead><tbody>
@forelse($reviews as $review)
  <tr><td>{{ $review->submission->folio }}</td><td><a href="{{ route('panel.admissibility.show', $review) }}">{{ $review->submission->title }}</a></td><td>{{ $review->submission->category->name }}</td><td><span class="ff-review-status ff-review-status-{{ $review->status->value }}">{{ $review->status->label() }}</span></td><td>{{ $review->reviewer?->name ?: 'Sin asignar' }}</td><td>{{ $review->submission->submitted_at?->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }}</td></tr>
@empty
  <tr><td colspan="6" class="p-4">No hay expedientes que coincidan con los filtros.</td></tr>
@endforelse
</tbody></table></div></div>
<div class="mt-3">{{ $reviews->links() }}</div>
@endsection
