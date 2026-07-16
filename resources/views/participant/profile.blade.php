@extends('layouts.flowerflow')
@section('title', 'Mi perfil')
@section('content')
<p class="ff-kicker mb-1">Datos personales y preferencias</p>
<h1>Mi perfil</h1>
<p class="lead">Estos datos se capturaron al crear tu cuenta. Puedes mantenerlos actualizados y cambiar tus autorizaciones opcionales.</p>

<form method="POST" action="{{ route('profile.update') }}" class="card ff-card p-4 mt-4" data-validated-form>
    @csrf
    @method('PUT')
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
        <div class="col-md-6">
            <label class="form-label" for="email">Correo electrónico</label>
            <input class="form-control" id="email" value="{{ auth()->user()->email }}" readonly>
            <div class="form-text">{{ auth()->user()->hasVerifiedEmail() ? 'Correo verificado.' : 'Correo pendiente de verificación.' }}</div>
        </div>
        <div class="col-md-6">
            <x-phone-number-field :value="$profile?->mobile_e164" />
        </div>
        <div class="col-md-6">
            <label class="form-label" for="birth_date">Fecha de nacimiento</label>
            <input class="form-control @error('birth_date') is-invalid @enderror" id="birth_date" name="birth_date" type="date" max="{{ now()->subYears(18)->toDateString() }}" value="{{ old('birth_date', $profile?->birth_date?->toDateString()) }}" required autocomplete="bday">
            @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="neighborhood">Colonia de residencia</label>
            <input class="form-control @error('neighborhood') is-invalid @enderror" id="neighborhood" name="neighborhood" value="{{ old('neighborhood', $profile?->neighborhood) }}" required autocomplete="address-level3">
            @error('neighborhood')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <hr class="my-4">

    <div class="form-check mb-3">
        <input type="hidden" name="whatsapp_opt_in" value="0">
        <input class="form-check-input" id="whatsapp_opt_in" name="whatsapp_opt_in" type="checkbox" value="1" @checked(old('whatsapp_opt_in', $profile ? $profile->whatsapp_opt_in : true))>
        <label class="form-check-label" for="whatsapp_opt_in">Acepto recibir comunicaciones operativas por WhatsApp. Puedo retirar esta autorización en cualquier momento.</label>
    </div>
    <div class="form-check mb-3">
        <input type="hidden" name="future_activities_opt_in" value="0">
        <input class="form-check-input" id="future_activities_opt_in" name="future_activities_opt_in" type="checkbox" value="1" @checked(old('future_activities_opt_in', $futureActivitiesOptIn))>
        <label class="form-check-label" for="future_activities_opt_in">Acepto recibir información sobre futuras actividades de FLORECE HERMOSILLO y FLOWER FLOW.</label>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input @error('adult_declaration') is-invalid @enderror" id="adult_declaration" name="adult_declaration" type="checkbox" value="1" required @checked(old('adult_declaration', filled($profile?->adult_declared_at)))>
        <label class="form-check-label" for="adult_declaration">Confirmo que tengo 18 años o más.</label>
        @error('adult_declaration')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="form-check mb-4">
        <input class="form-check-input @error('resident_declaration') is-invalid @enderror" id="resident_declaration" name="resident_declaration" type="checkbox" value="1" required @checked(old('resident_declaration', filled($profile?->hermosillo_resident_declared_at)))>
        <label class="form-check-label" for="resident_declaration">Confirmo que resido en Hermosillo, Sonora, y podré comprobarlo.</label>
        @error('resident_declaration')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <button class="btn btn-flower align-self-start" type="submit">Guardar cambios de mi perfil</button>
</form>
@endsection
