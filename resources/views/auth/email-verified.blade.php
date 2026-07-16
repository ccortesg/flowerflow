@extends('layouts.flowerflow')
@section('title', 'Correo verificado')
@section('content')
<section class="ff-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card ff-card p-4 text-center">
                    <div class="ff-verification-icon mx-auto mb-3" aria-hidden="true">✓</div>
                    <p class="ff-kicker">Cuenta confirmada</p>
                    <h1 class="h2">¡Tu correo fue verificado correctamente!</h1>
                    <p class="lead">Tu cuenta participante ya está lista. Puedes iniciar sesión cuando lo necesites y comenzar a registrar tus propuestas.</p>
                    @auth
                        <a class="btn btn-flower btn-lg align-self-center" href="{{ route('dashboard') }}">Ir a mi cuenta</a>
                    @else
                        <a class="btn btn-flower btn-lg align-self-center" href="{{ route('login') }}">Iniciar sesión</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
