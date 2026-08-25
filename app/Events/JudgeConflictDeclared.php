<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class JudgeConflictDeclared implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $conflictId,
        public readonly int $assignmentId,
        public readonly int $actorUserId,
    ) {}
}
