<?php

namespace App\Exceptions;

use RuntimeException;

class StaleEvaluationDraft extends RuntimeException
{
    public function __construct(
        public readonly int $persistedLockVersion,
    ) {
        parent::__construct('Otra pestaña guardó este borrador. Recarga la asignación antes de volver a guardar.');
    }
}
