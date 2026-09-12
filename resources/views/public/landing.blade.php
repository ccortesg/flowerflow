@extends('layouts.flowerflow')
@section('title', '¡La gente elige! · Hermosillo 2026')
@section('description', 'Vota por tu proyecto favorito. Tu opinión cuenta. Hagamos florecer a Hermosillo.')
@section('content')

<section class="ff-landing-hero ff-voting-hero" aria-labelledby="landing-title">
  <div class="ff-landing-container">
    <div class="ff-voting-card">
      <div class="ff-voting-copy">
        <p class="ff-eyebrow">Votación ciudadana · Hermosillo 2026</p>
        <h1 id="landing-title">¡La gente <span>elige!</span></h1>
        <p class="ff-voting-lead">Vota por tu proyecto favorito. Tu opinión cuenta. Hagamos florecer a Hermosillo.</p>
        <div class="ff-voting-actions">
          <a class="ff-button ff-button-primary ff-button-large" href="{{ config('flowerflow.voting.form_url') }}" target="_blank" rel="noopener noreferrer" data-voting-trigger>Votar</a>
          <a class="ff-inline-link" href="#ganadores">Cómo se eligen los ganadores <span class="ff-landing-icon ri-arrow-right-line" aria-hidden="true"></span></a>
        </div>
      </div>
      <img class="ff-voting-art" src="{{ asset('assets/flowerflow/landing/voting-illustration-1024.webp') }}" srcset="{{ asset('assets/flowerflow/landing/voting-illustration-640.webp') }} 640w, {{ asset('assets/flowerflow/landing/voting-illustration-1024.webp') }} 1024w" sizes="(max-width: 575px) calc(100vw - 2rem), (max-width: 991px) 512px, (max-width: 1212px) 49vw, 576px" width="1024" height="1024" alt="" fetchpriority="high">
    </div>
  </div>
</section>

<section class="ff-landing-section ff-section-intro" aria-labelledby="objetivo-title">
  <div class="ff-landing-container ff-intro-grid">
    <div>
      <p class="ff-eyebrow">Tu voto puede hacer la diferencia</p>
      <h2 id="objetivo-title">Hagamos florecer a Hermosillo</h2>
    </div>
    <p>Elige tu proyecto favorito y apoya con tu voto las ideas ciudadanas para transformar Hermosillo.</p>
  </div>
</section>

<section id="ganadores" class="ff-landing-section ff-prize-section" aria-labelledby="premio-title">
  <div class="ff-landing-container">
    <div class="ff-prize-card">
      <div class="ff-prize-copy">
        <p class="ff-eyebrow">Reconocemos las mejores ideas</p>
        <h2 id="premio-title">Los 2 proyectos con más votos serán los ganadores</h2>
      </div>
      <ol class="ff-awards-grid" aria-label="Premios de la votación ciudadana">
        <li class="ff-award">
          <p class="ff-award-place">Primer lugar</p>
          <div class="ff-award-media">
            <img src="{{ asset('assets/flowerflow/landing/prize-metaquest3s-960.webp') }}" srcset="{{ asset('assets/flowerflow/landing/prize-metaquest3s-480.webp') }} 480w, {{ asset('assets/flowerflow/landing/prize-metaquest3s-960.webp') }} 960w" sizes="(max-width: 767px) calc(100vw - 6rem), (max-width: 1212px) calc(50vw - 6rem), 460px" width="960" height="518" alt="Visor Meta Quest 3S con sus controles" loading="lazy" decoding="async">
          </div>
          <h3>Meta Quest 3S</h3>
        </li>
        <li class="ff-award">
          <p class="ff-award-place">Segundo lugar</p>
          <div class="ff-award-media">
            <img src="{{ asset('assets/flowerflow/landing/prize-beats-solo4-640.webp') }}" srcset="{{ asset('assets/flowerflow/landing/prize-beats-solo4-320.webp') }} 320w, {{ asset('assets/flowerflow/landing/prize-beats-solo4-640.webp') }} 640w" sizes="(max-width: 767px) calc(100vw - 6rem), (max-width: 1212px) calc(50vw - 6rem), 460px" width="640" height="921" alt="Audífonos inalámbricos Beats Solo 4" loading="lazy" decoding="async">
          </div>
          <h3>Beats Solo 4</h3>
          <p class="ff-award-description">Audífonos inalámbricos</p>
        </li>
      </ol>
    </div>
  </div>
</section>

<section class="ff-final-cta" aria-labelledby="cta-title">
  <div class="ff-landing-container">
    <div>
      <p class="ff-eyebrow">Tu opinión cuenta</p>
      <h2 id="cta-title">Elige tu proyecto favorito</h2>
      <p>Vota y hagamos florecer a Hermosillo.</p>
    </div>
    <a class="ff-button ff-button-light ff-button-large" href="{{ config('flowerflow.voting.form_url') }}" target="_blank" rel="noopener noreferrer" data-voting-trigger>Votar</a>
  </div>
</section>
@endsection

@push('modals')
  @include('public.partials.voting-modal')
@endpush

@push('scripts')
  @vite('resources/js/pages/public-voting.js')
@endpush
