@extends('layouts.flowerflow')
@section('title', 'Hermosillo Florece 2026')
@section('description', 'Convocatoria gratuita para propuestas que ayuden a florecer a Hermosillo.')
@section('content')
@php
  $categories = $competition?->categories ?? collect([
    (object)['name' => 'Movilidad con Flow', 'description' => 'Ideas para mejorar movilidad, vialidad, accesibilidad y seguridad de desplazamientos.'],
    (object)['name' => 'Hermosillo Florece', 'description' => 'Ideas para una ciudad más verde y sostenible: arbolado, espacios públicos, agua, sombra y cuidado ambiental.'],
    (object)['name' => 'Mi familia, mi mascota', 'description' => 'Ideas para bienestar animal, tenencia responsable y convivencia de familias con mascotas.'],
  ]);
  $isOpen = config('flowerflow.flags.submissions');
@endphp
<section class="ff-hero ff-section">
  <div class="container"><div class="row align-items-center g-5">
    <div class="col-lg-6">
      <p class="ff-kicker">Convocatoria ciudadana gratuita</p>
      <h1 class="ff-display">Hermosillo Florece 2026</h1>
      <p class="lead my-4">Comparte una propuesta clara y realizable para mejorar nuestra movilidad, el entorno urbano o la convivencia con nuestras mascotas.</p>
      <div class="ff-deadline rounded-4 p-4 mb-4"><strong>Fecha límite</strong><br><span class="fs-5">15 de agosto de 2026, 23:59 horas (tiempo de Hermosillo)</span></div>
      <p><span class="ff-status">{{ $isOpen ? 'Recepción abierta' : 'Recepción aún no habilitada' }}</span></p>
      <div class="d-flex flex-wrap gap-2 mt-4">
        @if(config('flowerflow.flags.registration'))<a class="btn btn-flower btn-lg" href="{{ route('register') }}">Crear cuenta</a>@else<span class="btn btn-secondary btn-lg disabled" aria-disabled="true">Registro próximamente</span>@endif
        <a class="btn btn-outline-dark btn-lg" href="{{ route('login') }}">Ya tengo cuenta</a>
      </div>
    </div>
    <div class="col-lg-6 text-center"><img class="ff-poster" src="{{ asset('assets/flowerflow/poster_evento.png') }}" width="1122" height="1402" alt="Cartel oficial de Hermosillo Florece 2026 con las tres categorías y fecha límite" fetchpriority="high"></div>
  </div></div>
</section>

<section class="ff-section bg-white" aria-labelledby="objetivo"><div class="container"><div class="row g-4 align-items-center"><div class="col-lg-5"><p class="ff-kicker">Nuestro objetivo</p><h2 id="objetivo" class="display-6 fw-bold">Ideas que se convierten en una ciudad mejor</h2></div><div class="col-lg-7"><p class="lead mb-0">Queremos escuchar soluciones ciudadanas originales, útiles y sostenibles. Puedes participar individualmente o en un equipo de hasta cinco integrantes.</p></div></div></div></section>

<section id="categorias" class="ff-section"><div class="container"><p class="ff-kicker">Tres oportunidades</p><h2 class="mb-4">Categorías</h2><div class="row g-4">@foreach($categories as $category)<div class="col-md-4"><article class="card ff-card p-3"><div class="card-body"><span class="fs-2" aria-hidden="true">{{ ['↗','✿','♥'][$loop->index] ?? '•' }}</span><h3 class="h4 mt-3">{{ $category->name }}</h3><p>{{ $category->description }}</p></div></article></div>@endforeach</div></div></section>

