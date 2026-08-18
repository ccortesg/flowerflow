@extends('layouts.flowerflow')

@section('title', 'Detalle de asignación')

@section('content')
<section class="ff-narrow-card" aria-labelledby="assignment-title">
  <div class="card ff-card p-4 p-lg-5">
    <a href="{{ route('judge.assignments.index') }}" class="mb-3">← Volver a mis asignaciones</a>
    <h1 id="assignment-title" class="h2">Asignación {{ $assignment->public_id }}</h1>
    <dl class="row">
      <dt class="col-sm-4">Categoría</dt><dd class="col-sm-8">{{ $assignment->submissionVersion->submission->category->name }}</dd>
      <dt class="col-sm-4">Plazo</dt><dd class="col-sm-8">{{ $assignment->due_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s') }} (Hermosillo)</dd>
      <dt class="col-sm-4">Estado</dt><dd class="col-sm-8">{{ $assignment->status->label() }}</dd>
    </dl>
    @if($package)
      @php($payload = $package->payload)
      <div class="alert alert-warning" role="note"><strong>Anonimización estructural.</strong> Se ocultan los datos estructurados de identidad y operación. El texto, los enlaces o los anexos pueden identificar a su autor; este paquete no promete anonimato semántico.</div>
      <article aria-labelledby="blind-package-title">
        <h2 id="blind-package-title" class="h4">Proyecto asignado</h2>
        <dl class="row">
          <dt class="col-sm-4">Categoría</dt><dd class="col-sm-8">{{ data_get($payload, 'category.name') }}</dd>
          <dt class="col-sm-4">Modalidad</dt><dd class="col-sm-8">{{ data_get($payload, 'submission.participation_type') === 'team' ? 'Equipo' : 'Individual' }}</dd>
          <dt class="col-sm-4">Título</dt><dd class="col-sm-8">{{ data_get($payload, 'submission.title') }}</dd>
        </dl>
        <h3 class="h5">Resumen</h3><p>{{ data_get($payload, 'submission.summary') }}</p>
        <h3 class="h5">Descripción</h3><div>{!! data_get($payload, 'submission.description_html') !!}</div>
        <h3 class="h5 mt-4">Enlaces externos</h3>
        <ul>@forelse(data_get($payload, 'external_links', []) as $link)<li><a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">{{ $link['kind'] === 'youtube' ? 'Video del proyecto' : 'Carpeta pública del proyecto' }}</a> <small>{{ $link['normalized_host'] }}</small></li>@empty<li>Sin enlaces externos.</li>@endforelse</ul>
        <h3 class="h5 mt-4">Anexos evaluables</h3>
        <ul class="list-group list-group-flush mb-4">
          @forelse($package->files as $file)
            <li class="list-group-item d-flex flex-wrap justify-content-between gap-2"><span>{{ $file->neutral_label }} <small>({{ $file->file_class->label() }}, {{ number_format($file->expected_size_bytes / 1024, 1) }} KiB)</small></span><a href="{{ route('judge.assignments.packages.files.download', [$assignment, $file]) }}">Descargar</a></li>
          @empty<li class="list-group-item">Sin anexos capturados.</li>@endforelse
        </ul>
      </article>
    @else
      <div class="alert alert-info">El paquete ciego activo todavía no está disponible. No se genera automáticamente desde este acceso.</div>
    @endif

    @if($assignment->conflict)
      <div class="alert alert-warning" role="status">Conflicto declarado: {{ $assignment->conflict->type->label() }}. La asignación permanece bloqueada.</div>
    @elseif($assignment->status === \App\Enums\JudgeAssignmentStatus::Active)
      <h2 class="h5">Declarar conflicto</h2>
      <form method="POST" action="{{ route('judge.assignments.conflicts.store', $assignment) }}">
        @csrf
        <fieldset>
          <legend class="form-label">Tipo de conflicto</legend>
          @foreach($conflictTypes as $type)
            <div class="form-check mb-2"><input class="form-check-input" type="radio" name="type" id="type-{{ $type->value }}" value="{{ $type->value }}" required><label class="form-check-label" for="type-{{ $type->value }}">{{ $type->label() }}</label></div>
          @endforeach
        </fieldset>
        <div class="my-3"><label class="form-label" for="explanation">Explicación (sólo para “Otro conflicto”)</label><textarea class="form-control" id="explanation" name="explanation" maxlength="1000" aria-describedby="explanation-help"></textarea><small id="explanation-help" class="text-secondary">Si eliges otro conflicto, escribe entre 20 y 1,000 caracteres.</small></div>
        <button class="btn btn-warning" type="submit">Confirmar declaración de conflicto</button>
      </form>
    @endif
  </div>
</section>
@endsection
