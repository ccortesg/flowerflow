@extends('layouts.flowerflow')

@section('title', 'Cancelar asignación')

@section('content')
<a href="{{ route('panel.assignments.show', $assignment->submissionVersion->submission) }}" class="d-inline-flex mb-3">← Volver</a>
<div class="card ff-card p-4 mx-auto" style="max-width: 44rem;">
  <p class="ff-kicker mb-1">Asignación manual</p>
  <h1 class="h2">Cancelar asignación</h1>
  <p>Cancelarás la asignación <code>{{ $assignment->public_id }}</code> de {{ $assignment->judgeProfile->user->name }}. La fila y la auditoría se conservarán.</p>
  <div class="alert alert-warning" role="note">Sólo se permite mientras esté activa, sin conflicto y sin evaluación iniciada.</div>
  <form method="POST" action="{{ route('panel.assignments.cancel.store', $assignment) }}" novalidate>
    @csrf
    <div class="mb-3"><label class="form-label" for="reason">Razón administrativa</label><textarea class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" minlength="20" maxlength="1000" required>{{ old('reason') }}</textarea>@error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="mb-3"><label class="form-label" for="current_password">Contraseña actual</label><input class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" type="password" autocomplete="current-password" required>@error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    @error('assignment')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror
    <button class="btn btn-outline-danger" type="submit">Confirmar cancelación</button>
  </form>
</div>
@endsection
