@extends('layouts.flowerflow')
@section('title', 'Cuenta y seguridad')
@section('content')
<p class="ff-kicker mb-1">Administración</p>
<h1>Cuenta y seguridad</h1>

<div class="row g-4 mt-2">
    <div class="col-lg-6">
        <form method="POST" action="{{ route('user-profile-information.update') }}" class="card ff-card p-4">
            @csrf
            @method('PUT')
            @php($profileErrors = $errors->getBag('updateProfileInformation'))
            <h2 class="h4">Perfil</h2>
            <label class="form-label" for="name">Nombre</label>
            <input class="form-control mb-3 @if($profileErrors->has('name')) is-invalid @endif" id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required autocomplete="name">
            @foreach($profileErrors->get('name') as $message)
                <div class="invalid-feedback d-block mb-3">{{ $message }}</div>
            @endforeach
            <label class="form-label" for="email">Correo electrónico</label>
            <input class="form-control mb-3 @if($profileErrors->has('email')) is-invalid @endif" id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required autocomplete="email">
            @foreach($profileErrors->get('email') as $message)
                <div class="invalid-feedback d-block mb-3">{{ $message }}</div>
            @endforeach
            <button class="btn btn-flower align-self-start">Actualizar perfil</button>
        </form>
    </div>

    <div class="col-lg-6">
        <form method="POST" action="{{ route('user-password.update') }}" class="card ff-card p-4">
            @csrf
            @method('PUT')
            <h2 class="h4">Cambiar contraseña</h2>
            <div class="mb-3">
                <label class="form-label" for="current_password">Contraseña actual</label>
                <div class="input-group">
                    <input class="form-control" id="current_password" name="current_password" type="password" required autocomplete="current-password">
                    <button class="btn btn-outline-secondary" type="button" data-password-toggle aria-controls="current_password" aria-label="Mostrar contraseña actual">Mostrar</button>
                </div>
                @foreach($errors->getBag('updatePassword')->get('current_password') as $message)
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @endforeach
            </div>
            <x-password-fields
                password-label="Nueva contraseña"
                confirmation-label="Confirmar nueva contraseña"
                error-bag="updatePassword"
            />
            <button class="btn btn-flower align-self-start">Cambiar contraseña</button>
        </form>
    </div>

    <div class="col-12">
        <div class="card ff-card p-4">
            <h2 class="h4">Autenticación en dos pasos</h2>
            <p>Agrega una segunda verificación con una aplicación de autenticación y conserva tus códigos de recuperación en un lugar seguro.</p>
            @php
                $accountUser = auth()->user();
                $twoFactorPending = filled($accountUser->two_factor_secret) && blank($accountUser->two_factor_confirmed_at);
                $twoFactorConfirmed = filled($accountUser->two_factor_secret) && filled($accountUser->two_factor_confirmed_at);
                $twoFactorErrors = $errors->getBag('twoFactorAuthentication');
                $confirmationErrors = $errors->getBag('confirmTwoFactorAuthentication');
            @endphp

            @if($twoFactorConfirmed)
                <div class="alert alert-success" role="status">2FA está activa y confirmada.</div>
                <h3 class="h5">Códigos de recuperación</h3>
                <p>Guarda estos códigos fuera del navegador. Cada código sólo puede usarse una vez.</p>
                <ul class="row row-cols-1 row-cols-md-2 g-2 list-unstyled" aria-label="Códigos de recuperación">
                    @foreach($accountUser->recoveryCodes() as $recoveryCode)
                        <li class="col"><code class="d-block border rounded p-2">{{ $recoveryCode }}</code></li>
                    @endforeach
                </ul>
                <form method="POST" action="{{ route('panel.account.two-factor.recovery-codes') }}" class="mt-4">
                    @csrf
                    <label class="form-label" for="two_factor_regenerate_password">Contraseña actual para generar códigos nuevos</label>
                    <input class="form-control mb-2 @if($twoFactorErrors->has('current_password')) is-invalid @endif" id="two_factor_regenerate_password" name="current_password" type="password" required autocomplete="current-password">
                    <button class="btn btn-outline-secondary">Regenerar códigos</button>
                </form>

                <hr class="my-4">
                <form method="POST" action="{{ route('panel.account.two-factor.disable') }}">
                    @csrf
                    @method('DELETE')
                    <label class="form-label" for="two_factor_disable_password">Contraseña actual para desactivar 2FA</label>
                    <input class="form-control mb-2 @if($twoFactorErrors->has('current_password')) is-invalid @endif" id="two_factor_disable_password" name="current_password" type="password" required autocomplete="current-password">
                    <button class="btn btn-outline-danger">Desactivar 2FA</button>
                </form>
            @elseif($twoFactorPending)
                <div class="alert alert-warning" role="status">La activación todavía no termina. Escanea el código y confirma el número de seis dígitos.</div>
                <div class="bg-white border rounded p-3 d-inline-block" aria-label="Código QR para configurar 2FA">
                    {!! $accountUser->twoFactorQrCodeSvg() !!}
                </div>
                <form method="POST" action="{{ route('panel.account.two-factor.confirm') }}" class="mt-4" novalidate>
                    @csrf
                    <label class="form-label" for="two_factor_code">Código de la aplicación</label>
                    <input class="form-control mb-2 @if($confirmationErrors->has('code')) is-invalid @endif" id="two_factor_code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required>
                    @foreach($confirmationErrors->get('code') as $message)
                        <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                    @endforeach
                    <button class="btn btn-flower">Confirmar activación</button>
                </form>
                <form method="POST" action="{{ route('panel.account.two-factor.disable') }}" class="mt-4">
                    @csrf
                    @method('DELETE')
                    <label class="form-label" for="two_factor_cancel_password">Contraseña actual para cancelar la activación</label>
                    <input class="form-control mb-2" id="two_factor_cancel_password" name="current_password" type="password" required autocomplete="current-password">
                    <button class="btn btn-outline-danger">Cancelar activación</button>
                </form>
            @else
                <form method="POST" action="{{ route('panel.account.two-factor.enable') }}">
                    @csrf
                    <label class="form-label" for="two_factor_enable_password">Confirma tu contraseña actual</label>
                    <input class="form-control mb-2 @if($twoFactorErrors->has('current_password')) is-invalid @endif" id="two_factor_enable_password" name="current_password" type="password" required autocomplete="current-password">
                    @foreach($twoFactorErrors->get('current_password') as $message)
                        <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                    @endforeach
                    <button class="btn btn-flower">Activar 2FA</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
