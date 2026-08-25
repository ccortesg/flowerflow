<?php

namespace App\Actions\Evaluations;

use App\Enums\EvaluationRevisionStatus;
use App\Enums\EvaluationStatus;
use App\Exceptions\EvaluationDraftRejected;
use App\Exceptions\StaleEvaluationDraft;
use App\Models\Evaluation;
use App\Models\EvaluationReopening;
use App\Models\EvaluationRevision;
use App\Models\EvaluationScore;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EvaluationWindow;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReopenEvaluation
{
    public function __construct(
        private EnsureEvaluationDraftContext $ensureContext,
        private EvaluationWindow $window,
        private AuditLogger $audit,
    ) {}

    public function execute(Evaluation $evaluation, User $actor, int $lockVersion, string $reason): Evaluation
    {
        $reason = preg_replace('/^\s+|\s+$/u', '', $reason) ?? trim($reason);
        $reasonLength = mb_strlen($reason);
        if ($reasonLength < 20 || $reasonLength > 1000) {
            throw ValidationException::withMessages(['reason' => 'El motivo debe contener entre 20 y 1,000 caracteres.']);
        }

        try {
            return DB::transaction(function () use ($evaluation, $actor, $lockVersion, $reason, $reasonLength): Evaluation {
                $context = $this->ensureContext->executeForAdmin(
                    $evaluation->judgeAssignment,
                    $actor,
                    'reopen evaluations',
                    true,
                    true,
                );
                $lockedEvaluation = Evaluation::query()->whereKey($evaluation->id)->lockForUpdate()->firstOrFail();
                if ($lockedEvaluation->lock_version !== $lockVersion) {
                    throw new StaleEvaluationDraft($lockedEvaluation->lock_version);
                }
                $this->window->assertReopeningOpen();
                $aggregate = $this->ensureContext->assertAggregate(
                    $lockedEvaluation,
                    $context,
                    true,
                    [EvaluationStatus::Submitted],
                    [EvaluationRevisionStatus::Submitted],
                );

                $source = $aggregate['revision'];
                if ($source->total_raw === null || $source->submitted_at === null
                    || EvaluationReopening::query()->where('source_revision_id', $source->id)->exists()) {
                    throw new EvaluationDraftRejected('evaluation_revision_not_reopenable', 'La revisión enviada vigente no puede reabrirse.');
                }

                $now = now('UTC');
                $target = new EvaluationRevision;
                $target->forceFill([
                    'evaluation_id' => $lockedEvaluation->id,
                    'revision_number' => $source->revision_number + 1,
                    'status' => EvaluationRevisionStatus::Draft->value,
                    'general_comment' => $source->general_comment,
                    'total_raw' => $source->total_raw,
                    'source_revision_id' => $source->id,
                    'subject_judge_profile_id' => $context['profile']->id,
                    'created_by_user_id' => $actor->id,
                    'last_saved_by_user_id' => $actor->id,
                    'submitted_by_user_id' => null,
                    'submitted_at' => null,
                    'submission_mode' => null,
                ])->save();
                foreach ($aggregate['scores'] as $sourceScore) {
                    $targetScore = new EvaluationScore;
                    $targetScore->forceFill([
                        'evaluation_revision_id' => $target->id,
                        'rubric_criterion_id' => $sourceScore->rubric_criterion_id,
                        'score' => $sourceScore->score,
                        'calculated_component' => $sourceScore->calculated_component,
                        'comment' => $sourceScore->comment,
                    ])->save();
                }

                $reopening = new EvaluationReopening;
                $reopening->forceFill([
                    'evaluation_id' => $lockedEvaluation->id,
                    'source_revision_id' => $source->id,
                    'target_revision_id' => $target->id,
                    'subject_judge_profile_id' => $context['profile']->id,
                    'reopened_by_user_id' => $actor->id,
                    'reason' => $reason,
                    'reason_plaintext_length' => $reasonLength,
                    'reopened_at' => $now,
                ])->save();

                $newLock = $lockedEvaluation->lock_version + 1;
                $updated = DB::table('evaluations')->where('id', $lockedEvaluation->id)
                    ->where('lock_version', $lockedEvaluation->lock_version)
                    ->update([
                        'current_revision_id' => $target->id,
                        'status' => EvaluationStatus::Reopened->value,
                        'lock_version' => $newLock,
                        'updated_at' => $now,
                    ]);
                if ($updated !== 1) {
                    throw new StaleEvaluationDraft($lockedEvaluation->lock_version);
                }

                $this->audit->record('evaluation.reopened', $lockedEvaluation, $actor, [
                    'evaluation_id' => $lockedEvaluation->id,
                    'assignment_id' => $context['assignment']->id,
                    'subject_judge_profile_id' => $context['profile']->id,
                    'source_revision_id' => $source->id,
                    'target_revision_id' => $target->id,
                    'revision_number_source' => $source->revision_number,
                    'revision_number_new' => $target->revision_number,
                    'status_previous' => EvaluationStatus::Submitted->value,
                    'status_new' => EvaluationStatus::Reopened->value,
                    'lock_version_previous' => $lockedEvaluation->lock_version,
                    'lock_version_new' => $newLock,
                    'captured_criteria_count' => $aggregate['scores']->whereNotNull('score')->count(),
                    'is_complete' => $target->total_raw !== null,
                ]);

                return $lockedEvaluation->fresh();
            }, 5);
        } catch (StaleEvaluationDraft $exception) {
            $this->audit->record('evaluation.reopen_rejected_stale', $evaluation, $actor, [
                'evaluation_id' => $evaluation->id,
                'assignment_id' => $evaluation->judge_assignment_id,
                'lock_version_previous' => $exception->persistedLockVersion,
                'reason_code' => 'stale_lock_version',
            ]);
            throw $exception;
        } catch (EvaluationDraftRejected $exception) {
            $this->audit->record('evaluation.reopen_rejected', $evaluation, $actor, [
                'evaluation_id' => $evaluation->id,
                'assignment_id' => $evaluation->judge_assignment_id,
                'reason_code' => $exception->reasonCode,
            ]);
            throw ValidationException::withMessages(['evaluation' => $exception->getMessage()]);
        }
    }
}
