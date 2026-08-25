@extends('layouts.flowerflow')
@section('title', 'Propuestas del panel')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
  <div>
    <p class="ff-kicker mb-1">Recepción</p>
    <h1>Propuestas</h1>
  </div>
  <div class="d-flex flex-wrap gap-2">
    @if(config('flowerflow.flags.bulk_judge_assignment'))
      @if(auth()->user()->hasExactRoles(['admin']) && auth()->user()->can('decide admissibility') && auth()->user()->can('manage blind review packages') && auth()->user()->can('manage evaluation assignments'))
        <a class="btn btn-outline-primary" href="{{ route('panel.assignments.bulk.create') }}">
          <i class="ri-user-add-line me-1" aria-hidden="true"></i> Asignar varias propuestas
        </a>
      @endif
    @endif
    @if(config('flowerflow.flags.submission_reminders'))
      @can('send submission reminders')
        <a class="btn btn-outline-primary" href="{{ route('panel.submissions.reminders.create') }}">
          <i class="ri-mail-send-line me-1" aria-hidden="true"></i> Enviar recordatorio
        </a>
      @endcan
    @endif
    @can('export submissions')
      <a class="btn btn-flower" href="{{ route('panel.submissions.exports.create') }}">
        <i class="ri-file-excel-2-line me-1" aria-hidden="true"></i> Exportar a Excel
      </a>
      <a class="btn btn-outline-success" href="{{ route('panel.submissions.exports.contacts.create') }}">
        <i class="ri-contacts-book-3-line me-1" aria-hidden="true"></i> Exportar Contactos
      </a>
    @endcan
  </div>
</div>

<form method="GET" class="card ff-card p-3 my-4" aria-label="Filtros de propuestas">
  <div class="row g-3 align-items-end">
    <div class="col-sm-6 col-xl-3">
      <label class="form-label" for="folio">Folio o ID de propuesta</label>
      <input class="form-control" id="folio" name="folio" maxlength="64" value="{{ request('folio') }}">
    </div>
    <div class="col-sm-6 col-xl-3">
      <label class="form-label" for="status">Estado</label>
      <select class="form-select" id="status" name="status">
        <option value="">Todos</option>
        <option value="draft" @selected(request('status') === 'draft')>Borrador</option>
        <option value="submitted" @selected(request('status') === 'submitted')>Enviada</option>
      </select>
    </div>
    <div class="col-sm-6 col-xl-4">
      <label class="form-label" for="category">Categoría</label>
      <select class="form-select" id="category" name="category">
        <option value="">Todas</option>
        @foreach($categories as $category)
          <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-sm-6 col-xl-2"><button class="btn btn-flower w-100">Filtrar</button></div>
  </div>
</form>

