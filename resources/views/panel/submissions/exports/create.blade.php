@extends('layouts.flowerflow')
@section('title', 'Exportar propuestas')
@section('content')
<p class="ff-kicker mb-1">Recepción</p>
<h1>Exportar propuestas a Excel</h1>

<div class="card ff-card mt-4">
  <div class="card-body">
    <p>Se generará un archivo XLSX con las <strong>{{ $proposalCount }}</strong> propuestas enviadas y borradores registrados al momento de ejecutar el proceso.</p>
    <p>Incluye datos de contacto, información completa del proyecto, integrantes, archivos y enlaces externos. No incluye fecha de nacimiento, comprobantes de residencia, aclaraciones, credenciales, datos de sesión ni rutas internas.</p>
    <div class="alert alert-warning" role="alert">
      El archivo contiene datos personales. Permanecerá privado, sólo tú podrás descargarlo después de autenticarte y expirará en {{ config('flowerflow.exports.retention_hours') }} horas.
    </div>
    <form method="POST" action="{{ route('panel.submissions.exports.store') }}">
      @csrf
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-flower" type="submit"><i class="ri-file-excel-2-line me-1" aria-hidden="true"></i> Generar Excel</button>
        <a class="btn btn-outline-secondary" href="{{ route('panel.submissions.index') }}">Cancelar</a>
      </div>
    </form>
  </div>
</div>
@endsection
