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
  <script>document.documentElement.classList.add('ff-js');</script>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @stack('head')
</head>
@php
  $isLandingPage = request()->routeIs('landing');
  $isLoginPage = request()->routeIs('login', 'panel.login');
  $isParticipant = auth()->check() && ! auth()->user()->hasAnyRole(['admin', 'reviewer']);
  $isProfilePage = request()->routeIs('profile.*');
  $isSubmissionsPage = request()->routeIs('submissions.index');

  if ($isParticipant) {
      $displayName = trim(auth()->user()->profile?->first_names ?: auth()->user()->name);
      $nameParts = collect(preg_split('/\s+/u', $displayName) ?: [])->filter()->take(2);
      $userInitials = $nameParts->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: 'FF';
      $roleName = auth()->user()->getRoleNames()->first();
      $roleLabel = $roleName === 'participant' ? 'Participante' : 'Cuenta verificada';
  }
@endphp
<body @class([
  'ff-public-landing' => $isLandingPage,
  'ff-auth-login-page' => $isLoginPage,
  'ff-panel-login-page' => request()->routeIs('panel.login'),
  'ff-participant-shell-page' => $isParticipant,
  'ff-participant-profile-page' => $isParticipant && $isProfilePage,
  'ff-participant-submissions-page' => $isParticipant && $isSubmissionsPage,
])>
<a class="skip-link" href="#contenido">Saltar al contenido</a>

@if($isParticipant)
  <div class="ff-participant-shell">
    <aside class="ff-participant-sidebar d-none d-lg-flex" aria-label="Navegación de cuenta">
      @include('partials.participant-navigation')
    </aside>

    <div class="ff-participant-workspace">
      <header class="ff-participant-mobile-header d-lg-none">
        <a class="ff-participant-mobile-brand" href="{{ route('dashboard') }}" aria-label="Ir al inicio de Flower Flow">
          <img src="{{ asset('assets/flowerflow/logo_flowerflow_transparente.png') }}" width="48" height="48" alt="">
          <strong>Flower Flow</strong>
        </a>
        <button class="ff-icon-button" type="button" data-bs-toggle="offcanvas" data-bs-target="#participantNavigation" aria-controls="participantNavigation" aria-label="Abrir menú">
          <span class="ri ri-menu-line" aria-hidden="true"></span>
        </button>
      </header>

      <div class="offcanvas offcanvas-start ff-participant-offcanvas" tabindex="-1" id="participantNavigation" aria-labelledby="participantNavigationLabel">
        <div class="offcanvas-header">
          <h2 class="visually-hidden" id="participantNavigationLabel">Navegación de cuenta</h2>
          <button class="ff-icon-button ms-auto" type="button" data-bs-dismiss="offcanvas" aria-label="Cerrar menú">
            <span class="ri ri-close-line" aria-hidden="true"></span>
          </button>
        </div>
        <div class="offcanvas-body p-0">
          @include('partials.participant-navigation', ['mobile' => true])
        </div>
      </div>

      <header class="ff-participant-topbar d-none d-lg-flex">
        <span class="ff-participant-topbar-context">Hermosillo Florece 2026</span>
        <div class="ff-user-chip" aria-label="Cuenta de {{ $displayName }}; {{ $roleLabel }}">
          <span class="ff-user-initials" aria-hidden="true">{{ $userInitials }}</span>
          <span>
            <strong>{{ $displayName }}</strong>
            <small>{{ $roleLabel }}</small>
          </span>
        </div>
      </header>

      <main id="contenido" class="ff-participant-content">
        @include('partials.messages')
        @yield('content')
      </main>
    </div>
  </div>
@elseif(auth()->check())
  <div class="container-fluid ff-shell">
    <div class="row">
      <aside class="col-md-3 col-xl-2 ff-sidebar p-3" aria-label="Navegación de cuenta">
        <a href="{{ route('panel.dashboard') }}" class="d-flex align-items-center gap-2 mb-4 p-0">
          <img class="ff-logo" src="{{ asset('assets/flowerflow/logo_flowerflow_transparente.png') }}" alt="">
          <strong>FlowerFlow</strong>
        </a>
        <a href="{{ route('panel.dashboard') }}" @if(request()->routeIs('panel.dashboard')) aria-current="page" @endif>Resumen</a>
        <a href="{{ route('panel.submissions.index') }}" @if(request()->routeIs('panel.submissions.*')) aria-current="page" @endif>Propuestas</a>
        <a href="{{ route('panel.account') }}" @if(request()->routeIs('panel.account')) aria-current="page" @endif>Cuenta y seguridad</a>
        <form method="POST" action="{{ route('logout') }}" class="mt-4">@csrf<button class="btn btn-sm btn-outline-light w-100">Cerrar sesión</button></form>
      </aside>
      <main id="contenido" class="col-md-9 col-xl-10 p-3 p-lg-5">
        @include('partials.messages')
        @yield('content')
      </main>
    </div>
  </div>
@else
  @if($isLandingPage)
    @include('public.partials.landing-header')
  @elseif(! $isLoginPage)
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
  @endif
  <main id="contenido" @class(['ff-landing-main' => $isLandingPage])>
    @unless($isLoginPage)@include('partials.messages')@endunless
    @yield('content')
  </main>
  @if($isLandingPage)
    @include('public.partials.landing-footer')
  @elseif(! $isLoginPage)
    <footer class="bg-dark text-white py-5"><div class="container small"><div class="row g-4"><div class="col-md-7"><strong>FLORECE HERMOSILLO</strong><p class="mb-0 mt-2">Agrupación: FLOWER FLOW<br>Colonia Centro Hermosillo, Sonora. CP 83000</p></div><div class="col-md-5"><strong>Contacto</strong><p class="mb-0 mt-2"><a class="text-white" href="mailto:convocatoria@flowerflow.com.mx">convocatoria@flowerflow.com.mx</a><br><a class="text-white" href="mailto:privacidad@flowerflow.com.mx">privacidad@flowerflow.com.mx</a></p></div></div></div></footer>
  @endif
@endif
@stack('scripts')
</body>
</html>
