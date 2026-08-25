@extends('layouts.flowerflow')

@section('title', 'Inicio del área de evaluación')

@section('content')
<header class="mb-4">
  <p class="ff-kicker mb-1">Hermosillo Florece 2026</p>
  <h1 class="h2 mb-2">Tu área de evaluación</h1>
  <p class="text-secondary mb-0">Consulta tus asignaciones, inicia o continúa borradores y declara un conflicto sólo cuando corresponda.</p>
</header>

<div class="row g-3 mb-4" aria-label="Resumen de asignaciones">
  @foreach([
    ['Vigentes', $active, 'ri-clipboard-line'],
    ['Sin iniciar', $notStarted, 'ri-play-circle-line'],
    ['Borradores en progreso', $drafts, 'ri-draft-line'],
    ['Próximas a vencer', $dueSoon, 'ri-time-line'],
  ] as [$label, $value, $icon])
    <div class="col-sm-6 col-xl-3"><div class="card ff-card p-4 h-100"><span class="ri {{ $icon }} fs-3 text-success" aria-hidden="true"></span><strong class="display-6">{{ $value }}</strong><span>{{ $label }}</span></div></div>
  @endforeach
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <section class="card ff-card p-4 h-100" aria-labelledby="next-action-title">
      <h2 id="next-action-title" class="h4">Siguiente paso</h2>
      @if($nextAssignment)
        <p>La asignación con vencimiento más próximo es <code>{{ $nextAssignment->public_id }}</code>.</p>
        <a class="btn btn-flower align-self-start" href="{{ route('judge.assignments.show', $nextAssignment) }}">Abrir siguiente asignación</a>
      @else
        <div class="alert alert-info mb-0" role="status">No tienes una asignación vigente que requiera acción.</div>
      @endif
    </section>
  </div>
  <div class="col-lg-5">
    <section class="card ff-card p-4 h-100" aria-labelledby="judge-flow-title">
      <h2 id="judge-flow-title" class="h4">Flujo sencillo</h2>
      <ol class="mb-3">
        <li>Abre la asignación.</li>
        <li>Revisa el paquete ciego.</li>
        <li>Inicia o continúa la evaluación.</li>
        <li>Guarda tu borrador.</li>
        @if(config('flowerflow.flags.evaluation_finalization'))
          <li>Revisa y confirma el envío.</li>
        @endif
      </ol>
      @if(config('flowerflow.flags.evaluation_finalization'))
        <div class="alert alert-info mb-0" role="note">Al enviar, la revisión queda sellada y ya no podrás modificarla.</div>
      @else
        <div class="alert alert-warning mb-0" role="note">El envío final de la evaluación todavía no está habilitado.</div>
      @endif
    </section>
  </div>
</div>
@endsection
