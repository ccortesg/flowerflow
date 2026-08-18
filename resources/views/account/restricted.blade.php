@extends('layouts.flowerflow')

@section('title', 'Acceso de cuenta protegido')
@section('description', 'Estado seguro de acceso a Flower Flow.')

@php
  $roles = auth()->user()->getRoleNames()->values();
  $isDisabledJudge = $roles->count() === 1 && $roles->first() === \App\Enums\BusinessRole::Judge->value;
@endphp

@section('content')
<section class="ff-narrow-card" aria-labelledby="restricted-account-title" aria-describedby="restricted-account-description">
  <div class="card ff-card p-4 p-lg-5">
    <p class="ff-kicker mb-2">Cuenta protegida</p>
    <h1 id="restricted-account-title" class="h2">
      {{ $isDisabledJudge ? 'El área de evaluación aún no está habilitada' : 'Tu acceso necesita revisión administrativa' }}
    </h1>
    <p id="restricted-account-description" class="lead mb-4">
      {{ $isDisabledJudge
          ? 'Tu sesión permanece segura y no se mostrarán proyectos ni herramientas mientras la evaluación esté cerrada.'
          : 'Por seguridad, esta cuenta no entra por descarte a las áreas de participante, juez o panel.' }}
    </p>
    <a class="btn btn-outline-dark align-self-start" href="{{ route('landing') }}">Ir al sitio público</a>
  </div>
</section>
@endsection
