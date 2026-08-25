@extends('layouts.flowerflow')

@section('title', 'Reabrir evaluación')

@section('content')
<section class="ff-narrow-card" aria-labelledby="reopen-title"><div class="card ff-card p-4 p-lg-5">
  <a href="{{ route('panel.evaluations.show', $evaluation) }}" class="mb-3">← Volver al detalle</a>
  <h1 id="reopen-title" class="h2">Reabrir evaluación</h1>
  <div class="alert alert-warning" role="alert"><strong>Acción append-only.</strong> Se conservará intacta la revisión {{ $evaluation->currentRevision->revision_number }} y se creará una nueva copia editable.</div>
  <p><strong>Juez sujeto:</strong> {{ $evaluation->judgeAssignment->judgeProfile->user->name }}</p>
  <form method="POST" action="{{ route('panel.evaluations.reopen.store', $evaluation) }}">
    @csrf
    <input type="hidden" name="lock_version" value="{{ $evaluation->lock_version }}">
    <div class="mb-3"><label class="form-label" for="reason">Motivo administrativo</label><textarea class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" minlength="20" maxlength="1000" required rows="5">{{ old('reason') }}</textarea>@error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="mb-3"><label class="form-label" for="current-password">Contraseña actual</label><input class="form-control @error('current_password') is-invalid @enderror" type="password" id="current-password" name="current_password" required autocomplete="current-password">@error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" value="1" id="confirm-reopen" name="confirm_reopen" required><label class="form-check-label" for="confirm-reopen">Confirmo la creación de una nueva revisión y la conservación de la evidencia anterior.</label></div>
    <button class="btn btn-warning" type="submit">Reabrir en nueva revisión</button>
  </form>
</div></section>
@endsection
