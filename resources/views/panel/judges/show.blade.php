@extends('layouts.flowerflow')

@section('title', 'Detalle de juez')

@section('content')
<p class="ff-kicker mb-1">Administración de jueces</p>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
  <div>
    <h1 class="mb-1">{{ $judgeProfile->user->name }}</h1>
    <p class="text-body-secondary mb-0">{{ $judgeProfile->user->email }}</p>
  </div>
  <a class="btn btn-outline-dark align-self-start" href="{{ route('panel.judges.index') }}">Volver al listado</a>
</div>

<div class="row g-4 mt-1">
  <div class="col-lg-5">
    <section class="card ff-card p-4 h-100" aria-labelledby="judge-access-summary">
      <h2 id="judge-access-summary" class="h4">Estado de acceso</h2>
      <dl class="row mb-0">
        <dt class="col-7">Perfil</dt><dd class="col-5">{{ $judgeProfile->status->label() }}</dd>
        <dt class="col-7">Función</dt><dd class="col-5">{{ $judgeProfile->assignment_role->label() }}</dd>
        <dt class="col-7">Correo</dt><dd class="col-5">{{ $judgeProfile->user->hasVerifiedEmail() ? 'Verificado' : 'Pendiente' }}</dd>
        <dt class="col-7">Contraseña propia</dt><dd class="col-5">{{ $judgeProfile->password_initialized_at ? 'Establecida' : 'Pendiente' }}</dd>
        <dt class="col-7">2FA</dt><dd class="col-5">{{ $judgeProfile->user->hasEnabledTwoFactorAuthentication() ? 'Configurada' : 'Opcional / sin configurar' }}</dd>
        <dt class="col-7">Capacidad</dt><dd class="col-5">{{ $judgeProfile->max_active_assignments ?? 'Sin límite' }}</dd>
      </dl>
      <p class="small text-body-secondary mt-3 mb-0">La función y capacidad no crean asignaciones ni habilitan módulos de evaluación. El sustituto no recibe asignaciones iniciales.</p>
    </section>
  </div>

  <div class="col-lg-7">
    <section class="card ff-card p-4" aria-labelledby="judge-actions-title">
      <h2 id="judge-actions-title" class="h4">Acciones de acceso</h2>

      @if($judgeProfile->status === \App\Enums\JudgeProfileStatus::PendingSetup)
        <form method="POST" action="{{ route('panel.judges.setup.resend', $judgeProfile) }}" class="mb-4">
          @csrf
          <p>Genera un enlace temporal nuevo sin cambiar la contraseña ni verificar el correo por inferencia.</p>
          <button class="btn btn-outline-dark" type="submit">Reenviar configuración</button>
        </form>
      @endif

      @if($judgeProfile->status === \App\Enums\JudgeProfileStatus::Suspended)
        <form method="POST" action="{{ route('panel.judges.reactivate', $judgeProfile) }}" class="mb-4" novalidate>
          @csrf
          <h3 class="h5">Reactivar</h3>
          <p>El resultado será activo sólo si correo y contraseña propia están completos; en otro caso volverá a configuración pendiente.</p>
          @include('panel.judges.partials.confirmed-reason', ['prefix' => 'reactivate', 'buttonLabel' => 'Confirmar reactivación', 'buttonClass' => 'btn-flower'])
        </form>
      @else
        <form method="POST" action="{{ route('panel.judges.suspend', $judgeProfile) }}" class="mb-4" novalidate>
          @csrf
          <h3 class="h5">Suspender</h3>
          <p>Bloquea inmediatamente el área de juez, rota el token persistente y revoca todas sus sesiones.</p>
          @include('panel.judges.partials.confirmed-reason', ['prefix' => 'suspend', 'buttonLabel' => 'Confirmar suspensión', 'buttonClass' => 'btn-outline-danger'])
        </form>
      @endif

      @can('recoverTwoFactor', $judgeProfile)
        <hr>
        <form method="POST" action="{{ route('panel.judges.two-factor.recover', $judgeProfile) }}" class="mt-4" novalidate>
          @csrf
          <h3 class="h5">Recuperar acceso 2FA</h3>
          <p>Elimina el material TOTP sin mostrar secretos o códigos al administrador y revoca todas las sesiones del juez.</p>
          @include('panel.judges.partials.confirmed-reason', ['prefix' => 'two_factor', 'buttonLabel' => 'Confirmar recuperación 2FA', 'buttonClass' => 'btn-outline-danger'])
        </form>
      @endcan
    </section>
  </div>
</div>
@endsection
