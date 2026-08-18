@extends('layouts.flowerflow')

@section('title', 'Estado de acceso de juez')
@section('description', 'Estado seguro de la cuenta de juez en Flower Flow.')

@section('content')
<section class="ff-narrow-card" aria-labelledby="judge-status-title" aria-describedby="judge-status-description">
  <div class="card ff-card p-4 p-lg-5">
    <p class="ff-kicker mb-2">Cuenta de juez</p>
    @if(! $judgeProfile)
      <h1 id="judge-status-title" class="h2">Tu acceso necesita revisión administrativa</h1>
      <p id="judge-status-description" class="lead">La cuenta no tiene un perfil operativo válido. Por seguridad no se muestran proyectos ni herramientas de evaluación.</p>
    @elseif($judgeProfile->status === \App\Enums\JudgeProfileStatus::Suspended)
      <h1 id="judge-status-title" class="h2">Tu acceso está suspendido</h1>
      <p id="judge-status-description" class="lead">La sesión permanece protegida y no puedes abrir el área de evaluación mientras la suspensión esté vigente.</p>
    @elseif($judgeProfile->status === \App\Enums\JudgeProfileStatus::PendingSetup)
      <h1 id="judge-status-title" class="h2">Completa la configuración de tu acceso</h1>
      <p id="judge-status-description" class="lead">Antes de abrir el área de evaluación debes establecer tu contraseña y verificar tu correo electrónico.</p>
      <ul class="list-group mb-4" aria-label="Prerrequisitos de acceso">
        <li class="list-group-item d-flex justify-content-between align-items-center">
          Contraseña propia
          <strong>{{ $judgeProfile->password_initialized_at ? 'Completada' : 'Pendiente' }}</strong>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          Correo verificado
          <strong>{{ auth()->user()->hasVerifiedEmail() ? 'Completado' : 'Pendiente' }}</strong>
        </li>
      </ul>
      @unless(auth()->user()->hasVerifiedEmail())
        <a class="btn btn-flower align-self-start" href="{{ route('verification.notice') }}">Verificar correo</a>
      @endunless
    @else
      <h1 id="judge-status-title" class="h2">Tu acceso está activo</h1>
      <p id="judge-status-description" class="lead">Los prerrequisitos están completos.</p>
      <a class="btn btn-flower align-self-start" href="{{ route('judge.dashboard') }}">Ir al área de evaluación</a>
    @endif
    <div class="alert alert-info d-flex gap-3 align-items-start mt-4 mb-0" role="status">
      <span class="ri ri-shield-check-line fs-4" aria-hidden="true"></span>
      <p class="mb-0">Esta pantalla no muestra propuestas, anexos, datos personales ni controles de evaluación.</p>
    </div>
  </div>
</section>
@endsection
