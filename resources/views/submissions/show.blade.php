@extends('layouts.flowerflow')

@section('title', $submission->isDraft() ? 'Revisión de propuesta' : $submission->title)

@php
  $isReview = $submission->isDraft() && config('flowerflow.flags.submissions');
  $links = $submission->externalLinks->keyBy('kind');
@endphp

@section('content')
@if($isReview)
  <div class="ff-wizard-page ff-submission-review-page">
    <header class="ff-wizard-heading">
      <p class="ff-ui-kicker">Convocatoria ciudadana</p>
      <h1>Nueva propuesta</h1>
      <p>Borrador editable · Convocatoria Ciudadana {{ $submission->competition->name }}</p>
    </header>

    @include('partials.submission-wizard-stepper', ['currentStep' => 4])

    <div class="ff-review-layout">
      <article class="ff-wizard-card ff-review-main-card">
        <header class="ff-wizard-section-heading">
          <span class="ri ri-checkbox-circle-line" aria-hidden="true"></span>
          <div>
            <h2>4. Revisión y envío</h2>
            <p>Confirma que la información esté completa. El envío final generará tu folio y una versión inmutable.</p>
          </div>
        </header>

        <section class="ff-review-section" aria-labelledby="review-basic-title">
          <div><h3 id="review-basic-title">Datos iniciales</h3><a href="{{ route('submissions.edit', ['submission' => $submission, 'step' => 1]) }}"><span class="ri ri-pencil-line" aria-hidden="true"></span> Editar</a></div>
          <dl>
            <div><dt>Título</dt><dd>{{ $submission->title }}</dd></div>
            <div><dt>Categoría</dt><dd>{{ $submission->category->name }}</dd></div>
            <div><dt>Modalidad</dt><dd>{{ $submission->participation_type === 'team' ? 'Equipo' : 'Individual' }}</dd></div>
            @if($submission->team)<div><dt>Equipo</dt><dd>{{ $submission->team->name }} · {{ $submission->team->members->count() }} integrantes</dd></div>@endif
            <div class="ff-review-wide"><dt>Resumen</dt><dd>{{ $submission->summary }}</dd></div>
          </dl>
        </section>

        <section class="ff-review-section" aria-labelledby="review-description-title">
          <div><h3 id="review-description-title">Descripción del proyecto</h3><a href="{{ route('submissions.edit', ['submission' => $submission, 'step' => 2]) }}"><span class="ri ri-pencil-line" aria-hidden="true"></span> Editar</a></div>
          @if(filled($submission->description_text))
            <div class="prose">{!! $safeHtml !!}</div>
          @else
            <p class="ff-review-missing"><span class="ri ri-error-warning-line" aria-hidden="true"></span> Falta completar la descripción antes de enviar.</p>
          @endif
        </section>

        <section class="ff-review-section" aria-labelledby="review-files-title">
          <div><h3 id="review-files-title">Archivos y enlaces</h3><a href="{{ route('submissions.edit', ['submission' => $submission, 'step' => 3]) }}"><span class="ri ri-pencil-line" aria-hidden="true"></span> Editar</a></div>
          @if($submission->files->where('kind', 'document')->isEmpty())
            <p class="ff-review-missing"><span class="ri ri-error-warning-line" aria-hidden="true"></span> Falta adjuntar al menos un documento antes de enviar.</p>
          @else
            <ul class="ff-review-file-list">
              @foreach($submission->files as $file)
                <li><span class="ri {{ $file->kind === 'editor_image' ? 'ri-image-line' : 'ri-file-text-line' }}" aria-hidden="true"></span><a href="{{ route('submissions.files.download', [$submission, $file]) }}">{{ $file->original_name }}</a><small>{{ number_format($file->size_bytes / 1024, 1) }} KiB</small></li>
              @endforeach
            </ul>
          @endif
          @if($links->isNotEmpty())
            <ul class="ff-review-link-list">
              @if($links->has('youtube'))<li><strong>Video de YouTube:</strong> <a href="{{ $links->get('youtube')->url }}" target="_blank" rel="noopener noreferrer">Abrir enlace</a></li>@endif
              @if($links->has('public_folder'))<li><strong>Carpeta pública:</strong> <a href="{{ $links->get('public_folder')->url }}" target="_blank" rel="noopener noreferrer">Abrir enlace</a></li>@endif
            </ul>
          @endif
        </section>
      </article>

      <aside class="ff-wizard-card ff-review-help-card">
        <div class="ff-wizard-help-heading"><span class="ri ri-shield-check-line" aria-hidden="true"></span><h2>Antes de enviar</h2></div>
        <ul>
          <li>Revisa que el título, resumen y descripción sean claros.</li>
          <li>Confirma que adjuntaste al menos un documento permitido.</li>
          <li>Verifica que los enlaces públicos abran sin solicitar acceso.</li>
          <li>No incluyas datos personales sensibles de terceros.</li>
        </ul>
        <p>Después del envío ya no podrás editar esta versión.</p>
      </aside>
    </div>

    <form method="POST" action="{{ route('submissions.submit', $submission) }}" class="ff-wizard-card ff-review-submit" data-confirm="Al enviar se congelará una versión y ya no podrás editarla. ¿Deseas continuar?">
      @csrf
      <h2>Aceptaciones para el envío final</h2>
      <p>Lee y confirma cada documento vigente. Estas aceptaciones se registran por separado.</p>
      <div class="form-check">
        <input class="form-check-input @error('accept_call_rules') is-invalid @enderror" id="accept_call_rules" name="accept_call_rules" type="checkbox" value="1" @checked(old('accept_call_rules')) required>
        <label class="form-check-label" for="accept_call_rules">He leído y acepto la <a href="{{ asset('documentos/2026/01_Mecanica_Convocatoria_Hermosillo_Florece_2026.pdf') }}" target="_blank" rel="noopener noreferrer">Mecánica de la Convocatoria</a>.</label>
        @error('accept_call_rules')<p class="ff-field-error">{{ $message }}</p>@enderror
      </div>
      <div class="form-check">
        <input class="form-check-input @error('accept_terms') is-invalid @enderror" id="accept_terms" name="accept_terms" type="checkbox" value="1" @checked(old('accept_terms')) required>
        <label class="form-check-label" for="accept_terms">He leído y acepto los <a href="{{ asset('documentos/2026/02_Terminos_y_Condiciones_Plataforma_Flower_Flow_2026.pdf') }}" target="_blank" rel="noopener noreferrer">Términos y Condiciones</a>.</label>
        @error('accept_terms')<p class="ff-field-error">{{ $message }}</p>@enderror
      </div>
      <div class="form-check">
        <input class="form-check-input @error('accept_privacy') is-invalid @enderror" id="accept_privacy" name="accept_privacy" type="checkbox" value="1" @checked(old('accept_privacy')) required>
        <label class="form-check-label" for="accept_privacy">He leído y acepto el <a href="{{ asset('documentos/2026/03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026.pdf') }}" target="_blank" rel="noopener noreferrer">Aviso de Privacidad Integral</a>.</label>
        @error('accept_privacy')<p class="ff-field-error">{{ $message }}</p>@enderror
      </div>
      <div class="ff-review-submit-actions">
        <a class="ff-ui-button ff-ui-button-secondary" href="{{ route('submissions.edit', ['submission' => $submission, 'step' => 3]) }}"><span class="ri ri-arrow-left-line" aria-hidden="true"></span> Volver a editar</a>
        <button class="ff-ui-button ff-ui-button-primary" type="submit">Enviar propuesta <span class="ri ri-send-plane-line" aria-hidden="true"></span></button>
      </div>
    </form>
  </div>
