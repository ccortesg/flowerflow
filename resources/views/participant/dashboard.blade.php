@extends('layouts.flowerflow')

@section('title', 'Inicio')
@section('description', 'Consulta el estado de tu perfil y tus propuestas para Hermosillo Florece 2026.')

@php
  $deadlineDate = $deadline?->translatedFormat('j \d\e F \d\e Y');
  $deadlineTime = $deadline?->format('H:i');
@endphp

@section('content')
<section class="ff-dashboard-hero" aria-labelledby="dashboard-title">
  <img
    class="ff-dashboard-hero-city"
    src="{{ asset('assets/flowerflow/landing/hermosillo-atardecer.webp') }}"
    width="1680"
    height="282"
    alt=""
    aria-hidden="true">
  <img
    class="ff-dashboard-hero-mark"
    src="{{ asset('assets/flowerflow/logo_florecehermosillo_transparente.png') }}"
    width="320"
    height="320"
    alt=""
    aria-hidden="true">

  <div class="ff-dashboard-hero-content">
    <p class="ff-ui-kicker">CUENTA PARTICIPANTE</p>
    <h1 id="dashboard-title">Hola, {{ $displayName }}</h1>
    <p>Bienvenido a Hermosillo Florece 2026. Desde aquí puedes crear y dar seguimiento a tus propuestas para mejorar nuestra ciudad.</p>
  </div>
</section>

<section class="ff-dashboard-summary" aria-label="Resumen de tu participación">
  <article class="ff-dashboard-summary-card is-proposals">
    <span class="ff-dashboard-summary-icon ri ri-file-list-3-line" aria-hidden="true"></span>
    <div class="ff-dashboard-summary-copy">
      <h2>Mis propuestas</h2>
      <strong data-testid="dashboard-total-submissions">{{ $submissionCount }}</strong>
      <p>Máximo {{ $submissionLimit }} por participante</p>
    </div>
    <div class="ff-dashboard-summary-action">
      @if($canCreateSubmission)
        <a href="{{ route('submissions.create') }}">
          Nueva propuesta <span class="ri ri-arrow-right-line" aria-hidden="true"></span>
        </a>
      @elseif($creationState === 'profile')
        <a href="{{ route('profile.edit') }}">
          Completar perfil <span class="ri ri-arrow-right-line" aria-hidden="true"></span>
        </a>
      @elseif($creationState === 'limit')
        <span><span class="ri ri-information-line" aria-hidden="true"></span> Máximo alcanzado</span>
      @else
        <span><span class="ri ri-time-line" aria-hidden="true"></span> Recepción cerrada</span>
      @endif
    </div>
  </article>

  <article class="ff-dashboard-summary-card is-submitted">
    <span class="ff-dashboard-summary-icon ri ri-send-plane-line" aria-hidden="true"></span>
    <div class="ff-dashboard-summary-copy">
      <h2>Enviadas</h2>
      <strong data-testid="dashboard-submitted-submissions">{{ $submittedCount }}</strong>
      <p>Con folio</p>
    </div>
    <div class="ff-dashboard-summary-action">
      <a href="{{ route('submissions.index') }}">
        Ver mis propuestas <span class="ri ri-arrow-right-line" aria-hidden="true"></span>
      </a>
    </div>
  </article>

  <article class="ff-dashboard-summary-card is-profile">
    <span class="ff-dashboard-summary-icon ri ri-user-3-line" aria-hidden="true"></span>
    <div class="ff-dashboard-summary-copy">
      <h2>Mi perfil</h2>
      <strong class="ff-dashboard-profile-state" data-testid="dashboard-profile-state">
        {{ $profileComplete ? 'Completo' : 'Pendiente' }}
        <span class="ri {{ $profileComplete ? 'ri-checkbox-circle-fill' : 'ri-time-line' }}" aria-hidden="true"></span>
      </strong>
      <p>{{ $profileComplete ? 'Tus datos están al día' : 'Completa los datos requeridos para participar' }}</p>
    </div>
    <div class="ff-dashboard-summary-action">
      <a href="{{ route('profile.edit') }}">
        Revisar perfil <span class="ri ri-arrow-right-line" aria-hidden="true"></span>
      </a>
    </div>
  </article>
</section>

