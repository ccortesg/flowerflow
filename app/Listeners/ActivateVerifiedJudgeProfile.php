<?php

namespace App\Listeners;

use App\Actions\Judges\SynchronizeJudgeProfileActivation;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

class ActivateVerifiedJudgeProfile
{
    public function __construct(private SynchronizeJudgeProfileActivation $synchronizeActivation) {}

    public function handle(Verified $event): void
    {
        if ($event->user instanceof User) {
            $this->synchronizeActivation->execute($event->user);
        }
    }
}
