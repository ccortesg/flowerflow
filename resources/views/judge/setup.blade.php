@extends('layouts.flowerflow')

@section('title', 'Configurar acceso de juez')

@section('content')
<section class="ff-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <div class="card ff-card p-4 p-lg-5">
          <p class="ff-kicker mb-2">Cuenta de juez</p>
          <h1 class="h2">Configura tu acceso</h1>
          <p>Este enlace confirma que tienes acceso al correo de la cuenta. Al guardar tu contraseña, el correo quedará verificado y no se enviará una segunda solicitud.</p>
          <form method="POST" action="{{ url()->full() }}" class="mt-3" novalidate>
            @csrf
            <x-password-fields password-label="Nueva contraseña" confirmation-label="Confirmar nueva contraseña" />
            <button class="btn btn-flower" type="submit">Guardar contraseña y verificar correo</button>
          </form>
          <p class="small text-secondary mt-3 mb-0">Por seguridad, al terminar volverás al inicio de sesión. Este enlace sólo puede usarse una vez.</p>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
