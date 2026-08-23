@extends('layouts.flowerflow')
@section('title', 'Confirmar envío de propuesta')
@section('description', 'Confirma de forma segura el envío de tu propuesta pendiente.')
@section('content')
<div class="container py-5" style="max-width:760px;">
  <p class="ff-kicker mb-1">Hermosillo Florece 2026</p>
  <h1>Confirmar envío de propuesta</h1>
  <div class="card ff-card mt-4">
    <div class="card-body p-4">
      <h2 class="h4">{{ $submission->title }}</h2>
      <p>{{ $submission->summary }}</p>
      <p><strong>Fecha límite:</strong> {{ $submission->competition->closes_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s') }} (Hermosillo)</p>
      <div class="alert alert-info" role="note">El envío puede completarse sin archivo adjunto, pero requiere título, resumen, descripción y las tres aceptaciones siguientes.</div>

      @if(! $submission->hasMinimumFinalizationContent())
        <div class="alert alert-warning" role="alert">La propuesta todavía no contiene título, resumen y descripción completos. Inicia sesión para corregirla antes de enviar.</div>
        <a class="btn btn-flower" href="{{ route('login') }}">Iniciar sesión y completar</a>
      @else
        <form method="POST" action="{{ $submitUrl }}" novalidate>
          @csrf
          <div class="form-check mb-3">
            <input class="form-check-input @error('accept_call_rules') is-invalid @enderror" id="accept_call_rules" name="accept_call_rules" type="checkbox" value="1" required @checked(old('accept_call_rules'))>
            <label class="form-check-label" for="accept_call_rules">He leído y acepto la <a href="{{ asset(config('flowerflow.legal_documents.mechanics.path')) }}" target="_blank" rel="noopener noreferrer">Mecánica de la Convocatoria versión {{ config('flowerflow.legal_documents.mechanics.version') }}</a>.</label>
            @error('accept_call_rules')<p class="invalid-feedback">{{ $message }}</p>@enderror
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input @error('accept_terms') is-invalid @enderror" id="accept_terms" name="accept_terms" type="checkbox" value="1" required @checked(old('accept_terms'))>
            <label class="form-check-label" for="accept_terms">He leído y acepto los <a href="{{ asset(config('flowerflow.legal_documents.terms.path')) }}" target="_blank" rel="noopener noreferrer">Términos y Condiciones versión {{ config('flowerflow.legal_documents.terms.version') }}</a>.</label>
            @error('accept_terms')<p class="invalid-feedback">{{ $message }}</p>@enderror
          </div>
          <div class="form-check mb-4">
            <input class="form-check-input @error('accept_privacy') is-invalid @enderror" id="accept_privacy" name="accept_privacy" type="checkbox" value="1" required @checked(old('accept_privacy'))>
            <label class="form-check-label" for="accept_privacy">He leído y acepto el <a href="{{ asset(config('flowerflow.legal_documents.privacy.path')) }}" target="_blank" rel="noopener noreferrer">Aviso de Privacidad Integral versión {{ config('flowerflow.legal_documents.privacy.version') }}</a>.</label>
            @error('accept_privacy')<p class="invalid-feedback">{{ $message }}</p>@enderror
          </div>
          <button class="btn btn-flower" type="submit">Enviar propuesta <i class="ri-send-plane-line ms-1" aria-hidden="true"></i></button>
        </form>
      @endif
    </div>
  </div>
</div>
@endsection
