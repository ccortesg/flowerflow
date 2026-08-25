<?php

namespace App\Exceptions;

use RuntimeException;

final class BulkJudgeAssignmentRejected extends RuntimeException
{
    public function __construct(
        public readonly string $reasonCode,
        string $message,
    ) {
        parent::__construct($message);
    }
}
