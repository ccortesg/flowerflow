<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class EvaluationReopened implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $evaluationId,
        public readonly int $reopeningId,
        public readonly int $sourceRevisionId,
        public readonly int $targetRevisionId,
        public readonly int $actorUserId,
    ) {}
}
