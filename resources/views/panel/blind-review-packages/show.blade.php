@extends('layouts.flowerflow')

@section('title', 'Paquete ciego')

@section('content')
<a href="{{ route('panel.blind-review-packages.index') }}" class="d-inline-flex align-items-center gap-1 mb-3">← Volver a paquetes</a>
<p class="ff-kicker mb-1">Fase 02B · M5</p>
<h1 class="h2">Paquete ciego estructural</h1>
<dl class="row">
  <dt class="col-sm-3">ID técnico</dt><dd class="col-sm-9"><code>{{ $submission->public_id }}</code></dd>
  <dt class="col-sm-3">Categoría</dt><dd class="col-sm-9">{{ $submission->category->name }}</dd>
  <dt class="col-sm-3">Versión final</dt><dd class="col-sm-9">{{ $version->version }}</dd>
  <dt class="col-sm-3">Estado</dt><dd class="col-sm-9">{{ $package?->status->label() ?? 'Sin generar' }}</dd>
  @if($package)<dt class="col-sm-3">Hash canónico</dt><dd class="col-sm-9"><code>{{ substr($package->payload_sha256, 0, 16) }}…</code></dd>@endif
</dl>

<div class="alert alert-warning" role="note"><strong>Anonimización estructural.</strong> Esta proyección omite identidad y datos operativos estructurados. El contenido o los anexos pueden identificar a su autor y no se promete anonimato semántico.</div>

@if($package)
  @php($payload = $package->payload)
  <article class="card ff-card p-4 mb-4" aria-labelledby="preview-title">
    <h2 id="preview-title" class="h4">Vista previa allowlist</h2>
    <dl class="row">
      <dt class="col-sm-3">Categoría</dt><dd class="col-sm-9">{{ data_get($payload, 'category.name') }} <small>({{ data_get($payload, 'category.slug') }})</small></dd>
      <dt class="col-sm-3">Modalidad</dt><dd class="col-sm-9">{{ data_get($payload, 'submission.participation_type') === 'team' ? 'Equipo' : 'Individual' }}</dd>
      <dt class="col-sm-3">Título</dt><dd class="col-sm-9">{{ data_get($payload, 'submission.title') }}</dd>
    </dl>
    <h3 class="h5">Resumen</h3><p>{{ data_get($payload, 'submission.summary') }}</p>
    <h3 class="h5">Descripción</h3><div>{!! data_get($payload, 'submission.description_html') !!}</div>
    <h3 class="h5 mt-4">Enlaces externos</h3>
    <ul>@forelse(data_get($payload, 'external_links', []) as $link)<li><a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">{{ $link['kind'] === 'youtube' ? 'Video' : 'Carpeta pública' }}</a> <small>{{ $link['normalized_host'] }}</small></li>@empty<li>Sin enlaces.</li>@endforelse</ul>
    <h3 class="h5 mt-4">Inventario neutro</h3>
    <ul>@forelse($package->files as $file)<li>{{ $file->neutral_label }} · {{ $file->file_class->label() }} · {{ number_format($file->expected_size_bytes / 1024, 1) }} KiB</li>@empty<li>Sin anexos.</li>@endforelse</ul>
  </article>
@endif

@if(!$package || $package->status === \App\Enums\BlindReviewPackageStatus::Draft)
  <div class="row g-4">
    <div class="col-lg-6"><section class="card ff-card p-4 h-100" aria-labelledby="generate-title">
      <h2 id="generate-title" class="h5">{{ $package ? 'Regenerar borrador' : 'Generar borrador' }}</h2>
      <form method="POST" action="{{ route('panel.blind-review-packages.generate', $submission) }}">@csrf
        <div class="mb-3"><label class="form-label" for="generation_reason">Razón administrativa</label><textarea class="form-control" id="generation_reason" name="reason" minlength="20" maxlength="1000" required>{{ old('reason') }}</textarea></div>
        <div class="mb-3"><label class="form-label" for="generation_password">Contraseña actual</label><input class="form-control" id="generation_password" name="current_password" type="password" autocomplete="current-password" required></div>
        <button class="btn btn-outline-primary" type="submit">{{ $package ? 'Regenerar y validar' : 'Generar y validar' }}</button>
      </form>
    </section></div>
    @if($package)
      <div class="col-lg-6"><section class="card ff-card p-4 h-100" aria-labelledby="activate-title">
        <h2 id="activate-title" class="h5">Activar paquete</h2>
        <p>La activación fija de forma inmutable la proyección y su inventario.</p>
        <form method="POST" action="{{ route('panel.blind-review-packages.activate', $submission) }}">@csrf
          <div class="mb-3"><label class="form-label" for="activation_reason">Razón administrativa</label><textarea class="form-control" id="activation_reason" name="reason" minlength="20" maxlength="1000" required>{{ old('reason') }}</textarea></div>
          <div class="mb-3"><label class="form-label" for="activation_password">Contraseña actual</label><input class="form-control" id="activation_password" name="current_password" type="password" autocomplete="current-password" required></div>
          <button class="btn btn-primary" type="submit">Activar paquete inmutable</button>
        </form>
      </section></div>
    @endif
  </div>
@else
  <div class="alert alert-success" role="status">El paquete activo está fijado y no admite edición o regeneración.</div>
@endif
@error('package')<div class="alert alert-danger mt-3" role="alert">{{ $message }}</div>@enderror
@endsection
