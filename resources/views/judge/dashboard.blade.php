@extends('layouts.flowerflow')

@section('title', 'Área de evaluación')
@section('description', 'Estado del área de evaluación de Hermosillo Florece 2026.')

@section('content')
<section class="ff-narrow-card" aria-labelledby="judge-dashboard-title" aria-describedby="judge-dashboard-description">
  <div class="card ff-card p-4 p-lg-5">
    <p class="ff-kicker mb-2">Hermosillo Florece 2026</p>
    <h1 id="judge-dashboard-title" class="h2">Área de evaluación</h1>
    <p id="judge-dashboard-description" class="lead">Tu acceso está habilitado para consultar tus asignaciones y declarar conflictos.</p>
    <div class="alert alert-info d-flex gap-3 align-items-start" role="status">
      <span class="ri ri-shield-check-line fs-4" aria-hidden="true"></span>
      <p class="mb-0">Una asignación activa puede mostrar su paquete ciego estructural y anexos con nombres neutros cuando la administración lo haya activado. La captura de evaluación aún no está habilitada.</p>
    </div>
    <a class="btn btn-flower" href="{{ route('judge.assignments.index') }}">Ver mis asignaciones</a>
  </div>
</section>
@endsection
