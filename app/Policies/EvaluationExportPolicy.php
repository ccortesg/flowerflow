<?php

namespace App\Policies;

use App\Models\EvaluationExport;
use App\Models\User;

class EvaluationExportPolicy
{
    public function create(User $user): bool
    {
        return $this->hasExportAccess($user);
    }

    public function view(User $user, EvaluationExport $evaluationExport): bool
    {
        return $evaluationExport->requested_by_user_id === $user->id
            && $this->hasExportAccess($user);
    }

    public function download(User $user, EvaluationExport $evaluationExport): bool
    {
        return $this->view($user, $evaluationExport);
    }

    private function hasExportAccess(User $user): bool
    {
        return $user->hasExactRoles(['admin'])
            && $user->can('view evaluations')
            && $user->can('view submissions')
            && $user->can('export evaluations');
    }
}
