@php
  $steps = [
      1 => ['label' => 'Modalidad y categoría', 'short' => 'Modalidad', 'description' => 'Datos iniciales'],
      2 => ['label' => 'Proyecto', 'short' => 'Proyecto', 'description' => 'Contenido de la idea'],
      3 => ['label' => 'Archivos y enlaces', 'short' => 'Archivos', 'description' => 'Documentos de apoyo'],
      4 => ['label' => 'Revisión y envío', 'short' => 'Revisión', 'description' => 'Confirmación final'],
  ];
@endphp

<nav class="ff-wizard-stepper" aria-label="Progreso de la propuesta">
  <p class="visually-hidden">Paso {{ $currentStep }} de 4: {{ $steps[$currentStep]['label'] }}</p>
  <ol>
    @foreach($steps as $number => $step)
      @php
        $isCurrent = $number === $currentStep;
        $isComplete = $number < $currentStep;
        $stepUrl = $submission->exists && $submission->isDraft() && $number <= 3
            ? route('submissions.edit', ['submission' => $submission, 'step' => $number])
            : null;
      @endphp
      <li @class(['is-current' => $isCurrent, 'is-complete' => $isComplete]) @if($isCurrent) aria-current="step" @endif>
        @if($isComplete && $stepUrl)
          <a href="{{ $stepUrl }}" data-wizard-navigation aria-label="Volver al paso {{ $number }}: {{ $step['label'] }}; completado">
        @else
          <div>
        @endif
            <span class="ff-wizard-step-number" aria-hidden="true">
              @if($isComplete)<span class="ri ri-check-line"></span>@else{{ $number }}@endif
            </span>
            <span class="ff-wizard-step-copy">
              <strong>
                <span class="ff-wizard-step-label-full">{{ $step['label'] }}</span>
                <span class="ff-wizard-step-label-short">{{ $step['short'] }}</span>
              </strong>
              <small>{{ $step['description'] }}</small>
              @if($isComplete)<span class="visually-hidden">Completado</span>@endif
            </span>
        @if($isComplete && $stepUrl)
          </a>
        @else
          </div>
        @endif
      </li>
    @endforeach
  </ol>
</nav>