<div class="ff-dashboard-details">
  <section class="ff-dashboard-panel ff-dashboard-next" aria-labelledby="dashboard-next-title">
    <header class="ff-dashboard-panel-heading">
      <span class="ri ri-route-line" aria-hidden="true"></span>
      <div>
        <p class="ff-ui-kicker">Tu ruta de participación</p>
        <h2 id="dashboard-next-title">Siguientes pasos</h2>
      </div>
    </header>

    <ol class="ff-dashboard-steps">
      <li class="is-create">
        @if($canCreateSubmission)
          <a href="{{ route('submissions.create') }}">
            <span class="ff-dashboard-step-icon ri ri-lightbulb-flash-line" aria-hidden="true"></span>
            <span><strong>Crea tu propuesta</strong><small>Cuéntanos tu idea para mejorar Hermosillo en alguna de las categorías.</small></span>
            <span class="ri ri-arrow-right-s-line" aria-hidden="true"></span>
          </a>
        @else
          <div>
            <span class="ff-dashboard-step-icon ri ri-lightbulb-flash-line" aria-hidden="true"></span>
            <span><strong>Crea tu propuesta</strong><small>Cuéntanos tu idea para mejorar Hermosillo en alguna de las categorías.</small></span>
          </div>
        @endif
      </li>
      <li class="is-submit">
        <div>
          <span class="ff-dashboard-step-icon ri ri-calendar-check-line" aria-hidden="true"></span>
          <span>
            <strong>Envíala antes del cierre</strong>
            <small>
              @if($deadline)
                Tienes hasta el {{ $deadlineDate }} a las {{ $deadlineTime }} horas, tiempo de Hermosillo.
              @else
                La fecha límite se publicará cuando exista una convocatoria activa.
              @endif
            </small>
          </span>
        </div>
      </li>
      <li class="is-evaluation">
        <div>
          <span class="ff-dashboard-step-icon ri ri-group-line" aria-hidden="true"></span>
          <span><strong>Tu proyecto será evaluado</strong><small>Las propuestas admitidas serán evaluadas por jueces ciudadanos expertos.</small></span>
        </div>
      </li>
      <li class="is-results">
        <div>
          <span class="ff-dashboard-step-icon ri ri-medal-line" aria-hidden="true"></span>
          <span><strong>Conoce los resultados</strong><small>El resultado se notificará por correo y se publicará en los canales oficiales.</small></span>
        </div>
      </li>
    </ol>
  </section>

  <aside class="ff-dashboard-panel ff-dashboard-info" aria-labelledby="dashboard-info-title">
    <header class="ff-dashboard-panel-heading">
      <span class="ri ri-information-line" aria-hidden="true"></span>
      <div>
        <p class="ff-ui-kicker">Convocatoria 2026</p>
        <h2 id="dashboard-info-title">Información importante</h2>
      </div>
    </header>

    <div class="ff-dashboard-info-list">
      <section class="ff-dashboard-info-item is-deadline" aria-labelledby="dashboard-deadline-title">
        <span class="ri ri-calendar-event-line" aria-hidden="true"></span>
        <div>
          <h3 id="dashboard-deadline-title">Fecha límite</h3>
          @if($deadline)
            <time datetime="{{ $deadline->toIso8601String() }}">
              <strong>{{ $deadlineDate }}</strong>
              <small>{{ $deadlineTime }} horas · Tiempo de Hermosillo</small>
            </time>
          @else
            <p>No hay una convocatoria activa con fecha de cierre disponible.</p>
          @endif
        </div>
      </section>

      <section class="ff-dashboard-info-item is-categories" aria-labelledby="dashboard-categories-title">
        <span class="ri ri-apps-2-line" aria-hidden="true"></span>
        <div>
          <h3 id="dashboard-categories-title">Categorías</h3>
          @if($competition && $competition->categories->isNotEmpty())
            <ul>
              @foreach($competition->categories as $category)
                <li>{{ $category->name }}</li>
              @endforeach
            </ul>
          @else
            <p>No hay categorías activas disponibles en este momento.</p>
          @endif
        </div>
      </section>

      <section class="ff-dashboard-info-item is-prize" aria-labelledby="dashboard-prize-title">
        <span class="ri ri-award-line" aria-hidden="true"></span>
        <div>
          <h3 id="dashboard-prize-title">Premio</h3>
          <p><strong>Un Apple iPad Pro por categoría</strong><small>Como máximo, un proyecto ganador por categoría.</small></p>
        </div>
      </section>

      <section class="ff-dashboard-info-item is-free" aria-labelledby="dashboard-free-title">
        <span class="ri ri-hand-coin-line" aria-hidden="true"></span>
        <div>
          <h3 id="dashboard-free-title">Participación gratuita</h3>
          <p>No se requiere pago, compra, donación ni afiliación.</p>
        </div>
      </section>
    </div>
  </aside>
</div>
@endsection