<div class="card ff-card">
  <p class="small text-muted px-3 pt-3 mb-0">En Acciones, el sobre envía un recordatorio, el avión registra administrativamente una propuesta y el escudo abre su expediente de admisibilidad.</p>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Folio</th><th>Proyecto</th><th>Participante</th><th>Categoría</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr></thead>
      <tbody>
      @forelse($submissions as $item)
        <tr>
          <td>{{ $item->folio ?: '—' }}</td>
          <td><a href="{{ route('panel.submissions.show', $item) }}">{{ $item->title }}</a></td>
          <td>{{ $item->user->name }}</td>
          <td>{{ $item->category->name }}</td>
          <td>{{ $item->statusLabel() }}</td>
          <td>{{ $item->created_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }}</td>
          <td>
            @php($hasRowAction = false)
            <div class="d-flex flex-wrap gap-1">
              @if(config('flowerflow.flags.submission_reminders'))
                @can('sendReminder', $item)
                  @php($hasRowAction = true)
                  <form method="POST" action="{{ route('panel.submissions.reminders.submissions.store', $item) }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-primary" type="submit" aria-label="Enviar recordatorio de la propuesta {{ $item->title }}">
                      <i class="ri-mail-send-line" aria-hidden="true"></i>
                      <span class="d-none d-xl-inline ms-1">Recordar</span>
                    </button>
                  </form>
                @endcan
              @endif
              @if(config('flowerflow.flags.administrative_finalization') && $item->isDraft())
                @can('administrativelyFinalize', $item)
                  @php($hasRowAction = true)
                  @if($item->hasMinimumFinalizationContent())
                    <a class="btn btn-sm btn-outline-success" href="{{ route('panel.submissions.administrative-finalization.show', $item) }}" aria-label="Registrar administrativamente la propuesta {{ $item->title }}">
                      <i class="ri-send-plane-line" aria-hidden="true"></i>
                      <span class="d-none d-xl-inline ms-1">Registrar</span>
                    </a>
                  @else
                    <span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true" title="Requiere nombre, resumen y descripción">
                      <i class="ri-send-plane-line" aria-hidden="true"></i>
                      <span class="d-none d-xl-inline ms-1">Incompleta</span>
                    </span>
                  @endif
                @endcan
              @endif
              @if(config('flowerflow.flags.admissibility_review') && $item->status === 'submitted')
                @can('view admissibility reviews')
                  @php($hasRowAction = true)
                  @if($item->eligibilityReview)
                    @php($admissibilityActionLabel = match ($item->eligibilityReview->status) {
                      \App\Enums\EligibilityReviewStatus::Pending => 'Revisar admisibilidad',
                      \App\Enums\EligibilityReviewStatus::InReview,
                      \App\Enums\EligibilityReviewStatus::ClarificationRequested => 'Continuar admisibilidad',
                      \App\Enums\EligibilityReviewStatus::Admitted => 'Ver admisión',
                      \App\Enums\EligibilityReviewStatus::NotAdmitted => 'Ver resolución',
                    })
                    <a class="btn btn-sm btn-outline-success" href="{{ route('panel.admissibility.show', $item->eligibilityReview) }}" aria-label="{{ $admissibilityActionLabel }} de la propuesta {{ $item->title }}">
                      <i class="ri-shield-check-line" aria-hidden="true"></i>
                      <span class="d-none d-xl-inline ms-1">{{ $admissibilityActionLabel }}</span>
                    </a>
                  @else
                    <span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true" aria-label="Sin expediente de admisibilidad para la propuesta {{ $item->title }}" title="Ejecuta el backfill de admisibilidad antes de revisar esta propuesta">
                      <i class="ri-shield-check-line" aria-hidden="true"></i>
                      <span class="d-none d-xl-inline ms-1">Sin expediente</span>
                    </span>
                  @endif
                @endcan
              @endif
              @unless($hasRowAction)
                <span aria-hidden="true">—</span><span class="visually-hidden">Sin acciones disponibles</span>
              @endunless
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="p-4">No hay resultados.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
<div class="mt-3">{{ $submissions->links() }}</div>

@can('export submissions')
  <section class="card ff-card mt-4" aria-labelledby="recent-exports-title">
    <div class="card-body">
      <h2 class="h5" id="recent-exports-title">Exportaciones recientes</h2>
      @if($hasStalledExports)
        <div class="alert alert-warning" role="alert">
          Hay una exportación que lleva más de {{ config('flowerflow.exports.stalled_after_minutes') }} minutos en espera. Un administrador debe verificar el worker de la cola <code>{{ config('flowerflow.exports.queue') }}</code>; no generes duplicados mientras se diagnostica.
        </div>
      @endif
      <p class="text-muted">Cada archivo permanece disponible durante {{ config('flowerflow.exports.retention_hours') }} horas y sólo puede descargarlo quien lo solicitó.</p>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead><tr><th>Solicitud</th><th>Tipo</th><th>Estado</th><th>Propuestas</th><th>Vigencia</th><th></th></tr></thead>
          <tbody>
          @forelse($exports as $export)
            <tr>
              <td>{{ $export->created_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }}</td>
              <td>{{ $export->kindLabel() }}</td>
              <td>{{ $export->status->label() }}</td>
              <td>{{ $export->proposal_count }}</td>
              <td>{{ $export->expires_at?->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') ?: '—' }}</td>
              <td class="text-end">
                @if($export->isAvailable())
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('panel.submissions.exports.download', $export) }}">Descargar</a>
                @elseif($export->status === \App\Enums\SubmissionExportStatus::Failed)
                  <span class="text-danger">Genera una nueva exportación.</span>
                @else
                  <span class="text-muted">No disponible</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="6">Aún no has generado exportaciones.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </section>
@endcan
@endsection
