@php
  $wizardEvaluation = $evaluation ?? null;
  $wizardComplete = $evaluationComplete ?? false;
  $wizardSubmitted = $wizardEvaluation?->status === \App\Enums\EvaluationStatus::Submitted;
  $wizardEditable = $wizardEvaluation && ! ($evaluationReadOnly ?? false);
  $steps = [
    1 => ['short' => 'Inicio', 'label' => 'Inicio', 'route' => route('judge.assignments.show', $assignment), 'enabled' => true],
    2 => ['short' => 'Proyecto', 'label' => 'Proyecto asignado', 'route' => $wizardEvaluation ? route('judge.assignments.project.show', $assignment) : null, 'enabled' => (bool) $wizardEvaluation],
    3 => ['short' => 'Evaluar', 'label' => 'Evaluación', 'route' => $wizardEvaluation ? route('judge.assignments.evaluation.show', $assignment) : null, 'enabled' => (bool) $wizardEvaluation],
    4 => ['short' => 'Enviar', 'label' => 'Revisar y enviar', 'route' => $wizardEvaluation && $wizardComplete && ! $wizardSubmitted ? route('judge.assignments.evaluation.confirm', $assignment) : null, 'enabled' => (bool) ($wizardEvaluation && ($wizardComplete || $wizardSubmitted))],
  ];
@endphp
<nav class="ff-evaluation-stepper" aria-label="Pasos de la evaluación">
  <ol>
    @foreach($steps as $number => $step)
      @php($isCurrent = $currentStep === $number)
      @php($isCompleted = $number < $currentStep || ($number === 4 && $wizardSubmitted))
      <li class="{{ $isCurrent ? 'is-current' : '' }} {{ $isCompleted ? 'is-completed' : '' }} {{ ! $step['enabled'] ? 'is-pending' : '' }}" data-wizard-step="{{ $number }}" @if($number === 4 && $wizardEvaluation) data-review-url="{{ route('judge.assignments.evaluation.confirm', $assignment) }}" @endif>
        @if($step['enabled'] && $step['route'] && ! $isCurrent)
          <a href="{{ $step['route'] }}" @if($currentStep === 3 && $number === 2 && $wizardEditable) data-save-before-navigation @endif>
            <span class="ff-step-number" aria-hidden="true">{{ $isCompleted ? '✓' : $number }}</span>
            <span class="ff-step-label"><span class="d-md-none">{{ $step['short'] }}</span><span class="d-none d-md-inline">{{ $step['label'] }}</span></span>
          </a>
        @else
          <span @if($isCurrent) aria-current="step" @elseif(! $step['enabled']) aria-disabled="true" @endif>
            <span class="ff-step-number" aria-hidden="true">{{ $isCompleted ? '✓' : $number }}</span>
            <span class="ff-step-label"><span class="d-md-none">{{ $step['short'] }}</span><span class="d-none d-md-inline">{{ $step['label'] }}</span></span>
          </span>
        @endif
      </li>
    @endforeach
  </ol>
</nav>
