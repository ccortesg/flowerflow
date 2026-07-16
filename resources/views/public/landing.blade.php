@extends('layouts.flowerflow')
@section('title', 'Hermosillo Florece 2026')
@section('description', 'Convocatoria ciudadana gratuita para compartir propuestas que ayuden a mejorar Hermosillo.')
@section('content')
@php
  $categories = $competition?->categories ?? collect([
    (object) ['name' => 'Movilidad con Flow', 'description' => 'Ideas para mejorar la movilidad, vialidad, accesibilidad y seguridad en nuestros traslados.'],
    (object) ['name' => 'Hermosillo Florece', 'description' => 'Propuestas para una ciudad más verde, sostenible, fresca y responsable con el agua.'],
    (object) ['name' => 'Mi familia, mi mascota', 'description' => 'Soluciones para el bienestar animal, la tenencia responsable y una mejor convivencia.'],
  ]);
  $categoryIcons = ['ri-bus-line', 'ri-seedling-line', 'ri-heart-3-line'];
  $registrationOpen = config('flowerflow.flags.registration');
  $submissionsOpen = config('flowerflow.flags.submissions');
@endphp

<section class="ff-landing-hero" aria-labelledby="landing-title">
  <div class="ff-landing-container">
    <div class="ff-hero-card">
      <div class="ff-hero-copy">
        <p class="ff-eyebrow">Convocatoria ciudadana · Hermosillo 2026</p>
        <h1 id="landing-title">¡Para mejorar aún más Hermosillo, <span>todos a participar!</span></h1>
        <p class="ff-hero-lead">Comparte una idea clara y realizable para transformar nuestra movilidad, el entorno urbano o la convivencia con nuestras mascotas.</p>

        <div class="ff-hero-deadline">
          <span class="ff-landing-icon ri-calendar-check-line" aria-hidden="true"></span>
          <p><strong>Fecha límite</strong><br>15 de agosto de 2026, 23:59 horas <span>(tiempo de Hermosillo)</span></p>
        </div>

        <p @class(['ff-landing-status', 'is-open' => $submissionsOpen])>
          <span aria-hidden="true"></span>
          {{ $submissionsOpen ? 'Recepción de propuestas abierta' : 'Recepción aún no habilitada' }}
        </p>

        <div class="ff-hero-actions">
          @if($registrationOpen)
            <a class="ff-button ff-button-primary ff-button-large" href="{{ url('/register') }}">Crear mi cuenta</a>
          @else
            <span class="ff-button ff-button-muted ff-button-large" aria-disabled="true">Registro próximamente</span>
          @endif
          <a class="ff-button ff-button-secondary ff-button-large" href="{{ route('login') }}">Ya tengo cuenta</a>
        </div>
        <a class="ff-inline-link" href="#como-participar">Conoce cómo participar <span class="ff-landing-icon ri-arrow-right-line" aria-hidden="true"></span></a>
      </div>

      <div class="ff-hero-prize" aria-label="Premio de la convocatoria">
        <p class="ff-hero-prize-label">Participa y gana</p>
        <p class="ff-hero-prize-title">Apple <strong>iPad Pro</strong></p>
        <img src="{{ asset('assets/flowerflow/landing/premio-ipad-pro.webp') }}" width="514" height="757" alt="" fetchpriority="high">
        <p class="ff-prize-badge"><span class="ff-landing-icon ri-trophy-line" aria-hidden="true"></span> 1 ganador por categoría</p>
      </div>

      <img class="ff-hero-city" src="{{ asset('assets/flowerflow/landing/hermosillo-atardecer.webp') }}" width="1680" height="282" alt="Vista panorámica de Hermosillo al atardecer" fetchpriority="high">
    </div>
  </div>
</section>

<section class="ff-landing-section ff-section-intro" aria-labelledby="objetivo-title">
  <div class="ff-landing-container ff-intro-grid">
    <div>
      <p class="ff-eyebrow">Tu idea puede hacer la diferencia</p>
      <h2 id="objetivo-title">Hagamos florecer a Hermosillo</h2>
    </div>
    <p>Buscamos soluciones ciudadanas originales, útiles y sostenibles. Puedes participar por tu cuenta o sumar talentos en un equipo de hasta cinco integrantes.</p>
  </div>
</section>

<section id="categorias" class="ff-landing-section ff-section-tinted" aria-labelledby="categorias-title">
  <div class="ff-landing-container">
    <div class="ff-section-heading">
      <p class="ff-eyebrow">Tres formas de transformar la ciudad</p>
      <h2 id="categorias-title">Elige tu categoría</h2>
      <p>Identifica el reto que más te mueve y presenta una propuesta concreta.</p>
    </div>
    <div class="ff-category-grid">
      @foreach($categories as $category)
        <article class="ff-category-card">
          <span class="ff-category-icon ff-landing-icon {{ $categoryIcons[$loop->index % count($categoryIcons)] }}" aria-hidden="true"></span>
          <div>
            <h3>{{ $category->name }}</h3>
            <p>{{ $category->description }}</p>
          </div>
          <span class="ff-landing-icon ri-arrow-right-line ff-category-arrow" aria-hidden="true"></span>
        </article>
      @endforeach
    </div>
  </div>
