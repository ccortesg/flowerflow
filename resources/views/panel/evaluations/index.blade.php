@extends('layouts.flowerflow')

@section('title', 'Evaluaciones')

@section('content')
<header class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div><p class="ff-kicker mb-1">Administración</p><h1 class="h2 mb-2">Evaluaciones</h1><p class="text-secondary mb-0">Consulta estados y revisiones sin consolidar calificaciones ni mostrar resultados.</p></div>
  @if(config('flowerflow.flags.evaluation_export'))
    @can('create', \App\Models\EvaluationExport::class)
      <a class="btn btn-flower" href="{{ route('panel.evaluations.exports.create') }}">
        <i class="ri-file-excel-2-line me-1" aria-hidden="true"></i> Exportar evaluaciones
      </a>
    @endcan
  @endif
</header>
<div class="card ff-card table-responsive">
  <table class="table align-middle mb-0">
    <thead><tr><th>Evaluación</th><th>Juez sujeto</th><th>Categoría</th><th>Estado</th><th>Revisión</th><th>Actualización</th><th><span class="visually-hidden">Acción</span></th></tr></thead>
    <tbody>
      @forelse($evaluations as $evaluation)
        <tr>
          <td><code>{{ $evaluation->public_id }}</code></td>
          <td>{{ $evaluation->judgeAssignment->judgeProfile->user->name }}</td>
          <td>{{ $evaluation->judgeAssignment->submissionVersion->submission->category->name }}</td>
          <td>{{ $evaluation->status->label() }}</td>
          <td>{{ $evaluation->currentRevision->revision_number }}</td>
          <td>{{ $evaluation->updated_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s') }}</td>
          <td><a class="btn btn-sm btn-outline-primary" href="{{ route('panel.evaluations.show', $evaluation) }}">Ver detalle</a></td>
        </tr>
      @empty<tr><td colspan="7">No hay evaluaciones registradas.</td></tr>@endforelse
    </tbody>
  </table>
</div>
<div class="mt-4">{{ $evaluations->links() }}</div>

@if(config('flowerflow.flags.evaluation_export'))
  @can('create', \App\Models\EvaluationExport::class)
    <section class="card ff-card mt-4" aria-labelledby="recent-evaluation-exports-title">
      <div class="card-body">
        <h2 class="h5" id="recent-evaluation-exports-title">Exportaciones recientes</h2>
        @if($hasStalledExports)
          <div class="alert alert-warning" role="alert">
            Hay una exportación en espera desde hace más de {{ config('flowerflow.exports.stalled_after_minutes') }} minutos. Verifica el worker de la cola <code>{{ config('flowerflow.exports.queue') }}</code> antes de solicitar otra.
          </div>
        @endif
        <p class="text-secondary">Los archivos son confidenciales, privados y permanecen disponibles durante {{ config('flowerflow.exports.retention_hours') }} horas únicamente para quien los solicitó.</p>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead><tr><th>Solicitud</th><th>Estado</th><th>Evaluaciones</th><th>Revisiones</th><th>Vigencia</th><th><span class="visually-hidden">Acción</span></th></tr></thead>
            <tbody>
            @forelse($exports as $export)
              <tr>
                <td>{{ $export->created_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }}</td>
                <td>{{ $export->status->label() }}</td>
                <td>{{ $export->evaluation_count }}</td>
                <td>{{ $export->revision_count }}</td>
                <td>{{ $export->expires_at?->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') ?: '—' }}</td>
                <td class="text-end">
                  @if($export->isAvailable())
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('panel.evaluations.exports.download', $export) }}">Descargar</a>
                  @elseif($export->status === \App\Enums\EvaluationExportStatus::Failed)
                    <span class="text-danger">Genera una nueva exportación.</span>
                  @else
                    <span class="text-secondary">No disponible</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="6">Aún no has generado exportaciones de evaluaciones.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </section>
  @endcan
@endif
@endsection
