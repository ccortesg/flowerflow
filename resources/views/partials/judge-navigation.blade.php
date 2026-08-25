@php($mobile = $mobile ?? false)
<div class="ff-participant-navigation">
  <a class="ff-participant-brand" href="{{ route('judge.dashboard') }}" @if($mobile) data-bs-dismiss="offcanvas" @endif>
    <span class="ff-participant-brand-logos" aria-hidden="true">
      <img src="{{ asset('assets/flowerflow/logo_flowerflow_transparente.png') }}" width="58" height="58" alt="">
      <span></span>
      <img src="{{ asset('assets/flowerflow/logo_florecehermosillo_transparente.png') }}" width="58" height="58" alt="">
    </span>
    <span class="ff-participant-brand-name">Hermosillo Florece 2026</span>
  </a>
  <nav class="ff-participant-menu" aria-label="Menú del juez">
    <a href="{{ route('judge.dashboard') }}" @if(request()->routeIs('judge.dashboard')) aria-current="page" @endif @if($mobile) data-bs-dismiss="offcanvas" @endif><span class="ri ri-home-5-line" aria-hidden="true"></span><span>Inicio</span></a>
    <a href="{{ route('judge.assignments.index') }}" @if(request()->routeIs('judge.assignments.*')) aria-current="page" @endif @if($mobile) data-bs-dismiss="offcanvas" @endif><span class="ri ri-clipboard-line" aria-hidden="true"></span><span>Mis asignaciones</span></a>
    <a href="{{ route('judge.account') }}" @if(request()->routeIs('judge.account*')) aria-current="page" @endif @if($mobile) data-bs-dismiss="offcanvas" @endif><span class="ri ri-shield-user-line" aria-hidden="true"></span><span>Cuenta y seguridad</span></a>
  </nav>
  <div class="ff-participant-navigation-footer">
    <div class="ff-participant-help"><span class="ri ri-customer-service-2-line" aria-hidden="true"></span><div><strong>¿Necesitas ayuda?</strong><a href="mailto:convocatoria@flowerflow.com.mx">Escríbenos por correo</a></div></div>
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="ff-participant-logout" type="submit"><span class="ri ri-logout-box-r-line" aria-hidden="true"></span><span>Cerrar sesión</span></button></form>
  </div>
</div>
