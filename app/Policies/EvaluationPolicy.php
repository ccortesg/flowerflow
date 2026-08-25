<?php

namespace App\Policies;

use App\Enums\EvaluationStatus;
use App\Models\Evaluation;
use App\Models\User;

class EvaluationPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdminWith($user, 'view evaluations');
    }

    public function view(User $user, Evaluation $evaluation): bool
    {
        return $this->isAdminWith($user, 'view evaluations');
    }

    public function reopen(User $user, Evaluation $evaluation): bool
    {
        return $this->isAdminWith($user, 'reopen evaluations')
            && $evaluation->status === EvaluationStatus::Submitted;
    }

    public function manageReopened(User $user, Evaluation $evaluation): bool
    {
        return $this->isAdminWith($user, 'manage reopened evaluations')
            && $evaluation->status === EvaluationStatus::Reopened;
    }

    private function isAdminWith(User $user, string $permission): bool
    {
        return $user->hasExactRoles(['admin']) && $user->can($permission);
    }
}
