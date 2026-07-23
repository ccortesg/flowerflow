@extends('layouts.flowerflow')
@section('title', 'Contenido no disponible')
@section('content')
<section class="container py-5" aria-labelledby="not-found-title">
  <div class="card ff-card mx-auto p-4 p-lg-5" style="max-width: 42rem">
    <p class="ff-kicker mb-2">Contenido no disponible</p>
    <h1 id="not-found-title">No encontramos la página que buscas</h1>
    <p>Es posible que la dirección haya cambiado o que esta función no esté habilitada para tu cuenta.</p>
    <a class="btn btn-flower align-self-start" href="{{ auth()->check() ? route('dashboard') : route('landing') }}">Volver al inicio</a>
  </div>
</section>
@endsection
