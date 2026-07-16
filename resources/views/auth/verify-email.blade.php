@extends('layouts.flowerflow')
@section('title', 'Verificar correo')
@section('content')
<div class="card ff-card p-4"><h1 class="h2">Verifica tu correo</h1><p>Antes de continuar, abre el enlace que enviamos a <strong>{{ auth()->user()->email }}</strong>.</p><form method="POST" action="{{ route('verification.send') }}">@csrf<button class="btn btn-flower">Reenviar verificación</button></form></div>
@endsection
