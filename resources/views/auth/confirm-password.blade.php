@extends('layouts.flowerflow')
@section('title', 'Confirmar contraseña')
@section('content')
<div class="card ff-card p-4"><h1 class="h2">Confirma tu contraseña</h1><form method="POST" action="{{ route('password.confirm') }}">@csrf<input class="form-control my-3" name="password" type="password" required autocomplete="current-password"><button class="btn btn-flower">Confirmar</button></form></div>
@endsection
