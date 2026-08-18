@extends('layouts.flowerflow')
@section('title', 'Panel')
@section('content')
<p class="ff-kicker mb-1">Administración</p><h1 class="mb-4">Resumen de convocatoria</h1>
<div class="row g-4 mb-4">@foreach(['participants' => 'Participantes', 'drafts' => 'Borradores', 'submitted' => 'Enviadas', 'total' => 'Total'] as $key => $label)<div class="col-sm-6 col-xl-3"><div class="card ff-card p-4"><span class="text-muted">{{ $label }}</span><strong class="display-5">{{ $counts[$key] }}</strong></div></div>@endforeach</div>
<div class="row g-4"><div class="col-lg-5"><div class="card ff-card p-4"><h2 class="h4">Enviadas por categoría</h2>@foreach($categoryDistribution as $category)<div class="d-flex justify-content-between border-bottom py-3"><span>{{ $category->name }}</span><strong>{{ $category->submissions_count }}</strong></div>@endforeach</div></div><div class="col-lg-7"><div class="card ff-card p-4"><div class="d-flex justify-content-between"><h2 class="h4">Actividad reciente</h2><a href="{{ route('panel.submissions.index') }}">Ver todas</a></div><div class="table-responsive"><table class="table"><thead><tr><th>Proyecto</th><th>Persona</th><th>Estado</th></tr></thead><tbody>@forelse($recent as $item)<tr><td><a href="{{ route('panel.submissions.show', $item) }}">{{ $item->title }}</a></td><td>{{ $item->user->name }}</td><td>{{ $item->statusLabel() }}</td></tr>@empty<tr><td colspan="3">Sin actividad.</td></tr>@endforelse</tbody></table></div></div></div></div>
<section class="card ff-card p-4 mt-4" aria-labelledby="panel-legal-title">
  <h2 class="h4" id="panel-legal-title">Documentos jurídicos vigentes</h2>
  <p class="mb-3">Responsable legal: <strong>{{ config('flowerflow.organization.legal_name') }}</strong>, RFC {{ config('flowerflow.organization.rfc') }}, bajo el nombre comercial {{ config('flowerflow.organization.commercial_name') }}.</p>
  <div class="d-flex flex-wrap gap-3">
    @foreach(config('flowerflow.legal_documents') as $document)
      <a href="{{ asset($document['path']) }}" target="_blank" rel="noopener noreferrer">{{ $document['title'] }} v{{ $document['version'] }}</a>
    @endforeach
  </div>
</section>
@endsection
