@extends('layouts.flowerflow')
@section('title', 'Crear cuenta')
@section('content')
<section class="ff-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card ff-card p-4">
                    <div class="card-body">
                        <p class="ff-kicker">Participantes</p>
                        <h1 class="h2">Crear cuenta</h1>
                        <form method="POST" action="{{ route('register') }}" class="mt-4">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label" for="name">Nombre para tu cuenta</label>
                                <input class="form-control" id="name" name="name" value="{{ old('name') }}" required autocomplete="name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="email">Correo electrónico</label>
                                <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
                            </div>
                            <x-password-fields />
                            <button class="btn btn-flower w-100">Crear cuenta</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
