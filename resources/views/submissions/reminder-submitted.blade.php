@extends('layouts.flowerflow')
@section('title', 'Propuesta enviada')
@section('content')
<div class="container py-5" style="max-width:720px;">
  <div class="card ff-card">
    <div class="card-body p-5 text-center">
      <i class="ri-checkbox-circle-line text-success" style="font-size:3rem;" aria-hidden="true"></i>
      <h1 class="h2 mt-3">Propuesta enviada</h1>
      <p>Tu propuesta quedó registrada correctamente.</p>
      <p class="h4">Folio {{ $submission->folio }}</p>
      <a class="btn btn-flower mt-3" href="{{ route('login') }}">Iniciar sesión</a>
    </div>
  </div>
</div>
@endsection
