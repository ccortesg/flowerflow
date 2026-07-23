<?php

namespace App\Models\Concerns;

use LogicException;

trait ImmutableRecord
{
    protected static function bootImmutableRecord(): void
    {
        static::updating(fn () => throw new LogicException('This record is immutable.'));
        static::deleting(fn () => throw new LogicException('This record is immutable.'));
    }
}
