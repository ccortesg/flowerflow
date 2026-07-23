@extends('layouts.flowerflow')
@section('title', 'Acceso no autorizado')
@section('content')
<section class="container py-5" aria-labelledby="forbidden-title">
  <div class="card ff-card mx-auto p-4 p-lg-5" style="max-width: 42rem">
    <p class="ff-kicker mb-2">Acceso restringido</p>
    <h1 id="forbidden-title">No tienes permiso para entrar a esta sección</h1>
    <p>Tu cuenta está protegida, pero no cuenta con la autorización necesaria para consultar este contenido.</p>
    <a class="btn btn-flower align-self-start" href="{{ auth()->check() ? route('dashboard') : route('login') }}">Volver a un área segura</a>
  </div>
</section>
@endsection
