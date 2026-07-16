@extends('layouts.flowerflow')

@section('title', $submission->exists ? 'Editar propuesta' : 'Nueva propuesta')
@section('description', 'Crea y guarda tu propuesta para Hermosillo Florece 2026 paso a paso.')

@php
  $isEdit = $submission->exists;
  $currentStep = (int) old('wizard_step', $wizardStep ?? 1);
  $currentStep = in_array($currentStep, [1, 2, 3], true) ? $currentStep : 1;
  $links = $isEdit ? $submission->externalLinks->keyBy('kind') : collect();
  $members = $isEdit && $submission->team
      ? $submission->team->members->where('is_representative', false)->values()
      : collect();
  $oldMembers = old('team_members', $members->map(fn ($member) => [
      'full_name' => $member->full_name,
      'email' => $member->email,
  ])->all());
  $participationType = old('participation_type', $submission->participation_type ?: 'individual');
  $selectedCategory = old('category_public_id', $submission->category?->public_id);
  $titleMax = (int) config('flowerflow.limits.submission_title_characters');
  $summaryMax = (int) config('flowerflow.limits.submission_summary_characters');
  $descriptionMax = (int) config('flowerflow.limits.submission_description_text_characters');
  $quotaBytes = (int) config('flowerflow.limits.upload_kib') * 1024;
  $existingBytes = $isEdit ? (int) $submission->files->sum('size_bytes') : 0;
  $documentFiles = $isEdit ? $submission->files->where('kind', 'document') : collect();
  $imageFiles = $isEdit ? $submission->files->where('kind', 'editor_image') : collect();
  $documentExtensions = config('flowerflow.allowed_document_extensions');
  $imageExtensions = config('flowerflow.allowed_editor_image_extensions');
  $documentAccept = collect($documentExtensions)->map(fn ($extension) => '.'.$extension)->implode(',');
  $imageAccept = collect($imageExtensions)->map(fn ($extension) => '.'.$extension)->implode(',');
@endphp

