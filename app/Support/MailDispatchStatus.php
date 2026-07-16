<?php

namespace App\Support;

final class MailDispatchStatus
{
    private ?string $warning = null;

    public function markFailed(string $warning): void
    {
        $this->warning ??= $warning;
    }

    public function warning(): ?string
    {
        return $this->warning;
    }

    public function failed(): bool
    {
        return $this->warning !== null;
    }
}
