<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class JudgeConflictResolved implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $conflictId,
        public readonly int $outgoingAssignmentId,
        public readonly int $replacementAssignmentId,
        public readonly int $actorUserId,
    ) {}
}
