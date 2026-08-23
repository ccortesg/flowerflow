@extends('layouts.flowerflow')
@section('title', 'Envío administrativo de propuesta')
@section('content')
<p class="ff-kicker mb-1">Recepción</p>
<h1>Registrar propuesta administrativamente</h1>

<div class="card ff-card mt-4">
  <div class="card-body">
    <h2 class="h4">{{ $submission->title }}</h2>
    <dl class="row">
      <dt class="col-sm-4">Participante</dt><dd class="col-sm-8">{{ $submission->user->name }}</dd>
      <dt class="col-sm-4">Categoría</dt><dd class="col-sm-8">{{ $submission->category->name }}</dd>
      <dt class="col-sm-4">Archivos</dt><dd class="col-sm-8">{{ $submission->files->count() }}</dd>
      <dt class="col-sm-4">Estado</dt><dd class="col-sm-8">{{ $submission->statusLabel() }}</dd>
    </dl>

    <div class="alert alert-warning" role="alert">
      Esta excepción generará folio y snapshot aunque falten archivo, perfil, elegibilidad del equipo o aceptaciones de envío. No se registrarán aceptaciones jurídicas en nombre del participante y el actor administrativo quedará auditado.
    </div>

    @if(! $submission->hasMinimumFinalizationContent())
      <div class="alert alert-danger" role="alert">No puede registrarse: deben existir título, resumen y descripción.</div>
      <a class="btn btn-outline-secondary" href="{{ route('panel.submissions.index') }}">Volver al listado</a>
    @else
      <form method="POST" action="{{ route('panel.submissions.administrative-finalization.store', $submission) }}" novalidate>
        @csrf
        <div class="mb-3">
          <label class="form-label" for="reason">Razón administrativa</label>
          <textarea class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" rows="5" minlength="20" maxlength="1000" required aria-describedby="reason-help @error('reason') reason-error @enderror">{{ old('reason') }}</textarea>
          <p class="form-text" id="reason-help">Entre 20 y 1,000 caracteres. Se conservará dentro del snapshot inmutable.</p>
          @error('reason')<p class="invalid-feedback" id="reason-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-check mb-4">
          <input class="form-check-input @error('confirm_administrative_finalization') is-invalid @enderror" id="confirm_administrative_finalization" name="confirm_administrative_finalization" type="checkbox" value="1" required @checked(old('confirm_administrative_finalization'))>
          <label class="form-check-label" for="confirm_administrative_finalization">Comprendo que esta es una excepción administrativa y que no representa una aceptación realizada por el participante.</label>
          @error('confirm_administrative_finalization')<p class="invalid-feedback">{{ $message }}</p>@enderror
        </div>
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-danger" type="submit"><i class="ri-send-plane-line me-1" aria-hidden="true"></i> Registrar como enviada</button>
          <a class="btn btn-outline-secondary" href="{{ route('panel.submissions.index') }}">Cancelar</a>
        </div>
      </form>
    @endif
  </div>
</div>
@endsection
