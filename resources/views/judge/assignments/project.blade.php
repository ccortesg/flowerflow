@extends('layouts.flowerflow')

@section('title', 'Proyecto asignado')

@section('content')
@php($payload = $package->payload)
<section class="ff-evaluation-shell" aria-labelledby="project-title">
  <div class="card ff-card p-3 p-md-4 p-lg-5">
    <a href="{{ route('judge.assignments.index') }}" class="mb-3 align-self-start">← Volver a mis asignaciones</a>
    @include('judge.assignments._wizard-stepper', ['currentStep' => 2])

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-3">
      <div>
        <p class="ff-kicker mb-1">Paso 2 de 4</p>
        <h1 id="project-title" class="h2 mb-1">Proyecto asignado</h1>
        <p class="text-secondary mb-0">Consulta la información que usarás como base para evaluar.</p>
      </div>
      <div class="d-flex flex-column flex-sm-row gap-2 ff-export-actions">
        <a class="btn btn-outline-danger" href="{{ route('judge.assignments.project.exports.pdf', $assignment) }}"><i class="ri-file-pdf-2-line" aria-hidden="true"></i> Exportar PDF</a>
        <a class="btn btn-outline-success" href="{{ route('judge.assignments.project.exports.xlsx', $assignment) }}"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel</a>
      </div>
    </div>

    <div class="alert alert-warning" role="note"><strong>Anonimización estructural.</strong> Se ocultan los datos estructurados de identidad y operación. El texto, los enlaces o los anexos pueden identificar a su autor; este paquete no promete anonimato semántico.</div>

    <article class="ff-project-content">
      <dl class="row ff-detail-list">
        <dt class="col-sm-4">Categoría</dt><dd class="col-sm-8">{{ data_get($payload, 'category.name') }}</dd>
        <dt class="col-sm-4">Modalidad</dt><dd class="col-sm-8">{{ data_get($payload, 'submission.participation_type') === 'team' ? 'Equipo' : 'Individual' }}</dd>
        <dt class="col-sm-4">Título</dt><dd class="col-sm-8">{{ data_get($payload, 'submission.title') }}</dd>
      </dl>
      <section aria-labelledby="project-summary"><h2 id="project-summary" class="h4">Resumen</h2><p class="text-break">{{ data_get($payload, 'submission.summary') }}</p></section>
      <section aria-labelledby="project-description"><h2 id="project-description" class="h4">Descripción</h2><div class="ff-rich-content">{!! data_get($payload, 'submission.description_html') !!}</div></section>
      <section class="mt-4" aria-labelledby="project-links"><h2 id="project-links" class="h4">Enlaces externos</h2>
        <ul>@forelse(data_get($payload, 'external_links', []) as $link)<li><a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">{{ $link['kind'] === 'youtube' ? 'Video del proyecto' : 'Carpeta pública del proyecto' }} <span class="visually-hidden">(abre en una pestaña nueva)</span></a> <small class="text-secondary">{{ $link['normalized_host'] }}</small></li>@empty<li>Sin enlaces externos.</li>@endforelse</ul>
      </section>
      <section class="mt-4" aria-labelledby="project-files"><h2 id="project-files" class="h4">Anexos evaluables</h2>
        <ul class="list-group list-group-flush mb-4">
          @forelse($package->files as $file)
            <li class="list-group-item d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2"><span>{{ $file->neutral_label }} <small>({{ $file->file_class->label() }}, {{ number_format($file->expected_size_bytes / 1024, 1) }} KiB)</small></span><a class="btn btn-sm btn-outline-primary" href="{{ route('judge.assignments.packages.files.download', [$assignment, $file]) }}">Descargar anexo</a></li>
          @empty<li class="list-group-item">Sin anexos capturados.</li>@endforelse
        </ul>
      </section>
    </article>

    <div class="ff-wizard-actions mt-4">
      <a class="btn btn-flower" href="{{ route('judge.assignments.evaluation.show', $assignment) }}">Ir a evaluación <span aria-hidden="true">→</span></a>
    </div>
  </div>
</section>
@endsection
