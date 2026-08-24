@extends('layouts.flowerflow')
@section('title', 'Exportar contactos')
@section('content')
<p class="ff-kicker mb-1">Recepción</p>
<h1>Exportar contactos de propuestas enviadas</h1>

<div class="card ff-card mt-4">
  <div class="card-body">
    <p>Se generará un archivo XLSX con los contactos de las <strong>{{ $proposalCount }}</strong> propuestas enviadas registradas al momento de ejecutar el proceso.</p>
    <p>Incluye una fila por proyecto con nombre completo, correo electrónico, teléfono de contacto, nombre y descripción del proyecto. No incluye borradores, integrantes, archivos ni otros datos del perfil.</p>
    <div class="alert alert-warning" role="alert">
      El archivo contiene datos personales. Permanecerá privado, sólo tú podrás descargarlo después de autenticarte y expirará en {{ config('flowerflow.exports.retention_hours') }} horas.
    </div>
    <form method="POST" action="{{ route('panel.submissions.exports.contacts.store') }}">
      @csrf
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-flower" type="submit"><i class="ri-contacts-book-3-line me-1" aria-hidden="true"></i> Generar contactos</button>
        <a class="btn btn-outline-secondary" href="{{ route('panel.submissions.index') }}">Cancelar</a>
      </div>
    </form>
  </div>
</div>
@endsection
