@extends('layouts.flowerflow')

@section('title', 'Detalle de rúbrica')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
  <div>
    <p class="ff-kicker mb-1">Rúbrica global</p>
    <h1 class="mb-1">Versión {{ $rubric->version }}</h1>
    <p class="text-body-secondary mb-0">{{ $rubric->title }} · {{ $rubric->competition->name }}</p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    @can('update', $rubric)
      <a class="btn btn-flower" href="{{ route('panel.rubrics.edit', $rubric) }}">Editar borrador</a>
    @endcan
    <a class="btn btn-outline-dark" href="{{ route('panel.rubrics.index') }}">Volver al listado</a>
  </div>
</div>

<div class="row g-4 mt-1">
  <div class="col-lg-4">
    <section class="card ff-card p-4 h-100" aria-labelledby="rubric-status-title">
      <h2 class="h4" id="rubric-status-title">Estado y versión</h2>
      <dl class="row mb-0">
        <dt class="col-7">Estado</dt><dd class="col-5">{{ $rubric->status->label() }}</dd>
        <dt class="col-7">Criterios</dt><dd class="col-5">{{ $rubric->criteria->count() }} de 5</dd>
        <dt class="col-7">Escala</dt><dd class="col-5">0–10</dd>
        <dt class="col-7">Paso</dt><dd class="col-5">0.5</dd>
        <dt class="col-7">Peso</dt><dd class="col-5">100 %</dd>
        <dt class="col-7">Redondeo</dt><dd class="col-5">{{ $rubric->rounding_mode }}</dd>
        <dt class="col-7">Precisión</dt><dd class="col-5">{{ $rubric->internal_decimal_places }}/{{ $rubric->display_decimal_places }}</dd>
      </dl>
      @if($rubric->activated_at)
        <p class="small text-body-secondary mt-3 mb-0">Activada el {{ $rubric->activated_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }} (hora de Hermosillo).</p>
      @endif
      @if($rubric->superseded_at)
        <p class="small text-body-secondary mt-2 mb-0">Sustituida el {{ $rubric->superseded_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i') }}.</p>
      @endif
    </section>
  </div>
  <div class="col-lg-8">
    <section class="card ff-card p-4" aria-labelledby="rubric-criteria-title">
      <h2 class="h4" id="rubric-criteria-title">Criterios</h2>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <caption class="visually-hidden">Criterios ordenados de la versión {{ $rubric->version }}</caption>
          <thead><tr><th scope="col">Orden</th><th scope="col">Código</th><th scope="col">Criterio</th><th scope="col">Peso</th><th scope="col">Descripción</th></tr></thead>
          <tbody>
            @foreach($rubric->criteria as $criterion)
              <tr>
                <td>{{ $criterion->sort_order }}</td>
                <td><code>{{ $criterion->code }}</code></td>
                <td>{{ $criterion->label }}</td>
                <td>{{ rtrim(rtrim($criterion->weight, '0'), '.') }} %</td>
                <td>{{ $criterion->description ?? 'POR_CONFIRMAR' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <p class="small text-body-secondary mt-3 mb-0">Comentario general futuro: 100–2,000 caracteres. Comentarios por criterio: opcionales, hasta 1,000. M3 no captura comentarios ni puntajes.</p>
    </section>
  </div>
</div>

@can('activate', $rubric)
  <section class="card ff-card p-4 mt-4" aria-labelledby="rubric-activate-title">
    <h2 class="h4" id="rubric-activate-title">Activar esta versión</h2>
    <div class="alert alert-warning" role="note">La activación vuelve inmutable esta versión y conserva la activa anterior como sustituida. No crea evaluaciones ni asignaciones.</div>
    <form method="POST" action="{{ route('panel.rubrics.activate', $rubric) }}" novalidate>
      @csrf
      <div class="mb-3">
        <label class="form-label" for="reason">Razón de activación</label>
        <textarea class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" rows="4" minlength="20" maxlength="1000" required>{{ old('reason') }}</textarea>
        <div class="form-text">Entre 20 y 1,000 caracteres; quedará en auditoría.</div>
        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="mb-3">
        <label class="form-label" for="current_password">Contraseña administrativa actual</label>
        <input class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <button class="btn btn-flower" type="submit">Confirmar activación</button>
    </form>
  </section>
@else
  <div class="alert alert-info mt-4" role="status">Esta versión es inmutable y no dispone de acciones de edición, reactivación o eliminación.</div>
@endcan
@endsection
