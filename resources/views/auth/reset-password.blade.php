@extends('layouts.flowerflow')
@section('title', 'Nueva contraseña')
@section('content')
<section class="ff-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card ff-card p-4">
                    <h1 class="h2">Define una nueva contraseña</h1>
                    <form method="POST" action="{{ route('password.update') }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="token" value="{{ $request->route('token') }}">
                        <div class="mb-3">
                            <label class="form-label" for="email">Correo electrónico</label>
                            <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autocomplete="email">
                        </div>
                        <x-password-fields password-label="Nueva contraseña" confirmation-label="Confirmar nueva contraseña" />
                        <button class="btn btn-flower">Guardar contraseña</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