</section>

<section id="como-participar" class="ff-landing-section" aria-labelledby="participar-title">
  <div class="ff-landing-container">
    <div class="ff-section-heading ff-section-heading-centered">
      <p class="ff-eyebrow">Un proceso sencillo</p>
      <h2 id="participar-title">¿Cómo participar?</h2>
      <p>Avanza a tu ritmo, guarda tu borrador y envía cuando todo esté listo.</p>
    </div>
    <ol class="ff-step-grid">
      <li class="ff-step-card">
        <span class="ff-step-number">01</span>
        <span class="ff-step-icon ff-landing-icon ri-user-add-line" aria-hidden="true"></span>
        <h3>Crea tu cuenta</h3>
        <p>Registra tus datos personales y acepta los documentos legales vigentes.</p>
      </li>
      <li class="ff-step-card">
        <span class="ff-step-number">02</span>
        <span class="ff-step-icon ff-landing-icon ri-mail-check-line" aria-hidden="true"></span>
        <h3>Verifica tu correo</h3>
        <p>Abre el mensaje de confirmación para activar el acceso a la plataforma.</p>
      </li>
      <li class="ff-step-card">
        <span class="ff-step-number">03</span>
        <span class="ff-step-icon ff-landing-icon ri-draft-line" aria-hidden="true"></span>
        <h3>Prepara tu propuesta</h3>
        <p>Describe tu idea, agrega a tu equipo y adjunta los archivos necesarios.</p>
      </li>
      <li class="ff-step-card">
        <span class="ff-step-number">04</span>
        <span class="ff-step-icon ff-landing-icon ri-send-plane-line" aria-hidden="true"></span>
        <h3>Revisa y envía</h3>
        <p>Finaliza antes del cierre y conserva el folio que confirma tu participación.</p>
      </li>
    </ol>
  </div>
</section>

<section id="requisitos" class="ff-landing-section ff-section-tinted" aria-labelledby="requisitos-title">
  <div class="ff-landing-container">
    <div class="ff-section-heading">
      <p class="ff-eyebrow">Antes de comenzar</p>
      <h2 id="requisitos-title">Requisitos para participar</h2>
      <p>Verifica que tú y tu propuesta cumplan estas condiciones esenciales.</p>
    </div>
    <div class="ff-requirement-grid">
      <article class="ff-requirement-card"><span class="ff-landing-icon ri-user-line" aria-hidden="true"></span><div><h3>Ser mayor de edad</h3><p>Tener 18 años cumplidos al momento de registrarte.</p></div></article>
      <article class="ff-requirement-card"><span class="ff-landing-icon ri-map-pin-line" aria-hidden="true"></span><div><h3>Vivir en Hermosillo</h3><p>Contar con residencia comprobable en el municipio.</p></div></article>
      <article class="ff-requirement-card"><span class="ff-landing-icon ri-team-line" aria-hidden="true"></span><div><h3>Participar solo o en equipo</h3><p>Integra un equipo de hasta cinco personas, incluida la representante.</p></div></article>
      <article class="ff-requirement-card"><span class="ff-landing-icon ri-draft-line" aria-hidden="true"></span><div><h3>Hasta tres propuestas</h3><p>Registra una propuesta por categoría y un máximo de tres por cuenta.</p></div></article>
      <article class="ff-requirement-card"><span class="ff-landing-icon ri-translate-2" aria-hidden="true"></span><div><h3>Presentar en español</h3><p>La propuesta debe ser clara, original y realizable. Participar es gratuito.</p></div></article>
      <article class="ff-requirement-card"><span class="ff-landing-icon ri-calendar-check-line" aria-hidden="true"></span><div><h3>Enviar a tiempo</h3><p>Finaliza tu propuesta antes del 15 de agosto de 2026 a las 23:59.</p></div></article>
    </div>
  </div>
</section>

<section class="ff-landing-section ff-prize-section" aria-labelledby="premio-title">
  <div class="ff-landing-container">
    <div class="ff-prize-card">
      <div class="ff-prize-visual" aria-hidden="true">
        <img src="{{ asset('assets/flowerflow/landing/premio-ipad-pro.webp') }}" width="514" height="757" alt="" loading="lazy">
      </div>
      <div class="ff-prize-copy">
        <p class="ff-eyebrow">Reconocemos las mejores ideas</p>
        <h2 id="premio-title">Gana un Apple <span>iPad Pro</span></h2>
        <p>Se entregará un premio por categoría. Si una propuesta de equipo resulta ganadora, se entregará un solo premio al equipo.</p>
        <div class="ff-prize-facts">
          <p><strong>1</strong><span>ganador máximo por categoría</span></p>
          <p><strong>3</strong><span>ganadores máximos en total</span></p>
        </div>
        <p class="ff-prize-note">Una categoría puede declararse desierta conforme a la convocatoria.</p>
      </div>
    </div>
  </div>
