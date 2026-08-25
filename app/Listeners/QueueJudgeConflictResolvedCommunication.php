<?php

namespace App\Listeners;

use App\Events\JudgeConflictResolved;
use App\Services\EvaluationCommunicationDispatcher;

final class QueueJudgeConflictResolvedCommunication
{
    public function __construct(private EvaluationCommunicationDispatcher $communications) {}

    public function handle(JudgeConflictResolved $event): void
    {
        $this->communications->conflictResolved($event);
    }
}
