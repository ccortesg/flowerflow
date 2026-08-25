<?php

namespace App\Actions\Evaluations;

use App\Enums\EvaluationRevisionStatus;
use App\Enums\EvaluationStatus;
use App\Exceptions\EvaluationDraftRejected;
use App\Models\Evaluation;
use App\Models\EvaluationRevision;
use App\Models\EvaluationScore;
use App\Models\JudgeAssignment;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OpenEvaluationDraft
{
    public function __construct(
        private EnsureEvaluationDraftContext $ensureContext,
        private AuditLogger $audit,
    ) {}

    public function execute(JudgeAssignment $assignment, User $actor): Evaluation
    {
        try {
            return DB::transaction(function () use ($assignment, $actor): Evaluation {
                $context = $this->ensureContext->execute($assignment, $actor, true, true);
                $existing = Evaluation::query()
                    ->where('judge_assignment_id', $context['assignment']->id)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    $this->ensureContext->assertAggregate($existing, $context, true);

                    return $existing;
                }

                $now = now('UTC');
                $evaluation = new Evaluation;
                $evaluation->forceFill([
                    'judge_assignment_id' => $context['assignment']->id,
                    'rubric_version_id' => $context['rubric']->id,
                    'blind_review_package_id' => $context['package']->id,
                    'current_revision_id' => null,
                    'status' => EvaluationStatus::Draft->value,
                    'lock_version' => 0,
                    'started_by_user_id' => $actor->id,
                    'started_at' => $now,
                ])->save();

                $revision = new EvaluationRevision;
                $revision->forceFill([
                    'evaluation_id' => $evaluation->id,
                    'revision_number' => 1,
                    'status' => EvaluationRevisionStatus::Draft->value,
                    'general_comment' => null,
                    'total_raw' => null,
                    'source_revision_id' => null,
                    'subject_judge_profile_id' => $context['profile']->id,
                    'created_by_user_id' => $actor->id,
                    'last_saved_by_user_id' => $actor->id,
                ])->save();

                foreach ($context['criteria'] as $criterion) {
                    $score = new EvaluationScore;
                    $score->forceFill([
                        'evaluation_revision_id' => $revision->id,
                        'rubric_criterion_id' => $criterion->id,
                        'score' => null,
                        'calculated_component' => null,
                        'comment' => null,
                    ])->save();
                }

                DB::table('evaluations')->where('id', $evaluation->id)->update([
                    'current_revision_id' => $revision->id,
                    'updated_at' => $now,
                ]);
                $evaluation->refresh();
                $this->ensureContext->assertAggregate($evaluation, $context, true);
                $this->audit->record('evaluation.draft_opened', $evaluation, $actor, [
                    'assignment_id' => $context['assignment']->id,
                    'rubric_version_id' => $context['rubric']->id,
                    'blind_review_package_id' => $context['package']->id,
                    'revision_number' => 1,
                    'lock_version_new' => 0,
                    'captured_criteria_count' => 0,
                    'is_complete' => false,
                ]);

                return $evaluation;
            }, 5);
        } catch (EvaluationDraftRejected $exception) {
            $this->audit->record('evaluation.draft_open_rejected', $assignment, $actor, [
                'assignment_id' => $assignment->id,
                'reason_code' => $exception->reasonCode,
            ]);

            throw ValidationException::withMessages(['evaluation' => $exception->getMessage()]);
        }
    }
}
