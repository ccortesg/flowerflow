<?php

namespace App\Services;

use App\Actions\Evaluations\EnsureEvaluationDraftContext;
use App\Enums\BlindReviewPackageStatus;
use App\Enums\EvaluationRevisionStatus;
use App\Enums\EvaluationStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Exceptions\EvaluationDraftRejected;
use App\Models\Evaluation;
use App\Models\JudgeAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class JudgeEvaluationWorkspace
{
    public function __construct(
        private EnsureEvaluationDraftContext $evaluationContext,
        private EvaluationDraftCalculator $calculator,
    ) {}

    /**
     * @return array{
     *   evaluation:?Evaluation,
     *   canStartEvaluation:bool,
     *   evaluationReadOnly:bool,
     *   evaluationUnavailable:bool,
     *   evaluationUnavailableReason:?string,
     *   evaluationTotalDisplay:?string,
     *   evaluationComplete:bool,
     *   hasSubmittedRevision:bool
     * }
     */
    public function inspect(JudgeAssignment $assignment, User $actor): array
    {
        $package = $assignment->submissionVersion->blindReviewPackage;
        $packageIsActive = $assignment->status === JudgeAssignmentStatus::Active
            && $package?->status === BlindReviewPackageStatus::Active;
        $evaluation = null;
        $canStartEvaluation = false;
        $evaluationReadOnly = false;
        $evaluationUnavailable = false;
        $evaluationUnavailableReason = null;
        $evaluationTotalDisplay = null;
        $evaluationComplete = false;
        $hasSubmittedRevision = false;
        $existing = Evaluation::query()
            ->where('judge_assignment_id', $assignment->id)
            ->first();

        if ($existing && (! $packageIsActive || Gate::forUser($actor)->denies('viewEvaluationDraft', $assignment))) {
            $evaluationUnavailable = true;
            $evaluationUnavailableReason = $assignment->status !== JudgeAssignmentStatus::Active
                || $assignment->conflict()->exists()
                ? 'assignment_not_active'
                : 'assignment_package_not_active';

            return compact(
                'evaluation',
                'canStartEvaluation',
                'evaluationReadOnly',
                'evaluationUnavailable',
                'evaluationUnavailableReason',
                'evaluationTotalDisplay',
                'evaluationComplete',
                'hasSubmittedRevision',
            );
        }

        if ($packageIsActive && Gate::forUser($actor)->allows('viewEvaluationDraft', $assignment)) {
            try {
                $context = $this->evaluationContext->execute($assignment, $actor, $existing === null, false);
                if ($existing) {
                    $this->evaluationContext->assertAggregate(
                        $existing,
                        $context,
                        false,
                        [$existing->status],
                        [$existing->status === EvaluationStatus::Submitted
                            ? EvaluationRevisionStatus::Submitted
                            : EvaluationRevisionStatus::Draft],
                    );
                    $evaluation = $existing->load([
                        'rubricVersion.criteria',
                        'currentRevision.scores.criterion',
                        'revisions.scores.criterion',
                    ]);
                    $hasSubmittedRevision = $evaluation->revisions
                        ->contains(fn ($revision): bool => $revision->status === EvaluationRevisionStatus::Submitted);
                    $evaluationReadOnly = $evaluation->status === EvaluationStatus::Submitted
                        || now('UTC')->greaterThan($assignment->due_at)
                        || ($evaluation->status === EvaluationStatus::Reopened
                            && config('flowerflow.flags.evaluation_finalization') !== true);
                    $evaluationTotalDisplay = $evaluation->currentRevision->total_raw === null
                        ? null
                        : $this->calculator->display($evaluation->currentRevision->total_raw);
                    $evaluationComplete = $this->isComplete($evaluation);
                } else {
                    $canStartEvaluation = Gate::forUser($actor)->allows('startEvaluationDraft', $assignment);
                }
            } catch (EvaluationDraftRejected $exception) {
                $evaluationUnavailable = $existing !== null;
                $evaluationUnavailableReason = $exception->reasonCode;
            }
        }

        return compact(
            'evaluation',
            'canStartEvaluation',
            'evaluationReadOnly',
            'evaluationUnavailable',
            'evaluationUnavailableReason',
            'evaluationTotalDisplay',
            'evaluationComplete',
            'hasSubmittedRevision',
        );
    }

    public function isComplete(Evaluation $evaluation): bool
    {
        $revision = $evaluation->currentRevision;
        $comment = preg_replace('/^\s+|\s+$/u', '', (string) $revision->general_comment) ?? '';

        return $revision->total_raw !== null
            && $revision->scores->whereNull('score')->isEmpty()
            && mb_strlen($comment) >= 100
            && mb_strlen($comment) <= 2000;
    }
}
