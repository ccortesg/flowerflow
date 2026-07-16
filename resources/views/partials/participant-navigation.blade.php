@php
  $mobile = $mobile ?? false;
  $routeSubmission = request()->route('submission');
  $isDraftSubmission = $routeSubmission instanceof \App\Models\Submission && $routeSubmission->isDraft();
  $isNewProposalActive = request()->routeIs('submissions.create', 'submissions.edit')
      || (request()->routeIs('submissions.show') && $isDraftSubmission);
  $canCreateAnother = $participantSubmissionCount < $participantSubmissionLimit;
  $showNewProposal = $participantReceptionOpen
      && $participantHasActiveCompetition
      && ($isNewProposalActive || ($participantProfileComplete && $canCreateAnother));
  $newProposalUrl = request()->routeIs('submissions.edit') && $isDraftSubmission
      ? route('submissions.edit', ['submission' => $routeSubmission, 'step' => request()->integer('step', 1)])
      : (request()->routeIs('submissions.show') && $isDraftSubmission
          ? route('submissions.show', $routeSubmission)
          : route('submissions.create'));
@endphp

<div class="ff-participant-navigation">
  <a class="ff-participant-brand" href="{{ route('dashboard') }}" @if($mobile) data-bs-dismiss="offcanvas" @endif>
    <span class="ff-participant-brand-logos" aria-hidden="true">
      <img src="{{ asset('assets/flowerflow/logo_flowerflow_transparente.png') }}" width="58" height="58" alt="">
      <span></span>
      <img src="{{ asset('assets/flowerflow/logo_florecehermosillo_transparente.png') }}" width="58" height="58" alt="">
    </span>
    <span class="ff-participant-brand-name">Hermosillo Florece 2026</span>
  </a>

  <nav class="ff-participant-menu" aria-label="Menú participante" data-testid="participant-menu">
    <a href="{{ route('dashboard') }}" data-participant-nav-item="dashboard" @if(request()->routeIs('dashboard')) aria-current="page" @endif @if($mobile) data-bs-dismiss="offcanvas" @endif>
      <span class="ri ri-home-5-line" aria-hidden="true"></span>
      <span>Inicio</span>
    </a>
    <a href="{{ route('submissions.index') }}" data-participant-nav-item="submissions" @if(request()->routeIs('submissions.index') || (request()->routeIs('submissions.show') && ! $isDraftSubmission)) aria-current="page" @endif @if($mobile) data-bs-dismiss="offcanvas" @endif>
      <span class="ri ri-file-list-3-line" aria-hidden="true"></span>
      <span>Mis propuestas</span>
    </a>
    @if($showNewProposal)
      <a href="{{ $newProposalUrl }}" data-participant-nav-item="create" @if($isNewProposalActive) aria-current="page" @endif @if($mobile) data-bs-dismiss="offcanvas" @endif>
        <span class="ri ri-add-circle-line" aria-hidden="true"></span>
        <span>Nueva propuesta</span>
      </a>
    @endif
    <a href="{{ route('profile.edit') }}" data-participant-nav-item="profile" @if(request()->routeIs('profile.*')) aria-current="page" @endif @if($mobile) data-bs-dismiss="offcanvas" @endif>
      <span class="ri ri-user-3-line" aria-hidden="true"></span>
      <span>Mi perfil</span>
    </a>
  </nav>

  <div class="ff-participant-navigation-footer">
    <div class="ff-participant-help">
      <span class="ri ri-customer-service-2-line" aria-hidden="true"></span>
      <div>
        <strong>¿Necesitas ayuda?</strong>
        <a href="mailto:convocatoria@flowerflow.com.mx">Escríbenos por correo</a>
      </div>
    </div>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="ff-participant-logout" type="submit">
        <span class="ri ri-logout-box-r-line" aria-hidden="true"></span>
        <span>Cerrar sesión</span>
      </button>
    </form>
  </div>
</div>
