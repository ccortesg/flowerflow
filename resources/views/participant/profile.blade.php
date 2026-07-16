@extends('layouts.flowerflow')

@section('title', 'Mi perfil')
@section('description', 'Consulta y actualiza los datos de tu cuenta participante.')

@php
  $isProfileComplete = $profile?->isComplete() ?? false;
  $birthDateLabel = $profile?->birth_date?->format('d/m/Y') ?? 'Pendiente de captura';
  $phoneNational = \App\Support\MexicoPhoneNumber::formatNational($profile?->mobile_e164);
  $phoneLabel = $phoneNational ? '+52 '.$phoneNational : 'Pendiente de captura';
@endphp

@section('content')
<header class="ff-participant-page-heading">
  <div>
    <p class="ff-ui-kicker">Cuenta participante</p>
    <h1>Mi perfil</h1>
    <p>Mantén actualizados tus datos y revisa las declaraciones necesarias para participar.</p>
  </div>
</header>

<section @class(['ff-profile-completion', 'is-complete' => $isProfileComplete]) aria-labelledby="profile-completion-title">
  <span class="ff-profile-completion-icon ri {{ $isProfileComplete ? 'ri-checkbox-circle-line' : 'ri-information-line' }}" aria-hidden="true"></span>
  <div>
    <h2 id="profile-completion-title">{{ $isProfileComplete ? '¡Tu perfil está completo!' : 'Tu perfil necesita atención' }}</h2>
    <p>{{ $isProfileComplete ? 'Perfil completado: 100%. Ya cuentas con la información obligatoria para participar.' : 'Revisa y completa la información obligatoria marcada en las secciones de tu perfil.' }}</p>
    @if($isProfileComplete)
      <div class="ff-profile-progress" role="progressbar" aria-label="Perfil completado" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"><span></span></div>
    @endif
  </div>
</section>

