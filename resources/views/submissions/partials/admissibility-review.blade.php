<section class="card ff-card ff-admissibility-card p-4 mt-4" aria-labelledby="participant-review-title">
  <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
      <p class="ff-kicker mb-1">Seguimiento administrativo</p>
      <h2 class="h4 mb-2" id="participant-review-title">Revisión de participación</h2>
      <p class="mb-0">Aquí puedes atender solicitudes sin modificar la propuesta que ya enviaste.</p>
    </div>
    @if($review)
      <span class="ff-review-status ff-review-status-{{ $review->status->value }}">{{ $review->status->label() }}</span>
    @else
      <span class="ff-review-status">Pendiente de integrar</span>
    @endif
  </div>

  @if(!$review)
    <div class="alert alert-info mt-4 mb-0" role="status">Tu propuesta está registrada. El expediente de revisión todavía no ha sido integrado.</div>
  @else
    @if($review->status->isFinal())
      <div class="ff-review-resolution mt-4">
        <h3 class="h5">Resolución</h3>
        <p>{{ $review->participant_reason }}</p>
        <p class="small mb-0">Registrada el {{ $review->resolved_at?->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }} (Hermosillo).</p>
        @if($review->status === \App\Enums\EligibilityReviewStatus::Admitted)
          <p class="small mt-2 mb-0"><strong>Importante:</strong> admitida significa que puede avanzar a una fase futura de evaluación; no significa que sea ganadora.</p>
        @endif
      </div>
    @endif

    <div class="mt-4">
      <h3 class="h5">Aclaraciones</h3>
      @forelse($review->clarifications->sortByDesc('created_at') as $clarification)
        <article class="ff-review-request" aria-labelledby="clarification-{{ $clarification->public_id }}">
          <div class="d-flex flex-wrap justify-content-between gap-2">
            <h4 class="h6" id="clarification-{{ $clarification->public_id }}">Solicitud del {{ $clarification->created_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }}</h4>
            <span class="badge text-bg-light">{{ $clarification->status->label() }}</span>
          </div>
          <p>{{ $clarification->message }}</p>
          @if($clarification->due_at)
            <p class="small"><strong>Fecha límite:</strong> {{ $clarification->due_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }} (Hermosillo)</p>
          @else
            <p class="small">Esta solicitud no tiene una fecha límite registrada.</p>
          @endif

          @foreach($clarification->responses->sortBy('created_at') as $response)
            <div class="ff-review-response">
              <strong>Tu respuesta · {{ $response->created_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }}</strong>
              <p>{{ $response->body }}</p>
              @if($response->files->isNotEmpty())
                <ul class="mb-0">
                  @foreach($response->files as $file)
                    <li><a href="{{ route('admissibility.clarification-files.download', $file) }}">{{ $file->original_name }}</a> <small>({{ number_format($file->size_bytes / 1024, 1) }} KiB)</small></li>
                  @endforeach
                </ul>
              @endif
            </div>
          @endforeach

          @if($clarification->status === \App\Enums\ClarificationStatus::Open)
            <form method="POST" action="{{ route('admissibility.clarifications.respond', $clarification) }}" enctype="multipart/form-data" class="mt-3" data-confirm="Tu respuesta quedará registrada y no podrá sobrescribirse. ¿Deseas enviarla?">
              @csrf
              <label class="form-label" for="response-{{ $clarification->public_id }}">Respuesta</label>
              <textarea class="form-control @error('response') is-invalid @enderror" id="response-{{ $clarification->public_id }}" name="response" rows="5" maxlength="2000" required aria-describedby="response-help-{{ $clarification->public_id }}">{{ old('response') }}</textarea>
              <p class="form-text" id="response-help-{{ $clarification->public_id }}">Máximo 2,000 caracteres. La respuesta no modifica el título, categoría, descripción, archivos ni snapshot enviados.</p>
              <label class="form-label mt-2" for="clarification-files-{{ $clarification->public_id }}">Archivos privados opcionales</label>
              <input class="form-control" id="clarification-files-{{ $clarification->public_id }}" name="files[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp">
              <p class="form-text">PDF, JPEG, PNG o WebP. Hasta tres archivos y 10 MiB acumulados.</p>
              <button class="btn btn-flower" type="submit">Enviar respuesta</button>
            </form>
          @endif
        </article>
      @empty
        <p class="text-muted">No tienes solicitudes de aclaración.</p>
      @endforelse
    </div>

    <div class="mt-4">
      <h3 class="h5">Verificación de residencia</h3>
      <p class="small">Carga documentos sólo cuando exista una solicitud. Puedes ocultar datos innecesarios y dejar visibles nombre, domicilio y fecha cuando corresponda.</p>
      @forelse($review->residencyRequests->sortByDesc('created_at') as $residencyRequest)
        <article class="ff-review-request" aria-labelledby="residency-{{ $residencyRequest->public_id }}">
          <div class="d-flex flex-wrap justify-content-between gap-2">
            <h4 class="h6" id="residency-{{ $residencyRequest->public_id }}">{{ $residencyRequest->subjectLabel() }}</h4>
            <span class="badge text-bg-light">{{ $residencyRequest->status->label() }}</span>
          </div>
          @if($residencyRequest->instructions)<p>{{ $residencyRequest->instructions }}</p>@endif
          @if($residencyRequest->status === \App\Enums\ResidencyVerificationStatus::Rejected && $residencyRequest->review_reason)
            <div class="alert alert-warning"><strong>Motivo del rechazo:</strong> {{ $residencyRequest->review_reason }}</div>
          @endif
          @if($residencyRequest->documents->isNotEmpty())
            <ul>
              @foreach($residencyRequest->documents as $document)
                <li><a href="{{ route('admissibility.residency-documents.download', $document) }}">{{ $document->original_name }}</a> · {{ $document->document_type->label() }}</li>
              @endforeach
            </ul>
          @endif

          @if(in_array($residencyRequest->status, [\App\Enums\ResidencyVerificationStatus::Requested, \App\Enums\ResidencyVerificationStatus::Rejected], true))
            <form method="POST" action="{{ route('admissibility.residency.upload', $residencyRequest) }}" enctype="multipart/form-data" class="row g-3 mt-1">
              @csrf
              <div class="col-12 col-lg-6">
                <label class="form-label" for="document-type-{{ $residencyRequest->public_id }}">Tipo de comprobante</label>
                <select class="form-select" id="document-type-{{ $residencyRequest->public_id }}" name="document_type" required>
                  <option value="">Selecciona una opción</option>
                  @foreach(\App\Enums\ResidencyDocumentType::options() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-12 col-lg-6">
                <label class="form-label" for="residency-files-{{ $residencyRequest->public_id }}">Documentos</label>
                <input class="form-control" id="residency-files-{{ $residencyRequest->public_id }}" name="documents[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" required>
              </div>
              <div class="col-12">
                <label class="form-label" for="equivalent-{{ $residencyRequest->public_id }}">Si elegiste “documento equivalente”, describe por qué aplica</label>
                <textarea class="form-control" id="equivalent-{{ $residencyRequest->public_id }}" name="equivalent_description" rows="2" maxlength="1000"></textarea>
              </div>
              <div class="col-12"><button class="btn btn-flower" type="submit">Cargar de forma privada</button></div>
            </form>
          @endif
        </article>
      @empty
        <p class="text-muted">No se te han solicitado comprobantes de residencia.</p>
      @endforelse
    </div>
  @endif
</section>
