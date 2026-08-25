<?php

namespace App\Listeners;

use App\Events\EvaluationSubmitted;
use App\Services\EvaluationCommunicationDispatcher;

final class QueueEvaluationSubmittedCommunications
{
    public function __construct(private EvaluationCommunicationDispatcher $communications) {}

    public function handle(EvaluationSubmitted $event): void
    {
        $this->communications->evaluationSubmitted($event);
    }
}
