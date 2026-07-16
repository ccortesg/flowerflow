@php($mobile = $mobile ?? false)

<div class="ff-participant-navigation">
  <a class="ff-participant-brand" href="{{ route('dashboard') }}" @if($mobile) data-bs-dismiss="offcanvas" @endif>
    <span class="ff-participant-brand-logos" aria-hidden="true">
      <img src="{{ asset('assets/flowerflow/logo_flowerflow_transparente.png') }}" width="58" height="58" alt="">
      <span></span>
      <img src="{{ asset('assets/flowerflow/logo_florecehermosillo_transparente.png') }}" width="58" height="58" alt="">
    </span>
    <span class="ff-participant-brand-name">Hermosillo Florece 2026</span>
  </a>

  <nav class="ff-participant-menu" aria-label="Menú participante">
    <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif @if($mobile) data-bs-dismiss="offcanvas" @endif>
      <span class="ri ri-home-5-line" aria-hidden="true"></span>
      <span>Inicio</span>
    </a>
    <a href="{{ route('submissions.index') }}" @if(request()->routeIs('submissions.*')) aria-current="page" @endif @if($mobile) data-bs-dismiss="offcanvas" @endif>
      <span class="ri ri-file-list-3-line" aria-hidden="true"></span>
      <span>Mis propuestas</span>
    </a>
    <a href="{{ route('profile.edit') }}" @if(request()->routeIs('profile.*')) aria-current="page" @endif @if($mobile) data-bs-dismiss="offcanvas" @endif>
      <span class="ri ri-user-3-line" aria-hidden="true"></span>
      <span>Mi perfil</span>
    </a>
    <a href="{{ route('documents') }}" @if(request()->routeIs('documents')) aria-current="page" @endif @if($mobile) data-bs-dismiss="offcanvas" @endif>
      <span class="ri ri-file-text-line" aria-hidden="true"></span>
      <span>Documentos</span>
    </a>
    <a href="{{ route('landing') }}#preguntas" @if($mobile) data-bs-dismiss="offcanvas" @endif>
      <span class="ri ri-question-answer-line" aria-hidden="true"></span>
      <span>Preguntas frecuentes</span>
    </a>
  </nav>

  <div class="ff-participant-navigation-footer">
    <div class="ff-participant-help">
      <span class="ri ri-customer-service-2-line" aria-hidden="true"></span>
      <div>
        <strong>¿Necesitas ayuda?</strong>
        <a href="mailto:convocatoria@flowerflow.com.mx">Escríbenos por correo</a>
      </div>
    </div>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="ff-participant-logout" type="submit">
        <span class="ri ri-logout-box-r-line" aria-hidden="true"></span>
        <span>Cerrar sesión</span>
      </button>
    </form>
  </div>
</div>
