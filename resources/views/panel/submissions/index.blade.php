@extends('layouts.flowerflow')
@section('title', 'Propuestas del panel')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
  <div>
    <p class="ff-kicker mb-1">Recepción</p>
    <h1>Propuestas</h1>
  </div>
  @can('export submissions')
    <a class="btn btn-flower" href="{{ route('panel.submissions.exports.create') }}">
      <i class="ri-file-excel-2-line me-1" aria-hidden="true"></i> Exportar a Excel
    </a>
  @endcan
</div>

<form method="GET" class="card ff-card p-3 my-4" aria-label="Filtros de propuestas">
  <div class="row g-3 align-items-end">
    <div class="col-md-4">
      <label class="form-label" for="status">Estado</label>
      <select class="form-select" id="status" name="status">
        <option value="">Todos</option>
        <option value="draft" @selected(request('status') === 'draft')>Borrador</option>
        <option value="submitted" @selected(request('status') === 'submitted')>Enviada</option>
      </select>
    </div>
    <div class="col-md-5">
      <label class="form-label" for="category">Categoría</label>
      <select class="form-select" id="category" name="category">
        <option value="">Todas</option>
        @foreach($categories as $category)
          <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3"><button class="btn btn-flower w-100">Filtrar</button></div>
  </div>
</form>

<div class="card ff-card">
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Folio</th><th>Proyecto</th><th>Participante</th><th>Categoría</th><th>Estado</th><th>Fecha</th></tr></thead>
      <tbody>
      @forelse($submissions as $item)
        <tr>
          <td>{{ $item->folio ?: '—' }}</td>
          <td><a href="{{ route('panel.submissions.show', $item) }}">{{ $item->title }}</a></td>
          <td>{{ $item->user->name }}</td>
          <td>{{ $item->category->name }}</td>
          <td>{{ $item->statusLabel() }}</td>
          <td>{{ $item->created_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }}</td>
        </tr>
      @empty
        <tr><td colspan="6" class="p-4">No hay resultados.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
<div class="mt-3">{{ $submissions->links() }}</div>

@can('export submissions')
  <section class="card ff-card mt-4" aria-labelledby="recent-exports-title">
    <div class="card-body">
      <h2 class="h5" id="recent-exports-title">Exportaciones recientes</h2>
      <p class="text-muted">Cada archivo permanece disponible durante {{ config('flowerflow.exports.retention_hours') }} horas y sólo puede descargarlo quien lo solicitó.</p>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead><tr><th>Solicitud</th><th>Estado</th><th>Propuestas</th><th>Vigencia</th><th></th></tr></thead>
          <tbody>
          @forelse($exports as $export)
            <tr>
              <td>{{ $export->created_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }}</td>
              <td>{{ $export->status->label() }}</td>
              <td>{{ $export->proposal_count }}</td>
              <td>{{ $export->expires_at?->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') ?: '—' }}</td>
              <td class="text-end">
                @if($export->isAvailable())
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('panel.submissions.exports.download', $export) }}">Descargar</a>
                @elseif($export->status === \App\Enums\SubmissionExportStatus::Failed)
                  <span class="text-danger">Genera una nueva exportación.</span>
                @else
                  <span class="text-muted">No disponible</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="5">Aún no has generado exportaciones.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </section>
@endcan
@endsection