<form method="POST" action="{{ route('profile.update') }}" @class(['ff-profile-form', 'is-editing' => $errors->any()]) data-validated-form data-profile-form>
  @csrf
  @method('PUT')

  <div class="ff-profile-card-grid">
    <section class="ff-profile-card" aria-labelledby="profile-personal-title">
      <header class="ff-profile-card-header">
        <div class="ff-profile-card-title">
          <span class="ri ri-user-3-line" aria-hidden="true"></span>
          <div><p>Identidad</p><h2 id="profile-personal-title">Información personal</h2></div>
        </div>
        <button class="ff-profile-edit-trigger" type="button" data-profile-edit data-profile-focus="first_names">
          <span class="ri ri-pencil-line" aria-hidden="true"></span> Editar
        </button>
      </header>

      <dl class="ff-profile-summary">
        <div><dt>Nombre(s)</dt><dd>{{ $profile?->first_names ?: 'Pendiente de captura' }}</dd></div>
        <div><dt>Apellidos</dt><dd>{{ $profile?->last_names ?: 'Pendiente de captura' }}</dd></div>
        <div><dt>Fecha de nacimiento</dt><dd>{{ $birthDateLabel }}</dd></div>
      </dl>

      <div class="ff-profile-edit-fields">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="first_names">Nombre(s)</label>
            <input class="form-control @error('first_names') is-invalid @enderror" id="first_names" name="first_names" value="{{ old('first_names', $profile?->first_names) }}" required autocomplete="given-name">
            @error('first_names')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label" for="last_names">Apellidos</label>
            <input class="form-control @error('last_names') is-invalid @enderror" id="last_names" name="last_names" value="{{ old('last_names', $profile?->last_names) }}" required autocomplete="family-name">
            @error('last_names')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-12">
            <label class="form-label" for="birth_date">Fecha de nacimiento</label>
            <input class="form-control @error('birth_date') is-invalid @enderror" id="birth_date" name="birth_date" type="date" max="{{ now()->subYears(18)->toDateString() }}" value="{{ old('birth_date', $profile?->birth_date?->toDateString()) }}" required autocomplete="bday">
            @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>
    </section>

    <section class="ff-profile-card" aria-labelledby="profile-contact-title">
      <header class="ff-profile-card-header">
        <div class="ff-profile-card-title">
          <span class="ri ri-contacts-book-3-line" aria-hidden="true"></span>
          <div><p>Comunicación</p><h2 id="profile-contact-title">Datos de contacto</h2></div>
        </div>
        <button class="ff-profile-edit-trigger" type="button" data-profile-edit data-profile-focus="mobile_national">
          <span class="ri ri-pencil-line" aria-hidden="true"></span> Editar
        </button>
      </header>

      <dl class="ff-profile-summary">
        <div>
          <dt>Correo electrónico</dt>
          <dd>{{ auth()->user()->email }}</dd>
          <dd class="ff-profile-meta {{ auth()->user()->hasVerifiedEmail() ? 'is-positive' : 'is-pending' }}">
            <span class="ri {{ auth()->user()->hasVerifiedEmail() ? 'ri-checkbox-circle-line' : 'ri-time-line' }}" aria-hidden="true"></span>
            {{ auth()->user()->hasVerifiedEmail() ? 'Correo verificado' : 'Correo pendiente de verificación' }}
          </dd>
        </div>
        <div><dt>Número de celular</dt><dd>{{ $phoneLabel }}</dd><dd class="ff-profile-meta">Número registrado</dd></div>
      </dl>

      <div class="ff-profile-edit-fields">
        <div class="mb-3">
          <label class="form-label" for="email">Correo electrónico</label>
          <input class="form-control" id="email" value="{{ auth()->user()->email }}" readonly>
          <div class="form-text">{{ auth()->user()->hasVerifiedEmail() ? 'Correo verificado.' : 'Correo pendiente de verificación.' }}</div>
        </div>
        <x-phone-number-field :value="$profile?->mobile_e164" />
      </div>
    </section>

    <section class="ff-profile-card" aria-labelledby="profile-residence-title">
      <header class="ff-profile-card-header">
        <div class="ff-profile-card-title">
          <span class="ri ri-map-pin-line" aria-hidden="true"></span>
          <div><p>Ubicación</p><h2 id="profile-residence-title">Datos de residencia</h2></div>
        </div>
        <button class="ff-profile-edit-trigger" type="button" data-profile-edit data-profile-focus="neighborhood">
          <span class="ri ri-pencil-line" aria-hidden="true"></span> Editar
        </button>
      </header>

      <dl class="ff-profile-summary">
        <div><dt>Colonia</dt><dd>{{ $profile?->neighborhood ?: 'Pendiente de captura' }}</dd></div>
        <div><dt>Municipio</dt><dd>Hermosillo</dd></div>
        <div><dt>Estado</dt><dd>Sonora</dd></div>
      </dl>

      <div class="ff-profile-edit-fields">
        <label class="form-label" for="neighborhood">Colonia de residencia</label>
        <input class="form-control @error('neighborhood') is-invalid @enderror" id="neighborhood" name="neighborhood" value="{{ old('neighborhood', $profile?->neighborhood) }}" required autocomplete="address-level3">
        @error('neighborhood')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <p class="ff-profile-static-location"><span class="ri ri-map-pin-2-line" aria-hidden="true"></span> Hermosillo, Sonora</p>
      </div>
    </section>

    <section class="ff-profile-card" aria-labelledby="profile-preferences-title">
      <header class="ff-profile-card-header">
        <div class="ff-profile-card-title">
          <span class="ri ri-checkbox-multiple-line" aria-hidden="true"></span>
          <div><p>Autorizaciones</p><h2 id="profile-preferences-title">Preferencias y declaraciones</h2></div>
        </div>
        <button class="ff-profile-edit-trigger" type="button" data-profile-edit data-profile-focus="whatsapp_opt_in">
          <span class="ri ri-pencil-line" aria-hidden="true"></span> Editar
        </button>
      </header>

      <div class="ff-profile-summary ff-profile-consent-summary">
        <div><span class="ri {{ $profile?->whatsapp_opt_in ? 'ri-checkbox-circle-line is-positive' : 'ri-close-circle-line' }}" aria-hidden="true"></span><p><strong>Comunicaciones por WhatsApp</strong><small>{{ $profile?->whatsapp_opt_in ? 'Autorizadas' : 'No autorizadas' }}</small></p></div>
        <div><span class="ri {{ $futureActivitiesOptIn ? 'ri-checkbox-circle-line is-positive' : 'ri-close-circle-line' }}" aria-hidden="true"></span><p><strong>Información de futuras actividades</strong><small>{{ $futureActivitiesOptIn ? 'Autorizada' : 'No autorizada' }}</small></p></div>
        <div><span class="ri {{ filled($profile?->adult_declared_at) ? 'ri-checkbox-circle-line is-positive' : 'ri-time-line is-pending' }}" aria-hidden="true"></span><p><strong>Mayoría de edad</strong><small>{{ filled($profile?->adult_declared_at) ? 'Declaración registrada' : 'Declaración pendiente' }}</small></p></div>
        <div><span class="ri {{ filled($profile?->hermosillo_resident_declared_at) ? 'ri-checkbox-circle-line is-positive' : 'ri-time-line is-pending' }}" aria-hidden="true"></span><p><strong>Residencia en Hermosillo</strong><small>{{ filled($profile?->hermosillo_resident_declared_at) ? 'Declaración registrada' : 'Declaración pendiente' }}</small></p></div>
      </div>

      <div class="ff-profile-edit-fields">
        <fieldset class="ff-profile-fieldset">
          <legend>Consentimientos opcionales</legend>
          <div class="form-check">
            <input type="hidden" name="whatsapp_opt_in" value="0">
            <input class="form-check-input" id="whatsapp_opt_in" name="whatsapp_opt_in" type="checkbox" value="1" @checked(old('whatsapp_opt_in', $profile ? $profile->whatsapp_opt_in : true))>
            <label class="form-check-label" for="whatsapp_opt_in">Acepto recibir comunicaciones operativas por WhatsApp. Puedo retirar esta autorización en cualquier momento.</label>
          </div>
          <div class="form-check">
            <input type="hidden" name="future_activities_opt_in" value="0">
            <input class="form-check-input" id="future_activities_opt_in" name="future_activities_opt_in" type="checkbox" value="1" @checked(old('future_activities_opt_in', $futureActivitiesOptIn))>
            <label class="form-check-label" for="future_activities_opt_in">Acepto recibir información sobre futuras actividades de FLORECE HERMOSILLO y FLOWER FLOW.</label>
          </div>
        </fieldset>

        <fieldset class="ff-profile-fieldset ff-profile-fieldset-required">
          <legend>Declaraciones obligatorias de elegibilidad</legend>
          <div class="form-check">
            <input class="form-check-input @error('adult_declaration') is-invalid @enderror" id="adult_declaration" name="adult_declaration" type="checkbox" value="1" required @checked(old('adult_declaration', filled($profile?->adult_declared_at)))>
            <label class="form-check-label" for="adult_declaration">Confirmo que tengo 18 años o más.</label>
            @error('adult_declaration')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-check">
            <input class="form-check-input @error('resident_declaration') is-invalid @enderror" id="resident_declaration" name="resident_declaration" type="checkbox" value="1" required @checked(old('resident_declaration', filled($profile?->hermosillo_resident_declared_at)))>
            <label class="form-check-label" for="resident_declaration">Confirmo que resido en Hermosillo, Sonora, y podré comprobarlo.</label>
            @error('resident_declaration')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </fieldset>
      </div>
    </section>
  </div>

  <div class="ff-profile-form-actions">
    <button class="ff-ui-button ff-ui-button-primary" type="submit">
      <span class="ri ri-save-3-line" aria-hidden="true"></span> Guardar cambios
    </button>
    <button class="ff-ui-button ff-ui-button-secondary ff-profile-cancel" type="button" data-profile-cancel>Cancelar</button>
  </div>
</form>

<aside class="ff-profile-privacy" aria-label="Privacidad de tus datos">
  <span class="ri ri-shield-user-line" aria-hidden="true"></span>
  <div>
    <h2>Tu información personal está protegida</h2>
    <p>Usamos tus datos para gestionar tu participación conforme al aviso vigente. <a href="{{ asset('documentos/2026/03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026.pdf') }}">Consulta el aviso de privacidad.</a></p>
  </div>
</aside>
@endsection
