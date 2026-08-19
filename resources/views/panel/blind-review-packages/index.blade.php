@extends('layouts.flowerflow')

@section('title', 'Paquetes ciegos')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <p class="ff-kicker mb-1">Fase 02B · M5</p>
    <h1 class="h2 mb-1">Paquetes ciegos</h1>
    <p class="text-secondary mb-0">Proyecciones estructurales de versiones enviadas, admitidas y cubiertas.</p>
  </div>
</div>

@if($submissions->isEmpty())
  <div class="alert alert-info" role="status">No hay propuestas elegibles para preparar paquetes.</div>
@else
  <div class="card ff-card">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr><th scope="col">ID técnico</th><th scope="col">Categoría</th><th scope="col">Versión</th><th scope="col">Paquete</th><th scope="col"><span class="visually-hidden">Acción</span></th></tr></thead>
        <tbody>
        @foreach($submissions as $submission)
          @php($version = $submission->versions->first())
          @php($package = $version?->blindReviewPackage)
          <tr>
            <td><code>{{ $submission->public_id }}</code></td>
            <td>{{ $submission->category->name }}</td>
            <td>{{ $version?->version ?? '—' }}</td>
            <td>{{ $package?->status->label() ?? 'Sin generar' }}</td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('panel.blind-review-packages.show', $submission) }}">Administrar</a></td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>
  <div class="mt-4">{{ $submissions->links() }}</div>
@endif
@endsection
