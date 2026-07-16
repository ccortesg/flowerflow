<!doctype html>
<html lang="es-MX">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Hermosillo Florece 2026') · FlowerFlow</title>
  <meta name="description" content="@yield('description', 'Registra tu propuesta para Hermosillo Florece 2026.')">
  <link rel="canonical" href="{{ config('flowerflow.canonical_url').request()->getPathInfo() }}">
  @if(request()->is('panel*') || auth()->check())<meta name="robots" content="noindex,nofollow">@endif
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @stack('head')
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
@if(auth()->check())
  <div class="container-fluid ff-shell">
    <div class="row">
      <aside class="col-md-3 col-xl-2 ff-sidebar p-3" aria-label="Navegación de cuenta">
        <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 mb-4 p-0">
          <img class="ff-logo" src="{{ asset('assets/flowerflow/logo_flowerflow_transparente.png') }}" alt="">
          <strong>FlowerFlow</strong>
        </a>
        @if(auth()->user()->hasAnyRole(['admin', 'reviewer']))
          <a href="{{ route('panel.dashboard') }}" @if(request()->routeIs('panel.dashboard')) aria-current="page" @endif>Resumen</a>
          <a href="{{ route('panel.submissions.index') }}" @if(request()->routeIs('panel.submissions.*')) aria-current="page" @endif>Propuestas</a>
          <a href="{{ route('panel.account') }}" @if(request()->routeIs('panel.account')) aria-current="page" @endif>Cuenta y seguridad</a>
        @else
          <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>Inicio</a>
          <a href="{{ route('submissions.index') }}" @if(request()->routeIs('submissions.*')) aria-current="page" @endif>Mis propuestas</a>
          <a href="{{ route('profile.edit') }}" @if(request()->routeIs('profile.*')) aria-current="page" @endif>Mi perfil</a>
        @endif
        <form method="POST" action="{{ route('logout') }}" class="mt-4">@csrf<button class="btn btn-sm btn-outline-light w-100">Cerrar sesión</button></form>
      </aside>
      <main id="contenido" class="col-md-9 col-xl-10 p-3 p-lg-5">
        @include('partials.messages')
        @yield('content')
      </main>
    </div>
  </div>
@else
  <header class="ff-navbar sticky-top">
    <nav class="navbar navbar-expand-lg container" aria-label="Navegación principal">
      <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('landing') }}">
        <img class="ff-logo" src="{{ asset('assets/flowerflow/logo_flowerflow_transparente.png') }}" alt="Logo FlowerFlow"> FlowerFlow
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navPublica" aria-controls="navPublica" aria-expanded="false" aria-label="Abrir navegación"><span class="navbar-toggler-icon"></span></button>
      <div class="collapse navbar-collapse" id="navPublica">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
          <li class="nav-item"><a class="nav-link" href="{{ route('landing') }}#categorias">Categorías</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('landing') }}#como-participar">Cómo participar</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('documents') }}">Documentos</a></li>
          <li class="nav-item"><a class="btn btn-flower ms-lg-2" href="{{ route('login') }}">Iniciar sesión</a></li>
        </ul>
      </div>
    </nav>
  </header>
  <main id="contenido">@include('partials.messages') @yield('content')</main>
  <footer class="bg-dark text-white py-5"><div class="container small"><div class="row g-4"><div class="col-md-7"><strong>FLORECE HERMOSILLO</strong><p class="mb-0 mt-2">Agrupación: FLOWER FLOW<br>Colonia Centro Hermosillo, Sonora. CP 83000</p></div><div class="col-md-5"><strong>Contacto</strong><p class="mb-0 mt-2"><a class="text-white" href="mailto:convocatoria@flowerflow.com.mx">convocatoria@flowerflow.com.mx</a><br><a class="text-white" href="mailto:privacidad@flowerflow.com.mx">privacidad@flowerflow.com.mx</a></p></div></div></div></footer>
@endif
@stack('scripts')
</body>
</html>