<section id="como-participar" class="ff-section bg-white"><div class="container"><div class="row g-5"><div class="col-lg-6"><p class="ff-kicker">Requisitos</p><h2>¿Quién puede participar?</h2><ul class="mt-4"><li>Personas físicas de 18 años o más.</li><li>Residencia comprobable en Hermosillo, Sonora.</li><li>Participación individual o en equipos de hasta cinco integrantes, incluida la persona representante.</li><li>Una propuesta por categoría y máximo tres propuestas por cuenta.</li><li>Propuestas en español y participación gratuita.</li></ul></div><div class="col-lg-6"><p class="ff-kicker">Proceso</p><h2>Cómo participar</h2><ol class="mt-4"><li>Crea tu cuenta con tus datos de participante.</li><li>Verifica tu correo electrónico.</li><li>Captura la propuesta y guarda borradores.</li><li>Adjunta documentos y revisa los consentimientos.</li><li>Envía antes del cierre y conserva tu folio.</li></ol></div></div></div></section>

<section class="ff-section"><div class="container"><div class="row g-4"><div class="col-lg-7"><article class="card ff-card p-4"><h2 class="h3">Archivos y enlaces</h2><p>Se aceptan PDF, DOC/DOCX, ODT, PPT/PPTX, ODP, XLS/XLSX y ODS. El total de documentos e imágenes del editor no puede exceder <strong>10 MiB por propuesta</strong>.</p><p class="mb-0">Puedes agregar opcionalmente un video de YouTube y una carpeta pública de Google Drive, OneDrive o Dropbox. FlowerFlow no descarga ni indexa esas carpetas.</p></article></div><div class="col-lg-5"><article class="card ff-card p-4"><h2 class="h3">Evaluación</h2><ul class="mb-0"><li>Claridad y pertinencia.</li><li>Impacto positivo para Hermosillo.</li><li>Viabilidad de la propuesta.</li><li>Originalidad y sostenibilidad.</li></ul></article></div></div></div></section>

<section class="ff-section bg-white"><div class="container"><div class="row align-items-center g-4"><div class="col-lg-7"><p class="ff-kicker">Premio</p><h2 class="display-5 fw-bold">Apple iPad Pro</h2><p class="lead">Se entregará un premio por categoría. Cuando gane un equipo, se entrega un solo premio al equipo ganador.</p></div><div class="col-lg-5"><div class="ff-prize p-4 rounded-4"><strong>Máximo de ganadores</strong><p class="display-5 fw-bold mb-0">3</p><span>Una categoría puede declararse desierta.</span></div></div></div></div></section>

<section class="ff-section"><div class="container"><div class="row g-5"><div class="col-lg-6"><p class="ff-kicker">Información legal</p><h2>Documentos de la convocatoria</h2><div class="list-group mt-4"><a class="list-group-item list-group-item-action" href="{{ asset('documentos/2026/01_Mecanica_Convocatoria_Hermosillo_Florece_2026.pdf') }}">Mecánica y convocatoria (PDF)</a><a class="list-group-item list-group-item-action" href="{{ asset('documentos/2026/02_Terminos_y_Condiciones_Plataforma_Flower_Flow_2026.pdf') }}">Términos y condiciones (PDF)</a><a class="list-group-item list-group-item-action" href="{{ asset('documentos/2026/03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026.pdf') }}">Aviso de privacidad integral (PDF)</a></div></div><div class="col-lg-6"><p class="ff-kicker">Preguntas frecuentes</p><div class="accordion" id="faq"><div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button" data-bs-toggle="collapse" data-bs-target="#faq1">¿Participar tiene costo?</button></h3><div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faq"><div class="accordion-body">No. La participación es gratuita.</div></div></div><div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#faq2">¿Un borrador ya cuenta como envío?</button></h3><div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faq"><div class="accordion-body">No. Sólo una propuesta finalizada genera folio y confirmación.</div></div></div><div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#faq3">¿Dónde resuelvo dudas?</button></h3><div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faq"><div class="accordion-body">Escribe a <a href="mailto:convocatoria@flowerflow.com.mx">convocatoria@flowerflow.com.mx</a>.</div></div></div></div></div></div></div></section>
@endsection
