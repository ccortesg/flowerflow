<?php

namespace App\Listeners;

use App\Events\JudgeConflictDeclared;
use App\Services\EvaluationCommunicationDispatcher;

final class QueueJudgeConflictDeclaredCommunication
{
    public function __construct(private EvaluationCommunicationDispatcher $communications) {}

    public function handle(JudgeConflictDeclared $event): void
    {
        $this->communications->conflictDeclared($event);
    }
}