@section('content')
<div class="ff-wizard-page">
  <header class="ff-wizard-heading">
    <p class="ff-ui-kicker">Convocatoria ciudadana</p>
    <h1>Nueva propuesta</h1>
    <p>Borrador editable · Convocatoria Ciudadana {{ $competition->name }}</p>
  </header>

  @include('partials.submission-wizard-stepper', ['currentStep' => $currentStep])

  <form
    method="POST"
    action="{{ $isEdit ? route('submissions.update', $submission) : route('submissions.store') }}"
    enctype="multipart/form-data"
    class="ff-wizard-form"
    data-wizard-form
    data-existing-bytes="{{ $existingBytes }}"
    data-quota-bytes="{{ $quotaBytes }}"
  >
    @csrf
    @if($isEdit) @method('PUT') @endif
    <input type="hidden" name="wizard_step" value="{{ $currentStep }}">

    <div class="ff-wizard-layout">
      <div class="ff-wizard-card ff-wizard-main-card">
        @if($currentStep === 1)
          <section aria-labelledby="wizard-step-one-title">
            <header class="ff-wizard-section-heading">
              <span class="ri ri-user-line" aria-hidden="true"></span>
              <div>
                <h2 id="wizard-step-one-title">1. Modalidad y categoría</h2>
                <p>Cuéntanos cómo participarás y selecciona la categoría que mejor corresponda a tu idea.</p>
              </div>
            </header>

            <fieldset class="ff-wizard-fieldset" @if($errors->has('participation_type')) aria-describedby="participation-type-error" @endif>
              <legend>Modalidad de participación <span aria-hidden="true">*</span></legend>
              <div class="ff-choice-grid ff-participation-options" data-team-choice>
                <label class="ff-choice-card">
                  <input
                    type="radio"
                    name="participation_type"
                    value="individual"
                    @checked($participationType === 'individual')
                    @if($errors->has('participation_type')) aria-invalid="true" @endif
                    required
                  >
                  <span class="ff-choice-icon ri ri-user-line" aria-hidden="true"></span>
                  <span><strong>Individual</strong><small>Participa por tu cuenta.</small></span>
                  <span class="ff-choice-indicator" aria-hidden="true"></span>
                </label>
                <label class="ff-choice-card">
                  <input
                    type="radio"
                    name="participation_type"
                    value="team"
                    @checked($participationType === 'team')
                    @if($errors->has('participation_type')) aria-invalid="true" @endif
                  >
                  <span class="ff-choice-icon ri ri-team-line" aria-hidden="true"></span>
                  <span><strong>Equipo</strong><small>Hasta cinco integrantes.</small></span>
                  <span class="ff-choice-indicator" aria-hidden="true"></span>
                </label>
              </div>
              @error('participation_type')<p class="ff-field-error" id="participation-type-error">{{ $message }}</p>@enderror
            </fieldset>

            <section class="ff-team-fields" id="team-fields" data-team-fields aria-labelledby="team-fields-title" aria-live="polite">
              <div class="ff-wizard-subsection-heading">
                <span class="ri ri-team-line" aria-hidden="true"></span>
                <div>
                  <h3 id="team-fields-title">Datos del equipo</h3>
                  <p>Tu cuenta es la representante y ya cuenta dentro del máximo de cinco personas.</p>
                </div>
              </div>
              <div class="ff-form-grid">
                <div class="ff-form-field ff-form-field-full">
                  <label for="team_name">Nombre del equipo <span aria-hidden="true">*</span></label>
                  <input
                    class="form-control @error('team_name') is-invalid @enderror"
                    id="team_name"
                    name="team_name"
                    value="{{ old('team_name', $submission->team?->name) }}"
                    maxlength="180"
                    @if($errors->has('team_name')) aria-invalid="true" aria-describedby="team-name-error" @endif
                  >
                  @error('team_name')<p class="ff-field-error" id="team-name-error">{{ $message }}</p>@enderror
                </div>
                @for($index = 0; $index < 4; $index++)
                  <div class="ff-form-field">
                    <label for="member_name_{{ $index }}">Integrante {{ $index + 2 }} · nombre</label>
                    <input
                      class="form-control @error('team_members.'.$index.'.full_name') is-invalid @enderror"
                      id="member_name_{{ $index }}"
                      name="team_members[{{ $index }}][full_name]"
                      value="{{ $oldMembers[$index]['full_name'] ?? '' }}"
                      maxlength="180"
                    >
                    @error('team_members.'.$index.'.full_name')<p class="ff-field-error">{{ $message }}</p>@enderror
                  </div>
                  <div class="ff-form-field">
                    <label for="member_email_{{ $index }}">Correo opcional</label>
                    <input
                      class="form-control @error('team_members.'.$index.'.email') is-invalid @enderror"
                      id="member_email_{{ $index }}"
                      name="team_members[{{ $index }}][email]"
                      type="email"
                      value="{{ $oldMembers[$index]['email'] ?? '' }}"
                      maxlength="255"
                      autocomplete="email"
                    >
                    @error('team_members.'.$index.'.email')<p class="ff-field-error">{{ $message }}</p>@enderror
                  </div>
                @endfor
              </div>
              <div class="form-check ff-team-declaration">
                <input
                  class="form-check-input @error('team_eligibility') is-invalid @enderror"
                  id="team_eligibility"
                  name="team_eligibility"
                  type="checkbox"
                  value="1"
                  @checked(old('team_eligibility', (bool) $submission->team?->eligibility_declared_at))
                  @if($errors->has('team_eligibility')) aria-invalid="true" aria-describedby="team-eligibility-error" @endif
                >
                <label class="form-check-label" for="team_eligibility">Declaro que todas las personas del equipo tienen 18 años o más y residencia comprobable en Hermosillo.</label>
                @error('team_eligibility')<p class="ff-field-error" id="team-eligibility-error">{{ $message }}</p>@enderror
              </div>
            </section>

            <fieldset class="ff-wizard-fieldset ff-category-fieldset" @if($errors->has('category_public_id')) aria-describedby="category-error" @endif>
              <legend>Categoría <span aria-hidden="true">*</span></legend>
              <div class="ff-category-grid">
                @foreach($competition->categories as $category)
                  @php($categoryIcon = config('flowerflow.category_icons.'.$category->slug, 'ri-lightbulb-flash-line'))
                  <label class="ff-choice-card ff-category-card">
                    <input
                      type="radio"
                      name="category_public_id"
                      value="{{ $category->public_id }}"
                      @checked($selectedCategory === $category->public_id)
                      @if($errors->has('category_public_id')) aria-invalid="true" @endif
                      required
                    >
                    <span class="ff-choice-icon ri {{ $categoryIcon }}" aria-hidden="true"></span>
                    <span><strong>{{ $category->name }}</strong><small>{{ $category->description }}</small></span>
                    <span class="ff-choice-indicator" aria-hidden="true"></span>
                  </label>
                @endforeach
              </div>
              @error('category_public_id')<p class="ff-field-error" id="category-error">{{ $message }}</p>@enderror
            </fieldset>

            <section class="ff-basic-information" aria-labelledby="basic-information-title">
              <div class="ff-wizard-subsection-heading">
                <span class="ri ri-draft-line" aria-hidden="true"></span>
                <h3 id="basic-information-title">Información básica del proyecto</h3>
              </div>
              <div class="ff-form-field">
                <label for="title">Título del proyecto <span aria-hidden="true">*</span></label>
                <input
                  class="form-control @error('title') is-invalid @enderror"
                  id="title"
                  name="title"
                  maxlength="{{ $titleMax }}"
                  value="{{ old('title', $submission->title) }}"
                  aria-describedby="title-help title-count @error('title') title-error @enderror"
                  @if($errors->has('title')) aria-invalid="true" @endif
                  required
                >
                <p class="ff-field-help" id="title-help">Usa un título claro, breve y representativo.</p>
                <p class="ff-character-count" id="title-count" data-character-counter data-for="title" data-max="{{ $titleMax }}" aria-live="polite">0 / {{ $titleMax }}</p>
                @error('title')<p class="ff-field-error" id="title-error">{{ $message }}</p>@enderror
              </div>
              <div class="ff-form-field">
                <label for="summary">Resumen breve <span aria-hidden="true">*</span></label>
                <textarea
                  class="form-control @error('summary') is-invalid @enderror"
                  id="summary"
                  name="summary"
                  maxlength="{{ $summaryMax }}"
                  rows="4"
                  aria-describedby="summary-help summary-count @error('summary') summary-error @enderror"
                  @if($errors->has('summary')) aria-invalid="true" @endif
                  required
                >{{ old('summary', $submission->summary) }}</textarea>
                <p class="ff-field-help" id="summary-help">Describe el problema, la solución y el beneficio principal para Hermosillo.</p>
                <p class="ff-character-count" id="summary-count" data-character-counter data-for="summary" data-max="{{ $summaryMax }}" aria-live="polite">0 / {{ $summaryMax }}</p>
                @error('summary')<p class="ff-field-error" id="summary-error">{{ $message }}</p>@enderror
              </div>
            </section>
          </section>
        @elseif($currentStep === 2)
          <section aria-labelledby="wizard-step-two-title">
            <header class="ff-wizard-section-heading">
              <span class="ri ri-file-edit-line" aria-hidden="true"></span>
              <div>
                <h2 id="wizard-step-two-title">2. Descripción del proyecto</h2>
                <p>Explica qué quieres lograr, cómo lo llevarás a cabo y por qué es importante para Hermosillo.</p>
              </div>
            </header>

            <div class="ff-project-context">
              <span class="ff-choice-icon ri {{ config('flowerflow.category_icons.'.$submission->category->slug, 'ri-lightbulb-flash-line') }}" aria-hidden="true"></span>
              <div>
                <strong>{{ $submission->title }}</strong>
                <p>
                  {{ $submission->participation_type === 'team' ? 'Proyecto en equipo' : 'Proyecto individual' }} ·
                  {{ $submission->category->name }}
                  @if($submission->team) · {{ $submission->team->name }} @endif
                </p>
              </div>
              <a href="{{ route('submissions.edit', ['submission' => $submission, 'step' => 1]) }}" data-wizard-navigation>
                <span class="ri ri-pencil-line" aria-hidden="true"></span> Editar datos iniciales
              </a>
            </div>

            <div class="ff-form-field ff-editor-field">
              <label for="description-editor">Descripción detallada, objetivos, impacto esperado y metodología <span aria-hidden="true">*</span></label>
              <p class="ff-field-help" id="description-help">Puedes guardar una versión incompleta; necesitarás contenido antes de continuar.</p>
              <div
                id="description-editor"
                data-flowerflow-editor
                data-placeholder="Describe el problema, tu solución, objetivos, metodología, participación y resultados esperados."
                data-max="{{ $descriptionMax }}"
                aria-label="Editor de descripción detallada"
                aria-describedby="description-help description-count @error('description_text') description-error @enderror"
              ></div>
              <input type="hidden" name="description_delta" value="{{ old('description_delta', $submission->description_delta ? json_encode($submission->description_delta) : '') }}">
              <input type="hidden" name="description_html" value="{{ old('description_html', $submission->description_html) }}">
              <input type="hidden" name="description_text" value="{{ old('description_text', $submission->description_text) }}">
              <p class="ff-character-count" id="description-count" data-editor-counter aria-live="polite">0 / {{ $descriptionMax }}</p>
              @error('description_text')<p class="ff-field-error" id="description-error">{{ $message }}</p>@enderror
              @error('description_html')<p class="ff-field-error">{{ $message }}</p>@enderror
              @error('description_delta')<p class="ff-field-error">{{ $message }}</p>@enderror
            </div>
          </section>
        @else
          <section aria-labelledby="wizard-step-three-title">
            <header class="ff-wizard-section-heading">
              <span class="ri ri-upload-cloud-2-line" aria-hidden="true"></span>
              <div>
                <h2 id="wizard-step-three-title">3. Archivos y enlaces</h2>
                <p>Agrega documentos, imágenes y enlaces que ayuden a comprender y respaldar tu proyecto.</p>
              </div>
            </header>

            <section class="ff-upload-group" aria-labelledby="documents-title">
              <div class="ff-upload-group-heading">
                <span class="ri ri-file-text-line" aria-hidden="true"></span>
                <div><h3 id="documents-title">Archivos del proyecto <small>(opcional)</small></h3><p>Opcionales para el borrador. Debes adjuntar al menos un documento antes del envío final.</p></div>
              </div>
              <div class="ff-file-picker" data-file-picker data-kind="document">
                <div class="ff-file-dropzone" data-file-dropzone>
                  <span class="ri ri-upload-2-line" aria-hidden="true"></span>
                  <strong>Arrastra y suelta tus archivos aquí</strong>
                  <span>o selecciónalos desde tu dispositivo</span>
                  <label class="ff-ui-button ff-ui-button-secondary" for="documents">Elegir archivos</label>
                  <input class="visually-hidden" id="documents" name="documents[]" type="file" multiple accept="{{ $documentAccept }}" data-file-input>
                </div>
                <ul class="ff-selected-files" data-file-list aria-live="polite" aria-label="Documentos seleccionados"></ul>
              </div>
              @error('documents')<p class="ff-field-error">{{ $message }}</p>@enderror
              @foreach($errors->get('documents.*') as $messages) @foreach($messages as $message)<p class="ff-field-error">{{ $message }}</p>@endforeach @endforeach
              @if($documentFiles->isNotEmpty())
                <h4>Documentos guardados</h4>
                <ul class="ff-existing-files">
                  @foreach($documentFiles as $file)
                    <li>
                      <span class="ri ri-file-text-line" aria-hidden="true"></span>
                      <span><strong>{{ $file->original_name }}</strong><small>{{ strtoupper($file->extension) }} · {{ number_format($file->size_bytes / 1024, 1) }} KiB</small></span>
                      <a href="{{ route('submissions.files.download', [$submission, $file]) }}"><span class="ri ri-download-line" aria-hidden="true"></span> Descargar</a>
                      <button type="submit" form="delete-file-{{ $file->public_id }}"><span class="ri ri-delete-bin-line" aria-hidden="true"></span> Eliminar</button>
                    </li>
                  @endforeach
                </ul>
              @endif
            </section>

            <section class="ff-upload-group" aria-labelledby="images-title">
              <div class="ff-upload-group-heading">
                <span class="ri ri-image-line" aria-hidden="true"></span>
                <div><h3 id="images-title">Imágenes de apoyo <small>(opcional)</small></h3><p>JPG, JPEG, PNG o WebP. Comparten la cuota total del proyecto.</p></div>
              </div>
              <div class="ff-file-picker" data-file-picker data-kind="image">
                <div class="ff-file-dropzone" data-file-dropzone>
                  <span class="ri ri-upload-2-line" aria-hidden="true"></span>
                  <strong>Arrastra y suelta tus imágenes aquí</strong>
                  <span>o selecciónalas desde tu dispositivo</span>
                  <label class="ff-ui-button ff-ui-button-secondary" for="editor_images">Elegir imágenes</label>
                  <input class="visually-hidden" id="editor_images" name="editor_images[]" type="file" multiple accept="{{ $imageAccept }}" data-file-input>
                </div>
                <ul class="ff-selected-files" data-file-list aria-live="polite" aria-label="Imágenes seleccionadas"></ul>
              </div>
              @foreach($errors->get('editor_images.*') as $messages) @foreach($messages as $message)<p class="ff-field-error">{{ $message }}</p>@endforeach @endforeach
              @if($imageFiles->isNotEmpty())
                <h4>Imágenes guardadas</h4>
                <ul class="ff-existing-files">
                  @foreach($imageFiles as $file)
                    <li>
                      <span class="ri ri-image-line" aria-hidden="true"></span>
                      <span><strong>{{ $file->original_name }}</strong><small>{{ strtoupper($file->extension) }} · {{ number_format($file->size_bytes / 1024, 1) }} KiB</small></span>
                      <a href="{{ route('submissions.files.download', [$submission, $file]) }}"><span class="ri ri-download-line" aria-hidden="true"></span> Descargar</a>
                      <button type="submit" form="delete-file-{{ $file->public_id }}"><span class="ri ri-delete-bin-line" aria-hidden="true"></span> Eliminar</button>
                    </li>
                  @endforeach
                </ul>
              @endif
            </section>

            <section class="ff-project-quota" aria-labelledby="project-quota-title">
              <div>
                <h3 id="project-quota-title">Total acumulado del proyecto</h3>
                <p data-quota-text>{{ number_format($existingBytes / 1048576, 2) }} de {{ number_format($quotaBytes / 1048576, 0) }} MiB</p>
              </div>
              <progress data-quota-progress max="{{ $quotaBytes }}" value="{{ $existingBytes }}">{{ $existingBytes }} de {{ $quotaBytes }} bytes</progress>
              <p class="ff-field-error" data-quota-error hidden>El total seleccionado supera la cuota permitida de 10 MiB.</p>
            </section>

            <section class="ff-link-group" aria-labelledby="youtube-title">
              <div class="ff-upload-group-heading">
                <span class="ri ri-youtube-line" aria-hidden="true"></span>
                <div><h3 id="youtube-title">Video de YouTube <small>(opcional)</small></h3><p>Comparte un enlace público HTTPS; la vista previa no se guarda como contenido.</p></div>
              </div>
              <label class="visually-hidden" for="youtube_url">URL del video de YouTube</label>
              <input
                class="form-control @error('youtube_url') is-invalid @enderror"
                id="youtube_url"
                name="youtube_url"
                type="url"
                value="{{ old('youtube_url', $links->get('youtube')?->url) }}"
                placeholder="https://youtu.be/..."
                data-youtube-url
                data-allowed-hosts="{{ implode(',', config('flowerflow.external_links.video_hosts')) }}"
                @if($errors->has('youtube_url')) aria-invalid="true" aria-describedby="youtube-error" @endif
              >
              @error('youtube_url')<p class="ff-field-error" id="youtube-error">{{ $message }}</p>@enderror
              <div class="ff-youtube-preview" data-youtube-preview aria-live="polite">
                <p>La vista previa aparecerá aquí cuando ingreses un enlace válido de YouTube.</p>
              </div>
            </section>

            <section class="ff-link-group" aria-labelledby="folder-title">
              <div class="ff-upload-group-heading">
                <span class="ri ri-folder-line" aria-hidden="true"></span>
                <div><h3 id="folder-title">Carpeta pública <small>(opcional)</small></h3><p>Verifica que cualquier persona con el enlace pueda consultar la carpeta.</p></div>
              </div>
              <label class="visually-hidden" for="public_folder_url">URL de la carpeta pública</label>
              <input
                class="form-control @error('public_folder_url') is-invalid @enderror"
                id="public_folder_url"
                name="public_folder_url"
                type="url"
                value="{{ old('public_folder_url', $links->get('public_folder')?->url) }}"
                placeholder="https://drive.google.com/..."
                @if($errors->has('public_folder_url')) aria-invalid="true" aria-describedby="folder-error" @endif
              >
              @error('public_folder_url')<p class="ff-field-error" id="folder-error">{{ $message }}</p>@enderror
              <p class="ff-link-warning"><span class="ri ri-shield-line" aria-hidden="true"></span> No incluyas comprobantes de residencia ni datos personales sensibles en carpetas públicas.</p>
            </section>
          </section>
        @endif
      </div>

      <aside class="ff-wizard-card ff-wizard-help-card" aria-label="Ayuda para este paso">
        @if($currentStep === 1)
          <div class="ff-wizard-help-heading"><span class="ri ri-lightbulb-flash-line" aria-hidden="true"></span><h2>Consejos para empezar</h2></div>
          <ul>
            <li>Elige la categoría que mejor se alinee con el objetivo principal.</li>
            <li>Usa un título breve, fácil de recordar y representativo.</li>
            <li>Resume con claridad qué problema resuelves y a quién beneficia.</li>
          </ul>
          <p class="ff-wizard-privacy-note"><span class="ri ri-shield-check-line" aria-hidden="true"></span> Tu borrador es privado y sólo será visible para el equipo autorizado.</p>
        @elseif($currentStep === 2)
          <div class="ff-wizard-help-heading"><span class="ri ri-lightbulb-flash-line" aria-hidden="true"></span><h2>Recomendaciones para escribir</h2></div>
          <ul>
            <li><strong>Sé claro y concreto.</strong> Evita tecnicismos innecesarios.</li>
            <li><strong>Enfócate en el impacto.</strong> Explica beneficios para la comunidad.</li>
            <li><strong>Incluye datos o ejemplos.</strong> Aporta referencias relevantes.</li>
            <li><strong>Describe la participación.</strong> Indica cómo se involucrará la ciudadanía.</li>
          </ul>
          <p class="ff-wizard-privacy-note"><span class="ri ri-shield-check-line" aria-hidden="true"></span> No incluyas datos personales sensibles ni información confidencial de terceros.</p>
        @else
          <div class="ff-wizard-help-heading"><span class="ri ri-file-shield-2-line" aria-hidden="true"></span><h2>Formato y tamaño permitidos</h2></div>
          <dl class="ff-format-list">
            <div><dt>Documentos</dt><dd>{{ collect($documentExtensions)->map(fn ($extension) => strtoupper($extension))->implode(', ') }}</dd></div>
            <div><dt>Imágenes</dt><dd>{{ collect($imageExtensions)->map(fn ($extension) => strtoupper($extension))->implode(', ') }}</dd></div>
            <div><dt>Cuota acumulada</dt><dd>{{ number_format($quotaBytes / 1048576, 0) }} MiB por proyecto</dd></div>
          </dl>
          <p><strong>Al menos un documento debe estar adjunto antes del envío final.</strong> No es obligatorio para guardar este borrador.</p>
          <p class="ff-wizard-privacy-note"><span class="ri ri-shield-check-line" aria-hidden="true"></span> Los archivos se almacenan de forma privada y sólo se entregan mediante acceso autorizado.</p>
        @endif
      </aside>
    </div>

    <footer class="ff-wizard-actions">
      <div class="ff-wizard-save-group">
        <button class="ff-ui-button ff-ui-button-secondary" type="submit" name="wizard_action" value="save">
          <span class="ri ri-save-line" aria-hidden="true"></span> Guardar borrador
        </button>
        <span class="ff-wizard-save-status" data-wizard-save-status aria-live="polite">{{ session('status') === 'Borrador guardado.' ? 'Borrador guardado' : 'Sin cambios pendientes' }}</span>
      </div>
      <div class="ff-wizard-navigation-actions">
        @if($currentStep > 1)
          <a class="ff-ui-button ff-ui-button-secondary" href="{{ route('submissions.edit', ['submission' => $submission, 'step' => $currentStep - 1]) }}" data-wizard-navigation>
            <span class="ri ri-arrow-left-line" aria-hidden="true"></span> Anterior
          </a>
        @endif
        <button class="ff-ui-button ff-ui-button-primary" type="submit" name="wizard_action" value="continue">
          Continuar <span class="ri ri-arrow-right-line" aria-hidden="true"></span>
        </button>
      </div>
    </footer>
  </form>
</div>

@if($isEdit)
  @foreach($submission->files as $file)
    <form
      id="delete-file-{{ $file->public_id }}"
      method="POST"
      action="{{ route('submissions.files.destroy', [$submission, $file]) }}"
      data-confirm="¿Deseas eliminar {{ $file->original_name }}? Esta acción no se puede deshacer."
    >
      @csrf
      @method('DELETE')
    </form>
  @endforeach
@endif
@endsection
