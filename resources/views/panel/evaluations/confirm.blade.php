@extends('layouts.flowerflow')

@section('title', 'Confirmar envío administrativo')

@section('content')
<section class="ff-narrow-card" aria-labelledby="admin-confirm-title"><div class="card ff-card p-4 p-lg-5">
  <a href="{{ route('panel.evaluations.show', $evaluation) }}" class="mb-3">← Volver al detalle</a>
  <h1 id="admin-confirm-title" class="h2">Confirmar envío administrativo</h1>
  <div class="alert alert-danger" role="alert">Actúas en nombre del juez. Flower Flow conservará tu cuenta como actor real y no atribuirá esta acción al juez.</div>
  @include('evaluations._revision-read', ['revision' => $evaluation->currentRevision])
  <form method="POST" action="{{ route('panel.evaluations.submit', $evaluation) }}">
    @csrf
    <input type="hidden" name="lock_version" value="{{ $evaluation->lock_version }}">
    <div class="mb-3"><label class="form-label" for="submit-current-password">Contraseña actual</label><input class="form-control" type="password" id="submit-current-password" name="current_password" required autocomplete="current-password"></div>
    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" value="1" id="admin-confirm-submission" name="confirm_submission" required><label class="form-check-label" for="admin-confirm-submission">Confirmo el envío inmutable de esta revisión.</label></div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" value="1" id="admin-acting" name="confirm_acting_on_behalf" required><label class="form-check-label" for="admin-acting">Confirmo que actúo administrativamente en nombre del juez y que mi cuenta quedará registrada como actor real.</label></div>
    <button class="btn btn-danger" type="submit">Enviar como administración</button>
  </form>
</div></section>
@endsection