@else
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
      <p class="ff-kicker mb-1">{{ $submission->category->name }}</p>
      <h1>{{ $submission->title }}</h1>
      <p><span class="badge badge-submitted">{{ $submission->statusLabel() }}</span>@if($submission->folio) <strong class="ms-2">Folio {{ $submission->folio }}</strong>@endif</p>
    </div>
  </div>
  <div class="row g-4">
    <div class="col-lg-8"><article class="card ff-card p-4"><h2 class="h4">Resumen</h2><p>{{ $submission->summary }}</p><h2 class="h4 mt-3">Descripción</h2><div class="prose">{!! $safeHtml !!}</div></article></div>
    <div class="col-lg-4"><aside class="card ff-card p-4"><h2 class="h4">Detalles</h2><dl><dt>Modalidad</dt><dd>{{ $submission->participation_type === 'team' ? 'Equipo' : 'Individual' }}</dd><dt>Archivos</dt><dd>{{ $submission->files->count() }}</dd>@if($submission->submitted_at)<dt>Enviada</dt><dd>{{ $submission->submitted_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }} (Hermosillo)</dd>@endif</dl><ul class="list-unstyled">@foreach($submission->files as $file)<li class="mb-2"><a href="{{ route('submissions.files.download', [$submission, $file]) }}">{{ $file->original_name }}</a></li>@endforeach</ul></aside></div>
  </div>
  @if($submission->status === 'submitted')
    @if(config('flowerflow.flags.admissibility_review'))
      @include('submissions.partials.admissibility-review', ['review' => $submission->eligibilityReview])
    @endif
    <div class="card ff-card p-4 mt-4">
      <h2 class="h4">Correo de confirmación</h2>
      <p class="mb-3">Tu folio en pantalla confirma el registro aunque el correo tarde. Si no recibiste el mensaje, puedes programarlo nuevamente.</p>
      <form method="POST" action="{{ route('submissions.confirmation.resend', $submission) }}">@csrf<button class="btn btn-outline-dark">Reenviar correo de confirmación</button></form>
    </div>
  @endif
@endif
@endsection
