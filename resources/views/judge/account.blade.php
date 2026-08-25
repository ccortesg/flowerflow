@extends('layouts.flowerflow')
@section('title', 'Cuenta y seguridad')
@section('content')
<header class="mb-4"><p class="ff-kicker mb-1">Área de evaluación</p><h1 class="h2">Cuenta y seguridad</h1><p class="text-secondary mb-0">Administra sólo tus datos de acceso como juez.</p></header>

<div class="row g-4">
  <div class="col-lg-6">
    <section class="card ff-card p-4 h-100" aria-labelledby="judge-identity-title">
      <h2 id="judge-identity-title" class="h4">Identidad de acceso</h2>
      <dl class="row"><dt class="col-sm-4">Nombre</dt><dd class="col-sm-8">{{ $accountUser->name }}</dd><dt class="col-sm-4">Correo</dt><dd class="col-sm-8 text-break">{{ $accountUser->email }}</dd><dt class="col-sm-4">Verificación</dt><dd class="col-sm-8">{{ $accountUser->hasVerifiedEmail() ? 'Correo verificado' : 'Verificación pendiente' }}</dd></dl>
      <div class="alert alert-info mb-0" role="note">Si cambias el correo mediante el flujo autorizado de cuenta, deberás verificar la nueva dirección y tu perfil volverá temporalmente a configuración pendiente.</div>
    </section>
  </div>
  <div class="col-lg-6">
    <form method="POST" action="{{ route('user-password.update') }}" class="card ff-card p-4 h-100">
      @csrf @method('PUT')
      <h2 class="h4">Cambiar contraseña</h2>
      <div class="mb-3"><label class="form-label" for="current_password">Contraseña actual</label><input class="form-control" id="current_password" name="current_password" type="password" required autocomplete="current-password">@foreach($errors->getBag('updatePassword')->get('current_password') as $message)<div class="invalid-feedback d-block">{{ $message }}</div>@endforeach</div>
      <x-password-fields password-label="Nueva contraseña" confirmation-label="Confirmar nueva contraseña" error-bag="updatePassword" />
      <button class="btn btn-flower align-self-start" type="submit">Cambiar contraseña</button>
    </form>
  </div>

  <div class="col-12">
    <section class="card ff-card p-4" aria-labelledby="judge-two-factor-title">
      <h2 id="judge-two-factor-title" class="h4">Autenticación en dos pasos</h2>
      <p>Es opcional y agrega una segunda verificación mediante una aplicación autenticadora.</p>
      @if($twoFactorConfirmed)
        <div class="alert alert-success" role="status">2FA está activa y confirmada.</div>
        <h3 class="h5">Códigos de recuperación</h3>
        <ul class="row row-cols-1 row-cols-md-2 g-2 list-unstyled" aria-label="Códigos de recuperación">@foreach($accountUser->recoveryCodes() as $recoveryCode)<li class="col"><code class="d-block border rounded p-2">{{ $recoveryCode }}</code></li>@endforeach</ul>
        <form method="POST" action="{{ route('judge.account.two-factor.recovery-codes') }}" class="mt-4">@csrf<label class="form-label" for="judge_two_factor_regenerate_password">Contraseña actual para generar códigos nuevos</label><input class="form-control mb-2" id="judge_two_factor_regenerate_password" name="current_password" type="password" required autocomplete="current-password"><button class="btn btn-outline-secondary" type="submit">Regenerar códigos</button></form>
        <hr class="my-4">
        <form method="POST" action="{{ route('judge.account.two-factor.disable') }}">@csrf @method('DELETE')<label class="form-label" for="judge_two_factor_disable_password">Contraseña actual para desactivar 2FA</label><input class="form-control mb-2" id="judge_two_factor_disable_password" name="current_password" type="password" required autocomplete="current-password"><button class="btn btn-outline-danger" type="submit">Desactivar 2FA</button></form>
      @elseif($twoFactorPending)
        <div class="alert alert-warning" role="status">Escanea el código y confirma el número de seis dígitos para terminar.</div>
        <div class="bg-white border rounded p-3 d-inline-block" aria-label="Código QR para configurar 2FA">{!! $accountUser->twoFactorQrCodeSvg() !!}</div>
        <form method="POST" action="{{ route('judge.account.two-factor.confirm') }}" class="mt-4">@csrf<label class="form-label" for="judge_two_factor_code">Código de la aplicación</label><input class="form-control mb-2" id="judge_two_factor_code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required><button class="btn btn-flower" type="submit">Confirmar activación</button></form>
        <form method="POST" action="{{ route('judge.account.two-factor.disable') }}" class="mt-4">@csrf @method('DELETE')<label class="form-label" for="judge_two_factor_cancel_password">Contraseña actual para cancelar</label><input class="form-control mb-2" id="judge_two_factor_cancel_password" name="current_password" type="password" required autocomplete="current-password"><button class="btn btn-outline-danger" type="submit">Cancelar activación</button></form>
      @else
        <form method="POST" action="{{ route('judge.account.two-factor.enable') }}">@csrf<label class="form-label" for="judge_two_factor_enable_password">Confirma tu contraseña actual</label><input class="form-control mb-2" id="judge_two_factor_enable_password" name="current_password" type="password" required autocomplete="current-password"><button class="btn btn-flower" type="submit">Activar 2FA</button></form>
      @endif
    </section>
  </div>
</div>
@endsection
