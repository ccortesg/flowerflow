<?php

namespace App\Actions\Judges;

use App\Models\JudgeProfile;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

final class InitializeJudgePassword
{
    public function __construct(
        private AuditLogger $audit,
        private SynchronizeJudgeProfileActivation $synchronizeActivation,
    ) {}

    public function execute(User $user): bool
    {
        $initialized = DB::transaction(function () use ($user): bool {
            $lockedUser = User::query()->lockForUpdate()->find($user->getKey());
            if (! $lockedUser?->hasExactRoles(['judge'])) {
                return false;
            }

            $profile = JudgeProfile::query()->where('user_id', $lockedUser->id)->lockForUpdate()->first();
            if (! $profile || $profile->password_initialized_at) {
                return false;
            }

            $profile->forceFill(['password_initialized_at' => now('UTC')])->save();
            $this->audit->record('judge.password_initialized', $profile, $lockedUser);

            return true;
        });

        $this->synchronizeActivation->execute($user);

        return $initialized;
    }
}
