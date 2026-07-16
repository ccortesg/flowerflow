@extends('layouts.flowerflow')
@section('title', 'Verificación en dos pasos')
@section('content')
<section class="ff-section"><div class="container"><div class="row justify-content-center"><div class="col-md-6"><div class="card ff-card p-4"><h1 class="h2">Verificación en dos pasos</h1><form method="POST" action="{{ route('two-factor.login') }}">@csrf<label class="form-label" for="code">Código de autenticación</label><input class="form-control mb-3" id="code" name="code" inputmode="numeric" autocomplete="one-time-code"><label class="form-label" for="recovery_code">O usa un código de recuperación</label><input class="form-control mb-3" id="recovery_code" name="recovery_code"><button class="btn btn-flower">Verificar</button></form></div></div></div></div></section>
@endsection
