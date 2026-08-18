@extends('layouts.flowerflow')

@section('title', 'Alta de juez')

@section('content')
<p class="ff-kicker mb-1">Administración de jueces</p>
<h1>Alta directa</h1>
<p class="text-body-secondary">Se creará una cuenta con rol exclusivo de juez, función de asignación explícita y configuración pendiente. La contraseña inicial no se mostrará ni se enviará.</p>

<form method="POST" action="{{ route('panel.judges.store') }}" class="card ff-card p-4 mt-4" novalidate>
  @csrf
  <div class="mb-3">
    <label class="form-label" for="name">Nombre</label>
    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" maxlength="255" autocomplete="name" required autofocus>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="mb-3">
    <label class="form-label" for="email">Correo electrónico</label>
    <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="mb-4">
    <label class="form-label" for="assignment_role">Función en la evaluación</label>
    <select class="form-select @error('assignment_role') is-invalid @enderror" id="assignment_role" name="assignment_role" required>
      <option value="">Selecciona una función</option>
      @foreach(\App\Enums\JudgeAssignmentRole::cases() as $assignmentRole)
        <option value="{{ $assignmentRole->value }}" @selected(old('assignment_role') === $assignmentRole->value)>{{ $assignmentRole->label() }}</option>
      @endforeach
    </select>
    @error('assignment_role')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-text">Los cuatro jueces principales evaluarán todas las propuestas elegibles sin límite fijo. El juez sustituto sólo recibirá reasignaciones y tendrá máximo diez activas.</div>
  </div>
  <div class="alert alert-info" role="note">Después del alta se programará un enlace temporal para que el juez establezca su propia contraseña. El correo se verificará mediante el flujo firmado de Flower Flow.</div>
  <div class="d-flex flex-wrap gap-2">
    <button class="btn btn-flower" type="submit">Crear cuenta de juez</button>
    <a class="btn btn-outline-dark" href="{{ route('panel.judges.index') }}">Cancelar</a>
  </div>
</form>
@endsection
