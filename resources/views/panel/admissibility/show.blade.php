@extends('layouts.flowerflow')
@section('title', 'Expediente de admisibilidad')
@section('content')
@php($submission = $review->submission)
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div><p class="ff-kicker mb-1">{{ $submission->folio }}</p><h1>{{ $submission->title }}</h1><p>{{ $submission->category->name }} · <span class="ff-review-status ff-review-status-{{ $review->status->value }}">{{ $review->status->label() }}</span></p></div>
  <a href="{{ route('panel.admissibility.index') }}">Volver a admisibilidad</a>
</div>

<div class="row g-4">
  <div class="col-xl-8">
    <section class="card ff-card p-4 mb-4" aria-labelledby="snapshot-title">
      <h2 class="h4" id="snapshot-title">Versión inmutable enviada</h2>
      <p class="small text-muted">Copia de la versión {{ $review->submissionVersion->version }} · capturada en UTC. Esta pantalla no ofrece controles para modificarla.</p>
      <dl class="row"><dt class="col-sm-3">Título</dt><dd class="col-sm-9">{{ data_get($review->submissionVersion->snapshot, 'submission.title') }}</dd><dt class="col-sm-3">Resumen</dt><dd class="col-sm-9">{{ data_get($review->submissionVersion->snapshot, 'submission.summary') }}</dd><dt class="col-sm-3">Modalidad</dt><dd class="col-sm-9">{{ data_get($review->submissionVersion->snapshot, 'submission.participation_type') === 'team' ? 'Equipo' : 'Individual' }}</dd></dl>
      <details><summary>Consultar descripción enviada</summary><div class="mt-3">{{ data_get($review->submissionVersion->snapshot, 'submission.description_text') }}</div></details>
      <h3 class="h5 mt-4">Archivos de la propuesta</h3>
      <ul>
        @forelse($submission->files as $file)
          <li><a href="{{ route('submissions.files.download', [$submission, $file]) }}">{{ $file->original_name }}</a> · {{ number_format($file->size_bytes / 1024, 1) }} KiB · SHA-256 {{ substr($file->sha256, 0, 12) }}…</li>
        @empty
          <li>No hay archivos registrados.</li>
        @endforelse
      </ul>
      @if($submission->externalLinks->isNotEmpty())
        <h3 class="h5 mt-4">Enlaces enviados</h3>
        <ul>@foreach($submission->externalLinks as $link)<li><a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer">{{ $link->normalized_host }}</a></li>@endforeach</ul>
      @endif
    </section>

    <section class="card ff-card p-4 mb-4" aria-labelledby="clarifications-title">
      <h2 class="h4" id="clarifications-title">Aclaraciones</h2>
      @forelse($review->clarifications->sortByDesc('created_at') as $clarification)
        <article class="ff-review-request">
          <div class="d-flex flex-wrap justify-content-between gap-2"><h3 class="h6">{{ $clarification->message }}</h3><span class="badge text-bg-light">{{ $clarification->status->label() }}</span></div>
          <p class="small">
            Solicitada {{ $clarification->created_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }}
            @if($clarification->due_at)
              · vence {{ $clarification->due_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }}
            @else
              · sin fecha límite
            @endif
            (Hermosillo)
          </p>
          @foreach($clarification->responses as $response)
            <div class="ff-review-response"><strong>Respuesta recibida {{ $response->created_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }}</strong><p>{{ $response->body }}</p>@if($response->files->isNotEmpty())<ul class="mb-0">@foreach($response->files as $file)<li><a href="{{ route('admissibility.clarification-files.download', $file) }}">{{ $file->original_name }}</a> · SHA-256 {{ substr($file->sha256, 0, 12) }}…</li>@endforeach</ul>@endif</div>
          @endforeach
          @if($clarification->status !== \App\Enums\ClarificationStatus::Closed)
            <form method="POST" action="{{ route('panel.admissibility.clarifications.close', [$review, $clarification]) }}" data-confirm="¿Deseas cerrar expresamente esta aclaración?">@csrf<button class="btn btn-sm btn-outline-secondary mt-3" type="submit">Cerrar aclaración</button></form>
          @endif
        </article>
      @empty<p class="text-muted">No hay aclaraciones registradas.</p>@endforelse

      @can('requestClarification', $review)
        @unless($review->status->isFinal())
          <form method="POST" action="{{ route('panel.admissibility.clarifications.store', $review) }}" class="border-top pt-4 mt-4">
            @csrf
            <h3 class="h5">Nueva solicitud de aclaración</h3>
            <div class="mb-3"><label class="form-label" for="message">Información requerida</label><textarea class="form-control" id="message" name="message" rows="4" maxlength="2000" required>{{ old('message') }}</textarea><p class="form-text">No solicites cambios que sustituyan o mejoren materialmente el proyecto.</p></div>
            <div class="mb-3"><label class="form-label" for="due_at">Fecha límite opcional (Hermosillo)</label><input class="form-control" id="due_at" name="due_at" type="datetime-local" value="{{ old('due_at') }}"></div>
            <button class="btn btn-flower" type="submit">Solicitar aclaración</button>
          </form>
        @endunless
      @endcan
    </section>

    <section class="card ff-card p-4 mb-4" aria-labelledby="residency-title">
      <h2 class="h4" id="residency-title">Residencia privada</h2>
      <p class="small">Los comprobantes están separados de los anexos evaluables y cada descarga queda auditada.</p>
      @forelse($review->residencyRequests->sortByDesc('created_at') as $residencyRequest)
        <article class="ff-review-request">
          <div class="d-flex flex-wrap justify-content-between gap-2"><h3 class="h6">{{ $residencyRequest->subjectLabel() }}</h3><span class="badge text-bg-light">{{ $residencyRequest->status->label() }}</span></div>
          @if($residencyRequest->instructions)<p>{{ $residencyRequest->instructions }}</p>@endif
          @if($residencyRequest->documents->isNotEmpty())
            <ul>@foreach($residencyRequest->documents as $document)<li><a href="{{ route('admissibility.residency-documents.download', $document) }}">{{ $document->original_name }}</a> · {{ $document->document_type->label() }} · {{ number_format($document->size_bytes / 1024, 1) }} KiB · SHA-256 {{ substr($document->sha256, 0, 12) }}…</li>@endforeach</ul>
          @else<p class="text-muted">Aún no hay documentos.</p>@endif
          @if($residencyRequest->review_reason)<p><strong>Justificación de revisión:</strong> {{ $residencyRequest->review_reason }}</p>@endif
          @can('review', $residencyRequest)
            @if(in_array($residencyRequest->status, [\App\Enums\ResidencyVerificationStatus::Requested, \App\Enums\ResidencyVerificationStatus::Rejected], true) && $residencyRequest->documents->isNotEmpty())
              <form method="POST" action="{{ route('panel.admissibility.residency.review', [$review, $residencyRequest]) }}">@csrf<button class="btn btn-sm btn-outline-dark" type="submit">Marcar en revisión</button></form>
            @endif
            @if(in_array($residencyRequest->status, [\App\Enums\ResidencyVerificationStatus::Requested, \App\Enums\ResidencyVerificationStatus::UnderReview, \App\Enums\ResidencyVerificationStatus::Rejected], true))
              <form method="POST" action="{{ route('panel.admissibility.residency.resolve', [$review, $residencyRequest]) }}" class="row g-3 mt-1" data-confirm="¿Confirmas esta resolución de residencia? Quedará auditada.">
                @csrf
                <div class="col-md-4"><label class="form-label" for="residency-status-{{ $residencyRequest->public_id }}">Resolución</label><select class="form-select" id="residency-status-{{ $residencyRequest->public_id }}" name="residency_status" required><option value="verified">Verificada</option><option value="rejected">Rechazada</option><option value="cancelled">Cancelada</option></select></div>
                <div class="col-md-8"><label class="form-label" for="review-reason-{{ $residencyRequest->public_id }}">Justificación</label><textarea class="form-control" id="review-reason-{{ $residencyRequest->public_id }}" name="review_reason" rows="2" maxlength="2000"></textarea><p class="form-text">Obligatoria para rechazar y para aceptar un documento equivalente.</p></div>
                <div class="col-12 form-check ms-2"><input class="form-check-input" id="confirm-residency-{{ $residencyRequest->public_id }}" name="confirm_resolution" type="checkbox" value="1" required><label class="form-check-label" for="confirm-residency-{{ $residencyRequest->public_id }}">Confirmo que revisé la documentación y deseo registrar esta resolución.</label></div>
                <div class="col-12"><button class="btn btn-sm btn-flower" type="submit">Guardar resolución de residencia</button></div>
              </form>
            @endif
          @endcan
        </article>
      @empty<p class="text-muted">No hay solicitudes de residencia.</p>@endforelse

      @can('review', $review)
        @unless($review->status->isFinal())
          <form method="POST" action="{{ route('panel.admissibility.residency.store', $review) }}" class="border-top pt-4 mt-4">
            @csrf
            <h3 class="h5">Solicitar comprobante</h3>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label" for="subject_type">Persona</label><select class="form-select" id="subject_type" name="subject_type" required><option value="representative">Representante: {{ $submission->user->name }}</option>@if($submission->team)<option value="team_member">Integrante del equipo</option>@endif</select></div>
              @if($submission->team)<div class="col-md-6"><label class="form-label" for="subject_team_member_id">Integrante</label><select class="form-select" id="subject_team_member_id" name="subject_team_member_id"><option value="">Selecciona cuando corresponda</option>@foreach($submission->team->members->where('is_representative', false) as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select></div>@endif
              <div class="col-md-6"><label class="form-label" for="clarification_public_id">Aclaración relacionada (opcional)</label><select class="form-select" id="clarification_public_id" name="clarification_public_id"><option value="">Sin relación</option>@foreach($review->clarifications as $clarification)<option value="{{ $clarification->public_id }}">{{ \Illuminate\Support\Str::limit($clarification->message, 70) }}</option>@endforeach</select></div>
              <div class="col-12"><label class="form-label" for="instructions">Indicaciones opcionales</label><textarea class="form-control" id="instructions" name="instructions" rows="3" maxlength="2000"></textarea></div>
              <div class="col-12"><button class="btn btn-flower" type="submit">Solicitar residencia</button></div>
            </div>
          </form>
        @endunless
      @endcan
    </section>
  </div>

  <div class="col-xl-4">
    <aside class="card ff-card p-4 mb-4" aria-labelledby="identity-title"><h2 class="h4" id="identity-title">Identidad</h2><dl><dt>Nombre</dt><dd>{{ $submission->user->name }}</dd><dt>Correo</dt><dd>{{ $submission->user->email }}</dd><dt>Celular</dt><dd>{{ $submission->user->profile?->mobile_e164 ?: '—' }}</dd><dt>Colonia</dt><dd>{{ $submission->user->profile?->neighborhood ?: '—' }}</dd></dl>@if($submission->team)<h3 class="h6">Equipo</h3><ul>@foreach($submission->team->members as $member)<li>{{ $member->full_name }} @if($member->is_representative)(representante)@endif</li>@endforeach</ul>@endif</aside>

    <aside class="card ff-card p-4 mb-4" aria-labelledby="internal-title"><h2 class="h4" id="internal-title">Notas internas</h2><p class="alert alert-warning small">Esta información nunca se muestra a participantes ni debe compartirse con futuros jueces.</p><p>{{ $review->internal_notes ?: 'Sin notas internas registradas.' }}</p></aside>

    <aside class="card ff-card p-4 mb-4" aria-labelledby="events-title"><h2 class="h4" id="events-title">Eventos inmutables</h2><ol class="ff-audit-list">@foreach($review->events->sortByDesc('created_at') as $event)<li><strong>{{ $event->label() }}</strong><small>{{ $event->created_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s') }} (Hermosillo) · {{ $event->actor?->name ?: 'Sistema' }}</small></li>@endforeach</ol></aside>

    @can('review', $review)
      @if($review->status === \App\Enums\EligibilityReviewStatus::Pending)
        <form method="POST" action="{{ route('panel.admissibility.start', $review) }}" class="card ff-card p-4 mb-4">@csrf<h2 class="h4">Asignar revisión</h2><p>Inicia el expediente y asígnatelo como persona revisora.</p><button class="btn btn-flower" type="submit">Iniciar revisión</button></form>
      @endif
    @endcan

    @can('decide', $review)
      @unless($review->status->isFinal())
        <form method="POST" action="{{ route('panel.admissibility.decide', $review) }}" class="card ff-card p-4" data-confirm="¿Confirmas la resolución final? Un doble clic no creará una segunda resolución.">
          @csrf
          <h2 class="h4">Resolver admisibilidad</h2>
          <div class="mb-3"><label class="form-label" for="decision">Decisión</label><select class="form-select" id="decision" name="decision" required><option value="admitted">Admitida</option><option value="not_admitted">No admitida</option></select></div>
          <div class="mb-3"><label class="form-label" for="participant_reason">Motivo visible para la persona participante</label><textarea class="form-control" id="participant_reason" name="participant_reason" rows="4" maxlength="2000" required></textarea></div>
          <div class="mb-3"><label class="form-label" for="internal_notes">Notas internas separadas</label><textarea class="form-control" id="internal_notes" name="internal_notes" rows="4" maxlength="5000"></textarea></div>
          <div class="form-check mb-3"><input class="form-check-input" id="confirm_resolution" name="confirm_resolution" type="checkbox" value="1" required><label class="form-check-label" for="confirm_resolution">Confirmo que revisé la versión enviada y deseo registrar esta resolución.</label></div>
          <button class="btn btn-flower" type="submit">Guardar resolución final</button>
        </form>
      @endunless
    @endcan
  </div>
</div>
@endsection
