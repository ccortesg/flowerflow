<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class EvaluationSubmitted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $evaluationId,
        public readonly int $revisionId,
        public readonly int $actorUserId,
        public readonly string $mode,
    ) {}
}
