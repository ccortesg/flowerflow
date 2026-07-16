@extends('layouts.flowerflow')
@section('title', 'Confirmar contraseña')
@section('content')
<section class="ff-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card ff-card p-4">
                    <h1 class="h2">Confirma tu contraseña</h1>
                    <p class="text-body-secondary">Por seguridad, confirma tu contraseña antes de continuar con esta acción.</p>

                    <form method="POST" action="{{ route('password.confirm') }}">
                        @csrf
                        <label class="form-label" for="password">Contraseña actual</label>
                        <div class="input-group mb-2">
                            <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" required autocomplete="current-password" @error('password') aria-invalid="true" @enderror>
                            <button class="btn btn-outline-secondary" type="button" data-password-toggle aria-controls="password" aria-label="Mostrar contraseña actual">Mostrar</button>
                        </div>
                        @error('password')<div class="invalid-feedback d-block mb-3">{{ $message }}</div>@enderror

                        <button class="btn btn-flower">Confirmar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
