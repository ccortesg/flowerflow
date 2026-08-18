@extends('layouts.flowerflow')

@section('title', 'Jueces')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
  <div>
    <p class="ff-kicker mb-1">Administración</p>
    <h1 class="mb-1">Jueces</h1>
    <p class="text-body-secondary mb-0">Cuentas operativas, prerrequisitos y estado de acceso.</p>
  </div>
  @can('create', \App\Models\JudgeProfile::class)
    <a class="btn btn-flower align-self-start" href="{{ route('panel.judges.create') }}">Dar de alta juez</a>
  @endcan
</div>

<div class="card ff-card mt-4 overflow-hidden">
  @if($judgeProfiles->isEmpty())
    <div class="p-4 p-lg-5 text-center">
      <span class="ri ri-user-search-line fs-1" aria-hidden="true"></span>
      <h2 class="h4 mt-3">Todavía no hay jueces</h2>
      <p class="mb-0">El sistema no crea cuentas automáticamente.</p>
    </div>
  @else
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <caption class="visually-hidden">Listado de perfiles de juez</caption>
        <thead>
          <tr>
            <th scope="col">Juez</th>
            <th scope="col">Estado</th>
            <th scope="col">Función</th>
            <th scope="col">Correo</th>
            <th scope="col">Contraseña</th>
            <th scope="col">Capacidad</th>
            <th scope="col"><span class="visually-hidden">Acciones</span></th>
          </tr>
        </thead>
        <tbody>
          @foreach($judgeProfiles as $profile)
            <tr>
              <td>{{ $profile->user->name }}</td>
              <td><span class="badge text-bg-secondary">{{ $profile->status->label() }}</span></td>
              <td>{{ $profile->assignment_role->label() }}</td>
              <td>{{ $profile->user->hasVerifiedEmail() ? 'Verificado' : 'Pendiente' }}</td>
              <td>{{ $profile->password_initialized_at ? 'Establecida' : 'Pendiente' }}</td>
              <td>{{ $profile->max_active_assignments ?? 'Sin límite' }}</td>
              <td class="text-end"><a class="btn btn-sm btn-outline-dark" href="{{ route('panel.judges.show', $profile) }}">Ver detalle</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

@if($judgeProfiles->hasPages())
  <nav class="mt-4" aria-label="Paginación de jueces">{{ $judgeProfiles->links() }}</nav>
@endif
@endsection
