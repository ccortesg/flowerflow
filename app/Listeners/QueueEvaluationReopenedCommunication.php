<?php

namespace App\Listeners;

use App\Events\EvaluationReopened;
use App\Services\EvaluationCommunicationDispatcher;

final class QueueEvaluationReopenedCommunication
{
    public function __construct(private EvaluationCommunicationDispatcher $communications) {}

    public function handle(EvaluationReopened $event): void
    {
        $this->communications->evaluationReopened($event);
    }
}
