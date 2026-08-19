<div class="card ff-card p-4">
  <h2 class="h4">Identificación y cálculo futuro</h2>
  <div class="row g-3">
    <div class="col-md-3">
      <label class="form-label" for="version">Versión</label>
      @if($rubric)
        <p class="form-control-plaintext" id="version">{{ $version }}</p>
      @else
        <input class="form-control @error('version') is-invalid @enderror" id="version" name="version" type="number" min="1" step="1" value="{{ old('version', $version) }}" required>
        @error('version')<div class="invalid-feedback">{{ $message }}</div>@enderror
      @endif
    </div>
    <div class="col-md-9">
      <label class="form-label" for="title">Título interno</label>
      <input class="form-control @error('title') is-invalid @enderror" id="title" name="title" maxlength="255" value="{{ old('title', $rubric?->title ?? \App\Services\EvaluationRubricContract::INITIAL_TITLE) }}" required autofocus>
      @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
  </div>

  <div class="row g-3 mt-1">
    @foreach([
      'criterion_score_min' => ['Mínimo por criterio', '0.5'],
      'criterion_score_max' => ['Máximo por criterio', '0.5'],
      'criterion_score_step' => ['Paso por criterio', '0.5'],
      'total_weight' => ['Peso total', '0.0001'],
      'total_score_min' => ['Total mínimo futuro', '0.0001'],
      'total_score_max' => ['Total máximo futuro', '0.0001'],
    ] as $field => [$label, $step])
      <div class="col-sm-6 col-lg-4">
        <label class="form-label" for="{{ $field }}">{{ $label }}</label>
        <input class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" type="number" step="{{ $step }}" value="{{ old($field, $versionAttributes[$field]) }}" required>
        @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
    @endforeach
  </div>

  <div class="row g-3 mt-1">
    @foreach([
      'internal_decimal_places' => 'Decimales internos',
      'display_decimal_places' => 'Decimales visibles',
      'general_comment_min_characters' => 'Comentario general mínimo',
      'general_comment_max_characters' => 'Comentario general máximo',
      'criterion_comment_max_characters' => 'Comentario por criterio máximo',
    ] as $field => $label)
      <div class="col-sm-6 col-lg-4">
        <label class="form-label" for="{{ $field }}">{{ $label }}</label>
        <input class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" type="number" min="0" step="1" value="{{ old($field, $versionAttributes[$field]) }}" required>
        @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
    @endforeach
    <div class="col-sm-6 col-lg-4">
      <label class="form-label" for="rounding_mode">Redondeo</label>
      <input class="form-control @error('rounding_mode') is-invalid @enderror" id="rounding_mode" name="rounding_mode" value="{{ old('rounding_mode', $versionAttributes['rounding_mode']) }}" maxlength="24" required>
      @error('rounding_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
  </div>
</div>

<div class="card ff-card p-4 mt-4">
  <h2 class="h4">Criterios exactos y ordenados</h2>
  <p class="text-body-secondary">No existe una descripción extensa aprobada. Cada descripción se conserva nula y se muestra como <strong>POR_CONFIRMAR</strong>.</p>
  @error('criteria')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror

  @foreach($criteria as $index => $criterion)
    <fieldset class="border rounded p-3 mb-3">
      <legend class="float-none w-auto px-2 h5">Criterio {{ $index + 1 }}</legend>
      <div class="row g-3">
        @foreach([
          'code' => ['Código estable', 'text', null],
          'label' => ['Etiqueta en español', 'text', null],
          'weight' => ['Peso porcentual', 'number', '0.0001'],
          'min_score' => ['Mínimo', 'number', '0.5'],
          'max_score' => ['Máximo', 'number', '0.5'],
          'score_step' => ['Paso', 'number', '0.5'],
          'sort_order' => ['Orden', 'number', '1'],
        ] as $field => [$label, $type, $step])
          <div class="col-sm-6 col-lg-3">
            <label class="form-label" for="criteria_{{ $index }}_{{ $field }}">{{ $label }}</label>
            <input class="form-control @error("criteria.$index.$field") is-invalid @enderror" id="criteria_{{ $index }}_{{ $field }}" name="criteria[{{ $index }}][{{ $field }}]" type="{{ $type }}" @if($step) step="{{ $step }}" @endif value="{{ old("criteria.$index.$field", $criterion[$field]) }}" required>
            @error("criteria.$index.$field")<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        @endforeach
        <div class="col-12">
          <span class="form-label d-block">Descripción</span>
          <p class="form-control-plaintext mb-0">POR_CONFIRMAR</p>
        </div>
      </div>
    </fieldset>
  @endforeach
</div>
