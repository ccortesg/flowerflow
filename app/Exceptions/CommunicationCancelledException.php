<?php

namespace App\Exceptions;

use RuntimeException;

class CommunicationCancelledException extends RuntimeException
{
    public function __construct(public readonly string $reasonCode)
    {
        parent::__construct('The communication is no longer valid.');
    }
}
