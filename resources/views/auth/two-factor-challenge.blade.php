@extends('layouts.flowerflow')
@section('title', 'Verificación en dos pasos')
@section('content')
<section class="ff-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card ff-card p-4">
                    <h1 class="h2">Verificación en dos pasos</h1>
                    <p class="text-body-secondary">Escribe el código de tu aplicación de autenticación. Si no tienes acceso, usa un código de recuperación.</p>

                    <form method="POST" action="{{ route('two-factor.login') }}">
                        @csrf
                        <label class="form-label" for="code">Código de autenticación</label>
                        <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" aria-describedby="two-factor-help" @error('code') aria-invalid="true" @enderror>
                        @error('code')<div class="invalid-feedback d-block mb-3">{{ $message }}</div>@enderror

                        <p id="two-factor-help" class="form-text my-3">Llena sólo una de las dos opciones.</p>

                        <label class="form-label" for="recovery_code">Código de recuperación</label>
                        <input class="form-control @error('recovery_code') is-invalid @enderror" id="recovery_code" name="recovery_code" autocomplete="one-time-code" @error('recovery_code') aria-invalid="true" @enderror>
                        @error('recovery_code')<div class="invalid-feedback d-block mb-3">{{ $message }}</div>@enderror

                        <button class="btn btn-flower mt-3">Verificar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
