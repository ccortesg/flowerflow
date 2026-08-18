@extends('layouts.flowerflow')

@section('title', 'Recepción no disponible')
@section('description', 'La recepción de propuestas de Hermosillo Florece 2026 no está disponible.')

@php
  $errors ??= new \Illuminate\Support\ViewErrorBag;
  $roles = auth()->check() ? auth()->user()->getRoleNames()->values() : collect();
  $role = $roles->count() === 1 ? $roles->first() : null;
  $isPanelUser = in_array($role, ['admin', 'reviewer'], true);
  $isParticipant = $role === 'participant';
  $returnUrl = $isPanelUser
      ? route('panel.dashboard')
      : ($isParticipant ? route('submissions.index') : (auth()->check() ? route('dashboard') : route('landing')));
  $returnLabel = $isPanelUser
      ? 'Ir al panel'
      : ($isParticipant ? 'Ver mis propuestas' : (auth()->check() ? 'Volver a un área segura' : 'Volver al inicio'));
@endphp

@section('content')
<section class="container py-5" aria-labelledby="service-unavailable-title" aria-describedby="service-unavailable-description">
  <div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-8">
      <div class="card ff-card p-4 p-lg-5">
        <p class="ff-kicker mb-2">Hermosillo Florece 2026</p>
        <h1 id="service-unavailable-title" class="h2">La recepción de propuestas no está disponible</h1>
        <p id="service-unavailable-description" class="lead mb-4">Por el momento no es posible crear, editar o enviar propuestas.</p>

        <div class="alert alert-info d-flex gap-3 align-items-start" role="status">
          <span class="ri ri-information-line fs-4" aria-hidden="true"></span>
          <p class="mb-0">Tus propuestas, archivos y folios guardados permanecen disponibles para consulta desde tu cuenta.</p>
        </div>

        <div class="d-flex flex-column flex-sm-row gap-3 mt-2">
          <a class="btn btn-flower" href="{{ $returnUrl }}">{{ $returnLabel }}</a>
          <a class="btn btn-outline-dark" href="{{ route('documents') }}">Consultar documentos</a>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
