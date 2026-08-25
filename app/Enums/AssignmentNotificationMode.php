<?php

namespace App\Enums;

enum AssignmentNotificationMode: string
{
    case None = 'none';
    case Individual = 'individual';
    case Bulk = 'bulk';

    public function wasRequested(): bool
    {
        return $this !== self::None;
    }
}