</section>

<section id="documentos" class="ff-landing-section ff-section-tinted" aria-labelledby="documentos-title">
  <div class="ff-landing-container ff-information-grid">
    <div>
      <div class="ff-section-heading">
        <p class="ff-eyebrow">Consulta antes de participar</p>
        <h2 id="documentos-title">Documentos oficiales</h2>
        <p>Descarga y revisa la información completa de la convocatoria.</p>
      </div>
      <div class="ff-document-list">
        <a href="{{ asset('documentos/2026/01_Mecanica_Convocatoria_Hermosillo_Florece_2026.pdf') }}" download type="application/pdf">
          <span class="ff-landing-icon ri-file-pdf-2-line" aria-hidden="true"></span>
          <span><strong>Mecánica de la convocatoria</strong><small>Descargar PDF</small></span>
          <span class="ff-landing-icon ri-arrow-right-line" aria-hidden="true"></span>
        </a>
        <a href="{{ asset('documentos/2026/02_Terminos_y_Condiciones_Plataforma_Flower_Flow_2026.pdf') }}" download type="application/pdf">
          <span class="ff-landing-icon ri-file-pdf-2-line" aria-hidden="true"></span>
          <span><strong>Términos y condiciones</strong><small>Descargar PDF</small></span>
          <span class="ff-landing-icon ri-arrow-right-line" aria-hidden="true"></span>
        </a>
        <a href="{{ asset('documentos/2026/03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026.pdf') }}" download type="application/pdf">
          <span class="ff-landing-icon ri-file-pdf-2-line" aria-hidden="true"></span>
          <span><strong>Aviso de privacidad integral</strong><small>Descargar PDF</small></span>
          <span class="ff-landing-icon ri-arrow-right-line" aria-hidden="true"></span>
        </a>
      </div>
    </div>

    <div id="preguntas">
      <div class="ff-section-heading">
        <p class="ff-eyebrow">Resolvemos tus dudas</p>
        <h2>Preguntas frecuentes</h2>
      </div>
      <div class="accordion ff-faq" id="landing-faq">
        <div class="accordion-item">
          <h3 class="accordion-header" id="faq-heading-1"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq-answer-1" aria-expanded="true" aria-controls="faq-answer-1">¿Participar tiene algún costo?</button></h3>
          <div id="faq-answer-1" class="accordion-collapse collapse show" aria-labelledby="faq-heading-1" data-bs-parent="#landing-faq"><div class="accordion-body">No. Registrarte y enviar tus propuestas es completamente gratuito.</div></div>
        </div>
        <div class="accordion-item">
          <h3 class="accordion-header" id="faq-heading-2"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-answer-2" aria-expanded="false" aria-controls="faq-answer-2">¿Puedo participar con otras personas?</button></h3>
          <div id="faq-answer-2" class="accordion-collapse collapse" aria-labelledby="faq-heading-2" data-bs-parent="#landing-faq"><div class="accordion-body">Sí. Puedes participar individualmente o en un equipo de hasta cinco integrantes, incluida la persona representante.</div></div>
        </div>
        <div class="accordion-item">
          <h3 class="accordion-header" id="faq-heading-3"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-answer-3" aria-expanded="false" aria-controls="faq-answer-3">¿Un borrador ya cuenta como envío?</button></h3>
          <div id="faq-answer-3" class="accordion-collapse collapse" aria-labelledby="faq-heading-3" data-bs-parent="#landing-faq"><div class="accordion-body">No. Sólo una propuesta finalizada genera folio y confirmación de participación.</div></div>
        </div>
        <div class="accordion-item">
          <h3 class="accordion-header" id="faq-heading-4"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-answer-4" aria-expanded="false" aria-controls="faq-answer-4">¿Dónde puedo resolver otra duda?</button></h3>
          <div id="faq-answer-4" class="accordion-collapse collapse" aria-labelledby="faq-heading-4" data-bs-parent="#landing-faq"><div class="accordion-body">Escribe a <a href="mailto:convocatoria@flowerflow.com.mx">convocatoria@flowerflow.com.mx</a> y te orientaremos.</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="ff-final-cta" aria-labelledby="cta-title">
  <div class="ff-landing-container">
    <div>
      <p class="ff-eyebrow">Hermosillo necesita tus ideas</p>
      <h2 id="cta-title">¿Listo para hacer florecer nuestra ciudad?</h2>
      <p>Da el primer paso y prepara una propuesta con impacto positivo.</p>
    </div>
    @if($registrationOpen)
      <a class="ff-button ff-button-light ff-button-large" href="{{ url('/register') }}">Crear mi cuenta</a>
    @else
      <span class="ff-button ff-button-dark-muted ff-button-large" aria-disabled="true">Registro próximamente</span>
    @endif
  </div>
</section>
@endsection
