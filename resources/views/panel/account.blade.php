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
            <h2 class="h4">Perfil</h2>
            <label class="form-label" for="name">Nombre</label>
            <input class="form-control mb-3" id="name" name="name" value="{{ auth()->user()->name }}" required>
            <label class="form-label" for="email">Correo electrónico</label>
            <input class="form-control mb-3" id="email" name="email" type="email" value="{{ auth()->user()->email }}" required>
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
            <p>Fortify protege el panel con TOTP y códigos de recuperación.</p>
            @if(auth()->user()->two_factor_secret)
                <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger">Desactivar 2FA</button>
                </form>
            @else
                <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                    @csrf
                    <button class="btn btn-flower">Activar 2FA</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
