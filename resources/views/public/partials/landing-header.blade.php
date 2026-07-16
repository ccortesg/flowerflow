<header class="ff-public-header" data-public-header>
  <nav class="ff-landing-container ff-public-nav" aria-label="Navegación principal">
    <a class="ff-brand-lockup" href="{{ route('landing') }}" aria-label="Flower Flow y Florece Hermosillo, página de inicio">
      <img src="{{ asset('assets/flowerflow/logo_flowerflow_transparente.png') }}" width="320" height="320" alt="Flower Flow">
      <span class="ff-brand-divider" aria-hidden="true"></span>
      <img src="{{ asset('assets/flowerflow/logo_florecehermosillo_transparente.png') }}" width="320" height="320" alt="Florece Hermosillo">
    </a>

    <div class="ff-public-nav-actions">
      @if(config('flowerflow.flags.registration'))
        <a class="ff-button ff-button-primary ff-header-cta ff-header-cta-mobile" href="{{ url('/register') }}">Participar</a>
      @endif
      <button class="ff-menu-toggle" type="button" aria-expanded="false" aria-controls="landing-navigation" aria-label="Abrir menú" data-public-menu-toggle>
        <span class="ff-landing-icon ri-menu-line" aria-hidden="true"></span>
        <span class="ff-landing-icon ri-close-line" aria-hidden="true"></span>
      </button>
    </div>

    <div class="ff-public-nav-panel" id="landing-navigation" data-public-nav>
      <ul class="ff-public-nav-links">
        <li><a href="#categorias">Categorías</a></li>
        <li><a href="#como-participar">Cómo participar</a></li>
        <li><a href="#requisitos">Requisitos</a></li>
        <li><a href="#preguntas">Preguntas</a></li>
      </ul>
      <div class="ff-public-nav-account">
        <a class="ff-login-link" href="{{ route('login') }}">
          <span class="ff-landing-icon ri-login-box-line" aria-hidden="true"></span>
          Iniciar sesión
        </a>
        @if(config('flowerflow.flags.registration'))
          <a class="ff-button ff-button-primary ff-header-cta" href="{{ url('/register') }}">Quiero participar</a>
        @else
          <span class="ff-button ff-button-muted ff-header-cta" aria-disabled="true">Registro próximamente</span>
        @endif
      </div>
    </div>
  </nav>
</header>
