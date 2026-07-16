@extends('layouts.flowerflow')
@section('title', 'Crear cuenta')
@section('content')
<section class="ff-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div class="card ff-card p-3 p-md-4">
                    <div class="card-body">
                        <p class="ff-kicker">Participantes</p>
                        <h1 class="h2">Crea tu cuenta para participar</h1>
                        <p class="text-body-secondary">Captura tus datos personales desde el inicio. Después sólo necesitarás verificar tu correo para comenzar una propuesta.</p>

                        <form method="POST" action="{{ route('register') }}" class="mt-4" data-validated-form>
                            @csrf
                            <fieldset>
                                <legend class="h4 mb-3">Datos de la persona participante</legend>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="first_names">Nombre(s)</label>
                                        <input class="form-control @error('first_names') is-invalid @enderror" id="first_names" name="first_names" value="{{ old('first_names') }}" required maxlength="120" autocomplete="given-name" @error('first_names') aria-invalid="true" @enderror>
                                        @error('first_names')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="last_names">Apellidos</label>
                                        <input class="form-control @error('last_names') is-invalid @enderror" id="last_names" name="last_names" value="{{ old('last_names') }}" required maxlength="120" autocomplete="family-name" @error('last_names') aria-invalid="true" @enderror>
                                        @error('last_names')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="email">Correo electrónico</label>
                                        <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" @error('email') aria-invalid="true" @enderror>
                                        <div class="form-text">Te enviaremos un enlace para verificar que el correo te pertenece.</div>
                                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <x-phone-number-field />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="birth_date">Fecha de nacimiento</label>
                                        <input class="form-control @error('birth_date') is-invalid @enderror" id="birth_date" name="birth_date" type="date" max="{{ now()->subYears(18)->toDateString() }}" value="{{ old('birth_date') }}" required autocomplete="bday" @error('birth_date') aria-invalid="true" @enderror>
                                        <div class="form-text">La convocatoria es únicamente para personas de 18 años o más.</div>
                                        @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="neighborhood">Colonia de residencia</label>
                                        <input class="form-control @error('neighborhood') is-invalid @enderror" id="neighborhood" name="neighborhood" value="{{ old('neighborhood') }}" required maxlength="180" autocomplete="address-level3" @error('neighborhood') aria-invalid="true" @enderror>
                                        @error('neighborhood')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </fieldset>

                            <hr class="my-4">

                            <fieldset>
                                <legend class="h4 mb-3">Contraseña de acceso</legend>
                                <x-password-fields />
                            </fieldset>

                            <hr class="my-4">

                            <fieldset>
                                <legend class="h4 mb-3">Declaraciones y preferencias</legend>
                                <div class="form-check mb-3">
                                    <input type="hidden" name="whatsapp_opt_in" value="0">
                                    <input class="form-check-input" id="whatsapp_opt_in" name="whatsapp_opt_in" type="checkbox" value="1" @checked(old('whatsapp_opt_in', true))>
                                    <label class="form-check-label" for="whatsapp_opt_in">Acepto recibir comunicaciones operativas relacionadas con mi participación por WhatsApp en el celular indicado. Puedo retirar esta autorización desde mi perfil.</label>
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input @error('resident_declaration') is-invalid @enderror" id="resident_declaration" name="resident_declaration" type="checkbox" value="1" required @checked(old('resident_declaration')) @error('resident_declaration') aria-invalid="true" @enderror>
                                    <label class="form-check-label" for="resident_declaration">Declaro que resido en Hermosillo, Sonora, y que podré comprobarlo cuando se me solicite.</label>
                                    @error('resident_declaration')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input @error('accept_legal') is-invalid @enderror" id="accept_legal" name="accept_legal" type="checkbox" value="1" required @checked(old('accept_legal')) @error('accept_legal') aria-invalid="true" @enderror>
                                    <label class="form-check-label" for="accept_legal">
                                        Declaro que soy mayor de 18 años, y que he leído y acepto los
                                        <a href="{{ asset('documentos/2026/02_Terminos_y_Condiciones_Plataforma_Flower_Flow_2026.pdf') }}" download>Términos y Condiciones</a>
                                        y el
                                        <a href="{{ asset('documentos/2026/03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026.pdf') }}" download>Aviso de Privacidad</a>.
                                    </label>
                                    @error('accept_legal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="form-check mb-4">
                                    <input type="hidden" name="future_activities_opt_in" value="0">
                                    <input class="form-check-input" id="future_activities_opt_in" name="future_activities_opt_in" type="checkbox" value="1" @checked(old('future_activities_opt_in', true))>
                                    <label class="form-check-label" for="future_activities_opt_in">Acepto recibir información sobre futuras actividades de FLORECE HERMOSILLO y FLOWER FLOW.</label>
                                    <div class="form-text">Esta autorización es opcional y puedes cambiarla posteriormente desde tu perfil.</div>
                                </div>
                            </fieldset>

                            <button class="btn btn-flower btn-lg w-100" type="submit">Crear cuenta y enviar verificación</button>
                            <p class="text-center mt-3 mb-0">¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a></p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
