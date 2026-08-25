@extends('layouts.flowerflow')

@section('title', 'Resultado de asignación de varias propuestas')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <p class="ff-kicker mb-1">Resultado</p>
    <h1 class="h2 mb-1">Asignación simultánea procesada</h1>
    <p class="text-secondary mb-0">Operación <code>{{ $result['operation_id'] }}</code></p>
  </div>
  <a class="btn btn-flower" href="{{ route('panel.assignments.bulk.create') }}">Preparar otra operación</a>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="card ff-card p-3 h-100"><span class="text-secondary">Seleccionadas</span><strong class="h3 mb-0">{{ $result['selected_count'] }}</strong></div></div>
  <div class="col-6 col-lg-3"><div class="card ff-card p-3 h-100"><span class="text-secondary">Asignadas</span><strong class="h3 mb-0">{{ $result['created_count'] }}</strong></div></div>
  <div class="col-6 col-lg-3"><div class="card ff-card p-3 h-100"><span class="text-secondary">Ya existentes</span><strong class="h3 mb-0">{{ $result['existing_count'] }}</strong></div></div>
  <div class="col-6 col-lg-3"><div class="card ff-card p-3 h-100"><span class="text-secondary">Fallidas</span><strong class="h3 mb-0">{{ $result['failed_count'] }}</strong></div></div>
</div>

@if($result['notification_requested'])
  <div class="alert {{ $result['notification_queued'] ? 'alert-success' : 'alert-warning' }}" role="status">
    {{ $result['notification_queued'] ? 'El correo consolidado quedó en cola.' : 'Las asignaciones se conservaron, pero el correo consolidado no quedó en cola.' }}
  </div>
@endif

<div class="card ff-card">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th>Propuesta</th><th>Admisibilidad</th><th>Paquete</th><th>Asignación</th><th>Resultado</th></tr></thead>
      <tbody>
      @foreach($result['items'] as $item)
        <tr>
          <td><code>{{ $item['submission_public_id'] }}</code></td>
          <td>{{ match($item['admissibility']) { 'admitted' => 'Admitida ahora', 'already_admitted' => 'Ya admitida', default => 'Fallida' } }}</td>
          <td>{{ match($item['package']) { 'created_and_activated' => 'Creado y activo', 'reused' => 'Activo reutilizado', default => 'Fallido' } }}</td>
          <td>{{ match($item['assignment']) { 'created' => 'Creada', 'already_exists' => 'Ya vigente', default => 'Fallida' } }}</td>
          <td>
            <span class="{{ $item['assignment'] === 'failed' ? 'text-danger' : 'text-success' }}">{{ $item['message'] }}</span>
            @if($item['reason_code'])<code class="d-block mt-1">{{ $item['reason_code'] }}</code>@endif
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
