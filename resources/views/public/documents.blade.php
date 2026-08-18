@extends('layouts.flowerflow')
@section('title', 'Documentos jurídicos')

@section('content')
<section class="ff-section">
  <div class="container">
    <p class="ff-kicker">Versión jurídica vigente</p>
    <h1>Documentos de Hermosillo Florece 2026</h1>
    <p class="lead">Consulta y conserva estos archivos PDF sin alteraciones.</p>

    <div class="alert alert-light border mt-4" role="note">
      <strong>Responsable legal:</strong> {{ config('flowerflow.organization.legal_name') }} · RFC {{ config('flowerflow.organization.rfc') }}<br>
      <strong>Nombre comercial:</strong> {{ config('flowerflow.organization.commercial_name') }} · <strong>Movimiento ciudadano:</strong> {{ config('flowerflow.organization.citizen_movement') }}<br>
      <strong>Domicilio:</strong> {{ config('flowerflow.organization.address') }}
    </div>

    <div class="list-group mt-4">
      @foreach(config('flowerflow.legal_documents') as $document)
        <a class="list-group-item list-group-item-action p-4" href="{{ asset($document['path']) }}">
          <strong>{{ $document['title'] }}</strong><br>
          <small>PDF · versión {{ $document['version'] }}</small>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endsection
