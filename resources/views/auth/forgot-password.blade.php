@extends('layouts.flowerflow')
@section('title', 'Recuperar contraseña')
@section('content')
<section class="ff-section"><div class="container"><div class="row justify-content-center"><div class="col-md-6"><div class="card ff-card p-4"><h1 class="h2">Recuperar contraseña</h1><p>Si existe una cuenta asociada, programaremos un enlace de recuperación. Si el correo tarda, revisa la carpeta de correo no deseado o vuelve a intentarlo más tarde.</p><form method="POST" action="{{ route('password.email') }}">@csrf<label class="form-label" for="email">Correo electrónico</label><input class="form-control mb-3" id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"><button class="btn btn-flower">Enviar enlace</button></form></div></div></div></div></section>
@endsection
