@extends('layouts.flowerflow')

@php($isPanelLogin = ($panel ?? false) === true)

@section('title', $isPanelLogin ? 'Acceso al panel' : 'Iniciar sesión')
@section('description', $isPanelLogin ? 'Acceso autorizado al panel de Flower Flow.' : 'Inicia sesión en tu cuenta participante de Hermosillo Florece 2026.')

@section('content')
<header class="ff-login-header">
  <nav class="ff-login-nav" aria-label="Navegación de acceso">
    <a class="ff-login-brand" href="{{ route('landing') }}" aria-label="Ir al inicio de Hermosillo Florece 2026">
      <img src="{{ asset('assets/flowerflow/logo_flowerflow_transparente.png') }}" width="58" height="58" alt="Flower Flow">
      <span aria-hidden="true"></span>
      <img src="{{ asset('assets/flowerflow/logo_florecehermosillo_transparente.png') }}" width="58" height="58" alt="Florece Hermosillo">
    </a>

    @unless($isPanelLogin)
      <button class="ff-login-menu-toggle d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#loginNavigation" aria-controls="loginNavigation" aria-expanded="false" aria-label="Abrir navegación">
        <span class="ri ri-menu-line" aria-hidden="true"></span>
      </button>
      <div class="collapse ff-login-nav-panel" id="loginNavigation">
        <a href="{{ route('landing') }}#categorias">Categorías</a>
        <a href="{{ route('landing') }}#como-participar">Cómo participar</a>
        <a href="{{ route('documents') }}">Documentos</a>
        @if(config('flowerflow.flags.registration'))
          <a class="ff-login-nav-cta" href="{{ url('/register') }}">Crear cuenta</a>
        @endif
      </div>
    @else
      <a class="ff-login-back-link" href="{{ route('landing') }}">
        <span class="ri ri-arrow-left-line" aria-hidden="true"></span>
        Volver al sitio público
      </a>
    @endunless
  </nav>
</header>

<section class="ff-login-stage" aria-labelledby="login-title">
  @unless($isPanelLogin)
    <img class="ff-login-brand-motif" src="{{ asset('assets/flowerflow/logo_florecehermosillo_transparente.png') }}" width="320" height="320" alt="" aria-hidden="true">
  @endunless

  <div class="ff-login-card">
    <div class="ff-login-card-heading">
      <p class="ff-ui-kicker">{{ $isPanelLogin ? 'Administración' : 'Cuenta participante' }}</p>
      <h1 id="login-title">{{ $isPanelLogin ? 'Acceso al panel' : 'Iniciar sesión' }}</h1>
      <p>{{ $isPanelLogin ? 'Ingresa con una cuenta autorizada para administrar la plataforma.' : 'Continúa con tu participación en Hermosillo Florece 2026.' }}</p>
    </div>

    @include('partials.messages')

    <form method="POST" action="{{ route('login') }}" class="ff-login-form">
      @csrf
      <div>
        <label class="form-label" for="email">Correo electrónico</label>
        <div class="ff-input-with-icon">
          <span class="ri ri-mail-line" aria-hidden="true"></span>
          <input
            class="form-control @error('email') is-invalid @enderror"
            id="email"
            name="email"
            type="email"
            value="{{ old('email') }}"
            required
            autocomplete="email"
            autofocus
            @error('email') aria-invalid="true" @enderror
          >
        </div>
        @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
      </div>

      <div>
        <label class="form-label" for="password">Contraseña</label>
        <div class="ff-input-with-icon ff-password-input-group">
          <span class="ri ri-lock-2-line" aria-hidden="true"></span>
          <input
            class="form-control @error('password') is-invalid @enderror"
            id="password"
            name="password"
            type="password"
            required
            autocomplete="current-password"
            @error('password') aria-invalid="true" @enderror
          >
          <button class="ff-password-toggle" type="button" data-password-toggle aria-controls="password" aria-label="Mostrar contraseña" aria-pressed="false">
            <span class="ri ri-eye-line" aria-hidden="true" data-password-toggle-icon></span>
            <span data-password-toggle-label>Mostrar</span>
          </button>
        </div>
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
      </div>

      <div class="ff-login-form-options">
        <div class="form-check">
          <input class="form-check-input" id="remember" name="remember" type="checkbox" @checked(old('remember'))>
          <label class="form-check-label" for="remember">Mantener sesión</label>
        </div>
        <a href="{{ route('password.request') }}">Olvidé mi contraseña</a>
      </div>

      <button class="ff-ui-button ff-ui-button-primary w-100" type="submit">
        Entrar
        <span class="ri ri-arrow-right-line" aria-hidden="true"></span>
      </button>
    </form>

    @if(! $isPanelLogin && config('flowerflow.flags.registration'))
      <p class="ff-login-register">¿Aún no tienes cuenta? <a href="{{ url('/register') }}">Regístrate</a></p>
    @endif

    <p class="ff-login-support">
      <span class="ri ri-shield-check-line" aria-hidden="true"></span>
      {{ $isPanelLogin ? 'Acceso restringido a personal autorizado.' : 'Conexión segura y protección de tus datos personales.' }}
    </p>
  </div>
</section>

@unless($isPanelLogin)
  <section class="ff-login-benefits" aria-label="Información de la convocatoria">
    <div class="ff-login-benefits-grid">
      <article>
        <span class="ri ri-user-line" aria-hidden="true"></span>
        <div><h2>¿Quién puede participar?</h2><p>Personas físicas de 18 años o más que residan en Hermosillo.</p></div>
      </article>
      <article>
        <span class="ri ri-trophy-line" aria-hidden="true"></span>
        <div><h2>¿Qué puedes ganar?</h2><p>Un Apple iPad Pro para el proyecto ganador de cada categoría.</p></div>
      </article>
      <article>
        <span class="ri ri-shield-user-line" aria-hidden="true"></span>
        <div><h2>Seguridad y privacidad</h2><p>Tus datos están protegidos. <a href="{{ asset('documentos/2026/03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026.pdf') }}">Consulta nuestro aviso de privacidad.</a></p></div>
      </article>
      <article>
        <span class="ri ri-customer-service-2-line" aria-hidden="true"></span>
        <div><h2>¿Necesitas ayuda?</h2><p>Escríbenos a <a href="mailto:convocatoria@flowerflow.com.mx">convocatoria@flowerflow.com.mx</a></p></div>
      </article>
    </div>
  </section>
@endunless
@endsection
