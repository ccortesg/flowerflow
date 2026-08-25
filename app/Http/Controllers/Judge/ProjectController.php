<?php

namespace App\Http\Controllers\Judge;

use App\Exceptions\BlindReviewPackageRejected;
use App\Http\Controllers\Controller;
use App\Models\JudgeAssignment;
use App\Services\AuditLogger;
use App\Services\BlindReviewProjectResolver;
use App\Services\JudgeEvaluationWorkspace;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function show(
        JudgeAssignment $judgeAssignment,
        BlindReviewProjectResolver $resolver,
        JudgeEvaluationWorkspace $workspace,
        AuditLogger $audit,
    ): View {
        try {
            $package = $resolver->resolve($judgeAssignment, request()->user());
        } catch (BlindReviewPackageRejected $exception) {
            abort($this->statusFor($exception), $exception->getMessage());
        }

        $audit->record('blind_review_package.accessed', $package, request()->user(), [
            'assignment_id' => $judgeAssignment->id,
            'schema_version' => $package->schema_version,
            'payload_sha256' => $package->payload_sha256,
            'status' => $package->status->value,
        ]);
        $judgeAssignment->load([
            'submissionVersion:id,submission_id',
            'submissionVersion.submission:id,category_id',
            'submissionVersion.submission.category:id,name',
            'submissionVersion.blindReviewPackage:id,submission_version_id,status',
        ]);
        $state = $workspace->inspect($judgeAssignment, request()->user());
        if (! $state['evaluation']) {
            abort(409, 'La evaluación iniciada ya no está disponible.');
        }

        return view('judge.assignments.project', [
            'assignment' => $judgeAssignment,
            'package' => $package,
            ...$state,
        ]);
    }

    private function statusFor(BlindReviewPackageRejected $exception): int
    {
        return in_array($exception->reasonCode, [
            'actor_not_authorized',
            'assignment_owner_not_operational',
            'assignment_not_active',
        ], true) ? 403 : 409;
    }
}
