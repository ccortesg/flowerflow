@extends('layouts.flowerflow')

@section('title', 'Mis propuestas')
@section('description', 'Consulta el avance y las acciones disponibles para tus propuestas.')

@php
  $submissionLimit = (int) config('flowerflow.limits.submissions_per_user');
  $submissionsOpen = (bool) config('flowerflow.flags.submissions');
  $hasReachedLimit = $submissions->count() >= $submissionLimit;
  $canCreateSubmission = $submissionsOpen && ! $hasReachedLimit;
@endphp

@section('content')
<section class="ff-submissions-hero" aria-labelledby="submissions-title">
  <div>
    <p class="ff-ui-kicker">Convocatoria ciudadana 2026</p>
    <h1 id="submissions-title">Mis propuestas</h1>
    <p>Consulta tus borradores, revisa las propuestas enviadas y continúa trabajando cuando la recepción esté disponible.</p>
    <p class="ff-submissions-count"><strong>{{ $submissions->count() }}</strong> de {{ $submissionLimit }} propuestas registradas</p>
  </div>

  <img src="{{ asset('assets/flowerflow/logo_florecehermosillo_transparente.png') }}" width="220" height="220" alt="" aria-hidden="true">

  <div class="ff-submissions-hero-action">
    @if($canCreateSubmission)
      <a class="ff-ui-button ff-ui-button-primary" href="{{ route('submissions.create') }}">
        <span class="ri ri-add-line" aria-hidden="true"></span> Nueva propuesta
      </a>
    @elseif($hasReachedLimit)
      <p><span class="ri ri-information-line" aria-hidden="true"></span> Ya alcanzaste el máximo de {{ $submissionLimit }} propuestas.</p>
    @else
      <p><span class="ri ri-time-line" aria-hidden="true"></span> La recepción de propuestas no está disponible en este momento.</p>
    @endif
  </div>
</section>

@if($submissions->isEmpty())
  <section class="ff-submissions-empty" aria-labelledby="submissions-empty-title">
    <span class="ff-submissions-empty-icon ri ri-draft-line" aria-hidden="true"></span>
    <h2 id="submissions-empty-title">Aún no tienes propuestas registradas</h2>
    <p>Cuando comiences una propuesta aparecerá aquí para que puedas consultar su estado y continuarla.</p>
    @if($canCreateSubmission)
      <a class="ff-ui-button ff-ui-button-primary" href="{{ route('submissions.create') }}">
        <span class="ri ri-add-line" aria-hidden="true"></span> Crear mi primera propuesta
      </a>
    @endif
  </section>
@else
  <section class="ff-submissions-panel" aria-labelledby="submissions-list-title" data-submissions-browser>
    <header class="ff-submissions-panel-header">
      <div>
        <h2 id="submissions-list-title">Propuestas registradas</h2>
        <p>Mostrando <span data-submissions-count aria-live="polite">{{ $submissions->count() }}</span> de {{ $submissions->count() }}</p>
      </div>

      <div class="ff-submissions-tools">
        <div class="ff-submissions-search">
          <label class="visually-hidden" for="submission-search">Buscar por título o categoría</label>
          <span class="ri ri-search-line" aria-hidden="true"></span>
          <input class="form-control" id="submission-search" type="search" placeholder="Buscar propuesta" autocomplete="off" data-submissions-search>
        </div>
        <div class="ff-submissions-filter">
          <label class="visually-hidden" for="submission-status">Filtrar por estado</label>
          <span class="ri ri-filter-3-line" aria-hidden="true"></span>
          <select class="form-select" id="submission-status" data-submissions-status>
            <option value="">Todos los estados</option>
            <option value="draft">Borrador</option>
            <option value="submitted">Enviada</option>
          </select>
        </div>
        <button class="ff-ui-button ff-ui-button-secondary" type="button" data-submissions-clear>Limpiar</button>
      </div>
    </header>

    <div class="ff-submissions-table-heading" aria-hidden="true">
      <span>Propuesta</span><span>Folio</span><span>Actualización</span><span>Estado</span><span>Acciones</span>
    </div>

    <div class="ff-submissions-list" data-submissions-list>
      @foreach($submissions as $item)
        @php
          $categoryName = \Illuminate\Support\Str::lower($item->category->name);
          $categoryIcon = \Illuminate\Support\Str::contains($categoryName, ['movilidad', 'transporte', 'vialidad'])
              ? 'ri-bike-line'
              : (\Illuminate\Support\Str::contains($categoryName, ['ambiente', 'verde', 'ecolog']) ? 'ri-seedling-line' : 'ri-government-line');
          $updatedAt = $item->updated_at?->copy()->timezone(config('flowerflow.timezone'));
        @endphp
        <article class="ff-submission-row" data-submission-item data-submission-status="{{ $item->status }}">
          <div class="ff-submission-primary">
            <span class="ff-submission-category-icon ri {{ $categoryIcon }}" aria-hidden="true"></span>
            <div>
              <h3>{{ $item->title }}</h3>
              <p>{{ $item->category->name }}</p>
            </div>
          </div>
          <div class="ff-submission-cell" data-label="Folio"><span>{{ $item->folio ?: '—' }}</span></div>
          <div class="ff-submission-cell" data-label="Actualización">
            <time datetime="{{ $item->updated_at?->toIso8601String() }}">{{ $updatedAt?->format('d/m/Y · H:i') ?? '—' }}{{ $updatedAt ? ' h' : '' }}</time>
          </div>
          <div class="ff-submission-cell" data-label="Estado">
            <span class="ff-submission-status {{ $item->status === 'submitted' ? 'is-submitted' : 'is-draft' }}">{{ $item->statusLabel() }}</span>
          </div>
          <div class="ff-submission-actions" data-label="Acciones">
            <a href="{{ route('submissions.show', $item) }}"><span class="ri ri-eye-line" aria-hidden="true"></span> Ver</a>
            @if($item->isDraft() && $submissionsOpen)
              <a href="{{ route('submissions.edit', $item) }}"><span class="ri ri-pencil-line" aria-hidden="true"></span> Editar</a>
            @endif
          </div>
        </article>
      @endforeach
    </div>

    <div class="ff-submissions-no-results" hidden data-submissions-empty-filter role="status">
      <span class="ri ri-search-eye-line" aria-hidden="true"></span>
      <h3>No encontramos propuestas con esos filtros</h3>
      <p>Prueba con otro término o limpia los filtros para ver todas.</p>
    </div>
  </section>
@endif
@endsection
